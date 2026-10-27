// Auto-detect API base URL for frontend pages (supports Live Server, WAMP, and production)
// Priority: 1) window.BASE_URL from PHP config, 2) VS Code Live Server detection, 3) auto-derive from URL
(function () {
    // If PHP has already injected a BASE_URL, use it (most reliable for production)
    if (window.BASE_URL) {
        window.PROJECT_BASE = window.BASE_URL.replace(/\/+$/, '');
        window.API_URL = (window.BASE_URL + '/backend/api').replace(/\/{2,}/g, '/');
        return;
    }

    const host = window.location.hostname;
    const port = window.location.port;

    const isLocalHost = host === '127.0.0.1' || host === 'localhost';

    // Case 1: Developing with VS Code Live Server (e.g., http://127.0.0.1:5500)
    // Need to point explicitly to the WAMP PHP backend that serves the API.
    if (isLocalHost && port === '5500') {
        window.BASE_URL = 'http://localhost/mombasahamlets_web';
        window.PROJECT_BASE = 'http://localhost/mombasahamlets_web';
        window.API_URL = 'http://localhost/mombasahamlets_web/backend/api';
        return;
    }

    /**
     * Derive the project root (e.g., /mombasahamlets_web) no matter how the site is hosted.
     * Works for:
     *  - http://localhost/mombasahamlets_web/frontend/index.php
     *  - http://example.com/frontend/index.php
     *  - Any subdirectory deployment.
     */
    function deriveProjectBase() {
        const scriptSrc = document.currentScript ? document.currentScript.src : '';
        const fromScript = (() => {
            if (!scriptSrc) return '';
            const url = new URL(scriptSrc, window.location.origin);
            const segments = url.pathname.split('/').filter(Boolean);
            const frontendIndex = segments.lastIndexOf('frontend');
            if (frontendIndex > 0) {
                return '/' + segments.slice(0, frontendIndex).join('/');
            }
            // Handle deployments where "frontend" is at the root (index 0)
            if (frontendIndex === 0) {
                return '';
            }
            return segments.length ? '/' + segments.slice(0, segments.length - 2).join('/') : '';
        })();

        if (fromScript) return fromScript;

        const pathSegments = window.location.pathname.split('/').filter(Boolean);
        const frontendIndex = pathSegments.lastIndexOf('frontend');
        if (frontendIndex > 0) {
            return '/' + pathSegments.slice(0, frontendIndex).join('/');
        }
        return frontendIndex === 0 ? '' : '/' + pathSegments.join('/');
    }

    const basePath = deriveProjectBase().replace(/\/+$/, '');
    window.PROJECT_BASE = window.location.origin + basePath;
    window.BASE_URL = window.PROJECT_BASE;

    // Case 2 & 3: Served from WAMP (localhost) or production server
    window.API_URL = (window.PROJECT_BASE + '/backend/api').replace(/\/{2,}/g, '/');
})();
