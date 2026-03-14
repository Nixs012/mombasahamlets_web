/**
 * order-details.js
 * Fetches and renders details for a specific order.
 */
document.addEventListener('DOMContentLoaded', function () {
    const loadingState = document.getElementById('loading-state');
    const orderContent = document.getElementById('order-content');
    const errorState = document.getElementById('error-state');
    const apiUrl = window.API_URL || '../backend/api';

    // Get Order ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('id');

    if (!orderId) {
        showError();
        return;
    }

    fetchOrderDetails();

    async function fetchOrderDetails() {
        try {
            const response = await fetch(`${apiUrl}/order_details.php?id=${orderId}`);
            if (!response.ok) throw new Error('Not found');
            const data = await response.json();

            renderOrder(data);
        } catch (error) {
            console.error('Error:', error);
            showError();
        }
    }

    function renderOrder(data) {
        const { order, items, payment } = data;

        loadingState.style.display = 'none';
        orderContent.style.display = 'block';

        // Basic Info
        document.getElementById('order-id-label').textContent = `Order #${order.id}`;
        document.getElementById('order-date-label').textContent = `Placed on ${new Date(order.created_at).toLocaleDateString()}`;

        const statusBadge = document.getElementById('order-status-badge');
        statusBadge.textContent = order.status;
        statusBadge.className = `badge badge-${order.status.toLowerCase()}`;

        // Progress Bar Mapping
        updateProgressBar(order.status);

        // Sidebar Summary
        const shipping = parseFloat(order.shipping_cost || 0);
        const total = parseFloat(order.total_amount || 0);
        const subtotal = total - shipping;

        document.getElementById('summary-subtotal').textContent = `KSh ${subtotal.toLocaleString()}`;
        document.getElementById('summary-shipping').textContent = `KSh ${shipping.toLocaleString()}`;
        document.getElementById('summary-total').textContent = `KSh ${total.toLocaleString()}`;
        document.getElementById('display-address').textContent = order.shipping_address || 'No address provided';

        // Payment Info
        if (payment) {
            document.getElementById('payment-method').textContent = payment.payment_method || 'N/A';
            document.getElementById('payment-ref').textContent = payment.reference || 'N/A';
            document.getElementById('payment-status').textContent = payment.payment_status || 'N/A';
        }

        // Items List
        const itemsList = document.getElementById('items-list');
        itemsList.innerHTML = items.map(item => {
            const isTicket = !!item.event_id;
            const title = isTicket ? item.event_title : (item.product_name || 'Generic Product');
            const subtitle = isTicket ? item.ticket_type_name : 'Shop Item';
            const imgPath = normalizeImg(isTicket ? item.image_url : item.product_image);

            return `
                <div class="item-row">
                    <img src="${imgPath}" alt="${title}" class="item-img" onerror="this.src='images/logo1.jpeg'">
                    <div class="item-info">
                        <h4>${title}</h4>
                        <p>${subtitle} x ${item.quantity}</p>
                    </div>
                    <div class="item-price">KSh ${parseFloat(item.subtotal).toLocaleString()}</div>
                </div>
            `;
        }).join('');
    }

    function updateProgressBar(status) {
        const s = status.toUpperCase();
        const steps = {
            'PENDING': 1,
            'PAID': 2,
            'COMPLETED': 3,
            'SHIPPED': 2, // Map shipped as between paid and completed or similar
            'SUCCESS': 2
        };

        const currentStep = steps[s] || 1;

        if (currentStep >= 1) {
            document.getElementById('step-pending').classList.add('completed');
        }
        if (currentStep >= 2) {
            document.getElementById('step-paid').classList.add('completed');
        }
        if (currentStep >= 3) {
            document.getElementById('step-completed').classList.add('completed');
        }

        // Mark current as active if not completed
        if (currentStep === 1) document.getElementById('step-pending').classList.add('active');
        if (currentStep === 2) document.getElementById('step-paid').classList.add('active');
        if (currentStep === 3) document.getElementById('step-completed').classList.add('active');
    }

    function normalizeImg(path) {
        if (!path) return 'images/logo1.jpeg';
        if (path.startsWith('/')) path = path.substring(1);
        if (path.indexOf('/') === -1) path = 'images/' + path;
        return path;
    }

    function showError() {
        loadingState.style.display = 'none';
        errorState.style.display = 'block';
    }
});
