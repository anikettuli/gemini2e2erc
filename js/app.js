// Hash-based Accessible Tab Router + Lazy Tab Resource Activation
const PAGE_TITLES = {
  home: 'Lions District 2-E2 Eyeglass Recycling Center',
  about: 'About Us | Lions District 2-E2 ERC',
  services: 'Our Services | Lions District 2-E2 ERC',
  'get-involved': 'Volunteer & Get Involved | Lions District 2-E2 ERC',
  locations: 'Drop-Off Locations | Lions District 2-E2 ERC',
  contact: 'Contact Us | Lions District 2-E2 ERC'
};

class Router {
  constructor(galleryManager) {
    this.tabs = ['home', 'about', 'services', 'get-involved', 'locations', 'contact'];
    this.galleryManager = galleryManager;
    this.mobileNav = document.getElementById('mobileNav');
    this.mobileMenuTrigger = document.getElementById('mobileMenuTrigger');
    this.dropdownArrow = document.querySelector('.dropdown-arrow');
    this.currentPageName = document.querySelector('.current-page-name');
    this.initialLoad = true;
    this.init();
  }

  init() {
    this.navigateToHash();
    this.initialLoad = false;

    window.addEventListener('hashchange', () => this.navigateToHash());

    document.querySelectorAll('.tab-button, .js-tab-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const tab = btn.getAttribute('data-tab');
        if (tab && this.tabs.includes(tab)) {
          if (window.location.hash !== '#' + tab) {
            history.pushState(null, '', '#' + tab);
          }
          this.switchTab(tab, true);
        }
      });
    });

    // Keyboard arrow navigation for desktop ARIA tablist
    const desktopTablist = document.querySelector('.tab-nav[role="tablist"]');
    if (desktopTablist) {
      const desktopTabs = Array.from(desktopTablist.querySelectorAll('[role="tab"]'));
      desktopTablist.addEventListener('keydown', (e) => {
        const currentIndex = desktopTabs.indexOf(document.activeElement);
        if (currentIndex === -1) return;

        let nextIndex = -1;
        if (e.key === 'ArrowRight') {
          nextIndex = (currentIndex + 1) % desktopTabs.length;
        } else if (e.key === 'ArrowLeft') {
          nextIndex = (currentIndex - 1 + desktopTabs.length) % desktopTabs.length;
        } else if (e.key === 'Home') {
          nextIndex = 0;
        } else if (e.key === 'End') {
          nextIndex = desktopTabs.length - 1;
        }

        if (nextIndex !== -1) {
          e.preventDefault();
          const targetBtn = desktopTabs[nextIndex];
          const tab = targetBtn.getAttribute('data-tab');
          targetBtn.focus();
          if (tab) {
            history.pushState(null, '', '#' + tab);
            this.switchTab(tab, false);
          }
        }
      });
    }

    if (this.mobileMenuTrigger && this.mobileNav) {
      this.mobileMenuTrigger.addEventListener('click', () => {
        const isOpen = this.mobileNav.classList.toggle('open');
        this.mobileMenuTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (this.dropdownArrow) this.dropdownArrow.classList.toggle('open', isOpen);
      });
    }
  }

  navigateToHash() {
    let tab = window.location.hash.slice(1) || 'home';
    if (!this.tabs.includes(tab)) {
      tab = 'home';
      history.replaceState(null, '', '#home');
    }
    this.switchTab(tab, !this.initialLoad);
  }

  switchTab(tab, shouldScroll = true) {
    document.querySelectorAll('.tab-content').forEach(el => {
      const isActive = el.id === tab;
      el.classList.toggle('active', isActive);
      el.hidden = !isActive;
    });

    document.querySelectorAll('.tab-nav [role="tab"]').forEach(btn => {
      const isActive = btn.getAttribute('data-tab') === tab;
      btn.classList.toggle('active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      btn.setAttribute('tabindex', isActive ? '0' : '-1');
      if (isActive && this.currentPageName) {
        this.currentPageName.textContent = btn.textContent.trim();
      }
    });

    document.querySelectorAll('.mobile-nav .tab-button').forEach(btn => {
      const isActive = btn.getAttribute('data-tab') === tab;
      btn.classList.toggle('active', isActive);
      if (isActive) {
        btn.setAttribute('aria-current', 'page');
      } else {
        btn.removeAttribute('aria-current');
      }
    });

    if (PAGE_TITLES[tab]) {
      document.title = PAGE_TITLES[tab];
    }

    if (this.mobileNav) {
      this.mobileNav.classList.remove('open');
      if (this.mobileMenuTrigger) this.mobileMenuTrigger.setAttribute('aria-expanded', 'false');
      if (this.dropdownArrow) this.dropdownArrow.classList.remove('open');
    }

    this.activateTabResources(tab);

    if (shouldScroll) {
      const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: prefersReduced ? 'auto' : 'smooth' });
    }
  }

  activateTabResources(tab) {
    if (tab === 'home') {
      if (typeof window.ensureTestimonialsLoaded === 'function') window.ensureTestimonialsLoaded();
    } else if (tab === 'about') {
      if (typeof window.ensureBoardLoaded === 'function') window.ensureBoardLoaded();
    } else if (tab === 'get-involved') {
      if (this.galleryManager) this.galleryManager.ensureLoaded();
      if (typeof window.ensureCalendarLoaded === 'function') window.ensureCalendarLoaded();
      if (typeof window.ensurePartnersLoaded === 'function') window.ensurePartnersLoaded();
    } else if (tab === 'locations') {
      if (typeof window.ensureLocationsLoaded === 'function') window.ensureLocationsLoaded();
    }
  }
}

