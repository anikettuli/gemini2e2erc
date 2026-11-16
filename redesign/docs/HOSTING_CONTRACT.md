# Lions District 2-E2 ERC — Technical Architecture & GoDaddy `public_html` Hosting Contract

> **Purpose:** Authoritative technical specification for all `redesign/` builders. All architecture choices, vendored library versions, JSON/ENV schemas, Admin actions, security rules, and coexistence mechanics below are **locked**. Do not introduce unapproved runtimes, build steps, ORMs, databases, or incompatible schema changes.

---

## 1. Locked Zero-Build Local-Files Stack (`redesign/`)

The redesign must run as a **1:1 drop-in `public_html` webroot** on GoDaddy Shared Linux cPanel (Apache + PHP `7.4+/8.x`; verified locally under native PHP `8.4`) with **zero server-side build steps**.

### Backend & Routing
- **Runtime:** Pure PHP 8 pages and reusable partials under `redesign/includes/*.php` (plus root API handlers and admin portal).
- **Clean URLs:** Extensionless Apache `.htaccess` rewrite rules in production (`redesign/.htaccess`), paired with an equivalent local PHP built-in dev router script (for `php -S`) so `/about`, `/volunteer`, `/locations`, etc. work identically on Apache and `127.0.0.1:8770`.
- **Forbidden Backend/Build Tools:** **NO** Astro, **NO** React/Vue/Svelte/Next.js, **NO** Node build pipeline (Vite/Webpack/Rollup/esbuild), **NO** Tailwind CLI, **NO** SQLite/MySQL/PostgreSQL, **NO** Composer autoloader dependency required on shared hosting.

### Frontend Platform Standards
- **CSS Architecture (Baseline 2026 Native CSS):**
  - Cascade layers via `@layer`
  - Two-state Light/Dark color tokens in `OKLCH` using native `light-dark()` and `color-scheme`
  - Component responsiveness with `@container` queries
  - Multi-page navigation transitions with `@view-transition` (guarded with `prefers-reduced-motion`)
  - CSS Anchor Positioning for popovers, tooltips, and anchored menus
  - High-contrast `:focus-visible` keyboard indicators and `:user-invalid` form validation styling
- **Native HTML5 Interactive Primitives:**
  - `<dialog closedby="any">` for modals (e.g., volunteer registration, photo lightbox)
  - Popover API (`popover`, `popovertarget`) for lightweight disclosures/menus
  - `<details>` / `<summary>` for FAQs and expandable reference panels
- **JavaScript & Calendar Export:**
  - Native Browser ES Modules (`<script type="module">`) — zero bundler/transpiler.
  - Volunteer session cards/details must generate client-side **`.ics` (iCalendar)** downloads and **Google Calendar** deep links (`America/Chicago` timezone).
  - Safe DOM construction (`createElement` / `textContent`, never raw `innerHTML` on user/JSON strings).

### Locally Vendored Libraries (`redesign/vendor/`)
Zero third-party JS/CSS may depend on unpinned public CDNs for core UI/functionality. Vendor exact locked releases inside `redesign/vendor/`:

| Library | Exact Version | Vendored Location | Usage & Constraints |
| :--- | :--- | :--- | :--- |
| **Lucide Icons** | `v1.73.0` | `redesign/vendor/lucide/` | Local SVG sprite and/or PHP SVG helper (`includes/icons.php`) for crisp UI icons; replaces legacy raw Unicode emojis and remote icon fonts. |
| **MapLibre GL JS** | `v6.18.0` | `redesign/vendor/maplibre-gl/` | Interactive drop-off locator map (`maplibre-gl.js` + `maplibre-gl.css`) using MapLibre's built-in GeoJSON Supercluster (`cluster: true`) and **OpenFreeMap** vector styles (`https://tiles.openfreemap.org/styles/liberty` or `https://tiles.openfreemap.org/styles/positron` in Light theme; `https://tiles.openfreemap.org/styles/dark` in Dark theme). |
| **Fuse.js** | `v7.5.0` | `redesign/vendor/fuse/` | Client-side fuzzy search across the `214+` DFW drop-off bin locations (name, street address, city, ZIP, phone) and FAQ filtering. |
| **PHPMailer** | `v7.2.0` | `redesign/vendor/phpmailer/` | Pure PHP source files (`PHPMailer.php`, `SMTP.php`, `Exception.php`) loaded via `require_once` (no Composer needed) for authenticated GoDaddy SMTP mail delivery (`SMTPS` on `465` / `STARTTLS` on `587`). |

