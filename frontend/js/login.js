document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorMessage.textContent = '';

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/users.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    username,
                    password
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Login failed');
            }

            if (!data.token) {
                throw new Error('Login succeeded but no token was received. Please contact support.');
            }

            localStorage.setItem('userToken', data.token);
            // Always land on frontend home
            window.location.href = '/mombasahamlets_web/frontend/index.php';

        } catch (error) {
            errorMessage.textContent = error.message;
        }
    });
});
