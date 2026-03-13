import AppState from '/app/js/app-state.js';
import Router from '/app/js/router.js';
import { injectIcons } from '/app/js/icons.js';

/**
 * Main application entry point
 */
function init() {
    // Injected icons
    injectIcons();

    // Setup routes
    const routes = {
        'home': { module: '/app/js/modules/home-view.js', title: 'Geograph' },
        'search': { module: '/app/js/modules/search-view.js', title: 'Search' },
        'results': { isIframe: true, url: '/finder/finder.php?inner=1', title: 'Results' },
        'map': { isIframe: true, url: '/mapper/combined.php?mobile=1&inner=1', title: 'Map' },

        'upload': { isIframe: true, url: '/app/upload.php', title: 'Upload' },
        'uploaded': { module: '/app/js/modules/uploaded-view.js', title: 'Submit' },
        'submit': { isIframe: true, url: '/app/submit.php', title: 'Submission' },

        'home2': { isIframe: true, url: '/app/home.php', title: 'Geograph' },
        'capture': { isIframe: true, url: '/app/capture.php', title: 'Capture (alpha)' },

        'profile': { module: '/app/js/modules/profile-view.js', title: 'Profile' },
        'recent': { module: '/app/js/modules/recent-view.js', title: 'Recent Submissions' },

        'settings': { module: '/app/js/modules/settings-view.js', title: 'Settings' },
        'help': { module: '/app/js/modules/help-view.js', title: 'Help' },
        'contact': { module: '/app/js/modules/contact-view.js', title: 'Contact' },
        'tos': { module: '/app/js/modules/tos-view.js', title: 'Terms' }
    };

    const router = new Router(routes);

    // Initial state sync
    AppState.init();

    // Menu toggle logic
    const menuBtn = document.getElementById('btn-menu');
    const menuOverlay = document.getElementById('menu-overlay');

    if (menuBtn && menuOverlay) {
        menuBtn.addEventListener('click', () => {
            menuOverlay.classList.toggle('hidden');
        });

        // Close menu when clicking outside the drawer
        menuOverlay.addEventListener('click', (e) => {
            if (e.target === menuOverlay) {
                menuOverlay.classList.add('hidden');
            }
        });
    }

    // Initialize router
    router.init();

    // Service Worker Boilerplate (Placeholder)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/app/js/sw.js')
             .then(reg => console.log('Service Worker registered', reg))
             .catch(err => console.error('Service Worker registration failed', err));
    }
}

// Start the application
document.addEventListener('DOMContentLoaded', init);
