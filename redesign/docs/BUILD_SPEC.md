# Lions District 2-E2 Eyeglass Recycling Center — Ground-Up Redesign Build Specification

> **Author:** `goldfish-architect` · **Target Branch:** `feat/ground-up-redesign`  
> **Authoritative Companion Inputs:** [`ORG_FACTS.md`](./ORG_FACTS.md) (domain truth) and [`HOSTING_CONTRACT.md`](./HOSTING_CONTRACT.md) (GoDaddy `public_html` stack, JSON schemas, 13 admin actions, security & dual-port coexistence).  
> **Scope Rule:** All 5 parallel `workspace: "share"` builders MUST follow every contract, token, route, media rule, and file-ownership boundary below so all branches merge cleanly with zero conflicts.

---

## 1. Aesthetic & Editorial Direction ("Optical Workshop & Humanitarian Dispatch")

### Visual Identity & Tone
- **Domain Personality:** A 100% volunteer North Texas Lions service center (`5621 Bunker Blvd, Watauga, TX 76148`) combining **warm civic trustworthiness** with **optical workshop precision** (`0.25`-diopter sorting, lensometry calibration, `< $0.10` per recycled pair reaching `40+` countries).
- **Accessibility-First Audience:** Explicitly designed for older Lions Club volunteers, donors with reading glasses or low vision, and humanitarian medical clinic coordinators on desktop, tablet, and mobile.
- **Anti-Generic Pledge:**
  - **NO** purple/pink AI SaaS gradients, **NO** neon glassmorphic blobs, **NO** cloned 6-tab monolithic SPA shells.
  - **NO** structural Unicode emojis (`👓`, `📍`, `☀️`, `🌙`) anywhere in UI chrome — use **Lucide `v1.73.0`** SVG icons exclusively via `erc_icon()` (PHP) and `ercIconElement()` (JS).
  - **NO** fabricated policies: `ORG_FACTS.md` confirms **no minimum volunteer age**, **no chaperone ratio**, and **no closed-toe shoe / dress-code rule** exists in organizational documents — never invent them.
  - **Authentic Media Only:** Every photo comes from the verified root `/e2e2rc_LOGO.png`, `/images/*`, and `/gallery/*` inventory (served via coexistence fallback in `router.php` on `:8770`).

### Self-Hosted Typography (`redesign/fonts/fonts.css` — Already Vendored)
Zero external Google Fonts requests; zero FOUT/CLS via WOFF2 preloads + `font-display: swap`:
- **`--font-display`:** `'Newsreader Variable', Georgia, 'Times New Roman', serif` (`opsz` + `wght` `200..800`, normal & italic). Apply `font-optical-sizing: auto; text-wrap: balance;` to `h1`–`h3`.
- **`--font-sans`:** `'Atkinson Hyperlegible Next Variable', system-ui, -apple-system, 'Segoe UI', sans-serif` (`wght` `200..800`, normal & italic). Designed by the Braille Institute specifically for **low-vision character distinguishability** (`1`/`l`/`I`, `0`/`O`). Minimum `16px` (`1rem`) body text, `1.6` line-height, `62ch–68ch` prose measure (`text-wrap: pretty` on body paragraphs).
- **`--font-mono`:** `'Atkinson Hyperlegible Mono Variable', ui-monospace, SFMono-Regular, Menlo, monospace` (`wght` `200..800`). Pair with `font-variant-numeric: tabular-nums;` for Lion-Year shipping tables (`2013–2025`), diopter grids (`+0.25` to `+5.00+`), bin counts (`214`), remaining seats, and phone/hours alignment.

---

## 2. Baseline 2026 Dual-Theme Token System (OKLCH + `light-dark()`)

Both **Light Mode** ("Warm Archival Paper & Optical Cobalt") and **Dark Mode** ("Deep Observatory Navy & Luminous Sky") are first-class citizens meeting **WCAG 2.2 AA/AAA** (`>= 4.5:1` body/caption text, `>= 3:1` UI borders, inputs, icons, and focus rings) and **Windows Forced Colors Mode** (`@media (forced-colors: active)`).

