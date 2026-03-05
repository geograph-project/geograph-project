/**
 * Global application state management
 */

const AppState = {
    isInnerPage: false,
    currentRoute: '/app/',
    pageTitle: 'PMA',

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
        if (this.isInnerPage) {
            document.body.classList.add('is-inner');
        } else {
            document.body.classList.remove('is-inner');
        }

        const titleEl = document.getElementById('page-title');
        if (titleEl) {
            titleEl.textContent = this.pageTitle;
        }
    }
};

export default AppState;
