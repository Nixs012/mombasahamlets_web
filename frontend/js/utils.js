/**
 * utils.js
 *
 * Contains shared utility functions for the Mombasa Hamlets FC website.
 */

/**
 * Constructs the correct, absolute path for an image.
 * This handles different environments (Live Server, WAMP, production).
 * @param {string} path - The relative path from the database (e.g., 'frontend/images/uploads/player.jpg').
 * @returns {string} The full, usable image URL.
 */
function getImagePath(path) {
    // If no path, return a transparent placeholder
    if (!path) {
        return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }
    // If it's already a full URL, use it as is
    if (path.startsWith('http')) {
        return path;
    }

    const host = window.location.hostname;
    const port = window.location.port;

    // For VS Code Live Server, the path is relative to the workspace root, which is the project root.
    if ((host === '127.0.0.1' || host === 'localhost') && port === '5500') {
        return `/${path.replace(/^\//, '')}`;
    }

    // For WAMP or a production server where the project is in a subdirectory.
    // This prepends the necessary subdirectory.
    return `/mombasahamlets_web/${path.replace(/^\//, '')}`;
}

/**
 * A fallback for broken images, replacing them with a generic placeholder.
 * @param {HTMLImageElement} element - The image element that failed to load.
 */
function handleImageError(element) {
    element.onerror = null; // Prevent infinite loops
    element.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQwIiBoZWlnaHQ9IjE0MCIgdmlld0JveD0iMCAwIDE0MCAxNDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iMTQwIiByeD0iNzAiIGZpbGw9IiNGM0Y0RjYiLz4KPHBhdGggZD0iTTcwIDUwQzYyLjU0NDEgNTAgNTYuNDU1NSA1Ni4wODgyIDU2LjQ1NTkgNjMuNTQ0MUM1Ni40NTU5IDcxIDYyLjU0NDEgNzcuMDg4MiA3MCA3Ny4wODgyQzc3LjQ1NTkgNzcuMDg4MiA4My41NDQxIDcxIDgzLjU0NDEgNjMuNTQ0MUM4My41NDQxIDU2LjA4ODIgNzcuNDU1OSA1MCA3MCA1MFoiIGZpbGw9IiNDRUNFQ0UiLz4KPHBhdGggZD0iTTkxLjAzODIgOTAuMDM4Mkg0OC45NjE4QzQ1LjAyOTQgOTAuMDM4MiA0MS44NTI5IDg2Ljg2MTggNDEuODUyOSA4Mi45Mjk0VjgyLjAwNzFDNDEuODUyOSA3Ny42NjE4IDQ1LjM3NjUgNzMuNzA3MSA0OS43MjM1IDcyLjg3NjVDNTQuODIzNSA3MS45MjA2IDYwLjgyMzUgNzEuNTg4MiA2Ny4yMDU5IDcxLjU4ODJDNzMuNTg4MiA3MS41ODgyIDc5LjU4ODIgNzEuOTIwNiA4NC41ODgyIDcyLjg3NjVDODguOTM1MyA3My43MDcxIDkyLjQ1ODggNzcuNjYxOCA5Mi40NTg4IDgyLjAwNzFWODIuOTI5NEM5Mi40NTg4IDg2Ljg2MTggODkuMjgyNCA5MC4wMzgyIDg1LjM1IDkwLjAzODJaIiBmaWxsPSIjQ0VDRUNFIi8+Cjwvc3ZnPgo=';
}

/**
 * Sets up the authentication UI elements based on the presence of a user token.
 */
function setupAuthUI() {
    const token = localStorage.getItem('userToken');
    const userAuthDesktop = document.getElementById('user-auth-desktop');
    const userAuthMobile = document.getElementById('user-auth-mobile');

    if (token) {
        // User is logged in
        // Since these are static labels "My Account" and "Logout", we can keep innerHTML or use fragments
        // But for safety, let's use a systematic approach
        const dashboardLink = document.createElement('a');
        dashboardLink.href = 'user.php';
        dashboardLink.textContent = 'My Account';

        const logoutLink = document.createElement('a');
        logoutLink.href = '#';
        logoutLink.id = 'logout-link';
        logoutLink.textContent = 'Logout';

        if (userAuthDesktop) {
            userAuthDesktop.innerHTML = '';
            userAuthDesktop.appendChild(dashboardLink);
            userAuthDesktop.appendChild(document.createTextNode(' | '));
            userAuthDesktop.appendChild(logoutLink);
        }

        if (userAuthMobile) {
            userAuthMobile.innerHTML = '';
            const li1 = document.createElement('li');
            li1.appendChild(dashboardLink.cloneNode(true));
            const li2 = document.createElement('li');
            li2.appendChild(logoutLink.cloneNode(true));
            userAuthMobile.appendChild(li1);
            userAuthMobile.appendChild(li2);
        }

        document.body.addEventListener('click', (e) => {
            if (e.target.id === 'logout-link') {
                e.preventDefault();
                localStorage.removeItem('userToken');
                window.location.href = 'login.php';
            }
        });
    } else {
        // User is logged out
        const loginLink = document.createElement('a');
        loginLink.href = 'login.php';
        loginLink.textContent = 'Login';

        const registerLink = document.createElement('a');
        registerLink.href = 'register.php';
        registerLink.textContent = 'Register';

        if (userAuthDesktop) {
            userAuthDesktop.innerHTML = '';
            userAuthDesktop.appendChild(loginLink);
            userAuthDesktop.appendChild(document.createTextNode(' | '));
            userAuthDesktop.appendChild(registerLink);
        }

        if (userAuthMobile) {
            userAuthMobile.innerHTML = '';
            const li1 = document.createElement('li');
            li1.appendChild(loginLink.cloneNode(true));
            const li2 = document.createElement('li');
            li2.appendChild(registerLink.cloneNode(true));
            userAuthMobile.appendChild(li1);
            userAuthMobile.appendChild(li2);
        }
    }
}

/**
 * Displays a toast message on the screen.
 * @param {string} message The message to display.
 * @param {string} type The type of toast (e.g., 'success', 'error').
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}