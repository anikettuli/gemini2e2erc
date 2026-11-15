/**
 * v3 Drop-Off Locator Module
 * Powers Fuse.js fuzzy search + 34-city filter + Near Me geolocation +
 * MapLibre GL v6.18.0 Supercluster map on `#drop-off`.
 */

import * as maplibregl from '/vendor/maplibre-gl/maplibre-gl.mjs';
import Fuse from '/vendor/fuse/fuse.min.mjs';
import { ercIconElement } from '/vendor/lucide/icons.mjs';

const STYLES = Object.freeze({
  light: 'https://tiles.openfreemap.org/styles/positron',
  dark: 'https://tiles.openfreemap.org/styles/dark',
});

// Optical Cobalt (#1D4E89 light / #6BA6F2 dark) + Lions Gold HQ accent
// (MapLibre GL JS v6.18.0 shader paints require hex/rgb strings rather than oklch()).
const MAP_THEME_PALETTE = Object.freeze({
  light: {
    pinFill: '#1D4E89',
    pinStroke: '#FFFFFF',
    clusterSmall: '#1D4E89',
    clusterMedium: '#163D6B',
    clusterLarge: '#0F2B4D',
    clusterText: '#FFFFFF',
    clusterStroke: '#FFFFFF',
    hqHalo: '#E6B84C',
    hqCore: '#1D4E89',
    hqStroke: '#FFFFFF',
    selectedRing: '#1D4E89',
  },
  dark: {
    pinFill: '#6BA6F2',
    pinStroke: '#0E141B',
    clusterSmall: '#6BA6F2',
    clusterMedium: '#88B8F6',
    clusterLarge: '#A6CBFA',
    clusterText: '#0B121B',
    clusterStroke: '#0E141B',
    hqHalo: '#DFB34B',
    hqCore: '#6BA6F2',
    hqStroke: '#0E141B',
    selectedRing: '#FFFFFF',
  },
});

const DEFAULT_CENTER = [-97.2620445, 32.8707405];
const SOURCE_BINS = 'dropoff-bins';
const SOURCE_HQ = 'watauga-hq';
const SOURCE_SELECTED = 'dropoff-selected';
const MOBILE_INITIAL_COUNT = 8;
const MOBILE_PAGE_INCREMENT = 16;
const MOBILE_SHOW_ALL_THRESHOLD = 20;

