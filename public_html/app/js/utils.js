//useful functions for the app, but also standalone iframes, for communicating with the app

export function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

export function escapeRegex(string) {
    // This replaces special regex characters with their escaped version
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

//actully seems like best way to request navigation is via an event!
export function navigateTo(path, options) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;

    const event = new CustomEvent('request-navigation', {
        detail: { path, options },
        bubbles: true
    });
    target.dispatchEvent(event);
}

export function updateAppState(detail) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;
    const event = new CustomEvent('update-app-state', {
        detail,
        bubbles: true
    });
    target.dispatchEvent(event);
}

export function setupSettingsListener() {
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;
       	try {
            const data = JSON.parse(event.data);
            if (data.settings) {
                document.body.classList.toggle('dark-mode', data.settings.darkMode);
            }
            if (data.settings && data.settings.uploadMaxDimension) {
                window.uploadMaxDimension = parseInt(data.settings.uploadMaxDimension, 10);
            }
        } catch (e) {
    		//just catching if fail to decode JSON
    	}
    });
}

export function openModal(id) {
    const modal = document.getElementById(id);
    modal.showModal();
    modal.scrollTop = 0;
}
export function closeModal(id) {
    document.getElementById(id).close();
}
