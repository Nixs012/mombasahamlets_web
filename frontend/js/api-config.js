// Auto-detect API base URL for frontend pages (supports Live Server, WAMP, and production)
(function () {
    const host = window.location.hostname;
    const port = window.location.port;

    const isLocalHost = host === '127.0.0.1' || host === 'localhost';

    // Case 1: Developing with VS Code Live Server (e.g., http://127.0.0.1:5500)
    // Need to point explicitly to the WAMP PHP backend that serves the API.
    if (isLocalHost && port === '5500') {
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
    window.PROJECT_BASE = basePath;

    // Case 2 & 3: Served from WAMP (localhost) or production server
    window.API_URL = `${basePath}/backend/api`.replace(/\/{2,}/g, '/');
})();
