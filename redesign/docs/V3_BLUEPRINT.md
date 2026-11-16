# Lions District 2-E2 Eyeglass Recycling Center — v3 Clean-Slate Redesign Blueprint

> **Author:** `v3-critic-researcher` · **Scope:** `redesign/`  
> **Companion Truth Docs (Unchanged):**  
> - [`ORG_FACTS.md`](./ORG_FACTS.md) — Authoritative organizational facts, inventory (`214` bins / `34` cities / `37 + Fiji` countries / `12` Lion Years / `16` Board / `24` Partners / `4` verbatim quotes / `17` gallery photos / `6` PDFs), and zero-fabrication guardrails.  
> - [`HOSTING_CONTRACT.md`](./HOSTING_CONTRACT.md) — Locked GoDaddy PHP `8.x` zero-build `public_html` stack, JSON schemas, `4`-tier admin auth, `13` admin actions, public endpoints, CSP, and `:8765` vs `:8770` coexistence.

---

## 1. Benchmark Research Synthesis (2025–2026 Web Design)

Synthesized from 2025–2026 acclaimed web design patterns (**Daylight Computer**, **charity: water**, **The Ocean Cleanup**, **Linear**, and **Swiss Editorial / International Typographic Style**):

| Benchmark | What Works & Why It Applies to Lions 2-E2 ERC |
| :--- | :--- |
| **Daylight Computer** | *"Ruthless reduction"* and calm, low-stimulation surfaces: generous margins, warm daylight paper tones, crisp hierarchy, and zero competing visual widgets per viewport. Ideal for older Lions Club volunteers, donors using reading glasses, and civic visitors. |
| **charity: water** | Turns nonprofit impact into dignified, transparent storytelling: streamlined navigation, authentic documentary photography over gimmicks, clear unit-cost framing (`< $0.10` per pair), and a single primary action path per moment. |
| **The Ocean Cleanup** | Pairs real-world operational metrics (`21,696` pairs shipped; `40+` countries) with calm scrollytelling and clear functional grouping without burying visitors in raw multi-page sprawl. |
| **Linear & Swiss Editorial** | Strict 2-font typographic discipline (`Inter Tight Variable` for crisp `-0.03em` display & `>= 16px` ragged-right body; `Martian Mono Variable` strictly for tabular numbers/coordinates/metadata), subtle hairline dividers (`1px`), surface elevation via gentle tonal steps instead of heavy shadows, and `IntersectionObserver` / CSS `:target-current` scroll-spy navigation. |

**Core Design Lesson for v3:** *Credibility comes from restraint, real workshop photography, and effortless findability — never from decorative stunts.*

---

## 2. Visual & Structural Post-Mortem of Current v2 (`http://127.0.0.1:8770/`)

Captured and inspected all 7 routes (`/`, `/donate`, `/locations`, `/volunteer`, `/missions`, `/about`, `/contact`) in Chromium `1248` (`1440×900`):

### Measured Bloat & Duplication
- **97,951 px of total vertical scroll across 7 fragmented pages:**
  - `/`: `8,068 px` | `/donate`: `6,709 px` | `/locations`: **`53,204 px`** (all 214 drop-off bin cards dumped into an unbounded vertical stack) | `/volunteer`: `10,833 px` | `/missions`: `7,683 px` | `/about`: `6,647 px` | `/contact`: `4,807 px`
- **Massive CSS/JS sprawl & v1/v2 collision:**
  - **`13,463` lines (`308 KB`) of CSS** across `9` files, including **`253` leftover v1 `.ed-*` rules** still sitting inside `css/pages/editorial.css` (`3,060` lines) alongside `.v2-*` rules and duplicated `.erc-rotated-frame` blocks.
  - **`6,807` lines (`222 KB`) of JS** across `9` modules.
