<?php
/**
 * checkout.php
 * Displays the cart summary and customer details form.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';

// Redirect back to shop if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit();
}

$cart = $_SESSION['cart'];
$user = $_SESSION['user'] ?? null;

// Pre-fill fields if user is logged in
$fullName = '';
if ($user) {
    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

$total = 0;
foreach ($cart as $item) {
    $price = $item['sale_price'] ?? $item['price'] ?? 0;
    $total += $price * $item['quantity'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .checkout-container { max-width: 1000px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .checkout-header { text-align: center; margin-bottom: 40px; }
        .order-summary { background: var(--card-bg, #f8f9fa); padding: 25px; border-radius: 12px; height: fit-content; }
        .order-summary h3 { margin-top: 0; padding-bottom: 15px; border-bottom: 1px solid var(--border-color, #dee2e6); }
        .cart-item { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color, #dee2e6); }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 600; display: block; }
        .cart-item-meta { font-size: 0.9em; color: var(--text-secondary, #666); }
        .order-total { font-size: 1.25em; font-weight: 700; display: flex; justify-content: space-between; margin-top: 20px; color: var(--primary-blue, #003366); }
        
        .checkout-form { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-navy, #0A192F); }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid var(--border-color, #ddd); border-radius: 8px; font-size: 16px; font-family: 'Open Sans', sans-serif; }
        .btn-place-order { background: var(--primary-blue, #003366); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 1.1em; font-weight: 700; cursor: pointer; transition: background 0.3s; margin-top: 20px; }
        .btn-place-order:hover { background: var(--accent-gold, #DAA520); }
        .btn-place-order:disabled { background: var(--text-secondary, #999); cursor: not-allowed; }
        
        /* Delivery Zone Selection */
        .delivery-zone-selector { border: 2px solid var(--border-color, #dee2e6); border-radius: 12px; padding: 20px; margin: 20px 0; background: var(--card-bg, #f8f9fa); }
        .delivery-zone-selector h4 { margin-top: 0; color: var(--dark-navy, #0A192F); display: flex; align-items: center; gap: 10px; }
        .delivery-zone-options { display: flex; flex-direction: column; gap: 12px; margin-top: 15px; }
        .zone-option { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; cursor: pointer; transition: all 0.2s; background: white; }
        .zone-option:hover { border-color: var(--primary-blue, #003366); background: var(--off-white, #f8f9fa); }
        .zone-option.selected { border-color: var(--primary-blue, #003366); background: rgba(0, 51, 102, 0.05); }
        .zone-option input[type="radio"] { margin-right: 12px; accent-color: var(--primary-blue, #003366); cursor: pointer; }
        .zone-option .zone-name { font-weight: 600; flex: 1; }
        .zone-option .zone-fee { background: var(--primary-blue, #003366); color: white; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.9em; }
        
        @media (max-width: 768px) { 
            .checkout-container { grid-template-columns: 1fr; }
            .delivery-zone-options { flex-direction: column; }
        }
    </style>
</head>
<body>
    <!-- Simple Header -->
    <header class="header">
        <div class="logo">
            <a href="index.php"><img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo"></a>
        </div>
        <nav class="desktop-nav">
            <ul>
                <li><a href="shop.php"><i class="fas fa-arrow-left"></i> Back to Shop</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="checkout-header">
            <h1>Checkout</h1>
            <p>Complete your order</p>
        </div>

        <div class="checkout-container">
            <!-- Sidebar: Order Summary -->
            <div class="order-summary">
                <h3>Your Order</h3>
                <div id="cart-summary">
                    <?php foreach ($cart as $item): 
                        $price = $item['sale_price'] ?? $item['price'] ?? 0;
                        $size = $item['selectedSize'] ?? 'Standard';
                    ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <span class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="cart-item-meta">Size: <?php echo htmlspecialchars($size); ?> | Qty: <?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="cart-item-price">
                                KSh <?php echo number_format($price * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="order-total">
                    <span>Total</span>
                    <span>KSh <?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <!-- Main: Checkout Form -->
            <div class="checkout-form">
                <h3>Shipping & Payment</h3>
                <form id="shop-checkout-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number (M-Pesa/Airtel)</label>
                        <input type="tel" id="phone" name="phone" required placeholder="e.g. 0712345678">
                    </div>

                    <div class="form-group">
                        <label for="shipping_address">Delivery Address</label>
                        <input type="text" id="shipping_address" name="shipping_address" required placeholder="Street, City, Apartment">
                    </div>

                    <?php if (!$user): ?>
                    <div class="form-group">
                        <label for="email_input">Email Address</label>
                        <input type="email" id="email_input" name="email" required placeholder="Enter your email for order confirmation">
                    </div>
                    <?php endif; ?>

                    <!-- Delivery Zone Selection -->
                    <div id="delivery-zone-section">
                        <div class="delivery-zone-selector">
                            <h4><i class="fas fa-truck"></i> Select Delivery Zone</h4>
                            <div class="delivery-zone-options" id="delivery-zone-options">
                                <!-- Zones loaded dynamically via JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Fallback: legacy shipping location (used if API fails to load zones) -->
                    <div class="form-group" id="shipping-location-fallback" style="display: none;">
                        <label for="shipping_location">Shipping Location</label>
                        <select id="shipping_location" name="shipping_location" required>
                            <option value="" disabled selected>Select location</option>
                            <option value="mombasa">Within Mombasa (KSh 150)</option>
                            <option value="outside">Outside Mombasa (KSh 300)</option>
                            <option value="pickup">Shop Pickup (Free)</option>
                        </select>
                    </div>

                    <input type="hidden" id="selected_zone_id" name="delivery_zone_id" value="">
                    <input type="hidden" id="selected_zone_fee" name="selected_zone_fee" value="0">

                    <!-- Hidden fields for Paystack -->
                    <input type="hidden" name="payment_method" id="payment-method-input" value="Paystack">
                    <input type="hidden" id="user_email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">

                    <!-- Payment Method Selector -->
                    <div class="payment-method-selector" style="margin: 20px 0; padding: 15px; border: 2px solid var(--border-color, #dee2e6); border-radius: 8px; background: var(--card-bg, #f8f9fa);">
                        <h4 style="margin: 0 0 12px; color: var(--dark-navy, #0A192F);"><i class="fas fa-wallet"></i> Select Payment Method</h4>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <label style="flex: 1; min-width: 140px; padding: 12px; border: 1px solid var(--border-color, #ddd); border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.2s;" onclick="selectPaymentMethod('paystack')">
                                <input type="radio" name="payment_method_choice" value="paystack" style="margin-bottom: 8px;" checked>
                                <div style="font-weight: 600; font-size: 0.95em;">Paystack</div>
                                <div style="font-size: 0.8em; color: var(--text-secondary, #666);">Card / Bank</div>
                            </label>
                            <label style="flex: 1; min-width: 140px; padding: 12px; border: 1px solid var(--border-color, #ddd); border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.2s;" onclick="selectPaymentMethod('mpesa')">
                                <input type="radio" name="payment_method_choice" value="mpesa" style="margin-bottom: 8px;">
                                <div style="font-weight: 600; font-size: 0.95em;">M-Pesa STK</div>
                                <div style="font-size: 0.8em; color: var(--text-secondary, #666);">Mobile Money</div>
                            </label>
                        </div>
                    </div>

                    <!-- M-Pesa Phone Field (shown only when M-Pesa selected) -->
                    <div id="mpesa-phone-field" style="display: none; margin-bottom: 20px;">
                        <label for="mpesa_phone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-navy, #0A192F);">
                            <i class="fab fa-mpesa"></i> M-Pesa Phone Number
                        </label>
                        <input type="tel" id="mpesa_phone" name="mpesa_phone" placeholder="e.g. 0712345678" style="width: 100%; padding: 12px; border: 1px solid var(--border-color, #ddd); border-radius: 8px; font-size: 16px;">
                    </div>

                    <button type="button" id="paystack-btn" class="btn-place-order" disabled>
                        <span>Pay KSh <span id="btn-total-display"><?php echo number_format($total, 2); ?></span> with Paystack</span>
                    </button>
                    <button type="button" id="mpesa-btn" class="btn-place-order" style="display: none; background: var(--kit-green, #006600);" disabled>
                        <span>Pay KSh <span id="btn-total-display-mpesa"><?php echo number_format($total, 2); ?></span> via M-Pesa</span>
                    </button>
                </form>
                <div style="margin-top: 20px; font-size: 0.85em; color: #777; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-lock"></i> Secured by <img src="https://static.paystack.com/assets/img/logo/logo.png" alt="Paystack" style="height: 15px;">
                </div>
            </div>
        </div>
    </main>

    <div style="margin-top: 60px;">
        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- Paystack Inline JS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <?php require_once '../backend/config/paystack_config.php'; ?>
    
    <script>
        const baseTotal = <?php echo $total; ?>;
        const shippingRates = {
            'mombasa': <?php echo SHIPPING_MOMBASA; ?>,
            'outside': <?php echo SHIPPING_OUTSIDE; ?>,
            'pickup': 0
        };
        const paystackPublicKey = '<?php echo PAYSTACK_PUBLIC_KEY; ?>';
        const apiUrl = '../backend/api';

        const shippingSelect = document.getElementById('shipping_location');
        const totalDisplay = document.getElementById('btn-total-display');
        const totalDisplayMpesa = document.getElementById('btn-total-display-mpesa');
        const orderSummaryTotal = document.querySelector('.order-total span:last-child');
        const cartSummary = document.getElementById('cart-summary');
        const paystackBtn = document.getElementById('paystack-btn');
        const mpesaBtn = document.getElementById('mpesa-btn');
        const mpesaPhoneField = document.getElementById('mpesa-phone-field');
        const zoneOptionsContainer = document.getElementById('delivery-zone-options');
        const selectedZoneIdInput = document.getElementById('selected_zone_id');
        const selectedZoneFeeInput = document.getElementById('selected_zone_fee');
        const paymentMethodInput = document.getElementById('payment-method-input');

        let deliveryZones = [];
        let selectedPaymentMethod = 'paystack';
        let selectedShippingFee = 0;

        // Select payment method
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            paymentMethodInput.value = method === 'paystack' ? 'Paystack' : 'mpesa_stk';
            
            // Update UI
            document.querySelectorAll('input[name="payment_method_choice"]').forEach(radio => {
                radio.checked = (radio.value === method);
            });
            
            // Show/hide phone field
            mpesaPhoneField.style.display = method === 'mpesa' ? 'block' : 'none';
            
            // Show/hide buttons
            paystackBtn.style.display = method === 'paystack' ? 'block' : 'none';
            mpesaBtn.style.display = method === 'mpesa' ? 'block' : 'none';
            
            // Update button label total displays
            updateAllTotals(selectedShippingFee);
        }

        // Update all total displays

        let deliveryZones = [];

        // Add Shipping Row to Summary if not exists
        let shippingRow = document.createElement('div');
        shippingRow.className = 'cart-item';
        shippingRow.innerHTML = `
            <div class="cart-item-info">
                <span class="cart-item-name">Delivery Fee</span>
            </div>
            <div class="cart-item-price" id="shipping-fee-display">KSh 0.00</div>
        `;
        cartSummary.appendChild(shippingRow);

        // Fetch delivery zones from API
        async function loadDeliveryZones() {
            try {
                const response = await fetch(`${apiUrl}/delivery_zones.php`);
                if (!response.ok) throw new Error('Failed to fetch zones');
                const zones = await response.json();
                
                if (Array.isArray(zones) && zones.length > 0) {
                    deliveryZones = zones;
                    renderZoneOptions(zones);
                } else {
                    // Fall back to legacy select
                    fallbackToLegacySelect();
                }
            } catch (error) {
                console.error('Error loading delivery zones:', error);
                fallbackToLegacySelect();
            }
        }

        function renderZoneOptions(zones) {
            zoneOptionsContainer.innerHTML = zones.map(zone => `
                <label class="zone-option" data-zone-id="${zone.id}">
                    <div style="display: flex; align-items: center; flex: 1;">
                        <input type="radio" name="delivery_zone" value="${zone.id}" required>
                        <span class="zone-name">${escapeHtml(zone.name)}</span>
                    </div>
                    <span class="zone-fee">KSh ${parseFloat(zone.fee).toFixed(2)}</span>
                </label>
            `).join('');

            // Add event listeners to zone options
            zoneOptionsContainer.querySelectorAll('input[name="delivery_zone"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    // Update selected styles
                    zoneOptionsContainer.querySelectorAll('.zone-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.closest('.zone-option').classList.add('selected');
                    
                    // Update hidden fields and total
                    const zoneId = this.value;
                    const zone = deliveryZones.find(z => z.id == zoneId);
                    const fee = zone ? parseFloat(zone.fee) : 0;
                    
                    selectedZoneIdInput.value = zoneId;
                    selectedZoneFeeInput.value = fee;
                    updateTotal(fee);
                });
            });

            // Auto-select first zone and enable payment buttons
            const firstRadio = zoneOptionsContainer.querySelector('input[name="delivery_zone"]');
            if (firstRadio) {
                firstRadio.checked = true;
                firstRadio.closest('.zone-option').classList.add('selected');
                const zone = deliveryZones.find(z => z.id == firstRadio.value);
                const fee = zone ? parseFloat(zone.fee) : 0;
                selectedZoneIdInput.value = firstRadio.value;
                selectedZoneFeeInput.value = fee;
                updateTotal(fee);
            }

            // Enable payment buttons
            paystackBtn.disabled = false;
            mpesaBtn.disabled = false;
        }

        function fallbackToLegacySelect() {
            // Hide the zone selector and show legacy select
            document.getElementById('delivery-zone-section').style.display = 'none';
            shippingSelect.parentElement.style.display = 'block';
            shippingSelect.style.display = 'block';

            shippingSelect.addEventListener('change', function() {
                const rate = shippingRates[this.value] || 0;
                updateTotal(rate);
            });
            
            // Enable payment buttons in fallback
            paystackBtn.disabled = false;
            mpesaBtn.disabled = false;
        }

        function updateAllTotals(fee) {
            selectedShippingFee = fee;
            const newTotal = baseTotal + fee;
            const formatted = newTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
            totalDisplay.textContent = formatted;
            totalDisplayMpesa.textContent = formatted;
            orderSummaryTotal.textContent = 'KSh ' + formatted;
            document.getElementById('shipping-fee-display').textContent = 'KSh ' + fee.toLocaleString(undefined, { minimumFractionDigits: 2 });
        }

        function updateTotal(fee) {
            updateAllTotals(fee);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load delivery zones on page load
        loadDeliveryZones();

        document.getElementById('paystack-btn').addEventListener('click', function(e) {
            const form = document.getElementById('shop-checkout-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Check if a delivery zone was selected
            let selectedZoneId = selectedZoneIdInput.value;
            let shippingCost = parseFloat(selectedZoneFeeInput.value);

            // If zones didn't load, fall back to legacy select
            if (!selectedZoneId && shippingSelect && shippingSelect.style.display !== 'none') {
                const location = shippingSelect.value;
                shippingCost = shippingRates[location] || 0;
                // Map legacy location to zone name for server validation
                selectedZoneId = ''; // Will be resolved server-side
            }

            const fullName = document.getElementById('full_name').value;
            const email = document.getElementById('email_input') ? document.getElementById('email_input').value : document.getElementById('user_email').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('shipping_address').value;
            const amount = (baseTotal + shippingCost) * 100; // Convert to kobo

            // Enable button and show loading state
            paystackBtn.disabled = true;
            const originalBtnContent = paystackBtn.innerHTML;
            paystackBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            const handler = PaystackPop.setup({
                key: paystackPublicKey,
                email: email,
                amount: amount,
                currency: 'KES',
                ref: 'ORD-' + Math.floor((Math.random() * 1000000000) + 1),
                metadata: {
                    custom_fields: [
                        { display_name: "Full Name", variable_name: "full_name", value: fullName },
                        { display_name: "Phone Number", variable_name: "phone", value: phone },
                        { display_name: "Shipping Address", variable_name: "address", value: address },
                        { display_name: "Shipping Cost", variable_name: "shipping_cost", value: shippingCost },
                        { display_name: "Delivery Zone ID", variable_name: "delivery_zone_id", value: selectedZoneId }
                    ]
                },
                callback: function(response) {
                    // Transaction successful
                    const reference = response.reference;
                    // Submit order to backend for verification and storage
                    submitOrder(reference, fullName, phone, email, address, shippingCost, selectedZoneId);
                },
                onClose: function() {
                    alert('Transaction cancelled.');
                    paystackBtn.disabled = false;
                    paystackBtn.innerHTML = originalBtnContent;
                }
            });
            paystackBtn.disabled = false;
            paystackBtn.innerHTML = originalBtnContent;
            handler.openIframe();
        });

        // M-Pesa STK Push button handler
        document.getElementById('mpesa-btn').addEventListener('click', async function(e) {
            const form = document.getElementById('shop-checkout-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            let selectedZoneId = selectedZoneIdInput.value;
            let shippingCost = parseFloat(selectedZoneFeeInput.value);

            // If zones didn't load, fall back to legacy select
            if (!selectedZoneId && shippingSelect && shippingSelect.style.display !== 'none') {
                const location = shippingSelect.value;
                shippingCost = shippingRates[location] || 0;
            }

            const phoneInput = document.getElementById('mpesa_phone');
            if (!phoneInput || !phoneInput.value) {
                alert('Please enter your M-Pesa phone number.');
                phoneInput?.focus();
                return;
            }

            const fullName = document.getElementById('full_name').value;
            const email = document.getElementById('email_input') ? document.getElementById('email_input').value : document.getElementById('user_email').value;
            const address = document.getElementById('shipping_address').value;
            const totalAmount = baseTotal + shippingCost;

            // Initiate payment via order creation API
            mpesaBtn.disabled = true;
            const originalBtnContent = mpesaBtn.innerHTML;
            mpesaBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initiating STK Push...';

            try {
                const response = await fetch('../backend/api/initiate_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: totalAmount,
                        phone: phoneInput.value,
                        payment_method: 'mpesa_stk',
                        full_name: fullName,
                        email: email,
                        shipping_address: address,
                        shipping_cost: shippingCost,
                        delivery_zone_id: selectedZoneId ? parseInt(selectedZoneId) : null
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    alert('STK Push sent! Please check your phone to complete payment.\nOrder #' + result.order_id);
                    // Poll for payment status
                    pollPaymentStatus(result.order_id);
                } else {
                    alert('Payment initiation failed: ' + result.message);
                    mpesaBtn.disabled = false;
                    mpesaBtn.innerHTML = originalBtnContent;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Network error. Please try again.');
                mpesaBtn.disabled = false;
                mpesaBtn.innerHTML = originalBtnContent;
            }
        });

        // Poll for payment status
        function pollPaymentStatus(orderId) {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`../backend/api/payment_status.php?order_id=${orderId}`);
                    const data = await response.json();
                    
                    if (data.payment_status === 'paid' || data.payment_status === 'success') {
                        clearInterval(interval);
                        window.location.href = 'order-success.php?status=success&order_id=' + orderId;
                    } else if (data.payment_status === 'failed' || data.payment_status === 'cancelled') {
                        clearInterval(interval);
                        window.location.href = 'payment-failed.php?order_id=' + orderId;
                    }
                    // Continue polling if pending/processing
                } catch (error) {
                    clearInterval(interval);
                    console.error('Polling error:', error);
                }
            }, 5000); // Poll every 5 seconds

            // Timeout after 5 minutes
            setTimeout(() => {
                clearInterval(interval);
                alert('Payment timeout. Please check your phone or retry.');
            }, 300000);
        }

        async function submitOrder(reference, fullName, phone, email, address, shippingCost, selectedZoneId) {
            const btn = document.getElementById('paystack-btn');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizing Order...';

            try {
                const response = await fetch('../backend/api/verify_paystack.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reference: reference,
                        full_name: fullName,
                        phone: phone,
                        email: email,
                        shipping_address: address,
                        shipping_cost: shippingCost,
                        delivery_zone_id: selectedZoneId ? parseInt(selectedZoneId) : null
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    window.location.href = result.redirect_url;
                } else {
                    alert('Verification failed: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during verification.');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }
    </script>
</html>
