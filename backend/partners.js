import { showNotification } from './admin.js';
const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

export function initPartners() {
    const partnerForm = document.getElementById('partner-form');
    const partnersTab = document.getElementById('partners-tab');
    const cancelEditBtn = document.getElementById('cancel-partner-edit');

    if (partnerForm) {
        partnerForm.addEventListener('submit', handlePartnerSubmit);
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', resetPartnerForm);
        }
    }

    // Initial fetch if the tab is already active on load
    if (partnersTab && partnersTab.classList.contains('active')) {
        fetchPartners();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'partners') {
            fetchPartners();
        }
    });

    // Handle image preview for the partner logo
    const partnerLogoInput = document.getElementById('partner-logo');
    if (partnerLogoInput) {
        partnerLogoInput.addEventListener('input', () => {
            updateLogoPreview(partnerLogoInput.value);
        });

        // Also watch for changes if the picker updates it
        const originalValue = partnerLogoInput.value;
        setInterval(() => {
            if (partnerLogoInput.value !== originalValue) {
                updateLogoPreview(partnerLogoInput.value);
            }
        }, 500);
    }
}

function updateLogoPreview(path) {
    const previewContainer = document.getElementById('partner-logo-preview');
    const previewImg = previewContainer ? previewContainer.querySelector('img') : null;

    if (path && path.trim()) {
        if (previewContainer && previewImg) {
            let fullPath = path.trim();
            if (!fullPath.startsWith('http')) {
                const basePath = '/mombasahamlets_web/frontend/';
                fullPath = basePath + fullPath.replace(/^\//, '').replace(/^frontend\//, '');
            }
            previewImg.src = fullPath;
            previewContainer.style.display = 'block';
        }
    } else if (previewContainer) {
        previewContainer.style.display = 'none';
    }
}

async function fetchPartners() {
    const list = document.getElementById('partners-list');
    if (!list) return;

    list.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading partners...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/partners.php`);
        if (!response.ok) throw new Error('Failed to fetch partners');
        const partners = await response.json();

        list.innerHTML = '';
        if (!partners || partners.length === 0) {
            list.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;">No partners found.</td></tr>';
            return;
        }

        partners.forEach(partner => {
            let logoUrl = partner.logo_url;
            if (!logoUrl.startsWith('http')) {
                logoUrl = '/mombasahamlets_web/frontend/' + logoUrl.replace(/^\//, '').replace(/^frontend\//, '');
            }

            const row = `
                <tr>
                    <td><img src="${logoUrl}" alt="${partner.name}" style="max-height: 40px; border-radius: 4px;"></td>
                    <td><strong>${partner.name}</strong></td>
                    <td>${partner.category}</td>
                    <td><span class="status-badge status-${partner.status.toLowerCase()}">${partner.status}</span></td>
                    <td>${partner.display_order}</td>
                    <td class="actions">
                        <button class="btn-action btn-edit" data-id="${partner.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" data-id="${partner.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            list.insertAdjacentHTML('beforeend', row);
        });

        list.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => handleEditPartner(btn.dataset.id));
        });
        list.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleDeletePartner(btn.dataset.id));
        });

    } catch (error) {
        console.error('Error fetching partners:', error);
        list.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#e74c3c;">Failed to load partners.</td></tr>';
        showNotification('Could not load partners.', 'error');
    }
}

async function handlePartnerSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const partnerId = document.getElementById('partner-id').value;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;

    const partnerData = {
        name: document.getElementById('partner-name').value,
        category: document.getElementById('partner-category').value,
        logo_url: document.getElementById('partner-logo').value.replace(/^\/mombasahamlets_web\/frontend\//, ''),
        website_url: document.getElementById('partner-url').value,
        status: document.getElementById('partner-status').value,
        display_order: parseInt(document.getElementById('partner-order').value) || 0
    };

    const method = partnerId ? 'PUT' : 'POST';
    const url = partnerId ? `${API_URL}/partners.php?id=${partnerId}` : `${API_URL}/partners.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(partnerData)
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Failed to save partner');
        }

        showNotification(`Partner ${partnerId ? 'updated' : 'added'} successfully!`, 'success');
        resetPartnerForm();
        fetchPartners();
    } catch (error) {
        console.error('Error saving partner:', error);
        showNotification(error.message, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

async function handleEditPartner(id) {
    try {
        const response = await fetch(`${API_URL}/partners.php?id=${id}`);
        if (!response.ok) throw new Error('Failed to fetch partner details');
        const partner = await response.json();

        document.getElementById('partner-id').value = partner.id;
        document.getElementById('partner-name').value = partner.name;
        document.getElementById('partner-category').value = partner.category;
        document.getElementById('partner-logo').value = partner.logo_url;
        document.getElementById('partner-url').value = partner.website_url || '';
        document.getElementById('partner-status').value = partner.status;
        document.getElementById('partner-order').value = partner.display_order;

        document.getElementById('partner-form-title').textContent = 'Edit Partner';
        document.getElementById('cancel-partner-edit').style.display = 'inline-block';
        updateLogoPreview(partner.logo_url);

        document.getElementById('partner-form').scrollIntoView({ behavior: 'smooth' });
    } catch (error) {
        console.error('Error loading partner for edit:', error);
        showNotification('Could not load partner details.', 'error');
    }
}

async function handleDeletePartner(id) {
    if (!confirm('Are you sure you want to delete this partner?')) return;

    try {
        const response = await fetch(`${API_URL}/partners.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete partner');

        showNotification('Partner deleted successfully!', 'success');
        fetchPartners();
    } catch (error) {
        console.error('Error deleting partner:', error);
        showNotification('Could not delete partner.', 'error');
    }
}

function resetPartnerForm() {
    const form = document.getElementById('partner-form');
    if (form) form.reset();
    document.getElementById('partner-id').value = '';
    document.getElementById('partner-form-title').textContent = 'Add New Partner';
    document.getElementById('cancel-partner-edit').style.display = 'none';
    updateLogoPreview('');
}
