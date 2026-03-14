// backend/admin/js/modules/matches.js
import { showNotification } from './admin.js';

const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

async function fetchMatches() {
    const tableBody = document.querySelector('#matches-tab .admin-table tbody');
    if (!tableBody) return;

    // Show loading state
    tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading matches...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/matches.php`);
        if (!response.ok) throw new Error('Failed to fetch matches');
        const matches = await response.json();

        tableBody.innerHTML = '';

        if (!matches || matches.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#666;">No matches found. Add one using the form above.</td></tr>';
            return;
        }

        matches.forEach(match => {
            const row = `
                <tr>
                    <td>${match.home_team} vs ${match.away_team}</td>
                    <td>${match.venue || 'TBA'}</td>
                    <td>${match.competition}</td>
                    <td>${new Date(match.match_date).toLocaleString()}</td>
                    <td><span class="status-badge status-${match.status}">${match.status}</span></td>
                    <td class="actions">
                        <button class="btn-edit" data-id="${match.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" data-id="${match.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    } catch (error) {
        console.error('Error fetching matches:', error);
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> Failed to load matches. Please try again.</td></tr>';
        showNotification('Could not load matches.', 'error');
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

function validateMatchData(data) {
    // Basic validation is handled by HTML5 attributes mostly
    // We can add specific checks here if needed
    return [];
}

function getLineupJson(startingId, subsId) {
    const startingText = document.getElementById(startingId).value;
    const subsText = document.getElementById(subsId).value;

    const starting = startingText.split('\n').filter(line => line.trim() !== '').map(name => ({ number: '', name: name.trim() }));
    const subs = subsText.split('\n').filter(line => line.trim() !== '').map(name => ({ number: '', name: name.trim() }));

    if (starting.length === 0 && subs.length === 0) return null;

    return JSON.stringify({
        starting: starting,
        subs: subs
    });
}

function setLineupForm(jsonString, startingId, subsId) {
    const startingInput = document.getElementById(startingId);
    const subsInput = document.getElementById(subsId);

    startingInput.value = '';
    subsInput.value = '';

    if (!jsonString) return;

    try {
        const data = JSON.parse(jsonString);

        if (Array.isArray(data)) {
            // Legacy format: Array of players. We'll assume they are all starters for now, or just dump them in starting.
            startingInput.value = data.map(p => p.name).join('\n');
        } else if (typeof data === 'object') {
            // New format: { starting: [], subs: [] }
            if (data.starting && Array.isArray(data.starting)) {
                startingInput.value = data.starting.map(p => p.name).join('\n');
            }
            if (data.subs && Array.isArray(data.subs)) {
                subsInput.value = data.subs.map(p => p.name).join('\n');
            }
        }
    } catch (e) {
        console.error('Error parsing lineup JSON:', e);
        // Fallback: just put raw text in starting if it looks like text
        startingInput.value = jsonString;
    }
}

async function handleMatchSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const matchId = form.dataset.editingId;

    // Clear previous errors
    clearValidationErrors(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    // Construct JSON from textareas
    const homeLineupJson = getLineupJson('home-starting', 'home-subs');
    const awayLineupJson = getLineupJson('away-starting', 'away-subs');

    const matchData = {
        home_team: document.getElementById('home-team').value,
        away_team: document.getElementById('away-team').value,
        competition: document.getElementById('competition').value,
        venue: document.getElementById('venue').value || 'TBA',
        match_date: document.getElementById('match-date').value,
        status: document.getElementById('match-status').value,
        home_score: document.getElementById('home-score').value || null,
        away_score: document.getElementById('away-score').value || null,
        match_report: document.getElementById('match-report').value,
        home_lineup: homeLineupJson,
        away_lineup: awayLineupJson,
        home_logo: (document.getElementById('home-logo')?.value || '').trim() || null,
        away_logo: (document.getElementById('away-logo')?.value || '').trim() || null,
    };

    const validationErrors = validateMatchData(matchData);
    if (validationErrors.length > 0) {
        displayValidationErrors(form, validationErrors);
        showNotification('Please correct the errors in the form.', 'error');
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        return;
    }

    const method = matchId ? 'PUT' : 'POST';
    const url = matchId ? `${API_URL}/matches.php?id=${matchId}` : `${API_URL}/matches.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(matchData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save match');
        }

        showNotification(`Match ${matchId ? 'updated' : 'added'} successfully!`, 'success');
        form.reset();
        document.getElementById('venue').value = 'TBA'; // Reset venue to TBA explicitly
        delete form.dataset.editingId;
        const matchesTabH3 = document.querySelector('#matches-tab h2, #matches-tab h3');
        if (matchesTabH3) matchesTabH3.textContent = 'Add New Match';
        // Refresh the list after a short delay
        setTimeout(() => {
            fetchMatches();
        }, 300);
    } catch (error) {
        console.error('Error saving match:', error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

function handleEdit(match) {
    const form = document.getElementById('match-form');
    form.dataset.editingId = match.id;

    document.getElementById('home-team').value = match.home_team;
    document.getElementById('away-team').value = match.away_team;
    document.getElementById('competition').value = match.competition;
    document.getElementById('venue').value = match.venue || 'TBA'; // Set venue or default
    document.getElementById('match-date').value = match.match_date.slice(0, 16); // Format for datetime-local
    document.getElementById('match-status').value = match.status;
    document.getElementById('home-score').value = match.home_score;
    document.getElementById('away-score').value = match.away_score;

    document.getElementById('match-report').value = match.match_report || '';

    // Populate lineups
    setLineupForm(match.home_lineup, 'home-starting', 'home-subs');
    setLineupForm(match.away_lineup, 'away-starting', 'away-subs');

    // Populate logos
    const homeLogoInput = document.getElementById('home-logo');
    const awayLogoInput = document.getElementById('away-logo');
    const homeLogoPreview = document.getElementById('home-logo-preview');
    const awayLogoPreview = document.getElementById('away-logo-preview');

    if (homeLogoInput) homeLogoInput.value = match.home_logo || '';
    if (awayLogoInput) awayLogoInput.value = match.away_logo || '';

    if (homeLogoPreview) {
        if (match.home_logo) {
            const logoUrl = match.home_logo.startsWith('http') ? match.home_logo : `../frontend/${match.home_logo}`;
            homeLogoPreview.querySelector('img').src = logoUrl;
            homeLogoPreview.style.display = 'block';
        } else {
            homeLogoPreview.style.display = 'none';
        }
    }

    if (awayLogoPreview) {
        if (match.away_logo) {
            const logoUrl = match.away_logo.startsWith('http') ? match.away_logo : `../frontend/${match.away_logo}`;
            awayLogoPreview.querySelector('img').src = logoUrl;
            awayLogoPreview.style.display = 'block';
        } else {
            awayLogoPreview.style.display = 'none';
        }
    }

    form.querySelector('h3').textContent = 'Edit Match';
    form.scrollIntoView({ behavior: 'smooth' });
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this match?')) return;

    try {
        const response = await fetch(`${API_URL}/matches.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete match');
        showNotification('Match deleted successfully!', 'success');
        await fetchMatches();
    } catch (error) {
        console.error('Error deleting match:', error);
        showNotification(`Could not delete match: ${error.message}`, 'error');
    }
}

