/**
 * Settings View Module
 */
import AppState from '/app/js/app-state.js';

export function render() {
    return `
        <div class="view settings-view">
            <h2>Settings</h2>
            <p>Adjust your preferences for the Geograph application.</p>

            <div class="settings-group">
                <div class="setting-item">
                    <label for="dark-mode-toggle">Dark Mode</label>
                    <input type="checkbox" id="dark-mode-toggle">
                </div>
            </div>

            <div class="settings-group">
                <div class="setting-item">
                    <label for="uploadMaxSize">Maximum Size to release: (pixels)</label>
                    <select id="uploadMaxSize" style="max-width:110px">
	                <option value="640">640 x 640 (the minimum size)</option>
        	        <option value="800">800 x 800</option>
	                <option value="1024">1024 x 1024 (recommended, if don't want to release full)</option>
        	        <option value="1600">1600 x 1600</aoption>
                	<option value="65536">As uploaded (release full resolution)</option>
		    </select>
                </div>
            </div>

        </div>
    `;
}

export function onMount() {
    const toggle = document.getElementById('dark-mode-toggle');
    const select = document.getElementById('uploadMaxSize');
    if (toggle) {
        // Initial state
        toggle.checked = AppState.settings.darkMode;
        select.value = AppState.settings.uploadMaxSize;

        // Handle change
        toggle.addEventListener('change', (e) => {
            AppState.updateSettings({ darkMode: e.target.checked });
        });
        select.addEventListener('change', (e) => {
            AppState.updateSettings({ uploadMaxSize: e.target.value });
        });
    }
}
