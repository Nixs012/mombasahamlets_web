document.addEventListener('DOMContentLoaded', function () {
    const eventDetailContainer = document.getElementById('event-detail-container');
    const params = new URLSearchParams(window.location.search);
    const eventId = params.get('id');

    if (!eventId) {
        eventDetailContainer.innerHTML = '<section class="container"><p>No event ID provided. Please go back to the <a href="events.php">events page</a>.</p></section>';
        return;
    }

    fetchEventDetails(eventId);

    async function fetchEventDetails(id) {
        if (!eventDetailContainer) return;
        eventDetailContainer.innerHTML = '<div class="loading-spinner"></div>';

        try {
            const response = await fetch((window.API_URL || '/backend/api') + '/events.php?id=' + id);
            if (!response.ok) throw new Error('Failed to fetch event details');
            const event = await response.json();
            displayEventDetails(event);
        } catch (error) {
            console.error('Error fetching event details:', error);
            eventDetailContainer.innerHTML = '<section class="container"><p>Event not found or error loading details. Please check the URL or go back to the <a href="events.php">events page</a>.</p></section>';
        }
    }

    function displayEventDetails(event) {
        document.title = `${event.title} - Mombasa Hamlets FC`;

        // Normalize image path
        let imageUrl = event.image_url || event.image || 'images/logo1.jpeg';
        if (imageUrl && !imageUrl.startsWith('http') && !imageUrl.startsWith('images/')) {
            // Remove leading slash if present to make it relative
            if (imageUrl.startsWith('/')) imageUrl = imageUrl.substring(1);

            // If it doesn't already have images/ at the start, prepend it
            // Only if it doesn't look like a subdirectory path already (has /)
            if (imageUrl.indexOf('/') === -1) {
                imageUrl = 'images/' + imageUrl;
            }
        }

        // Determine ticket type for modal (fallback logic)
        let ticketType = 'rsvp';
        const category = (event.category || '').toLowerCase();

        // Priority: If tickets are added, use 'match' type which leads to checkout
        if (event.ticket_count > 0) {
            ticketType = 'match';
        } else {
            if (category === 'match') ticketType = 'match';
            else if (category === 'community') ticketType = 'clinic';
            else if (category === 'special') ticketType = 'special';
        }

        const isPast = new Date(event.event_date) < new Date();

        let eventHTML = `
            <section class="page-hero" style="background-image: url('${imageUrl}');">
                <div class="hero-overlay"></div>
                <div class="container hero-content">
                    <h1 class="animate-pop-in">${event.title}</h1>
                    <div class="event-meta-single animate-pop-in">
                        ${event.event_date ? `<span><i class="far fa-calendar-alt"></i> ${new Date(event.event_date).toLocaleDateString()}</span>` : ''}
                        ${event.event_time ? `<span><i class="far fa-clock"></i> ${event.event_time}</span>` : ''}
                        <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
                    </div>
                </div>
            </section>
            <section class="event-content-section">
                <div class="container">
                    <div class="event-details-layout">
                        <div class="event-description">
                            <h2>About the Event</h2>
                            <p>${event.description || 'No description available for this event.'}</p>
                        </div>
                        <aside class="event-sidebar">
                            <h3>Event Details</h3>
                            <ul class="details-list">
                                ${event.event_date ? `<li><strong>Date:</strong> ${new Date(event.event_date).toLocaleDateString()}</li>` : ''}
                                ${event.event_time ? `<li><strong>Time:</strong> ${event.event_time}</li>` : ''}
                                <li><strong>Location:</strong> ${event.location}</li>
                                <li><strong>Category:</strong> ${category.charAt(0).toUpperCase() + category.slice(1)}</li>
                                <li><strong>Status:</strong> ${event.status || 'Scheduled'}</li>
                            </ul>
                            ${!isPast ? `<button class="btn btn-primary btn-block action-btn" data-event-name="${event.title}" data-ticket-type="${ticketType}">Get Tickets / Register</button>` : `<a href="events.php" class="btn btn-secondary btn-block">Back to Events</a>`}
                        </aside>
                    </div>
                </div>
            </section>
        `;
        eventDetailContainer.innerHTML = eventHTML;

        // Re-attach modal trigger if needed
        const actionBtn = eventDetailContainer.querySelector('.action-btn');
        if (actionBtn) {
            actionBtn.addEventListener('click', () => {
                const eventId = params.get('id');
                window.location.href = `ticket_checkout.php?event_id=${eventId}`;
            });
        }
    }
});
