/**
 * v2 Dual-Theme & 3-State Cycler Manager (AUTO / LIGHT / DARK).
 * Synchronizes localStorage['erc-color-scheme'], <meta name="color-scheme">,
 * <html data-theme="...">, <meta name="theme-color">, and `erc:themechange`.
 */

const STORAGE_KEY = 'erc-color-scheme';
const THEME_COLORS = {
  light: '#eaf0f8',
  dark: '#0b1117',
};

const osDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

function readStoredPreference() {
  try {
    const val = localStorage.getItem(STORAGE_KEY);
    return val === 'light' || val === 'dark' ? val : null;
  } catch {
    return null;
  }
}

function writeStoredPreference(pref) {
  try {
    if (pref === 'light' || pref === 'dark') {
      localStorage.setItem(STORAGE_KEY, pref);
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  } catch {
    // Storage unavailable (private mode or policy); keep in-memory state via meta tag.
  }
}

function getOsTheme() {
  return osDarkQuery.matches ? 'dark' : 'light';
}

export function getThemePreference() {
  const stored = readStoredPreference();
  if (stored) {
    return stored;
  }
  const attr = document.documentElement.getAttribute('data-theme');
  if (attr === 'light' || attr === 'dark') {
    return attr;
  }
  return 'auto';
}

export function getResolvedTheme() {
  const pref = getThemePreference();
  if (pref === 'light' || pref === 'dark') {
    return pref;
  }
  const metaScheme = document.querySelector('meta[name="color-scheme"]');
  const content = metaScheme ? metaScheme.getAttribute('content') : null;
  if (content === 'light' || content === 'dark') {
    return content;
  }
  return getOsTheme();
}

function syncThemeColorMeta(normalizedPref, resolved) {
  const metaLight = document.getElementById('metaThemeColorLight');
  const metaDark = document.getElementById('metaThemeColorDark');
  const genericThemeMeta = document.querySelector('meta[name="theme-color"]:not([media])');

  if (normalizedPref === 'light' || normalizedPref === 'dark') {
    const hex = THEME_COLORS[resolved];
    if (metaLight) metaLight.setAttribute('content', hex);
    if (metaDark) metaDark.setAttribute('content', hex);
    if (genericThemeMeta) genericThemeMeta.setAttribute('content', hex);
  } else {
    if (metaLight) metaLight.setAttribute('content', THEME_COLORS.light);
    if (metaDark) metaDark.setAttribute('content', THEME_COLORS.dark);
    if (genericThemeMeta) genericThemeMeta.setAttribute('content', THEME_COLORS[resolved]);
  }
}

function syncToggleButton(prefMode, resolved) {
  const btn = document.getElementById('themeToggle');
  if (!btn) return;

  const labelEl = document.getElementById('themeToggleLabel');
  const glyphEl = btn.querySelector('.theme-toggle__glyph');

  const modeUpper = prefMode === 'light' ? 'LIGHT' : prefMode === 'dark' ? 'DARK' : 'AUTO';
  const nextUpper = prefMode === 'auto' ? 'LIGHT' : prefMode === 'light' ? 'DARK' : 'AUTO';

  if (labelEl) {
    labelEl.textContent = modeUpper;
  }
  if (glyphEl) {
    glyphEl.textContent = prefMode === 'light' ? '\u2600' : prefMode === 'dark' ? '\u263E' : '\u25D0';
  }

  const ariaLabel = `Color theme: ${modeUpper} (${resolved}). Click to switch to ${nextUpper}.`;
  btn.setAttribute('aria-label', ariaLabel);
  btn.setAttribute('title', `Theme: ${modeUpper} — Click for ${nextUpper}`);
  btn.setAttribute('data-theme-pref', prefMode);
  btn.setAttribute('data-resolved-theme', resolved);
}

export function applyColorScheme(preference, { dispatch = true } = {}) {
  const normalizedPref = preference === 'light' || preference === 'dark' ? preference : 'light dark';
  const prefMode = normalizedPref === 'light dark' ? 'auto' : normalizedPref;
  const resolved = prefMode === 'auto' ? getOsTheme() : prefMode;

  const metaScheme = document.querySelector('meta[name="color-scheme"]');
  if (metaScheme) {
    metaScheme.setAttribute('content', normalizedPref);
  }

  if (prefMode === 'light' || prefMode === 'dark') {
    document.documentElement.setAttribute('data-theme', prefMode);
  } else {
    document.documentElement.removeAttribute('data-theme');
  }

  syncThemeColorMeta(normalizedPref, resolved);
  syncToggleButton(prefMode, resolved);

  if (dispatch) {
    window.dispatchEvent(
      new CustomEvent('erc:themechange', {
        detail: {
          resolved,
          preference: normalizedPref,
          mode: prefMode,
        },
      })
    );
  }
}

export function setThemePreference(nextTheme) {
  if (nextTheme === 'auto' || nextTheme === 'light dark') {
    writeStoredPreference(null);
    applyColorScheme('light dark');
    return;
  }
  const target = nextTheme === 'dark' ? 'dark' : 'light';
  writeStoredPreference(target);
  applyColorScheme(target);
}

function cycleThemePreference() {
  const current = getThemePreference();
  if (current === 'auto') {
    setThemePreference('light');
  } else if (current === 'light') {
    setThemePreference('dark');
  } else {
    setThemePreference('auto');
  }
}

function initThemeToggle() {
  const initialPref = readStoredPreference() ?? 'light dark';
  applyColorScheme(initialPref, { dispatch: false });

  const toggleBtn = document.getElementById('themeToggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', cycleThemePreference);
  }

  osDarkQuery.addEventListener('change', () => {
    const stored = readStoredPreference();
    if (!stored) {
      applyColorScheme('light dark');
    }
  });

  window.addEventListener('storage', (event) => {
    if (event.key !== STORAGE_KEY) return;
    const pref = event.newValue === 'light' || event.newValue === 'dark' ? event.newValue : 'light dark';
    applyColorScheme(pref);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initThemeToggle, { once: true });
} else {
  initThemeToggle();
}
