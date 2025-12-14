# Lions District 2-E2 Eyeglass Recycling Center (ERC) Website

A modern, responsive, and accessible website for the District 2-E2 Eyeglass Recycling Center. This project serves as the digital hub for volunteer coordination, donation information, and event management.

## 🛠 Technical Stack

### Frontend
- **HTML5**: Semantic markup with accessibility (WCAG AAA) in mind.
- **CSS3**: Custom styling with CSS Variables for theming (Light/Dark mode).
- **JavaScript (ES6+)**: Vanilla JS for DOM manipulation, routing, and dynamic content loading.
- **MapLibre GL JS**: Open-source mapping library for displaying drop-off locations.

### Backend
- **PHP**: Server-side scripting for form processing and administrative functions.
    - `register-volunteer.php`: Handles volunteer signups and stores data in JSON.
    - `send-email.php`: Processes contact form submissions.
    - `admin-view.php`: Protected interface for viewing volunteer data.
- **JSON**: Flat-file database structure for storing content and form submissions.

### Data Architecture
Content is decoupled from the HTML structure and stored in the `data/` directory:
- `events.json`: Calendar events.
- `board.json`: Board of Directors profiles.
- `partners.json`: Mission partners.
- `signups.json`: Volunteer registration database (generated).

## 📂 Project Structure

```text
├── admin-view.php          # Admin dashboard for volunteer management
├── config.php              # Server-side configuration
├── index.html              # Single Page Application (SPA) entry point
├── register-volunteer.php  # Volunteer form processor
├── send-email.php          # Contact form processor
├── css/
│   └── styles.css          # Main stylesheet (includes Dark Mode)
├── data/                   # JSON data stores
│   ├── board.json
│   ├── events.json
│   └── signups.json        # Writable file for volunteer data
├── js/
│   ├── app.js              # Core logic: Routing, Theme, UI
│   ├── calendar.js         # Event calendar rendering logic
│   ├── config.js           # Client-side configuration (Email, Phone)
│   ├── dynamic-content.js  # JSON fetcher and renderer
│   └── locations.js        # MapLibre configuration and location data
└── images/                 # Optimized assets
```

## 🌟 Key Functionality

### 1. Hash-Based Routing
The site uses a lightweight SPA architecture. Navigation is handled via URL hashes (e.g., `#home`, `#get-involved`). `app.js` listens for hash changes to toggle visibility of content sections without page reloads.

### 2. Dynamic Content Loading
To allow non-technical updates, content sections like the Board of Directors, Partners, and Events are fetched from JSON files in the `data/` folder and rendered client-side by `dynamic-content.js`.

### 3. Volunteer Management System
- **Registration**: Users submit the volunteer form (`#get-involved`).
- **Processing**: `register-volunteer.php` validates inputs and appends the entry to `data/signups.json`.
- **Administration**: `admin-view.php` provides a table view of all signups. *Note: Ensure this file is secured in production.*

### 4. Interactive Map
The Locations tab features an interactive map powered by MapLibre GL. It renders markers for drop-off locations defined in `js/locations.js`.

### 5. Dark Mode
A system-aware theme toggle allows users to switch between Light and Dark modes. Preferences are persisted in `localStorage`.

## 🚀 Setup & Deployment

### Prerequisites
- A web server with **PHP 7.4+** support (Apache/Nginx).
- Write permissions for the `data/` directory (for `signups.json`).

### Local Development
1. Clone the repository.
2. Start a local PHP server:
   ```bash
   php -S localhost:8000
   ```
3. Open `http://localhost:8000` in your browser.

### Production Deployment
1. Upload all files to the public web directory.
2. **Security**: Ensure `data/signups.json` is protected from direct public access via `.htaccess` or server config, while remaining writable by the PHP script.
3. **Permissions**: Set write permissions (chmod 664 or 775) on `data/signups.json` so the server can save volunteer registrations.

## ⚙️ Configuration

- **Client-side**: Edit `js/config.js` to update global contact info (Email, Phone).
- **Server-side**: Edit `config.php` for email sending settings (SMTP, headers).