> **CSP Note for MapLibre + OpenFreeMap:** Legacy root `.htaccess` sent `Content-Security-Policy "frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'"` (without restrictive `connect-src`/`worker-src`/`img-src`). Keep any expanded CSP compatible with `https://tiles.openfreemap.org` (`connect-src`, `img-src`, `style-src`) and MapLibre WebGL/workers (`worker-src blob:`, `img-src data: blob: https://tiles.openfreemap.org`).

### Persistent Storage Model
- **Atomic-Locked Flat-File JSON (`data/*.json`):**
  - Exclusive `flock(LOCK_EX)` re-entrant process lock on `data/write.lock` via `erc_with_storage_lock()`.
  - Shared `flock(LOCK_SH)` reads via `erc_load_json()`.
  - Atomic writes via `erc_save_json_atomic()`: `json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)`, random temp file `$file . '.tmp.' . getmypid() . '_' . bin2hex(random_bytes(4))`, `@chmod($tmpFile, $chmod)`, and `rename()` (with Windows unlink+rename fallback when destination exists).
  - Keeps data human-readable and editable directly inside GoDaddy cPanel File Manager with zero database setup.

---

## 2. Exact On-Disk JSON & `.env` Schemas (GoDaddy Drop-In Parity)

All paths below are relative to the active webroot (`redesign/` during coexistence; repo root after cutover). Every `data/*.json` file is excluded from FTP deployments in `.github/workflows/deploy.yml` so production data is never overwritten.

### A. Data Files in `data/`

| File | Mode | HTTP Access (`data/.htaccess`) | JSON Structure & Field Types |
| :--- | :--- | :--- | :--- |
| `data/events.json` | `0644` | Public | Array of `{ "id": int, "title": string, "date": "YYYY-MM-DD", "time": string, "people": int, "maxPeople": int, "location": string, "image": string, "description": string, "contact": string }` |
| `data/signups.json` | `0600` | **Denied** | Object keyed by `(string)$eventId` -> Array of `{ "name": string, "email": string, "phone": string, "timestamp": "YYYY-MM-DD HH:i:s" }` |
| `data/locations.json` | `0644` | **Denied** *(served via `get-locations.php`)* | Array of `{ "name": string, "address": string, "phone": string }` *(optional `"lat"?: float, "lng"?: float`)*. Sorted case-insensitively by `name` on admin save. |
| `data/geo_cache.json` | `0644` | **Denied** *(merged by `get-locations.php`)* | Object keyed by exact raw `address` string -> `{ "lat": float \| null, "lng": float \| null, "timestamp": int }` |
| `data/board.json` | `0644` | Public | Array of `{ "name": string, "image": string, "tag": "current" \| "previous" }` |
| `data/partners.json` | `0644` | Public | Array of `string` (`["Partner Name", ...]`) |
| `data/testimonials.json` | `0644` | Public | Array of `{ "text": string, "author": string, "organization": string }` |
| `data/admin-auth.json` | `0600` | **Denied** | `{ "user": string, "password_hash": string, "updated_at": string (ISO 8601 `date('c')`) }` |
| `data/site-config.json` | `0600` | **Denied** | `{ "email": string, "phone": string }` |
| `data/write.lock` | `0644` | **Denied** | Persistent advisory lockfile for `erc_with_storage_lock()` |
| `data/email_errors.log` | `0600` | **Denied** | Timestamped (`America/Chicago`) single-line SMTP/PHP error log |

### B. `.env` Keys & Runtime Config Resolution

`.env` lives in the server webroot on GoDaddy cPanel (never committed to Git). Parser rules (`erc_load_env()`): skips blank lines and `#` comments, strips optional leading `export `, splits on first `=`, trims outer whitespace and matching `"` or `'` quotes, caches statically per request.

