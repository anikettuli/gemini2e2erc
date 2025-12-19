// Modern App Logic for District 2-E2 ERC

class TurboRouter {
  constructor() {
    this.mainContent = document.querySelector('main');
    this.initLinkInterception();
    window.addEventListener('popstate', () => this.handlePopState());
  }

  initLinkInterception() {
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a');
      if (link && link.href.includes('?page=') && !link.getAttribute('target')) {
        const url = new URL(link.href, window.location.origin);
        if (url.origin === window.location.origin) {
          e.preventDefault();
          this.navigate(url.href);
        }
      }
    });
  }

  async navigate(url, push = true) {
    try {
      const response = await fetch(url);
      const text = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(text, 'text/html');
      const newMain = doc.querySelector('main').innerHTML;
      const newTitle = doc.title;

      this.mainContent.style.opacity = '0';
      this.mainContent.style.transition = 'opacity 0.2s ease';

      setTimeout(() => {
        this.mainContent.innerHTML = newMain;
        document.title = newTitle;
        this.updateNavState(url);

        if (push) window.history.pushState({}, '', url);

        // Re-initialize logic
        this.reinitPageScripts();

        this.mainContent.style.opacity = '1';
        // Force scroll to top on all browsers
        window.scrollTo({ top: 0, behavior: 'instant' });
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        // Robust AOS Handling - with increased delays to prevent race conditions
        if (window.AOS) {
          // First pass: wait for DOM to settle
          setTimeout(() => {
            window.AOS.refreshHard();
            // Second pass: force-animate any stubborn elements
            setTimeout(() => {
              document.querySelectorAll('[data-aos]').forEach(el => {
                if (getComputedStyle(el).opacity === '0' || getComputedStyle(el).visibility === 'hidden') {
                  el.classList.add('aos-animate');
                  el.style.opacity = '1';
                  el.style.visibility = 'visible';
                  el.style.transform = 'none';
                }
              });
            }, 500);
          }, 150);
        }
      }, 200);

    } catch (error) {
      console.error('Navigation error:', error);
      window.location.href = url;
    }
  }

  handlePopState() {
    this.navigate(window.location.href, false);
  }

  updateNavState(url) {
    const currentParams = new URLSearchParams(new URL(url).search);
    const currentPage = currentParams.get('page') || 'home';

    document.querySelectorAll('nav a').forEach(a => {
      // 1. Always remove active classes first
      a.classList.remove('text-indigo-600', 'dark:text-indigo-400', 'bg-indigo-50', 'dark:bg-indigo-900/20', 'dark:bg-white/10', 'font-semibold');
      a.classList.add('text-slate-600', 'dark:text-slate-300');

      // 2. Determine if this link matches the current page
      let linkPage = null;
      try {
        const linkUrl = new URL(a.href, window.location.origin);
        // Only consider internal links
        if (linkUrl.searchParams.has('page')) {
          linkPage = linkUrl.searchParams.get('page');
        } else if (linkUrl.pathname.endsWith('index.php') || linkUrl.pathname === '/' || linkUrl.pathname.endsWith('/')) {
          // Treat root or index.php as home
          linkPage = 'home';
        }
      } catch (e) {
        // Ignore invalid URLs (tel:, mailto:, etc inside nav)
      }

      // 3. Apply active classes if match
      if (linkPage === currentPage) {
        a.classList.add('text-indigo-600', 'dark:text-indigo-400', 'bg-indigo-50', 'dark:bg-indigo-900/20', 'font-semibold');
        a.classList.remove('text-slate-600', 'dark:text-slate-300'); // Remove inactive styling
      }
    });
  }

  reinitPageScripts() {
    // Calendar
    if (window.eventCalendar) window.eventCalendar.init();

    // Global Info
    if (typeof updateGlobalInfo === 'function') updateGlobalInfo();

    // Maps
    const mapContainer = document.getElementById('allLocationsMap');
    if (mapContainer && typeof loadLocations === 'function') {
      loadLocations();
    }

    // Dynamic Lists
    if (typeof window.loadPartners === 'function') window.loadPartners();
    if (typeof window.loadBoard === 'function') window.loadBoard();

    // Gallery
    if (document.querySelector('.gallery-track')) {
      new GalleryManager();
    }

    // Bind Modals if present
    window.bindModalLogic();
    // Bind Contact Form if present
    if (window.bindContactLogic) window.bindContactLogic();
  }
}

