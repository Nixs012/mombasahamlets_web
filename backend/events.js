// backend/admin/js/modules/events.js
import { showNotification } from './admin.js';

const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

// Function to fetch and display events
async function fetchEvents() {
    const eventsTableBody = document.querySelector('#events-tab .admin-table tbody');
    if (!eventsTableBody) return;

    // Show loading state
    eventsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading events...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/events.php`);
        if (!response.ok) throw new Error('Failed to fetch events');
        const events = await response.json();

        eventsTableBody.innerHTML = ''; // Clear loading state

        if (!events || events.length === 0) {
            eventsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;">No events found. Add one using the form above.</td></tr>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/29.jpg';

        events.forEach(event => {
            // Build image URL - events use 'image' or 'image_url' field
            let imageUrl = fallbackImagePath;
            const imgField = event.image_url || event.image;
            if (imgField && imgField.trim()) {
                let imgPath = imgField.trim();
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');
                if (imgPath && imgPath.length > 0) {
                    imageUrl = `/mombasahamlets_web/frontend/${imgPath}`;
                }
            }

            const row = `
                <tr>
                    <td>
                        <img src="${imageUrl}" alt="${event.title}" class="table-event-image" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                    </td>
                    <td>${event.title}</td>
                    <td>${event.category}</td>
                    <td>${new Date(event.event_date).toLocaleDateString()}</td>
                    <td>${event.status || 'scheduled'}</td>
                    <td>${event.location}</td>
                    <td class="actions">
                        <button class="btn-edit" data-id="${event.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" data-id="${event.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            eventsTableBody.insertAdjacentHTML('beforeend', row);
        });

        // Re-attach event listeners after rendering
        document.querySelectorAll('#events-tab .btn-edit').forEach(btn => {
            btn.addEventListener('click', () => handleEdit(events.find(e => e.id == btn.dataset.id)));
        });
        document.querySelectorAll('#events-tab .btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleDelete(btn.dataset.id));
        });

    } catch (error) {
        console.error('Error fetching events:', error);
        showNotification('Could not load events.', 'error');
    }
}

// Function to handle form submission for adding/editing a event
async function handleEventSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const eventId = form.dataset.editingId;

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    let imageUrl = document.getElementById('event-image').value;
    // Normalize image_url - remove unnecessary prefixes
    if (imageUrl) {
        imageUrl = imageUrl.replace(/^\/mombasahamlets_web\//, '');
        imageUrl = imageUrl.replace(/^\/?frontend\//, '');
        imageUrl = imageUrl.replace(/^\//, '');
        if (imageUrl.length === 0) {
            imageUrl = null;
        }
    }

    const eventData = {
        title: document.getElementById('event-title').value,
        description: document.getElementById('event-description').value,
        event_date: document.getElementById('event-date').value,
        event_time: document.getElementById('event-time') ? document.getElementById('event-time').value : undefined,
        location: document.getElementById('event-location').value,
        category: document.getElementById('event-category').value,
        status: document.getElementById('event-status') ? document.getElementById('event-status').value : 'scheduled',
        image_url: imageUrl || null
    };

    const method = eventId ? 'PUT' : 'POST';
    const url = eventId ? `${API_URL}/events.php?id=${eventId}` : `${API_URL}/events.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save event');
        }

        showNotification(`Event ${eventId ? 'updated' : 'added'} successfully!`, 'success');
        form.reset();
        delete form.dataset.editingId; // Clear editing state
        const eventsTabH2 = document.querySelector('#events-tab h2');
        if (eventsTabH2) eventsTabH2.textContent = 'Events Management';
        const eventFormTitle = document.getElementById('event-form-title');
        if (eventFormTitle) eventFormTitle.textContent = 'Add New Event';
        // Clear image preview if exists
        const imageInput = document.getElementById('event-image');
        if (imageInput) {
            const preview = imageInput.parentNode.querySelector('img');
            if (preview) preview.src = '';
        }
        // Refresh the list after a short delay
        setTimeout(() => {
            fetchEvents();
        }, 300);
    } catch (error) {
        console.error('Error saving event:', error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

// Function to handle editing a event
function handleEdit(event) {
    const form = document.getElementById('event-form');
    form.dataset.editingId = event.id;
    document.getElementById('event-form-title').textContent = 'Edit Event';

    document.getElementById('event-title').value = event.title;
    document.getElementById('event-description').value = event.description;
    try {
        const dt = new Date(event.event_date + 'T' + (event.event_time || '00:00:00'));
        document.getElementById('event-date').value = dt.toISOString().split('T')[0];
        if (document.getElementById('event-time')) {
            const time = (event.event_time || '').slice(0, 5) || dt.toTimeString().slice(0, 5);
            document.getElementById('event-time').value = time;
        }
    } catch (e) {
        console.error("Error parsing event date/time:", e);
        document.getElementById('event-date').value = event.event_date || '';
    }
    document.getElementById('event-location').value = event.location;
    document.getElementById('event-category').value = event.category;
    if (document.getElementById('event-status')) document.getElementById('event-status').value = event.status || 'scheduled';
    if (document.getElementById('event-image')) document.getElementById('event-image').value = event.image_url || event.image || '';

    form.scrollIntoView({ behavior: 'smooth' });
}

// Function to handle deleting a event
async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this event?')) return;

    try {
        const response = await fetch(`${API_URL}/events.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete event');
        showNotification('Event deleted successfully!', 'success');
        await fetchEvents(); // Refresh the list
    } catch (error) {
        console.error('Error deleting event:', error);
        showNotification(`Could not delete the event: ${error.message}`, 'error');
    }
}

export function initEvents() {
    const eventForm = document.getElementById('event-form');
    const eventsTable = document.querySelector('#events-tab .admin-table');

    if (eventForm) {
        eventForm.addEventListener('submit', handleEventSubmit);
    }

    // Fetch events when tab becomes visible
    const eventsTab = document.getElementById('events-tab');
    // Initial fetch if the tab is already active on load
    if (eventsTab && eventsTab.classList.contains('active')) {
        fetchEvents();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'events') {
            fetchEvents();
        }
    });
}
