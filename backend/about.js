import { showNotification } from './admin.js';
const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

export function initAbout() {
    const generalForm = document.getElementById('about-general-form');
    const achievementForm = document.getElementById('achievement-form');
    const managementForm = document.getElementById('management-form');
    const addAchievementBtn = document.getElementById('add-achievement-btn');
    const addManagementBtn = document.getElementById('add-management-btn');
    const aboutTab = document.getElementById('about-tab');

    if (generalForm) generalForm.addEventListener('submit', handleGeneralSubmit);
    if (achievementForm) achievementForm.addEventListener('submit', handleAchievementSubmit);
    if (managementForm) managementForm.addEventListener('submit', handleManagementSubmit);

    if (addAchievementBtn) {
        addAchievementBtn.addEventListener('click', () => {
            resetAchievementForm();
            document.getElementById('achievement-modal').classList.add('active');
        });
    }

    if (addManagementBtn) {
        addManagementBtn.addEventListener('click', () => {
            resetManagementForm();
            document.getElementById('management-modal').classList.add('active');
        });
    }

    // Initial fetch if active
    if (aboutTab && aboutTab.classList.contains('active')) {
        loadAboutData();
    }

    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'about') {
            loadAboutData();
        }
    });
}

async function loadAboutData() {
    fetchGeneralSettings();
    fetchAchievements();
    fetchManagement();
}

async function fetchGeneralSettings() {
    try {
        const response = await fetch(`${API_URL}/settings.php`);
        const settings = await response.json();

        const keys = ['about_hero_title', 'about_hero_subtitle', 'about_history', 'about_history_image', 'about_mission', 'about_vision', 'about_values'];
        keys.forEach(key => {
            const el = document.getElementById(key);
            if (el) el.value = settings[key] || '';
        });
    } catch (error) {
        console.error('Error fetching settings:', error);
    }
}

async function handleGeneralSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {};
    formData.forEach((value, key) => data[key] = value);

    try {
        const response = await fetch(`${API_URL}/settings.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (response.ok) {
            showNotification('General content updated!', 'success');
        } else {
            throw new Error('Failed to update content');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

// Achievements
async function fetchAchievements() {
    const list = document.getElementById('achievements-list');
    if (!list) return;

    try {
        const response = await fetch(`${API_URL}/achievements.php`);
        const items = await response.json();
        list.innerHTML = items.map(item => `
            <tr>
                <td><i class="${item.icon}"></i></td>
                <td>${item.title}</td>
                <td>${item.years}</td>
                <td>${item.display_order}</td>
                <td class="actions">
                    <button class="btn-action edit-achievement" data-id="${item.id}"><i class="fas fa-edit"></i></button>
                    <button class="btn-action delete-achievement" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');

        list.querySelectorAll('.edit-achievement').forEach(btn => {
            btn.addEventListener('click', () => editAchievement(btn.dataset.id));
        });
        list.querySelectorAll('.delete-achievement').forEach(btn => {
            btn.addEventListener('click', () => deleteAchievement(btn.dataset.id));
        });
    } catch (error) {
        console.error('Error fetching achievements:', error);
    }
}

async function handleAchievementSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('achievement-id').value;
    const data = {
        title: document.getElementById('achievement-title').value,
        years: document.getElementById('achievement-years').value,
        icon: document.getElementById('achievement-icon').value,
        display_order: parseInt(document.getElementById('achievement-order').value)
    };

    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_URL}/achievements.php?id=${id}` : `${API_URL}/achievements.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (response.ok) {
            showNotification('Achievement saved!', 'success');
            document.getElementById('achievement-modal').classList.remove('active');
            fetchAchievements();
        }
    } catch (error) {
        showNotification('Error saving achievement', 'error');
    }
}

async function editAchievement(id) {
    const response = await fetch(`${API_URL}/achievements.php?id=${id}`);
    const item = await response.json();
    document.getElementById('achievement-id').value = item.id;
    document.getElementById('achievement-title').value = item.title;
    document.getElementById('achievement-years').value = item.years;
    document.getElementById('achievement-icon').value = item.icon;
    document.getElementById('achievement-order').value = item.display_order;
    document.getElementById('achievement-modal-title').textContent = 'Edit Achievement';
    document.getElementById('achievement-modal').classList.add('active');
}

async function deleteAchievement(id) {
    if (confirm('Delete this achievement?')) {
        await fetch(`${API_URL}/achievements.php?id=${id}`, { method: 'DELETE' });
        fetchAchievements();
        showNotification('Achievement deleted', 'success');
    }
}

function resetAchievementForm() {
    document.getElementById('achievement-form').reset();
    document.getElementById('achievement-id').value = '';
    document.getElementById('achievement-modal-title').textContent = 'Add Achievement';
}

// Management
async function fetchManagement() {
    const list = document.getElementById('management-list');
    if (!list) return;

    try {
        const response = await fetch(`${API_URL}/management.php`);
        const items = await response.json();
        list.innerHTML = items.map(item => `
            <tr>
                <td><img src="${item.image_url ? (window.PROJECT_BASE + '/frontend/' + item.image_url.replace(/^\/?frontend\//, '')).replace(/\/{2,}/g, '/') : '../frontend/images/29.jpg'}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"></td>
                <td>${item.name}</td>
                <td>${item.role}</td>
                <td>${item.display_order}</td>
                <td class="actions">
                    <button class="btn-action edit-management" data-id="${item.id}"><i class="fas fa-edit"></i></button>
                    <button class="btn-action delete-management" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');

        list.querySelectorAll('.edit-management').forEach(btn => {
            btn.addEventListener('click', () => editManagement(btn.dataset.id));
        });
        list.querySelectorAll('.delete-management').forEach(btn => {
            btn.addEventListener('click', () => deleteManagement(btn.dataset.id));
        });
    } catch (error) {
        console.error('Error fetching management:', error);
    }
}

async function handleManagementSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('management-id').value;
    const data = {
        name: document.getElementById('management-name').value,
        role: document.getElementById('management-role').value,
        bio: document.getElementById('management-bio').value,
        image_url: document.getElementById('management-image').value,
        display_order: parseInt(document.getElementById('management-order').value)
    };

    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_URL}/management.php?id=${id}` : `${API_URL}/management.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (response.ok) {
            showNotification('Team member saved!', 'success');
            document.getElementById('management-modal').classList.remove('active');
            fetchManagement();
        }
    } catch (error) {
        showNotification('Error saving team member', 'error');
    }
}

async function editManagement(id) {
    const response = await fetch(`${API_URL}/management.php?id=${id}`);
    const item = await response.json();
    document.getElementById('management-id').value = item.id;
    document.getElementById('management-name').value = item.name;
    document.getElementById('management-role').value = item.role;
    document.getElementById('management-bio').value = item.bio;
    document.getElementById('management-image').value = item.image_url;
    document.getElementById('management-order').value = item.display_order;
    document.getElementById('management-modal-title').textContent = 'Edit Team Member';
    document.getElementById('management-modal').classList.add('active');
}

async function deleteManagement(id) {
    if (confirm('Delete this team member?')) {
        await fetch(`${API_URL}/management.php?id=${id}`, { method: 'DELETE' });
        fetchManagement();
        showNotification('Team member deleted', 'success');
    }
}

function resetManagementForm() {
    document.getElementById('management-form').reset();
    document.getElementById('management-id').value = '';
    document.getElementById('management-modal-title').textContent = 'Add Team Member';
}
