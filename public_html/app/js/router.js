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

        const newState = {
            isInnerPage: relativePath !== 'home',
            currentRoute: path,
            pageTitle: route.title || 'PMA'
        };
        if (options.message) {
            try {
                const data = JSON.parse(options.message);
                if (data.transfer_id) {
                    newState.upload_id = data.transfer_id;
                }
            } catch (e) {
                console.warn("Failed to parse navigation options message", e);
            }
        }
        AppState.setState(newState);

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

	if (id === 'map' && AppState.getPreference('mapAutoLocate',false))
		options.param = "locate=1";

        // 1. URL Param Logic: If data-param exists, we force a URL update/reload
        let finalUrl = baseUrl;
        if (options.param) {
            const separator = baseUrl.includes('?') ? '&' : '?';
            finalUrl = `${baseUrl}${separator}${options.param}`;
        }

        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.setAttribute('allow', 'geolocation');

            // Mark as "loading" before we change the source
            iframe.dataset.isTransitioning = "true";
            iframe.addEventListener('load', () => {
                iframe.dataset.isTransitioning = "false";

                // Always send the latest settings to the iframe upon load
                iframe.contentWindow.postMessage(JSON.stringify({ settings: AppState.settings }), '*');

                // Process any pending messages once loaded
                if (iframe.dataset.pendingMessage) {
                    iframe.contentWindow.postMessage(iframe.dataset.pendingMessage, '*');
                    iframe.dataset.pendingMessage = ""; // Clear
                }
            });

            iframe.src = finalUrl;
            iframe.id = `iframe-${id}`;
            this.iframeContainer.appendChild(iframe);
            this.iframes[id] = iframe;

        } else if (baseUrl == '/app/submit.php' && AppState.newSubmission) {
            //we need to explicitly force it to refresh
            iframe.dataset.isTransitioning = "true";
            iframe.src = finalUrl;

            AppState.setState({ newSubmission: false });

        } else if (options.param) {
            iframe.dataset.isTransitioning = "true";

            // Only update src if params are actually provided
            iframe.src = finalUrl;
        }

        iframe.style.display = 'block';
        iframe.classList.add('active');

        // Always send the latest settings to the iframe when it becomes active
        if (iframe.dataset.isTransitioning !== 'true') {
            iframe.contentWindow.postMessage(JSON.stringify({ settings: AppState.settings }), '*');
        }

        // 2. postMessage Logic
        if (options.message) {
            if (iframe.dataset.isTransitioning === 'true') {
                iframe.dataset.pendingMessage = options.message;
            } else {
                iframe.contentWindow.postMessage(options.message, '*');
            }
        }
    }

    hideAllIframes() {
        Object.values(this.iframes).forEach(iframe => {
            iframe.classList.remove('active');
            iframe.style.display = 'none';
        });
    }

    /**
     * Initializes the router on page load
     */
    init() {
        this.handleNavigation(window.location.pathname);
    }
}

export default Router;
