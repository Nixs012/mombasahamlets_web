/**
 * Authentication Notification System
 * Shows notifications when users try to access protected features
 */

// Protected actions that require login
const PROTECTED_ACTIONS = {
    'add-to-cart': 'Please login or register to add items to your cart',
    'quick-view': 'Please login or register to view product details',
    'get-tickets': 'Please login or register to purchase tickets',
    'register-event': 'Please login or register to register for events',
    'book-tour': 'Please login or register to book a tour',
    'rsvp': 'Please login or register to RSVP for events',
    'buy-tickets': 'Please login or register to buy tickets'
};

/**
 * Safely decode the stored auth token and return its payload.
 * Returns null when the token is missing, invalid, or expired.
 */
function getTokenPayload() {
    const token = localStorage.getItem('userToken');
    if (!token) return null;

    try {
        // JWT is header.payload.signature
        const parts = token.split('.');
        if (parts.length !== 3) {
            throw new Error('Invalid token structure');
        }

        const base64Url = parts[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        const decoded = JSON.parse(jsonPayload);
        const now = Math.floor(Date.now() / 1000);

        if (decoded.exp && decoded.exp < now) {
            localStorage.removeItem('userToken');
            return null;
        }
        return decoded;
    } catch (e) {
        console.error('Error decoding token:', e);
        localStorage.removeItem('userToken');
        return null;
    }
}

/**
 * Helper to clear auth and refresh the page.
 */
function logoutUser() {
    localStorage.removeItem('userToken');
    window.location.href = 'index.php';
}

/**
 * Escape user-provided strings before rendering.
 */
function escapeHtml(value = '') {
    return value
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Show the right auth links in the header/sidebar based on login state.
 * Keeps "Sign Up" visible for guests and shows Dashboard/Logout for logged-in users.
 */
function renderAuthLinks() {
    const payload = getTokenPayload();

    // Construct display name: prefer Username, fallback to First Last or 'User'
    let displayName = payload?.username || 'User';
    displayName = escapeHtml(displayName);

    // Avatar Logic
    let avatarSrc = 'images/logo1.jpeg';
    if (payload?.profile_picture) {
        // Handle relative paths. Usually stored as 'frontend/images/...' or 'images/...'
        // If we are on root index.php, 'frontend/images/...' works if we are in root? No, images are in frontend/images.
        // Wait, index.php is in frontend/ so 'images/...' works.
        // Backend saves full path or relative? 'upload-image.php' returns relative to project root usually?
        // Let's assume it renders well if we strip 'frontend/' if present, similar to profile.js logic.
        avatarSrc = payload.profile_picture.replace(/^frontend\//, '');
    }

    const desktopAuth = document.querySelector('.nav-auth');
    if (desktopAuth) {
        desktopAuth.innerHTML = payload
            ? `
                <div class="nav-user-dropdown">
                    <button class="nav-user-trigger" aria-haspopup="true" aria-expanded="false">
                        <img src="${avatarSrc}" alt="User avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" onerror="this.src='https://via.placeholder.com/32x32/4361ee/ffffff?text=U';this.onerror=null;">
                        <span class="nav-user-name">${displayName}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="nav-user-menu" role="menu">
                        <a href="profile.php" role="menuitem"><i class="fas fa-sliders-h"></i> Account</a>
                        <a href="#" class="nav-logout" role="menuitem"><i class="fas fa-sign-out-alt"></i> Log Out</a>
                    </div>
                </div>
            `
            : `
                <a href="register.php" class="nav-register">Sign Up</a>
                <span class="nav-separator">|</span>
                <a href="login.php" class="nav-login">Login</a>
            `;
    }

    const mobileAuth = document.querySelector('.nav-auth-mobile');
    const mobileSidebar = document.querySelector('.mobile-sidebar');

    if (mobileAuth) {
        mobileAuth.innerHTML = payload
            ? `
                <a href="profile.php"><i class="fas fa-sliders-h"></i> Account</a>
                <a href="#" class="nav-logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            `
            : `
                <a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            `;
    }

    // Handle Mobile Sidebar Profile Section
    if (mobileSidebar) {
        let sidebarProfile = mobileSidebar.querySelector('.sidebar-profile');

        if (payload) {
            // Create profile section if it doesn't exist
            if (!sidebarProfile) {
                sidebarProfile = document.createElement('div');
                sidebarProfile.className = 'sidebar-profile';
                // Insert after sidebar-header
                const header = mobileSidebar.querySelector('.sidebar-header');
                if (header) {
                    header.after(sidebarProfile);
                } else {
                    mobileSidebar.prepend(sidebarProfile);
                }
            }

            sidebarProfile.innerHTML = `
                <div class="sidebar-user-avatar">
                    <img src="${avatarSrc}" alt="User avatar" onerror="this.src='https://via.placeholder.com/60x60/DA291C/ffffff?text=U';this.onerror=null;">
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name">${displayName}</span>
                    <span class="sidebar-user-status">Member</span>
                </div>
            `;
            sidebarProfile.style.display = 'flex';
        } else if (sidebarProfile) {
            // Hide or remove if not logged in
            sidebarProfile.style.display = 'none';
        }
    }

    // If logged out, remove any stray dropdowns that might be outside nav-auth.
    if (!payload) {
        document.querySelectorAll('.nav-user-dropdown').forEach(el => el.remove());
    }

    document.querySelectorAll('.nav-logout').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            logoutUser();
        });
    });

    setupUserDropdown();
}

/**
 * Check if user is logged in
 */
function isUserLoggedIn() {
    return Boolean(getTokenPayload());
}

/**
 * Show authentication required notification
 */
