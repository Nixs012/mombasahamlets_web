// Auto-detect API base URL for admin/backend pages (supports WAMP, LAN, and production)
(function () {
    function deriveBackendBase() {
        const scriptSrc = document.currentScript ? document.currentScript.src : '';

        if (scriptSrc) {
            const url = new URL(scriptSrc, window.location.origin);
            const segments = url.pathname.split('/').filter(Boolean);
            const backendIndex = segments.lastIndexOf('backend');
            if (backendIndex >= 0) {
                return '/' + segments.slice(0, backendIndex + 1).join('/');
            }
        }

        const pathSegments = window.location.pathname.split('/').filter(Boolean);
        const backendIndex = pathSegments.lastIndexOf('backend');
        if (backendIndex >= 0) {
            return '/' + pathSegments.slice(0, backendIndex + 1).join('/');
        }

        return '/backend';
    }

    const backendBase = deriveBackendBase().replace(/\/+$/, '');
    window.API_URL = `${backendBase}/api`.replace(/\/{2,}/g, '/');

    // Global fetch interceptor to automatically append CSRF token and credentials
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        let [resource, config] = args;
        
        // Ensure config exists
        config = config || {};
        
        // Only modify requests to our API
        if (typeof resource === 'string' && resource.includes(window.API_URL)) {
             // 1. Ensure credentials are sent (for sessions)
            config.credentials = 'include';
            
            // 2. Append CSRF Token if it's a mutating request
            const method = (config.method || 'GET').toUpperCase();
            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                if (csrfToken) {
                    if (config.body instanceof FormData) {
                        if (!config.body.has('csrf_token')) {
                            config.body.append('csrf_token', csrfToken);
                        }
                    } else if (typeof config.body === 'string') {
                        try {
                            // Try parsing as JSON
                            const jsonBody = JSON.parse(config.body);
                            jsonBody.csrf_token = csrfToken;
                            config.body = JSON.stringify(jsonBody);
                            
                            config.headers = config.headers || {};
                            if (!config.headers['Content-Type']) {
                                config.headers['Content-Type'] = 'application/json';
                            }
                        } catch (e) {
                            // URL Encoded fallback
                            if (!config.body.includes('csrf_token=')) {
                                config.body += (config.body.length > 0 ? '&' : '') + 'csrf_token=' + encodeURIComponent(csrfToken);
                            }
                        }
                    } else if (!config.body) {
                         // Default empty body to JSON with CSRF token
                        config.body = JSON.stringify({ csrf_token: csrfToken });
                        config.headers = config.headers || {};
                        config.headers['Content-Type'] = 'application/json';
                    }
                }
            }
        }
        
        return originalFetch(resource, config);
    };
})();