### CSS Cascade & Theme Toggle Architecture (Required by `goldfish-shell-ui`)
1. **`head.php` (FOUC-free scheme initialization):**
   ```html
   <meta name="color-scheme" content="light dark">
   <script>
     try {
       var s = localStorage.getItem('erc-color-scheme');
       if (s === 'light' || s === 'dark') {
         document.querySelector('meta[name="color-scheme"]').content = s;
       }
     } catch (e) {}
   </script>
   ```
2. **`tokens.css` (`:root` scheme mapping — never hardcode `light` or `dark` as static default):**
   ```css
   :root {
     color-scheme: light dark;
   }
   :root:has(> head > meta[name="color-scheme"][content="dark"]) {
     color-scheme: dark;
   }
   :root:has(> head > meta[name="color-scheme"][content="light"]) {
     color-scheme: light;
   }
   ```
   *(Also include `@media (prefers-color-scheme: dark)` custom-property fallback block inside `@supports not (color: light-dark(white, black))` just in case older clients visit, plus explicit `:root:has(> head > meta[name="color-scheme"][content="dark"])` override!)*
3. **Toggle Semantics (`theme.js`):**
   - Header utility hosts a compact accessible `<button type="button" id="themeToggle">` (plus optional explicit System/Light/Dark option or two-state comfort toggle that removes `localStorage['erc-color-scheme']` and restores `content="light dark"` whenever the target matches current OS `prefers-color-scheme` — never clearing `localStorage` on `matchMedia` change!).
   - Sync `<meta name="theme-color">` and dispatch `window.dispatchEvent(new CustomEvent('erc:themechange', { detail: { resolved: 'light' | 'dark', preference: 'system' | 'light' | 'dark' } }))` so MapLibre (`/locations`) swaps OpenFreeMap styles live.

### Locked OKLCH Semantic Token Contract (`css/tokens.css`)
All builders MUST style exclusively with these CSS variables (never raw hex values in page CSS):

