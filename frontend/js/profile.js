document.addEventListener('DOMContentLoaded', async () => {
    // 1. Auth Check
    const token = localStorage.getItem('userToken');
    if (!token || !window.isUserLoggedIn()) {
        window.location.href = 'login.php';
        return;
    }

    // 2. DOM Elements
    const elements = {
        avatarImg: document.getElementById('profile-avatar'),
        btnUpload: document.getElementById('btn-trigger-upload'),
        inputUpload: document.getElementById('avatar-upload'),
        btnDeleteAvatar: document.getElementById('btn-delete-avatar'),
        firstName: document.getElementById('first-name'),
        lastName: document.getElementById('last-name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        currentPassword: document.getElementById('current-password'),
        newPassword: document.getElementById('new-password'),
        btnSave: document.getElementById('btn-save-changes'),
        statusMsg: document.getElementById('status-message'),
        togglePasswordIcons: document.querySelectorAll('.toggle-password')
    };

    let currentAvatarPath = '';
    const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

    // 3. Load Profile Data
    async function loadProfile() {
        try {
            const response = await fetch(`${API_URL}/users.php?token=${encodeURIComponent(token)}`);
            if (!response.ok) throw new Error('Failed to load profile');

            const data = await response.json();
            if (!data.valid || !data.user) throw new Error('Invalid token');

            const user = data.user;

            // Populate Fields
            elements.firstName.value = user.first_name || '';
            elements.lastName.value = user.last_name || '';
            elements.email.value = user.email || '';
            elements.phone.value = user.phone_number || ''; // Assuming backend returns phone_number

            // Handle Avatar
            if (user.profile_picture && user.profile_picture.trim() !== '') {
                currentAvatarPath = user.profile_picture;
                // Construct full URL if path is relative
                // Backend usually returns "frontend/images/uploads/..."
                // We need to make sure it points to the correct place relative to this page
                // profile.php is in frontend/ so "images/uploads/..." works if path is relative to frontend
                // But backend stores "frontend/images/..." usually or whatever upload-image returns
                // Let's normalize. 
                let displayPath = currentAvatarPath;
                if (displayPath.startsWith('frontend/')) {
                    displayPath = displayPath.replace('frontend/', '');
                }
                elements.avatarImg.src = displayPath;
            }

        } catch (error) {
            console.error(error);
            elements.statusMsg.textContent = 'Could not load profile data.';
            elements.statusMsg.className = 'status-message error';
            // Optional: Logout if token invalid
        }
    }

    // 4. Event Listeners

    // Toggle Password Visibility
    elements.togglePasswordIcons.forEach(icon => {
        icon.addEventListener('click', () => {
            const input = icon.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Upload Button Trigger
    elements.btnUpload.addEventListener('click', () => {
        elements.inputUpload.click();
    });

    // File Selection Change
    elements.inputUpload.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Validations (size/type) - handled by backend mostly but good UX here
        if (file.size > 150 * 1024 * 1024) { // 150MB
            alert('File too large. Max 150MB.');
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        try {
            elements.btnUpload.textContent = 'Uploading...';
            elements.btnUpload.disabled = true;

            const response = await fetch(`${API_URL}/upload-image.php`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const err = await response.json();
                throw new Error(err.error || 'Upload failed');
            }

            const data = await response.json();
            // data.path is the saved path, e.g. "frontend/images/uploads/img_....jpg"

            currentAvatarPath = data.path;

            // Update Preview
            let displayPath = currentAvatarPath;
            if (displayPath.startsWith('frontend/')) {
                displayPath = displayPath.replace('frontend/', '');
            }
            elements.avatarImg.src = displayPath;

        } catch (error) {
            alert('Upload failed: ' + error.message);
        } finally {
            elements.btnUpload.textContent = 'Upload new picture';
            elements.btnUpload.disabled = false;
            elements.inputUpload.value = ''; // Reset
        }
    });

    // Delete Avatar
    elements.btnDeleteAvatar.addEventListener('click', () => {
        if (!confirm('Are you sure you want to remove your profile picture?')) return;
        currentAvatarPath = '';
        elements.avatarImg.src = 'images/logo1.jpeg'; // Default fallback
    });

    // Save Changes
    elements.btnSave.addEventListener('click', async () => {
        elements.statusMsg.textContent = '';
        elements.btnSave.disabled = true;
        elements.btnSave.textContent = 'Saving...';

        const firstName = elements.firstName.value.trim();
        const lastName = elements.lastName.value.trim();

        const namePattern = /^[A-Za-z\s'\-]+$/;
        if (!namePattern.test(firstName)) {
            elements.statusMsg.textContent = 'First name should only contain letters, spaces, hyphens and apostrophes';
            elements.statusMsg.className = 'status-message error';
            elements.btnSave.disabled = false;
            elements.btnSave.textContent = 'Save changes';
            return;
        }
        if (!namePattern.test(lastName)) {
            elements.statusMsg.textContent = 'Last name should only contain letters, spaces, hyphens and apostrophes';
            elements.statusMsg.className = 'status-message error';
            elements.btnSave.disabled = false;
            elements.btnSave.textContent = 'Save changes';
            return;
        }

        const payload = {
            first_name: firstName,
            last_name: lastName,
            phone_number: elements.phone.value,
            profile_picture: currentAvatarPath,
            // Password fields
            current_password: elements.currentPassword.value,
            new_password: elements.newPassword.value
        };

        try {
            const response = await fetch(`${API_URL}/users.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Update failed');
            }

            if (data.token) {
                localStorage.setItem('userToken', data.token);
            }

            elements.statusMsg.textContent = 'Changes saved successfully!';
            elements.statusMsg.className = 'status-message success';

            // Clear password fields
            elements.currentPassword.value = '';
            elements.newPassword.value = '';

            // Update Header UI if name/avatar changed (optional, if auth-notification uses localStorage)
            // But auth-notification generally decodes the token. 
            // If we want detailed updates, we might need to refresh the page or update the token.
            // Since our token stores payload, and we just updated the user, the token is technically "stale" regarding the name/avatar 
            // UNLESS the backend returned a new token (which it didn't).
            // This is a common JWT limitation. 
            // For now, we accept that the header might show old data until re-login, OR we force a page refresh.
            // Or we update the UI manually using `auth-notification.js` logic if it exposed it.

            // Reload page after short delay to see persistence
            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } catch (error) {
            elements.statusMsg.textContent = error.message;
            elements.statusMsg.className = 'status-message error';
        } finally {
            elements.btnSave.disabled = false;
            elements.btnSave.textContent = 'Save changes';
        }
    });

    // Initial Load
    loadProfile();
});
