# Website To-Do List & Status

## ✅ Priority Tasks — Status (Completed)

- [x] **1. Change all emails to 2e2erc1854@gmail.com**
    - Status: Completed. Master contact configured in `config.php` and referenced across forms.

- [x] **2. Board of Directors Sections**
    - Status: Completed. Split into "Current Board of Directors" and "Previous Board Members" with dedicated responsive card grids in `#about`.

- [x] **3. 2nd Saturday Event Logo & Session Branding**
    - Status: Completed. References `images/events/second-saturday-session.jpg`.

- [x] **4. Robson Ranch Events & Upcoming Volunteer Dates**
    - Status: Completed. Updated `data/events.json` with active 2026 sessions.

- [x] **5. Mission Request Form**
    - Status: Completed. Accessible via `files/Mission-Request-Form.pdf` in `#about` and `#services`.

- [x] **6. Location List & Pre-computed Coordinates**
    - Status: Completed. Coordinates pre-baked into `data/locations.json`, eliminating redundant geocoding runtime queries.

- [x] **7. Locations Page — Zip Code & City Live Search**
    - Status: Completed. Full real-time search filtering across Zip Code, City, Partner Name, and Address in the Locations tab.

- [x] **8. Top Announcements Banner**
    - Status: Completed. Integrated responsive alert banner at the very top of the website for Saturday walk-in hours, greetings, and weather closures.

- [x] **9. Contact Form "Need Collection Boxes" Routing**
    - Status: Completed. Pre-populated subject option in Contact form with direct action triggers from Services.

- [x] **10. Security & Concurrency Hardening**
    - Status: Completed. Protected `signups.json` and `.env` in `.htaccess`, implemented atomic `flock` locking in `register-volunteer.php`, constant-time Basic Auth in `admin-view.php`, and CSRF protection.

- [x] **11. OpenUI Rebuild & Performance Streamlining**
    - Status: Completed. Modern component-driven OpenUI design system, WCAG compliant contrast, native HTML5 dialog & details, single high-performance `app.js` module.
