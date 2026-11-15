/**
 * v3 Story Sections Module (Hero, Process, Impact & About)
 * - Orthographic Global Mission Arc Canvas Globe (Watauga, TX -> 38 destinations)
 * - Zero idle rAF ticks when paused, hidden, off-screen, or under prefers-reduced-motion
 * - Region destination filter tabs & country chip focus
 * - Progressive-disclosure <dialog closedby="any"> drawers (Ledger, Board, Partners)
 * - Sortable 2013-2025 shipping ledger table & Board roster filter pills
 * - Contact topic prefill bridge
 */

const WATAUGA_ORIGIN = { lat: 32.87, lon: -97.26, label: 'Watauga, TX' };

const MISSION_DESTINATIONS = [
  // Americas (18)
  { name: 'Belize', region: 'americas', lat: 17.19, lon: -88.5 },
  { name: 'Bolivia', region: 'americas', lat: -16.29, lon: -63.59 },
  { name: 'Brazil', region: 'americas', lat: -14.24, lon: -51.93 },
  { name: 'Colombia', region: 'americas', lat: 4.57, lon: -74.3 },
  { name: 'Costa Rica', region: 'americas', lat: 9.75, lon: -83.75 },
  { name: 'Cuba', region: 'americas', lat: 21.52, lon: -77.78 },
  { name: 'Dominican Republic', region: 'americas', lat: 18.74, lon: -70.16 },
  { name: 'Ecuador', region: 'americas', lat: -1.83, lon: -78.18 },
  { name: 'El Salvador', region: 'americas', lat: 13.79, lon: -88.9 },
  { name: 'Guatemala', region: 'americas', lat: 15.78, lon: -90.23 },
  { name: 'Haiti', region: 'americas', lat: 18.97, lon: -72.29 },
  { name: 'Honduras', region: 'americas', lat: 15.2, lon: -86.24 },
  { name: 'Mexico', region: 'americas', lat: 23.63, lon: -102.55 },
  { name: 'Nicaragua', region: 'americas', lat: 12.87, lon: -85.21 },
  { name: 'Panama', region: 'americas', lat: 8.54, lon: -80.78 },
  { name: 'Paraguay', region: 'americas', lat: -23.44, lon: -58.44 },
  { name: 'Peru', region: 'americas', lat: -9.19, lon: -75.02 },
  { name: 'Venezuela', region: 'americas', lat: 6.42, lon: -66.59 },
  // Africa (10)
  { name: 'Cameroon', region: 'africa', lat: 7.37, lon: 12.35 },
  { name: 'Ethiopia', region: 'africa', lat: 9.15, lon: 40.49 },
  { name: 'Ghana', region: 'africa', lat: 7.95, lon: -1.02 },
  { name: 'Kenya', region: 'africa', lat: -0.02, lon: 37.91 },
  { name: 'Malawi', region: 'africa', lat: -13.25, lon: 34.3 },
  { name: 'Nigeria', region: 'africa', lat: 9.08, lon: 8.68 },
  { name: 'South Africa', region: 'africa', lat: -30.56, lon: 22.94 },
  { name: 'Tanzania', region: 'africa', lat: -6.37, lon: 34.89 },
  { name: 'Uganda', region: 'africa', lat: 1.37, lon: 32.29 },
  { name: 'Zambia', region: 'africa', lat: -13.13, lon: 27.85 },
  // Asia (5)
  { name: 'Bangladesh', region: 'asia', lat: 23.68, lon: 90.36 },
  { name: 'Cambodia', region: 'asia', lat: 12.57, lon: 104.99 },
  { name: 'Nepal', region: 'asia', lat: 28.39, lon: 84.12 },
  { name: 'Philippines', region: 'asia', lat: 12.88, lon: 121.77 },
  { name: 'Vietnam', region: 'asia', lat: 14.06, lon: 108.28 },
  // Europe (4)
  { name: 'Moldova', region: 'europe', lat: 47.41, lon: 28.37 },
  { name: 'Poland', region: 'europe', lat: 51.92, lon: 19.15 },
  { name: 'Romania', region: 'europe', lat: 45.94, lon: 24.97 },
  { name: 'Ukraine', region: 'europe', lat: 48.38, lon: 31.17 },
  // Fiji (1)
  { name: 'Fiji', region: 'fiji', lat: -17.71, lon: 178.07 },
];

