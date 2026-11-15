/**
 * v2 Interactive Missions Globe, 2013-2025 Shipping Telemetry, and Board Roster Controls.
 * Scope: /missions and /about
 * Zero external CDNs; zero unsafe innerHTML; full prefers-reduced-motion compliance.
 */

const WATAUGA_HQ = {
  name: 'Watauga HQ',
  lat: 32.8707,
  lng: -97.2621,
};

const REGION_META = {
  all: { label: 'All Regions', lat: 14, lng: -45 },
  americas: { label: 'The Americas', lat: 8, lng: -76 },
  africa: { label: 'Africa', lat: 1, lng: 22 },
  asia: { label: 'Asia', lat: 18, lng: 103 },
  europe: { label: 'Europe', lat: 48, lng: 26 },
  oceania: { label: 'Oceania (Fiji)', lat: -17.7, lng: 178.1 },
};

const COUNTRY_COORDS = {
  // The Americas (18)
  Belize: { lat: 17.19, lng: -88.5, region: 'americas', regionLabel: 'The Americas' },
  Bolivia: { lat: -16.29, lng: -63.59, region: 'americas', regionLabel: 'The Americas' },
  Brazil: { lat: -14.24, lng: -51.93, region: 'americas', regionLabel: 'The Americas' },
  Colombia: { lat: 4.57, lng: -74.3, region: 'americas', regionLabel: 'The Americas' },
  'Costa Rica': { lat: 9.75, lng: -83.75, region: 'americas', regionLabel: 'The Americas' },
  Cuba: { lat: 21.52, lng: -77.78, region: 'americas', regionLabel: 'The Americas' },
  'Dominican Republic': { lat: 18.74, lng: -70.16, region: 'americas', regionLabel: 'The Americas' },
  Ecuador: { lat: -1.83, lng: -78.18, region: 'americas', regionLabel: 'The Americas' },
  'El Salvador': { lat: 13.79, lng: -88.9, region: 'americas', regionLabel: 'The Americas' },
  Guatemala: { lat: 15.78, lng: -90.23, region: 'americas', regionLabel: 'The Americas' },
  Haiti: { lat: 18.97, lng: -72.29, region: 'americas', regionLabel: 'The Americas' },
  Honduras: { lat: 15.2, lng: -86.24, region: 'americas', regionLabel: 'The Americas' },
  Mexico: { lat: 23.63, lng: -102.55, region: 'americas', regionLabel: 'The Americas' },
  Nicaragua: { lat: 12.87, lng: -85.21, region: 'americas', regionLabel: 'The Americas' },
  Panama: { lat: 8.54, lng: -80.78, region: 'americas', regionLabel: 'The Americas' },
  Paraguay: { lat: -23.44, lng: -58.44, region: 'americas', regionLabel: 'The Americas' },
  Peru: { lat: -9.19, lng: -75.02, region: 'americas', regionLabel: 'The Americas' },
  Venezuela: { lat: 6.42, lng: -66.59, region: 'americas', regionLabel: 'The Americas' },
  // Africa (10)
  Cameroon: { lat: 7.37, lng: 12.35, region: 'africa', regionLabel: 'Africa' },
  Ethiopia: { lat: 9.15, lng: 40.49, region: 'africa', regionLabel: 'Africa' },
  Ghana: { lat: 7.95, lng: -1.02, region: 'africa', regionLabel: 'Africa' },
  Kenya: { lat: -0.02, lng: 37.91, region: 'africa', regionLabel: 'Africa' },
  Malawi: { lat: -13.25, lng: 34.3, region: 'africa', regionLabel: 'Africa' },
  Nigeria: { lat: 9.08, lng: 8.68, region: 'africa', regionLabel: 'Africa' },
  'South Africa': { lat: -30.56, lng: 22.94, region: 'africa', regionLabel: 'Africa' },
  Tanzania: { lat: -6.37, lng: 34.89, region: 'africa', regionLabel: 'Africa' },
  Uganda: { lat: 1.37, lng: 32.29, region: 'africa', regionLabel: 'Africa' },
  Zambia: { lat: -13.13, lng: 27.85, region: 'africa', regionLabel: 'Africa' },
  // Asia (5)
  Bangladesh: { lat: 23.68, lng: 90.36, region: 'asia', regionLabel: 'Asia' },
  Cambodia: { lat: 12.57, lng: 104.99, region: 'asia', regionLabel: 'Asia' },
  Nepal: { lat: 28.39, lng: 84.12, region: 'asia', regionLabel: 'Asia' },
  Philippines: { lat: 12.88, lng: 121.77, region: 'asia', regionLabel: 'Asia' },
  Vietnam: { lat: 14.06, lng: 108.28, region: 'asia', regionLabel: 'Asia' },
  // Europe (4)
  Moldova: { lat: 47.41, lng: 28.37, region: 'europe', regionLabel: 'Europe' },
  Poland: { lat: 51.92, lng: 19.15, region: 'europe', regionLabel: 'Europe' },
  Romania: { lat: 45.94, lng: 24.97, region: 'europe', regionLabel: 'Europe' },
  Ukraine: { lat: 48.38, lng: 31.17, region: 'europe', regionLabel: 'Europe' },
  // Oceania (1)
  Fiji: { lat: -17.71, lng: 178.07, region: 'oceania', regionLabel: 'Oceania (Fiji)' },
};

