<?php
declare(strict_types=1);

/**
 * Official Lucide v1.73.0 SVG icon registry and renderer.
 * Extracted verbatim from lucide-static@1.73.0.
 */

const ERC_LUCIDE_VERSION = '1.73.0';

/**
 * @return array<string, string>
 */
function erc_icon_registry(): array
{
    static $icons = [
        'alert-triangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" /> <path d="M12 9v4" /> <path d="M12 17h.01" />',
        'arrow-left' => '<path d="m12 19-7-7 7-7" /> <path d="M19 12H5" />',
        'arrow-right' => '<path d="M5 12h14" /> <path d="m12 5 7 7-7 7" />',
        'arrow-up-right' => '<path d="M7 7h10v10" /> <path d="M7 17 17 7" />',
        'award' => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526" /> <circle cx="12" cy="8" r="6" />',
        'ban' => '<circle cx="12" cy="12" r="10" /> <path d="M4.929 4.929 19.07 19.071" />',
        'book-open' => '<path d="M12 5v16" /> <path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z" />',
        'building-2' => '<path d="M10 12h4" /> <path d="M10 8h4" /> <path d="M14 21v-3a2 2 0 0 0-4 0v3" /> <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2" /> <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16" />',
        'calendar' => '<path d="M8 2v3" /> <path d="M16 2v3" /> <rect x="3" y="3" width="18" height="18" rx="2" /> <path d="M3 9h18" />',
        'calendar-check' => '<path d="M8 2v3" /> <path d="M16 2v3" /> <rect x="3" y="3" width="18" height="18" rx="2" /> <path d="M3 9h18" /> <path d="m9 15 2 2 4-4" />',
        'calendar-days' => '<path d="M8 2v3" /> <path d="M16 2v3" /> <rect x="3" y="3" width="18" height="18" rx="2" /> <path d="M3 9h18" /> <path d="M8 13h.01" /> <path d="M12 13h.01" /> <path d="M16 13h.01" /> <path d="M8 17h.01" /> <path d="M12 17h.01" /> <path d="M16 17h.01" />',
        'calendar-plus' => '<path d="M16 18h6" /> <path d="M16 2v3" /> <path d="M19 15v6" /> <path d="M21 11.5V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h8.3" /> <path d="M3 9h18" /> <path d="M8 2v3" />',
        'check' => '<path d="M20 6 9 17l-5-5" />',
        'check-circle-2' => '<circle cx="12" cy="12" r="10" /> <path d="m16 9-5.5 5.5L8 12" />',
        'chevron-down' => '<path d="m6 9 6 6 6-6" />',
        'chevron-left' => '<path d="m15 18-6-6 6-6" />',
        'chevron-right' => '<path d="m9 18 6-6-6-6" />',
        'circle-alert' => '<circle cx="12" cy="12" r="10" /> <line x1="12" x2="12" y1="8" y2="12" /> <line x1="12" x2="12.01" y1="16" y2="16" />',
        'circle-check' => '<circle cx="12" cy="12" r="10" /> <path d="m16 9-5.5 5.5L8 12" />',
        'circle-check-big' => '<path d="M21.801 10A10 10 0 1 1 17 3.335" /> <path d="m9 11 3 3L22 4" />',
        'circle-help' => '<circle cx="12" cy="12" r="10" /> <path d="M9 9.5a3 2.5 0 016 0c0 2-2 2-3 3.5" /> <path d="M12 17h.01" />',
        'circle-x' => '<circle cx="12" cy="12" r="10" /> <path d="m15 9-6 6" /> <path d="m9 9 6 6" />',
        'clock' => '<circle cx="12" cy="12" r="10" /> <path d="M12 6v6l4 2" />',
        'copy' => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2" /> <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />',
        'dollar-sign' => '<path d="M12 3v18" /> <path d="M17 5H9.5a1 1 0 000 7h5a1 1 0 010 7H6" />',
        'download' => '<path d="M12 15V3" /> <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /> <path d="m7 10 5 5 5-5" />',
        'ear' => '<path d="M6 8.5a6.5 6.5 0 1 1 13 0c0 6-6 6-6 10a3.5 3.5 0 1 1-7 0" /> <path d="M15 8.5a2.5 2.5 0 0 0-5 0v1a2 2 0 1 1 0 4" />',
        'edit-3' => '<path d="M13 21h8" /> <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />',
        'external-link' => '<path d="M15 3h6v6" /> <path d="M10 14 21 3" /> <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />',
        'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" /> <circle cx="12" cy="12" r="3" />',
        'file-text' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" /> <path d="M14 2v5a1 1 0 0 0 1 1h5" /> <path d="M10 9H8" /> <path d="M16 13H8" /> <path d="M16 17H8" />',
        'filter' => '<path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z" />',
        'funnel' => '<path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z" />',
        'glasses' => '<circle cx="6" cy="15" r="4" /> <circle cx="18" cy="15" r="4" /> <path d="M14 15a2 2 0 0 0-2-2 2 2 0 0 0-2 2" /> <path d="M2.5 13 5 7c.7-1.3 1.4-2 3-2" /> <path d="M21.5 13 19 7c-.7-1.3-1.5-2-3-2" />',
        'globe' => '<circle cx="12" cy="12" r="10" /> <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" /> <path d="M2 12h20" />',
        'hand-heart' => '<path d="M11 14h2a2 2 0 0 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16" /> <path d="m14.45 13.39 5.05-4.694C20.196 8 21 6.85 21 5.75a2.75 2.75 0 0 0-4.797-1.837.276.276 0 0 1-.406 0A2.75 2.75 0 0 0 11 5.75c0 1.2.802 2.248 1.5 2.946L16 11.95" /> <path d="m2 15 6 6" /> <path d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a1 1 0 0 0-2.75-2.91" />',
        'heart' => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />',
        'heart-handshake' => '<path d="M19.414 14.414C21 12.828 22 11.5 22 9.5a5.5 5.5 0 0 0-9.591-3.676.6.6 0 0 1-.818.001A5.5 5.5 0 0 0 2 9.5c0 2.3 1.5 4 3 5.5l5.535 5.362a2 2 0 0 0 2.879.052 2.12 2.12 0 0 0-.004-3 2.124 2.124 0 1 0 3-3 2.124 2.124 0 0 0 3.004 0 2 2 0 0 0 0-2.828l-1.881-1.882a2.41 2.41 0 0 0-3.409 0l-1.71 1.71a2 2 0 0 1-2.828 0 2 2 0 0 1 0-2.828l2.823-2.762" />',
        'help-circle' => '<circle cx="12" cy="12" r="10" /> <path d="M9 9.5a3 2.5 0 016 0c0 2-2 2-3 3.5" /> <path d="M12 17h.01" />',
        'home' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" /> <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />',
        'house' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" /> <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />',
        'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2" /> <circle cx="9" cy="9" r="2" /> <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />',
        'info' => '<circle cx="12" cy="12" r="10" /> <path d="M12 16v-4" /> <path d="M12 8h.01" />',
        'landmark' => '<path d="M10 18v-7" /> <path d="M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z" /> <path d="M14 18v-7" /> <path d="M18 18v-7" /> <path d="M3 22h18" /> <path d="M6 18v-7" />',
        'laptop' => '<path d="M18 5a2 2 0 0 1 2 2v8.526a2 2 0 0 0 .212.897l1.068 2.127a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45l1.068-2.127A2 2 0 0 0 4 15.526V7a2 2 0 0 1 2-2z" /> <path d="M20.054 15.987H3.946" />',
        'list' => '<path d="M3 5h.01" /> <path d="M3 12h.01" /> <path d="M3 19h.01" /> <path d="M8 5h13" /> <path d="M8 12h13" /> <path d="M8 19h13" />',
        'locate-fixed' => '<line x1="2" x2="5" y1="12" y2="12" /> <line x1="19" x2="22" y1="12" y2="12" /> <line x1="12" x2="12" y1="2" y2="5" /> <line x1="12" x2="12" y1="19" y2="22" /> <circle cx="12" cy="12" r="7" /> <circle cx="12" cy="12" r="3" />',
        'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2" /> <path d="M7 11V7a5 5 0 0 1 10 0v4" />',
        'log-out' => '<path d="m16 17 5-5-5-5" /> <path d="M21 12H9" /> <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />',
        'mail' => '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" /> <rect x="2" y="4" width="20" height="16" rx="2" />',
        'map' => '<path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z" /> <path d="M15 5.764v15" /> <path d="M9 3.236v15" />',
        'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /> <circle cx="12" cy="10" r="3" />',
        'menu' => '<path d="M4 5h16" /> <path d="M4 12h16" /> <path d="M4 19h16" />',
        'minus' => '<path d="M5 12h14" />',
        'moon' => '<path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401" />',
        'navigation' => '<polygon points="3 11 22 2 13 21 11 13 3 11" />',
        'package' => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" /> <path d="M12 22V12" /> <polyline points="3.29 7 12 12 20.71 7" /> <path d="m7.5 4.27 9 5.15" />',
        'pencil' => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" /> <path d="m15 5 4 4" />',
        'phone' => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />',
        'plus' => '<path d="M5 12h14" /> <path d="M12 5v14" />',
        'printer' => '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /> <path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6" /> <rect x="6" y="14" width="12" height="8" rx="1" />',
        'recycle' => '<path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5" /> <path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12" /> <path d="m14 16-3 3 3 3" /> <path d="M8.293 13.596 7.196 9.5 3.1 10.598" /> <path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843" /> <path d="m13.378 9.633 4.096 1.098 1.097-4.096" />',
        'refresh-cw' => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" /> <path d="M21 3v5h-5" /> <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" /> <path d="M8 16H3v5" />',
        'scale' => '<path d="M12 3v18" /> <path d="m19 8 3 8a5 5 0 0 1-6 0zV7" /> <path d="M3 7h1a17 17 0 0 0 8-2 17 17 0 0 0 8 2h1" /> <path d="m5 8 3 8a5 5 0 0 1-6 0zV7" /> <path d="M7 21h10" />',
        'search' => '<path d="m21 21-4.34-4.34" /> <circle cx="11" cy="11" r="8" />',
        'send' => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z" /> <path d="m21.854 2.147-10.94 10.939" />',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /> <path d="m9 12 2 2 4-4" />',
        'sliders-horizontal' => '<path d="M10 5H3" /> <path d="M12 19H3" /> <path d="M14 3v4" /> <path d="M16 17v4" /> <path d="M21 12h-9" /> <path d="M21 19h-5" /> <path d="M21 5h-7" /> <path d="M8 10v4" /> <path d="M8 12H3" />',
        'smartphone' => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2" /> <path d="M12 18h.01" />',
        'sparkles' => '<path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z" /> <path d="M20 2v4" /> <path d="M22 4h-4" /> <circle cx="4" cy="20" r="2" />',
        'square-pen' => '<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /> <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />',
        'sun' => '<circle cx="12" cy="12" r="4" /> <path d="M12 2v2" /> <path d="M12 20v2" /> <path d="m4.93 4.93 1.41 1.41" /> <path d="m17.66 17.66 1.41 1.41" /> <path d="M2 12h2" /> <path d="M20 12h2" /> <path d="m6.34 17.66-1.41 1.41" /> <path d="m19.07 4.93-1.41 1.41" />',
        'sun-moon' => '<path d="M12 2v2" /> <path d="M14.837 16.385a6 6 0 1 1-7.223-7.222c.624-.147.97.66.715 1.248a4 4 0 0 0 5.26 5.259c.589-.255 1.396.09 1.248.715" /> <path d="M16 12a4 4 0 0 0-4-4" /> <path d="m19 5-1.256 1.256" /> <path d="M20 12h2" />',
        'trash-2' => '<path d="M10 11v6" /> <path d="M14 11v6" /> <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /> <path d="M3 6h18" /> <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />',
        'triangle-alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" /> <path d="M12 9v4" /> <path d="M12 17h.01" />',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /> <path d="M15 18H9" /> <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" /> <circle cx="17" cy="18" r="2" /> <circle cx="7" cy="18" r="2" />',
        'upload' => '<path d="M12 3v12" /> <path d="m17 8-5-5-5 5" /> <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />',
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /> <circle cx="9" cy="7" r="4" /> <line x1="19" x2="19" y1="8" y2="14" /> <line x1="22" x2="16" y1="11" y2="11" />',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /> <path d="M16 3.128a4 4 0 0 1 0 7.744" /> <path d="M22 21v-2a4 4 0 0 0-3-3.87" /> <circle cx="9" cy="7" r="4" />',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z" />',
        'x' => '<path d="M18 6 6 18" /> <path d="m6 6 12 12" />',
        'zoom-in' => '<circle cx="11" cy="11" r="8" /> <line x1="21" x2="16.65" y1="21" y2="16.65" /> <line x1="11" x2="11" y1="8" y2="14" /> <line x1="8" x2="14" y1="11" y2="11" />',
    ];
    return $icons;
}

