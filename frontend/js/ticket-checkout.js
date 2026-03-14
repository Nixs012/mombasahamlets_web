document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const eventId = params.get('event_id');
    const apiUrl = window.API_URL || '/mombasahamlets_web/backend/api';

    if (!eventId) {
        window.location.href = 'events.php';
        return;
    }

    const eventTitle = document.getElementById('event-title');
    const eventMeta = document.getElementById('event-meta');
    const eventImg = document.getElementById('event-img');
    const eventLoading = document.getElementById('event-loading');
    const eventInfoContainer = document.getElementById('event-info-container');
    const ticketTypesList = document.getElementById('ticket-types-list');
    const grandTotalElement = document.getElementById('grand-total');
    const btnTotalElement = document.getElementById('btn-total');
    const submitBtn = document.getElementById('submit-btn');
    const checkoutForm = document.getElementById('ticket-checkout-form');

    let ticketTypesData = [];
    let selectedQuantities = {};

    // 1. Fetch Event Details
    fetch(`${apiUrl}/events.php?id=${eventId}`)
        .then(res => res.json())
        .then(event => {
            eventTitle.textContent = event.title;
            const eventDate = new Date(event.event_date).toLocaleDateString();
            eventMeta.innerHTML = `<i class="far fa-calendar-alt"></i> ${eventDate} | <i class="fas fa-map-marker-alt"></i> ${event.location}`;

            if (event.image_url) {
                // Simplified normalization for checkout
                let img = event.image_url.trim();
                if (img.startsWith('/')) img = img.substring(1);
                if (img.indexOf('/') === -1) img = 'images/' + img;
                eventImg.src = img;
            }

            // 2. Fetch Ticket Types
            return fetch(`${apiUrl}/ticket_types.php?event_id=${eventId}`);
        })
        .then(res => res.json())
        .then(types => {
            ticketTypesData = types;
            renderTicketTypes(types);
            eventLoading.style.display = 'none';
            eventInfoContainer.style.display = 'block';
        })
        .catch(err => {
            console.error('Error loading checkout data:', err);
            alert('Could not load event details. Please try again.');
        });

    function renderTicketTypes(types) {
        if (types.length === 0) {
            ticketTypesList.innerHTML = '<p>No tickets available for this event yet.</p>';
            return;
        }

        ticketTypesList.innerHTML = types.map(type => `
            <div class="ticket-item" data-id="${type.id}">
                <div class="ticket-info">
                    <span class="ticket-name">${type.name}</span>
                    <span class="ticket-price">KSh ${parseFloat(type.price).toLocaleString()} per ticket</span>
                </div>
                <div class="quantity-controls">
                    <button type="button" class="qty-btn minus" data-id="${type.id}"><i class="fas fa-minus"></i></button>
                    <input type="number" class="qty-input" value="0" min="0" max="10" data-id="${type.id}" readonly>
                    <button type="button" class="qty-btn plus" data-id="${type.id}"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        `).join('');

        // Add Listeners
        document.querySelectorAll('.qty-btn.plus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, 1));
        });
        document.querySelectorAll('.qty-btn.minus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, -1));
        });
    }

    function updateQty(typeId, delta) {
        const input = document.querySelector(`.qty-input[data-id="${typeId}"]`);
        let val = parseInt(input.value) + delta;
        if (val < 0) val = 0;
        if (val > 10) val = 10;
        input.value = val;

        selectedQuantities[typeId] = val;
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        let totalQty = 0;

        ticketTypesData.forEach(type => {
            const qty = selectedQuantities[type.id] || 0;
            total += qty * parseFloat(type.price);
            totalQty += qty;
        });

        grandTotalElement.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2 });
        btnTotalElement.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2 });
        submitBtn.disabled = (totalQty === 0);
    }

    // 3. Handle Submission
    checkoutForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const cart = [];
        ticketTypesData.forEach(type => {
            const qty = selectedQuantities[type.id] || 0;
            if (qty > 0) {
                cart.push({
                    id: null, // Product ID is null for tickets
                    name: `${eventTitle.textContent} - ${type.name}`,
                    price: parseFloat(type.price),
                    quantity: qty,
                    event_id: parseInt(eventId),
                    ticket_type_id: parseInt(type.id)
                });
            }
        });

        if (cart.length === 0) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            // 1. First Sync Cart
            const syncResponse = await fetch(`${apiUrl}/sync_cart.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart: cart, token: localStorage.getItem('userToken') })
            });

            if (!syncResponse.ok) throw new Error('Sync failed');

            // 2. Open Paystack Inline
            const fullName = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('shipping_address').value || 'Digital Ticket';
            const totalAmount = parseFloat(grandTotalElement.textContent.replace(/,/g, ''));

            const handler = PaystackPop.setup({
                key: window.paystackPublicKey || 'pk_test_placeholder',
                email: email,
                amount: totalAmount * 100, // Kobo
                currency: 'KES',
                ref: 'TIX-' + Math.floor((Math.random() * 1000000000) + 1),
                metadata: {
                    custom_fields: [
                        { display_name: "Full Name", variable_name: "full_name", value: fullName },
                        { display_name: "Phone Number", variable_name: "phone", value: phone },
                        { display_name: "Order Type", variable_name: "order_type", value: "tickets" }
                    ]
                },
                callback: function (response) {
                    // 3. Verify on backend
                    const reference = response.reference;
                    submitTicketOrder(reference, fullName, phone, email, address, totalAmount);
                },
                onClose: function () {
                    alert('Transaction cancelled.');
                    resetSubmitBtn(totalAmount);
                }
            });
            handler.openIframe();

        } catch (error) {
            console.error('Checkout error:', error);
            alert('There was an error processing your checkout. Please try again.');
            resetSubmitBtn(parseFloat(grandTotalElement.textContent.replace(/,/g, '')));
        }
    });

    async function submitTicketOrder(reference, fullName, phone, email, address, totalAmount) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizing...';

        try {
            const verifyResponse = await fetch(`${apiUrl}/verify_paystack.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reference: reference,
                    full_name: fullName,
                    phone: phone,
                    email: email, // Passing email here too
                    shipping_address: address,
                    shipping_cost: 0 // No shipping for tickets
                })
            });

            const result = await verifyResponse.json();
            if (result.status === 'success') {
                window.location.href = result.redirect_url;
            } else {
                alert('Verification failed: ' + result.message);
                resetSubmitBtn(totalAmount);
            }
        } catch (err) {
            console.error('Verification Error:', err);
            alert('Error verifying payment.');
            resetSubmitBtn(totalAmount);
        }
    }

    function resetSubmitBtn(total) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Pay KSh ${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span> <i class="fas fa-arrow-right"></i>`;
    }
});
