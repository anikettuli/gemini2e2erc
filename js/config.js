// Global site contact configuration (hydrated from get-config.php)
let SITE_CONFIG = {
  email: '2e2erc1854@gmail.com',
  phone: '(817) 710-5403',
  phoneLink: '+18177105403',
  defaultImage: 'e2e2rc_LOGO.png'
};

async function fetchConfig() {
  try {
    const response = await fetch('get-config.php', { cache: 'no-cache' });
    if (response.ok) {
      const data = await response.json();
      if (data && typeof data === 'object') {
        SITE_CONFIG = { ...SITE_CONFIG, ...data };
      }
    }
  } catch (error) {
    console.error('Error fetching site config:', error);
  } finally {
    updateGlobalInfo();
  }
}

function updateGlobalInfo() {
  document.querySelectorAll('.global-email-link').forEach(el => {
    el.href = `mailto:${SITE_CONFIG.email}`;
    if (el.classList.contains('show-text')) {
      el.textContent = SITE_CONFIG.email;
    }
  });

  document.querySelectorAll('.global-email-text').forEach(el => {
    el.textContent = SITE_CONFIG.email;
  });

  document.querySelectorAll('.global-phone-link').forEach(el => {
    el.href = `tel:${SITE_CONFIG.phoneLink}`;
    if (el.classList.contains('show-text')) {
      el.textContent = SITE_CONFIG.phone;
    }
  });

  document.querySelectorAll('.global-phone-text').forEach(el => {
    el.textContent = SITE_CONFIG.phone;
  });
}

document.addEventListener('DOMContentLoaded', fetchConfig);
