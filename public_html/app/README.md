# Geograph PWA App

This directory contains the source code for the Geograph Progressive Web App (PWA). It is designed to provide a mobile-friendly interface for capturing, uploading, and submitting photos to Geograph Britain and Ireland.

## Architecture

The app uses a **Hybrid Shell Architecture**. It combines a modern single-page application (SPA) shell with legacy PHP pages integrated via iframes.

- **Shell**: The main wrapper (`index.php`) provides the navigation UI (header, footer, and drawer menu).
- **Client-Side Routing**: A custom JavaScript router (`js/router.js`) manages navigation without full page reloads.
- **Views**:
    - **Native Modules**: Modern views built as JavaScript modules (`js/modules/*.js`) that render directly into the shell.
    - **Iframe Views**: Legacy PHP pages (e.g., `upload.php`, `capture.php`) or existing site tools (e.g., Mapper, Finder) embedded within the shell.

## Directory Structure

- `assets/`: Static assets including CSS (`css/style.css`).
- `js/`: Core application logic.
    - `modules/`: Native view components (home, profile, settings, etc.).
    - `app-state.js`: Centralized state management and localStorage persistence.
    - `router.js`: Handles URL routing and manages the lifecycle of native modules and iframes.
    - `main.js`: Application entry point and initialization.
    - `sw.js`: Service worker for PWA installability.
- `*.php`:
    - `index.php`: The main entry point. Handles authentication, generates the JS import map, and renders the shell.
    - API Endpoints: PHP scripts that return JSON data (e.g., `submissions.json.php`, `status.json.php`).
    - Iframe Views: PHP scripts intended to be loaded inside the app shell's iframe (e.g., `upload.php`, `submit.php`).

## Core Components

### Routing (`js/router.js`)
The router maps URL paths to either a JavaScript module or an iframe URL. It manages the visibility of the app container versus the iframe container and handles the "Dirty Bit" for forcing refreshes when necessary (e.g., after a new upload).

### State Management (`js/app-state.js`)
A global `AppState` object tracks current route, page title, and user preferences (dark mode, layout settings). It synchronizes state changes to the DOM (via CSS classes) and persists settings to `localStorage`.

### Shell-Iframe Communication
Communication between the SPA shell and embedded iframes is handled via `window.postMessage`.
- **Shell to Iframe**: Used to sync user settings (like dark mode) to the iframe content.
- **Iframe to Shell**: Used to request navigation (`request-navigation` event) or update global app state (`update-app-state` event).

## Development

### Adding a New Route
1. Create a new module in `js/modules/` if it's a native view.
2. Register the route in `js/main.js` within the `routes` object.
3. Add a navigation link with the `data-route` attribute (e.g., `<button data-route="/app/my-view">`).

### Authentication
The app requires the `basic` permission. `index.php` handles session initialization and forces a login if a valid session or autologin cookie is not present.

### Versioning
JavaScript and CSS files are versioned in `index.php` using the `pma_revision` function, which appends a timestamp or a revision number to the URL to bypass browser caching.
