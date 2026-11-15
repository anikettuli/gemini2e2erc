/**
 * v3 2-State Comfort Theme Toggle (OS Default <-> Opposite Override).
 * Synchronizes localStorage['erc-color-scheme'], <meta name="color-scheme">,
 * <html data-theme="...">, <meta name="theme-color">, and `erc:themechange`.
 */

const STORAGE_KEY = 'erc-color-scheme';
const THEME_COLORS = {
  light: '#FAF9F6',
  dark: '#0E141B',
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

  if (normalizedPref === 'light' || normalizedPref === 'dark') {
    const hex = THEME_COLORS[resolved];
    if (metaLight) metaLight.setAttribute('content', hex);
    if (metaDark) metaDark.setAttribute('content', hex);
  } else {
    if (metaLight) metaLight.setAttribute('content', THEME_COLORS.light);
    if (metaDark) metaDark.setAttribute('content', THEME_COLORS.dark);
  }
}

function syncToggleButton(prefMode, resolved) {
  const btn = document.getElementById('themeToggle');
  if (!btn) return;

  const labelEl = document.getElementById('themeToggleLabel');
  const glyphEl = btn.querySelector('.theme-toggle__glyph');

  const isDark = resolved === 'dark';
  const nextLabel = isDark ? 'Light' : 'Dark';

  if (labelEl) {
    labelEl.textContent = isDark ? 'Dark' : 'Light';
  }
  if (glyphEl) {
    glyphEl.textContent = isDark ? '\u263E' : '\u2600';
  }

  btn.setAttribute('aria-pressed', prefMode !== 'auto' ? 'true' : 'false');
  btn.setAttribute(
    'aria-label',
    `Color theme: ${isDark ? 'Dark' : 'Light'}${prefMode === 'auto' ? ' (system)' : ''}. Switch to ${nextLabel}.`
  );
  btn.setAttribute('title', `Switch to ${nextLabel} theme`);
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

function toggleComfortTheme() {
  const osTheme = getOsTheme();
  const currentResolved = getResolvedTheme();
  const nextTarget = currentResolved === 'dark' ? 'light' : 'dark';

  // Revert cleanly to OS default (removing stored key) when clicking back to OS theme
  if (nextTarget === osTheme) {
    setThemePreference('auto');
  } else {
    setThemePreference(nextTarget);
  }
}

function initThemeToggle() {
  const initialPref = readStoredPreference() ?? 'light dark';
  applyColorScheme(initialPref, { dispatch: false });

  const toggleBtn = document.getElementById('themeToggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', toggleComfortTheme);
  }

  // Never clear localStorage when OS preference changes; only update when in OS-follow mode
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