// Simplified continental land polygons ([lng, lat]) for local 3D dot-matrix sphere
const LAND_POLYGONS = [
  // North & Central America
  [
    [-168, 65], [-160, 71], [-140, 70], [-126, 71], [-110, 74], [-92, 74],
    [-80, 68], [-64, 62], [-55, 52], [-65, 45], [-74, 40], [-81, 30],
    [-80, 25], [-83, 29], [-90, 29], [-97, 26], [-96, 19], [-90, 21],
    [-87, 21], [-88, 16], [-83, 14], [-83, 9], [-78, 8], [-80, 7],
    [-85, 10], [-92, 14], [-105, 19], [-110, 24], [-114, 31], [-117, 32],
    [-124, 41], [-125, 49], [-136, 58], [-152, 60], [-164, 55], [-168, 65],
  ],
  // Greenland
  [
    [-58, 76], [-42, 83], [-20, 81], [-18, 72], [-34, 66], [-44, 60],
    [-52, 66], [-58, 76],
  ],
  // Caribbean (Cuba + Hispaniola)
  [
    [-85, 22], [-75, 20], [-74, 21], [-83, 23], [-85, 22],
  ],
  [
    [-74, 19], [-68, 18], [-69, 20], [-74, 20], [-74, 19],
  ],
  // South America
  [
    [-78, 9], [-72, 12], [-62, 10], [-50, 1], [-35, -6], [-38, -14],
    [-40, -22], [-48, -26], [-53, -34], [-63, -41], [-68, -54], [-74, -52],
    [-73, -40], [-71, -30], [-70, -18], [-77, -12], [-81, -5], [-79, 2], [-78, 9],
  ],
  // Africa
  [
    [-17, 15], [-13, 28], [-6, 36], [10, 37], [12, 33], [25, 32],
    [33, 31], [38, 22], [43, 12], [51, 12], [46, 2], [40, -10],
    [35, -24], [28, -33], [18, -35], [14, -22], [12, -6], [9, 4],
    [2, 6], [-8, 5], [-15, 11], [-17, 15],
  ],
  // Madagascar
  [
    [44, -25], [47, -25], [50, -15], [49, -12], [44, -18], [44, -25],
  ],
  // Europe
  [
    [-9, 37], [-9, 43], [-2, 44], [-5, 48], [2, 51], [8, 54],
    [6, 59], [5, 62], [18, 70], [28, 71], [32, 69], [30, 60],
    [22, 60], [21, 55], [29, 58], [40, 66], [42, 43], [36, 46],
    [28, 41], [23, 37], [16, 38], [12, 43], [7, 44], [3, 42], [-5, 36], [-9, 37],
  ],
  // Great Britain & Ireland
  [
    [-6, 50], [-5, 58], [-1, 58], [2, 52], [-6, 50],
  ],
  [
    [-10, 52], [-6, 55], [-6, 51], [-10, 52],
  ],
  // Asia + Middle East + India + SE Asia
  [
    [27, 41], [41, 42], [48, 46], [58, 52], [60, 69], [85, 73],
    [115, 73], [145, 70], [178, 66], [165, 58], [156, 51], [141, 53],
    [136, 44], [128, 35], [122, 38], [121, 31], [116, 22], [108, 20],
    [109, 12], [104, 10], [100, 13], [103, 2], [99, 8], [96, 16],
    [90, 22], [82, 15], [77, 8], [72, 20], [67, 25], [56, 26],
    [59, 22], [52, 15], [44, 13], [35, 29], [35, 37], [27, 41],
  ],
  // Japan
  [
    [130, 31], [136, 34], [141, 38], [145, 44], [140, 42], [136, 37], [130, 33], [130, 31],
  ],
  // Philippines & Indonesia clusters
  [
    [120, 18], [126, 12], [125, 6], [120, 12], [120, 18],
  ],
  [
    [96, 5], [106, -6], [115, -8], [118, -2], [110, 1], [103, -2], [96, 5],
  ],
  // Australia
  [
    [114, -22], [123, -16], [131, -12], [136, -12], [142, -11], [149, -20],
    [153, -28], [150, -37], [141, -38], [131, -32], [115, -34], [113, -26], [114, -22],
  ],
  // New Zealand
  [
    [167, -46], [173, -41], [178, -37], [174, -44], [167, -46],
  ],
];

const DEG2RAD = Math.PI / 180;

function latLngToVec(lat, lng) {
  const phi = lat * DEG2RAD;
  const lambda = lng * DEG2RAD;
  const cosPhi = Math.cos(phi);
  return {
    x: cosPhi * Math.sin(lambda),
    y: Math.sin(phi),
    z: cosPhi * Math.cos(lambda),
  };
}

function pointInPolygon(lng, lat, poly) {
  let inside = false;
  for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
    const xi = poly[i][0];
    const yi = poly[i][1];
    const xj = poly[j][0];
    const yj = poly[j][1];
    const intersect =
      yi > lat !== yj > lat &&
      lng < ((xj - xi) * (lat - yi)) / (yj - yi + 1e-12) + xi;
    if (intersect) inside = !inside;
  }
  return inside;
}

function isLand(lat, lng) {
  for (let i = 0; i < LAND_POLYGONS.length; i++) {
    if (pointInPolygon(lng, lat, LAND_POLYGONS[i])) {
      return true;
    }
  }
  return false;
}

