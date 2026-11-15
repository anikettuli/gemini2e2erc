/**
 * Stage 2 Drop-Off Locator Module (Worker B)
 * Powers Fuse.js fuzzy search + 34-city filter + MapLibre GL Supercluster map on `#drop-off`.
 */

export function initDropoffLocator() {
  // Implemented in Stage 2
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDropoffLocator, { once: true });
} else {
  initDropoffLocator();
}