function getResolvedTheme() {
  const attr = (document.documentElement.getAttribute('data-theme') || '').trim().toLowerCase();
  if (attr === 'dark' || attr === 'light') return attr;
  const meta = document.querySelector('meta[name="color-scheme"]');
  const content = (meta?.getAttribute('content') || 'light dark').trim().toLowerCase();
  if (content === 'dark') return 'dark';
  if (content === 'light') return 'light';
  return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function prefersReducedMotion() {
  return Boolean(window.matchMedia?.('(prefers-reduced-motion: reduce)').matches);
}

function cleanAddress(rawAddress) {
  return String(rawAddress || '')
    .replace(/,,+/g, ',')
    .replace(/\s+/g, ' ')
    .trim();
}

function extractZip(address) {
  const txMatch = address.match(/\b(7\d{4})\b/);
  if (txMatch) return txMatch[1];
  const anyMatch = address.match(/\b(\d{5})\b/);
  return anyMatch ? anyMatch[1] : '';
}

function escapeRegExp(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function discoverCities(rawLocations) {
  const cities = [];
  for (const row of rawLocations) {
    if (!row || typeof row !== 'object') continue;
    const addr = String(row.address || '').trim();
    const withZip = addr.match(/,\s*([A-Za-z][A-Za-z .'-]+?)(?:\s*,?\s*(?:TX|Texas))?\s*\d{5}\s*$/i);
    const noZip = !withZip ? addr.match(/,\s*([A-Za-z][A-Za-z .'-]+?)\s*,?\s*(?:TX|Texas)\s*$/i) : null;
    const match = withZip || noZip;
    if (match) {
      const candidate = match[1].replace(/[,.]+$/, '').trim();
      if (candidate && !cities.includes(candidate)) {
        cities.push(candidate);
      }
    }
  }
  cities.sort((a, b) => b.length - a.length);
  return cities;
}

function extractCity(name, address, citiesLongestFirst) {
  for (const city of citiesLongestFirst) {
    if (new RegExp(`\\b${escapeRegExp(city)}\\b`, 'i').test(address)) {
      return city;
    }
  }
  for (const city of citiesLongestFirst) {
    if (new RegExp(`\\b${escapeRegExp(city)}\\b`, 'i').test(name)) {
      return city;
    }
  }
  return 'Other';
}

function formatPhoneTel(phone) {
  const digits = String(phone || '').replace(/\D+/g, '');
  if (!digits) return '';
  if (digits.length === 10) return `+1${digits}`;
  if (digits.length === 11 && digits.startsWith('1')) return `+${digits}`;
  return `+${digits}`;
}

function buildDirectionsUrl(loc) {
  let destination;
  if (loc.geocoded && !loc.isPOBox) {
    destination = loc.address || `${loc.lat.toFixed(6)},${loc.lng.toFixed(6)}`;
  } else if (loc.isPOBox) {
    destination = `${loc.name} ${loc.city !== 'Other' ? loc.city : ''} TX ${loc.zip}`.trim();
  } else {
    destination = loc.address || loc.name;
  }
  return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}`;
}

function normalizeLocations(rawList, globalPhone) {
  const citiesLongestFirst = discoverCities(rawList);
  const normalized = [];

  for (const entry of rawList) {
    if (!entry || typeof entry !== 'object') continue;
    const name = String(entry.name || '').trim();
    const rawAddress = String(entry.address || '');
    const address = cleanAddress(rawAddress);
    if (!name && !address) continue;

    const rawPhone = String(entry.phone || '').trim();
    const isMainCenter = /5621\s+Bunker\s+Blvd/i.test(rawAddress);
    const isLionsHost = !isMainCenter && /\blions?\b|\b2-?e2\b/i.test(`${name} ${address}`);
    const isPOBox = /^\s*P\.?\s*O\.?\s*Box\b/i.test(rawAddress);
    const city = extractCity(name, address, citiesLongestFirst);
    const zip = extractZip(address);

    const latNum = entry.lat !== null && entry.lat !== undefined && entry.lat !== '' ? Number(entry.lat) : NaN;
    const lngNum = entry.lng !== null && entry.lng !== undefined && entry.lng !== '' ? Number(entry.lng) : NaN;
    const geocoded = Number.isFinite(latNum) && Number.isFinite(lngNum);

    const phone = isMainCenter && !rawPhone ? globalPhone : rawPhone;
    const tel = phone ? formatPhoneTel(phone) : '';
    const phoneDigits = phone.replace(/\D+/g, '');
    const idx = normalized.length;

    const item = {
      id: idx,
      name,
      address,
      rawAddress,
      city,
      zip,
      phone,
      tel,
      phoneDigits,
      lat: geocoded ? latNum : null,
      lng: geocoded ? lngNum : null,
      geocoded,
      coordKey: geocoded ? `${latNum.toFixed(6)},${lngNum.toFixed(6)}` : '',
      isMainCenter,
      isLionsHost,
      isPOBox,
      distanceMiles: null,
      searchText: `${name} ${address} ${city} ${zip} ${phone} ${phoneDigits}`.toLowerCase(),
      directionsUrl: '',
    };
    item.directionsUrl = buildDirectionsUrl(item);
    normalized.push(item);
  }

  return normalized;
}

function haversineMiles(lat1, lng1, lat2, lng2) {
  const toRad = (deg) => (deg * Math.PI) / 180;
  const rMiles = 3958.8;
  const dLat = toRad(lat2 - lat1);
  const dLng = toRad(lng2 - lng1);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
  return 2 * rMiles * Math.asin(Math.min(1, Math.sqrt(a)));
}

function canCreateWebGLContext() {
  try {
    const canvas = document.createElement('canvas');
    return Boolean(
      (window.WebGL2RenderingContext && canvas.getContext('webgl2')) ||
      (window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')))
    );
  } catch {
    return false;
  }
}

function readSsrSeed() {
  const el = document.getElementById('dropoffLocationsSeed');
  if (!el) return [];
  try {
    const parsed = JSON.parse(el.textContent || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function iconNode(name, size = 16) {
  try {
    return ercIconElement(name, { width: size, height: size, 'aria-hidden': 'true' });
  } catch {
    const span = document.createElement('span');
    span.setAttribute('aria-hidden', 'true');
    return span;
  }
}

export function initDropoffLocator() {
  const appEl = document.getElementById('locApp');
  if (!appEl || appEl.dataset.initialized === 'true') return;
  appEl.dataset.initialized = 'true';

  const mainLat = Number(appEl.dataset.mainLat) || DEFAULT_CENTER[1];
  const mainLng = Number(appEl.dataset.mainLng) || DEFAULT_CENTER[0];
  const globalPhone = appEl.dataset.globalPhone || '(817) 710-5403';
  const desktopMedia = window.matchMedia?.('(min-width: 60rem)');

  const els = {
    filterForm: document.getElementById('locFilterForm'),
    searchInput: document.getElementById('locSearch'),
    searchKbd: document.getElementById('locSearchKbd'),
    searchClear: document.getElementById('locSearchClear'),
    citySelect: document.getElementById('locCitySelect'),
    nearMeBtn: document.getElementById('locNearMe'),
    focusMainBtn: document.getElementById('locFocusMain'),
    resetBtn: document.getElementById('locReset'),
    emptyResetBtn: document.getElementById('locEmptyReset'),
    resultsSummary: document.getElementById('locResultsSummary'),
    statusLive: document.getElementById('locStatusLive'),
    geoBanner: document.getElementById('locGeoBanner'),
    geoMessage: document.getElementById('locGeoMessage'),
    geoActions: document.getElementById('locGeoActions'),
    listPanel: document.getElementById('dropoffListPanel'),
    listEl: document.getElementById('dropoffList'),
    paginationWrap: document.getElementById('dropoffPagination'),
    showMoreBtn: document.getElementById('locShowMore'),
    emptyEl: document.getElementById('dropoffEmpty'),
    mapPanel: document.getElementById('dropoffMapPanel'),
    mapContainer: document.getElementById('dropoffMap'),
    mapFallback: document.getElementById('dropoffMapFallback'),
    mapFallbackMsg: document.getElementById('dropoffMapFallbackMsg'),
    mapRetryBtn: document.getElementById('locMapRetry'),
    selectedCard: document.getElementById('locSelectedCard'),
  };

  const state = {
    allLocations: [],
    filteredLocations: [],
    cityCounts: new Map(),
    fuse: null,
    searchQuery: '',
    cityFilter: 'all',
    userCoords: null,
    selectedLocId: null,
    mobilePageSize: MOBILE_INITIAL_COUNT,
    map: null,
    activePopup: null,
    currentTheme: getResolvedTheme(),
    styleLoaded: false,
    pendingFocusId: null,
    styleLoadTimer: null,
    liveTimer: null,
    searchTimer: null,
  };

  function isDesktopLayout() {
    return desktopMedia ? desktopMedia.matches : window.innerWidth >= 960;
  }

  function announceStatus(message) {
    if (!els.statusLive) return;
    window.clearTimeout(state.liveTimer);
    state.liveTimer = window.setTimeout(() => {
      els.statusLive.textContent = message;
    }, 200);
  }

  function buildFuseIndex(locations) {
    state.fuse = new Fuse(locations, {
      includeScore: true,
      ignoreLocation: true,
      threshold: 0.34,
      minMatchCharLength: 2,
      keys: [
        { name: 'name', weight: 2.2 },
        { name: 'city', weight: 2.0 },
        { name: 'zip', weight: 1.8 },
        { name: 'address', weight: 1.4 },
        { name: 'phone', weight: 0.9 },
        { name: 'phoneDigits', weight: 0.9 },
        { name: 'searchText', weight: 1.6 },
      ],
    });
  }

  function recomputeCityCounts(locations) {
    const counts = new Map();
    for (const loc of locations) {
      counts.set(loc.city, (counts.get(loc.city) || 0) + 1);
    }
    state.cityCounts = new Map([...counts.entries()].sort((a, b) => a[0].localeCompare(b[0])));
  }

  function syncCitySelect() {
    if (!els.citySelect) return;
    const total = state.allLocations.length;
    const cityTotal = state.cityCounts.size;
    const prev = state.cityFilter;

    els.citySelect.textContent = '';
    const allOpt = document.createElement('option');
    allOpt.value = 'all';
    allOpt.textContent = `All ${cityTotal} Cities (${total})`;
    els.citySelect.appendChild(allOpt);

    for (const [city, count] of state.cityCounts.entries()) {
      const opt = document.createElement('option');
      opt.value = city;
      opt.textContent = `${city} (${count})`;
      els.citySelect.appendChild(opt);
    }

    els.citySelect.value = state.cityCounts.has(prev) ? prev : 'all';
    if (els.citySelect.value !== prev) {
      state.cityFilter = 'all';
    }
  }

  function computeFilteredLocations() {
    const queryRaw = state.searchQuery.trim();
    const queryLower = queryRaw.toLowerCase();
    const tokens = queryLower ? queryLower.split(/\s+/).filter(Boolean) : [];
    const digitQuery = queryRaw.replace(/\D+/g, '');

    let pool =
      state.cityFilter === 'all'
        ? state.allLocations.slice()
        : state.allLocations.filter((loc) => loc.city.toLowerCase() === state.cityFilter.toLowerCase());

    if (state.userCoords) {
      for (const loc of pool) {
        loc.distanceMiles = loc.geocoded
          ? haversineMiles(state.userCoords.lat, state.userCoords.lng, loc.lat, loc.lng)
          : null;
      }
    } else {
      for (const loc of pool) {
        loc.distanceMiles = null;
      }
    }

    if (queryLower) {
      const isFiveDigitZip = /^\d{5}$/.test(queryRaw);

      if (isFiveDigitZip) {
        const exactZipMatches = pool.filter((loc) => loc.zip === queryRaw);
        pool =
          exactZipMatches.length > 0
            ? exactZipMatches
            : pool.filter((loc) => loc.address.includes(queryRaw) || loc.zip.startsWith(queryRaw.slice(0, 3)));
      } else {
        const fuseScores = new Map();
        if (state.fuse && queryRaw.length >= 2) {
          for (const hit of state.fuse.search(queryRaw)) {
            fuseScores.set(hit.item.id, hit.score ?? 1);
          }
        }

        const matched = [];
        for (const loc of pool) {
          const allTokensPresent = tokens.every((tok) => loc.searchText.includes(tok));
          const hasDigitMatch = digitQuery.length >= 3 && (loc.zip.includes(digitQuery) || loc.phoneDigits.includes(digitQuery));
          const hasFuse = fuseScores.has(loc.id);

          if (!allTokensPresent && !hasDigitMatch && !hasFuse) continue;

          let tier = 4;
          if (loc.city.toLowerCase() === queryLower || loc.zip === queryLower) {
            tier = 0;
          } else if (loc.name.toLowerCase().startsWith(queryLower)) {
            tier = 1;
          } else if (
            allTokensPresent &&
            (loc.name.toLowerCase().includes(queryLower) || loc.address.toLowerCase().includes(queryLower))
          ) {
            tier = 2;
          } else if (allTokensPresent || hasDigitMatch) {
            tier = 3;
          }

          matched.push({
            loc,
            tier,
            score: fuseScores.get(loc.id) ?? 1,
          });
        }

        matched.sort((a, b) => {
          if (a.tier !== b.tier) return a.tier - b.tier;
          if (a.score !== b.score) return a.score - b.score;
          if (state.userCoords) {
            const da = a.loc.distanceMiles ?? Number.POSITIVE_INFINITY;
            const db = b.loc.distanceMiles ?? Number.POSITIVE_INFINITY;
            if (da !== db) return da - db;
          }
          return a.loc.id - b.loc.id;
        });

        pool = matched.map((m) => m.loc);
      }
    }

    if (!queryLower && state.userCoords) {
      pool.sort((a, b) => {
        const da = a.distanceMiles ?? Number.POSITIVE_INFINITY;
        const db = b.distanceMiles ?? Number.POSITIVE_INFINITY;
        if (da !== db) return da - db;
        return a.id - b.id;
      });
    }

    state.filteredLocations = pool;
  }

  function createCardElement(loc) {
    const li = document.createElement('li');
    li.className = 'dropoff-list-item';

    const article = document.createElement('article');
    article.className = 'loc-card';
    if (loc.isMainCenter) article.classList.add('loc-card--hq');
    if (!loc.geocoded) article.classList.add('loc-card--directory-only');
    if (state.selectedLocId === loc.id) article.classList.add('is-selected');
    article.dataset.locId = String(loc.id);

    const mainCol = document.createElement('div');
    mainCol.className = 'loc-card__main';

    const topRow = document.createElement('div');
    topRow.className = 'loc-card__top';

    const title = document.createElement('h3');
    title.className = 'loc-card__title';
    title.textContent = loc.name;

    const badges = document.createElement('div');
    badges.className = 'loc-card__badges';

    if (loc.isMainCenter) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--hq';
      b.textContent = 'Watauga HQ';
      badges.appendChild(b);
    }
    if (loc.isLionsHost) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--lions';
      b.textContent = 'Lions Club';
      badges.appendChild(b);
    }
    if (loc.distanceMiles !== null && Number.isFinite(loc.distanceMiles)) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--distance';
      b.textContent = `${loc.distanceMiles.toFixed(1)} mi`;
      badges.appendChild(b);
    }
    if (!loc.geocoded) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--dir-only';
      b.textContent = 'Directory Only';
      badges.appendChild(b);
    }

    topRow.append(title);
    if (badges.childElementCount > 0) {
      topRow.append(badges);
    }

    const meta = document.createElement('p');
    meta.className = 'loc-card__meta';
    meta.textContent = loc.address;
    if (loc.phone) {
      const phoneSpan = document.createElement('span');
      phoneSpan.className = 'loc-card__phone';
      phoneSpan.textContent = ` · ${loc.phone}`;
      meta.appendChild(phoneSpan);
    }

    mainCol.append(topRow, meta);

    const actions = document.createElement('div');
    actions.className = 'loc-card__actions';

    if (loc.geocoded) {
      const mapBtn = document.createElement('button');
      mapBtn.type = 'button';
      mapBtn.className = 'loc-card-btn';
      mapBtn.dataset.action = 'view-on-map';
      mapBtn.dataset.locId = String(loc.id);
      mapBtn.setAttribute('aria-label', `View ${loc.name} on map`);

      const fullLbl = document.createElement('span');
      fullLbl.className = 'loc-card-btn__label-full';
      fullLbl.textContent = 'View on Map';

      const shortLbl = document.createElement('span');
      shortLbl.className = 'loc-card-btn__label-short';
      shortLbl.textContent = 'Map';

      mapBtn.append(iconNode('map-pin', 14), fullLbl, shortLbl);
      actions.appendChild(mapBtn);
    }

    const dirA = document.createElement('a');
    dirA.className = 'loc-card-btn loc-card-btn--primary';
    dirA.href = loc.directionsUrl;
    dirA.target = '_blank';
    dirA.rel = 'noopener noreferrer';
    dirA.setAttribute('aria-label', `Directions to ${loc.name} (opens in new tab)`);

    const dirFull = document.createElement('span');
    dirFull.className = 'loc-card-btn__label-full';
    dirFull.textContent = 'Directions';

    const dirShort = document.createElement('span');
    dirShort.className = 'loc-card-btn__label-short';
    dirShort.textContent = 'Go';

    dirA.append(dirFull, dirShort, iconNode('arrow-up-right', 14));
    actions.appendChild(dirA);

    article.append(mainCol, actions);
    li.appendChild(article);
    return li;
  }

  function renderDirectory() {
    if (!els.listEl) return;

    const totalAll = state.allLocations.length;
    const matched = state.filteredLocations;
    const desktop = isDesktopLayout();

    let visibleList = matched;
    if (!desktop && matched.length > MOBILE_SHOW_ALL_THRESHOLD) {
      visibleList = matched.slice(0, state.mobilePageSize);
    }

    const fragment = document.createDocumentFragment();
    for (const loc of visibleList) {
      fragment.appendChild(createCardElement(loc));
    }

    els.listEl.textContent = '';
    els.listEl.appendChild(fragment);

    if (els.paginationWrap && els.showMoreBtn) {
      const remaining = matched.length - visibleList.length;
      if (!desktop && remaining > 0) {
        const nextBatch = Math.min(MOBILE_PAGE_INCREMENT, remaining);
        els.showMoreBtn.textContent = `Show More Locations (+${nextBatch})`;
        els.paginationWrap.hidden = false;
      } else {
        els.paginationWrap.hidden = true;
      }
    }

    if (els.emptyEl) {
      els.emptyEl.hidden = matched.length > 0;
    }

    if (els.resultsSummary) {
      const citiesInResult = new Set(matched.map((l) => l.city)).size;
      const dirOnlyCount = matched.filter((l) => !l.geocoded).length;

      if (state.cityFilter !== 'all') {
        els.resultsSummary.textContent =
          dirOnlyCount > 0
            ? `Showing ${matched.length} locations in ${state.cityFilter} (${dirOnlyCount} directory only)`
            : `Showing ${matched.length} ${matched.length === 1 ? 'location' : 'locations'} in ${state.cityFilter}`;
      } else if (state.searchQuery.trim() !== '') {
        els.resultsSummary.textContent = `Showing ${matched.length} of ${totalAll} locations across ${citiesInResult} ${citiesInResult === 1 ? 'city' : 'cities'}`;
      } else {
        els.resultsSummary.textContent = `Showing ${matched.length} locations across ${citiesInResult} cities`;
      }
    }

    const hasQuery = Boolean(state.searchQuery.trim());
    if (els.resetBtn) {
      els.resetBtn.hidden = !(hasQuery || state.cityFilter !== 'all' || state.userCoords);
    }
    if (els.searchClear) {
      els.searchClear.hidden = !hasQuery;
    }
    if (els.searchKbd) {
      els.searchKbd.hidden = hasQuery;
    }

    announceStatus(els.resultsSummary?.textContent || `Showing ${matched.length} locations.`);
  }

  function buildGeoJsonCollections() {
    const partnerFeatures = [];
    const hqFeatures = [];
    const selectedFeatures = [];

    for (const loc of state.filteredLocations) {
      if (!loc.geocoded) continue;
      const feature = {
        type: 'Feature',
        geometry: {
          type: 'Point',
          coordinates: [loc.lng, loc.lat],
        },
        properties: {
          id: loc.id,
          name: loc.name,
          address: loc.address,
          city: loc.city,
          zip: loc.zip,
          phone: loc.phone,
          tel: loc.tel,
          isMainCenter: loc.isMainCenter,
          coordKey: loc.coordKey,
        },
      };
      if (loc.isMainCenter) {
        hqFeatures.push(feature);
      } else {
        partnerFeatures.push(feature);
      }
      if (state.selectedLocId === loc.id) {
        selectedFeatures.push(feature);
      }
    }

    return {
      partners: { type: 'FeatureCollection', features: partnerFeatures },
      hq: { type: 'FeatureCollection', features: hqFeatures },
      selected: { type: 'FeatureCollection', features: selectedFeatures },
    };
  }

  function updateMapSources() {
    if (!state.map || !state.styleLoaded) return;
    const collections = buildGeoJsonCollections();
    state.map.getSource(SOURCE_BINS)?.setData?.(collections.partners);
    state.map.getSource(SOURCE_HQ)?.setData?.(collections.hq);
    state.map.getSource(SOURCE_SELECTED)?.setData?.(collections.selected);
  }

  function buildLocationDetailDom(locationsAtPoint) {
    const wrapper = document.createElement('div');
    wrapper.className = 'loc-popup';

    locationsAtPoint.forEach((loc) => {
      const entry = document.createElement('div');
      entry.className = 'loc-popup-entry';

      const cityLine = document.createElement('p');
      cityLine.className = 'loc-popup-serial';
      cityLine.textContent = loc.isMainCenter ? `Watauga HQ · ${loc.city}` : loc.city;
      entry.appendChild(cityLine);

      const title = document.createElement('h3');
      title.className = 'loc-popup-title';
      title.textContent = loc.name;
      entry.appendChild(title);

      if (loc.isMainCenter) {
        const hqNote = document.createElement('p');
        hqNote.className = 'loc-popup-note';
        hqNote.textContent =
          '24/7 East-Side Walkway Drop Bin · Center open Sat 9 AM–3 PM (Mon–Fri by appt).';
        entry.appendChild(hqNote);
      }

      const addr = document.createElement('p');
      addr.className = 'loc-popup-addr';
      addr.textContent = loc.address;
      entry.appendChild(addr);

      const actions = document.createElement('div');
      actions.className = 'loc-popup-actions';

      if (loc.phone && loc.tel) {
        const callA = document.createElement('a');
        callA.className = 'loc-popup-btn';
        callA.href = `tel:${loc.tel}`;
        const callTxt = document.createElement('span');
        callTxt.textContent = loc.phone;
        callA.append(iconNode('phone', 15), callTxt);
        actions.appendChild(callA);
      }

      const dirA = document.createElement('a');
      dirA.className = 'loc-popup-btn loc-popup-btn--primary';
      dirA.href = loc.directionsUrl;
      dirA.target = '_blank';
      dirA.rel = 'noopener noreferrer';
      dirA.setAttribute('aria-label', `Directions to ${loc.name} (opens in new tab)`);
      const dirTxt = document.createElement('span');
      dirTxt.textContent = 'Directions';
      dirA.append(dirTxt, iconNode('arrow-up-right', 15));
      actions.appendChild(dirA);

      entry.appendChild(actions);
      wrapper.appendChild(entry);
    });

    return wrapper;
  }

  function buildCompactPopupDom(locationsAtPoint) {
    const wrapper = document.createElement('div');
    wrapper.className = 'loc-popup loc-popup--compact';

    const primary = locationsAtPoint[0];
    const cityLine = document.createElement('p');
    cityLine.className = 'loc-popup-serial';
    cityLine.textContent = primary.isMainCenter ? `Watauga HQ · ${primary.city}` : primary.city;
    wrapper.appendChild(cityLine);

    const title = document.createElement('h3');
    title.className = 'loc-popup-title';
    title.textContent =
      locationsAtPoint.length > 1
        ? `${primary.name} (+${locationsAtPoint.length - 1} more)`
        : primary.name;
    wrapper.appendChild(title);

    const hint = document.createElement('p');
    hint.className = 'loc-popup-hint';
    hint.textContent = 'Full address & Directions below map.';
    wrapper.appendChild(hint);

    return wrapper;
  }

  function clearSelectedLocation() {
    state.selectedLocId = null;
    document.querySelectorAll('.loc-card.is-selected').forEach((el) => el.classList.remove('is-selected'));
    if (state.activePopup) {
      state.activePopup.remove();
      state.activePopup = null;
    }
    if (els.selectedCard) {
      els.selectedCard.hidden = true;
      els.selectedCard.textContent = '';
    }
    updateMapSources();
    window.requestAnimationFrame(() => state.map?.resize());
  }

  function renderSelectedCard(venues) {
    if (!els.selectedCard || !Array.isArray(venues) || venues.length === 0) return;

    els.selectedCard.textContent = '';

    const header = document.createElement('div');
    header.className = 'loc-selected-card__head';

    const kicker = document.createElement('span');
    kicker.className = 'loc-selected-card__kicker';
    kicker.textContent =
      venues.length > 1
        ? `Selected · ${venues.length} bins at this address`
        : venues[0].isMainCenter
          ? 'Selected · Watauga HQ'
          : `Selected · ${venues[0].city}`;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'loc-selected-card__close';
    closeBtn.setAttribute('aria-label', 'Close selected location details');
    closeBtn.appendChild(iconNode('x', 16));
    closeBtn.addEventListener('click', clearSelectedLocation);

    header.append(kicker, closeBtn);
    els.selectedCard.appendChild(header);
    els.selectedCard.appendChild(buildLocationDetailDom(venues));
    els.selectedCard.hidden = false;

    window.requestAnimationFrame(() => state.map?.resize());
  }

  function nudgePopupIntoView() {
    if (!state.map || !state.activePopup || !els.mapContainer) return;
    const popupEl = state.activePopup.getElement?.();
    if (!popupEl) return;

    const frameRect = els.mapContainer.getBoundingClientRect();
    const pRect = popupEl.getBoundingClientRect();
    if (frameRect.width <= 0 || frameRect.height <= 0 || pRect.width <= 0 || pRect.height <= 0) {
      return;
    }

    const pad = 10;
    let dx = 0;
    let dy = 0;

    if (pRect.left < frameRect.left + pad) {
      dx = pRect.left - (frameRect.left + pad);
    } else if (pRect.right > frameRect.right - pad) {
      dx = pRect.right - (frameRect.right - pad);
    }

    if (pRect.top < frameRect.top + pad) {
      dy = pRect.top - (frameRect.top + pad);
    } else if (pRect.bottom > frameRect.bottom - pad) {
      dy = pRect.bottom - (frameRect.bottom - pad);
    }

    if ((Math.abs(dx) > 1 || Math.abs(dy) > 1) && typeof state.map.panBy === 'function') {
      state.map.panBy([dx, dy], { duration: 0 });
    }
  }

  function syncSelectedListCard(locId) {
    document.querySelectorAll('.loc-card.is-selected').forEach((el) => el.classList.remove('is-selected'));
    const card = document.querySelector(`.loc-card[data-loc-id="${locId}"]`);
    if (!card) return;
    card.classList.add('is-selected');

    if (isDesktopLayout() && els.listPanel) {
      const panelRect = els.listPanel.getBoundingClientRect();
      const cardRect = card.getBoundingClientRect();
      if (cardRect.top < panelRect.top || cardRect.bottom > panelRect.bottom) {
        const offset = cardRect.top - panelRect.top + els.listPanel.scrollTop - 12;
        els.listPanel.scrollTo({
          top: Math.max(0, offset),
          behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
      }
    }
  }

  function openPopupForLocation(loc, allSameCoord = null) {
    if (!loc?.geocoded) return;

    state.selectedLocId = loc.id;
    syncSelectedListCard(loc.id);
    updateMapSources();

    const venues =
      allSameCoord && allSameCoord.length > 0
        ? allSameCoord
        : state.filteredLocations.filter((item) => item.geocoded && item.coordKey === loc.coordKey);
    const resolvedVenues = venues.length > 0 ? venues : [loc];

    renderSelectedCard(resolvedVenues);

    if (!state.map) return;

    state.map.resize();

    if (state.activePopup) {
      state.activePopup.remove();
      state.activePopup = null;
    }

    const containerHeight = els.mapContainer?.clientHeight || 360;
    const containerWidth = els.mapContainer?.clientWidth || 360;
    const isShortCanvas = containerHeight < 380;
    const domContent = isShortCanvas
      ? buildCompactPopupDom(resolvedVenues)
      : buildLocationDetailDom(resolvedVenues);

    state.activePopup = new maplibregl.Popup({
      closeButton: true,
      closeOnClick: true,
      anchor: 'bottom',
      offset: loc.isMainCenter ? 16 : 12,
      maxWidth: `${Math.min(300, Math.max(200, containerWidth - 24))}px`,
    })
      .setLngLat([loc.lng, loc.lat])
      .setDOMContent(domContent)
      .addTo(state.map);

    const popupContent = state.activePopup.getElement()?.querySelector('.maplibregl-popup-content');
    if (popupContent) {
      popupContent.style.maxBlockSize = `${Math.max(84, containerHeight - (isShortCanvas ? 64 : 88))}px`;
      popupContent.style.overflowY = 'auto';
    }

    window.requestAnimationFrame(() => nudgePopupIntoView());
    state.map.once('moveend', () => nudgePopupIntoView());
  }

  function fitMapToFiltered() {
    if (!state.map || !state.styleLoaded) return;
    const pts = state.filteredLocations.filter((l) => l.geocoded);
    if (pts.length === 0) return;

    const animOpts = prefersReducedMotion() ? { animate: false } : { duration: 500 };

    if (pts.length === 1) {
      state.map.flyTo({
        center: [pts[0].lng, pts[0].lat],
        zoom: 13.8,
        ...animOpts,
      });
      return;
    }

    const bounds = new maplibregl.LngLatBounds();
    for (const pt of pts) {
      bounds.extend([pt.lng, pt.lat]);
    }
    state.map.fitBounds(bounds, {
      padding: { top: 44, right: 44, bottom: 44, left: 44 },
      maxZoom: 13.5,
      ...animOpts,
    });
  }

  function showMapFallback(message) {
    if (els.mapFallback) els.mapFallback.hidden = false;
    if (els.mapFallbackMsg && message) els.mapFallbackMsg.textContent = message;
  }

  function hideMapFallback() {
    if (els.mapFallback) els.mapFallback.hidden = true;
  }

  function attachMapLayers() {
    const map = state.map;
    if (!map) return;

    const palette = MAP_THEME_PALETTE[state.currentTheme] || MAP_THEME_PALETTE.light;
    const collections = buildGeoJsonCollections();

    if (!map.getSource(SOURCE_BINS)) {
      map.addSource(SOURCE_BINS, {
        type: 'geojson',
        data: collections.partners,
        cluster: true,
        clusterMaxZoom: 13,
        clusterRadius: 48,
      });
    }

    if (!map.getSource(SOURCE_HQ)) {
      map.addSource(SOURCE_HQ, {
        type: 'geojson',
        data: collections.hq,
      });
    }

    if (!map.getSource(SOURCE_SELECTED)) {
      map.addSource(SOURCE_SELECTED, {
        type: 'geojson',
        data: collections.selected,
      });
    }

    if (!map.getLayer('dropoff-clusters')) {
      map.addLayer({
        id: 'dropoff-clusters',
        type: 'circle',
        source: SOURCE_BINS,
        filter: ['has', 'point_count'],
        paint: {
          'circle-color': [
            'step',
            ['get', 'point_count'],
            palette.clusterSmall,
            10,
            palette.clusterMedium,
            25,
            palette.clusterLarge,
          ],
          'circle-radius': ['step', ['get', 'point_count'], 17, 10, 21, 25, 25],
          'circle-stroke-width': 2.5,
          'circle-stroke-color': palette.clusterStroke,
        },
      });
    }

    if (!map.getLayer('dropoff-cluster-count')) {
      map.addLayer({
        id: 'dropoff-cluster-count',
        type: 'symbol',
        source: SOURCE_BINS,
        filter: ['has', 'point_count'],
        layout: {
          'text-field': ['get', 'point_count_abbreviated'],
          'text-font': ['Noto Sans Regular'],
          'text-size': 13,
          'text-allow-overlap': true,
        },
        paint: {
          'text-color': palette.clusterText,
        },
      });
    }

    if (!map.getLayer('dropoff-points')) {
      map.addLayer({
        id: 'dropoff-points',
        type: 'circle',
        source: SOURCE_BINS,
        filter: ['!', ['has', 'point_count']],
        paint: {
          'circle-color': palette.pinFill,
          'circle-radius': 7.5,
          'circle-stroke-width': 2.5,
          'circle-stroke-color': palette.pinStroke,
        },
      });
    }

    if (!map.getLayer('watauga-hq-halo')) {
      map.addLayer({
        id: 'watauga-hq-halo',
        type: 'circle',
        source: SOURCE_HQ,
        paint: {
          'circle-color': palette.hqHalo,
          'circle-opacity': 0.3,
          'circle-radius': 18,
          'circle-stroke-width': 2,
          'circle-stroke-color': palette.hqHalo,
        },
      });
    }

    if (!map.getLayer('watauga-hq-core')) {
      map.addLayer({
        id: 'watauga-hq-core',
        type: 'circle',
        source: SOURCE_HQ,
        paint: {
          'circle-color': palette.hqCore,
          'circle-radius': 10.5,
          'circle-stroke-width': 3,
          'circle-stroke-color': palette.hqStroke,
        },
      });
    }

    if (!map.getLayer('dropoff-selected-ring')) {
      map.addLayer({
        id: 'dropoff-selected-ring',
        type: 'circle',
        source: SOURCE_SELECTED,
        paint: {
          'circle-color': 'rgba(0,0,0,0)',
          'circle-radius': 15,
          'circle-stroke-width': 3,
          'circle-stroke-color': palette.selectedRing,
        },
      });
    }
  }

  function wireMapEvents(map) {
    map.on('click', 'dropoff-clusters', async (ev) => {
      const features = map.queryRenderedFeatures(ev.point, { layers: ['dropoff-clusters'] });
      if (!features.length) return;
      const feature = features[0];
      const clusterId = feature.properties.cluster_id;
      const source = map.getSource(SOURCE_BINS);
      if (!source || typeof source.getClusterExpansionZoom !== 'function') return;

      try {
        const zoom = await source.getClusterExpansionZoom(clusterId);
        map.easeTo({
          center: feature.geometry.coordinates,
          zoom: Math.min(zoom + 0.4, 16),
          duration: prefersReducedMotion() ? 0 : 450,
        });
      } catch {
        // Ignore transient cluster zoom error
      }
    });

    map.on('click', 'dropoff-points', (ev) => {
      if (!ev.features?.length) return;
      const clickedId = Number(ev.features[0].properties.id);
      const loc = state.allLocations.find((item) => item.id === clickedId);
      if (loc) openPopupForLocation(loc);
    });

    map.on('click', 'watauga-hq-core', (ev) => {
      if (!ev.features?.length) return;
      const clickedId = Number(ev.features[0].properties.id);
      const loc = state.allLocations.find((item) => item.id === clickedId);
      if (loc) openPopupForLocation(loc);
    });

    for (const layerId of ['dropoff-clusters', 'dropoff-points', 'watauga-hq-core']) {
      map.on('mouseenter', layerId, () => {
        map.getCanvas().style.cursor = 'pointer';
      });
      map.on('mouseleave', layerId, () => {
        map.getCanvas().style.cursor = '';
      });
    }
  }

  function destroyMap() {
    window.clearTimeout(state.styleLoadTimer);
    if (state.activePopup) {
      state.activePopup.remove();
      state.activePopup = null;
    }
    if (state.map) {
      state.map.remove();
      state.map = null;
    }
    state.styleLoaded = false;
  }

  function initMap() {
    if (!els.mapContainer) return;
    destroyMap();
    hideMapFallback();

    if (!canCreateWebGLContext()) {
      showMapFallback(
        'WebGL is disabled in this browser context. All 214 drop-off locations and Directions links remain available in the directory.'
      );
      return;
    }

    state.currentTheme = getResolvedTheme();
    const initialStyle = STYLES[state.currentTheme] || STYLES.light;

    try {
      const map = new maplibregl.Map({
        container: els.mapContainer,
        style: initialStyle,
        center: [mainLng, mainLat],
        zoom: 9.2,
        cooperativeGestures: true,
        attributionControl: false,
      });

      state.map = map;
      map.addControl(new maplibregl.NavigationControl({ showCompass: true }), 'top-right');
      map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

      window.clearTimeout(state.styleLoadTimer);
      state.styleLoadTimer = window.setTimeout(() => {
        if (state.map === map && !state.styleLoaded) {
          showMapFallback(
            'Map tiles took too long to respond or your connection is offline. Use the directory or retry.'
          );
        }
      }, 12000);

      map.on('style.load', () => {
        if (state.map !== map) return;
        window.clearTimeout(state.styleLoadTimer);
        state.styleLoaded = true;
        hideMapFallback();
        attachMapLayers();

        if (state.selectedLocId !== null) {
          const selected = state.allLocations.find((item) => item.id === state.selectedLocId);
          if (selected?.geocoded) {
            if (state.pendingFocusId === selected.id) {
              state.pendingFocusId = null;
              map.jumpTo({
                center: [selected.lng, selected.lat],
                zoom: selected.isMainCenter ? 14.2 : 13.8,
              });
            }
            openPopupForLocation(selected);
          }
        }
      });

      map.on('error', (ev) => {
        if (state.map === map && !state.styleLoaded && ev?.error) {
          window.clearTimeout(state.styleLoadTimer);
          showMapFallback(
            'Unable to load map tiles right now. You can still browse all 214 bins and open Google Maps Directions.'
          );
        }
      });

      wireMapEvents(map);
    } catch {
      showMapFallback(
        'Interactive map could not initialize in this browser context. All 214 drop-off locations and Directions links remain available.'
      );
    }
  }

  function applyMapTheme(nextTheme) {
    if (!state.map || nextTheme === state.currentTheme) return;
    const map = state.map;
    state.currentTheme = nextTheme;
    state.styleLoaded = false;
    window.clearTimeout(state.styleLoadTimer);
    state.styleLoadTimer = window.setTimeout(() => {
      if (state.map === map && !state.styleLoaded) {
        showMapFallback(
          'Map tiles took too long to respond while updating map theme. Use the directory or retry.'
        );
      }
    }, 12000);
    map.setStyle(STYLES[nextTheme] || STYLES.light);
  }

  function focusLocationOnMap(loc) {
    if (!loc?.geocoded) return;

    state.selectedLocId = loc.id;
    syncSelectedListCard(loc.id);

    if (state.map && state.styleLoaded) {
      state.pendingFocusId = null;
      const animOpts = prefersReducedMotion() ? { duration: 0 } : { duration: 480 };
      state.map.easeTo({
        center: [loc.lng, loc.lat],
        zoom: Math.max(state.map.getZoom(), loc.isMainCenter ? 14.2 : 13.6),
        ...animOpts,
      });
      openPopupForLocation(loc);
    } else if (state.map) {
      state.pendingFocusId = loc.id;
      openPopupForLocation(loc);
    } else {
      openPopupForLocation(loc);
    }
  }

  function clearGeoBanner() {
    if (!els.geoBanner) return;
    els.geoBanner.hidden = true;
    els.geoBanner.classList.remove('is-warn');
    if (els.geoMessage) els.geoMessage.textContent = '';
    if (els.geoActions) els.geoActions.textContent = '';
  }

  function showGeoSuccessBanner() {
    if (!els.geoBanner || !els.geoMessage || !els.geoActions) return;
    els.geoBanner.classList.remove('is-warn');
    els.geoBanner.hidden = false;
    els.geoMessage.textContent = 'Sorted nearest drop-off bins first using your current coordinates.';
    els.geoActions.textContent = '';

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'btn btn--secondary btn--sm';
    clearBtn.textContent = 'Clear Distance Sort';
    clearBtn.addEventListener('click', () => {
      state.userCoords = null;
      if (els.nearMeBtn) els.nearMeBtn.setAttribute('aria-pressed', 'false');
      clearGeoBanner();
      applyFilters(false);
    });
    els.geoActions.appendChild(clearBtn);
  }

  function showGeoFallbackBanner(message) {
    if (!els.geoBanner || !els.geoMessage || !els.geoActions) return;
    els.geoBanner.classList.add('is-warn');
    els.geoBanner.hidden = false;
    els.geoMessage.textContent = message;
    els.geoActions.textContent = '';

    const dismissBtn = document.createElement('button');
    dismissBtn.type = 'button';
    dismissBtn.className = 'btn btn--secondary btn--sm';
    dismissBtn.textContent = 'Dismiss';
    dismissBtn.addEventListener('click', clearGeoBanner);
    els.geoActions.appendChild(dismissBtn);
  }

  function applyFilters(shouldFitBounds = false, resetPage = true) {
    if (resetPage) {
      state.mobilePageSize = MOBILE_INITIAL_COUNT;
    }
    computeFilteredLocations();
    renderDirectory();
    updateMapSources();
    if (shouldFitBounds) {
      fitMapToFiltered();
    }
  }

  function resetAllFilters() {
    state.searchQuery = '';
    state.cityFilter = 'all';
    state.userCoords = null;
    clearSelectedLocation();

    if (els.searchInput) els.searchInput.value = '';
    if (els.citySelect) els.citySelect.value = 'all';
    if (els.nearMeBtn) els.nearMeBtn.setAttribute('aria-pressed', 'false');
    clearGeoBanner();
    applyFilters(true, true);
  }

  function bindUiEvents() {
    if (els.filterForm) {
      els.filterForm.addEventListener('submit', (ev) => ev.preventDefault());
    }

    if (els.searchInput) {
      els.searchInput.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchQuery = els.searchInput.value;
        state.searchTimer = window.setTimeout(() => {
          applyFilters(false, true);
        }, 100);
      });

      els.searchInput.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && els.searchInput.value !== '') {
          ev.stopPropagation();
          state.searchQuery = '';
          els.searchInput.value = '';
          applyFilters(false, true);
        }
      });
    }

    document.addEventListener('keydown', (ev) => {
      if (ev.key !== '/' || ev.ctrlKey || ev.metaKey || ev.altKey) return;
      const active = document.activeElement;
      if (
        active instanceof HTMLElement &&
        (active.tagName === 'INPUT' ||
          active.tagName === 'TEXTAREA' ||
          active.tagName === 'SELECT' ||
          active.isContentEditable)
      ) {
        return;
      }
      if (els.searchInput) {
        ev.preventDefault();
        els.searchInput.focus();
        els.searchInput.select?.();
      }
    });

    if (els.searchClear) {
      els.searchClear.addEventListener('click', () => {
        state.searchQuery = '';
        if (els.searchInput) {
          els.searchInput.value = '';
          els.searchInput.focus();
        }
        applyFilters(false, true);
      });
    }

    if (els.citySelect) {
      els.citySelect.addEventListener('change', () => {
        state.cityFilter = els.citySelect.value || 'all';
        applyFilters(true, true);
      });
    }

    if (els.showMoreBtn) {
      els.showMoreBtn.addEventListener('click', () => {
        state.mobilePageSize += MOBILE_PAGE_INCREMENT;
        renderDirectory();
      });
    }

    if (els.nearMeBtn) {
      els.nearMeBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
          showGeoFallbackBanner(
            'Geolocation is not supported in this browser. Pick a city or search by ZIP code above.'
          );
          return;
        }

        const labelSpan = els.nearMeBtn.querySelector('span');
        const originalLabel = labelSpan ? labelSpan.textContent : 'Near Me';
        els.nearMeBtn.disabled = true;
        els.nearMeBtn.setAttribute('aria-busy', 'true');
        if (labelSpan) labelSpan.textContent = 'Locating...';

        navigator.geolocation.getCurrentPosition(
          (pos) => {
            els.nearMeBtn.disabled = false;
            els.nearMeBtn.removeAttribute('aria-busy');
            els.nearMeBtn.setAttribute('aria-pressed', 'true');
            if (labelSpan) labelSpan.textContent = originalLabel;

            state.userCoords = {
              lat: pos.coords.latitude,
              lng: pos.coords.longitude,
            };
            showGeoSuccessBanner();
            applyFilters(false, true);

            const nearestMapped = state.filteredLocations.find((l) => l.geocoded);
            if (nearestMapped && state.map && state.styleLoaded) {
              const bounds = new maplibregl.LngLatBounds();
              bounds.extend([state.userCoords.lng, state.userCoords.lat]);
              bounds.extend([nearestMapped.lng, nearestMapped.lat]);
              state.map.fitBounds(bounds, {
                padding: 64,
                maxZoom: 13.2,
                duration: prefersReducedMotion() ? 0 : 500,
              });
            }
          },
          (err) => {
            els.nearMeBtn.disabled = false;
            els.nearMeBtn.removeAttribute('aria-busy');
            els.nearMeBtn.setAttribute('aria-pressed', 'false');
            if (labelSpan) labelSpan.textContent = originalLabel;

            const denied = err && err.code === 1;
            showGeoFallbackBanner(
              denied
                ? 'Location permission was declined. Choose your city or enter a 5-digit Texas ZIP code in Search.'
                : 'Unable to determine your location right now. Choose your city or search by ZIP code above.'
            );
          },
          { enableHighAccuracy: false, timeout: 9000, maximumAge: 300000 }
        );
      });
    }

    if (els.resetBtn) els.resetBtn.addEventListener('click', resetAllFilters);
    if (els.emptyResetBtn) els.emptyResetBtn.addEventListener('click', resetAllFilters);

    if (els.focusMainBtn) {
      els.focusMainBtn.addEventListener('click', () => {
        let mainLoc = state.filteredLocations.find((l) => l.isMainCenter);
        if (!mainLoc) {
          state.searchQuery = '';
          state.cityFilter = 'all';
          if (els.searchInput) els.searchInput.value = '';
          if (els.citySelect) els.citySelect.value = 'all';
          applyFilters(false, true);
          mainLoc = state.allLocations.find((l) => l.isMainCenter);
        }
        if (mainLoc) {
          focusLocationOnMap(mainLoc);
        } else if (state.map && state.styleLoaded) {
          state.map.flyTo({
            center: [mainLng, mainLat],
            zoom: 14.2,
            duration: prefersReducedMotion() ? 0 : 500,
          });
        }
      });
    }

    if (els.listEl) {
      els.listEl.addEventListener('click', (ev) => {
        const btn = ev.target instanceof Element ? ev.target.closest('[data-action="view-on-map"]') : null;
        if (!btn) return;
        const id = Number(btn.dataset.locId);
        const loc = state.allLocations.find((item) => item.id === id);
        if (loc) focusLocationOnMap(loc);
      });
    }

    if (els.mapRetryBtn) {
      els.mapRetryBtn.addEventListener('click', initMap);
    }

    window.addEventListener('erc:themechange', (ev) => {
      const next = ev?.detail?.resolved || getResolvedTheme();
      applyMapTheme(next);
    });

    if (desktopMedia) {
      const onBreakpoint = () => {
        renderDirectory();
        window.requestAnimationFrame(() => state.map?.resize());
      };
      if (typeof desktopMedia.addEventListener === 'function') {
        desktopMedia.addEventListener('change', onBreakpoint);
      } else if (typeof desktopMedia.addListener === 'function') {
        desktopMedia.addListener(onBreakpoint);
      }
    }
  }

  function hydrateLocations(rawList) {
    state.allLocations = normalizeLocations(rawList, globalPhone);
    recomputeCityCounts(state.allLocations);
    buildFuseIndex(state.allLocations);
    syncCitySelect();
    computeFilteredLocations();
    renderDirectory();
    updateMapSources();
  }

  async function boot() {
    const seed = readSsrSeed();
    if (seed.length > 0) {
      hydrateLocations(seed);
    }
    initMap();

    try {
      const resp = await fetch('/get-locations.php', {
        headers: { Accept: 'application/json' },
      });
      if (resp.ok) {
        const json = await resp.json();
        if (Array.isArray(json) && json.length > 0 && json.length !== seed.length) {
          hydrateLocations(json);
        } else if (Array.isArray(json) && json.length > 0 && seed.length === 0) {
          hydrateLocations(json);
        }
      }
    } catch {
      // Initial SSR seed already rendered cleanly
    }
  }

  bindUiEvents();
  boot();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDropoffLocator, { once: true });
} else {
  initDropoffLocator();
}