class ThemeManager {
  constructor() {
    this.themeToggleMobile = document.getElementById('themeToggle');
    this.themeToggleDesktop = document.getElementById('themeToggleDesktop');
    this.themeIconMobile = document.getElementById('themeIcon');
    this.themeIconDesktop = document.getElementById('themeIconDesktop');
    this.metaColorScheme = document.querySelector('meta[name="color-scheme"]');
    this.htmlElement = document.documentElement;
    this.mediaQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    this.init();
  }

  getStoredOverride() {
    try {
      const stored = localStorage.getItem('color-scheme') || localStorage.getItem('theme');
      if (stored === 'light' || stored === 'dark') {
        return stored;
      }
    } catch (e) {
      // Ignore storage access restrictions
    }
    return null;
  }

  getSystemScheme() {
    return this.mediaQuery && this.mediaQuery.matches ? 'dark' : 'light';
  }

  init() {
    this.syncUI();

    if (this.themeToggleMobile) {
      this.themeToggleMobile.addEventListener('click', () => this.toggleTheme());
    }
    if (this.themeToggleDesktop) {
      this.themeToggleDesktop.addEventListener('click', () => this.toggleTheme());
    }

    if (this.mediaQuery && typeof this.mediaQuery.addEventListener === 'function') {
      this.mediaQuery.addEventListener('change', () => {
        if (!this.getStoredOverride()) {
          this.syncUI();
        }
      });
    }

    window.addEventListener('storage', (e) => {
      if (e.key === 'color-scheme' || e.key === 'theme' || e.key === null) {
        this.syncUI();
      }
    });
  }

  syncUI() {
    const stored = this.getStoredOverride();
    const resolved = stored || this.getSystemScheme();

    if (this.metaColorScheme) {
      this.metaColorScheme.content = stored || 'light dark';
    }

    this.htmlElement.setAttribute('data-theme', resolved);

    const icon = resolved === 'dark' ? '🌙' : '☀️';
    const isDark = resolved === 'dark';

    if (this.themeIconMobile) this.themeIconMobile.textContent = icon;
    if (this.themeIconDesktop) this.themeIconDesktop.textContent = icon;
    if (this.themeToggleMobile) this.themeToggleMobile.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    if (this.themeToggleDesktop) this.themeToggleDesktop.setAttribute('aria-pressed', isDark ? 'true' : 'false');
  }