function showAuthNotification(action = 'access this feature') {
    const message = typeof action === 'string' && PROTECTED_ACTIONS[action]
        ? PROTECTED_ACTIONS[action]
        : `Please login or register to ${action}`;

    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'auth-notification';
    notification.innerHTML = `
        <div class="auth-notification-content">
            <div class="auth-notification-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="auth-notification-text">
                <h4>Login Required</h4>
                <p>${message}</p>
                <div class="auth-notification-actions">
                    <a href="register.php" class="btn-auth btn-register">Sign Up</a>
                    <a href="login.php" class="btn-auth btn-login">Login</a>
                </div>
            </div>
            <button class="auth-notification-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Add styles if not already added
    if (!document.getElementById('auth-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'auth-notification-styles';
        style.textContent = `
            .auth-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                max-width: 400px;
                animation: slideInRight 0.3s ease;
                border-left: 4px solid #DA291C;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .auth-notification-content {
                display: flex;
                align-items: flex-start;
                padding: 20px;
                gap: 15px;
            }
            
            .auth-notification-icon {
                font-size: 24px;
                color: #DA291C;
                flex-shrink: 0;
            }
            
            .auth-notification-text {
                flex: 1;
            }
            
            .auth-notification-text h4 {
                margin: 0 0 8px 0;
                color: #333;
                font-size: 18px;
            }
            
            .auth-notification-text p {
                margin: 0 0 15px 0;
                color: #666;
                font-size: 14px;
                line-height: 1.5;
            }
            
            .auth-notification-actions {
                display: flex;
                gap: 10px;
            }
            
            .btn-auth {
                padding: 8px 16px;
                border-radius: 6px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 600;
                transition: all 0.3s ease;
                display: inline-block;
            }
            
            .btn-register {
                background: #DA291C;
                color: white;
            }
            
            .btn-register:hover {
                background: #BB0A21;
                transform: translateY(-2px);
            }
            
            .btn-login {
                background: #f5f5f5;
                color: #333;
                border: 1px solid #ddd;
            }
            
            .btn-login:hover {
                background: #e9e9e9;
                transform: translateY(-2px);
            }
            
            .auth-notification-close {
                background: none;
                border: none;
                font-size: 18px;
                color: #999;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                transition: color 0.3s ease;
            }
            
            .auth-notification-close:hover {
                color: #333;
            }
        `;
        document.head.appendChild(style);
    }

    // Add to page
    document.body.appendChild(notification);

    // Close button handler
    const closeBtn = notification.querySelector('.auth-notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    });

    // Auto-close after 8 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 8000);
}

/**
 * Protect an action - check if user is logged in, show notification if not
 */
function protectAction(action, callback) {
    if (isUserLoggedIn()) {
        if (callback) callback();
        return true;
    } else {
        showAuthNotification(action);
        return false;
    }
}

/**
 * Initialize protected action handlers
 */
document.addEventListener('DOMContentLoaded', () => {
    // Ensure auth links are present and accurate as soon as the page is ready
    renderAuthLinks();

    // Protect add to cart buttons
    document.addEventListener('click', (e) => {
        const target = e.target.closest('.add-to-cart-btn, .add-to-cart');
        if (target) {
            e.preventDefault();
            protectAction('add-to-cart', () => {
                // If logged in, proceed with add to cart
                const productCard = target.closest('.product-card');
                if (productCard) {
                    const productId = productCard.dataset.productId;
                    // Trigger add to cart functionality
                    if (window.addToCart) {
                        window.addToCart(productId);
                    }
                }
            });
        }
    });

    // Protect quick view buttons
    document.addEventListener('click', (e) => {
        const target = e.target.closest('.quick-view-btn, .quick-view');
        if (target) {
            e.preventDefault();
            protectAction('quick-view', () => {
                // If logged in, proceed with quick view
                const productCard = target.closest('.product-card');
                if (productCard) {
                    const productId = productCard.dataset.productId;
                    // Trigger quick view functionality
                    if (window.showQuickView) {
                        window.showQuickView(productId);
                    }
                }
            });
        }
    });

    // Protect ticket/event buttons
    document.addEventListener('click', (e) => {
        const target = e.target.closest('.get-tickets-btn, .register-btn, .rsvp-btn, .buy-tickets-btn, .book-tour-btn');
        if (target) {
            e.preventDefault();
            const action = target.classList.contains('get-tickets-btn') ? 'get-tickets' :
                target.classList.contains('register-btn') ? 'register-event' :
                    target.classList.contains('rsvp-btn') ? 'rsvp' :
                        target.classList.contains('buy-tickets-btn') ? 'buy-tickets' :
                            'book-tour';
            protectAction(action, () => {
                // If logged in, proceed with action
                const eventName = target.dataset.event || target.textContent;
                alert(`Proceeding with: ${eventName}`);
            });
        }
    });
});

// Export functions for use in other scripts
window.isUserLoggedIn = isUserLoggedIn;
window.showAuthNotification = showAuthNotification;
window.protectAction = protectAction;
window.logoutUser = logoutUser;
window.renderAuthLinks = renderAuthLinks;
window.getTokenPayload = getTokenPayload;

/**
 * Simple dropdown toggle for the user menu in the header
 */
function setupUserDropdown() {
    const trigger = document.querySelector('.nav-user-trigger');
    const menu = document.querySelector('.nav-user-menu');
    if (!trigger || !menu) return;

    const closeMenu = () => {
        menu.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    };

    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const isOpen = menu.classList.toggle('open');
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
        if (!menu.classList.contains('open')) return;
        if (!menu.contains(e.target) && !trigger.contains(e.target)) {
            closeMenu();
        }
    });
}

