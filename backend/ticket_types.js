export async function initTicketTypes() {
    const ticketForm = document.getElementById('ticket-type-form');
    const eventSelect = document.getElementById('ticket-event-id');
    const filterSelect = document.getElementById('filter-ticket-event');
    const ticketList = document.getElementById('ticket-types-list');

    if (!ticketForm || !eventSelect || !filterSelect || !ticketList) return;

    // Fetch and populate events
    async function populateEvents() {
        try {
            const resp = await fetch((window.API_URL || '/backend/api') + '/events.php');
            if (!resp.ok) throw new Error('Failed to fetch events');
            const events = await resp.json();

            // Clear previous options (except the first one)
            eventSelect.innerHTML = '<option value="">-- Choose Event --</option>';
            filterSelect.innerHTML = '<option value="all">All Events</option>';

            events.forEach(event => {
                const opt = document.createElement('option');
                opt.value = event.id;
                opt.textContent = event.title;
                eventSelect.appendChild(opt.cloneNode(true));
                filterSelect.appendChild(opt);
            });
        } catch (err) {
            console.error('Error populating events:', err);
        }
    }

    // Fetch and display ticket types
    async function fetchTicketTypes(eventId = 'all') {
        try {
            const url = eventId === 'all'
                ? (window.API_URL || '/backend/api') + '/ticket_types.php'
                : (window.API_URL || '/backend/api') + '/ticket_types.php?event_id=' + eventId;

            const resp = await fetch(url);
            if (!resp.ok) throw new Error('Failed to fetch ticket types');
            const data = await resp.json();

            displayTicketTypes(data);
        } catch (err) {
            console.error('Error fetching ticket types:', err);
        }
    }

    function displayTicketTypes(types) {
        ticketList.innerHTML = '';
        if (types.length === 0) {
            ticketList.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">No ticket types found.</td></tr>';
            return;
        }

        types.forEach(type => {
            // Find event title (ideally the API should return it, but we can look it up from the select)
            const eventOpt = Array.from(eventSelect.options).find(opt => opt.value == type.event_id);
            const eventTitle = eventOpt ? eventOpt.textContent : 'Unknown Event';

            const row = `
                <tr>
                    <td>${eventTitle}</td>
                    <td>${type.name}</td>
                    <td>${parseFloat(type.price).toLocaleString()} KES</td>
                    <td>${type.max_quantity > 0 ? type.max_quantity : 'Unlimited'}</td>
                    <td class="actions">
                        <button class="btn-delete" data-id="${type.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            ticketList.insertAdjacentHTML('beforeend', row);
        });

        // Add delete listeners
        ticketList.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = async () => {
                if (!confirm('Are you sure you want to delete this ticket type?')) return;
                const id = btn.dataset.id;
                try {
                    const resp = await fetch((window.API_URL || '/backend/api') + '/ticket_types.php?id=' + id, {
                        method: 'DELETE'
                    });
                    if (!resp.ok) throw new Error('Failed to delete');
                    await fetchTicketTypes(filterSelect.value);
                    if (window.showNotification) window.showNotification('Ticket type deleted successfully', 'success');
                } catch (err) {
                    alert('Error: ' + err.message);
                }
            };
        });
    }

    // Handle form submission
    ticketForm.onsubmit = async (e) => {
        e.preventDefault();
        const eventId = eventSelect.value;
        const name = document.getElementById('ticket-name').value;
        const price = document.getElementById('ticket-price').value;
        const qty = document.getElementById('ticket-quantity').value;

        try {
            const resp = await fetch((window.API_URL || '/backend/api') + '/ticket_types.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    event_id: eventId,
                    name: name,
                    price: price,
                    max_quantity: qty
                })
            });

            const result = await resp.json();
            if (!resp.ok) throw new Error(result.error || 'Failed to add ticket type');

            ticketForm.reset();
            await fetchTicketTypes(filterSelect.value);
            if (window.showNotification) window.showNotification('Ticket type added successfully', 'success');
        } catch (err) {
            alert('Error: ' + err.message);
        }
    };

    filterSelect.onchange = () => {
        fetchTicketTypes(filterSelect.value);
    };

    // Initial load
    await populateEvents();
    await fetchTicketTypes();

    // Initial fetch if the tab is already active on load
    const ticketingTab = document.getElementById('ticketing-tab');
    if (ticketingTab && ticketingTab.classList.contains('active')) {
        populateEvents();
        fetchTicketTypes();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'ticketing') {
            populateEvents();
            fetchTicketTypes(filterSelect.value);
        }
    });
}