class ThemeManager {
  constructor() {
    this.themeToggle = document.getElementById('themeToggleDesktop');
    this.themeToggleMobile = document.getElementById('themeToggleMobile');
    this.htmlElement = document.documentElement;
    this.init();
  }
  init() {
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      this.htmlElement.classList.add('dark');
    } else {
      this.htmlElement.classList.remove('dark');
    }
    if (this.themeToggle) {
      this.themeToggle.addEventListener('click', () => this.toggleTheme());
    }
    if (this.themeToggleMobile) {
      this.themeToggleMobile.addEventListener('click', () => this.toggleTheme());
    }
  }
  toggleTheme() {
    if (this.htmlElement.classList.contains('dark')) {
      this.htmlElement.classList.remove('dark');
      localStorage.theme = 'light';
    } else {
      this.htmlElement.classList.add('dark');
      localStorage.theme = 'dark';
    }
  }
}

class GalleryManager {
  constructor() {
    this.track = document.querySelector('.gallery-track');
    this.scrollInterval = null;
    if (this.track) this.init();
  }
  async init() {
    try {
      const response = await fetch('get-gallery-images.php');
      const images = await response.json();
      this.renderGallery(images);
    } catch (error) {
      console.error('Error loading gallery images:', error);
    }
  }
  renderGallery(images) {
    this.track.innerHTML = '';
    if (!images || images.length === 0) {
      this.track.innerHTML = '<p class="text-white text-center w-full">No images found.</p>';
      return;
    }
    const imagesToRender = [...images, ...images, ...images];
    imagesToRender.forEach(imagePath => {
      const img = document.createElement('img');
      img.src = imagePath;
      img.className = "inline-block h-64 w-auto rounded-xl shadow-lg mx-2 object-cover shrink-0 cursor-pointer hover:opacity-90 transition-opacity";
      img.onclick = () => window.open(imagePath, '_blank');
      this.track.appendChild(img);
    });
    this.startAutoScroll();
    this.setupControls();
  }
  startAutoScroll() {
    if (this.scrollInterval) clearInterval(this.scrollInterval);
    this.scrollInterval = setInterval(() => {
      if (this.track.matches(':hover')) return;
      this.track.scrollLeft += 1;
      if (this.track.scrollLeft >= (this.track.scrollWidth / 3) * 2) {
        this.track.scrollLeft = this.track.scrollWidth / 3;
      }
    }, 15);
  }
  setupControls() {
    const wrapper = this.track.parentElement;
    if (!wrapper) return;
    const leftBtn = wrapper.querySelector('.gallery-arrow.left');
    const rightBtn = wrapper.querySelector('.gallery-arrow.right');
    leftBtn?.addEventListener('click', (e) => { e.preventDefault(); this.track.scrollBy({ left: -400, behavior: 'smooth' }); });
    rightBtn?.addEventListener('click', (e) => { e.preventDefault(); this.track.scrollBy({ left: 400, behavior: 'smooth' }); });
    this.track.addEventListener('touchstart', () => clearInterval(this.scrollInterval));
    this.track.addEventListener('touchend', () => this.startAutoScroll());
  }
}

// Global Loaders
window.loadPartners = function () {
  const partnersList = document.getElementById('partners-list');
  if (partnersList) {
    fetch('data/partners.json')
      .then(r => r.json())
      .then(data => {
        if (Array.isArray(data)) {
          partnersList.innerHTML = '';
          data.forEach(p => {
            const li = document.createElement('li');
            li.className = "flex items-center gap-3 text-slate-600 dark:text-slate-400 mb-2";
            li.innerHTML = `<span class="w-2 h-2 rounded-full bg-indigo-500"></span> ${p}`;
            partnersList.appendChild(li);
          });
        }
      })
      .catch(console.error);
  }
};

