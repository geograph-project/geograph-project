/**
 * Global application state management
 */

const AppState = {
    isInnerPage: false,
    currentRoute: '/app/',
    pageTitle: 'Geograph',
    settings: {
        darkMode: false,
	imagesPerScreen: 16,
	uploadMaxDimension: 65536 //effectively unlimited!
    },

    /**
     * Initializes state from localStorage or system preferences
     */
    init() {
        const savedSettings = localStorage.getItem('pma_settings');
        if (savedSettings) {
            this.settings = JSON.parse(savedSettings);
        } else {
            // Default to system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.settings.darkMode = prefersDark;
	    //todo, uploadMaxDimension needs syncing from $USER->upload_size
        }
        this.syncWithDOM();
    },

    /**
     * Updates settings and persists them
     */
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        localStorage.setItem('pma_settings', JSON.stringify(this.settings));
        this.syncWithDOM();
    },

    /**
     * Updates the global state and synchronizes with the DOM if necessary
     */
    setState(newState) {
        Object.assign(this, newState);
        this.syncWithDOM();
    },

    /**
     * Synchronizes state with CSS/DOM
     */
    syncWithDOM() {
        // Handle bar sizing
        if (this.isInnerPage) {
            document.body.classList.add('is-inner');
        } else {
            document.body.classList.remove('is-inner');
        }

        // Handle Dark Mode
        if (this.settings.darkMode) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }

        const titleEl = document.getElementById('page-title');
        if (titleEl) {
            titleEl.textContent = this.pageTitle;
        }
    }
};

export default AppState;
