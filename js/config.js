// Global configuration
const SITE_CONFIG = {
    email: "2e2erc1854@gmail.com",
    phone: "(817) 710-5403",
    phoneLink: "+18177105403",
    defaultImage: "e2e2rc_LOGO.png" // Image in the root directory
};

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
document.addEventListener('DOMContentLoaded', updateGlobalInfo);
