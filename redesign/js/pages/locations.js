import * as maplibregl from '/vendor/maplibre-gl/maplibre-gl.mjs';
import Fuse from '/vendor/fuse/fuse.min.mjs';
import { ercIconElement } from '/vendor/lucide/icons.mjs';

const STYLES = Object.freeze({
  light: 'https://tiles.openfreemap.org/styles/positron',
  dark: 'https://tiles.openfreemap.org/styles/dark',
});

// High-contrast Vermilion & Optical Cyan sRGB tokens for WebGL shader paints
// (MapLibre GL JS v6.18.0 parses hex/rgb strings rather than oklch()).
const MAP_THEME_PALETTE = Object.freeze({
  light: {
    pinFill: '#0e7490',
    pinStroke: '#ffffff',
    clusterSmall: '#0e7490',
    clusterMedium: '#b83227',
    clusterLarge: '#9e271d',
    clusterText: '#ffffff',
    clusterStroke: '#ffffff',
    hqHalo: '#0e7490',
    hqCore: '#b83227',
    hqStroke: '#ffffff',
  },
  dark: {
    pinFill: '#22d3ee',
    pinStroke: '#0b1117',
    clusterSmall: '#22d3ee',
    clusterMedium: '#e5533a',
    clusterLarge: '#ff6a4d',
    clusterText: '#0b1117',
    clusterStroke: '#0b1117',
    hqHalo: '#22d3ee',
    hqCore: '#e5533a',
    hqStroke: '#0b1117',
  },
});

const DEFAULT_CENTER = [-97.2620445, 32.8707405];
const SOURCE_BINS = 'dropoff-bins';
const SOURCE_HQ = 'watauga-hq';