const REGION_FOCUS = {
  all: { lon: -42, lat: 18, label: '38 destinations active' },
  americas: { lon: -78, lat: 8, label: 'Americas · 18 destinations' },
  africa: { lon: 20, lat: 4, label: 'Africa · 10 destinations' },
  asia: { lon: 100, lat: 18, label: 'Asia · 5 destinations' },
  europe: { lon: 24, lat: 48, label: 'Europe · 4 destinations' },
  fiji: { lon: 178, lat: -15, label: 'Fiji · 1 destination' },
};

// Compact continent coastlines [lon, lat] for subtle cartographic context
const LANDMASS_POLYGONS = [
  // North & Central America
  [
    [-168, 66], [-156, 71], [-140, 70], [-125, 70], [-108, 68], [-94, 62],
    [-80, 62], [-64, 58], [-55, 50], [-66, 44], [-75, 36], [-81, 25],
    [-87, 21], [-83, 10], [-78, 8], [-85, 11], [-92, 15], [-105, 20],
    [-112, 28], [-117, 32], [-124, 41], [-124, 49], [-136, 58], [-152, 60],
    [-165, 62],
  ],
  // South America
  [
    [-78, 9], [-72, 12], [-62, 10], [-50, 1], [-35, -6], [-39, -15],
    [-46, -24], [-54, -34], [-64, -41], [-68, -53], [-74, -50], [-72, -37],
    [-70, -18], [-81, -5], [-79, 2],
  ],
  // Africa
  [
    [-17, 15], [-13, 28], [-6, 36], [10, 37], [25, 32], [33, 31],
    [43, 12], [51, 11], [41, -2], [40, -15], [33, -26], [20, -35],
    [15, -26], [12, -12], [9, 4], [2, 6], [-8, 5], [-16, 11],
  ],
  // Europe
  [
    [-9, 37], [-9, 43], [-2, 44], [-5, 50], [2, 51], [8, 54],
    [10, 58], [18, 69], [30, 70], [42, 66], [42, 50], [38, 45],
    [29, 41], [23, 38], [16, 38], [12, 42], [4, 43], [-4, 36],
  ],
  // Asia
  [
    [29, 41], [42, 42], [48, 30], [56, 25], [60, 22], [68, 23],
    [77, 8], [82, 16], [90, 22], [97, 16], [104, 2], [109, 12],
    [120, 24], [122, 32], [129, 36], [140, 42], [156, 51], [172, 64],
    [150, 71], [110, 74], [75, 72], [58, 68], [45, 55], [40, 45],
  ],
  // Australia & Fiji proximity marker
  [
    [114, -22], [129, -14], [142, -11], [153, -27], [148, -38], [137, -35],
    [124, -34], [115, -34],
  ],
];

const DEG2RAD = Math.PI / 180;

function isReducedMotion() {
  return Boolean(window.matchMedia?.('(prefers-reduced-motion: reduce)').matches);
}

function toCartesian(latDeg, lonDeg) {
  const lat = latDeg * DEG2RAD;
  const lon = lonDeg * DEG2RAD;
  const cosLat = Math.cos(lat);
  return [cosLat * Math.cos(lon), Math.sin(lat), cosLat * Math.sin(lon)];
}

function rotatePoint(xyz, rotLonDeg, rotLatDeg) {
  const yaw = rotLonDeg * DEG2RAD;
  const pitch = rotLatDeg * DEG2RAD;

  // Rotate around Y (longitude) so rotLonDeg sits at front (+X -> +Z view axis)
  const cosY = Math.cos(-yaw);
  const sinY = Math.sin(-yaw);
  const x1 = xyz[0] * cosY - xyz[2] * sinY;
  const z1 = xyz[0] * sinY + xyz[2] * cosY;
  const y1 = xyz[1];

  // Tilt around Z/X for latitude
  const cosP = Math.cos(pitch);
  const sinP = Math.sin(pitch);
  const y2 = y1 * cosP - x1 * sinP;
  const depth = y1 * sinP + x1 * cosP;

  return [z1, -y2, depth];
}