- **Repeated content across every route:** `5621 Bunker Blvd`, the east-side wooden walkway instruction, Saturday `9–3` hours, `our-story-group.png`, and the 4 core stats (`21,696`, `< $0.10`, `214`, `40+`) are repeated in 5–7 separate places.
- **Confused navigation ("neither infinite scroll nor tabs"):** `/` is already an 8,000px multi-section scroll, yet 6 additional multi-thousand-pixel pages exist behind the top nav, each with its own sub-pills, anchor jumps, filter chips, and tabs.
- **Lazy-load / visual rendering defects observed in capture:** In full-page screenshots, below-the-fold `loading="lazy"` portraits in `/about` (`16` Board cards), `FIG. 01` (`gallery/peru-bound.jpg`) in `/missions`, and `11` event logos in `/volunteer` rendered as blank boxes until scrolled; `h1` stacked spans on `/missions` (`38 COUNTRIES.5 CONTINENTS.`) and `/about` (`100% VOLUNTEERSIGHT WORKSHOP.`) visually collided without word spacing.

### `visheshb.com` Portfolio Gimmicks & Visual Clutter to Remove Completely
1. **Fake `01/04`–`04/04` progress loader bar + `SKIP INTRO`** at the top of `/`.
2. **Giant Solari split-flap airport departure board** (`ONE CENTER. GLOBAL SIGHT.` + `SPIN BOARD` + 6 pills) consuming ~1,000 px on `/`.
3. **Perforated movie-ticket stubs (`.v2-ticket`)** forced onto 5 unrelated contexts (3 home passes, 7 accepted item cards, 11 volunteer shifts, 6 PDF cards, 2 award cards).
4. **Gamified `#7CFC06` neon-lime rubber-stamp inspection queues** (`00 ACCEPTED | 00 RECYCLED | 00 SKIP` on `/donate`; `00 CLEARED | 08 PENDING` + `STAMP ALL` on `/contact`) and the `20/20 CALIBRATION` neon-lime swatch in the footer.
5. **Terminal/courier cosplay (`REQ-2E2 · DRAFT`, `FEE: $0`, `OPEN A DISPATCH`)** and jarring black-on-white `.v2-sheet` slab whiplash.
6. **`15+` `Caveat` handwritten scrawls + curved red SVG arrows** (`you're the inspector ↙`, `pick your pass ↙`, etc.).
7. **`Anton` all-caps condensed shouting + vermilion knockout boxes + red square periods `.`** on every section headline across every page, plus the giant outlined `LIONS 2-E2 ERC` footer watermark.

---

## 3. v3 Visual Identity ("Warm Optical Editorial")

One calm, daylight-first, accessible visual system built for clarity, real photography, and zero visual fatigue.

### A. 2-Font Self-Hosted Typographic System (`redesign/fonts/fonts.css`)
**Drop `Anton` and `Caveat Variable` completely** (stop loading them in `fonts.css`, and delete `anton-*`, `caveat-*`, `LICENSE-Anton.txt`, `LICENSE-Caveat.txt` from `redesign/fonts/`). Keep only:

| Role | Font Family (`redesign/fonts/`) | Weights | Rules |
| :--- | :--- | :--- | :--- |
| `--font-sans` | `'Inter Tight Variable', system-ui, -apple-system, sans-serif` | `100..900` (normal + italic) | Both **display headings** (sentence case, `font-weight: 600–700`, `letter-spacing: -0.025em`, `line-height: 1.08`, `text-wrap: balance`) and **body prose** (`>= 16px`, `line-height: 1.6`, `64ch` max measure, ragged-right). |
| `--font-mono` | `'Martian Mono Variable', ui-monospace, monospace` | `100..800` | Strictly for tabular figures (`font-variant-numeric: tabular-nums`), diopter ranges (`+0.25` to `±5.00+`), bin counts (`214`), Lion-Year shipping tables, and quiet section kickers (`0.75rem`, `letter-spacing: 0.08em`). |

### B. Balanced Dual-Theme OKLCH Palette (`redesign/css/tokens.css`)
Uses `color-scheme: light dark` and `light-dark()` with light-first daylight clarity and cohesive dark observatory parity (WCAG 2.2 AA `>= 4.5:1` text, `>= 3:1` UI controls). **Zero `#7CFC06`, zero vermilion poster blocks, zero `.v2-sheet` inversion slabs.**

