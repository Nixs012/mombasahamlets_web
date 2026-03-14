document.addEventListener('DOMContentLoaded', async () => {
    const userToken = localStorage.getItem('userToken');
    const usernameSpan = document.getElementById('username');
    const logoutButton = document.getElementById('logout-button');

    if (!userToken) {
        // If no token is found, redirect to the login page
        window.location.href = 'login.php';
        return;
    }

    try {
        // Verify token with backend
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        const response = await fetch(`${apiUrl}/users.php?token=${encodeURIComponent(userToken)}`);

        if (!response.ok) {
            throw new Error('Invalid token');
        }

        const data = await response.json();

        if (data.valid && data.user) {
            usernameSpan.textContent = data.user.username || 'User';
        } else {
            throw new Error('Invalid token');
        }
    } catch (error) {
        console.error('Error verifying token:', error);
        localStorage.removeItem('userToken');
        window.location.href = 'login.php';
        return;
    }

    // Add event listener for the logout button
    logoutButton.addEventListener('click', () => {
        // Remove the token from local storage
        localStorage.removeItem('userToken');
        // Redirect to the login page
        window.location.href = 'login.php';
    });
});
