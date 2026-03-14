/**
 * dashboard.js
 * Handles data fetching and rendering for the unified DASHBOARD hub.
 * Includes visual progress bars for orders and tickets.
 */
document.addEventListener('DOMContentLoaded', function () {
    const ordersList = document.getElementById('orders-list');
    const ticketsList = document.getElementById('tickets-list');
    const apiUrl = window.API_URL || '../backend/api';
    const token = localStorage.getItem('userToken');

    // 1. Update Greeting from Token Payload (Immediate UX)
    updateGreetingFromToken();

    // 2. Redundant Cart Clearing: If redirected here after a successful payment
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        localStorage.removeItem('cart');
        console.log('Cart cleared after successful payment redirect.');
    }

    // 3. Sync Session if needed and fetch data
    syncSessionAndLoadData();

    function updateGreetingFromToken() {
        const payload = window.getTokenPayload ? window.getTokenPayload() : null;
        const greeting = document.getElementById('welcome-greeting');
        if (payload && payload.username && greeting) {
            greeting.textContent = `Welcome Back, ${payload.username}!`;
        }
    }

    async function syncSessionAndLoadData() {
        if (!token) return;

        try {
            // Ping the verification endpoint to ensure PHP session is synced
            const resp = await fetch(`${apiUrl}/users.php?token=${encodeURIComponent(token)}`);
            if (!resp.ok) {
                if (resp.status === 401) {
                    localStorage.removeItem('userToken');
                    window.location.href = 'login.php';
                    return;
                }
            }

            // After sync, load the data
            fetchOrders();
            fetchTickets();
        } catch (err) {
            console.error('Session sync failed:', err);
            // Fallback: try loading data anyway, though APIs might fail with 401 if session is hard-missing
            fetchOrders();
            fetchTickets();
        }
    }

    async function fetchOrders() {
        try {
            const resp = await fetch(`${apiUrl}/my_orders.php`);
            if (!resp.ok) throw new Error('Failed to load orders');
            const orders = await resp.json();

            if (orders.length === 0) {
                ordersList.innerHTML = '<tr><td colspan="4" class="text-center">No orders found.</td></tr>';
                return;
            }

            ordersList.innerHTML = orders.map(order => {
                const prog = getOrderProgress(order.order_status);
                return `
                <tr>
                    <td data-label="Order ID">
                        <div style="font-weight: 700;">#${order.order_id}</div>
                        <div class="card-label-small">${new Date(order.order_date).toLocaleDateString()}</div>
                    </td>
                    <td data-label="Progress">
                        <div class="progress-container">
                            <div class="progress-track">
                                <div class="progress-fill status-${order.order_status.toLowerCase()}" style="width: ${prog.pct}%"></div>
                            </div>
                            <div class="progress-labels">
                                <span>${prog.label}</span>
                                <span>${prog.pct}%</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Status">
                        <span class="badge badge-${order.order_status.toLowerCase()}">${order.order_status}</span>
                    </td>
                    <td data-label="Action">
                        <a href="order_details.php?id=${order.order_id}" class="btn btn-sm btn-outline-secondary">Details</a>
                    </td>
                </tr>
            `}).join('');
        } catch (err) {
            ordersList.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${err.message}</td></tr>`;
        }
    }

    async function fetchTickets() {
        try {
            const resp = await fetch(`${apiUrl}/my_tickets.php`);
            if (!resp.ok) throw new Error('Failed to load tickets');
            const tickets = await resp.json();

            if (tickets.length === 0) {
                ticketsList.innerHTML = '<tr><td colspan="3" class="text-center">No tickets found.</td></tr>';
                return;
            }

            ticketsList.innerHTML = tickets.map(t => {
                const isUnused = t.ticket_status.toUpperCase() === 'UNUSED';
                const rowClass = isUnused ? 'row-highlight-unused' : '';
                const prog = getTicketProgress(t.ticket_status);

                return `
                <tr class="${rowClass}">
                    <td data-label="Ticket Info">
                        <div class="ticket-info-bundle">
                            <span class="ticket-event-name">${t.event_title}</span>
                            <span class="ticket-meta-small">${t.ticket_code} • ${t.ticket_type_name}</span>
                        </div>
                        <div class="progress-container" style="max-width: 150px;">
                            <div class="progress-track" style="height: 4px;">
                                <div class="progress-fill ${isUnused ? 'status-unused' : 'status-used'}" style="width: ${prog.pct}%"></div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Status">
                        <span class="badge badge-${t.ticket_status.toLowerCase()}">${t.ticket_status}</span>
                    </td>
                    <td data-label="Action">
                        <a href="ticket_details.php?id=${t.ticket_id}${t.ticket_id == 0 ? `&order_id=${t.order_id}&type_id=${t.ticket_type_id}` : ''}" class="btn btn-sm ${isUnused ? 'btn-primary' : 'btn-outline-secondary'}">
                            View QR
                        </a>
                    </td>
                </tr>
            `}).join('');
        } catch (err) {
            ticketsList.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${err.message}</td></tr>`;
        }
    }

    function getOrderProgress(status) {
        const s = status.toUpperCase();
        if (s === 'PENDING') return { pct: 25, label: 'Order Registered' };
        if (s === 'PROCESSING') return { pct: 50, label: 'Processing Payment' };
        if (s === 'PAID' || s === 'SUCCESS' || s === 'SHIPPED') return { pct: 75, label: 'Payment Confirmed' };
        if (s === 'COMPLETED' || s === 'DELIVERED') return { pct: 100, label: 'Order Fulfilled' };
        return { pct: 0, label: 'Unknown' };
    }

    function getTicketProgress(status) {
        const s = status.toUpperCase();
        if (s === 'PROCESSING') return { pct: 25 };
        if (s === 'UNUSED') return { pct: 60 };
        return { pct: 100 };
    }
});
