document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const modal = document.getElementById('ticketModal');
    const modalEventTitle = document.getElementById('modalEventTitle');
    const modalBody = document.getElementById('modalBody');
    const modalClose = document.getElementById('modalClose');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNav = document.getElementById('mobileNav');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const newsletterForm = document.getElementById('newsletterForm');

    // --- PAGINATION STATE ---
    let currentCategory = 'all';
    let currentPage = 1;
    const limit = 8;
    const eventsGrid = document.querySelector('.events-grid');
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'pagination-container';
    if (eventsGrid) eventsGrid.after(paginationContainer);

    // Fetch events from API
    fetchEvents();

    // --- EVENT LISTENERS ---

    if (mobileMenuToggle && mobileNav) {
        // Mobile menu toggle
        mobileMenuToggle.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
        });
    }

    if (filterButtons.length > 0) {
        // Event filtering functionality
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));

                // Add active class to clicked button
                button.classList.add('active');

                const filter = button.getAttribute('data-filter');
                currentCategory = filter;
                fetchEvents(1, filter);
            });
        });
    }

    if (modalClose) {
        // Close modal when clicking on X
        modalClose.addEventListener('click', closeModal);
    }

    if (modal) {
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    if (newsletterForm) {
        // Newsletter form submission
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = newsletterForm.querySelector('input[type="email"]').value;
            alert(`Thank you for subscribing with ${email}! You'll now receive our latest updates.`);
            newsletterForm.reset();
        });
    }


    // --- FUNCTIONS ---

    async function fetchEvents(page = 1, category = 'all') {
        const eventsContainer = document.querySelector('.events-grid');
        if (!eventsContainer) return;

        currentPage = page;
        currentCategory = category;
        eventsContainer.innerHTML = '<div class="loading-spinner">Loading events...</div>';
        paginationContainer.innerHTML = '';

        try {
            const response = await fetch(`${window.API_URL || '/backend/api'}/events.php?page=${page}&limit=${limit}&category=${category}`);
            if (!response.ok) throw new Error('Failed to fetch events');
            const result = await response.json();

            if (result.data) {
                displayEvents(result.data);
                renderPagination(result.pagination);
            } else {
                // Fallback for non-paginated response if any
                displayEvents(result);
            }

            if (page > 1 || category !== 'all') {
                eventsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            console.error('Error fetching events:', error);
            eventsContainer.innerHTML = '<p>Could not load events at this time.</p>';
        }
    }

    function renderPagination(pagination) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        if (!pagination || pagination.pages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = `page-btn ${pagination.page === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.onclick = () => {
            if (pagination.page > 1) fetchEvents(pagination.page - 1, currentCategory);
        };
        paginationContainer.appendChild(prevBtn);

        // Page numbers
        for (let i = 1; i <= pagination.pages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${pagination.page === i ? 'active' : ''}`;
            pageBtn.innerText = i;
            pageBtn.onclick = () => fetchEvents(i, currentCategory);
            paginationContainer.appendChild(pageBtn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = `page-btn ${pagination.page === pagination.pages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.onclick = () => {
            if (pagination.page < pagination.pages) fetchEvents(pagination.page + 1, currentCategory);
        };
        paginationContainer.appendChild(nextBtn);
    }

    function displayEvents(events) {
        const eventsContainer = document.querySelector('.events-grid');
        if (!events || events.length === 0) {
            eventsContainer.innerHTML = '<div class="no-events"><p>No upcoming events found in this category.</p></div>';
            return;
        }

        eventsContainer.innerHTML = events.map(createEventCard).join('');
        setupActionButtons();
    }

    function createEventCard(event) {
        const eventDate = new Date(event.event_date + 'T' + (event.event_time || '00:00:00'));
        const month = eventDate.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        const day = eventDate.getDate();

        // Determine button text and type based on category
        let buttonText = 'Learn More';
        let buttonClass = 'learn-more-btn';
        let ticketType = 'rsvp'; // Default

        // If ticket_count > 0, always show "Get Tickets" button
        if (event.ticket_count > 0) {
            buttonText = 'Get Tickets';
            buttonClass = 'get-tickets-btn';
            ticketType = 'match'; // Redirects to ticket_checkout.php
        } else {
            // Fallback to category-based buttons if no tickets are explicitly added
            switch (event.category.toLowerCase()) {
                case 'match':
                    buttonText = 'Get Tickets';
                    buttonClass = 'get-tickets-btn';
                    ticketType = 'match';
                    break;
                case 'community':
                    buttonText = 'Register';
                    buttonClass = 'register-btn';
                    ticketType = 'clinic';
                    break;
                case 'special':
                    buttonText = 'Buy Tickets';
                    buttonClass = 'buy-tickets-btn';
                    ticketType = 'special';
                    break;
                case 'tour':
                    buttonText = 'Book Tour';
                    buttonClass = 'book-tour-btn';
                    ticketType = 'tour';
                    break;
            }
        }

        function normalizeImagePath(p, placeholder = 'images/logo1.jpeg') {
            if (!p) return placeholder;
            if (/^https?:\/\//i.test(p)) return p;
            if (p.startsWith('/')) {
                // Remove leading slash to make it relative to the current page/folder (frontend/)
                // This is safer for subfolder-hosted sites
                p = p.substring(1);
            }
            if (p.length === 0) return placeholder;

            // If it already starts with images/, use it as is
            if (p.startsWith('images/')) return p;

            // Otherwise prepend images/ (redundant if it's already images/uploads/ but safe)
            // Actually, if it has a slash but doesn't start with images/, it might be already images/uploads/
            // Let's just return p if it contains a slash, otherwise prepend images/
            return p.indexOf('/') !== -1 ? p : 'images/' + p;
        }

        return `
            <a href="event-single.php?id=${event.id}" class="event-card-link">
                <div class="event-card" data-category="${event.category.toLowerCase()}">
                    <div class="event-image">
                        <img src="${normalizeImagePath(event.image_url || event.image)}" alt="${event.title}" onerror="this.src='images/logo1.jpeg'">
                    </div>
                    <div class="event-info">
                        <div class="event-date">
                            <span class="month">${month}</span>
                            <span class="day">${day}</span>
                        </div>
                        <h3 class="event-title">${event.title}</h3>
                        <p class="event-location"><i class="fas fa-map-marker-alt"></i> ${event.location}</p>
                        <button class="btn ${buttonClass}" data-event-id="${event.id}" data-event="${event.title}" data-ticket-type="${ticketType}">${buttonText}</button>
                    </div>
                </div>
            </a>
        `;
    }

    function setupActionButtons() {
        // Get all action buttons after they are rendered
        const getTicketsBtns = document.querySelectorAll('.get-tickets-btn');
        const buyTicketsBtns = document.querySelectorAll('.buy-tickets-btn');
        const registerBtns = document.querySelectorAll('.register-btn');
        const rsvpBtns = document.querySelectorAll('.rsvp-btn');
        const bookTourBtns = document.querySelectorAll('.book-tour-btn');

        // Add event listeners to all action buttons
        getTicketsBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.location.href = `ticket_checkout.php?event_id=${btn.dataset.eventId}`;
            });
        });
        buyTicketsBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.location.href = `ticket_checkout.php?event_id=${btn.dataset.eventId}`;
            });
        });
        registerBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openRegistrationModal(btn.dataset.event, 'clinic');
            });
        });
        rsvpBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openRegistrationModal(btn.dataset.event, 'rsvp');
            });
        });
        bookTourBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openTourModal(btn.dataset.event);
            });
        });
    }

    // Function to open ticket modal
    function openTicketModal(eventName, type) {
        if (!modalEventTitle) return;
        modalEventTitle.textContent = `Tickets for ${eventName}`;

        if (type === 'match') {
            modalBody.innerHTML = `
                    <div class="ticket-types">
                        <div class="ticket-type">
                            <div>
                                <strong>General Admission</strong>
                                <div>KES 500</div>
                            </div>
                            <div class="ticket-quantity">
                                <button class="quantity-btn" onclick="updateQuantity('ga', -1)">-</button>
                                <input type="number" class="quantity-input" id="gaQty" value="0" min="0" max="10">
                                <button class="quantity-btn" onclick="updateQuantity('ga', 1)">+</button>
                            </div>
                        </div>
                        <div class="ticket-type">
                            <div>
                                <strong>VIP Stand</strong>
                                <div>KES 1,500</div>
                            </div>
                            <div class="ticket-quantity">
                                <button class="quantity-btn" onclick="updateQuantity('vip', -1)">-</button>
                                <input type="number" class="quantity-input" id="vipQty" value="0" min="0" max="10">
                                <button class="quantity-btn" onclick="updateQuantity('vip', 1)">+</button>                            </div>
                        </div>
                        <div class="ticket-type">
                            <div>
                                <strong>Family Package (4 tickets)</strong>
                                <div>KES 1,600</div>
                            </div>
                            <div class="ticket-quantity">
                                <button class="quantity-btn" onclick="updateQuantity('family', -1)">-</button>
                                <input type="number" class="quantity-input" id="familyQty" value="0" min="0" max="5">
                                <button class="quantity-btn" onclick="updateQuantity('family', 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ticketName">Full Name</label>
                        <input type="text" id="ticketName" required>
                    </div>
                    <div class="form-group">
                        <label for="ticketEmail">Email Address</label>
                        <input type="email" id="ticketEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="ticketPhone">Phone Number</label>
                        <input type="tel" id="ticketPhone" required>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processTicketPurchase()">Purchase Tickets</button>
                    </div>
                `;
        } else if (type === 'special') {
            modalBody.innerHTML = `
                    <div class="ticket-types">
                        <div class="ticket-type">
                            <div>
                                <strong>Standard Ticket</strong>
                                <div>KES 3,000</div>
                            </div>
                            <div class="ticket-quantity">
                                <button class="quantity-btn" onclick="updateQuantity('standard', -1)">-</button>
                                <input type="number" class="quantity-input" id="standardQty" value="0" min="0" max="10">
                                <button class="quantity-btn" onclick="updateQuantity('standard', 1)">+</button>
                            </div>
                        </div>
                        <div class="ticket-type">
                            <div>
                                <strong>VIP Table (10 seats)</strong>
                                <div>KES 40,000</div>
                            </div>
                            <div class="ticket-quantity">
                                <button class="quantity-btn" onclick="updateQuantity('vipTable', -1)">-</button>
                                <input type="number" class="quantity-input" id="vipTableQty" value="0" min="0" max="2">
                                <button class="quantity-btn" onclick="updateQuantity('vipTable', 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="galaName">Full Name</label>
                        <input type="text" id="galaName" required>
                    </div>
                    <div class="form-group">
                        <label for="galaEmail">Email Address</label>
                        <input type="email" id="galaEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="galaPhone">Phone Number</label>
                        <input type="tel" id="galaPhone" required>
                    </div>
                    <div class="form-group">
                        <label for="galaGuests">Number of Guests (if applicable)</label>
                        <input type="number" id="galaGuests" min="0" max="10">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processGalaPurchase()">Purchase Tickets</button>
                    </div>
                `;
        }

        modal.style.display = 'flex';
    }

    // Function to open registration modal
    function openRegistrationModal(eventName, type) {
        if (!modalEventTitle) {
            // If modal doesn't exist, redirect to event page for now or show alert
            alert('Please select this event to see more details and register.');
            return;
        }
        modalEventTitle.textContent = `Register for ${eventName}`;

        if (type === 'clinic') {
            modalBody.innerHTML = `
                    <div class="form-group">
                        <label for="participantName">Participant's Full Name</label>
                        <input type="text" id="participantName" required>
                    </div>
                    <div class="form-group">
                        <label for="participantAge">Age</label>
                        <input type="number" id="participantAge" min="8" max="16" required>
                    </div>
                    <div class="form-group">
                        <label for="guardianName">Parent/Guardian Name</label>
                        <input type="text" id="guardianName" required>
                    </div>
                    <div class="form-group">
                        <label for="guardianEmail">Email Address</label>
                        <input type="email" id="guardianEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="guardianPhone">Phone Number</label>
                        <input type="tel" id="guardianPhone" required>
                    </div>
                    <div class="form-group">
                        <label for="experienceLevel">Football Experience Level</label>
                        <select id="experienceLevel" required>
                            <option value="">Select Level</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processClinicRegistration()">Register Now</button>
                    </div>
                `;
        } else if (type === 'rsvp') {
            modalBody.innerHTML = `
                    <div class="form-group">
                        <label for="rsvpName">Full Name</label>
                        <input type="text" id="rsvpName" required>
                    </div>
                    <div class="form-group">
                        <label for="rsvpEmail">Email Address</label>
                        <input type="email" id="rsvpEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="rsvpPhone">Phone Number</label>
                        <input type="tel" id="rsvpPhone" required>
                    </div>
                    <div class="form-group">
                        <label for="rsvpGuests">Number of Additional Guests</label>
                        <input type="number" id="rsvpGuests" min="0" max="2" value="0">
                    </div>
                    <div class="form-group">
                        <label for="rsvpDietary">Dietary Restrictions (if any)</label>
                        <input type="text" id="rsvpDietary" placeholder="e.g., Vegetarian, Allergies">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processRSVP()">Confirm RSVP</button>
                    </div>
                `;
        }

        modal.style.display = 'flex';
    }

    // Function to open tour booking modal
    function openTourModal(eventName) {
        if (!modalEventTitle) {
            alert('Please select this event to see more details and book a tour.');
            return;
        }
        modalEventTitle.textContent = `Book ${eventName}`;

        modalBody.innerHTML = `
                <div class="form-group">
                    <label for="tourDate">Preferred Date</label>
                    <input type="date" id="tourDate" required>
                </div>
                <div class="form-group">
                    <label for="tourTime">Preferred Time</label>
                    <select id="tourTime" required>
                        <option value="">Select Time</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="14:00">2:00 PM</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tourParticipants">Number of Participants</label>
                    <input type="number" id="tourParticipants" min="1" max="15" value="1" required>
                </div>
                <div class="form-group">
                    <label for="tourName">Contact Person Name</label>
                    <input type="text" id="tourName" required>
                </div>
                <div class="form-group">
                    <label for="tourEmail">Email Address</label>
                    <input type="email" id="tourEmail" required>
                </div>
                <div class="form-group">
                    <label for="tourPhone">Phone Number</label>
                    <input type="tel" id="tourPhone" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processTourBooking()">Book Tour</button>
                </div>
            `;

        // Set minimum date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('tourDate').min = tomorrow.toISOString().split('T')[0];

        modal.style.display = 'flex';
    }

    // Function to close modal
    function closeModal() {
        modal.style.display = 'none';
    }

    // Function to update quantity
    function updateQuantity(type, change) {
        const input = document.getElementById(`${type}Qty`);
        let value = parseInt(input.value) + change;

        if (value < 0) value = 0;
        if (value > parseInt(input.max)) value = parseInt(input.max);

        input.value = value;
    }

    // Function to process ticket purchase
    function processTicketPurchase() {
        const gaQty = parseInt(document.getElementById('gaQty').value);
        const vipQty = parseInt(document.getElementById('vipQty').value);
        const familyQty = parseInt(document.getElementById('familyQty').value);
        const name = document.getElementById('ticketName').value;
        const email = document.getElementById('ticketEmail').value;
        const phone = document.getElementById('ticketPhone').value;

        if (gaQty + vipQty + familyQty === 0) {
            alert('Please select at least one ticket.');
            return;
        }

        if (!name || !email || !phone) {
            alert('Please fill in all required fields.');
            return;
        }

        // Calculate total
        const total = (gaQty * 500) + (vipQty * 1500) + (familyQty * 1600);

        // In a real application, this would process payment
        alert(`Thank you, ${name}! Your ticket purchase for KES ${total} has been processed. A confirmation has been sent to ${email}.`);
        closeModal();
    }

    // Function to process gala purchase
    function processGalaPurchase() {
        const standardQty = parseInt(document.getElementById('standardQty').value);
        const vipTableQty = parseInt(document.getElementById('vipTableQty').value);
        const name = document.getElementById('galaName').value;
        const email = document.getElementById('galaEmail').value;
        const phone = document.getElementById('galaPhone').value;

        if (standardQty + vipTableQty === 0) {
            alert('Please select at least one ticket.');
            return;
        }

        if (!name || !email || !phone) {
            alert('Please fill in all required fields.');
            return;
        }

        // Calculate total
        const total = (standardQty * 3000) + (vipTableQty * 40000);

        // In a real application, this would process payment
        alert(`Thank you, ${name}! Your gala ticket purchase for KES ${total} has been processed. A confirmation has been sent to ${email}.`);
        closeModal();
    }

    // Function to process clinic registration
    function processClinicRegistration() {
        const participantName = document.getElementById('participantName').value;
        const age = document.getElementById('participantAge').value;
        const guardianName = document.getElementById('guardianName').value;
        const email = document.getElementById('guardianEmail').value;
        const phone = document.getElementById('guardianPhone').value;
        const experienceLevel = document.getElementById('experienceLevel').value;

        if (!participantName || !age || !guardianName || !email || !phone || !experienceLevel) {
            alert('Please fill in all required fields.');
            return;
        }

        // In a real application, this would save the registration
        alert(`Thank you for registering ${participantName} for the Youth Football Clinic! A confirmation has been sent to ${email}.`);
        closeModal();
    }

    // Function to process RSVP
    function processRSVP() {
        const name = document.getElementById('rsvpName').value;
        const email = document.getElementById('rsvpEmail').value;
        const phone = document.getElementById('rsvpPhone').value;

        if (!name || !email || !phone) {
            alert('Please fill in all required fields.');
            return;
        }

        // In a real application, this would save the RSVP
        alert(`Thank you, ${name}! Your RSVP has been confirmed. A confirmation has been sent to ${email}.`);
        closeModal();
    }

    // Function to process tour booking
    function processTourBooking() {
        const date = document.getElementById('tourDate').value;
        const time = document.getElementById('tourTime').value;
        const participants = document.getElementById('tourParticipants').value;
        const name = document.getElementById('tourName').value;
        const email = document.getElementById('tourEmail').value;
        const phone = document.getElementById('tourPhone').value;

        if (!date || !time || !participants || !name || !email || !phone) {
            alert('Please fill in all required fields.');
            return;
        }

        // In a real application, this would save the booking
        alert(`Thank you, ${name}! Your stadium tour has been booked for ${date} at ${time}. A confirmation has been sent to ${email}.`);
        closeModal();
    }

    // Initialize page

    // Add animation to event cards
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = 1;
                entry.target.style.transform = 'translateY(0)';
            }
        });

        // Poll updates endpoint every 15s and refresh events list if new items exist
        (function startEventsPolling() {
            const pollInterval = 2000;
            let lastKnownUpdate = 0;

            async function poll() {
                try {
                    const resp = await fetch((window.API_URL || '/backend/api') + '/updates.php');
                    if (!resp.ok) return;
                    const data = await resp.json();
                    const serverLastUpdated = data.last_updated ? Number(data.last_updated) : 0;

                    if (serverLastUpdated > lastKnownUpdate) {
                        window.location.reload();
                    }
                } catch (e) { /* ignore polling errors */ }
            }
            setInterval(poll, pollInterval);
        })();
    }, observerOptions);

    // Apply animation to event cards
    document.querySelectorAll('.event-card').forEach(card => {
        card.style.opacity = 0;
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });

    // Initialize local max id for events to prevent immediate reloads
    try {
        const ids = Array.from(document.querySelectorAll('.event-card')).map(c => Number(c.getAttribute('data-id') || 0));
        window.__eventsMaxId = ids.length ? Math.max(...ids) : 0;
    } catch (e) {
        window.__eventsMaxId = 0;
    }
});