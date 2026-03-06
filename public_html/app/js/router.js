import AppState from '/app/js/app-state.js';

/**
 * Simple client-side router
 */
class Router {
    constructor(routes = {}, base = '/app/') {
        this.routes = routes;
        this.base = base;
        this.appContainer = document.getElementById('app-container');
        this.iframeContainer = document.getElementById('iframe-container');
        this.iframes = {}; // Cache for persistent iframes

        // Handle navigation events
        window.addEventListener('popstate', (e) => this.handleNavigation(window.location.pathname));

        // Intercept link clicks
        document.body.addEventListener('click', (e) => {
            const link = e.target.closest('[data-route]');
            if (link) {
                e.preventDefault();
                const path = link.getAttribute('data-route');

                const param = link.getAttribute('data-param');
                const message = link.getAttribute('data-message');
                this.navigate(path, { param, message });

            }
        });

        // Listen for navigation requests from iframes
        window.addEventListener('request-navigation', (e) => {
            const { path, options } = e.detail;
            this.navigate(path, options);
        });
    }

    /**
     * @param {Object} options - { param: 'id=19', message: 'load(19)' }
     */
    navigate(path, options = {}) {
        // We push state even if path is same, if params changed
        window.history.pushState(options, '', path);
        this.handleNavigation(path, options);
    }

    /**
     * Handles the view rendering logic for a path
     * @param {string} path
     */
    async handleNavigation(path, options = {}) {
        let relativePath = path.startsWith(this.base) ? path.slice(this.base.length) : path;
        if (relativePath.startsWith('/')) relativePath = relativePath.slice(1);
        if (relativePath === '') relativePath = 'home';

        const route = this.routes[relativePath] || this.routes['home'];

        // UI Updates
        const menuOverlay = document.getElementById('menu-overlay');
        if (menuOverlay) menuOverlay.classList.add('hidden');

        AppState.setState({
            isInnerPage: relativePath !== 'home',
            currentRoute: path,
            pageTitle: route.title || 'PMA'
        });

        if (!route.isIframe) {
            this.renderModule(route, options);
        } else {
            this.appContainer.classList.add('hidden');
            // Pass the options to the iframe handler
            this.showIframe(relativePath, route.url, options);
        }
    }

    async renderModule(route, options = {}) {
            this.appContainer.innerHTML = '<div class="loading">Loading...</div>';
            this.hideAllIframes();
            this.appContainer.classList.remove('hidden');

            try {
                const module = await import(route.module);
                const html = await module.render(options);
                this.appContainer.innerHTML = html;
                if (module.onMount) module.onMount(options);
            } catch (err) {
                console.error('Failed to load module:', err);
                this.appContainer.innerHTML = '<div class="error">View failed to load.</div>';
            }
    }

    /**
     * Manages persistent iframes
     */
    showIframe(id, baseUrl, options = {}) {
        this.hideAllIframes();

        let iframe = this.iframes[id];

        // 1. URL Param Logic: If data-param exists, we force a URL update/reload
        let finalUrl = baseUrl;
        if (options.param) {
            const separator = baseUrl.includes('?') ? '&' : '?';
            finalUrl = `${baseUrl}${separator}${options.param}`;
        }

        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.src = finalUrl;
            iframe.id = `iframe-${id}`;
            this.iframeContainer.appendChild(iframe);
            this.iframes[id] = iframe;
        } else if (options.param) {
            // Only update src if params are actually provided
            iframe.src = finalUrl;
        }

        iframe.style.display = 'block';
        iframe.classList.add('active');

        // 2. postMessage Logic
        if (options.message) {
            this.sendMessageToIframe(iframe, options.message);
        }
    }

    hideAllIframes() {
        Object.values(this.iframes).forEach(iframe => {
            iframe.classList.remove('active');
            iframe.style.display = 'none';
        });
    }

    /**
     * Ensures message is sent only when iframe is ready
     */
    sendMessageToIframe(iframe, message) {
        const deliver = () => {
            // Ensure targetOrigin is restricted in production for security
            iframe.contentWindow.postMessage(message, '*');
        };

        // If iframe is still loading, wait for it
        if (iframe.contentDocument && iframe.contentDocument.readyState !== 'complete') {
            iframe.onload = () => {
                deliver();
                iframe.onload = null; // Clean up
            };
        } else {
            // Iframe is already loaded, send immediately
            deliver();
        }
    }

    /**
     * Initializes the router on page load
     */
    init() {
        this.handleNavigation(window.location.pathname);
    }
}

export default Router;
