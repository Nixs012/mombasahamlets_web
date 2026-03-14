// admin.js - Fixed version
// Corrected imports for admin modules
import { initPlayers } from './players.js'; // Corrected path to the main players admin script
import { initMatches } from './matches.js'; // Corrected path to the main matches admin script
import { initShop } from './shop.js';     // Corrected path to the main shop admin script
import { initNews } from './news.js';     // Corrected path to the main news admin script
import { initEvents } from './events.js';   // Corrected path to the main events admin script
import { initTicketTypes } from './ticket_types.js';
import { initPartners } from './partners.js';
import { initMedia } from './media.js';   // Corrected path to the main media admin script
import { initUI } from './ui.js';         // Corrected path to the main ui admin script
import { initAbout } from './about.js';
import { initContact } from './contact.js';

// Set a global API URL for all modules to use
const projectBase = window.location.origin + '/mombasahamlets_web';
window.PROJECT_BASE = projectBase;
window.API_URL = projectBase + '/backend/api';

// Simple progress bar controller
const ProgressBar = {
    el: null,
    init() {
        if (this.el) return;
        this.el = document.createElement('div');
        this.el.className = 'admin-progress-bar';
        document.querySelector('.main-content').prepend(this.el);
    },
    start() {
        this.init();
        this.el.style.width = '0%';
        this.el.style.opacity = '1';
        this.el.classList.add('active');
        setTimeout(() => this.el.style.width = '30%', 10);
    },
    intermediate() {
        this.el.style.width = '70%';
    },
    finish() {
        this.el.style.width = '100%';
        setTimeout(() => {
            this.el.style.opacity = '0';
            setTimeout(() => {
                this.el.style.width = '0%';
                this.el.classList.remove('active');
            }, 300);
        }, 200);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing admin panel...');
    initializeAdminPanel();
});

function initializeAdminPanel() {
    if (window.adminPanelInitialized) {
        console.log('Admin panel already initialized, skipping duplicate call.');
        return;
    }
    window.adminPanelInitialized = true;
    console.log('Starting admin panel initialization...');

    try {
        // Initialize modules safely
        initUI();
        initModalHandlers(); // New function
        initNews();
        initPlayers();
        initShop();
        initMatches();
        initEvents();
        initTicketTypes();
        initPartners();
        initMedia();
        initNotifications();
        initMessages();
        initAbout();
        initContact();

        console.log('All modules initialized successfully');
    } catch (error) {
        console.error('Error initializing modules:', error);
        // Continue with basic functionality even if modules fail
    }

    // Mobile menu toggle - robust open/close behavior
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const sidebarClose = document.querySelector('.sidebar .sidebar-header .sidebar-close') || document.querySelector('.sidebar-close');

    if (menuToggle && sidebar && sidebarOverlay) {
        const openSidebar = () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            menuToggle.setAttribute('aria-expanded', 'true');
            sidebar.setAttribute('aria-hidden', 'false');
            // prevent body scroll when sidebar is open
            document.body.classList.add('no-scroll');
        };

        const closeSidebar = () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
            sidebar.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
        };

        const toggleSidebar = () => {
            if (sidebar.classList.contains('active')) closeSidebar();
            else openSidebar();
        };

        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSidebar();
        });

        // Clicking overlay or close button should always close the sidebar
        sidebarOverlay.addEventListener('click', (e) => {
            e.stopPropagation();
            closeSidebar();
        });

        if (sidebarClose) {
            sidebarClose.addEventListener('click', (e) => {
                e.stopPropagation();
                closeSidebar();
            });
        }

        // Close sidebar on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });

        // Ensure sidebar closes on window resize to large screens
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });

        console.log('Menu toggle initialized (robust handlers attached)');
    } else {
        if (!menuToggle) console.warn('Menu toggle not found');
        if (!sidebar) console.warn('Sidebar not found');
        if (!sidebarOverlay) console.warn('Sidebar overlay not found');
    }

    // Modal behavior logic
    function initModalHandlers() {
        const closeButtons = document.querySelectorAll('.close-modal');
        const modals = document.querySelectorAll('.admin-modal');

        closeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const modal = btn.closest('.admin-modal');
                if (modal) modal.classList.remove('active');
            });
        });

        // Close on clicking outside the content
        modals.forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    }

    // Tab switching functionality
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    const tabContents = document.querySelectorAll('.tab-content');
    const pageTitle = document.getElementById('page-title');

    // Make switchTab global so other modules can use it
    window.switchTab = function (tabName) {
        console.log('Switching to tab:', tabName);

        ProgressBar.start();

        // Use requestAnimationFrame for smoother class switching
        requestAnimationFrame(() => {
            // Hide all tabs
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            const selectedTab = document.getElementById(`${tabName}-tab`);
            if (selectedTab) {
                selectedTab.classList.add('active');
                ProgressBar.intermediate();

                // Dispatch a custom event so modules can refresh their data
                window.dispatchEvent(new CustomEvent('tabChanged', { detail: { tabName } }));

                // Simulate and/or wait for content load
                setTimeout(() => ProgressBar.finish(), 500);
            } else {
                console.warn('Tab not found:', tabName);
                ProgressBar.finish();
            }

            // Update active link
            sidebarLinks.forEach(link => {
                const isActive = link.getAttribute('data-tab') === tabName;
                link.classList.toggle('active', isActive);

                if (isActive && window.innerWidth <= 1024) {
                    link.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            });

            if (pageTitle) {
                pageTitle.textContent = tabName.charAt(0).toUpperCase() + tabName.slice(1);
            }
        });

        // Save active tab
        try {
            sessionStorage.setItem('activeTab', tabName);
        } catch (error) {
            console.warn('Could not save tab to sessionStorage:', error);
        }
    };

    function switchTab(tabName) {
        window.switchTab(tabName);
    }

    // Add click listeners to sidebar links
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const tabName = link.getAttribute('data-tab');
            if (tabName) {
                e.preventDefault();
                switchTab(tabName);

                // Close sidebar on mobile after selection
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                }
            }
        });
    });

    // Initialize with saved tab or default to dashboard
    let activeTab = 'dashboard';
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');

        if (tabParam) {
            activeTab = tabParam;
        } else {
            activeTab = sessionStorage.getItem('activeTab') || 'dashboard';
        }
    } catch (error) {
        console.warn('Could not read from sessionStorage or URL:', error);
    }

    switchTab(activeTab);

    // Logout functionality
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });
        console.log('Logout button initialized');
    }

    console.log('Admin panel initialization complete');
}