```ini
# SMTP Delivery (GoDaddy cPanel defaults shown)
SMTP_HOST=p3plzcpnl507374.prod.phx3.secureserver.net
SMTP_PORT=465
SMTP_USER=noreply@2e2erc.org
SMTP_PASS=

# Inbound Admin Notification & Public Contact Fallbacks
ADMIN_EMAIL=2e2erc1854@gmail.com
GLOBAL_EMAIL=2e2erc1854@gmail.com
GLOBAL_PHONE=(817) 710-5403

# Admin Portal Credentials (see 4-tier resolution below)
ADMIN_USER=admin
ADMIN_PASS=
ADMIN_PASS_HASH=
```

**Runtime Precedence for Public Contact & Notification Email:**
- `$GLOBAL_EMAIL`: `data/site-config.json['email']` (if non-empty) -> `.env['GLOBAL_EMAIL']` (if non-empty) -> `'2e2erc1854@gmail.com'`.
- `$GLOBAL_PHONE`: `data/site-config.json['phone']` (if non-empty) -> `.env['GLOBAL_PHONE']` (if non-empty) -> `'(817) 710-5403'`.
- `admin_email` (recipient for inbound contact & signup alert emails): `data/site-config.json['email']` -> `.env['ADMIN_EMAIL']` -> `.env['GLOBAL_EMAIL']` -> `'2e2erc1854@gmail.com'`. *(Note: legacy `mail-utils.php` used `$GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com'`; respecting `ADMIN_EMAIL` as an explicit override or fallback maintains full backward compatibility with both legacy `.env` and extended `.env` configurations.)*
- `America/Chicago` must be set as default PHP timezone across mail, signup, and admin handlers.

### C. Exact 4-Tier Admin Credential Resolution Order

Do **NOT** modify or omit any tier — existing GoDaddy admins may currently authenticate using any of the 4 tiers:

1. **Tier 1 (`data/admin-auth.json`):** If `password_hash` exists and is non-empty string -> verify `$inputUser` against `stored['user'] ?? 'admin'` (`hash_equals`) and `$inputPass` via `password_verify($inputPass, $stored['password_hash'])`.
2. **Tier 2 (`.env['ADMIN_PASS_HASH']`):** Username from `.env['ADMIN_USER'] ?? 'admin'`; password verified via `password_verify($inputPass, $env['ADMIN_PASS_HASH'])`.
3. **Tier 3 (`.env['ADMIN_PASS']`):** Username from `.env['ADMIN_USER'] ?? 'admin'`; password verified via `hash_equals($env['ADMIN_PASS'], $inputPass)`.
4. **Tier 4 (Legacy PBKDF2-HMAC-SHA256 Fallback):** Prevents lockout when neither `data/admin-auth.json` nor `.env` password variables are set:
   - Username: `.env['ADMIN_USER'] ?? 'admin'`
   - Algorithm: `bin2hex(hash_pbkdf2('sha256', $inputPass, '2e2erc-legacy-admin-v1', 210000, 32, true))`
   - Expected Digest (exact lowercase hex): `'06a860745dbe420b317f6e606d01d3c4b62fbf762ee36ec74c948389515de109'`
   - Constant-time compare with `hash_equals()`.

### D. Public Backend Endpoint Contracts

Preserve exact URL routes and payload contracts so existing bookmarks, automated tests, and external clients work seamlessly:

1. **`GET /get-config.php`** (`Cache-Control: no-cache`, `application/json; charset=UTF-8`):
   - Returns `{ "email": string, "phone": string, "phoneLink": string }` where `phoneLink` normalizes digits to E.164 `+1...` (fallback `+18177105403`).
2. **`GET /get-gallery-images.php`** (`application/json; charset=UTF-8`):
   - Runs `erc_scan_gallery_images()` scanning `gallery/` for `['jpg','jpeg','png','gif','webp']`, **skipping** any `<stem>.webp` sidecar whenever a non-WebP `<stem>.{jpg,jpeg,png,gif}` file exists in the same directory, sorted naturally (`natcasesort`) as `["gallery/<filename>", ...]`.
3. **`GET /get-locations.php`** (`Cache-Control: no-cache`, `application/json; charset=UTF-8`):
   - Loads `data/locations.json` and merges `data/geo_cache.json[$address]` when numeric `lat`/`lng` are present, casting `(float)$lat` / `(float)$lng`, returning JSON array `array_values($locations)` (entries without cached coordinates still appear without `lat`/`lng`).