function interpolateArc(a, b, steps) {
  const dot = Math.max(-1, Math.min(1, a[0] * b[0] + a[1] * b[1] + a[2] * b[2]));
  const omega = Math.acos(dot);
  const points = [];
  for (let i = 0; i <= steps; i++) {
    const t = i / steps;
    let x;
    let y;
    let z;
    if (omega < 0.001) {
      x = a[0] + (b[0] - a[0]) * t;
      y = a[1] + (b[1] - a[1]) * t;
      z = a[2] + (b[2] - a[2]) * t;
    } else {
      const sinOmega = Math.sin(omega);
      const s0 = Math.sin((1 - t) * omega) / sinOmega;
      const s1 = Math.sin(t * omega) / sinOmega;
      x = a[0] * s0 + b[0] * s1;
      y = a[1] * s0 + b[1] * s1;
      z = a[2] * s0 + b[2] * s1;
    }
    const lift = 1 + Math.sin(Math.PI * t) * Math.min(0.24, omega * 0.09);
    points.push([x * lift, y * lift, z * lift]);
  }
  return points;
}

function initImpactGlobeAndFilters() {
  const canvas = document.getElementById('impactGlobeCanvas');
  const toggleBtn = document.getElementById('impactGlobeToggle');
  const statusEl = document.getElementById('impactGlobeStatus');
  const filterTabs = Array.from(document.querySelectorAll('[data-region-filter]'));
  const countryItems = Array.from(document.querySelectorAll('.impact-country-item[data-region]'));
  const countryChips = Array.from(document.querySelectorAll('.impact-country-chip[data-country]'));

  if (!(canvas instanceof HTMLCanvasElement)) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const originVec = toCartesian(WATAUGA_ORIGIN.lat, WATAUGA_ORIGIN.lon);
  const preparedDestinations = MISSION_DESTINATIONS.map((dest) => {
    const vec = toCartesian(dest.lat, dest.lon);
    return {
      ...dest,
      vec,
      arc: interpolateArc(originVec, vec, 26),
    };
  });

  const preparedLandmasses = LANDMASS_POLYGONS.map((ring) =>
    ring.map(([lon, lat]) => toCartesian(lat, lon))
  );

  let activeRegion = 'all';
  let activeCountry = '';
  let rotLon = REGION_FOCUS.all.lon;
  let rotLat = REGION_FOCUS.all.lat;
  let targetLon = null;
  let targetLat = null;
  let isPaused = isReducedMotion();
  let isVisible = true;
  let isDragging = false;
  let dragStartX = 0;
  let dragStartY = 0;
  let dragStartLon = rotLon;
  let dragStartLat = rotLat;
  let rafId = 0;
  let lastFrameTime = 0;

  const syncToggleUi = () => {
    if (!toggleBtn) return;
    toggleBtn.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
    toggleBtn.textContent = isPaused ? 'Play' : 'Pause';
  };

  const renderGlobe = () => {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const cssWidth = canvas.clientWidth || 420;
    const cssHeight = canvas.clientHeight || 420;
    const targetW = Math.round(cssWidth * dpr);
    const targetH = Math.round(cssHeight * dpr);

    if (canvas.width !== targetW || canvas.height !== targetH) {
      canvas.width = targetW;
      canvas.height = targetH;
    }

    ctx.save();
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, cssWidth, cssHeight);

    const cx = cssWidth / 2;
    const cy = cssHeight / 2;
    const radius = Math.min(cssWidth, cssHeight) * 0.42;

    // Globe sphere backdrop
    const sphereGrad = ctx.createRadialGradient(
      cx - radius * 0.28,
      cy - radius * 0.28,
      radius * 0.1,
      cx,
      cy,
      radius
    );
    sphereGrad.addColorStop(0, '#222F3F');
    sphereGrad.addColorStop(1, '#101720');

    ctx.beginPath();
    ctx.arc(cx, cy, radius, 0, Math.PI * 2);
    ctx.fillStyle = sphereGrad;
    ctx.fill();
    ctx.lineWidth = 1.25;
    ctx.strokeStyle = 'rgba(188, 197, 209, 0.26)';
    ctx.stroke();

    // Clip interior graticule and landmasses to sphere
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, cy, radius, 0, Math.PI * 2);
    ctx.clip();

    // Subtle longitude meridians & latitude parallels
    ctx.lineWidth = 0.75;
    ctx.strokeStyle = 'rgba(188, 197, 209, 0.12)';
    for (let lon = -150; lon <= 180; lon += 30) {
      ctx.beginPath();
      let started = false;
      for (let lat = -80; lat <= 80; lat += 6) {
        const p = rotatePoint(toCartesian(lat, lon), rotLon, rotLat);
        if (p[2] > 0) {
          const sx = cx + p[0] * radius;
          const sy = cy + p[1] * radius;
          if (!started) {
            ctx.moveTo(sx, sy);
            started = true;
          } else {
            ctx.lineTo(sx, sy);
          }
        } else {
          started = false;
        }
      }
      ctx.stroke();
    }

    for (let lat = -60; lat <= 60; lat += 30) {
      ctx.beginPath();
      let started = false;
      for (let lon = -180; lon <= 180; lon += 6) {
        const p = rotatePoint(toCartesian(lat, lon), rotLon, rotLat);
        if (p[2] > 0) {
          const sx = cx + p[0] * radius;
          const sy = cy + p[1] * radius;
          if (!started) {
            ctx.moveTo(sx, sy);
            started = true;
          } else {
            ctx.lineTo(sx, sy);
          }
        } else {
          started = false;
        }
      }
      ctx.stroke();
    }

    // Continent landmass silhouettes
    ctx.fillStyle = 'rgba(188, 197, 209, 0.17)';
    ctx.strokeStyle = 'rgba(188, 197, 209, 0.28)';
    ctx.lineWidth = 0.9;
    for (const poly of preparedLandmasses) {
      ctx.beginPath();
      let anyFront = false;
      let started = false;
      for (let i = 0; i < poly.length; i++) {
        const p = rotatePoint(poly[i], rotLon, rotLat);
        // Project slightly behind limb smoothly for continuous land edges
        if (p[2] >= -0.15) {
          anyFront = anyFront || p[2] > 0;
          const clampLen = p[2] < 0 ? Math.hypot(p[0], p[1]) || 1 : 1;
          const sx = cx + (p[2] < 0 ? p[0] / clampLen : p[0]) * radius;
          const sy = cy + (p[2] < 0 ? p[1] / clampLen : p[1]) * radius;
          if (!started) {
            ctx.moveTo(sx, sy);
            started = true;
          } else {
            ctx.lineTo(sx, sy);
          }
        } else {
          started = false;
        }
      }
      if (anyFront) {
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
      }
    }
    ctx.restore();

    // Geodesic Optical Cobalt arcs from Watauga, TX
    for (const dest of preparedDestinations) {
      const isMatch =
        (activeRegion === 'all' || dest.region === activeRegion) &&
        (activeCountry === '' || dest.name === activeCountry);

      ctx.beginPath();
      let started = false;
      for (let i = 0; i < dest.arc.length; i++) {
        const p = rotatePoint(dest.arc[i], rotLon, rotLat);
        if (p[2] > -0.05) {
          const sx = cx + p[0] * radius;
          const sy = cy + p[1] * radius;
          if (!started) {
            ctx.moveTo(sx, sy);
            started = true;
          } else {
            ctx.lineTo(sx, sy);
          }
        } else {
          started = false;
        }
      }
      if (isMatch) {
        ctx.strokeStyle = activeCountry === dest.name ? 'rgba(136, 184, 246, 0.95)' : 'rgba(107, 166, 242, 0.58)';
        ctx.lineWidth = activeCountry === dest.name ? 2.2 : 1.35;
      } else {
        ctx.strokeStyle = 'rgba(107, 166, 242, 0.14)';
        ctx.lineWidth = 0.8;
      }
      ctx.stroke();
    }

    // Destination nodes
    for (const dest of preparedDestinations) {
      const p = rotatePoint(dest.vec, rotLon, rotLat);
      if (p[2] <= 0) continue;
      const sx = cx + p[0] * radius;
      const sy = cy + p[1] * radius;
      const isMatch =
        (activeRegion === 'all' || dest.region === activeRegion) &&
        (activeCountry === '' || dest.name === activeCountry);

      ctx.beginPath();
      ctx.arc(sx, sy, isMatch ? (activeCountry === dest.name ? 4.8 : 3.1) : 2.1, 0, Math.PI * 2);
      ctx.fillStyle = isMatch ? '#6BA6F2' : 'rgba(188, 197, 209, 0.35)';
      ctx.fill();

      if (activeCountry === dest.name) {
        ctx.font = '600 12px "Inter Tight Variable", system-ui, sans-serif';
        ctx.fillStyle = '#EFF2F5';
        ctx.fillText(dest.name, sx + 8, sy + 4);
      }
    }

    // Watauga TX origin reticle
    const orig = rotatePoint(originVec, rotLon, rotLat);
    if (orig[2] > 0) {
      const ox = cx + orig[0] * radius;
      const oy = cy + orig[1] * radius;

      ctx.beginPath();
      ctx.arc(ox, oy, 7.5, 0, Math.PI * 2);
      ctx.strokeStyle = 'rgba(223, 179, 75, 0.65)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.beginPath();
      ctx.arc(ox, oy, 3.8, 0, Math.PI * 2);
      ctx.fillStyle = '#DFB34B';
      ctx.fill();

      ctx.font = '600 11px "Martian Mono Variable", monospace';
      ctx.fillStyle = '#EFF2F5';
      ctx.fillText('Watauga, TX', ox + 10, oy - 7);
    }

    ctx.restore();
  };

  const stopLoop = () => {
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = 0;
    }
    lastFrameTime = 0;
  };

  const shouldAnimate = () => !isPaused && !isDragging && isVisible && !document.hidden;

  const stepFrame = (timestamp) => {
    rafId = 0;
    if (!shouldAnimate()) {
      return;
    }

    if (!lastFrameTime) {
      lastFrameTime = timestamp;
    }
    const dt = Math.min((timestamp - lastFrameTime) / 1000, 0.05);
    lastFrameTime = timestamp;

    if (targetLon !== null && targetLat !== null) {
      let dLon = targetLon - rotLon;
      while (dLon > 180) dLon -= 360;
      while (dLon < -180) dLon += 360;
      const dLat = targetLat - rotLat;
      rotLon += dLon * Math.min(1, dt * 5);
      rotLat += dLat * Math.min(1, dt * 5);
      if (Math.abs(dLon) < 0.25 && Math.abs(dLat) < 0.25) {
        rotLon = targetLon;
        rotLat = targetLat;
        targetLon = null;
        targetLat = null;
      }
    } else {
      rotLon = (rotLon + dt * 6) % 360;
    }

    renderGlobe();
    rafId = requestAnimationFrame(stepFrame);
  };

  const startLoopIfNeeded = () => {
    if (shouldAnimate() && !rafId) {
      lastFrameTime = 0;
      rafId = requestAnimationFrame(stepFrame);
    }
  };

  const focusCoordinates = (lon, lat) => {
    if (isPaused || isReducedMotion()) {
      targetLon = null;
      targetLat = null;
      rotLon = lon;
      rotLat = lat;
      renderGlobe();
      return;
    }
    targetLon = lon;
    targetLat = lat;
    startLoopIfNeeded();
  };

  // Drag-to-rotate pointer events
  canvas.addEventListener('pointerdown', (event) => {
    isDragging = true;
    targetLon = null;
    targetLat = null;
    dragStartX = event.clientX;
    dragStartY = event.clientY;
    dragStartLon = rotLon;
    dragStartLat = rotLat;
    stopLoop();
    canvas.setPointerCapture?.(event.pointerId);
  });

  canvas.addEventListener('pointermove', (event) => {
    if (!isDragging) return;
    const dx = event.clientX - dragStartX;
    const dy = event.clientY - dragStartY;
    rotLon = dragStartLon - dx * 0.4;
    rotLat = Math.max(-70, Math.min(70, dragStartLat + dy * 0.35));
    renderGlobe();
  });

  const endDrag = (event) => {
    if (!isDragging) return;
    isDragging = false;
    try {
      canvas.releasePointerCapture?.(event.pointerId);
    } catch {
      // Ignore if not captured
    }
    startLoopIfNeeded();
  };

  canvas.addEventListener('pointerup', endDrag);
  canvas.addEventListener('pointercancel', endDrag);

  // Keyboard arrow rotation on focused canvas
  canvas.addEventListener('keydown', (event) => {
    const step = 10;
    let handled = true;
    if (event.key === 'ArrowLeft') rotLon -= step;
    else if (event.key === 'ArrowRight') rotLon += step;
    else if (event.key === 'ArrowUp') rotLat = Math.min(70, rotLat + step);
    else if (event.key === 'ArrowDown') rotLat = Math.max(-70, rotLat - step);
    else handled = false;

    if (handled) {
      event.preventDefault();
      targetLon = null;
      targetLat = null;
      renderGlobe();
    }
  });

  // Pause / Play control
  toggleBtn?.addEventListener('click', () => {
    isPaused = !isPaused;
    syncToggleUi();
    if (isPaused) {
      stopLoop();
      renderGlobe();
    } else {
      startLoopIfNeeded();
    }
  });

  // Region filter tabs
  const applyRegionFilter = (regionKey) => {
    activeRegion = regionKey;
    activeCountry = '';

    for (const tab of filterTabs) {
      const isMatch = tab.dataset.regionFilter === regionKey;
      tab.classList.toggle('is-active', isMatch);
      tab.setAttribute('aria-selected', isMatch ? 'true' : 'false');
    }

    for (const item of countryItems) {
      const show = regionKey === 'all' || item.dataset.region === regionKey;
      item.hidden = !show;
    }

    for (const chip of countryChips) {
      chip.classList.remove('is-highlighted');
      chip.removeAttribute('aria-pressed');
    }

    const focus = REGION_FOCUS[regionKey] || REGION_FOCUS.all;
    if (statusEl) {
      statusEl.textContent = focus.label;
    }
    focusCoordinates(focus.lon, focus.lat);
  };

  for (const tab of filterTabs) {
    tab.addEventListener('click', () => {
      const key = tab.dataset.regionFilter || 'all';
      applyRegionFilter(key);
    });
  }

  for (const chip of countryChips) {
    chip.addEventListener('click', () => {
      const name = chip.dataset.country || '';
      const isSame = activeCountry === name;
      activeCountry = isSame ? '' : name;

      for (const other of countryChips) {
        const selected = !isSame && other === chip;
        other.classList.toggle('is-highlighted', selected);
        other.setAttribute('aria-pressed', selected ? 'true' : 'false');
      }

      if (activeCountry) {
        const match = preparedDestinations.find((d) => d.name === activeCountry);
        if (match) {
          if (statusEl) statusEl.textContent = `${match.name} selected`;
          focusCoordinates(match.lon, match.lat);
        }
      } else {
        const focus = REGION_FOCUS[activeRegion] || REGION_FOCUS.all;
        if (statusEl) statusEl.textContent = focus.label;
        focusCoordinates(focus.lon, focus.lat);
      }
    });
  }

  // Visibility & resize observers so zero rAF ticks run off-screen or in hidden tabs
  if (typeof IntersectionObserver !== 'undefined') {
    const io = new IntersectionObserver(
      (entries) => {
        isVisible = Boolean(entries[0]?.isIntersecting);
        if (isVisible) {
          renderGlobe();
          startLoopIfNeeded();
        } else {
          stopLoop();
        }
      },
      { threshold: 0.05 }
    );
    io.observe(canvas);
  }

  if (typeof ResizeObserver !== 'undefined') {
    const ro = new ResizeObserver(() => {
      renderGlobe();
    });
    ro.observe(canvas);
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopLoop();
    } else {
      renderGlobe();
      startLoopIfNeeded();
    }
  });

  syncToggleUi();
  renderGlobe();
  startLoopIfNeeded();
}

