# Website Data Directory (`data/`)

This folder stores the JSON files that power dynamic content and form submissions on `2e2erc.org`.

> **Important:** `data/*.json` is excluded from GitHub Actions FTP deployments so live volunteer signups and Admin Dashboard changes on GoDaddy cPanel are never overwritten when code updates are deployed.

## Public Content Files (`0644`)
Served to the frontend or read by public endpoints:
- **`events.json`**: Volunteer calendar events (`id`, `title`, `date` in `YYYY-MM-DD`, `time`, `people`, `maxPeople`, `location`, `image`, `description`, `contact`).
- **`board.json`**: Board of Directors profiles (`name`, `image`, `tag` = `"current"` or `"previous"`).
- **`partners.json`**: Array of partner organization names displayed on `#get-involved`.
- **`testimonials.json`**: Mission partner quotes (`text`, `author`, `organization`) displayed on `#home`.
- **`locations.json`**: Drop-off locations (`name`, `address`, `phone`, optional `lat`/`lng`), merged with `geo_cache.json` by `get-locations.php`.
- **`geo_cache.json`**: Cached latitude/longitude lookups keyed by street address. Automatically updated when saving a location in `admin-view.php` (or via `php tools/warm-cache.php`).

## Protected Server-Only Files (`0600`)
Blocked from direct HTTP access via `data/.htaccess` (`signups.json`, `admin-auth.json`, `site-config.json`, `*.lock`, `*.log`):
- **`signups.json`**: Volunteer event registrations managed atomically by `register-volunteer.php` and `admin-view.php`. Do not edit manually while the site is live.
- **`site-config.json`**: Optional global `email` and `phone` overrides saved from `admin-view.php` (read by `config.php` and `get-config.php`).
- **`admin-auth.json`**: Optional bcrypt admin credential file written when rotating the admin password in `admin-view.php`.
- **`write.lock`**: Persistent advisory lockfile used by `erc_with_storage_lock()`.
- **`email_errors.log`**: Operational SMTP error log written by `mail-utils.php`.
