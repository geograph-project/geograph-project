/**
 * Settings View Module
 */
import AppState from '/app/js/app-state.js';

export function render() {
    return `
        <div class="view settings-view">
            <h2>Settings</h2>
            <p>Adjust your preferences for the Geograph application.<br><br></p>

            <div class="settings-group">
                <div class="setting-item">
                    <label for="darkMode">Dark Mode</label>
                    <input type="checkbox" id="darkMode">
                </div>
            </div>

            <div class="settings-group">
                <div class="setting-item">
                    <label for="uploadMaxDimension">Maximum Resolution to release: (pixels)</label>
                    <select id="uploadMaxDimension" style="max-width:110px">
	                <option value="640">640 x 640 (the minimum size)</option>
        	        <option value="800">800 x 800</option>
	                <option value="1024">1024 x 1024 (recommended, if don't want to release full)</option>
        	        <option value="1600">1600 x 1600</aoption>
                	<option value="65536">As uploaded (release full resolution)</option>
		    </select>
                </div>
            </div>

            <div class="settings-group">
                <div class="setting-item">
                    <label for="showBottomButtons">Show Navigation Buttons</label>
                    <input type="checkbox" id="showBottomButtons">
                </div>
                <div class="setting-item">
                    <label for="shrinkHeader">Small Header/Footer</label>
                    <input type="checkbox" id="shrinkHeader">
                </div>
            </div>

        </div>
    `;
}

export function onMount() {
    const inputs = document.querySelectorAll('.setting-item input, .setting-item select');

    inputs.forEach(input => {
        const key = input.id;
        const settingValue = AppState.settings[key];

        // 1. Set Initial State
        if (settingValue !== undefined) {
            if (input.type === 'checkbox') {
                input.checked = settingValue;
            } else {
                input.value = settingValue;
            }
        }

        // 2. Attach Universal Listener
        input.addEventListener('change', (e) => {
            const value = e.target.type === 'checkbox' 
                ? e.target.checked 
                : e.target.value;

            // Dynamically update the key that matches the ID
            AppState.updateSettings({ [key]: value });
        });
    });
}