| Token | Light Value (`OKLCH`) | Dark Value (`OKLCH`) | Purpose & Contrast Guarantee |
| :--- | :--- | :--- | :--- |
| `--bg-canvas` | `oklch(98.4% 0.006 85)` | `oklch(15.5% 0.022 254)` | Page canvas (warm paper / observatory navy) |
| `--bg-surface` | `oklch(99.7% 0.002 90)` | `oklch(20.5% 0.026 254)` | Elevated card / modal / form container |
| `--bg-subtle` | `oklch(95.4% 0.011 85)` | `oklch(24.5% 0.028 254)` | Alternate band / zebra table / secondary well |
| `--bg-tint` | `oklch(94.2% 0.024 246)` | `oklch(25.5% 0.048 250)` | Optical cobalt highlight card / notice surface |
| `--ink-primary` | `oklch(20.5% 0.024 252)` | `oklch(95.5% 0.008 90)` | Headings & primary body (`~15:1` / `~14.5:1`) |
| `--ink-secondary` | `oklch(36.0% 0.026 250)` | `oklch(83.0% 0.015 248)` | Lead paragraphs, table descriptions (`>= 8:1`) |
| `--ink-muted` | `oklch(45.0% 0.022 250)` | `oklch(73.0% 0.018 248)` | Hints, timestamps, metadata (`>= 5.5:1`, always > `4.5:1`) |
| `--border-subtle` | `oklch(86.5% 0.014 248)` | `oklch(34.5% 0.024 252)` | Card outlines, section dividers, table rules |
| `--border-strong` | `oklch(60.0% 0.025 250)` | `oklch(58.0% 0.028 250)` | Input & control boundaries (`>= 3:1` WCAG 1.4.11) |
| `--brand-primary` | `oklch(38.0% 0.145 256)` | `oklch(75.0% 0.125 245)` | Primary interactive cobalt (`light`) / luminous sky (`dark`) |
| `--brand-primary-hover` | `oklch(32.0% 0.145 256)` | `oklch(81.0% 0.120 245)` | Hover / active shift |
| `--on-brand-primary` | `oklch(99.2% 0.004 90)` | `oklch(15.0% 0.030 254)` | Text/icon inside `--brand-primary` fills (`>= 7:1`) |
| `--brand-gold-fill` | `oklch(82.0% 0.145 86)` | `oklch(79.0% 0.140 86)` | Lions heritage gold highlight / pill fill |
| `--on-brand-gold` | `oklch(20.0% 0.035 70)` | `oklch(17.5% 0.035 70)` | Dark ink on gold fills (`> 9:1`) — NEVER white text on gold |
| `--brand-gold-ink` | `oklch(43.5% 0.120 72)` | `oklch(82.5% 0.135 84)` | Gold/brass text accent on canvas (`>= 4.5:1`) |
| `--brand-emerald` | `oklch(43.0% 0.125 162)` | `oklch(73.0% 0.130 162)` | Sustainability / recycling icon & status ink |
| `--on-brand-emerald` | `oklch(99.0% 0.005 160)` | `oklch(15.5% 0.030 162)` | Ink on solid emerald button/pill |
| `--status-success-bg` | `oklch(94.5% 0.035 152)` | `oklch(24.0% 0.045 152)` | Available slots / confirmation banner fill |
| `--status-success-ink` | `oklch(36.5% 0.125 152)` | `oklch(81.0% 0.130 152)` | Confirmation text & icon (`>= 4.5:1`) |
| `--status-warn-bg` | `oklch(95.5% 0.045 84)` | `oklch(25.0% 0.045 75)` | Advisory notice fill (e.g., 30-day lead time, un-geocoded bin) |
| `--status-warn-ink` | `oklch(41.5% 0.130 70)` | `oklch(83.5% 0.135 80)` | Warning text & icon (`>= 4.5:1`) |
| `--status-danger-bg` | `oklch(95.0% 0.032 25)` | `oklch(25.0% 0.055 25)` | Error / Excluded item banner fill |
| `--status-danger-ink` | `oklch(44.0% 0.185 25)` | `oklch(79.0% 0.155 25)` | Error / excluded text & icon (`>= 4.5:1`) |
| `--focus-ring` | `oklch(42.0% 0.175 256)` | `oklch(82.0% 0.145 86)` | `3px solid` `:focus-visible` outline (`2px` offset) |
| `--shadow-sm` / `--shadow-md` | subtle warm navy alpha | deep surface + top hairline | Elevation parity across Light & Dark |

### Baseline 2026 Platform Patterns (`css/base.css` + `js/shell.js` + `js/forms.js`)
1. **CSS Layers:** `@layer reset, tokens, base, layout, components, utilities;` inside `base.css` / `tokens.css` so page stylesheets (`@layer pages`) layer predictably without specificity wars.
2. **Cross-Document View Transitions:**
   ```css
   @view-transition { navigation: auto; }
   @media (prefers-reduced-motion: reduce) {
     @view-transition { navigation: none; }
   }
   ```
   Paired with `<link rel="expect" href="#main-content" blocking="render">` in `head.php`.
3. **Native `<dialog closedby="any">`:**
   - Never add `role="dialog"`, `aria-modal`, or manual focus traps on native modal `<dialog>`. Always name with `aria-labelledby` and include a visible Close `<button>`.
   - Prevent background page scroll chaining (never set `overflow: hidden` on `html`/`body`):
     ```css
     dialog:modal { overscroll-behavior: contain; }
     dialog::backdrop { overflow: hidden; overscroll-behavior: contain; }
     ```
   - Animate open/close via `@starting-style`, `transition-property: opacity, transform, display, overlay; transition-behavior: allow-discrete;` gated inside `@media (prefers-reduced-motion: no-preference)`.
   - `shell.js` provides a shared Safari light-dismiss fallback (`!('closedBy' in HTMLDialogElement.prototype)` using `pointerdown` + `pointerup` outside `getBoundingClientRect()`) and `closeTopLayer(dialog)` helper for browsers lacking `overlay` / discrete `display` transitions.
4. **Native Popovers & Anchor Positioning (`[popover]`):**
   - Use `<button popovertarget="...">` and `<div popover="auto">` for lightweight menus/disclosures (do **NOT** put `role="menu"` unless full ARIA arrow-key contract is implemented).
   - Gate `inset: auto; margin: 0;` + `position-area` strictly inside `@supports (position-area: block-end)` so browsers without Anchor Positioning keep centered popover placement rather than stranding at `(0,0)`.