function buildFibonacciLandDots(totalSamples = 4600) {
  const dots = [];
  const goldenAngle = Math.PI * (3 - Math.sqrt(5));
  for (let i = 0; i < totalSamples; i++) {
    const y = 1 - (i / (totalSamples - 1)) * 2;
    const radiusAtY = Math.sqrt(Math.max(0, 1 - y * y));
    const theta = goldenAngle * i;
    const x = Math.sin(theta) * radiusAtY;
    const z = Math.cos(theta) * radiusAtY;
    const lat = Math.asin(y) / DEG2RAD;
    const lng = Math.atan2(x, z) / DEG2RAD;
    if (isLand(lat, lng)) {
      dots.push({ x, y, z, lat, lng });
    }
  }
  // Ensure Fiji cluster has visible land dots
  dots.push(latLngToVec(-17.8, 178.0));
  dots.push(latLngToVec(-16.6, 179.2));
  return dots;
}

function rotatePoint(p, rotX, rotY) {
  const cosY = Math.cos(rotY);
  const sinY = Math.sin(rotY);
  const x1 = p.x * cosY + p.z * sinY;
  const z1 = -p.x * sinY + p.z * cosY;

  const cosX = Math.cos(rotX);
  const sinX = Math.sin(rotX);
  const y2 = p.y * cosX - z1 * sinX;
  const z2 = p.y * sinX + z1 * cosX;

  return { x: x1, y: y2, z: z2 };
}

function buildArcPoints(originVec, destVec, segments = 34) {
  const dot = Math.min(1, Math.max(-1, originVec.x * destVec.x + originVec.y * destVec.y + originVec.z * destVec.z));
  const omega = Math.acos(dot);
  const sinOmega = Math.sin(omega);
  const peak = 0.08 + 0.25 * (omega / Math.PI);
  const pts = [];

  for (let i = 0; i <= segments; i++) {
    const t = i / segments;
    let vx;
    let vy;
    let vz;
    if (sinOmega < 1e-4) {
      vx = originVec.x;
      vy = originVec.y;
      vz = originVec.z;
    } else {
      const w1 = Math.sin((1 - t) * omega) / sinOmega;
      const w2 = Math.sin(t * omega) / sinOmega;
      vx = w1 * originVec.x + w2 * destVec.x;
      vy = w1 * originVec.y + w2 * destVec.y;
      vz = w1 * originVec.z + w2 * destVec.z;
    }
    const elev = 1 + peak * Math.sin(Math.PI * t);
    pts.push({
      x: vx * elev,
      y: vy * elev,
      z: vz * elev,
      t,
    });
  }

  return {
    points: pts,
    distanceKm: Math.round(omega * 6371),
  };
}

function pointToSegmentDistSq(px, py, ax, ay, bx, by) {
  const dx = bx - ax;
  const dy = by - ay;
  const lenSq = dx * dx + dy * dy;
  if (lenSq === 0) return (px - ax) ** 2 + (py - ay) ** 2;
  const t = Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / lenSq));
  const projX = ax + t * dx;
  const projY = ay + t * dy;
  return (px - projX) ** 2 + (py - projY) ** 2;
}