  toggleTheme() {
    const currentRendered = this.htmlElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const target = currentRendered === 'dark' ? 'light' : 'dark';
    const systemScheme = this.getSystemScheme();

    try {
      localStorage.removeItem('theme');
      if (target === systemScheme) {
        // Returning to system preference removes explicit override so OS changes apply again
        localStorage.removeItem('color-scheme');
      } else {
        localStorage.setItem('color-scheme', target);
      }
    } catch (e) {
      // Apply in-memory even if persistence is unavailable
    }

    if (this.metaColorScheme) {
      this.metaColorScheme.content = target === systemScheme ? 'light dark' : target;
    }
    this.htmlElement.setAttribute('data-theme', target);

    const icon = target === 'dark' ? '🌙' : '☀️';
    const isDark = target === 'dark';
    if (this.themeIconMobile) this.themeIconMobile.textContent = icon;
    if (this.themeIconDesktop) this.themeIconDesktop.textContent = icon;
    if (this.themeToggleMobile) this.themeToggleMobile.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    if (this.themeToggleDesktop) this.themeToggleDesktop.setAttribute('aria-pressed', isDark ? 'true' : 'false');
  }
}

class GalleryManager {
  constructor() {
    this.galleryTrack = document.querySelector('.gallery-track');
    this.loaded = false;
  }

  async ensureLoaded() {
    if (this.loaded || !this.galleryTrack) return;
    this.loaded = true;

    try {
      const response = await fetch('get-gallery-images.php', { cache: 'no-cache' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const images = await response.json();
      this.renderGallery(Array.isArray(images) ? images : []);
    } catch (error) {
      this.loaded = false;
      console.error('Error loading gallery images:', error);
    }
  }

  renderGallery(images) {
    this.galleryTrack.replaceChildren();

    if (images.length === 0) {
      const p = document.createElement('p');
      p.textContent = 'No images found.';
      this.galleryTrack.appendChild(p);
      return;
    }

    const fragment = document.createDocumentFragment();

    images.forEach((imagePath, index) => {
      const img = document.createElement('img');
      img.src = String(imagePath);
      img.alt = `Volunteers processing donated eyeglasses (${index + 1} of ${images.length})`;
      img.height = 300;
      img.decoding = 'async';
      if (index < 3) {
        img.loading = 'eager';
      } else {
        img.loading = 'lazy';
        img.fetchPriority = 'low';
      }
      fragment.appendChild(img);
    });

    // Clone track items for seamless CSS loop (marked decorative for assistive tech)
    images.forEach(imagePath => {
      const clone = document.createElement('img');
      clone.src = String(imagePath);
      clone.alt = '';
      clone.setAttribute('aria-hidden', 'true');
      clone.height = 300;
      clone.loading = 'lazy';
      clone.decoding = 'async';
      clone.fetchPriority = 'low';
      clone.classList.add('gallery-clone');
      fragment.appendChild(clone);
    });

    this.galleryTrack.appendChild(fragment);
    this.galleryTrack.classList.add('animate-scroll');
  }
}

function showToast(message, type = 'info', title = '') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.setAttribute('role', 'status');
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  const allowedTypes = ['success', 'error', 'info', 'warning'];
  const safeType = allowedTypes.includes(type) ? type : 'info';
  toast.className = `toast toast-${safeType}`;

  const icon = safeType === 'success' ? '✓' : (safeType === 'error' ? '✕' : 'ℹ');
  const defaultTitle = safeType.charAt(0).toUpperCase() + safeType.slice(1);

  const iconEl = document.createElement('div');
  iconEl.className = 'toast-icon';
  iconEl.setAttribute('aria-hidden', 'true');
  iconEl.textContent = icon;

  const contentEl = document.createElement('div');
  contentEl.className = 'toast-content';

  const titleEl = document.createElement('div');
  titleEl.className = 'toast-title';
  titleEl.textContent = title || defaultTitle;

  const messageEl = document.createElement('div');
  messageEl.className = 'toast-message';
  messageEl.textContent = message;

  contentEl.append(titleEl, messageEl);
  toast.append(iconEl, contentEl);
  container.appendChild(toast);

  setTimeout(() => toast.classList.add('show'), 10);

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 500);
  }, 5000);
}