5. **Container Queries (`@container`):**
   - Wrap modular event cards, drop-off location cards, and impact metric grids in `container-type: inline-size;` so cards adapt seamlessly between narrow drawers/columns and wide grids.
6. **Accessible Form Validation (`js/forms.js` + `css/base.css`):**
   - Place format hints **above** inputs (`aria-describedby="...-hint"`) so mobile keyboards and autofill dropdowns never cover them.
   - Start fields at `aria-invalid="false"`. Sync `:user-invalid` on `blur`, `input`, `change`, `invalid`, and `reset` events (`data-error="field-error-id"` added to `aria-describedby` **only while invalid**), plus a single `.visually-hidden[aria-live="polite"]` announcer (`500ms` delay). Style `:is(:user-invalid, .user-invalid-fallback)` and `:user-valid` alongside visible error icon + text.

---

## 3. Clean-URL Information Architecture & Legacy Redirects

### Primary Navigation & Page Map
Keep top navigation focused and calm: **5 primary nav links + 1 Contact CTA + Theme Toggle** inside a sticky header (`scroll-padding-top` set on `html` so anchor jumps & keyboard focus never hide under the sticky bar!).

| Clean Route | PHP Entrypoint | Builder Owner | Primary Purpose & Content Scope (`ORG_FACTS.md`) |
| :--- | :--- | :--- | :--- |
| **`/`** | `index.php` | `goldfish-story-impact` | **Home:** Editorial hero (`e2e2rc_LOGO.png` + Watauga team `our-story-group.png`), live Watauga Saturday `9 AM–3 PM` status + east-side walkway note, `21,696` `2024–2025` pairs / `214` bins / `40+` countries / `< $0.10` metrics, 4 visitor intent paths, 6-step recycling overview (using rotated lensometer fix), featured verbatim quote, quick center & P.O. Box summary. |
| **`/donate`** | `donate.php` | `goldfish-story-impact` | **Donate & Process:** Complete accepted items (Eyeglasses/sunglasses/broken frames, Hearing aids, Windows 11 laptops/tablets, Cell phones wiped), Explicitly NOT accepted (Desktops, damaged screens, non-functional keyboards, missing parts), 6-step refurbishment & `0.25`-diopter `50`-pair box breakdown, Bulk/business shipping, Sponsor collection boxes (Westlake Ace Hardware, MarketStreet), Monetary check donations (`P.O. Box 1854, Keller, TX 76244`). |
| **`/locations`** | `locations.php` | `goldfish-dropoff-map` | **Drop-Off Map & Directory:** Watauga HQ spotlight + east-side walkway card, MapLibre GL `v6.18.0` Supercluster interactive map (OpenFreeMap Light `positron` / Dark `dark` synced to theme), Fuse.js `v7.5.0` fuzzy search + 34-city filter + optional same-origin Near Me (`locate-fixed`) + A–Z directory rendering all `214` locations cleanly (including the `10` un-geocoded addresses with clear "Directory Listing — Call to Confirm" badge). |
| **`/volunteer`** | `volunteer.php` | `goldfish-volunteer-hub` | **Volunteer & Events:** Individual Saturday walk-in info (`9 AM–3 PM`, no prior experience, no fabricated rules), Group Volunteer Days (up to `20`, `9–12` / `12–3` shifts, `Group-Volunteer-Form.pdf`), Interactive Monthly Calendar + `All Upcoming Events` (`events.json`, live seats remaining), native `<dialog closedby="any">` signup (`POST /register-volunteer.php`), client-side `.ics` & Google Calendar export (`America/Chicago`), Community Photo Gallery (`/get-gallery-images.php` + native `<dialog>` lightbox). |
| **`/missions`** | `missions.php` | `goldfish-story-impact` | **Missions & Impact:** WHO stats (`2.2B` / `1B` / `157M`), `< $0.10`/pair cost, full 12-row `2013–2025` Lion-Year Shipping Table (`ERC-Brochure.pdf`), Global Reach (`4` regions / `37` countries + `Fiji` = `40+`), Medical Mission Request Guide (`Mission-Request-Form.pdf`, `30`-day lead time, Tax ID, refractive verification, `50`-pair diopter brackets, NOT FOR RESALE, mission pays shipping), all 4 verbatim testimonials (`testimonials.json`). |
| **`/about`** | `about.php` | `goldfish-story-impact` | **About, Board & Lions:** History since 2013, `501(c)(3)` (`2014`), Legal identity (`District 2-E2 Lions Eyeglass Recycling Center Foundation`), Fiscal Year July 1–June 30, District 2-E2 & Texas Lions context, 5 Core Values, 4 Human Impact Groups, Bylaws governance (`ERC-Bylaws.pdf`), Current (`12`) & Previous (`4`) Board portraits (`board.json`), all `24` Partners & Friends (`partners.json`), Helen Keller Award (`$250`/`$500`/`$1,000` + service hours) & ERC Life Membership forms. |
| **`/contact`** | `contact.php` | `goldfish-shell-ui` | **Contact, Forms & FAQs:** Watauga address + east-side walkway access + free parking, dynamic `$GLOBAL_PHONE` & `$GLOBAL_EMAIL`, P.O. Box `1854`, 24h response turnaround, categorized contact form (`8` subjects -> `POST /send-email.php`, inline status alert for `?status=success|error`), Downloadable Forms Center (all `6` PDFs in `files/`), Verified Factual FAQs (`<details>`/`<summary>`). |
| **`/admin-view.php`** (`/admin`) | `admin-view.php` | `goldfish-php-admin` | **Admin Dashboard:** Auth-protected (`4`-tier credential check, `8/900s` brute-force protection, CSRF, `303` PRG) managing all `13` operational actions. |

