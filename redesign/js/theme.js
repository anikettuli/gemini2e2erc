/**
 * Dual light/dark theme manager matching BUILD_SPEC.md Section 2 & head.php FOUC bootstrap.
 * Uses localStorage key "erc-color-scheme" only when the user overrides the OS preference.
 */

const STORAGE_KEY = 'erc-color-scheme';
const THEME_COLORS = {
  light: '#faf8f3',
  dark: '#0d1522',
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

export function getResolvedTheme() {
  const stored = readStoredPreference();
  if (stored) {
    return stored;
  }
  const metaScheme = document.querySelector('meta[name="color-scheme"]');
  const content = metaScheme ? metaScheme.getAttribute('content') : null;
  if (content === 'light' || content === 'dark') {
    return content;
  }
  return getOsTheme();
}

function syncThemeColorMeta(preference, resolved) {
  const metaLight = document.getElementById('metaThemeColorLight');
  const metaDark = document.getElementById('metaThemeColorDark');
  const genericThemeMeta = document.querySelector('meta[name="theme-color"]:not([media])');

  if (preference === 'light' || preference === 'dark') {
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

function syncToggleButton(resolved) {
  const btn = document.getElementById('themeToggle');
  if (!btn) return;
  const label = resolved === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
  btn.setAttribute('aria-label', label);
  btn.setAttribute('title', label);
  btn.setAttribute('data-resolved-theme', resolved);
}

export function applyColorScheme(preference, { dispatch = true } = {}) {
  const normalizedPref = preference === 'light' || preference === 'dark' ? preference : 'light dark';
  const resolved = normalizedPref === 'light dark' ? getOsTheme() : normalizedPref;

  const metaScheme = document.querySelector('meta[name="color-scheme"]');
  if (metaScheme) {
    metaScheme.setAttribute('content', normalizedPref);
  }

  syncThemeColorMeta(normalizedPref, resolved);
  syncToggleButton(resolved);

  if (dispatch) {
    window.dispatchEvent(
      new CustomEvent('erc:themechange', {
        detail: {
          resolved,
          preference: normalizedPref,
        },
      })
    );
  }
}

export function setThemePreference(nextTheme) {
  const target = nextTheme === 'dark' ? 'dark' : 'light';
  const osTheme = getOsTheme();

  if (target === osTheme) {
    writeStoredPreference(null);
    applyColorScheme('light dark');
  } else {
    writeStoredPreference(target);
    applyColorScheme(target);
  }
}

function initThemeToggle() {
  const initialPref = readStoredPreference() ?? 'light dark';
  applyColorScheme(initialPref, { dispatch: false });

  const toggleBtn = document.getElementById('themeToggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      const currentResolved = getResolvedTheme();
      const next = currentResolved === 'dark' ? 'light' : 'dark';
      setThemePreference(next);
    });
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
