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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .checkout-container { max-width: 1000px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .checkout-header { text-align: center; margin-bottom: 40px; }
        .order-summary { background: #f9f9f9; padding: 25px; border-radius: 12px; height: fit-content; }
        .order-summary h3 { margin-top: 0; padding-bottom: 15px; border-bottom: 1px solid #ddd; }
        .cart-item { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 600; display: block; }
        .cart-item-meta { font-size: 0.9em; color: #666; }
        .order-total { font-size: 1.25em; font-weight: 700; display: flex; justify-content: space-between; margin-top: 20px; color: #DA291C; }
        
        .checkout-form { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        .btn-place-order { background: #DA291C; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 1.1em; font-weight: 700; cursor: pointer; transition: background 0.3s; margin-top: 20px; }
        .btn-place-order:hover { background: #BB0A21; }
        
        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
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

                    <div class="form-group">
                        <label for="shipping_location">Shipping Location</label>
                        <select id="shipping_location" name="shipping_location" required>
                            <option value="" disabled selected>Select location</option>
                            <option value="mombasa">Within Mombasa (KSh 150)</option>
                            <option value="outside">Outside Mombasa (KSh 300)</option>
                        </select>
                    </div>

                    <!-- Hidden fields for Paystack -->
                    <input type="hidden" name="payment_method" value="Paystack">
                    <input type="hidden" id="user_email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">

                    <button type="button" id="paystack-btn" class="btn-place-order">Pay KSh <span id="btn-total-display"><?php echo number_format($total, 2); ?></span> with Card / M-Pesa (Paystack)</button>
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
            'outside': <?php echo SHIPPING_OUTSIDE; ?>
        };
        const paystackPublicKey = '<?php echo PAYSTACK_PUBLIC_KEY; ?>';

        const shippingSelect = document.getElementById('shipping_location');
        const totalDisplay = document.getElementById('btn-total-display');
        const orderSummaryTotal = document.querySelector('.order-total span:last-child');
        const cartSummary = document.getElementById('cart-summary');

        // Add Shipping Row to Summary if not exists
        let shippingRow = document.createElement('div');
        shippingRow.className = 'cart-item';
        shippingRow.innerHTML = `
            <div class="cart-item-info">
                <span class="cart-item-name">Shipping fee</span>
            </div>
            <div class="cart-item-price" id="shipping-fee-display">KSh 0.00</div>
        `;
        cartSummary.appendChild(shippingRow);

        shippingSelect.addEventListener('change', function() {
            const rate = shippingRates[this.value] || 0;
            const newTotal = baseTotal + rate;
            
            totalDisplay.textContent = newTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
            orderSummaryTotal.textContent = 'KSh ' + newTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
            document.getElementById('shipping-fee-display').textContent = 'KSh ' + rate.toLocaleString(undefined, { minimumFractionDigits: 2 });
        });

        document.getElementById('paystack-btn').addEventListener('click', function(e) {
            const form = document.getElementById('shipping_location').closest('form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const fullName = document.getElementById('full_name').value;
            const email = document.getElementById('email_input') ? document.getElementById('email_input').value : document.getElementById('user_email').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('shipping_address').value;
            const location = shippingSelect.value;
            const shippingCost = shippingRates[location];
            const amount = (baseTotal + shippingCost) * 100; // Convert to kobo

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
                        { display_name: "Shipping Cost", variable_name: "shipping_cost", value: shippingCost }
                    ]
                },
                callback: function(response) {
                    // Transaction successful
                    const reference = response.reference;
                    // Submit order to backend for verification and storage
                    submitOrder(reference, fullName, phone, address, shippingCost, location);
                },
                onClose: function() {
                    alert('Transaction cancelled.');
                }
            });
            handler.openIframe();
        });

        async function submitOrder(reference, fullName, phone, address, shippingCost, location) {
            const btn = document.getElementById('paystack-btn');
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
                        shipping_address: address,
                        shipping_cost: shippingCost,
                        shipping_location: location
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    window.location.href = result.redirect_url;
                } else {
                    alert('Verification failed: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = 'Pay KSh ' + totalDisplay.textContent + ' with Paystack';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during verification.');
                btn.disabled = false;
                btn.innerHTML = 'Pay KSh ' + totalDisplay.textContent + ' with Paystack';
            }
        }
    </script>
</html>
