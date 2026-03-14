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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Checkout - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .checkout-container { max-width: 1000px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .checkout-header { text-align: center; margin-bottom: 40px; }
        .order-summary { background: #f9f9f9; padding: 25px; border-radius: 12px; height: fit-content; }
        .order-summary h3 { margin-top: 0; padding-bottom: 15px; border-bottom: 1px solid #ddd; }
        .ticket-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .ticket-info { flex: 1; }
        .ticket-name { font-weight: 600; display: block; font-size: 1.1em; }
        .ticket-price { color: #666; font-size: 0.95em; }
        .quantity-controls { display: flex; align-items: center; gap: 10px; }
        .qty-btn { background: #eee; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .qty-btn:hover { background: #ddd; }
        .qty-input { width: 40px; text-align: center; border: 1px solid #ddd; border-radius: 4px; padding: 4px; font-weight: 600; }
        .order-total { font-size: 1.4em; font-weight: 700; display: flex; justify-content: space-between; margin-top: 30px; color: #DA291C; padding-top: 20px; border-top: 2px solid #ddd; }
        
        .checkout-form { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        .btn-pay { background: #DA291C; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 1.1em; font-weight: 700; cursor: pointer; transition: background 0.3s; margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-pay:hover:not(:disabled) { background: #BB0A21; }
        .btn-pay:disabled { background: #ccc; cursor: not-allowed; }

        .event-mini-card { display: flex; gap: 15px; margin-bottom: 25px; align-items: center; }
        .event-mini-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .event-mini-details h4 { margin: 0; font-size: 1.1em; }
        .event-mini-details p { margin: 5px 0 0; font-size: 0.9em; color: #666; }

        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <a href="index.php"><img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo"></a>
        </div>
        <nav class="desktop-nav">
            <ul>
                <li><a href="event-single.php?id=<?php echo $eventId; ?>"><i class="fas fa-arrow-left"></i> Back to Event</a></li>
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