export function showNotification(message, type = 'info') {
    console.log('Showing notification:', message, type);

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        success: '#4cc9f0',
        error: '#f72585',
        warning: '#f8961e',
        info: '#4361ee'
    };
    return colors[type] || colors.info;
}

console.log('Admin JS file loaded successfully');

/**
 * Initializes the notification system (bell, dropdown, polling, and shake)
 */
function initNotifications() {
    const bell = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    const badge = document.getElementById('notification-unread-count');
    const list = document.getElementById('notification-list');
    const markAllReadBtn = document.getElementById('mark-all-read');

    if (!bell || !dropdown) return;

    // Toggle dropdown OR navigate if there's unread
    bell.addEventListener('click', (e) => {
        e.stopPropagation();

        const unreadCount = parseInt(badge.textContent || '0');

        // If there are unread notifications, navigate to the first unread one on bell click
        if (unreadCount > 0) {
            const firstUnread = list.querySelector('.notification-item.unread');
            if (firstUnread) {
                firstUnread.click();
                return;
            }
        }

        // Otherwise just toggle dropdown
        dropdown.classList.toggle('active');
        if (dropdown.classList.contains('active')) {
            loadNotifications();
        }
    });

    // Close dropdown when clicking elsewhere
    document.addEventListener('click', () => {
        dropdown.classList.remove('active');
    });

    dropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Mark all as read
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                const response = await fetch(`${window.API_URL}/get-notifications.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                if (response.ok) {
                    loadNotifications();
                }
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        });
    }

    async function loadNotifications() {
        try {
            const response = await fetch(`${window.API_URL}/get-notifications.php`);
            if (!response.ok) throw new Error('Failed to fetch notifications');

            const data = await response.json();
            if (data.status === 'success') {
                updateNotificationUI(data.unread_count, data.notifications);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    function updateNotificationUI(unreadCount, notifications) {
        // 1. Update Badge & Shake
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.style.display = 'flex';
            bell.classList.add('shaking');
        } else {
            badge.style.display = 'none';
            bell.classList.remove('shaking');
        }

        // 2. Update List
        if (notifications.length === 0) {
            list.innerHTML = '<div class="no-notifications">No new notifications</div>';
            return;
        }

        list.innerHTML = notifications.map(n => {
            let title = n.type.toUpperCase();
            let desc = `New ${n.type} received`;
            let icon = 'fa-info-circle';

            if (n.type === 'message') {
                icon = 'fa-envelope';
                desc = 'You have a new contact message';
            } else if (n.type === 'order') {
                icon = 'fa-shopping-bag';
                desc = `New Order #${n.reference_id}`;
            } else if (n.type === 'payment') {
                icon = 'fa-credit-card';
                desc = `Successful payment received`;
            }

            return `
                <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}" data-type="${n.type}" data-ref="${n.reference_id}">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas ${icon}" style="color: var(--primary);"></i>
                        <div>
                            <p class="notif-title">${title}</p>
                            <p class="notif-desc">${desc}</p>
                            <span class="notif-time">${new Date(n.created_at).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // 3. Add Click Listeners
        list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', async () => {
                const id = item.dataset.id;
                const type = item.dataset.type;
                const refId = item.dataset.ref;

                // Mark as read
                await fetch(`${window.API_URL}/get-notifications.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                // Navigate to related page/tab
                navigateToRelated(type, refId);
                dropdown.classList.remove('active');
                loadNotifications();
            });
        });
    }

    async function navigateToRelated(type, refId) {
        if (type === 'message') {
            if (typeof window.switchTab === 'function') {
                window.switchTab('messages');
            }
            // Wait for tab to switch and messages to load
            setTimeout(() => {
                if (typeof window.viewMessageDetails === 'function') {
                    window.viewMessageDetails(refId);
                } else {
                    console.warn('window.viewMessageDetails is not a function');
                }
            }, 500);
        } else if (type === 'order') {
            window.location.href = `admin_order_view.php?order_id=${refId}`;
        } else if (type === 'payment') {
            window.location.href = `admin_payments.php?id=${refId}`;
        }
    }

    // Initial load and periodic check
    loadNotifications();

    // Optimize polling: only poll if document is visible
    let pollInterval;
    const startPolling = () => {
        if (!pollInterval) {
            pollInterval = setInterval(loadNotifications, 30000); // Increased to 30s for performance
        }
    };
    const stopPolling = () => {
        clearInterval(pollInterval);
        pollInterval = null;
    };

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopPolling();
        else {
            loadNotifications();
            startPolling();
        }
    });

    startPolling();
}