export function initMatches() {
    const matchForm = document.getElementById('match-form');
    const matchesTab = document.getElementById('matches-tab');
    const table = document.querySelector('#matches-tab .admin-table');

    if (matchForm) {
        matchForm.addEventListener('submit', handleMatchSubmit);
        matchForm.addEventListener('reset', () => {
            document.getElementById('match-report').value = '';
            delete matchForm.dataset.editingId;
            const matchesTabH3 = document.querySelector('#matches-tab h2, #matches-tab h3');
            if (matchesTabH3) matchesTabH3.textContent = 'Add New Match';

            // Clear logo previews
            const homeLogoPreview = document.getElementById('home-logo-preview');
            const awayLogoPreview = document.getElementById('away-logo-preview');
            if (homeLogoPreview) homeLogoPreview.style.display = 'none';
            if (awayLogoPreview) awayLogoPreview.style.display = 'none';
        });

        // Handle Logo Uploads via AdminImagePicker
        matchForm.addEventListener('click', (e) => {
            const uploadBtn = e.target.closest('.btn-upload');
            if (!uploadBtn) return;

            const targetId = uploadBtn.dataset.target;
            if (!targetId) return;

            if (window.AdminImagePicker) {
                window.AdminImagePicker.open((path) => {
                    const input = document.getElementById(targetId);
                    if (input) {
                        input.value = path;
                        // Trigger change to update any auto-previews
                        input.dispatchEvent(new Event('change', { bubbles: true }));

                        // Update our specific manual preview
                        const previewContainer = document.getElementById(`${targetId}-preview`);
                        if (previewContainer) {
                            const img = previewContainer.querySelector('img');
                            if (img) {
                                img.src = path.startsWith('http') ? path : `../${path}`;
                                previewContainer.style.display = 'block';
                            }
                        }
                    }
                });
            } else {
                showNotification('Image picker not loaded.', 'error');
            }
        });
    }

    if (table) {
        table.addEventListener('click', async (e) => {
            const target = e.target.closest('button');
            if (!target) return;

            const id = target.dataset.id;
            if (!id) return;

            if (target.classList.contains('btn-delete')) {
                await handleDelete(id);
            } else if (target.classList.contains('btn-edit')) {
                try {
                    const response = await fetch(`${API_URL}/matches.php?id=${id}`);
                    if (!response.ok) throw new Error('Failed to fetch match');
                    const match = await response.json();
                    if (match) handleEdit(match);
                } catch (error) {
                    console.error('Error fetching match:', error);
                    showNotification('Could not load match details.', 'error');
                }
            }
        });
    }

    // Initial fetch if the tab is already active on load
    if (matchesTab && matchesTab.classList.contains('active')) {
        fetchMatches();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'matches') {
            fetchMatches();
        }
    });
}