### Legacy URL & `#hash` Compatibility Table (Mandatory)
1. **Apache `.htaccess` & `router.php`:**
   - `/index.html` -> HTTP `301` redirect to `/` (preserves `#hash` fragments in browsers!).
   - Extensionless `/donate`, `/locations`, `/volunteer`, `/missions`, `/about`, `/contact`, `/admin` internally rewrite/dispatch to their `.php` files.
   - Exact API paths preserved at root: `/get-config.php`, `/get-locations.php`, `/get-gallery-images.php`, `/register-volunteer.php`, `/send-email.php`, `/admin-view.php`.
2. **Client-Side Legacy SPA Hash Router (in `head.php` inline bootstrap before first paint on `/`):**
   - `#home` -> replaceState to `/`
   - `#about` -> `location.replace('/about')`
   - `#services` -> `location.replace('/donate')`
   - `#get-involved` -> `location.replace('/volunteer')`
   - `#locations` -> `location.replace('/locations')`
   - `#contact` -> `location.replace('/contact')`

---

## 4. Shared PHP & Media Contracts

### A. `redesign/config.php` Contract (Owned by `goldfish-php-admin`)
All public pages start with `require_once __DIR__ . '/config.php';`. `config.php` MUST:
- Enforce `date_default_timezone_set('America/Chicago');`
- Define path constants:
  - `ERC_ROOT_DIR` (`__DIR__`)
  - `ERC_DATA_DIR` (`__DIR__ . '/data'`)
  - `ERC_ASSET_BASE_DIR` (`is_dir(__DIR__ . '/images') ? __DIR__ : dirname(__DIR__)` — enables coexistence reads without copying binaries into `redesign/`)
- Load `.env` + `data/site-config.json` and expose globals:
  - `$GLOBAL_EMAIL` (string, fallback `'2e2erc1854@gmail.com'`)
  - `$GLOBAL_PHONE` (string, fallback `'(817) 710-5403'`)
  - `$GLOBAL_PHONE_LINK` (string E.164 `+1...`, fallback `'+18177105403'`)
  - `$ADMIN_EMAIL` (string, fallback `'2e2erc1854@gmail.com'`)
