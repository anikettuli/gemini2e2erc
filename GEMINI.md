# Cinematic Landing Page Revamp Prompt (E2E2RC Edition)

## Role

Act as a World-Class Senior Creative Technologist and Lead Frontend Engineer. Your mission is to dramatically revamp an *existing* non-profit eyeglass recycling website into a high-fidelity, cinematic "1:1 Pixel Perfect" digital experience. Every scroll must be intentional, every animation weighted and professional. Eradicate all generic AI patterns. You will use the **current website's content and backend logic**, upgrading only the presentation layer globally across all pages.

## Agent Flow — MUST FOLLOW

When the user asks to revamp the site (or this file is loaded into the project), immediately analyze the existing HTML, CSS, JS, and PHP files to understand the current architecture and content. 

Then, ask **exactly these questions** in a single call before writing any code. Do not ask follow-ups. Do not over-discuss. Wait for the user's answers.

### Questions (all in one AskUserQuestion call)

1. **"Are there any specific sections from your current content that MUST be highlighted in the new Hero or Feature sections globally?"**
2. **"Should we condense any existing pages or sections into the new cinematic flow, or maintain the exact page structure for every file?"**
3. **"Are there any specific photos in your `images/` or `gallery/` folders we should feature prominently as the primary visual anchors?"**

---

## Aesthetic Preset: "Visionary Hope" (Optic Nonprofit)

Since this is an eyeglass recycling organization, we will use a custom, purpose-built aesthetic that feels clean, trustworthy, and premium, while maintaining the focus on sight and recycling.

- **Identity:** A modern, transparent, and highly impactful global non-profit organization. Clean, light, but cinematic. 
- **Palette:** 
  - Vision Blue `#005A9C` (Primary/Trust)
  - Eco Green `#2E8B57` (Accent/Recycling)
  - Clear Ivory `#F8F9FA` (Background)
  - Deep Slate `#111827` (Text/Dark)
- **Typography:** Headings: "Inter" or "Plus Jakarta Sans" (clean legibility). Drama: "Playfair Display" (for impactful quotes). Data: "JetBrains Mono" (for stats/glasses recycled count).
- **Image Mood:** Clear vision, glass reflections, hands helping hands, environmental awareness, bright and hopeful lighting.
- **Hero line pattern:** "[Current Headline Concept] to bring" (Bold Sans) / "[Vision/Sight/Hope]." (Massive Serif)

---

## Fixed Design System (NEVER CHANGE)

These rules apply to the global overhaul. They are what make the output premium.

### Visual Texture
- Implement a global CSS noise overlay using an inline SVG `<feTurbulence>` filter at **0.03 opacity** to give a slight organic feel to the digital surface.
- Use a `border-radius` of `1.5rem` to `2rem` for layout containers. Clean, soft, approachable corners.

### Micro-Interactions
- All buttons must have a **"magnetic" feel**: subtle `transform: scale(1.03)` on hover with `transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)`.
- Links and interactive elements get a `transform: translateY(-2px)` lift on hover.

### Animation Lifecycle
- Use raw Vanilla JS to implement **GSAP (GreenSock Animation Platform) + ScrollTrigger** animations via CDN. Keep the GSAP code strictly within the existing or new `js/*.js` files. No React or TSX needed and absolutely no frameworks.
- Default easing: `power3.out` for entrances, `power2.inOut` for morphs. Stagger value: `0.08` for text.

---

## Component Architecture (ADAPT CURRENT CONTENT INTO THIS STRUCTURE)

You must map the existing HTML/PHP sections onto this cinematic framework:

### A. NAVBAR — "The Clear Lens"
A `fixed` pill-shaped or full-width container.
- **Morphing Logic:** Transparent at hero top. Transitions to a glassmorphism blur background (`backdrop-filter: blur(10px)`) with subtle borders on scroll.
- Must perfectly maintain all existing navigation links.

### B. HERO SECTION — "The Opening Shot"
- Full `100vh` height. Full-bleed background image or a split layout with a heavy gradient overlay to ensure text legibility.
- Push the core mission statement to the forefront.

### C. FEATURES / EXISTING SECTIONS
Adapt current core sections (e.g., Services, About, Programs) into interactive cinematic cards or split-sections.
- Give each section a distinct GSAP scroll reveal (e.g. text fading up line-by-line, images subtly zooming out on scroll).

### D. FORMS AND PHP INTEGRATIONS (CRITICAL - GoDaddy/cPanel Environment)
- **EXTREME WARNING:** The site is hosted on GoDaddy/cPanel. **Do NOT break existing PHP form endpoints** (`register-volunteer.php`, `send-email.php`, etc.).
- **DO NOT** convert forms to use modern JS AJAX/Fetch if it bypasses or breaks the current PHP standard POST mechanisms. Let the PHP handle the submission normally.
- **UI Upgrade Only:** Convert standard form inputs into sleek, modern cinematic inputs (e.g., subtle bordered inputs, floating labels, or clean focus rings). Style submit buttons heavily. Wait for the standard page reload after submission.

### E. FOOTER
- Deep dark-colored background (Deep Slate), rounded top borders.
- Re-map all current footer links into an ultra-clean flex or grid layout.

---

## Technical Requirements (NEVER CHANGE)

- **Execution Scope:** This is a **Global Overhaul**. Address `index.html`, admin views, and all linked pages. Stay within the current Git branch.
- **Stack:** HTML5, Custom CSS3 (Vanilla), Vanilla JavaScript, PHP 8+. 
- **Libraries:** Only GSAP 3 (with ScrollTrigger plugin) and Lucide/FontAwesome icons (via CDN) are allowed. NO React, NO Tailwind, NO Vite, NO Next.js.
- **CSS Architecture:** Overwrite or upgrade the existing custom CSS (`css/` files). Create a robust set of CSS variables (`--primary`, `--accent`, etc.) based on the Visionary Hope preset. Use modern layout modules like Flexbox and Grid.
- **Backend:** Maintain exact existing PHP file structures, POST/GET endpoints, form names, and data loading mechanisms. All dynamic data fetching must still work seamlessly.
- **No placeholders:** Use the CURRENT content from the existing site. Do not invent filler text. Use the current images.

---

## Build Sequence

After analyzing the current repository and receiving the user's answers:

1. **Token Mapping:** Define the "Visionary Hope" preset into a new global CSS variables block (`:root`) in the site's main CSS files.
2. **HTML/PHP Refactoring:** Systematically rewrite `index.html` and other targeted PHP/HTML pages to wrap existing content into the new elegant semantic structure (Hero, Features, Impact). 
3. **Styles Overhaul:** Overwrite the custom `css/` files to match the cinematic design system using vanilla CSS.
4. **JS Integration:** Embed GSAP via CDN in the HTML/PHP `<head>` or before `</body>`, and write animation logic in the `js/` folder targeting the newly classed HTML structures.
5. **Form Safekeeping:** Carefully style all PHP-driven forms (volunteer, contact, admin) without altering their `name` attributes, `action` URLs, validation logic, or standard submission behaviors.
6. **Deploy / Test:** Verify that the UI looks cinematic and the GoDaddy backend still processes natively.

**Execution Directive:** "Do not just restyle the website; translate its non-profit mission into a digital instrument of hope. Every scroll should feel intentional, every animation should feel weighted and professional. Eradicate all generic AI patterns, while preserving 100% of the underlying PHP/HTML/JS GoDaddy constraints."
