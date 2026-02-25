// Hash-based routing for persisting tab state
class Router {
  constructor() {
    this.tabs = ['home', 'about', 'services', 'get-involved', 'locations', 'contact'];
    this.mobileNav = document.getElementById('mobileNav');
    this.dropdownArrow = document.querySelector('.dropdown-arrow');
    this.currentPageName = document.querySelector('.current-page-name');
    this.init();
  }

  init() {
    // Handle initial load
    this.navigateToHash();

    // Handle hash changes
    window.addEventListener('hashchange', () => this.navigateToHash());

    // Setup tab buttons
    document.querySelectorAll('.tab-button, .js-tab-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const tab = btn.getAttribute('data-tab');
        // Use pushState to prevent default browser scrolling to anchor
        history.pushState(null, null, '#' + tab);
        window.dispatchEvent(new Event('hashchange'));
      });
    });

    // Setup Mobile Menu
    const mobileMenuTrigger = document.getElementById('mobileMenuTrigger');
    if (mobileMenuTrigger && this.mobileNav) {
      mobileMenuTrigger.addEventListener('click', () => {
        this.mobileNav.classList.toggle('open');
        if (this.dropdownArrow) this.dropdownArrow.classList.toggle('open');
      });
    }
  }

  navigateToHash() {
    let tab = window.location.hash.slice(1) || 'home';

    // Validate tab
    if (!this.tabs.includes(tab)) {
      tab = 'home';
      window.location.hash = tab;
    }

    this.switchTab(tab);
  }

  switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => {
      el.classList.remove('active');
    });

    // Remove active from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
      btn.classList.remove('active');
    });

    // Show selected tab
    const tabContent = document.getElementById(tab);
    if (tabContent) {
      tabContent.classList.add('active');
    }

    // Activate button
    const tabBtn = document.querySelector(`[data-tab="${tab}"]`);
    if (tabBtn) {
      tabBtn.classList.add('active');
      // Update mobile menu text
      if (this.currentPageName) {
        this.currentPageName.textContent = tabBtn.textContent;
      }
    }

    // Close mobile menu
    if (this.mobileNav) {
      this.mobileNav.classList.remove('open');
      if (this.dropdownArrow) this.dropdownArrow.classList.remove('open');
    }

    // Scroll to top
    setTimeout(() => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 10);
  }
}

class ThemeManager {
  constructor() {
    this.themeToggleMobile = document.getElementById('themeToggle');
    this.themeToggleDesktop = document.getElementById('themeToggleDesktop');
    this.themeIconMobile = document.getElementById('themeIcon');
    this.themeIconDesktop = document.getElementById('themeIconDesktop');
    this.htmlElement = document.documentElement;
    this.init();
  }

  init() {
    // Check for saved theme preference or system preference
    const savedTheme = localStorage.getItem('theme') ||
      (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    // Apply saved theme
    if (savedTheme === 'dark') {
      this.setTheme('dark');
    } else {
      this.setTheme('light');
    }

    // Add click listeners
    if (this.themeToggleMobile) {
      this.themeToggleMobile.addEventListener('click', () => this.toggleTheme());
    }
    if (this.themeToggleDesktop) {
      this.themeToggleDesktop.addEventListener('click', () => this.toggleTheme());
    }

    // Listen for system theme changes
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('theme')) {
          this.setTheme(e.matches ? 'dark' : 'light');
        }
      });
    }
  }

  setTheme(theme) {
    const icon = theme === 'dark' ? '🌙' : '☀️';
    if (theme === 'dark') {
      this.htmlElement.setAttribute('data-theme', 'dark');
    } else {
      this.htmlElement.removeAttribute('data-theme');
    }

    if (this.themeIconMobile) this.themeIconMobile.textContent = icon;
    if (this.themeIconDesktop) this.themeIconDesktop.textContent = icon;
  }

  toggleTheme() {
    const currentTheme = this.htmlElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    this.setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
  }
}

class GalleryManager {
  constructor() {
    this.galleryTrack = document.querySelector('.gallery-track');
    this.init();
  }

  async init() {
    if (!this.galleryTrack) return;

    try {
      const response = await fetch('get-gallery-images.php');
      const images = await response.json();
      this.renderGallery(images);
    } catch (error) {
      console.error('Error loading gallery images:', error);
    }
  }