4. **`POST /register-volunteer.php`** (`application/json; charset=UTF-8`, `set_time_limit(60)`):
   - Non-POST -> HTTP `405` `{ success: false, message }`.
   - JSON body `{ eventId: int, name: string, email: string, phone?: string, website_url?: string }`. Invalid JSON -> HTTP `400`.
   - Non-empty `website_url` honeypot -> returns `{ success: true, message: "Registration successful!" }` without touching disk or email.
   - Rate limit `'volunteer_signup'`: `10` requests per `900` seconds per IP -> HTTP `429`.
   - Validation: `eventId` int > 0, `name` non-empty `<= 120` chars (`strip_tags`), `email` `<= 254` chars valid `FILTER_VALIDATE_EMAIL`, `phone` `<= 40` chars (`strip_tags`).
   - Transactional lock (`erc_with_storage_lock`):
     - Reject if event missing, capacity full (`$maxPeople > 0 && max(count($signups[$key]), (int)$event['people']) >= $maxPeople`), or case-insensitive duplicate email in that event (`You are already registered for this event`).
     - Append `{ name, email, phone, timestamp: date('Y-m-d H:i:s') }`, set `$event['people'] = count($signups[$key])`.
     - Save `signups.json` (`0600`) then `events.json` (`0644`); roll back `signups.json` on failure.
   - Email delivery (non-blocking to saved DB record): sends confirmation email to volunteer + notification email (`Reply-To` = volunteer) to admin via PHPMailer; logs SMTP errors to `data/email_errors.log`.
   - Returns `{ success: true, message: "Registration successful!", newCount: int }`.
5. **`POST /send-email.php`**:
   - Accepts form POST (`name`, `email`, `phone`, `subject`, `message`, hidden `website_url`).
   - Can detect JSON/fetch vs classic browser form POST if enhanced, **must** keep standard form POST + PRG redirect semantics intact when JS is disabled (or cleanly map redirect target to contact route `?status=success` / `?status=error&reason=...`).
   - Honeypot `website_url` non-empty -> silently drops and redirects/returns success.
   - Rate limit `'contact_form'`: `5` requests per `900` seconds per IP -> error `rate_limited`.
   - Bounds: `name` (`1..120`), `email` (`1..254`, `FILTER_VALIDATE_EMAIL`), `phone` (`0..40`), `subject` (`0..100`, default `'General Inquiry'`), `message` (`1..5000`) -> error `invalid_input`.
   - Sanitizes headers against CR/LF/NUL, sends exclusively to configured admin recipient with `Reply-To` = sender (no open CC relay).

### E. All 13 Admin Actions & Admin Security Contract (`admin-view.php`)

**Authentication, Session & Brute-Force Protection:**
- Extract Basic Auth from `$_SERVER['PHP_AUTH_USER']` / `$_SERVER['PHP_AUTH_PW']` OR `$_SERVER['HTTP_AUTHORIZATION']` / `$_SERVER['REDIRECT_HTTP_AUTHORIZATION']` (enabled via `.htaccess` `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`).
- Check brute-force bucket `'admin_login_fail'`: max **`8`** failed attempts per **`900`** seconds per IP (`recordHit = false` pre-check -> HTTP `429` + `Retry-After: 900`; failed attempt with header records hit + `WWW-Authenticate: Basic realm="ERC Admin Area"`, HTTP `401`; success calls `erc_clear_rate_limit('admin_login_fail')`).
- PHP Session cookie params: `lifetime => 0`, `path => '/'`, `secure => $isHttps`, `httponly => true`, `samesite => 'Strict'`.
- Response headers: `Cache-Control: no-store, private`, `X-Robots-Tag: noindex, nofollow`.
- All POST mutations require `$_SESSION['csrf_token']` (`bin2hex(random_bytes(32))`, `hash_equals`), redirect with **HTTP `303` PRG** + `$_SESSION['flash_message']` / `$_SESSION['flash_error']`. Detect POST truncation when `empty($_POST) && CONTENT_LENGTH > 0`.

**Complete List of 13 Supported `action` Values:**

