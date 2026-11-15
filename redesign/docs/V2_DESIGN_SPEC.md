# Lions District 2-E2 Eyeglass Recycling Center — v2 Design System & Stage 2 Builder Contract

> **Author:** `v2-architect` · **Branch:** `feat/ground-up-redesign` · **Scope:** `redesign/`  
> **Companion Truth Docs:** [`ORG_FACTS.md`](./ORG_FACTS.md) (domain truth) · [`HOSTING_CONTRACT.md`](./HOSTING_CONTRACT.md) (GoDaddy PHP 8 stack, JSON schemas, 13 admin actions, CSP, dual-port coexistence)  
> **Visual North Star:** Precision optical workshop & humanitarian dispatch — dark-first high-contrast surfaces, monumental condensed poster typography, alternating rounded contrast sheets, technical telemetry eyebrows, warm handwritten annotations, and interactive vector/canvas craft.

---

## 1. Self-Hosted 4-Layer Typography (`redesign/fonts/fonts.css`)

Zero Google Fonts CDN links; all `.woff2` files + OFL licenses live locally in `redesign/fonts/`:

| Token | Font Family | Weights | Usage & Typographic Rules |
| :--- | :--- | :--- | :--- |
| `--font-display` | `'Anton', Impact, 'Arial Narrow', sans-serif` | `400` | Monumental uppercase display headings (`letter-spacing: -0.02em`, `line-height: 0.93`, `text-transform: uppercase`) |
| `--font-sans` | `'Inter Tight Variable', system-ui, sans-serif` | `100..900` (normal + italic) | Body prose, UI labels, descriptions (`letter-spacing: -0.015em`, `>= 16px` body, `62ch–66ch` measure) |
| `--font-mono` | `'Martian Mono Variable', ui-monospace, monospace` | `100..800` | Uppercase eyebrows, coordinates, timestamps, ticket IDs, diopter/year tables (`font-variant-numeric: tabular-nums`, `0.1em–0.12em` tracking) |
| `--font-hand` | `'Caveat Variable', 'Segoe Print', cursive` | `400..700` | Warm handwritten human annotations paired with `erc_curved_arrow()` inline SVGs |

---

## 2. Dark-First / Dual-Theme Token Contract (`redesign/css/tokens.css`)

Theme mode cycles `AUTO` → `LIGHT` → `DARK` → `AUTO` via `#themeToggle` (`js/theme.js`), synchronizing `localStorage['erc-color-scheme']`, `<meta name="color-scheme">`, `<html data-theme>`, and `window` event `erc:themechange` (`detail: { resolved, preference, mode }`).

### Primary v2 Tokens (WCAG 2.2 AA `>= 4.5:1` Verified)

| Token | Dark Value (`#0b1117` stage) | Light Value (`#eaf0f8` stage) | Usage & Contrast Rules |
| :--- | :--- | :--- | :--- |
| `--bg` | `#0b1117` | `#eaf0f8` | Primary page canvas |
| `--bg-2` | `#121b24` | `#dce4ee` | Secondary band / schematic inset / footer |
| `--surface` | `#17212b` | `#f7f9fc` | Cards, tickets, modals, form containers |
| `--surface-2` | `#1e2b38` | `#ffffff` | Elevated hover / highlight surface |
| `--fg` | `#e8eef5` | `#14202b` | Headings & primary copy (`16.2:1` / `14.4:1`) |
| `--fg-2` | `#b4c0cc` | `#334150` | Lead copy & secondary paragraphs (`8.8:1` / `9.9:1`) |
| `--muted` | `#8b9bb0` | `#4e5d6c` | Telemetry, captions, metadata (`>= 5.09:1` on all surfaces) |
| `--rule` | `rgba(232, 238, 245, 0.13)` | `rgba(20, 32, 43, 0.14)` | Hairline borders & section dividers |
| `--rule-strong` | `rgba(232, 238, 245, 0.28)` | `rgba(20, 32, 43, 0.30)` | Input borders, dashed ticket perforations, outline strokes |
| `--accent` | `#e5533a` | `#b83227` | International Vermilion fills, knockout blocks, pill CTAs |
| `--accent-hot` | `#ff6a4d` | `#9e271d` | Hover shift / luminous dark-mode highlight |
| `--accent-ink` | `#ff6a4d` | `#b83227` | Vermilion text/links/icons on surfaces (`>= 5.09:1` AA) |
| `--on-accent` | `#0b1117` | `#ffffff` | **Mandatory ink on `--accent` fills** (`5.09:1` dark / `5.96:1` light) |
| `--signal` / `--on-signal` | `#7cfc06` / `#0b1117` | `#7cfc06` / `#0b1117` | Optical calibration / unlocked telemetry pill (`14.26:1`) |
| `--cyan` | `#22d3ee` | `#0e7490` | Optical reticle & info accents (`10.5:1` / `4.67:1`) |

