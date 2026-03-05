/**
 * Settings View Module
 */
import AppState from '/app/js/app-state.js';

export function render() {
    return `
        <div class="view settings-view">
            <h2>Settings</h2>
            <div class="settings-group">
                <div class="setting-item">
                    <label for="dark-mode-toggle">Dark Mode</label>
                    <input type="checkbox" id="dark-mode-toggle">
                </div>
            </div>
            <p>Adjust your preferences for the PMA application.</p>
        </div>
    `;
}

export function onMount() {
    const toggle = document.getElementById('dark-mode-toggle');
    if (toggle) {
        // Initial state
        toggle.checked = AppState.settings.darkMode;

        // Handle change
        toggle.addEventListener('change', (e) => {
            AppState.updateSettings({ darkMode: e.target.checked });
        });
    }
}