| Token | Light ("Warm Archival Paper") | Dark ("Deep Slate Observatory") | Purpose |
| :--- | :--- | :--- | :--- |
| `--bg` | `oklch(98.5% 0.004 85)` (`#FAF9F6`) | `oklch(15.5% 0.015 245)` (`#0E141B`) | Primary page canvas |
| `--bg-alt` | `oklch(96.0% 0.006 85)` (`#F2F0EB`) | `oklch(19.5% 0.017 245)` (`#151D27`) | Subtle alternating section wash |
| `--surface` | `oklch(99.8% 0.002 90)` (`#FFFFFF`) | `oklch(22.5% 0.018 245)` (`#1B2430`) | Cards, map drawer, inputs, `<dialog>` |
| `--fg` | `oklch(20.0% 0.020 250)` (`#141D29`) | `oklch(95.0% 0.006 90)` (`#EFF2F5`) | Headings & primary copy |
| `--fg-2` | `oklch(38.0% 0.020 250)` (`#3B4654`) | `oklch(80.0% 0.012 245)` (`#BCC5D1`) | Lead & secondary copy |
| `--muted` | `oklch(48.0% 0.016 250)` (`#596574`) | `oklch(68.0% 0.014 245)` (`#919EAE`) | Captions, kickers, helper text |
| `--rule` | `oklch(88.0% 0.008 245)` | `oklch(31.0% 0.016 245)` | Crisp `1px` hairlines & borders |
| `--rule-strong` | `oklch(62.0% 0.018 250)` | `oklch(54.0% 0.018 245)` | Input borders (`>= 3:1` UI contrast) |
| `--accent` | `oklch(43.0% 0.130 255)` (`#1D4E89`) | `oklch(72.0% 0.125 245)` (`#6BA6F2`) | **ONE** primary Optical Cobalt accent (CTAs, active nav, map pins) |
| `--accent-hover` | `oklch(36.0% 0.130 255)` | `oklch(79.0% 0.115 245)` | Hover/active shift |
| `--on-accent` | `oklch(99.0% 0.003 90)` (`#FFFFFF`) | `oklch(15.0% 0.020 245)` (`#0B121B`) | Guaranteed `>= 7:1` ink on `--accent` fills |
| `--gold` / `--on-gold` | `oklch(82.0% 0.135 85)` / `oklch(20.0% 0.030 70)` | `oklch(78.0% 0.130 85)` / `oklch(16.0% 0.030 70)` | Sparingly for Lions recognition badges (`Helen Keller`, `Life Membership`) |

### C. Theme Control Standards (`modern-web-guidance: dark-mode-toggle`)
- `<meta name="color-scheme" content="light dark">` in `head.php` + tiny inline FOUC-prevention `<script>` checking `localStorage.getItem('erc-color-scheme')`.
- `:root` sets `color-scheme: light dark` and maps `:has(> head > meta[name="color-scheme"][content="dark"|"light"])`.
- Compact 2-state comfort toggle button (`#themeToggle`) in header (switches between OS default and opposite override; removes stored key when reverting to OS; never clears `localStorage` on OS `matchMedia` change) + dispatches `erc:themechange` so MapLibre swaps OpenFreeMap `positron` / `dark` cleanly.

---

## 4. Unified Information Architecture: Single Continuous Scroll on `/`

Replace the fractured 7-page maze (`97,951 px`) with **ONE continuous, airy, single-scroll narrative on `/`** (`~4,800–5,500 px` total desktop height — an **18× reduction in bloat**), organized into **6 unmistakable sections** with zero repeated heroes, zero repeated address blocks, and progressive-disclosure `<dialog>` drawers for dense reference data.

### A. Sticky Glass Pill Header & Scroll-Spy Navigation
- **Left:** Clean `2E2` optical emblem (`erc_optical_reticle()` + `Lions 2-E2 ERC` wordmark linking to `#top`).
- **Center (`<nav>` with `scroll-target-group: auto`):** Six crisp, single-phrase links:
  1. `Drop-Off` (`#drop-off`)
  2. `Process` (`#process`)
  3. `Impact` (`#impact`)
  4. `Volunteer` (`#volunteer`)
  5. `About` (`#about`)
  6. `Contact` (`#contact`)