> **CRITICAL CONTRAST RULE:** In Dark mode, NEVER put `#ffffff` text on `#e5533a` (only `3.73:1`). Always pair `--accent` backgrounds with `color: var(--on-accent)` (`#0b1117` in dark, `#ffffff` in light), and use `var(--accent-ink)` for vermilion text on dark surfaces!

### Alternating Contrast Sheet Tokens (`.v2-sheet`)
Inside `.v2-sheet`, all semantic tokens (`--bg`, `--surface`, `--fg`, `--fg-2`, `--muted`, `--rule`, `--accent`, `--accent-ink`, `--on-accent`, plus backward-compatible `--bg-canvas`, `--ink-primary`, etc.) automatically invert:
- When page is **Dark** → `.v2-sheet` renders on `#eaf0f8` / `#f7f9fc` with `#14202b` ink and `#b83227` accent (`#ffffff` on accent).
- When page is **Light** → `.v2-sheet` renders on `#0e151c` / `#17212b` with `#edf2f7` ink and `#ff6a4d` accent (`#0b1117` on accent).

### Backward-Compatible v1 Aliases
All v1 tokens (`--bg-canvas`, `--bg-surface`, `--bg-subtle`, `--bg-tint`, `--ink-primary`, `--ink-secondary`, `--ink-muted`, `--border-subtle`, `--border-strong`, `--brand-primary`, `--brand-primary-hover`, `--on-brand-primary`, `--brand-gold-*`, `--brand-emerald`, `--status-*`, `--focus-ring`) remain mapped in `tokens.css` so `admin.css` and progressive migrations never break.

---

## 3. Reusable v2 Primitives (`css/base.css` + `includes/icons.php`)

Page stylesheets MUST wrap rules inside `@layer pages { ... }` so they compose cleanly above `@layer components`.

| Primitive | Markup / CSS Classes | Purpose |
| :--- | :--- | :--- |
| **Contrast Stage Sheet** | `<section class="v2-sheet"><div class="v2-sheet__inner">...</div></section>` | Inset rounded `clamp(18px, 2vw, 30px)` inverted-polarity section stage |
| **Poster Display Heading** | `<h1 class="v2-display v2-display--hero">` (variants: `--lg`, `--md`, `--sm`) | Stacked `Anton` uppercase headline (`line-height: 0.93`, `-0.02em`) |
| **Vermilion Knockout & Dot** | `<span class="v2-knockout">...</span>`, `<span class="v2-accent-text">...</span>`, `<span class="v2-dot" aria-hidden="true"></span>` | Vermilion rectangle block behind hero stats/words & signature square period |
| **Ruled Telemetry Eyebrow** | `<div class="v2-eyebrow"><span>TAG</span><span class="v2-eyebrow__rule" aria-hidden="true"></span><span>META</span></div>` | `Martian Mono` `0.75rem` uppercase header with hairline rule |
| **Live Pulse Badge** | `<span class="v2-telemetry"><span class="v2-pulse v2-pulse--live" aria-hidden="true"></span>...</span>` | Status dot + tabular telemetry (`data-ct-clock` auto-populates Central Time via `shell.js`) |
| **Handwritten Annotation** | `<p class="v2-note"><span>...</span><?= erc_curved_arrow('down-left') ?></p>` | `Caveat Variable` annotation + curved SVG arrow (`down-left`, `down-right`, `up-right`, `up-left`) |
| **Perforated Ticket Card** | `<article class="v2-ticket"><div class="v2-ticket__main">...</div><div class="v2-ticket__stub"><span class="v2-ticket__serial">NO. 01</span>...</div></article>` | Notched admission/dispatch ticket with semicircular cutouts & vertical tear stub |
| **Blueprint Schematic Card** | `<article class="v2-schematic-card v2-schematic-card--split">...<div class="v2-schematic-box"><span class="v2-schematic-box__caption">FIG. 01 · ...</span>...</div></article>` | `22px`-radius card with station number + inset `FIG. 01` wireframe schematic diagram |
| **Pill CTA Buttons** | `.btn.btn--primary`, `.btn.btn--secondary`, `.btn.v2-btn-pill` | `>= 44x44px` rounded pill actions |
| **Rotated Lensometer Fix** | `<div class="erc-rotated-frame erc-rotated-frame--landscape"><img class="is-ccw90-no-exif" ...></div>` | Normalizes `images/process-lensometers.jpg` & `gallery/lensometers.jpg` |