- Expose exact public helper functions:
  - `erc_load_env(): array`
  - `erc_get_site_config(): array` — returns `['email' => string, 'phone' => string, 'phoneLink' => string]`
  - `erc_format_phone_tel(string $phone): string`
  - `erc_with_storage_lock(callable $callback): mixed` — re-entrant `flock(LOCK_EX)` on `ERC_DATA_DIR . '/write.lock'`
  - `erc_load_json(string $path, mixed $default = []): mixed` — `flock(LOCK_SH)` read; resolves relative paths from `ERC_ROOT_DIR` if needed
  - `erc_save_json_atomic(string $path, mixed $data, int $chmod = 0644): bool` — atomic temp write + `@chmod` + `rename` (with Windows unlink+rename fallback)
  - `erc_escape(mixed $value): string` and `e(mixed $value): string` — `htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`
  - `erc_media_url(string $relativePath): string` — normalizes `'images/foo.jpg'` -> `'/images/foo.jpg'`
  - `erc_scan_gallery_images(): array` — returns sorted `['gallery/<filename>', ...]` skipping `.webp` sidecars when non-WebP `<stem>.{jpg,jpeg,png,gif}` exists (reading `ERC_ASSET_BASE_DIR . '/gallery'`)
  - `erc_client_ip(): string`
  - `erc_check_rate_limit(string $bucket, int $maxHits, int $windowSeconds, bool $recordHit = true): array`
  - `erc_clear_rate_limit(string $bucket): void`
  - `erc_send_security_headers(bool $isHtml = true): void`
- **Security Guard:** Direct HTTP access to `/config.php` or `/mail-utils.php` returns `403` in `.htaccess` and `router.php`.

### B. `redesign/includes/head.php`, `header.php`, `footer.php` Contract (Owned by `goldfish-shell-ui`)
Pages configure `$pageMeta` before requiring `head.php` and `header.php`:
```php
$pageMeta = [
    'slug'        => 'donate', // 'home' | 'donate' | 'locations' | 'volunteer' | 'missions' | 'about' | 'contact' | 'admin'
    'title'       => 'Donate Eyeglasses & Electronics | Lions District 2-E2 ERC',
    'description' => 'Accepted eyewear, hearing aids, cell phones, Windows 11 devices, bulk shipments, and monetary gifts.',
    'canonical'   => '/donate',
    'preload_img' => '/images/our-story-group.png', // optional high-priority LCP image
    'og_image'    => '/images/our-story-group.png', // optional
    'css'         => ['/css/pages/editorial.css'],  // optional extra stylesheets
    'js_modules'  => ['/js/pages/editorial.js'],    // optional extra <script type="module"> paths
    'json_ld'     => [],                            // optional extra JSON-LD associative array(s)
    'noindex'     => false,                         // optional
];
```
- `head.php` automatically loads `/fonts/fonts.css`, `/css/tokens.css`, `/css/base.css`, `/css/shell.css`, `$pageMeta['css']`, `<link rel="expect" href="#main-content" blocking="render">`, `<meta name="color-scheme" content="light dark">` + inline FOUC-prevention script + `#hash` redirect bootstrap (when `slug === 'home'`), base NGO JSON-LD schema (`5621 Bunker Blvd, Watauga, TX 76148`, `$GLOBAL_EMAIL`, `$GLOBAL_PHONE_LINK`), and `includes/icons.php`.
- `header.php` opens the skip-to-content link (`href="#main-content"`), sticky accessible navbar (`aria-current="page"` matching `$pageMeta['slug']`), mobile drawer (`<dialog closedby="any">` or `[popover]`), and theme toggle button. Pages then render `<main id="main-content" class="site-main" tabindex="-1">...</main>` and finish with `require __DIR__ . '/includes/footer.php';`.
- `footer.php` closes cleanly with center physical address + east-side walkway hint, P.O. Box `1854` Keller TX `76244`, dynamic `$GLOBAL_PHONE` & `$GLOBAL_EMAIL`, 501(c)(3) notice, quick links + Admin link, and `<script type="module">` tags for `/js/theme.js`, `/js/shell.js`, `/js/forms.js`, plus `$pageMeta['js_modules']`.