window.loadBoard = function () {
  const boardGrid = document.getElementById('board-grid');
  if (boardGrid) {
    fetch('data/board.json')
      .then(r => r.json())
      .then(data => {
        boardGrid.innerHTML = '';
        if (data.length === 0) { boardGrid.innerHTML = '<p class="col-span-full text-center">No members found.</p>'; return; }
        data.forEach(m => {
          const card = document.createElement('div');
          card.className = "glass-panel p-6 rounded-2xl flex flex-col items-center hover:scale-105 transition-transform duration-300 group";
          card.innerHTML = `
            <div class="w-24 h-24 rounded-full overflow-hidden mb-4 border-4 border-indigo-50 dark:border-indigo-900 shadow-md group-hover:border-indigo-400 transition-colors">
              <img src="${m.image}" alt="${m.name}" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(m.name)}&background=6366f1&color=fff'">
            </div>
            <h3 class="font-bold text-lg text-slate-800 dark:text-white text-center mb-1">${m.name}</h3>
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">Board Member</span>`;
          boardGrid.appendChild(card);
        });
      })
      .catch((e) => { console.error(e); boardGrid.innerHTML = '<p class="col-span-full text-center">Error loading members.</p>'; });
  }
};

window.bindModalLogic = function () {
  const modal = document.getElementById('signupModal');
  const signupForm = document.getElementById('volunteerSignupForm');
  if (!modal) return;

  document.querySelectorAll('.close-modal').forEach(btn => btn.onclick = () => modal.classList.add('hidden'));
  modal.addEventListener('click', (e) => {
    if (e.target === modal || e.target.classList.contains('close-modal-bg')) modal.classList.add('hidden');
  });

  if (signupForm && !signupForm.dataset.bound) {
    signupForm.dataset.bound = 'true'; // Prevent multiple bindings
    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = signupForm.querySelector('button[type="submit"]');
      const originalText = btn.innerText;
      btn.innerText = "Registering...";
      btn.disabled = true;
      try {
        const formData = {
          eventId: document.getElementById('signupEventId').value,
          name: document.getElementById('signupName').value,
          email: document.getElementById('signupEmail').value,
          phone: document.getElementById('signupPhone').value
        };
        const res = await fetch('register-volunteer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData) });
        const result = await res.json();
        if (result.success) {
          alert('Registration successful!');
          modal.classList.add('hidden');
          signupForm.reset();
          if (window.eventCalendar) { await window.eventCalendar.loadEvents(); window.eventCalendar.renderCalendar(); }
        } else { alert(result.message); }
      } catch (e) { alert('Error occurred.'); }
      finally { btn.innerText = originalText; btn.disabled = false; }
    });
  }
};

window.bindContactLogic = function () {
  const contactForm = document.getElementById('contactForm');
  if (contactForm && !contactForm.dataset.bound) {
    contactForm.dataset.bound = 'true';
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = contactForm.querySelector('button[type="submit"]');
      const originalText = btn.innerText;
      btn.innerText = "Sending...";
      btn.disabled = true;

      try {
        const formData = new FormData(contactForm);
        formData.append('ajax', '1');

        const res = await fetch('send-email.php', {
          method: 'POST',
          body: formData
        });

        const result = await res.json();

        if (result.success) {
          alert('Message sent successfully!');
          contactForm.reset();
        } else {
          alert(result.message || 'Error sending message.');
        }
      } catch (err) {
        console.error(err);
        alert('An network error occurred. Please try again.');
      } finally {
        btn.innerText = originalText;
        btn.disabled = false;
      }
    });
  }
};

window.openSignupModal = function (eventId, title, date, time) {
  const modal = document.getElementById('signupModal');
  if (!modal) return;
  document.getElementById('signupEventId').value = eventId;
  document.getElementById('modalEventTitle').innerText = "Sign Up: " + title;
  document.getElementById('modalEventDetails').innerText = `${date} • ${time}`;
  modal.classList.remove('hidden');
};

document.addEventListener('DOMContentLoaded', () => {
  if (!window.appRouter) window.appRouter = new TurboRouter();
  new ThemeManager();
  new GalleryManager();
  window.loadPartners();
  window.loadBoard();
  window.bindModalLogic();
  if (window.bindContactLogic) window.bindContactLogic();
});