document.addEventListener('DOMContentLoaded', () => {
  const galleryManager = new GalleryManager();
  new ThemeManager();
  new Router(galleryManager);

  // Handle contact form status redirect query parameters
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('status') === 'success') {
    showToast('Your message has been sent successfully.', 'success', 'Message Sent');
    window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
  } else if (urlParams.get('status') === 'error') {
    const reason = urlParams.get('reason') || '';
    let msg = 'There was an error sending your message. Please try again.';
    if (reason === 'invalid_input') {
      msg = 'Please check your information and try again.';
    } else if (reason === 'rate_limited') {
      msg = 'Too many messages sent recently. Please wait a few minutes and try again.';
    }
    showToast(msg, 'error', 'Error');
    window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
  }

  // Prevent double-submission on the Contact Us form after valid submit click
  const contactForm = document.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', () => {
      if (contactForm.checkValidity()) {
        const btn = contactForm.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Sending...';
        }
      }
    });
  }

  // Native <dialog> modal logic + Safari closedby="any" light-dismiss fallback
  const modal = document.getElementById('signupModal');
  const closeBtn = modal ? modal.querySelector('.close-modal') : null;
  const signupForm = document.getElementById('volunteerSignupForm');

  if (modal) {
    if (closeBtn) {
      closeBtn.addEventListener('click', () => modal.close());
    }
    modal.addEventListener('close', () => {
      modal.returnValue = '';
    });

    if (typeof HTMLDialogElement !== 'undefined' && !('closedBy' in HTMLDialogElement.prototype)) {
      const outside = (e, dialog) => {
        const r = dialog.getBoundingClientRect();
        return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
      };
      for (const dialog of document.querySelectorAll('dialog[closedby="any"]')) {
        let pressedOutside = false;
        dialog.addEventListener('pointerdown', (e) => { pressedOutside = outside(e, dialog); });
        dialog.addEventListener('pointerup', (e) => { if (pressedOutside && outside(e, dialog)) dialog.close(); });
      }
    }
  }

  if (signupForm && modal) {
    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = signupForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Registering...';
      submitBtn.disabled = true;

      const honeypotEl = document.getElementById('signupWebsiteUrl');
      const formData = {
        eventId: document.getElementById('signupEventId').value,
        name: document.getElementById('signupName').value,
        email: document.getElementById('signupEmail').value,
        phone: document.getElementById('signupPhone').value,
        website_url: honeypotEl ? honeypotEl.value : ''
      };

      try {
        const response = await fetch('register-volunteer.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
          showToast('Registration successful! Check your email for confirmation.', 'success', 'Registered');
          modal.close();
          signupForm.reset();

          if (window.eventCalendar) {
            await window.eventCalendar.loadEvents();
            window.eventCalendar.renderCalendar();
            window.eventCalendar.renderUpcomingEvents();
            if (window.eventCalendar.selectedDate) {
              window.eventCalendar.showDayEvents(window.eventCalendar.selectedDate);
            }
          }
        } else {
          showToast(result.message || 'Registration failed.', 'error', 'Registration Failed');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error', 'Connection Error');
      } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    });
  }
});

window.openSignupModal = function (eventId, title, date, time) {
  const modal = document.getElementById('signupModal');
  if (!modal) return;
  document.getElementById('signupEventId').value = String(eventId);
  document.getElementById('modalEventTitle').textContent = 'Sign Up: ' + title;
  document.getElementById('modalEventDetails').textContent = `${date} • ${time}`;
  if (typeof modal.showModal === 'function') {
    if (!modal.open) modal.showModal();
  } else {
    modal.setAttribute('open', '');
  }
};
