import AppState from './app-state.js';

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
                this.navigate(path);
            }
        });
    }

    /**
     * Navigates to a specific path
     * @param {string} path
     */
    navigate(path) {
        if (window.location.pathname === path) return;

        window.history.pushState({}, '', path);
        this.handleNavigation(path);
    }

    /**
     * Handles the view rendering logic for a path
     * @param {string} path
     */
    async handleNavigation(path) {
        // Normalize path to remove base
        let relativePath = path.startsWith(this.base) ? path.slice(this.base.length) : path;
        if (relativePath.startsWith('/')) relativePath = relativePath.slice(1);
        if (relativePath === '') relativePath = 'home';

        const route = this.routes[relativePath] || this.routes['home'];

        // Close menu if open
        const menuOverlay = document.getElementById('menu-overlay');
        if (menuOverlay) menuOverlay.classList.add('hidden');

        // Update state
        AppState.setState({
            isInnerPage: relativePath !== 'home',
            currentRoute: path,
            pageTitle: route.title || 'PMA'
        });

        // Clear app container for non-iframe routes
        if (!route.isIframe) {
            this.appContainer.innerHTML = '<div class="loading">Loading...</div>';
            this.hideAllIframes();
            this.appContainer.classList.remove('hidden');

            try {
                const module = await import(route.module);
                const html = await module.render();
                this.appContainer.innerHTML = html;
                if (module.onMount) module.onMount();
            } catch (err) {
                console.error('Failed to load module:', err);
                this.appContainer.innerHTML = '<div class="error">View failed to load.</div>';
            }
        } else {
            // Handle persistent iframe
            this.appContainer.classList.add('hidden');
            this.showIframe(relativePath, route.url);
        }
    }

    /**
     * Manages persistent iframes
     */
    showIframe(id, url) {
        this.hideAllIframes();

        if (!this.iframes[id]) {
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.id = `iframe-${id}`;
            this.iframeContainer.appendChild(iframe);
            this.iframes[id] = iframe;
        }

        this.iframes[id].classList.add('active');
        this.iframes[id].style.display = 'block';
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