### C. Rotated Media Handling (`images/process-lensometers.jpg` vs `gallery/lensometers.jpg`)
Verified directly on disk:
1. **`images/process-lensometers.jpg`** (`1200×900` pixels on disk, **NO EXIF orientation tag**) is stored rotated **90° counter-clockwise** (ceiling appears on the left). It requires **`+90deg` clockwise rotation** in CSS (`css/base.css`), using a dedicated non-distorting frame class:
   ```css
   img { image-orientation: from-image; }

   .erc-rotated-frame {
     position: relative;
     overflow: hidden;
     aspect-ratio: 3 / 4;
   }
   .erc-rotated-frame--landscape {
     aspect-ratio: 4 / 3;
   }
   .erc-rotated-frame > img.is-ccw90-no-exif {
     position: absolute;
     top: 50%;
     left: 50%;
     max-width: none;
     width: 133.3333%;
     height: 75%;
     object-fit: cover;
     transform: translate(-50%, -50%) rotate(90deg);
   }
   .erc-rotated-frame--landscape > img.is-ccw90-no-exif {
     width: 133.3333%;
     height: 133.3333%;
   }
   ```
2. **`gallery/lensometers.jpg`** (`2880×2160` pixels on disk, **`EXIF Orientation = 6`**) is **automatically rotated upright by modern browsers** via default `image-orientation: from-image`. **DO NOT** apply `.is-ccw90-no-exif` or manual `rotate(90deg)` to `gallery/lensometers.jpg`, or it will appear upside down (`180°`).

### D. Locally Vendored Assets (`redesign/vendor/` & `redesign/includes/icons.php` — Already Built)
Do **not** modify `redesign/vendor/**`, `redesign/fonts/**`, or `redesign/includes/icons.php`. Import them as follows:
1. **Lucide Icons `v1.73.0`:**
   - **PHP:** `require_once __DIR__ . '/includes/icons.php'; echo erc_icon('glasses', ['class' => 'icon-md']);` (`82` icons preloaded; defaults to `aria-hidden="true"` unless `aria-label`/`aria-labelledby` provided).
   - **JS:** `import { ercIconElement } from '/vendor/lucide/icons.mjs';` (constructs safe `SVGSVGElement` via `createElementNS`, zero `innerHTML`).
2. **MapLibre GL JS `v6.18.0` (NOTE: Pure ESM + Module Worker!):**
   - Load `/vendor/maplibre-gl/maplibre-gl.css` via `$pageMeta['css']`.
   - Import in JS module: `import * as maplibregl from '/vendor/maplibre-gl/maplibre-gl.mjs';` (v6.18.0 is **ESM-only** — there is NO `window.maplibregl` global!).
   - `maplibre-gl-worker.mjs` sits alongside `maplibre-gl.mjs` and loads automatically via `import.meta.url`. `.htaccess` MUST declare `AddType text/javascript .mjs`, and CSP MUST permit `worker-src 'self' blob:`, `connect-src 'self' https://tiles.openfreemap.org`, `img-src 'self' data: blob: https://tiles.openfreemap.org`, and `style-src 'self' 'unsafe-inline' https://tiles.openfreemap.org`.
   - Vector basemaps: `https://tiles.openfreemap.org/styles/positron` (Light) and `https://tiles.openfreemap.org/styles/dark` (Dark).
3. **Fuse.js `v7.5.0` (ESM):**
   - `import Fuse from '/vendor/fuse/fuse.min.mjs';`
4. **PHPMailer `v7.2.0` (`mail-utils.php`):**
   - `require_once ERC_ROOT_DIR . '/vendor/phpmailer/Exception.php';`
   - `require_once ERC_ROOT_DIR . '/vendor/phpmailer/PHPMailer.php';`
   - `require_once ERC_ROOT_DIR . '/vendor/phpmailer/SMTP.php';`

---

## 5. Windows PHP 8.4 Runtime & Dual-Port QA Server Specification (`goldfish-php-admin`)

On Windows in this workspace, bare `php` lacks extensions. Always invoke:
```powershell
C:\Users\anike\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe `
  -d "extension_dir=C:\Users\anike\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\ext" `
  -d extension=gd -d extension=openssl -d extension=fileinfo -d extension=mbstring
```
*(Note: wrap `if (function_exists('exif_read_data'))` in GD upload processing because `php_exif` is not in the default command flags above.)*