function getGlobePalette() {
  const attr = document.documentElement.getAttribute('data-theme');
  const isDark =
    attr === 'dark' ||
    (attr !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

  if (isDark) {
    return {
      isDark: true,
      sphereBg: '#0b131c',
      sphereEdge: '#182838',
      halo: 'rgba(34, 211, 238, 0.22)',
      rimStroke: 'rgba(143, 168, 194, 0.28)',
      dotBack: 'rgba(139, 155, 176, 0.14)',
      dotFront: 'rgba(180, 200, 222, 0.84)',
      arcMuted: 'rgba(229, 83, 58, 0.16)',
      arcBase: 'rgba(229, 83, 58, 0.56)',
      arcActive: '#ff6a4d',
      pulseDot: '#ffd6cc',
      pinMuted: 'rgba(229, 83, 58, 0.35)',
      pinBase: '#ff6a4d',
      pinCore: '#ffffff',
      hqRing: '#22d3ee',
      hqCore: '#ffffff',
    };
  }

  return {
    isDark: false,
    sphereBg: '#dce6f2',
    sphereEdge: '#c5d4e6',
    halo: 'rgba(14, 116, 144, 0.16)',
    rimStroke: 'rgba(20, 32, 43, 0.22)',
    dotBack: 'rgba(51, 65, 80, 0.14)',
    dotFront: 'rgba(20, 32, 43, 0.72)',
    arcMuted: 'rgba(184, 50, 39, 0.16)',
    arcBase: 'rgba(184, 50, 39, 0.58)',
    arcActive: '#b83227',
    pulseDot: '#14202b',
    pinMuted: 'rgba(184, 50, 39, 0.32)',
    pinBase: '#b83227',
    pinCore: '#ffffff',
    hqRing: '#0e7490',
    hqCore: '#14202b',
  };
}

function initMissionGlobe() {
  const canvas = document.getElementById('missionGlobeCanvas');
  if (!(canvas instanceof HTMLCanvasElement)) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const shell = document.querySelector('[data-globe-shell]');
  const toggleBtn = document.getElementById('globeToggleBtn');
  const toggleLabel = toggleBtn ? toggleBtn.querySelector('[data-globe-toggle-label]') : null;
  const readoutRoute = document.querySelector('[data-readout-route]');
  const readoutDetail = document.querySelector('[data-readout-detail]');
  const tooltipEl = document.getElementById('globeHoverTooltip');
  const tooltipCountry = tooltipEl ? tooltipEl.querySelector('[data-tooltip-country]') : null;
  const tooltipMeta = tooltipEl ? tooltipEl.querySelector('[data-tooltip-meta]') : null;

  const regionButtons = Array.from(document.querySelectorAll('[data-globe-region]'));
  const countryChips = Array.from(document.querySelectorAll('[data-focus-country]'));
  const searchInput = document.querySelector('[data-country-search]');
  const regionPanels = Array.from(document.querySelectorAll('[data-region-card]'));
  const countryItems = Array.from(document.querySelectorAll('[data-country-item]'));
  const statusEl = document.querySelector('[data-country-status]');
  const emptyEl = document.querySelector('[data-country-empty]');
  const resetBtn = document.querySelector('[data-country-reset]');

  const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  let isReducedMotion = reduceMotionQuery.matches;

  const originVec = latLngToVec(WATAUGA_HQ.lat, WATAUGA_HQ.lng);
  const landDots = buildFibonacciLandDots(4600);

  const destinations = Object.entries(COUNTRY_COORDS).map(([name, info]) => {
    const vec = latLngToVec(info.lat, info.lng);
    const arc = buildArcPoints(originVec, vec, 34);
    return {
      name,
      lat: info.lat,
      lng: info.lng,
      region: info.region,
      regionLabel: info.regionLabel,
      vec,
      arcPoints: arc.points,
      distanceKm: arc.distanceKm,
    };
  });

  // Initial camera orientation: show Americas + Atlantic + Africa/Europe
  let rotY = -REGION_META.all.lng * DEG2RAD;
  let rotX = REGION_META.all.lat * DEG2RAD;
  let velY = 0;
  let velX = 0;
  let targetRotY = null;
  let targetRotX = null;

  let isPaused = isReducedMotion;
  let isDragging = false;
  let dragStartX = 0;
  let dragStartY = 0;
  let lastPointerX = 0;
  let lastPointerY = 0;

  let activeRegion = 'all';
  let searchQuery = '';
  let hoveredCountry = null;
  let selectedCountry = null;

  let canvasWidth = 520;
  let canvasHeight = 520;
  let palette = getGlobePalette();
  let projectedPins = [];
  let projectedArcSegments = [];
  let rafId = 0;
  let lastTime = performance.now();
  let animPhase = 0;

  function syncToggleUI() {
    if (!toggleBtn) return;
    toggleBtn.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
    if (toggleLabel) {
      toggleLabel.textContent = isPaused ? 'RESUME GLOBE' : 'PAUSE GLOBE';
    }
  }

  function updateReadout(dest) {
    if (!readoutRoute || !readoutDetail) return;
    if (!dest) {
      readoutRoute.textContent = 'WATAUGA HQ \u2192 GLOBAL NETWORK (38 DESTINATIONS)';
      readoutDetail.textContent =
        'Hover or tap any destination pin or geodesic arc to inspect route distance and 50-pair box dispatch format.';
      return;
    }
    readoutRoute.textContent = `WATAUGA HQ \u2192 ${dest.name.toUpperCase()}`;
    readoutDetail.textContent = `${dest.regionLabel} \u00B7 Great-circle arc: ${dest.distanceKm.toLocaleString('en-US')} km \u00B7 Shipped in 50-pair diopter boxes.`;
  }

  function updateTooltip(dest, px, py) {
    if (!tooltipEl || !tooltipCountry || !tooltipMeta) return;
    if (!dest) {
      tooltipEl.hidden = true;
      tooltipEl.setAttribute('aria-hidden', 'true');
      return;
    }
    tooltipCountry.textContent = dest.name;
    tooltipMeta.textContent = `${dest.regionLabel} \u00B7 ${dest.distanceKm.toLocaleString('en-US')} KM`;
    const clampedX = Math.max(76, Math.min(canvasWidth - 76, px));
    const clampedY = Math.max(58, Math.min(canvasHeight - 16, py));
    tooltipEl.style.left = `${clampedX}px`;
    tooltipEl.style.top = `${clampedY}px`;
    tooltipEl.hidden = false;
    tooltipEl.setAttribute('aria-hidden', 'false');
  }

  function focusCoordinates(lat, lng) {
    const nextY = -lng * DEG2RAD;
    const nextX = Math.max(-1.05, Math.min(1.05, lat * DEG2RAD));
    if (isReducedMotion) {
      rotY = nextY;
      rotX = nextX;
      targetRotY = null;
      targetRotX = null;
      drawFrame(0);
    } else {
      targetRotY = nextY;
      targetRotX = nextX;
      velY = 0;
      velX = 0;
    }
  }

  function applyDirectoryFilters() {
    const query = searchQuery.trim().toLowerCase();
    let visibleCount = 0;

    countryItems.forEach((item) => {
      const name = (item.getAttribute('data-country-name') || '').toLowerCase();
      const region = item.getAttribute('data-country-region') || '';
      const matchesRegion = activeRegion === 'all' || region === activeRegion;
      const matchesQuery = query === '' || name.includes(query);
      const show = matchesRegion && matchesQuery;
      item.hidden = !show;
      if (show) visibleCount += 1;
    });

    regionPanels.forEach((panel) => {
      const items = Array.from(panel.querySelectorAll('[data-country-item]'));
      const openItems = items.filter((el) => !el.hidden);
      panel.hidden = openItems.length === 0;
      const badge = panel.querySelector('[data-region-count]');
      if (badge) {
        badge.textContent = String(openItems.length);
      }
    });

    if (emptyEl) {
      emptyEl.hidden = visibleCount > 0;
    }

    if (statusEl) {
      const regionLabel = REGION_META[activeRegion]?.label || 'All Regions';
      if (query !== '') {
        statusEl.textContent = `Showing ${visibleCount} destination${visibleCount === 1 ? '' : 's'} matching "${searchQuery.trim()}" in ${regionLabel}.`;
      } else {
        statusEl.textContent = `Showing ${visibleCount} documented destination${visibleCount === 1 ? '' : 's'} (${regionLabel}).`;
      }
    }
  }

  function resizeCanvas() {
    const rect = shell ? shell.getBoundingClientRect() : canvas.getBoundingClientRect();
    const size = Math.max(240, Math.round(rect.width || 480));
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvasWidth = size;
    canvasHeight = size;
    canvas.width = Math.round(size * dpr);
    canvas.height = Math.round(size * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function drawFrame(dtSeconds) {
    if (!isPaused && !isDragging && !isReducedMotion) {
      animPhase = (animPhase + dtSeconds * 0.35) % 1;
      if (targetRotY === null) {
        rotY -= dtSeconds * 0.16;
      }
    }

    if (targetRotY !== null && targetRotX !== null) {
      // Shortest angular path for yaw
      let diffY = targetRotY - rotY;
      while (diffY > Math.PI) diffY -= Math.PI * 2;
      while (diffY < -Math.PI) diffY += Math.PI * 2;
      const diffX = targetRotX - rotX;
      rotY += diffY * Math.min(1, dtSeconds * 6.5);
      rotX += diffX * Math.min(1, dtSeconds * 6.5);
      if (Math.abs(diffY) < 0.008 && Math.abs(diffX) < 0.008) {
        rotY = targetRotY;
        rotX = targetRotX;
        targetRotY = null;
        targetRotX = null;
      }
    } else if (!isDragging && (Math.abs(velY) > 0.0001 || Math.abs(velX) > 0.0001)) {
      rotY += velY;
      rotX = Math.max(-1.15, Math.min(1.15, rotX + velX));
      velY *= 0.92;
      velX *= 0.92;
    }

    ctx.clearRect(0, 0, canvasWidth, canvasHeight);

    const cx = canvasWidth / 2;
    const cy = canvasHeight / 2;
    const R = canvasWidth * 0.41;

    // 1. Atmospheric halo
    const haloGrad = ctx.createRadialGradient(cx, cy, R * 0.72, cx, cy, R * 1.2);
    haloGrad.addColorStop(0, palette.halo);
    haloGrad.addColorStop(1, 'transparent');
    ctx.fillStyle = haloGrad;
    ctx.beginPath();
    ctx.arc(cx, cy, R * 1.2, 0, Math.PI * 2);
    ctx.fill();

    // 2. Sphere base fill
    const sphereGrad = ctx.createRadialGradient(cx - R * 0.25, cy - R * 0.28, R * 0.1, cx, cy, R);
    sphereGrad.addColorStop(0, palette.sphereEdge);
    sphereGrad.addColorStop(1, palette.sphereBg);
    ctx.fillStyle = sphereGrad;
    ctx.beginPath();
    ctx.arc(cx, cy, R, 0, Math.PI * 2);
    ctx.fill();

    ctx.strokeStyle = palette.rimStroke;
    ctx.lineWidth = 1.25;
    ctx.stroke();

    // 3. Dot-matrix continents (back hemisphere first, then front hemisphere)
    const dotRadius = Math.max(1.05, R * 0.0072);
    ctx.fillStyle = palette.dotBack;
    ctx.beginPath();
    const frontDots = [];
    for (let i = 0; i < landDots.length; i++) {
      const rp = rotatePoint(landDots[i], rotX, rotY);
      const sx = cx + rp.x * R;
      const sy = cy - rp.y * R;
      if (rp.z < 0) {
        ctx.moveTo(sx + dotRadius * 0.85, sy);
        ctx.arc(sx, sy, dotRadius * 0.85, 0, Math.PI * 2);
      } else {
        frontDots.push({ sx, sy, z: rp.z });
      }
    }
    ctx.fill();

    ctx.fillStyle = palette.dotFront;
    ctx.beginPath();
    for (let i = 0; i < frontDots.length; i++) {
      const d = frontDots[i];
      const rScale = 0.75 + 0.35 * d.z;
      ctx.moveTo(d.sx + dotRadius * rScale, d.sy);
      ctx.arc(d.sx, d.sy, dotRadius * rScale, 0, Math.PI * 2);
    }
    ctx.fill();

    // 4. Geodesic arcs from Watauga HQ
    projectedArcSegments = [];
    const focusedName = hoveredCountry?.name || selectedCountry?.name || null;

    destinations.forEach((dest) => {
      const matchesFilter = activeRegion === 'all' || dest.region === activeRegion;
      const isHighlighted = focusedName === dest.name;

      let strokeColor = palette.arcMuted;
      let lineWidth = 1.05;
      if (isHighlighted) {
        strokeColor = palette.arcActive;
        lineWidth = 2.4;
      } else if (matchesFilter) {
        strokeColor = palette.arcBase;
        lineWidth = 1.35;
      }

      ctx.strokeStyle = strokeColor;
      ctx.lineWidth = lineWidth;
      ctx.beginPath();

      let drawing = false;
      let prevScreen = null;
      const visibleArcPoints = [];

      for (let i = 0; i < dest.arcPoints.length; i++) {
        const pt = dest.arcPoints[i];
        const rp = rotatePoint(pt, rotX, rotY);
        const distFromCenterSq = rp.x * rp.x + rp.y * rp.y;
        const occluded = rp.z < 0 && distFromCenterSq < 1.0;

        if (!occluded) {
          const sx = cx + rp.x * R;
          const sy = cy - rp.y * R;
          if (!drawing) {
            ctx.moveTo(sx, sy);
            drawing = true;
          } else {
            ctx.lineTo(sx, sy);
            if (prevScreen && matchesFilter) {
              projectedArcSegments.push({
                dest,
                ax: prevScreen.sx,
                ay: prevScreen.sy,
                bx: sx,
                by: sy,
              });
            }
          }
          prevScreen = { sx, sy };
          visibleArcPoints.push({ sx, sy, t: pt.t });
        } else {
          drawing = false;
          prevScreen = null;
        }
      }
      ctx.stroke();

      // Subtle traveling light pulse along active arcs when animated
      if (!isReducedMotion && !isPaused && (matchesFilter || isHighlighted) && visibleArcPoints.length > 2) {
        const offset = (dest.name.length * 0.17) % 1;
        const pulseT = (animPhase + offset) % 1;
        let closest = null;
        let bestDelta = 1;
        for (let k = 0; k < visibleArcPoints.length; k++) {
          const delta = Math.abs(visibleArcPoints[k].t - pulseT);
          if (delta < bestDelta) {
            bestDelta = delta;
            closest = visibleArcPoints[k];
          }
        }
        if (closest && bestDelta < 0.08) {
          ctx.fillStyle = palette.pulseDot;
          ctx.beginPath();
          ctx.arc(closest.sx, closest.sy, isHighlighted ? 3 : 2, 0, Math.PI * 2);
          ctx.fill();
        }
      }
    });

    // 5. Destination pins (front hemisphere)
    projectedPins = [];
    destinations.forEach((dest) => {
      const rp = rotatePoint(dest.vec, rotX, rotY);
      if (rp.z < -0.04) return;
      const sx = cx + rp.x * R;
      const sy = cy - rp.y * R;
      const matchesFilter = activeRegion === 'all' || dest.region === activeRegion;
      const isHighlighted = focusedName === dest.name;

      projectedPins.push({ dest, sx, sy, z: rp.z });

      if (isHighlighted) {
        ctx.strokeStyle = palette.pinBase;
        ctx.lineWidth = 1.6;
        ctx.beginPath();
        ctx.arc(sx, sy, 8.5, 0, Math.PI * 2);
        ctx.stroke();
      }

      ctx.fillStyle = isHighlighted ? palette.arcActive : matchesFilter ? palette.pinBase : palette.pinMuted;
      ctx.beginPath();
      ctx.arc(sx, sy, isHighlighted ? 4.4 : matchesFilter ? 3.2 : 2.1, 0, Math.PI * 2);
      ctx.fill();

      if (matchesFilter || isHighlighted) {
        ctx.fillStyle = palette.pinCore;
        ctx.beginPath();
        ctx.arc(sx, sy, isHighlighted ? 1.7 : 1.15, 0, Math.PI * 2);
        ctx.fill();
      }
    });

    // 6. Watauga HQ origin beacon
    const hqRot = rotatePoint(originVec, rotX, rotY);
    if (hqRot.z > -0.05) {
      const hqx = cx + hqRot.x * R;
      const hqy = cy - hqRot.y * R;
      ctx.strokeStyle = palette.hqRing;
      ctx.lineWidth = 1.8;
      ctx.beginPath();
      ctx.arc(hqx, hqy, 8, 0, Math.PI * 2);
      ctx.stroke();

      ctx.fillStyle = palette.hqCore;
      ctx.beginPath();
      ctx.arc(hqx, hqy, 3.6, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  function findHitAt(clientX, clientY) {
    const rect = canvas.getBoundingClientRect();
    const px = clientX - rect.left;
    const py = clientY - rect.top;

    let bestPin = null;
    let bestPinDistSq = 15 * 15;
    for (let i = 0; i < projectedPins.length; i++) {
      const pin = projectedPins[i];
      const dSq = (px - pin.sx) ** 2 + (py - pin.sy) ** 2;
      if (dSq < bestPinDistSq) {
        bestPinDistSq = dSq;
        bestPin = pin;
      }
    }
    if (bestPin) {
      return { dest: bestPin.dest, x: bestPin.sx, y: bestPin.sy };
    }

    let bestArcDest = null;
    let bestArcDistSq = 9 * 9;
    for (let i = 0; i < projectedArcSegments.length; i++) {
      const seg = projectedArcSegments[i];
      const dSq = pointToSegmentDistSq(px, py, seg.ax, seg.ay, seg.bx, seg.by);
      if (dSq < bestArcDistSq) {
        bestArcDistSq = dSq;
        bestArcDest = seg.dest;
      }
    }
    if (bestArcDest) {
      return { dest: bestArcDest, x: px, y: py };
    }

    return null;
  }

  function loop(now) {
    const dt = Math.min(0.05, Math.max(0, (now - lastTime) / 1000));
    lastTime = now;
    drawFrame(dt);
    rafId = window.requestAnimationFrame(loop);
  }

  function setSelectedCountry(dest) {
    selectedCountry = dest;
    countryChips.forEach((chip) => {
      const match = dest && chip.getAttribute('data-focus-country') === dest.name;
      chip.classList.toggle('is-selected', Boolean(match));
      chip.setAttribute('aria-pressed', match ? 'true' : 'false');
    });
    updateReadout(dest);
  }

  // Pointer drag + hover events
  canvas.addEventListener('pointerdown', (event) => {
    if (event.button !== 0) return;
    isDragging = true;
    targetRotY = null;
    targetRotX = null;
    dragStartX = event.clientX;
    dragStartY = event.clientY;
    lastPointerX = event.clientX;
    lastPointerY = event.clientY;
    velY = 0;
    velX = 0;
    canvas.classList.add('is-dragging');
    canvas.setPointerCapture(event.pointerId);
  });

  canvas.addEventListener('pointermove', (event) => {
    if (isDragging) {
      const dx = event.clientX - lastPointerX;
      const dy = event.clientY - lastPointerY;
      lastPointerX = event.clientX;
      lastPointerY = event.clientY;
      const sensitivity = 0.0058;
      rotY += dx * sensitivity;
      rotX = Math.max(-1.15, Math.min(1.15, rotX + dy * sensitivity));
      velY = dx * sensitivity * 0.65;
      velX = dy * sensitivity * 0.65;
      updateTooltip(null, 0, 0);
      if (isPaused || isReducedMotion) {
        drawFrame(0);
      }
      return;
    }

    const hit = findHitAt(event.clientX, event.clientY);
    if (hit) {
      hoveredCountry = hit.dest;
      canvas.style.cursor = 'pointer';
      updateTooltip(hit.dest, hit.x, hit.y);
      updateReadout(hit.dest);
    } else {
      if (hoveredCountry) {
        hoveredCountry = null;
        canvas.style.cursor = 'grab';
        updateTooltip(null, 0, 0);
        updateReadout(selectedCountry);
      }
    }
    if (isPaused || isReducedMotion) {
      drawFrame(0);
    }
  });

  const endDrag = (event) => {
    if (!isDragging) return;
    isDragging = false;
    canvas.classList.remove('is-dragging');
    const moved = Math.hypot(event.clientX - dragStartX, event.clientY - dragStartY);
    if (moved < 6) {
      const hit = findHitAt(event.clientX, event.clientY);
      if (hit) {
        setSelectedCountry(hit.dest);
        focusCoordinates(hit.dest.lat, hit.dest.lng);
      }
    }
    if (isPaused || isReducedMotion) {
      drawFrame(0);
    }
  };

  canvas.addEventListener('pointerup', endDrag);
  canvas.addEventListener('pointercancel', endDrag);
  canvas.addEventListener('pointerleave', () => {
    if (!isDragging && hoveredCountry) {
      hoveredCountry = null;
      canvas.style.cursor = 'grab';
      updateTooltip(null, 0, 0);
      updateReadout(selectedCountry);
      if (isPaused || isReducedMotion) {
        drawFrame(0);
      }
    }
  });

  // Pause / Resume toggle
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      isPaused = !isPaused;
      syncToggleUI();
      if (!isPaused && isReducedMotion) {
        // Explicit user opt-in overrides initial reduced-motion pause cleanly for this session
        isReducedMotion = false;
      }
      drawFrame(0);
    });
  }

  // Region filter pills
  regionButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const nextRegion = btn.getAttribute('data-globe-region') || 'all';
      activeRegion = nextRegion;
      regionButtons.forEach((b) => {
        const isCurrent = b === btn;
        b.classList.toggle('is-active', isCurrent);
        b.setAttribute('aria-pressed', isCurrent ? 'true' : 'false');
      });
      const meta = REGION_META[nextRegion] || REGION_META.all;
      focusCoordinates(meta.lat, meta.lng);
      setSelectedCountry(null);
      applyDirectoryFilters();
      if (isPaused || isReducedMotion) {
        drawFrame(0);
      }
    });
  });

  // Country chip click -> focus globe on that destination
  countryChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const countryName = chip.getAttribute('data-focus-country') || '';
      const dest = destinations.find((d) => d.name === countryName);
      if (!dest) return;
      setSelectedCountry(dest);
      focusCoordinates(dest.lat, dest.lng);
      if (isPaused || isReducedMotion) {
        drawFrame(0);
      }
    });
  });

  // Search filter
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      searchQuery = searchInput.value || '';
      applyDirectoryFilters();
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      searchQuery = '';
      if (searchInput) searchInput.value = '';
      activeRegion = 'all';
      regionButtons.forEach((b) => {
        const isAll = b.getAttribute('data-globe-region') === 'all';
        b.classList.toggle('is-active', isAll);
        b.setAttribute('aria-pressed', isAll ? 'true' : 'false');
      });
      setSelectedCountry(null);
      applyDirectoryFilters();
      focusCoordinates(REGION_META.all.lat, REGION_META.all.lng);
    });
  }

  // Theme & motion listeners
  window.addEventListener('erc:themechange', () => {
    palette = getGlobePalette();
    drawFrame(0);
  });

  reduceMotionQuery.addEventListener('change', (event) => {
    isReducedMotion = event.matches;
    if (isReducedMotion) {
      isPaused = true;
    }
    syncToggleUI();
    drawFrame(0);
  });

  if (typeof ResizeObserver !== 'undefined' && shell) {
    const ro = new ResizeObserver(() => {
      resizeCanvas();
      drawFrame(0);
    });
    ro.observe(shell);
  } else {
    window.addEventListener('resize', () => {
      resizeCanvas();
      drawFrame(0);
    }, { passive: true });
  }

  syncToggleUI();
  resizeCanvas();
  applyDirectoryFilters();
  drawFrame(0);
  rafId = window.requestAnimationFrame(loop);

  window.addEventListener('pagehide', () => {
    if (rafId) window.cancelAnimationFrame(rafId);
  });
}

