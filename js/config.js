// Global configuration
let SITE_CONFIG = {
    email: "2e2erc1854@gmail.com",
    phone: "(817) 710-5403",
    phoneLink: "+18177105403",
    defaultImage: "e2e2rc_LOGO.png"
};

// Function to fetch config from server for a single source of truth
async function fetchConfig() {
    try {
        const response = await fetch('get-config.php');
        const data = await response.json();
        SITE_CONFIG = { ...SITE_CONFIG, ...data };
        updateGlobalInfo();
    } catch (error) {
        console.error('Error fetching site config:', error);
        // Fallback to defaults already in SITE_CONFIG
        updateGlobalInfo();
    }
}

// Function to update HTML elements with global config values
function updateGlobalInfo() {
    // Update Email Links
    document.querySelectorAll('.global-email-link').forEach(el => {
        el.href = `mailto:${SITE_CONFIG.email}`;
        if (el.classList.contains('show-text')) {
            el.textContent = SITE_CONFIG.email;
        }
    });

    // Update Email Text (non-link)
    document.querySelectorAll('.global-email-text').forEach(el => {
        el.textContent = SITE_CONFIG.email;
    });

    // Update Phone Links
    document.querySelectorAll('.global-phone-link').forEach(el => {
        el.href = `tel:${SITE_CONFIG.phoneLink}`;
        if (el.classList.contains('show-text')) {
            el.textContent = SITE_CONFIG.phone;
        }
    });

    // Update Phone Text (non-link)
    document.querySelectorAll('.global-phone-text').forEach(el => {
        el.textContent = SITE_CONFIG.phone;
    });
}

// Run update on load
document.addEventListener('DOMContentLoaded', fetchConfig);