| # | `action` | Key POST Inputs / Files | Mutated Files & Behavior |
| :-: | :--- | :--- | :--- |
| 1 | `save_event` | `id?`, `title`, `date`, `time`, `maxPeople`, `location`, `description`, `contact`, `existing_image`, file `image` | Creates or updates event in `data/events.json` (`0644`); preserves `people` from `signups.json`; validates & optimizes event image (`max 1600×1600`, `10MB` cap) into `images/events/`; deletes old replaced `images/events/*` + `.webp` if unreferenced. |
| 2 | `delete_event` | `id` | Removes event from `data/events.json`, cascades delete in `data/signups.json[(string)$id]`, deletes orphaned `images/events/*` + `.webp`. |
| 3 | `save_location` | `index` (`-1` for new), `name`, `address`, `phone` | Adds/updates `data/locations.json` (`0644`), case-insensitively sorts by `name`, geocodes missing address via OpenStreetMap Nominatim -> US Census fallback and stores in `data/geo_cache.json`. |
| 4 | `delete_location` | `index` | Removes item at `$index` from `data/locations.json` and re-indexes array. |
| 5 | `save_board` | `names[]`, `tags[]` (`current`\|`previous`), `images[]`, files `board_files[]` | Replaces `data/board.json` (`0644`); validates & optimizes portrait uploads (`max 400×400`, `10MB` cap) into `images/board/`; deletes removed non-placeholder portraits + `.webp`. |
| 6 | `delete_signup` | `event_id`, `index` | Removes attendee from `data/signups.json` (`0600`), unsets key if empty, updates `people` count in `data/events.json`. |
| 7 | `upload_gallery` | `gallery_photos[]` (multi-file) | Validates & optimizes images (`max 1600×1600`, `10MB` cap) into `gallery/` with `<stem>.webp` sidecar; unlinks `data/gallery-cache.json` if present. |
| 8 | `delete_gallery` | `photo` (relative path) | Confines `realpath` strictly inside `gallery/`, deletes target image + companion `.webp`; unlinks `data/gallery-cache.json` if present. |
| 9 | `save_partners` | `partners` (newline-separated string) | Trims non-empty lines and writes `string[]` to `data/partners.json` (`0644`). |
| 10 | `save_testimonials` | `texts[]`, `authors[]`, `orgs[]` | Writes array of `{text, author, organization}` (for non-empty `text`) to `data/testimonials.json` (`0644`). |
| 11 | `save_config` | `global_email` (valid email), `global_phone` (`<= 40` chars) | Saves `{email, phone}` persistently to `data/site-config.json` (`0600`). |
| 12 | `change_admin_password` | `admin_username` (`1..64`), `current_password`, `new_password` (`>= 8`), `confirm_password` | Verifies current password against active tier (`$authConfig`), hashes via `password_hash(..., PASSWORD_DEFAULT)`, writes `data/admin-auth.json` (`0600`). |
| 13 | `upload_form` | `target_filename`, file `new_form` | Enforces exact `basename === target_filename`, `.pdf` extension, `<= 20MB`, `finfo` MIME `application/pdf`, 5-byte magic header `%PDF-`; replaces `files/<cleanFilename>` (`0644`). |

### F. Upload Validation, GD Optimization & Apache Security Controls

- **Image Validation (`isValidImageUpload`):** `UPLOAD_ERR_OK`, `finfo(FILEINFO_MIME_TYPE)` in `['image/jpeg', 'image/png', 'image/gif', 'image/webp']`, lowercase extension in `['jpg', 'jpeg', 'png', 'gif', 'webp']`, `@getimagesize()` truthy, `<= 10MB`.
- **GD Optimization (`erc_optimize_uploaded_image`):**
  - Pixel safety guard: skip GD processing if `$width * $height > 36000000` (prevents cPanel memory exhaustion).
  - JPEG EXIF `Orientation` auto-rotation: `3` -> `180°`, `6` -> `-90°`, `8` -> `+90°` (updating dimensions).
  - Downscale proportionally inside `$maxWidth × $maxHeight` (`1600×1600` for gallery/events; `400×400` for board), preserving PNG/WebP transparency (`imagealphablending(false)`, `imagesavealpha(true)`).
  - Re-encode scaled image: JPEG quality `84`, PNG compression `7`, WebP quality `84`.
  - Generate companion `.webp` (`imagewebp` quality `82`, converting palette PNGs via `imagepalettetotruecolor()`) and `@chmod(..., 0644)`.