function initShippingTelemetry() {
  const section = document.querySelector('[data-shipping-section]');
  if (!section) return;

  const chartReadout = section.querySelector('[data-chart-readout]');
  const barButtons = Array.from(section.querySelectorAll('[data-chart-year]'));

  barButtons.forEach((btn) => {
    const updateBarReadout = () => {
      const yr = btn.getAttribute('data-chart-year') || '';
      const foreign = btn.getAttribute('data-chart-foreign') || '0';
      const local = btn.getAttribute('data-chart-local') || '0';
      const total = btn.getAttribute('data-chart-total') || '0';
      if (chartReadout) {
        chartReadout.textContent = `${yr}: ${foreign} FOREIGN + ${local} LOCAL = ${total} PAIRS`;
      }
    };

    btn.addEventListener('click', () => {
      barButtons.forEach((b) => b.classList.toggle('is-selected', b === btn));
      updateBarReadout();
    });
    btn.addEventListener('focus', updateBarReadout);
    btn.addEventListener('mouseenter', updateBarReadout);
  });

  const controls = section.querySelector('[data-shipping-controls]');
  const sortButtons = Array.from(section.querySelectorAll('[data-sort-mode]'));
  const table = section.querySelector('[data-shipping-table]');
  const tbody = table ? table.querySelector('tbody') : null;
  const statusEl = section.querySelector('[data-shipping-status]');
  const yearHeader = section.querySelector('[data-col-year]');
  const totalHeader = section.querySelector('[data-col-total]');

  if (controls) controls.hidden = false;
  if (!tbody || sortButtons.length === 0) return;

  const rows = Array.from(tbody.querySelectorAll('tr'));

  sortButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const mode = btn.getAttribute('data-sort-mode') || 'year-asc';
      const sorted = [...rows].sort((a, b) => {
        const yA = Number(a.getAttribute('data-year-start') || 0);
        const yB = Number(b.getAttribute('data-year-start') || 0);
        const tA = Number(a.getAttribute('data-combined') || 0);
        const tB = Number(b.getAttribute('data-combined') || 0);

        if (mode === 'year-desc') return yB - yA;
        if (mode === 'total-desc') return tB - tA || yB - yA;
        return yA - yB;
      });

      sorted.forEach((row) => tbody.appendChild(row));

      sortButtons.forEach((b) => {
        const active = b === btn;
        b.classList.toggle('is-active', active);
        b.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      if (yearHeader && totalHeader) {
        if (mode === 'year-asc') {
          yearHeader.setAttribute('aria-sort', 'ascending');
          totalHeader.removeAttribute('aria-sort');
        } else if (mode === 'year-desc') {
          yearHeader.setAttribute('aria-sort', 'descending');
          totalHeader.removeAttribute('aria-sort');
        } else {
          yearHeader.removeAttribute('aria-sort');
          totalHeader.setAttribute('aria-sort', 'descending');
        }
      }

      if (statusEl) {
        statusEl.textContent =
          mode === 'total-desc'
            ? 'Sorted shipping ledger by highest combined pairs.'
            : mode === 'year-desc'
              ? 'Sorted shipping ledger newest Lion Year first.'
              : 'Sorted shipping ledger chronologically (2013 to 2025).';
      }
    });
  });
}

function initBoardRosterFilter() {
  const root = document.querySelector('[data-board-roster]');
  if (!root) return;

  const buttons = Array.from(root.querySelectorAll('[data-board-filter]'));
  const currentGroup = root.querySelector('[data-board-group="current"]');
  const previousGroup = root.querySelector('[data-board-group="previous"]');
  const statusEl = root.querySelector('[data-board-status]');

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const filter = btn.getAttribute('data-board-filter') || 'all';
      buttons.forEach((b) => {
        const active = b === btn;
        b.classList.toggle('is-active', active);
        b.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      if (currentGroup) {
        currentGroup.hidden = filter === 'previous';
      }
      if (previousGroup) {
        previousGroup.hidden = filter === 'current';
      }

      if (statusEl) {
        statusEl.textContent =
          filter === 'current'
            ? 'Showing Current Board of Directors.'
            : filter === 'previous'
              ? 'Showing Previous Board Members.'
              : 'Showing all Current and Previous Board Members.';
      }
    });
  });
}

function initMissionsAndAbout() {
  initMissionGlobe();
  initShippingTelemetry();
  initBoardRosterFilter();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMissionsAndAbout, { once: true });
} else {
  initMissionsAndAbout();
}