- **Right:** Compact theme toggle (`#themeToggle`) + mobile `<dialog closedby="any">` menu button (`< 960px`).
- **Scroll-Spy Mechanics (`modern-web-guidance: scrollspy`):**
  - CSS `scroll-target-group: auto` on `.site-nav` + `.site-nav__link:is(:target-current, .is-active)` (combined via `:is()` so older browsers don't discard the rule).
  - `IntersectionObserver` (`rootMargin: '-25% 0px -65% 0px'`, `threshold: 0`) syncs `.is-active`, `aria-current="true"`, and quietly updates `history.replaceState(null, '', '#' + id)`.
  - Every target `<section id="...">` includes `tabindex="-1"` and `scroll-margin-top: 5.5rem` for keyboard accessibility.

### B. Exact 6-Section Story Flow (`index.php` + `includes/sections/*.php`)

| Section | ID | Content & Interactive Scope (100% from `ORG_FACTS.md`) | Height Discipline & Progressive Disclosure |
| :--- | :--- | :--- | :--- |
| **Hero** | `#top` | Sentence-case `h1`: *"Giving clear sight to 40+ nations."* Subhead summarizing the 100% volunteer Watauga workshop (`21,696` pairs shipped in `2024–2025` for `< $0.10`/pair). 2 CTAs: **Donate & Find a Bin** (`#drop-off`), **Volunteer Saturday** (`#volunteer`). Right: `images/our-story-group.png` + single concise HQ info strip (`5621 Bunker Blvd, Watauga, TX 76148` · Sat `9 AM–3 PM`, Mon–Fri by appt · east-side wooden walkway to rear entrance). Subtle 4-stat hairline bar (`21,696` Pairs `2024–25` · `< $0.10` Cost/Pair · `214` DFW Bins · `40+` Countries). | Above the fold (`~760px`); **only place** with hero photography & full HQ walkway intro. |
| **1. Drop-Off** | `#drop-off` | **Accepted Items (Clean 2×2 Grid + Not-Accepted Rule):** Eyeglasses (prescription, readers, sunglasses in any condition incl. broken), Hearing Aids (digital/analog, working or not), Cell Phones (any condition, data wiped), Windows 11-ready/installed Laptops & Tablets. Clear exclusion line (no desktops, damaged screens, non-functional keyboards, missing parts) + partner bin variance note + bulk/check mail note (`P.O. Box 1854, Keller, TX 76244`).<br>**Interactive DFW Drop-Off Finder:** Split-pane workspace (`520px` fixed desktop height): Left (`380px`) has Fuse.js search + `34`-city `<select>` + **Near Me** geolocation + scrollable list of all `214` bins (clearly marking the `10` un-geocoded directory-only entries). Right has MapLibre GL `v6.18.0` Supercluster map (`OpenFreeMap` `positron`/`dark`). | **Fixes the 53,204 px `/locations` bug:** all `214` bins live inside a bounded `520px` scrollable directory pane alongside the map. |
| **2. Process** | `#process` | **5-Step Optical Refurbishment Flow:** Clean horizontal editorial timeline (`Collection` → `Inspection & Sorting` [5 pathways] → `Wash & Sanitize` → `0.25D Lensometry` → `50-Pair Box & Dispatch`). Paired with properly rotated `images/process-lensometers.jpg` (`.erc-rotated-frame`) & `gallery/drying-glasses.jpg`, plus compact diopter specification bar (`+0.25` to `±5.00+`, `50 pairs/box`) and `ERC-Brochure.pdf` link. | Replaces both the `1,000px` Solari board AND the 6 giant wireframe CAD cards (~`620px` total). |
| **3. Impact** | `#impact` | **Global Reach & Vision Gap:** Concise WHO context (`2.2B` impaired, `1B` unaddressed, `157M` lack glasses) + interactive region filter tabs (`Americas [18]`, `Africa [10]`, `Asia [5]`, `Europe [4]`, `Fiji [1]`) displaying the exact `37 + Fiji` documented nations.<br>**2×2 Verbatim Field Testimonials:** Exact quotes from Carol Fanelli, Jeff Thomas, Millie Roberts, and Wayne Williams.<br>**Request Eyeglasses for a Mission:** Concise eligibility summary (`Mission-Request-Form.pdf`, `30+` days lead time, nonprofit Tax ID + refractive testing verification, not for resale, mission pays shipping). | **Drawer `<dialog closedby="any">`:** *"View 12-Year Shipping Ledger (2013–2025)"* opens the complete `12`-row Lion-Year table & summary bar chart on demand instead of bloating the main scroll. |
| **4. Volunteer** | `#volunteer` | **Walk-Ins & Group Days:** Concise summary of Saturday open hours (`9 AM–3 PM`), `9–12` & `12–3` shifts, on-site training, zero prior experience required (and zero fabricated age/shoe/chaperone rules), plus Group Days up to `20` volunteers (`Group-Volunteer-Form.pdf`).<br>**Interactive Shift Calendar & Upcoming Sessions:** Month calendar synced with clean session cards (`events.json`, live seats, `.ics` + Google Calendar export, **Sign Up** button opening native `<dialog closedby="any">` backed by `POST /register-volunteer.php`).<br>**Workshop Photos:** Clean 6-photo preview row + **"View All 17 Photos"** button opening native `<dialog>` gallery lightbox (`GET /get-gallery-images.php`, keyboard Left/Right/Esc). | Replaces 11 giant perforated ticket cards + 17-tile filmstrip grid with a crisp split calendar/sessions panel + 6-photo strip + lightbox `<dialog>`. |
| **5. About** | `#about` | **Story, Values & Impact:** Founded `2013`, `501(c)(3)` (`2014`), District 2-E2 / MD-2 Texas Lions; 5 Core Values (`Integrity`, `Compassion`, `Sustainability`, `Community`, `Excellence`) + 4 Impact Groups (`Children`, `Adults`, `Seniors`, `Communities`) + Governance summary (`ERC-Bylaws.pdf`).<br>**Recognition & Quick Reference:** Compact cards for **Helen Keller Award** (`$250` / `$500` / `$1,000` or volunteer hours, `Helen-Keller-Award-Application.pdf`) and **ERC Life Membership** (`ERC-Life-Membership-Application.pdf`). | **Drawers `<dialog closedby="any">`:**<br>1. **"Meet the Board (16)"** opens Current (`12`) / Previous (`4`) portrait grid (`images/board/*` with `loading="eager"` on open + fallback SVG).<br>2. **"Partners & Friends (24)"** opens clean 24-partner directory. |
| **6. Contact** | `#contact` | **Left Column:** Direct Phone (`$GLOBAL_PHONE`), Email (`$GLOBAL_EMAIL`), `24-hour` response note, P.O. Box `1854` check mailing address, **All 6 Official PDFs** clean download list, and normal `<details>`/`<summary>` **FAQ Accordion (`8` verified answers)**.<br>**Right Column:** Clean accessible contact form (`POST /send-email.php`, `8` inquiry categories, character counter, honeypot `website_url`, inline `?status=success`/`error` banner). | Normal clean `<details>` FAQ + straightforward form (zero `REQ-2E2` terminal cosplay, zero `STAMP ALL` FAQ game). |
| **Footer** | `<footer>` | Quiet single-band footer: Foundation legal name + `501(c)(3)` notice, `No trackers · Self-hosted fonts`, `Admin Portal` (`/admin-view.php`), and `Back to Top`. | ~`140px` tall; zero giant watermark, zero neon calibration pill. |

---

## 5. Clean URL Parity, Backend Contracts & File Consolidation

### A. URL Compatibility (`router.php` & `.htaccess` — Zero Divergent Templates)
Visitors, bookmarks, search engines, QR codes, and `send-email.php` redirects MUST all resolve cleanly on a **single `index.php` template**:
1. **Route Mapping in `.htaccess` and `router.php`:**
   - `/donate` (`donate.php`) → serves `index.php` with `$initialSection = 'drop-off'`
   - `/locations` (`locations.php`) → serves `index.php` with `$initialSection = 'drop-off'` (opens/scrolls to `#drop-off` finder)
   - `/volunteer` (`volunteer.php`) → serves `index.php` with `$initialSection = 'volunteer'`
   - `/missions` (`missions.php`) → serves `index.php` with `$initialSection = 'impact'`
   - `/about` (`about.php`) → serves `index.php` with `$initialSection = 'about'`
   - `/contact` (`contact.php`, including `/contact?status=success` from `send-email.php`) → serves `index.php` with `$initialSection = 'contact'`
   - `/index.html` → `301` redirect to `/`
   - `/admin` and `/admin-view.php` → serve standalone `admin-view.php`
2. **Client-Side Initial Deep-Link & Legacy `#hash` Bridge (`js/app.js`):**
   - If `data-initial-section` is set (from `/donate`, `/locations`, `/volunteer`, `/missions`, `/about`, `/contact`), scroll immediately (`behavior: 'instant'`) to `#<section>`, focus it (`tabindex="-1"`), and normalize URL cleanly if no status query param is present (keeping `?status=...` intact on `/contact` feedback).
   - Map legacy SPA `#hash` fragments on initial load: `#home` → `#top`, `#services` → `#impact`, `#get-involved` → `#volunteer`, `#locations` / `#map` → `#drop-off`, `#about` → `#about`, `#contact` → `#contact`.
3. **All Existing PHP Endpoints & Admin Portal Unchanged:**
   - `get-config.php`, `get-gallery-images.php`, `get-locations.php`, `register-volunteer.php`, `send-email.php`, `config.php`, `mail-utils.php`, and all `13` actions / `4`-tier auth in `admin-view.php` remain **100% contract-identical** to `HOSTING_CONTRACT.md`.
   - Re-skin `css/admin.css` cleanly to match v3 tokens (`Inter Tight` + `Martian Mono`, Optical Cobalt accent, clean cards/tables).

### B. Target File Footprint (Delete All v1/v2 Dead Weight)
Consolidate from **24 CSS/JS/page files** down to a lean, crystal-clear structure:

- **Keep / Refactor:**
  - `redesign/index.php` + `redesign/includes/{head,header,footer,icons}.php` + `redesign/includes/sections/{hero,dropoff,process,impact,volunteer,about,contact}.php`
  - `redesign/css/tokens.css`, `redesign/css/app.css`, `redesign/css/admin.css` (**3 CSS files total**, ~2,200 lines max vs. `13,463` lines in v2!)
  - `redesign/js/theme.js`, `redesign/js/app.js`, `redesign/js/locations.js`, `redesign/js/admin.js` (**4 JS modules total**)
  - `redesign/fonts/fonts.css` + `inter-tight-*` + `martian-mono-*` + their 2 `LICENSE-*.txt` files
  - `redesign/admin-view.php`, `redesign/config.php`, `redesign/mail-utils.php`, `redesign/get-*.php`, `redesign/register-volunteer.php`, `redesign/send-email.php`, `redesign/router.php`, `redesign/.htaccess`, `redesign/vendor/*`, `redesign/data/*`
- **Delete During v3 Build:**
  - Standalone redundant PHP pages (`donate.php`, `locations.php`, `volunteer.php`, `missions.php`, `about.php`, `contact.php`) once `.htaccess` & `router.php` route them cleanly to `index.php`
  - Redundant CSS (`css/base.css`, `css/shell.css`, `css/pages/{editorial,locations,volunteer,missions-about,contact}.css`)
  - Redundant JS (`js/shell.js`, `js/forms.js`, `js/pages/{editorial,volunteer,missions-about,contact}.js`)
  - Unused display/handwriting fonts (`fonts/anton-*`, `fonts/caveat-*`, `fonts/LICENSE-Anton.txt`, `fonts/LICENSE-Caveat.txt`)

---

## 6. Dialog & Interaction Implementation Rules (`modern-web-guidance`)

- **Native `<dialog closedby="any">`:** Open via `.showModal()`. Never add `role="dialog"`, `aria-modal`, custom focus traps, or `body { overflow: hidden }`. Always include:
  ```css
  dialog:modal { overscroll-behavior: contain; }
  dialog::backdrop { overflow: hidden; overscroll-behavior: contain; }
  ```
  Plus the `pointerdown`/`pointerup` bounding-rect fallback when `!('closedBy' in HTMLDialogElement.prototype)`.
- **Rotated Lensometer Photos:** Preserve `.erc-rotated-frame` for `images/process-lensometers.jpg` and `gallery/lensometers.jpg` so they render upright without mutating shared root JPEGs.
- **MapLibre & CSP:** Keep `vendor/maplibre-gl` (`v6.18.0`) + `vendor/fuse` (`v7.5.0`) + `OpenFreeMap` CSP headers unchanged.
- **Zero-Fabrication & Security:** Zero inline `onclick=`, zero `innerHTML` with JSON/user strings (`createElement` + `textContent`), zero fabricated volunteer age/dress-code rules, and `>= 44×44px` touch targets across `375px` (`100%`/`200%` zoom) and `1440px`.