// Remove localized switchTab helper to use global instead

/**
 * Initializes the messages management tab
 */
function initMessages() {
    const list = document.getElementById('admin-messages-list');
    if (!list) return;

    // Expose functions for direct navigation
    window.viewMessageDetails = viewMessageDetails;
    window.loadMessages = loadMessages;

    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'messages') {
            loadMessages();
        }
    });

    async function loadMessages() {
        try {
            const response = await fetch(`${window.API_URL}/messages.php`);
            if (!response.ok) throw new Error('Failed to fetch messages');

            const messages = await response.json();
            renderMessages(messages);
        } catch (error) {
            console.error('Error loading messages:', error);
            list.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--danger);">Failed to load messages.</td></tr>';
        }
    }

    function renderMessages(messages) {
        if (messages.length === 0) {
            list.innerHTML = '<tr><td colspan="5" style="text-align: center;">No messages found.</td></tr>';
            return;
        }

        list.innerHTML = messages.map(m => `
            <tr>
                <td>${new Date(m.created_at).toLocaleDateString()}</td>
                <td>
                    <strong>${m.name}</strong><br>
                    <small>${m.email}</small>
                </td>
                <td>${m.subject}</td>
                <td><span class="status-badge status-${(m.status || 'unread').toLowerCase()}">${(m.status || 'unread').toUpperCase()}</span></td>
                <td class="actions">
                    <button class="btn-action btn-edit" title="View Message" data-id="${m.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action btn-delete" title="Delete" data-id="${m.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        // Add event listeners for dynamic buttons
        list.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => viewMessageDetails(btn.dataset.id));
        });
        list.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => deleteMessage(btn.dataset.id));
        });
    }

    async function viewMessageDetails(id) {
        try {
            const response = await fetch(`${window.API_URL}/messages.php`);
            const messages = await response.json();
            const message = messages.find(m => m.id == id);

            if (message) {
                // Populate modal
                const modal = document.getElementById('message-modal');
                if (modal) {
                    document.getElementById('msg-sender').textContent = message.name;
                    document.getElementById('msg-email').textContent = message.email;
                    document.getElementById('msg-date').textContent = new Date(message.created_at).toLocaleString();
                    document.getElementById('msg-subject').textContent = message.subject;
                    document.getElementById('msg-content').textContent = message.message;

                    modal.classList.add('active');
                } else {
                    // Fallback to alert if modal not found
                    alert(`From: ${message.name} (${message.email})\nSubject: ${message.subject}\n\nMessage:\n${message.message}`);
                }

                // Mark as read if it was unread
                if (message.status === 'unread') {
                    await fetch(`${window.API_URL}/messages.php`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, status: 'read' })
                    });
                    loadMessages();
                }
            }
        } catch (error) {
            console.error('Error viewing message:', error);
        }
    }

    async function deleteMessage(id) {
        if (confirm('Are you sure you want to delete this message?')) {
            try {
                const response = await fetch(`${window.API_URL}/messages.php?id=${id}`, {
                    method: 'DELETE'
                });
                if (response.ok) {
                    loadMessages();
                }
            } catch (error) {
                console.error('Error deleting message:', error);
            }
        }
    }
}