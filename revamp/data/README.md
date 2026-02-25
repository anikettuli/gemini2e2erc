# How to Update Website Content

This folder contains the data files that power the dynamic content on the website.

## 1. Events (`events.json`)
This file controls the calendar and upcoming events list.
- **Format:** List of event objects.
- **Fields:** `id`, `title`, `date` (YYYY-MM-DD), `time`, `location`, `description`, `image`, `people` (current count), `maxPeople`.

## 2. Board Members (`board.json`)
This file controls the "Board of Directors" section on the About page.
- **Format:** List of objects.
- **Fields:**
    - `name`: Name of the board member.
    - `image`: Path to their photo (e.g., "images/board/Jim-Cook.jpg").

## 3. Partners (`partners.json`)
This file controls the "Our Partners & Friends" list on the Get Involved page.
- **Format:** Simple list of strings.
- **Example:** `["Partner A", "Partner B", "Partner C"]`

## 4. Testimonials (`testimonials.json`)
This file controls the testimonials section on the Home page.
- **Format:** List of objects.
- **Fields:**
    - `text`: The quote itself.
    - `author`: Name of the person.
    - `organization`: Their organization or title.

## 5. Locations (`locations.json`)
This file controls the "Drop-Off Locations" map and list.
- **Format:** List of objects.
- **Fields:**
    - `name`: Name of the location (e.g., "Advanced Vision Care").
    - `address`: Full address (e.g., "4919 South Hulen Street, Fort Worth, TX 76132").
    - `phone`: Phone number (e.g., "(817) 370-2100").
- **Note:** You do NOT need to provide coordinates (`lat`/`lng`). The website automatically calculates them using the address (via US Census Bureau API).

## Signups File
- **Do NOT edit `signups.json`**. This file is automatically updated when people sign up on the website.
