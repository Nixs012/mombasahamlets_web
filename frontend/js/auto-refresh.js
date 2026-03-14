/**
 * auto-refresh.js
 * Automatically polls the backend for content updates and reloads the page.
 */

(function () {
    // Configuration
    const CHECK_INTERVAL = 12000; // 12 seconds
    const API_URL = (window.API_URL ? window.API_URL + '/check_update.php' : '../backend/api/check_update.php');
    const STORAGE_KEY = 'site_last_update';

    let lastUpdate = localStorage.getItem(STORAGE_KEY) || 0;

    async function checkForUpdates() {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) return;

            const data = await response.json();
            const serverLastUpdate = data.last_update;

            if (lastUpdate === 0) {
                // First run: just save the current state
                lastUpdate = serverLastUpdate;
                localStorage.setItem(STORAGE_KEY, lastUpdate);
            } else if (serverLastUpdate > lastUpdate) {
                // Content has changed!
                console.log('Content update detected, reloading...');
                localStorage.setItem(STORAGE_KEY, serverLastUpdate);

                // Optional: Show a subtle notification before reload
                // For now, just reload to keep it simple and consistent as requested
                location.reload();
            }
        } catch (error) {
            console.error('Auto-refresh check failed:', error);
        }
    }

    // Initial check on load
    checkForUpdates();

    // Set interval for periodic checks
    // Check for updates every 12 seconds
    // Using 12s instead of 10s to reduce slight server grouping if multiple clients are open
    setInterval(checkForUpdates, CHECK_INTERVAL);

})();
