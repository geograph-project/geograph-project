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
        uploadMaxDimension: 65536,
	showBottomButtons: false,
	shrinkHeader: false
    },

    /**
     * Initializes state from localStorage or system preferences
     */
    init() {
        const savedSettings = localStorage.getItem('pma_settings');
        if (savedSettings) {
            this.settings = JSON.parse(savedSettings);
        } else {
	    //some dynamic defaults

            // Default to system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.settings.darkMode = prefersDark;

            // Sync uploadMaxDimension from server-provided preference
            if (window.GEOGRAPH_USER_PREFERENCES && window.GEOGRAPH_USER_PREFERENCES.uploadMaxDimension) {
                this.settings.uploadMaxDimension = parseInt(window.GEOGRAPH_USER_PREFERENCES.uploadMaxDimension, 10);
		//becaused saved in smallint(5) unsigned, its actully reported as 65535!! (we need 65536)
		if (this.settings.uploadMaxDimension > 65530)
		    this.settings.uploadMaxDimension = 65536;
            }
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
     * Checks if a specific preference exists in the settings
     */
    hasPreference(key) {
        return Object.prototype.hasOwnProperty.call(this.settings, key);
    },

    /**
     * Gets a preference by key with an optional default value
     */
    getPreference(key, defaultValue = null) {
        return this.hasPreference(key) ? this.settings[key] : defaultValue;
    },

    /**
     * Updates a single preference and persists the change
     */
    setPreference(key, value) {
        this.updateSettings({ [key]: value });
    },

    /**
     * Deletes a specific preference and updates storage
     */
    removePreference(key) {
        if (this.hasPreference(key)) {
            delete this.settings[key];
            // Persist the version without the deleted key
            localStorage.setItem('pma_settings', JSON.stringify(this.settings));
            this.syncWithDOM();
        }
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
        if (this.isInnerPage && this.settings.shrinkHeader) {
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

	document.getElementById('bottom-buttons').classList.toggle('hidden', !this.settings.showBottomButtons);

	//the button in footer
	document.getElementById('btn-resume').classList.toggle('hidden', !this.upload_id || this.upload_id === 'none');

        const titleEl = document.getElementById('page-title');
        if (titleEl) {
            titleEl.textContent = this.pageTitle;
        }
    }
};

export default AppState;
