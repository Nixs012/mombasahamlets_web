<?php
/**
 * ticket_checkout.php
 * Checkout page for event tickets.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';
$eventId = $_GET['event_id'] ?? 0;
if (!$eventId) {
    header("Location: events.php");
    exit();
}
?>
<?php
require_once __DIR__ . '/../config/asset_helper.php';
$pageTitle = 'Ticket Checkout - Mombasa Hamlets FC';
$pageDescription = 'Complete your ticket purchase for Mombasa Hamlets FC match. Secure payment via M-Pesa.';
$pageImage = 'images/logo1.webp';
include __DIR__ . '/includes/meta_head.php';
?>
<body>
    <header class="header">
        <div class="logo">
            <a href="index.php"><img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo"></a>
        </div>
        <nav class="desktop-nav">
            <ul>
                <li><a href="event-single.php?id=<?php echo (int) $eventId; ?>"><i class="fas fa-arrow-left"></i> Back to Event</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="checkout-header">
            <h1>Ticket Checkout</h1>
            <p>Secure your spot at the event</p>
        </div>

        <div class="checkout-container">
            <div class="order-summary">
                <div id="event-loading">
                    <div class="loading-spinner"></div>
                </div>
                
                <div id="event-info-container" style="display: none;">
                    <div class="event-mini-card">
                        <img src="images/logo1.jpeg" id="event-img" alt="Event" class="event-mini-img">
                        <div class="event-mini-details">
                            <h4 id="event-title">Loading Event...</h4>
                            <p id="event-meta"><i class="far fa-calendar-alt"></i> Loading date...</p>
                        </div>
                    </div>

                    <h3>Select Tickets</h3>
                    <div id="ticket-types-list">
                        <!-- Ticket types will be loaded here -->
                    </div>

                    <div class="order-total">
                        <span>Total Amount</span>
                        <span>KSh <span id="grand-total">0.00</span></span>
                    </div>
                </div>
            </div>

            <div class="checkout-form">
                <h3>Customer Details</h3>
                <form id="ticket-checkout-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number (M-Pesa/Airtel)</label>
                        <input type="tel" id="phone" name="phone" required placeholder="e.g. 0712345678">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="For receiving your digital tickets">
                    </div>

                    <div class="form-group">
                        <label for="shipping_address">Physical Address (Optional)</label>
                        <input type="text" id="shipping_address" name="shipping_address" placeholder="Stree, City (Default: Ticket Only)" value="Digital Ticket">
                    </div>

                    <!-- Delivery Zone Selection -->
                    <div class="delivery-zone-selector">
                        <h4><i class="fas fa-truck"></i> Select Delivery Zone</h4>
                        <p style="font-size: 0.9em; color: var(--text-secondary, #666); margin-bottom: 15px;">
                            Select a delivery zone for your order. Delivery fees will be calculated accordingly.
                        </p>
                        <div class="delivery-zone-options" id="delivery-zone-options">
                            <!-- Zones loaded dynamically via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Hidden fields for delivery zone -->
                    <input type="hidden" id="delivery_zone_id" name="delivery_zone_id" value="">
                    <input type="hidden" id="delivery_zone_fee" name="delivery_zone_fee" value="0">

                    <button type="submit" id="submit-btn" class="btn-pay" disabled>
                        <span>Pay KSh <span id="btn-total">0.00</span></span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                <div style="margin-top: 20px; font-size: 0.85em; color: #777;">
                    <i class="fas fa-lock"></i> Secure checkout powered by Paystack
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
        window.paystackPublicKey = '<?php echo PAYSTACK_PUBLIC_KEY; ?>';
    </script>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script src="js/ticket-checkout.js"></script>
</body>
</html>
