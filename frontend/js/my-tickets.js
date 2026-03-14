/**
 * my-tickets.js
 * Handles fetching and rendering of user tickets.
 */
document.addEventListener('DOMContentLoaded', function () {
    const ticketsGrid = document.getElementById('tickets-grid');
    const loadingMessage = document.getElementById('tickets-loading');
    const noTicketsMessage = document.getElementById('no-tickets-message');
    const apiUrl = window.API_URL || '../backend/api';

    fetchTickets();

    async function fetchTickets() {
        try {
            const response = await fetch(`${apiUrl}/my_tickets.php`);

            if (response.status === 401) {
                // Not logged in, redirect to login
                window.location.href = 'login.php?redirect=my_tickets.php';
                return;
            }

            if (!response.ok) {
                throw new Error('Failed to fetch tickets');
            }

            const tickets = await response.json();

            loadingMessage.style.display = 'none';

            if (tickets.length === 0) {
                noTicketsMessage.style.display = 'block';
                return;
            }

            renderTickets(tickets);

        } catch (error) {
            console.error('Error:', error);
            loadingMessage.innerHTML = `<i class="fas fa-exclamation-circle" style="color: #DA291C; font-size: 3em;"></i><h2>Something went wrong</h2><p>${error.message}</p>`;
        }
    }

    function renderTickets(tickets) {
        ticketsGrid.innerHTML = '';
        ticketsGrid.style.display = 'grid';

        tickets.forEach(ticket => {
            const ticketCard = createTicketCard(ticket);
            ticketsGrid.appendChild(ticketCard);
        });
    }

    function createTicketCard(ticket) {
        const div = document.createElement('div');
        div.className = 'ticket-card';

        const eventDate = new Date(ticket.event_date).toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Use qrserver.com for QR codes
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(ticket.qr_code)}`;

        // Normalize image path
        let imgPath = ticket.image_url || 'images/logo1.jpeg';
        if (imgPath.startsWith('/')) imgPath = imgPath.substring(1);
        if (imgPath.indexOf('/') === -1) imgPath = 'images/' + imgPath;

        div.innerHTML = `
            <div class="ticket-status-badge status-${ticket.ticket_status.toLowerCase()}">
                ${ticket.ticket_status}
            </div>
            <div class="ticket-visual">
                <img src="${imgPath}" alt="${ticket.event_title}" onerror="this.src='images/logo1.jpeg'">
            </div>
            <div class="ticket-content">
                <h3 class="ticket-event-title">${ticket.event_title}</h3>
                <div class="ticket-details">
                    <div class="ticket-detail-item">
                        <i class="far fa-calendar-alt"></i>
                        <span>${eventDate}</span>
                    </div>
                    <div class="ticket-detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${ticket.location}</span>
                    </div>
                    <div class="ticket-type-label">${ticket.ticket_type_name}</div>
                </div>
                <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Ticket</button>
            </div>
            <div class="ticket-footer">
                <div class="qr-section">
                    <img src="${qrUrl}" alt="QR Code">
                </div>
                <div class="ticket-info-summary">
                    <span class="ticket-code-label">Ticket Code</span>
                    <span class="ticket-code-value">${ticket.ticket_code}</span>
                </div>
            </div>
        `;

        return div;
    }
});