### PHP & JS Helpers Available
- `erc_icon(string $slug, array $attrs = [])` in `includes/icons.php` — Lucide `v1.73.0` inline SVG renderer.
- `erc_curved_arrow(string $direction, array $attrs = [])` in `includes/icons.php` — curved annotation arrows.
- `erc_optical_reticle(array $attrs = [])` in `includes/icons.php` — concentric optical reticle ring SVG.
- `erc_media_url(string $path)` in `config.php` — resolves `/e2e2rc_LOGO.png`, `/files/*`, `/gallery/*`, `/images/*` cleanly in coexistence (`:8770`) and production.
- `includes/head.php` accepts `$pageMeta['css']` (e.g. `['/css/pages/missions-about.css']`) and `includes/footer.php` accepts `$pageMeta['js_modules']` (e.g. `['/js/pages/missions-about.js']`).

---

## 4. Stage 2 Disjoint File Ownership Table (5 Parallel Builders)

Each Stage 2 builder MUST edit ONLY its assigned files below (`includes/`, `css/tokens.css`, `css/base.css`, `css/shell.css`, `fonts/`, `js/theme.js`, `js/shell.js`, `js/forms.js` are frozen in Stage 1):

| Builder | Owned PHP Entrypoints | Owned CSS / JS Files | Primary Signature Scope |
| :--- | :--- | :--- | :--- |
| **`v2-home-pipeline`** | `index.php`, `donate.php` | `css/pages/editorial.css`, `js/pages/editorial.js` | Split/poster Hero (`21,696` / `< $0.10` / `214` / `40+`), live Watauga telemetry, `Caveat` callouts, interactive split-flap optical board, `01`–`06` schematic refurbishment pipeline (`FIG. 01`–`FIG. 06` inline SVG blueprints), `0.25D` `50`-pair box spec, interactive donation eligibility checker on `/donate` |
| **`v2-globe-missions`** | `missions.php`, `about.php` | `css/pages/missions-about.css`, `js/pages/missions-about.js` | Interactive HTML5 Canvas dotted 3D globe with hoverable pins/arcs & `PAUSE GLOBE` toggle across all `37 + Fiji` documented countries, `2013–2025` Lion-Year bar/table telemetry, `.v2-sheet` mission request checklist & verbatim testimonials on `/missions`; history/values, governance, current (`12`) & previous (`4`) board portraits, `24` partners, Helen Keller & Life Membership on `/about` |
| **`v2-radar-locations`** | `locations.php` | `css/pages/locations.css`, `js/pages/locations.js` | Watauga HQ spotlight (`5621 Bunker Blvd` + east-side walkway note), MapLibre GL `v6.18.0` Supercluster map (auto-swapping OpenFreeMap `dark` / `positron` on `erc:themechange`), Fuse.js `v7.5.0` fuzzy search + 34-city filter + Near Me, clean directory for all `214` bins including the `10` un-geocoded entries |
| **`v2-volunteer-hub`** | `volunteer.php` | `css/pages/volunteer.css`, `js/pages/volunteer.js` | Perforated `.v2-ticket` volunteer session cards (`events.json`, live seats), Saturday walk-in & up-to-20 group info (`Group-Volunteer-Form.pdf`, zero fabricated rules), interactive monthly calendar, native `<dialog closedby="any">` registration (`POST /register-volunteer.php`), `.ics` & Google Calendar export, gallery lightbox (`/get-gallery-images.php`) |
| **`v2-contact-admin`** | `contact.php`, `admin-view.php` | `css/pages/contact.css`, `js/pages/contact.js`, `css/pages/admin.css`, `js/pages/admin.js` | `.v2-sheet` dispatch contact layout (`8` inquiry categories -> `POST /send-email.php`, character counter, inline `?status=` alerts), all `6` PDF forms center, `<details>`/`<summary>` FAQs on `/contact`; v2 dark/light token-aligned Admin Operations Console preserving all `13` actions & `4`-tier auth on `/admin-view.php` |

---

## 5. Non-Negotiable Quality & Hosting Guardrails

1. **Zero Fabrication:** Use ONLY facts from `ORG_FACTS.md`. Never invent volunteer minimum age, chaperone ratios, or closed-toe shoe/dress-code rules.
2. **Zero Horizontal Overflow:** Test at `375px` (`100%` & `200%` zoom) and `1440px`; keep `min-inline-size: 0` on all grid/flex children and `overflow-wrap: anywhere` on long strings.
3. **Touch Targets:** Every link, button, tab, and form control MUST be `>= 44x44px` (`2.75rem`).
4. **Reduced Motion:** Guard all CSS keyframe animations and JS canvas/globe loops behind `prefers-reduced-motion: reduce` (render static crisp canvas frame when reduced motion is active).
5. **Security & CSP:** Never use inline `onclick=` attributes or `innerHTML` with JSON/user strings (use `createElement` + `textContent`). Keep `HOSTING_CONTRACT.md` endpoints and JSON schemas untouched.