function bindLightDismissFallback(dialog) {
  if (!(dialog instanceof HTMLDialogElement)) return;
  if ('closedBy' in HTMLDialogElement.prototype) return;
  if (dialog.dataset.lightDismissBound === 'true') return;
  dialog.dataset.lightDismissBound = 'true';

  let startedOutside = false;
  const isOutsideRect = (event) => {
    const rect = dialog.getBoundingClientRect();
    return (
      event.clientX < rect.left ||
      event.clientX > rect.right ||
      event.clientY < rect.top ||
      event.clientY > rect.bottom
    );
  };

  dialog.addEventListener('pointerdown', (event) => {
    if (event.button !== 0) {
      startedOutside = false;
      return;
    }
    startedOutside = event.target === dialog && isOutsideRect(event);
  });

  dialog.addEventListener('pointerup', (event) => {
    if (!startedOutside) return;
    startedOutside = false;
    if (event.target === dialog && isOutsideRect(event) && dialog.open) {
      dialog.close();
    }
  });
}

function initStoryDrawers() {
  document.querySelectorAll('dialog.drawer').forEach((dialog) => {
    bindLightDismissFallback(dialog);
  });

  document.querySelectorAll('[data-open-drawer]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-open-drawer') || '';
      const dialog = document.getElementById(targetId);
      if (dialog instanceof HTMLDialogElement && !dialog.open) {
        dialog.showModal();
      }
    });
  });

  document.querySelectorAll('[data-close-drawer]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const dialog = btn.closest('dialog');
      if (dialog instanceof HTMLDialogElement && dialog.open) {
        dialog.close();
      }
    });
  });
}

