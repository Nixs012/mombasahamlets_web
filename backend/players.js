// backend/admin/js/modules/players.js
import { showNotification } from './admin.js';

const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

// Function to fetch and display players
async function fetchPlayers() {
    const playerTableBody = document.querySelector('#players-tab .admin-table tbody');
    if (!playerTableBody) return;

    // Show loading state
    playerTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading players...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/players.php`);
        if (!response.ok) throw new Error('Failed to fetch players');
        const players = await response.json();

        playerTableBody.innerHTML = ''; // Clear loading state

        if (!players || players.length === 0) {
            playerTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#666;">No players found. Add one using the form above.</td></tr>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/29.jpg';

        players.forEach(player => {
            // Build correct image URL for admin panel (backend folder)
            // Use absolute path for consistency
            let imageUrl = fallbackImagePath;
            if (player.image_url && player.image_url.trim()) {
                let imgPath = player.image_url.trim();
                // Remove any leading slashes or project root prefixes
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');
                // Construct absolute path - same format as other tabs
                if (imgPath && imgPath.length > 0) {
                    imageUrl = `/mombasahamlets_web/frontend/${imgPath}`;
                }
            }

            const row = `
                <tr>
                    <td>
                        <div class="player-info-cell">
                            <img src="${imageUrl}" alt="${player.first_name} ${player.last_name}" class="table-player-image" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                            ${player.first_name} ${player.last_name}
                        </div>
                    </td>
                    <td>${player.position}</td>
                    <td>${player.jersey_number || '-'}</td>
                    <td>${player.nationality}</td>
                    <td class="actions">
                        <button class="btn-edit" data-id="${player.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" data-id="${player.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            playerTableBody.insertAdjacentHTML('beforeend', row);
        });

        // Attach event listeners for edit/delete buttons
        playerTableBody.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const response = await fetch(`${API_URL}/players.php?id=${btn.dataset.id}`);
                    if (!response.ok) throw new Error('Failed to fetch player');
                    const player = await response.json();
                    if (player) handleEdit(player);
                } catch (error) {
                    console.error('Error fetching player:', error);
                    showNotification('Could not load player details.', 'error');
                }
            });
        });

        playerTableBody.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleDelete(btn.dataset.id));
        });
    } catch (error) {
        console.error('Error fetching players:', error);
        playerTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> Failed to load players. Please try again.</td></tr>';
        showNotification('Could not load players.', 'error');
    }
}

// Helper to clear all validation errors
function clearValidationErrors(form) {
    form.querySelectorAll('.error-message').forEach(el => el.textContent = '');
    form.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
}

// Helper to display validation errors
function displayValidationErrors(form, errors) {
    errors.forEach(error => {
        const field = form.querySelector(`#${error.field}`);
        if (field) {
            field.classList.add('invalid');
            const errorContainer = field.nextElementSibling;
            if (errorContainer && errorContainer.classList.contains('error-message')) {
                errorContainer.textContent = error.message;
            }
        }
    });
}

function validatePlayerData(data) {
    const errors = [];
    const firstName = (data.first_name || '').trim();
    const lastName = (data.last_name || '').trim();
    const nationality = (data.nationality || '').trim();

    if (!firstName) {
        errors.push({ field: 'first_name', message: 'First name is required.' });
    }
    if (!lastName) {
        errors.push({ field: 'last_name', message: 'Last name is required.' });
    }

    if (data.jersey_number !== null && (isNaN(data.jersey_number) || data.jersey_number < 1 || data.jersey_number > 99)) {
        errors.push({ field: 'player-number', message: 'Jersey number must be between 1 and 99 (optional).' });
    }

    if (!nationality) {
        errors.push({ field: 'player-nationality', message: 'Nationality is required.' });
    }

    if (!data.dob) {
        errors.push({ field: 'player-dob', message: 'Date of Birth is required.' });
    }

    // Basic check for a valid-looking path. It should not be empty and contain a path separator.
    // Image URL is optional, so only validate if provided
    if (data.image_url && data.image_url.trim() !== '' && data.image_url.indexOf('/') === -1) {
        // This is a very basic check, more robust validation might be needed
        errors.push({ field: 'player-image-url', message: 'Please provide a valid image URL or leave empty.' });
    }

    return errors;
}

