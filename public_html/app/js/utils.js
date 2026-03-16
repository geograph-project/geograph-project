export function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
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