function initLedgerTableSort() {
  const table = document.getElementById('ledgerTable');
  if (!(table instanceof HTMLTableElement)) return;

  const tbody = table.querySelector('tbody');
  const sortButtons = Array.from(table.querySelectorAll('.ledger-sort-btn[data-sort-col]'));
  if (!tbody || sortButtons.length === 0) return;

  let currentCol = 'year';
  let currentDir = 'asc';

  const updateHeaders = () => {
    for (const btn of sortButtons) {
      const th = btn.closest('th');
      const indicator = btn.querySelector('.ledger-sort-indicator');
      const col = btn.dataset.sortCol;
      if (col === currentCol) {
        const sortLabel = currentDir === 'asc' ? 'ascending' : 'descending';
        th?.setAttribute('aria-sort', sortLabel);
        if (indicator) indicator.textContent = currentDir === 'asc' ? '\u2191' : '\u2193';
      } else {
        th?.setAttribute('aria-sort', 'none');
        if (indicator) indicator.textContent = '\u2195';
      }
    }
  };

  for (const btn of sortButtons) {
    btn.addEventListener('click', () => {
      const col = btn.dataset.sortCol || 'year';
      if (col === currentCol) {
        currentDir = currentDir === 'asc' ? 'desc' : 'asc';
      } else {
        currentCol = col;
        currentDir = col === 'year' ? 'asc' : 'desc';
      }

      const rows = Array.from(tbody.querySelectorAll('tr'));
      rows.sort((a, b) => {
        let cmp = 0;
        if (col === 'year') {
          cmp = (a.dataset.year || '').localeCompare(b.dataset.year || '');
        } else {
          const av = Number(a.dataset[col] || 0);
          const bv = Number(b.dataset[col] || 0);
          cmp = av - bv;
        }
        return currentDir === 'asc' ? cmp : -cmp;
      });

      for (const row of rows) {
        tbody.appendChild(row);
      }
      updateHeaders();
    });
  }
}

