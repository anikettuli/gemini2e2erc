# 🔍 Remaining Codebase Audit Report

The following is a list of known issues, potential UX improvements, and security concerns. Critical functional bugs (incorrect contact info, conflicting stats, broken scrollbars) have been **resolved**.

## 🛡️ Security & Code Quality

1.  **Hardcoded Admin Password**
    -   **File**: `admin-view.php`
    -   **Issue**: The admin password ("Lions@2025") is hardcoded in the PHP file.
    -   **Recommendation**: Move to an Environment Variable (`.env`) or a secure database config.

2.  **Legacy CSS File**
    -   **File**: `css/styles.css`
    -   **Issue**: This file is no longer linked (unlinked to fix Dark Mode font colors & whitespace bugs), but still exists in the codebase.
    -   **Recommendation**: Verify if any rules are needed (e.g., specific print styles) and migrate them to Tailwind or `index.php`, then delete the file.

3.  **Public Admin Link**
    -   **File**: `templates/footer.php`
    -   **Issue**: Contains a visible "Admin Portal" link.
    -   **Recommendation**: Remove from public footer or hide behind a keyboard shortcut/hidden trigger.

## 🎨 Visual Annoyances & UX Inconsistencies

4.  **External Dependency Risk**
    -   **File**: `views/home.php`
    -   **Issue**: Relies on `www.transparenttextures.com` for the "Call to Action" background pattern.
    -   **Recommendation**: Download the pattern asset to the local `images/` directory to ensure reliability.

5.  **Map Marker Fallback**
    -   **File**: `js/locations.js`
    -   **Issue**: Relies on a specific `images/marker-icon.svg`. If this file is missing or fails to load, the marker might be invisible.
    -   **Recommendation**: Add a robust fallback to a default colored HTML element if the image fails.

## ✅ Resolved Items
-   **Inconsistent Form Experience**: Refactored Contact Form to use AJAX/JSON, matching the modern experience of the Volunteer form.
-   **Process Terminology Mismatch**: Standardized `views/services.php` to match the 4-step process described in `views/home.php`.
-   **Stats Inconsistency**: Footer now matches "20+ countries" (was "40+").
-   **Dark Mode Font Colors**: Fixed by unlinking legacy `styles.css`.
-   **Whitespace Issues**: Fixed by adjusting padding and removing global margins.
-   **Contact Page Readability**: Improved Dark Mode text contrast for Phone/Email links.