function erc_icon_exists(string $name): bool
{
    $icons = erc_icon_registry();
    return isset($icons[$name]);
}

/**
 * Render an inline Lucide v1.73.0 SVG icon.
 *
 * Defaults to decorative (aria-hidden="true") unless aria-label,
 * aria-labelledby, or explicit aria-hidden is supplied in $attrs.
 *
 * @param string $name Lucide icon slug (e.g., 'glasses', 'map-pin', 'calendar')
 * @param array<string, string|int|float|bool|null> $attrs Extra SVG attributes
 */
function erc_icon(string $name, array $attrs = []): string
{
    $icons = erc_icon_registry();
    if (!isset($icons[$name])) {
        return '';
    }

    $baseClass = 'erc-icon erc-icon-' . $name;
    if (isset($attrs['class']) && is_string($attrs['class']) && trim($attrs['class']) !== '') {
        $attrs['class'] = $baseClass . ' ' . trim($attrs['class']);
    } else {
        $attrs['class'] = $baseClass;
    }

    $defaults = [
        'xmlns' => 'http://www.w3.org/2000/svg',
        'width' => '24',
        'height' => '24',
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '2',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'focusable' => 'false',
    ];

    $merged = array_merge($defaults, $attrs);

    if (
        !array_key_exists('aria-hidden', $merged)
        && !array_key_exists('aria-label', $merged)
        && !array_key_exists('aria-labelledby', $merged)
    ) {
        $merged['aria-hidden'] = 'true';
    }

    $attrParts = [];
    foreach ($merged as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        $cleanKey = preg_replace('/[^a-zA-Z0-9:_-]/', '', (string)$key);
        if ($cleanKey === '') {
            continue;
        }
        $strVal = $value === true ? 'true' : (string)$value;
        $attrParts[] = $cleanKey . '="' . htmlspecialchars($strVal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
    }

    return '<svg ' . implode(' ', $attrParts) . '>' . $icons[$name] . '</svg>';
}