  renderGallery(images) {
    this.galleryTrack.innerHTML = ''; // Clear existing content
    this.originalImages = images; // Store for cloning

    if (images.length === 0) {
      this.galleryTrack.innerHTML = '<p>No images found.</p>';
      return;
    }

    // Create image elements and track loading
    const imageElements = [];
    let loadedCount = 0;

    images.forEach(imagePath => {
      const img = document.createElement('img');
      img.src = imagePath;
      img.alt = 'Gallery Image';
      img.loading = 'eager'; // Load images immediately for carousel
      imageElements.push(img);
      this.galleryTrack.appendChild(img);

      // Track when each image loads
      img.onload = () => {
        loadedCount++;
        if (loadedCount === images.length) {
          // All images loaded, now setup controls and clone for infinite scroll
          this.setupInfiniteScroll();
          this.setupGalleryControls();
        }
      };

      // Handle error case - count as loaded to not block forever
      img.onerror = () => {
        loadedCount++;
        if (loadedCount === images.length) {
          this.setupInfiniteScroll();
          this.setupGalleryControls();
        }
      };
    });

    // Fallback: if images don't load within 3 seconds, start anyway
    setTimeout(() => {
      if (!this._controlsInitialized) {
        this.setupInfiniteScroll();
        this.setupGalleryControls();
      }
    }, 3000);
  }

  setupInfiniteScroll() {
    // Clone all images for a seamless CSS loop
    if (!this.originalImages || this.originalImages.length === 0) return;

    this.originalImages.forEach(src => {
      const img = document.createElement('img');
      img.src = src;
      img.alt = 'Gallery Image';
      img.classList.add('gallery-clone');
      this.galleryTrack.appendChild(img);
    });
  }

  setupGalleryControls() {
    if (this._controlsInitialized) return; // Only setup once
    this._controlsInitialized = true;

    // Use the performant CSS animation class defined in styles.css
    this.galleryTrack.classList.add('animate-scroll');
  }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  new Router();
  new ThemeManager();
  new GalleryManager();

  // Check for email status from PHP redirection
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('status') === 'success') {
    alert('Thank you! Your message has been sent.');
    // Clean the URL but keep the hash if present
    const newUrl = window.location.pathname + window.location.hash;
    window.history.replaceState({}, document.title, newUrl);
  } else if (urlParams.get('status') === 'error') {
    alert('Sorry, there was an error sending your message. Please try again.');
  }

  // Modal Logic
  const modal = document.getElementById('signupModal');
  const closeBtn = document.querySelector('.close-modal');
  const signupForm = document.getElementById('volunteerSignupForm');

  if (closeBtn) {
    closeBtn.onclick = () => modal.style.display = "none";
  }

  window.onclick = (event) => {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }

  if (signupForm) {
    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = signupForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerText;
      submitBtn.innerText = "Registering...";
      submitBtn.disabled = true;

      const formData = {
        eventId: document.getElementById('signupEventId').value,
        name: document.getElementById('signupName').value,
        email: document.getElementById('signupEmail').value,
        phone: document.getElementById('signupPhone').value
      };

      try {
        const response = await fetch('register-volunteer.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
          alert('Registration successful! Check your email for confirmation.');
          modal.style.display = "none";
          signupForm.reset();
          // Reload events to update count
          if (window.eventCalendar) {
            await window.eventCalendar.loadEvents();
            window.eventCalendar.renderCalendar();
            window.eventCalendar.renderUpcomingEvents();

            // Reset the day view to ensure no stale data is shown
            const eventsDisplay = document.getElementById('eventsDisplay');
            if (eventsDisplay) {
              eventsDisplay.innerHTML = `
                                <div class="events-display-empty">
                                    <p style="font-size: 1.1rem; margin: 0;">📅</p>
                                    <p>Select a date to view events</p>
                                </div>
                            `;
            }
          }
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      } finally {
        submitBtn.innerText = originalText;
        submitBtn.disabled = false;
      }
    });
  }
});

// Global function to open modal (called from calendar.js)
window.openSignupModal = function (eventId, title, date, time) {
  const modal = document.getElementById('signupModal');
  document.getElementById('signupEventId').value = eventId;
  document.getElementById('modalEventTitle').innerText = "Sign Up: " + title;
  document.getElementById('modalEventDetails').innerText = `${date} • ${time}`;
  modal.style.display = "block";
}