- **Apache `.htaccess` Controls:**
  - `Options -Indexes`
  - Force HTTPS redirect (`RewriteCond %{HTTPS} off` in production only — omit or gate on non-localhost host if needed locally)
  - Pass `Authorization` header (`E=HTTP_AUTHORIZATION:%{HTTP:Authorization}`)
  - Content-negotiated `.webp` rewrite (`HTTP_ACCEPT image/webp` + `-f` `%1.webp` -> serve `image/webp` with `Header append Vary Accept env=REDIRECT_accept`)
  - Deny direct access (`Require all denied` / `Deny from all`) to dotfiles (`.env`, `.git`), `*.log`, `*.lock`, `*.tmp.*`, `*.md`, internal PHP helpers (`config.php`, `mail-utils.php`, `includes/*.php`)
  - Security headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), geolocation=(), microphone=()`, `Cross-Origin-Opener-Policy: same-origin-allow-popups`
  - `data/.htaccess`: Deny everything in `data/` by default; grant direct GET only for `^(board|events|partners|testimonials)\.json$`.

### G. Backward URL Compatibility (Mandatory Upon Cutover)

Bookmarks, search-engine links, printed QR/material references, and legacy redirects MUST continue functioning cleanly after cutover:
- **Legacy SPA Hash Fragments (`/` with `#home`, `#about`, `#services`, `#get-involved`, `#locations`, `#contact`):** The landing entrypoint (`/` / `index.php` or client-side boot module) must inspect `window.location.hash` on initial load and redirect immediately to the corresponding new extensionless route (e.g., `/#locations` -> locations page, `/#get-involved` -> volunteer/donate page, `/#services` -> services/mission requests page, `/#about` -> about/board page, `/#contact` -> contact page, `/#home` -> `/`).
- **`/index.html` and `/admin-view.php`:** Keep `/admin-view.php` (plus optional clean `/admin` alias) operational at that exact path, and preserve `/get-config.php`, `/get-locations.php`, `/get-gallery-images.php`, `/register-volunteer.php`, and `/send-email.php`.

---

## 3. Dual-Port Coexistence Architecture (`:8765` vs `:8770`)

During development and QA on `feat/ground-up-redesign`, both sites run simultaneously without interfering with each other:

| Aspect | Legacy Site (Untouched) | Redesign Site (`redesign/`) |
| :--- | :--- | :--- |
| **Document Root** | Repo root `/` | `redesign/` |
| **Exposed Browser Port** | **`8765`** (proxied to PHP `8.4` on `127.0.0.1:8766`) | **`8770`** (proxied to PHP `8.4` on `127.0.0.1:8771`) |
| **Shared Read-Only Media** | Served directly from `/e2e2rc_LOGO.png`, `/files/*`, `/gallery/*`, `/images/*` | **Do NOT copy binaries into `redesign/` in Git.** Local router / resolver on `:8770` falls back to repo-root `/e2e2rc_LOGO.png`, `/files/**`, `/gallery/**`, `/images/**` whenever absent inside `redesign/` (and `erc_scan_gallery_images()` resolves shared `gallery/` gracefully when `redesign/gallery/` is absent). |
| **Runtime JSON Data Store** | `/data/*.json` (untouched) | Seeded copy under `redesign/data/*.json` + `redesign/data/.htaccess` so `:8770` form signups and admin edits **never** mutate `:8765` |
| **CI/FTP Exclusion Guard** | Active on `main` | `.github/workflows/deploy.yml` excludes `redesign/**`, `**/.agents/**`, and `skills-lock.json` until cutover |

### Promotion / Cutover Checklist (Later Phase)
When ready to promote `redesign/` to production root:
1. Move/promote `redesign/` files into repo root, preserving `.htaccess`, `includes/`, `vendor/`, and updated `config.php` / `admin-view.php` / `mail-utils.php` / endpoints.
2. Remove coexistence fallback to parent-dir media (`..`) and remove `redesign/**` FTP exclusion only after promotion, while continuing to exclude live mutable server state (`data/*.json`, `data/*.lock`, `data/*.log`, `files/**`, `gallery/**`, `images/**`, `.env*`).
3. Keep `admin-view.php`, 4-tier credential resolution, JSON file schemas, `.env` keys, and legacy `#hash` redirects 100% intact.
