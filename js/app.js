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

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new Router();
    new ThemeManager();
});

// Form submission handler
function handleSubmit(event) {
    event.preventDefault();
    const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        subject: document.getElementById('subject').value,
        message: document.getElementById('message').value,
        subscribe: document.getElementById('subscribe').checked
    };
    
    alert('Thank you for your message! We will get back to you soon.\n\nForm Data:\n' + JSON.stringify(formData, null, 2));
    event.target.reset();
}
