# Lions District 2-E2 Eyeglass Recycling Center (ERC) Website

A responsive, accessible single-page website and lightweight PHP administration portal for the Lions District 2-E2 Eyeglass Recycling Center (`2e2erc.org`).

## Technical Stack

### Frontend
- **HTML5**: Semantic landmarks, ARIA tablist/tabpanel navigation, native `<dialog closedby="any">` volunteer registration modal, and explicit responsive image dimensions.
- **CSS3**: Custom design tokens with two-state Light/Dark `color-scheme` support, `:focus-visible` keyboard rings, `:user-invalid` validation states, and `prefers-reduced-motion` support.
- **Vanilla JavaScript (ES6+)**: Zero-build modular scripts (`defer`-loaded) using safe DOM construction (`createElement` / `textContent`) and per-tab lazy data hydration.
- **MapLibre GL JS**: Lazily loaded with SHA-384 Subresource Integrity (SRI) only when a visitor opens the `#locations` tab.

### Backend (GoDaddy cPanel / Apache + PHP 7.4+/8.x)
- `config.php`: Shared `.env` parser, re-entrant `flock()` storage lock (`data/write.lock`), atomic temp-file-plus-rename JSON persistence, `.webp`-aware gallery scanner, and per-IP sliding-window rate limiter.
- `mail-utils.php`: Shared HTML email template renderer, protected mail error logger (`data/email_errors.log`), and RFC 5321-hardened `SimpleSMTP` client (CRLF header sanitization + DATA dot-stuffing).
- `send-email.php`: Contact Us form processor with honeypot and per-IP rate limiting.
- `register-volunteer.php`: Transactional volunteer signup endpoint updating `data/signups.json` and `data/events.json` atomically under lock.
- `get-config.php`, `get-locations.php`, `get-gallery-images.php`: Lightweight JSON endpoints for global contact info, geocoded drop-off locations, and gallery photos.
- `admin-view.php`: HTTP Basic + CSRF-protected administration dashboard with bcrypt password rotation (`data/admin-auth.json`), Post/Redirect/Get (`303`) form handling, GD image optimization + `.webp` sidecars, and automatic Nominatim/US Census geocoding when saving locations.

## Project Structure

```text
├── .htaccess               # Apache security headers, WebP negotiation, caching, dotfile/helper protection
├── admin-view.php          # Admin dashboard (events, signups, board, partners, locations, gallery, files, settings)
├── config.php              # Shared env, locking, atomic JSON, gallery scan, and rate-limit helpers
├── get-config.php          # Public site contact configuration endpoint
├── get-gallery-images.php  # Dynamic gallery directory endpoint (deduplicates .webp sidecars)
├── get-locations.php       # Merges data/locations.json with data/geo_cache.json
├── index.html              # SPA entry point
├── mail-utils.php          # Branded email template + SimpleSMTP client
├── register-volunteer.php  # Volunteer registration processor
├── send-email.php          # Contact form processor
├── css/
│   └── styles.css          # Main stylesheet
├── data/                   # Flat-file JSON data store (preserved across FTP deployments)
├── files/                  # Downloadable PDF brochures & forms
├── gallery/                # Volunteer gallery photos (preserved across FTP deployments)
├── images/                 # Static UI icons, placeholders, board/ & events/ uploads
├── js/
│   ├── app.js              # Hash router, theme manager, gallery carousel, dialog & toast handlers
│   ├── calendar.js         # Accessible calendar & volunteer event cards
│   ├── config.js           # Hydrates phone/email links from get-config.php
│   ├── dynamic-content.js  # Lazy board, partners, and testimonials renderers
│   └── locations.js        # Lazy MapLibre GL loader + A-Z drop-off directory
├── tests/
│   └── smoke.php           # CLI smoke test suite (run automatically in CI prior to FTP deploy)
└── tools/
    └── warm-cache.php      # Optional CLI utility to pre-populate data/geo_cache.json
```

## Configuration (`.env`)

Create a `.env` file in the webroot on GoDaddy cPanel (never committed to Git):

```ini
SMTP_HOST=p3plzcpnl507374.prod.phx3.secureserver.net
SMTP_PORT=465
SMTP_USER=noreply@2e2erc.org
SMTP_PASS=your_smtp_password
ADMIN_USER=admin
# Generate a bcrypt hash via Admin Dashboard -> General Settings -> Admin Credentials,
# or set ADMIN_PASS_HASH / ADMIN_PASS here:
ADMIN_PASS_HASH=$2y$12$...
```

> **Note:** Changing the password inside `admin-view.php` ("Admin Credentials") writes a bcrypt hash to `data/admin-auth.json` (`0600`), which takes precedence without requiring SSH/cPanel file edits.

## Local Development & Testing

```bash
# Run syntax checks and automated smoke tests
php tests/smoke.php

# Start local development server
php -S localhost:8000
```

## Deployment (GoDaddy cPanel)

Pushes to `main` trigger `.github/workflows/deploy.yml`, which:
1. Lints all PHP scripts (`php -l`) and runs `php tests/smoke.php`.
2. Generates `.webp` companions for any changed `.jpg`/`.jpeg`/`.png` assets.
3. Syncs application code via FTP while **excluding** live mutable directories (`data/*.json`, `gallery/*`, `images/events/*`, `images/board/*`) so admin edits and volunteer signups on GoDaddy are never overwritten.