// Function to handle form submission for adding/editing a player
async function handlePlayerSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const playerId = form.dataset.editingId;

    clearValidationErrors(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    // Correctly gather form data using FormData
    const formData = new FormData(form);
    const playerData = Object.fromEntries(formData.entries());

    // Ensure numeric types are correctly formatted
    playerData.jersey_number = playerData.jersey_number ? parseInt(playerData.jersey_number, 10) : null;

    // Normalize image_url - remove unnecessary prefixes
    if (playerData.image_url) {
        playerData.image_url = playerData.image_url.replace(/^\/mombasahamlets_web\//, '');
        playerData.image_url = playerData.image_url.replace(/^\/?frontend\//, '');
        playerData.image_url = playerData.image_url.replace(/^\//, '');
        if (playerData.image_url.length === 0) {
            playerData.image_url = null;
        }
    }

    console.log('Player data being sent:', playerData); // DEBUG: Log data before sending
    const validationErrors = validatePlayerData(playerData);
    if (validationErrors.length > 0) {
        displayValidationErrors(form, validationErrors);
        showNotification('Please correct the errors in the form.', 'error');
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        return;
    }

    // The backend is configured to handle both create and update via POST.
    // It will check for an 'id' to determine if it's an update.
    const url = playerId ? `${API_URL}/players.php?id=${playerId}` : `${API_URL}/players.php`;

    try {
        const response = await fetch(url, {
            method: 'POST', // Always use POST for simplicity with this backend setup
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(playerData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save player');
        }

        showNotification(`Player ${playerId ? 'updated' : 'added'} successfully!`, 'success');
        form.reset();
        delete form.dataset.editingId;
        const playersTabH2 = document.querySelector('#players-tab h2, #players-tab h3');
        if (playersTabH2) playersTabH2.textContent = 'Add/Edit Player';
        // Clear image preview if exists
        const imageInput = document.getElementById('player-image-url');
        if (imageInput) {
            const preview = imageInput.parentNode.querySelector('img');
            if (preview) preview.src = '';
        }
        // Refresh the list after a short delay
        setTimeout(() => {
            fetchPlayers();
        }, 300);
    } catch (error) {
        console.error('Error saving player:', error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

// Function to handle editing a player
function handleEdit(player) {
    const form = document.getElementById('player-form');
    form.dataset.editingId = player.id;
    const idInput = document.getElementById('player-id');
    if (idInput) idInput.value = player.id;

    document.getElementById('first_name').value = player.first_name || '';
    document.getElementById('last_name').value = player.last_name || '';
    document.getElementById('player-position').value = player.position;
    document.getElementById('player-number').value = player.jersey_number || '';
    document.getElementById('player-nationality').value = player.nationality || '';
    document.getElementById('player-dob').value = player.dob || '';
    document.getElementById('player-joined').value = player.joined || '';

    document.getElementById('player-apps').value = player.appearances || 0;
    document.getElementById('player-goals').value = player.goals || 0;
    document.getElementById('player-assists').value = player.assists || 0;
    document.getElementById('player-clean-sheets').value = player.clean_sheets || 0;
    document.getElementById('player-saves').value = player.saves || 0;


    document.getElementById('player-bio').value = player.bio || '';
    if (document.getElementById('player-image-url')) document.getElementById('player-image-url').value = player.image_url || '';

    document.querySelector('#players-tab h3').textContent = 'Edit Player';
    form.scrollIntoView({ behavior: 'smooth' });
}

// Function to handle deleting a player
async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this player?')) return;

    try {
        const response = await fetch(`${API_URL}/players.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete player');
        showNotification('Player deleted successfully!', 'success');
        await fetchPlayers(); // Refresh the list
    } catch (error) {
        console.error('Error deleting player:', error);
        showNotification(`Could not delete the player: ${error.message}`, 'error');
    }
}

export function initPlayers() {
    const playerForm = document.getElementById('player-form');
    const playerTable = document.querySelector('#players-tab .admin-table');

    if (playerForm) {
        playerForm.addEventListener('submit', handlePlayerSubmit);
    }

    if (playerTable) {
        playerTable.addEventListener('click', async (e) => {
            const target = e.target.closest('button');
            if (!target) return;

            const id = target.dataset.id;
            if (target.classList.contains('btn-delete')) {
                await handleDelete(id);
            } else if (target.classList.contains('btn-edit')) {
                try {
                    const response = await fetch(`${API_URL}/players.php?id=${id}`);
                    const player = await response.json();
                    if (player) {
                        handleEdit(player);
                    }
                } catch (error) {
                    console.error('Could not fetch player for editing:', error);
                }
            }
        });
    }

    // Initial fetch if the tab is already active on load
    const playersTab = document.getElementById('players-tab');
    if (playersTab && playersTab.classList.contains('active')) {
        fetchPlayers();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'players') {
            fetchPlayers();
        }
    });
}
