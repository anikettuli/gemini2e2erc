# Lions District 2-E2 Eyeglass Recycling Center (ERC) Website

A modern, responsive, and accessible web application for the District 2-E2 Eyeglass Recycling Center, built following **OpenUI** design system principles and optimized for standard GoDaddy shared/WordPress hosting environments (HTML5, Vanilla CSS, Modern ES6+ JavaScript, and PHP 7.4+).

---

## 🛠 Technical Architecture

### Frontend
- **Framework & Guidelines**: Built using [OpenUI](https://openui.com) component standards & design tokens (sleek surfaces, accessible WCAG contrast, responsive layout grids).
- **HTML5**: Semantic tags, native `<dialog>` for modal registration, and native `<details>`/`<summary>` for collapsible FAQs.
- **CSS3 (Vanilla)**: High-performance, tokenized design system (`css/styles.css`) supporting system-aware Dark & Light modes with zero build dependencies.
- **JavaScript (ES6+)**: Consolidated, high-performance vanilla JS module (`js/app.js`) handling hash routing, theme switching, live search, calendar logic, and AJAX requests.
- **Mapping**: Open-source MapLibre GL JS rendering drop-off locations with pre-computed GPS coordinates.

### Backend & Deployment
- **Target Platform**: GoDaddy cPanel / Managed WordPress / Apache Shared Hosting (PHP 7.4+).
- **Core Endpoints**:
  - `register-volunteer.php`: Concurrency-safe atomic registration with `flock(LOCK_EX)`, capacity validation, past-event checks, and automated SMTP confirmations.
  - `send-email.php`: Contact form handler with CRLF injection protection, anti-spam validation, and SMTP relay.
  - `admin-view.php`: Secure administrative dashboard protected by constant-time HTTP Basic Auth (`hash_equals`), CSRF tokens, session verification, and CSV volunteer export.
  - `get-locations.php`, `get-config.php`, `get-gallery-images.php`: High-speed JSON endpoints.
- **Security Firewalls (`.htaccess`)**:
  - Enforces HTTPS and standard security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`).
  - Passes FastCGI/PHP-FPM authorization headers (`HTTP_AUTHORIZATION`).
  - Denies public web access to `.env`, `data/signups.json`, `*.log`, and temporary files.

---

## 📂 Project Structure

```text
├── .htaccess               # Apache configuration & security firewall
├── admin-view.php          # Admin console (Volunteer signups, Events, Locations, CSV export)
├── config.php              # Centralized site settings & environment helper
├── get-config.php          # Public site config API
├── get-gallery-images.php  # Public gallery images API
├── get-locations.php       # Drop-off locations API with pre-computed coordinates
├── index.html              # Main application entry point (OpenUI SPA)
├── mail-utils.php          # Standalone lightweight SMTP client & branded email templates
├── register-volunteer.php  # Atomic volunteer registration processor
├── send-email.php          # Contact form processor
├── css/
│   └── styles.css          # Tokenized OpenUI design system (Light/Dark themes)
├── data/
│   ├── board.json          # Board of Directors profiles (Current & Previous)
│   ├── events.json         # Scheduled volunteer events & capacities
│   ├── locations.json      # 215 drop-off locations with GPS coordinates
│   ├── partners.json       # Community partners & friends list
│   ├── signups.json        # Writable volunteer registrations (protected)
│   └── testimonials.json   # Partner testimonials
├── files/                  # Downloadable official PDFs
├── gallery/                # Volunteer action photo gallery
├── images/                 # Optimized brand assets & event graphics
└── js/
    └── app.js              # Consolidated OpenUI application logic
```

---

## 🌟 Key Features

1. **Top Announcement Bar**: Displays Saturday open hours (9:00 AM – 3:00 PM), center address, and quick links.
2. **Interactive Location Search**: Real-time filtering by **Zip Code, City, Partner Name, or Street Address** across 200+ drop-off locations with one-click Google Maps directions.
3. **Volunteer Management**: Live calendar, capacity progress bars, spots-remaining indicators, and atomic registration.
4. **Current & Previous Board Members**: Clear leadership sections with high-resolution portraits.
5. **Staff Admin Suite**: Volunteer management with instant one-click CSV export, event scheduling, and partner location manager.
6. **Dark / Light Mode**: Instant, flicker-free theme toggle with `localStorage` and OS preference syncing.