Dual-port coexistence setup owned by `goldfish-php-admin`:
- **Legacy Site (Untouched):** `:8765` -> PHP `8.4` backend on `127.0.0.1:8766` (docroot = repo root).
- **Redesign Site (`redesign/`):** `:8770` -> PHP `8.4` backend on `127.0.0.1:8771` (`-t redesign redesign/router.php`).
- `redesign/router.php` handles:
  - Extensionless routes (`/`, `/donate`, `/locations`, `/volunteer`, `/missions`, `/about`, `/contact`, `/admin`), `/index.html` -> `/` `301`, direct `.php` endpoints.
  - Proper MIME headers (`.mjs` -> `text/javascript; charset=UTF-8`, `.woff2` -> `font/woff2`, `.svg` -> `image/svg+xml`).
  - Access denial (`403`) mirroring `.htaccess` (`config.php`, `mail-utils.php`, `includes/*`, `vendor/phpmailer/*`, `docs/*`, `.env*`, `data/signups.json`, `data/locations.json`, `data/geo_cache.json`, `data/admin-auth.json`, `data/site-config.json`, `*.lock`, `*.log`, `*.tmp.*`).
  - Coexistence fallback serving read-only `/e2e2rc_LOGO.png`, `/files/**`, `/gallery/**`, `/images/**` from `dirname(__DIR__)` whenever absent inside `redesign/` (strictly `realpath`-confined).

---

## 6. Strictly Disjoint File Ownership Table (Zero Overlap Across Builders)

Every file in `redesign/` belongs to **exactly one** role. Builders MUST NOT create or edit files owned by another role.

| Owner Role | Exclusive File Paths (`redesign/...`) |
| :--- | :--- |
| **`goldfish-architect`** *(Committed / Read-Only)* | `docs/ORG_FACTS.md`, `docs/HOSTING_CONTRACT.md`, `docs/BUILD_SPEC.md`, `data/.gitignore`, `data/events.json`, `data/locations.json`, `data/geo_cache.json`, `data/board.json`, `data/partners.json`, `data/testimonials.json`, `data/signups.json`, `vendor/maplibre-gl/*`, `vendor/fuse/*`, `vendor/phpmailer/*`, `vendor/lucide/*`, `fonts/*`, `includes/icons.php` |
| **`goldfish-shell-ui`** | `css/tokens.css`, `css/base.css`, `css/shell.css`, `css/pages/contact.css`, `includes/head.php`, `includes/header.php`, `includes/footer.php`, `js/theme.js`, `js/shell.js`, `js/forms.js`, `js/pages/contact.js`, `contact.php` |
| **`goldfish-story-impact`** | `index.php`, `donate.php`, `missions.php`, `about.php`, `css/pages/editorial.css`, `js/pages/editorial.js` |
| **`goldfish-volunteer-hub`** | `volunteer.php`, `css/pages/volunteer.css`, `js/pages/volunteer.js` |
| **`goldfish-dropoff-map`** | `locations.php`, `css/pages/locations.css`, `js/pages/locations.js` |
| **`goldfish-php-admin`** | `config.php`, `mail-utils.php`, `.htaccess`, `data/.htaccess`, `router.php`, `.env.example`, `get-config.php`, `get-gallery-images.php`, `get-locations.php`, `register-volunteer.php`, `send-email.php`, `admin-view.php`, `css/pages/admin.css`, `js/pages/admin.js` |

### Parallel Merge & Integration Checklist
- `admin-view.php` loads `/fonts/fonts.css`, `/css/tokens.css`, `/css/base.css`, `/css/pages/admin.css`, `/js/theme.js`, and `includes/icons.php` directly (with its own self-contained `<head>` sending `noindex, nofollow` and `Cache-Control: no-store, private`) so it works independently during parallel development and integrates cleanly with the shared design tokens.
- All JSON mutations during `:8770` QA write strictly inside `redesign/data/` (never root `/data/`).
- No builder may modify any legacy root file (`index.html`, `css/`, `js/`, root `*.php`, root `data/`, `files/`, `gallery/`, `images/`, root `.htaccess`, `tests/`).