function initBoardFilter() {
  const grid = document.getElementById('boardGrid');
  const filterButtons = Array.from(document.querySelectorAll('[data-board-filter]'));
  if (!grid || filterButtons.length === 0) return;

  const cards = Array.from(grid.querySelectorAll('[data-board-tag]'));

  for (const btn of filterButtons) {
    btn.addEventListener('click', () => {
      const filter = btn.dataset.boardFilter || 'all';
      for (const other of filterButtons) {
        const active = other === btn;
        other.classList.toggle('is-active', active);
        other.setAttribute('aria-selected', active ? 'true' : 'false');
      }

      for (const card of cards) {
        const show = filter === 'all' || card.dataset.boardTag === filter;
        card.hidden = !show;
      }
    });
  }
}

function initMissionContactPrefill() {
  document.querySelectorAll('[data-prefill-topic]').forEach((link) => {
    link.addEventListener('click', () => {
      const topic = link.getAttribute('data-prefill-topic') || '';
      if (!topic) return;

      const select =
        document.querySelector('#contact select[name="topic"]') ||
        document.querySelector('#contact select[name="subject"]') ||
        document.getElementById('contactTopic');

      if (select instanceof HTMLSelectElement) {
        const hasExact = Array.from(select.options).some((opt) => opt.value === topic);
        if (hasExact) {
          select.value = topic;
        } else if (topic === 'mission') {
          const hasPartner = Array.from(select.options).some((opt) => opt.value === 'partnership');
          if (hasPartner) select.value = 'partnership';
        }
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }

      document.dispatchEvent(
        new CustomEvent('erc:prefill-contact', {
          detail: { topic },
        })
      );
    });
  });
}

let storyInitialized = false;

export function initStorySections() {
  if (storyInitialized) return;
  storyInitialized = true;

  initImpactGlobeAndFilters();
  initStoryDrawers();
  initLedgerTableSort();
  initBoardFilter();
  initMissionContactPrefill();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initStorySections, { once: true });
} else {
  initStorySections();
}
