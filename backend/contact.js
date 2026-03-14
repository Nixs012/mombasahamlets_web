import { showNotification } from './admin.js';
const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

export function initContact() {
    const detailsForm = document.getElementById('contact-details-form');
    const faqForm = document.getElementById('faq-form');
    const addFaqBtn = document.getElementById('add-faq-btn');
    const contactTab = document.getElementById('contact-tab');

    if (detailsForm) detailsForm.addEventListener('submit', handleDetailsSubmit);
    if (faqForm) faqForm.addEventListener('submit', handleFaqSubmit);

    if (addFaqBtn) {
        addFaqBtn.addEventListener('click', () => {
            resetFaqForm();
            document.getElementById('faq-modal').classList.add('active');
        });
    }

    // Initial fetch if active
    if (contactTab && contactTab.classList.contains('active')) {
        loadContactData();
    }

    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'contact') {
            loadContactData();
        }
    });
}

async function loadContactData() {
    fetchContactSettings();
    fetchFaqs();
}

async function fetchContactSettings() {
    try {
        const response = await fetch(`${API_URL}/settings.php`);
        const settings = await response.json();

        const keys = [
            'contact_stadium_address',
            'contact_office_phone',
            'contact_ticketing_phone',
            'contact_academy_phone',
            'contact_general_email',
            'contact_tickets_email',
            'contact_partnerships_email',
            'contact_office_hours'
        ];
        keys.forEach(key => {
            const el = document.getElementById(key);
            if (el) el.value = settings[key] || '';
        });
    } catch (error) {
        console.error('Error fetching contact settings:', error);
    }
}

async function handleDetailsSubmit(e) {
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
            showNotification('Contact details updated!', 'success');
        } else {
            throw new Error('Failed to update details');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

// FAQs
async function fetchFaqs() {
    const list = document.getElementById('faqs-list');
    if (!list) return;

    try {
        const response = await fetch(`${API_URL}/faqs.php`);
        const items = await response.json();
        list.innerHTML = items.map(item => `
            <tr>
                <td>${item.question}</td>
                <td>${item.display_order}</td>
                <td class="actions">
                    <button class="btn-action edit-faq" data-id="${item.id}"><i class="fas fa-edit"></i></button>
                    <button class="btn-action delete-faq" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');

        list.querySelectorAll('.edit-faq').forEach(btn => {
            btn.addEventListener('click', () => editFaq(btn.dataset.id));
        });
        list.querySelectorAll('.delete-faq').forEach(btn => {
            btn.addEventListener('click', () => deleteFaq(btn.dataset.id));
        });
    } catch (error) {
        console.error('Error fetching FAQs:', error);
    }
}

async function handleFaqSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('faq-id').value;
    const data = {
        question: document.getElementById('faq-question').value,
        answer: document.getElementById('faq-answer').value,
        display_order: parseInt(document.getElementById('faq-order').value)
    };

    const method = id ? 'PUT' : 'POST';
    const url = id ? `${API_URL}/faqs.php?id=${id}` : `${API_URL}/faqs.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (response.ok) {
            showNotification('FAQ saved!', 'success');
            document.getElementById('faq-modal').classList.remove('active');
            fetchFaqs();
        }
    } catch (error) {
        showNotification('Error saving FAQ', 'error');
    }
}

async function editFaq(id) {
    const response = await fetch(`${API_URL}/faqs.php?id=${id}`);
    const item = await response.json();
    document.getElementById('faq-id').value = item.id;
    document.getElementById('faq-question').value = item.question;
    document.getElementById('faq-answer').value = item.answer;
    document.getElementById('faq-order').value = item.display_order;
    document.getElementById('faq-modal-title').textContent = 'Edit FAQ';
    document.getElementById('faq-modal').classList.add('active');
}

async function deleteFaq(id) {
    if (confirm('Delete this FAQ?')) {
        await fetch(`${API_URL}/faqs.php?id=${id}`, { method: 'DELETE' });
        fetchFaqs();
        showNotification('FAQ deleted', 'success');
    }
}

function resetFaqForm() {
    document.getElementById('faq-form').reset();
    document.getElementById('faq-id').value = '';
    document.getElementById('faq-modal-title').textContent = 'Add FAQ';
}