function getResolvedTheme() {
  const attr = (document.documentElement.getAttribute('data-theme') || '').trim().toLowerCase();
  if (attr === 'dark' || attr === 'light') return attr;
  const meta = document.querySelector('meta[name="color-scheme"]');
  const content = (meta?.getAttribute('content') || 'light dark').trim().toLowerCase();
  if (content === 'dark') return 'dark';
  if (content === 'light') return 'light';
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function prefersReducedMotion() {
  return Boolean(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
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
      serial: `BIN-${String(idx + 1).padStart(3, '0')}`,
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
  const el = document.getElementById('locations-ssr-seed');
  if (!el) return [];
  try {
    const parsed = JSON.parse(el.textContent || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function initLocationsDirectory() {
  const appEl = document.getElementById('loc-app');
  if (!appEl) return;

  const mainLat = Number(appEl.dataset.mainLat) || DEFAULT_CENTER[1];
  const mainLng = Number(appEl.dataset.mainLng) || DEFAULT_CENTER[0];
  const globalPhone = appEl.dataset.globalPhone || '(817) 710-5403';

  const els = {
    filterForm: document.getElementById('loc-filter-form'),
    searchInput: document.getElementById('loc-search'),
    searchKbd: document.getElementById('loc-search-kbd'),
    searchClear: document.getElementById('loc-search-clear'),
    citySelect: document.getElementById('loc-city-select'),
    cityChipsTrack: document.getElementById('loc-city-chips'),
    openCitiesDialogBtn: document.getElementById('loc-open-cities-dialog'),
    citiesDialog: document.getElementById('loc-cities-dialog'),
    citiesDialogClose: document.getElementById('loc-cities-dialog-close'),
    citiesDialogList: document.getElementById('loc-cities-dialog-list'),
    nearMeBtn: document.getElementById('loc-near-me'),
    resetBtn: document.getElementById('loc-reset'),
    emptyResetBtn: document.getElementById('loc-empty-reset'),
    focusMainBtn: document.getElementById('loc-focus-main'),
    geoBanner: document.getElementById('loc-geo-banner'),
    geoMessage: document.getElementById('loc-geo-message'),
    geoActions: document.getElementById('loc-geo-actions'),
    resultsSummary: document.getElementById('loc-results-summary'),
    statusLive: document.getElementById('loc-status-live'),
    workspace: document.getElementById('loc-workspace'),
    directoryPanel: document.getElementById('loc-directory-panel'),
    listEl: document.getElementById('loc-list'),
    emptyEl: document.getElementById('loc-empty'),
    mapPanel: document.getElementById('loc-map-panel'),
    mapContainer: document.getElementById('locations-map'),
    selectedCard: document.getElementById('loc-selected-card'),
    mapFallback: document.getElementById('loc-map-fallback'),
    mapFallbackMsg: document.getElementById('loc-map-fallback-msg'),
    mapRetryBtn: document.getElementById('loc-map-retry'),
    viewBtns: Array.from(document.querySelectorAll('.loc-view-btn')),
    totalCount: document.getElementById('loc-total-count'),
    cityCount: document.getElementById('loc-city-count'),
    mappedCount: document.getElementById('loc-mapped-count'),
    directoryOnlyCount: document.getElementById('loc-directory-only-count'),
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
    viewMode: 'both',
    map: null,
    activePopup: null,
    currentTheme: getResolvedTheme(),
    styleLoaded: false,
    pendingFocusId: null,
    styleLoadTimer: null,
    liveTimer: null,
    searchTimer: null,
  };

  function iconNode(name, size = 16) {
    try {
      return ercIconElement(name, { width: size, height: size, 'aria-hidden': 'true' });
    } catch {
      const span = document.createElement('span');
      span.setAttribute('aria-hidden', 'true');
      return span;
    }
  }

  function announceStatus(message) {
    if (!els.statusLive) return;
    window.clearTimeout(state.liveTimer);
    state.liveTimer = window.setTimeout(() => {
      els.statusLive.textContent = message;
    }, 250);
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
        { name: 'serial', weight: 1.4 },
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

  function syncCountersAndCityControls() {
    const total = state.allLocations.length;
    const mapped = state.allLocations.filter((l) => l.geocoded).length;
    const dirOnly = total - mapped;
    const cityTotal = state.cityCounts.size;

    if (els.totalCount) els.totalCount.textContent = String(total);
    if (els.cityCount) els.cityCount.textContent = String(cityTotal);
    if (els.mappedCount) els.mappedCount.textContent = String(mapped);
    if (els.directoryOnlyCount) els.directoryOnlyCount.textContent = String(dirOnly);

    if (els.citySelect) {
      const prev = state.cityFilter;
      els.citySelect.textContent = '';
      const allOpt = document.createElement('option');
      allOpt.value = 'all';
      allOpt.textContent = `All Cities (${total})`;
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

    if (els.cityChipsTrack) {
      for (const chip of els.cityChipsTrack.querySelectorAll('[data-city-chip]')) {
        const cityKey = chip.dataset.cityChip;
        if (!cityKey) continue;
        const countEl = chip.querySelector('.loc-chip-count');
        if (!countEl) continue;
        countEl.textContent = cityKey === 'all' ? String(total) : String(state.cityCounts.get(cityKey) || 0);
      }
    }

    if (els.openCitiesDialogBtn) {
      const label = els.openCitiesDialogBtn.querySelector('span');
      if (label) label.textContent = `All ${cityTotal} Cities`;
    }

    if (els.citiesDialogList) {
      els.citiesDialogList.textContent = '';

      const allBtn = document.createElement('button');
      allBtn.type = 'button';
      allBtn.className = 'loc-dialog-city';
      allBtn.dataset.dialogCity = 'all';
      allBtn.setAttribute('aria-pressed', String(state.cityFilter === 'all'));
      if (state.cityFilter === 'all') allBtn.classList.add('is-active');
      const allLbl = document.createElement('span');
      allLbl.textContent = 'All Cities';
      const allCnt = document.createElement('span');
      allCnt.className = 'loc-chip-count';
      allCnt.textContent = String(total);
      allBtn.append(allLbl, allCnt);
      els.citiesDialogList.appendChild(allBtn);

      for (const [city, count] of state.cityCounts.entries()) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'loc-dialog-city';
        btn.dataset.dialogCity = city;
        const active = state.cityFilter === city;
        btn.setAttribute('aria-pressed', String(active));
        if (active) btn.classList.add('is-active');
        const lbl = document.createElement('span');
        lbl.textContent = city;
        const cnt = document.createElement('span');
        cnt.className = 'loc-chip-count';
        cnt.textContent = String(count);
        btn.append(lbl, cnt);
        els.citiesDialogList.appendChild(btn);
      }
    }
  }

  function syncCityChipStates() {
    const active = state.cityFilter;
    for (const chip of document.querySelectorAll('[data-city-chip]')) {
      const match = chip.dataset.cityChip === active;
      chip.classList.toggle('is-active', match);
      chip.setAttribute('aria-pressed', String(match));
    }
    for (const btn of document.querySelectorAll('[data-dialog-city]')) {
      const match = btn.dataset.dialogCity === active;
      btn.classList.toggle('is-active', match);
      btn.setAttribute('aria-pressed', String(match));
    }
    if (els.citySelect && els.citySelect.value !== active) {
      els.citySelect.value = active;
    }
  }

  function setViewMode(mode) {
    state.viewMode = mode;
    if (els.workspace) {
      els.workspace.dataset.view = mode;
    }
    for (const btn of els.viewBtns) {
      btn.setAttribute('aria-pressed', String(btn.dataset.viewMode === mode));
    }
    if (state.map && mode !== 'list') {
      state.map.resize();
      window.requestAnimationFrame(() => {
        state.map?.resize();
      });
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
        pool = exactZipMatches.length > 0
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
          const allTokensPresent = tokens.every((tok) => loc.searchText.includes(tok) || loc.serial.toLowerCase().includes(tok));
          const hasDigitMatch = digitQuery.length >= 3 && (loc.zip.includes(digitQuery) || loc.phoneDigits.includes(digitQuery));
          const hasFuse = fuseScores.has(loc.id);

          if (!allTokensPresent && !hasDigitMatch && !hasFuse) continue;

          let tier = 4;
          if (loc.city.toLowerCase() === queryLower || loc.zip === queryLower || loc.serial.toLowerCase() === queryLower) {
            tier = 0;
          } else if (loc.name.toLowerCase().startsWith(queryLower)) {
            tier = 1;
          } else if (allTokensPresent && (loc.name.toLowerCase().includes(queryLower) || loc.address.toLowerCase().includes(queryLower))) {
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
    li.className = 'loc-list-item';

    const article = document.createElement('article');
    article.className = 'loc-card';
    if (loc.isMainCenter) article.classList.add('loc-card--hq');
    if (!loc.geocoded) article.classList.add('loc-card--directory-only');
    if (state.selectedLocId === loc.id) article.classList.add('is-selected');
    article.dataset.locId = String(loc.id);

    const telemetry = document.createElement('div');
    telemetry.className = 'loc-card-telemetry';
    const serialEl = document.createElement('span');
    serialEl.className = 'loc-card-serial';
    serialEl.textContent = loc.serial;
    const cityEl = document.createElement('span');
    cityEl.className = 'loc-card-city';
    cityEl.textContent = loc.city;
    telemetry.append(serialEl, cityEl);

    const header = document.createElement('div');
    header.className = 'loc-card-header';

    const title = document.createElement('h3');
    title.className = 'loc-card-title';
    title.textContent = loc.name;

    const badges = document.createElement('div');
    badges.className = 'loc-card-badges';

    if (loc.isMainCenter) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--hq';
      b.textContent = 'Watauga HQ';
      badges.appendChild(b);
    }
    if (loc.isLionsHost) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--lions';
      b.textContent = 'Lions Club Host';
      badges.appendChild(b);
    }
    if (loc.distanceMiles !== null && Number.isFinite(loc.distanceMiles)) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--distance';
      b.textContent = `${loc.distanceMiles.toFixed(1)} mi`;
      badges.appendChild(b);
    }
    if (loc.isPOBox) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--neutral';
      b.textContent = 'P.O. Box';
      badges.appendChild(b);
    }
    if (!loc.geocoded) {
      const b = document.createElement('span');
      b.className = 'loc-badge loc-badge--dir-only';
      b.textContent = 'Directory Only';
      badges.appendChild(b);
    }

    header.append(title, badges);

    const addrP = document.createElement('p');
    addrP.className = 'loc-card-addr';
    const addrText = document.createElement('span');
    addrText.textContent = loc.address;
    addrP.append(iconNode('map-pin', 16), addrText);

    const phoneP = document.createElement('p');
    phoneP.className = 'loc-card-phone';
    phoneP.appendChild(iconNode('phone', 16));
    if (loc.phone && loc.tel) {
      const telA = document.createElement('a');
      telA.href = `tel:${loc.tel}`;
      telA.textContent = loc.phone;
      phoneP.appendChild(telA);
    } else {
      const missing = document.createElement('span');
      missing.className = 'loc-phone-missing';
      missing.textContent = `Phone not listed — call ${globalPhone} to verify`;
      phoneP.appendChild(missing);
    }

    const actions = document.createElement('div');
    actions.className = 'loc-card-actions';

    if (loc.geocoded) {
      const mapBtn = document.createElement('button');
      mapBtn.type = 'button';
      mapBtn.className = 'loc-card-btn loc-card-btn--map';
      mapBtn.dataset.action = 'show-on-map';
      mapBtn.dataset.locId = String(loc.id);
      const mapBtnText = document.createElement('span');
      mapBtnText.textContent = 'Show on Radar';
      mapBtn.append(iconNode('locate-fixed', 16), mapBtnText);
      actions.appendChild(mapBtn);
    }

    const dirA = document.createElement('a');
    dirA.className = 'loc-card-btn loc-card-btn--dir';
    dirA.href = loc.directionsUrl;
    dirA.target = '_blank';
    dirA.rel = 'noopener noreferrer';
    const dirLabel = document.createElement('span');
    dirLabel.textContent = 'Directions';
    const dirSr = document.createElement('span');
    dirSr.className = 'sr-only';
    dirSr.textContent = ` to ${loc.name} (opens in new tab)`;
    dirA.append(dirLabel, iconNode('arrow-up-right', 16), dirSr);
    actions.appendChild(dirA);

    article.append(telemetry, header, addrP, phoneP, actions);
    li.appendChild(article);
    return li;
  }

  function renderDirectory() {
    if (!els.listEl) return;

    const totalAll = state.allLocations.length;
    const shown = state.filteredLocations;
    const shownMapped = shown.filter((l) => l.geocoded).length;
    const shownDirOnly = shown.length - shownMapped;

    const fragment = document.createDocumentFragment();
    for (const loc of shown) {
      fragment.appendChild(createCardElement(loc));
    }

    els.listEl.textContent = '';
    els.listEl.appendChild(fragment);

    if (els.emptyEl) {
      els.emptyEl.hidden = shown.length > 0;
    }

    if (els.resultsSummary) {
      els.resultsSummary.textContent = '';
      const strong = document.createElement('strong');
      strong.textContent = String(shown.length);
      const tail = document.createTextNode(
        ` of ${totalAll} locations · ${shownMapped} geocoded pins · ${shownDirOnly} directory only`
      );
      els.resultsSummary.append('Showing ', strong, tail);
    }

    const hasQuery = Boolean(state.searchQuery.trim());
    if (els.resetBtn) {
      const hasFilters = Boolean(hasQuery || state.cityFilter !== 'all' || state.userCoords);
      els.resetBtn.hidden = !hasFilters;
    }

    if (els.searchClear) {
      els.searchClear.hidden = !hasQuery;
    }
    if (els.searchKbd) {
      els.searchKbd.hidden = hasQuery;
    }

    announceStatus(
      `Showing ${shown.length} of ${totalAll} drop-off locations (${shownMapped} geocoded pins, ${shownDirOnly} directory only).`
    );
  }

  function buildGeoJsonCollections() {
    const partnerFeatures = [];
    const hqFeatures = [];

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
          serial: loc.serial,
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
    }

    return {
      partners: { type: 'FeatureCollection', features: partnerFeatures },
      hq: { type: 'FeatureCollection', features: hqFeatures },
    };
  }

  function updateMapSources() {
    if (!state.map || !state.styleLoaded) return;
    const collections = buildGeoJsonCollections();
    const binSource = state.map.getSource(SOURCE_BINS);
    const hqSource = state.map.getSource(SOURCE_HQ);
    if (binSource && typeof binSource.setData === 'function') {
      binSource.setData(collections.partners);
    }
    if (hqSource && typeof hqSource.setData === 'function') {
      hqSource.setData(collections.hq);
    }
  }

  function buildPopupDom(locationsAtPoint) {
    const wrapper = document.createElement('div');
    wrapper.className = 'loc-popup';

    locationsAtPoint.forEach((loc) => {
      const entry = document.createElement('div');
      entry.className = 'loc-popup-entry';

      const serial = document.createElement('p');
      serial.className = 'loc-popup-serial';
      serial.textContent = `${loc.serial} · ${loc.city}`;
      entry.appendChild(serial);

      const title = document.createElement('h3');
      title.className = 'loc-popup-title';
      title.textContent = loc.name;
      entry.appendChild(title);

      if (loc.isMainCenter) {
        const hqNote = document.createElement('p');
        hqNote.className = 'loc-popup-note';
        hqNote.textContent =
          'Watauga HQ · Sat 9:00 AM - 3:00 PM (Mon–Fri by appt). East-side wooden walkway to rear entrance.';
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
      const dirTxt = document.createElement('span');
      dirTxt.textContent = 'Directions';
      const dirSr = document.createElement('span');
      dirSr.className = 'sr-only';
      dirSr.textContent = ` to ${loc.name} (opens in new tab)`;
      dirA.append(dirTxt, iconNode('arrow-up-right', 15), dirSr);
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
    const serial = document.createElement('p');
    serial.className = 'loc-popup-serial';
    serial.textContent = `${primary.serial} · ${primary.city}`;
    wrapper.appendChild(serial);

    const title = document.createElement('h3');
    title.className = 'loc-popup-title';
    title.textContent =
      locationsAtPoint.length > 1
        ? `${primary.name} (+${locationsAtPoint.length - 1} more)`
        : primary.name;
    wrapper.appendChild(title);

    const hint = document.createElement('p');
    hint.className = 'loc-popup-hint';
    hint.textContent = 'Telemetry & Directions below radar.';
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
        ? `RADAR LOCK · ${venues.length} BINS AT COORDINATE`
        : `RADAR LOCK · ${venues[0].serial}`;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'loc-selected-card__close';
    closeBtn.setAttribute('aria-label', 'Close selected location telemetry');
    const closeGlyph = document.createElement('span');
    closeGlyph.setAttribute('aria-hidden', 'true');
    closeGlyph.textContent = '×';
    closeBtn.appendChild(closeGlyph);
    closeBtn.addEventListener('click', clearSelectedLocation);

    header.append(kicker, closeBtn);
    els.selectedCard.appendChild(header);

    const body = buildPopupDom(venues);
    body.classList.add('loc-selected-card__body');
    els.selectedCard.appendChild(body);
    els.selectedCard.hidden = false;
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

  function getPopupMapPadding(containerHeight) {
    const topPad =
      containerHeight < 300
        ? Math.min(115, Math.round(containerHeight * 0.56))
        : Math.min(260, Math.round(containerHeight * 0.62));
    return { top: topPad, bottom: 24, left: 16, right: 16 };
  }

  function openPopupForLocation(loc, allSameCoord = null) {
    if (!loc?.geocoded) return;

    state.selectedLocId = loc.id;
    document.querySelectorAll('.loc-card.is-selected').forEach((el) => el.classList.remove('is-selected'));
    const card = document.querySelector(`.loc-card[data-loc-id="${loc.id}"]`);
    if (card) card.classList.add('is-selected');

    const venues =
      allSameCoord && allSameCoord.length > 0
        ? allSameCoord
        : state.filteredLocations.filter((item) => item.geocoded && item.coordKey === loc.coordKey);
    const resolvedVenues = venues.length > 0 ? venues : [loc];

    renderSelectedCard(resolvedVenues);

    if (!state.map) return;

    if (state.activePopup) {
      state.activePopup.remove();
      state.activePopup = null;
    }

    const containerHeight = els.mapContainer?.clientHeight || 400;
    const containerWidth = els.mapContainer?.clientWidth || 360;
    const isShortCanvas = containerHeight < 320;
    const domContent = isShortCanvas
      ? buildCompactPopupDom(resolvedVenues)
      : buildPopupDom(resolvedVenues);

    state.activePopup = new maplibregl.Popup({
      closeButton: true,
      closeOnClick: true,
      anchor: 'bottom',
      offset: loc.isMainCenter ? 16 : 12,
      maxWidth: `${Math.min(312, Math.max(220, containerWidth - 24))}px`,
    })
      .setLngLat([loc.lng, loc.lat])
      .setDOMContent(domContent)
      .addTo(state.map);

    const popupContent = state.activePopup.getElement()?.querySelector('.maplibregl-popup-content');
    if (popupContent) {
      popupContent.style.maxBlockSize = `${Math.max(92, containerHeight - (isShortCanvas ? 68 : 96))}px`;
      popupContent.style.overflowY = 'auto';
    }

    window.requestAnimationFrame(() => nudgePopupIntoView());
  }

  function fitMapToFiltered() {
    if (!state.map || !state.styleLoaded) return;
    const pts = state.filteredLocations.filter((l) => l.geocoded);
    if (pts.length === 0) return;

    const animOpts = prefersReducedMotion() ? { animate: false } : { duration: 550 };

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
      padding: { top: 48, right: 48, bottom: 48, left: 48 },
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

    const palette = MAP_THEME_PALETTE[state.currentTheme] || MAP_THEME_PALETTE.dark;
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
          'circle-radius': [
            'step',
            ['get', 'point_count'],
            18,
            10,
            22,
            25,
            27,
          ],
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
          'circle-radius': 8,
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
          'circle-opacity': 0.28,
          'circle-radius': 20,
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
          'circle-radius': 11,
          'circle-stroke-width': 3,
          'circle-stroke-color': palette.hqStroke,
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
        // Ignore transient cluster zoom resolution error
      }
    });

    map.on('click', 'dropoff-points', (ev) => {
      if (!ev.features || !ev.features.length) return;
      const clickedId = Number(ev.features[0].properties.id);
      const loc = state.allLocations.find((item) => item.id === clickedId);
      if (!loc) return;
      openPopupForLocation(loc);
    });

    map.on('click', 'watauga-hq-core', (ev) => {
      if (!ev.features || !ev.features.length) return;
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
        'WebGL hardware acceleration is disabled in this browser context. All 214 drop-off locations and Directions links remain available in the directory.'
      );
      return;
    }

    state.currentTheme = getResolvedTheme();
    const initialStyle = STYLES[state.currentTheme] || STYLES.dark;

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
            'Map tiles took too long to respond or your connection is offline. Use the directory below or retry loading the radar.'
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
          if (selected && selected.geocoded) {
            if (state.pendingFocusId === selected.id) {
              state.pendingFocusId = null;
              const containerHeight = els.mapContainer?.clientHeight || 400;
              map.jumpTo({
                center: [selected.lng, selected.lat],
                zoom: selected.isMainCenter ? 14.5 : 14,
                padding: getPopupMapPadding(containerHeight),
              });
            }
            openPopupForLocation(selected);
          }
        }
      });

      map.on('error', (ev) => {
        if (state.map === map && !state.styleLoaded && ev && ev.error) {
          window.clearTimeout(state.styleLoadTimer);
          showMapFallback(
            'Unable to reach the map tile service right now. You can still browse all 214 bins and open Google Maps Directions from the directory.'
          );
        }
      });

      wireMapEvents(map);
    } catch {
      showMapFallback(
        'Interactive radar could not initialize in this browser context. All 214 drop-off locations and Directions links remain available.'
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
          'Map tiles took too long to respond while updating radar theme. Use the directory below or retry.'
        );
      }
    }, 12000);
    map.setStyle(STYLES[nextTheme] || STYLES.dark);
  }

  function focusLocationOnMap(loc) {
    if (!loc || !loc.geocoded) return;

    if (state.viewMode === 'list') {
      setViewMode('both');
    }

    if (els.mapPanel && window.matchMedia('(max-width: 63.999rem)').matches) {
      els.mapPanel.scrollIntoView({
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        block: 'nearest',
      });
    }

    state.selectedLocId = loc.id;
    document.querySelectorAll('.loc-card.is-selected').forEach((el) => el.classList.remove('is-selected'));
    const activeCard = document.querySelector(`.loc-card[data-loc-id="${loc.id}"]`);
    if (activeCard) activeCard.classList.add('is-selected');

    if (state.map && state.styleLoaded) {
      state.pendingFocusId = null;
      const containerHeight = els.mapContainer?.clientHeight || 400;
      state.map.jumpTo({
        center: [loc.lng, loc.lat],
        zoom: loc.isMainCenter ? 14.5 : 14,
        padding: getPopupMapPadding(containerHeight),
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
    els.geoMessage.textContent = 'Radar sorted nearest drop-off bins first using your current coordinates.';
    els.geoActions.textContent = '';

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'btn btn--secondary';
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

    const form = document.createElement('form');
    form.className = 'loc-zip-quick';
    form.addEventListener('submit', (ev) => {
      ev.preventDefault();
      const val = zipInput.value.trim();
      if (!val) return;
      state.searchQuery = val;
      state.cityFilter = 'all';
      if (els.searchInput) els.searchInput.value = val;
      syncCityChipStates();
      clearGeoBanner();
      applyFilters(true);
    });

    const zipInput = document.createElement('input');
    zipInput.type = 'text';
    zipInput.inputMode = 'numeric';
    zipInput.maxLength = 5;
    zipInput.placeholder = 'e.g. 76148';
    zipInput.setAttribute('aria-label', 'Enter 5-digit Texas ZIP code');

    const zipBtn = document.createElement('button');
    zipBtn.type = 'submit';
    zipBtn.className = 'btn btn--primary';
    zipBtn.textContent = 'Find ZIP';

    const dismissBtn = document.createElement('button');
    dismissBtn.type = 'button';
    dismissBtn.className = 'btn btn--secondary';
    dismissBtn.textContent = 'Dismiss';
    dismissBtn.addEventListener('click', clearGeoBanner);

    form.append(zipInput, zipBtn, dismissBtn);
    els.geoActions.appendChild(form);
  }

  function applyFilters(shouldFitBounds = false) {
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
    if (els.nearMeBtn) els.nearMeBtn.setAttribute('aria-pressed', 'false');
    clearGeoBanner();
    syncCityChipStates();
    applyFilters(true);
  }

  function selectCity(cityName) {
    state.cityFilter = cityName || 'all';
    syncCityChipStates();
    applyFilters(true);
  }

  function openCitiesDialog() {
    if (!els.citiesDialog) return;
    if (typeof els.citiesDialog.showModal === 'function') {
      els.citiesDialog.showModal();
    } else {
      els.citiesDialog.setAttribute('open', '');
    }
  }

  function closeCitiesDialog() {
    if (!els.citiesDialog) return;
    if (typeof els.citiesDialog.close === 'function') {
      els.citiesDialog.close();
    } else {
      els.citiesDialog.removeAttribute('open');
    }
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
          applyFilters(false);
        }, 120);
      });

      els.searchInput.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && els.searchInput.value !== '') {
          ev.stopPropagation();
          state.searchQuery = '';
          els.searchInput.value = '';
          applyFilters(false);
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
        applyFilters(false);
      });
    }

    if (els.citySelect) {
      els.citySelect.addEventListener('change', () => {
        selectCity(els.citySelect.value || 'all');
      });
    }

    document.addEventListener('click', (ev) => {
      const target = ev.target instanceof Element ? ev.target : null;
      if (!target) return;

      const chip = target.closest('[data-city-chip]');
      if (chip) {
        selectCity(chip.dataset.cityChip || 'all');
        return;
      }

      const dialogCity = target.closest('[data-dialog-city]');
      if (dialogCity) {
        selectCity(dialogCity.dataset.dialogCity || 'all');
        closeCitiesDialog();
      }
    });

    if (els.openCitiesDialogBtn) {
      els.openCitiesDialogBtn.addEventListener('click', openCitiesDialog);
    }

    if (els.citiesDialogClose) {
      els.citiesDialogClose.addEventListener('click', closeCitiesDialog);
    }

    if (els.nearMeBtn) {
      els.nearMeBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
          showGeoFallbackBanner(
            'Geolocation is not supported in this browser. Pick your city above or jump directly to a 5-digit Texas ZIP:'
          );
          return;
        }

        const labelSpan = els.nearMeBtn.querySelector('span');
        const originalLabel = labelSpan ? labelSpan.textContent : 'NEAR ME';
        els.nearMeBtn.disabled = true;
        els.nearMeBtn.setAttribute('aria-busy', 'true');
        if (labelSpan) labelSpan.textContent = 'LOCATING...';

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
            applyFilters(false);

            const nearestMapped = state.filteredLocations.find((l) => l.geocoded);
            if (nearestMapped && state.map && state.styleLoaded) {
              const bounds = new maplibregl.LngLatBounds();
              bounds.extend([state.userCoords.lng, state.userCoords.lat]);
              bounds.extend([nearestMapped.lng, nearestMapped.lat]);
              state.map.fitBounds(bounds, {
                padding: 72,
                maxZoom: 13.2,
                duration: prefersReducedMotion() ? 0 : 550,
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
                ? "Location access isn't permitted here. Select your city above or enter a 5-digit Texas ZIP code:"
                : 'Unable to determine your location right now. Select your city above or enter a 5-digit Texas ZIP code:'
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
          syncCityChipStates();
          applyFilters(false);
          mainLoc = state.allLocations.find((l) => l.isMainCenter);
        }
        if (mainLoc) {
          focusLocationOnMap(mainLoc);
        } else if (state.map && state.styleLoaded) {
          state.map.flyTo({
            center: [mainLng, mainLat],
            zoom: 14.5,
            duration: prefersReducedMotion() ? 0 : 600,
          });
        }
      });
    }

    if (els.listEl) {
      els.listEl.addEventListener('click', (ev) => {
        const btn = ev.target instanceof Element ? ev.target.closest('[data-action="show-on-map"]') : null;
        if (!btn) return;
        const id = Number(btn.dataset.locId);
        const loc = state.allLocations.find((item) => item.id === id);
        if (loc) focusLocationOnMap(loc);
      });
    }

    for (const btn of els.viewBtns) {
      btn.addEventListener('click', () => {
        setViewMode(btn.dataset.viewMode || 'both');
      });
    }

    if (els.mapRetryBtn) {
      els.mapRetryBtn.addEventListener('click', initMap);
    }

    window.addEventListener('erc:themechange', (ev) => {
      const next = ev?.detail?.resolved || getResolvedTheme();
      applyMapTheme(next);
    });

    if (window.matchMedia) {
      const mql = window.matchMedia('(prefers-color-scheme: dark)');
      mql.addEventListener('change', () => {
        applyMapTheme(getResolvedTheme());
      });
    }
  }

  async function loadDataAndBoot() {
    let rawData = null;
    try {
      const resp = await fetch('/get-locations.php', {
        headers: { Accept: 'application/json' },
      });
      if (resp.ok) {
        const json = await resp.json();
        if (Array.isArray(json) && json.length > 0) {
          rawData = json;
        }
      }
    } catch {
      // Offline / dev fallback uses server-rendered seed JSON cleanly
    }

    if (!rawData) {
      rawData = readSsrSeed();
    }

    state.allLocations = normalizeLocations(rawData, globalPhone);
    recomputeCityCounts(state.allLocations);
    buildFuseIndex(state.allLocations);
    syncCountersAndCityControls();
    syncCityChipStates();
    computeFilteredLocations();
    renderDirectory();
    initMap();
  }

  bindUiEvents();
  loadDataAndBoot();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLocationsDirectory, { once: true });
} else {
  initLocationsDirectory();
}
