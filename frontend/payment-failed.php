<?php
/**
 * payment-failed.php
 * Displays when a payment fails, is cancelled, or encounters an error.
 * Includes a retry button and order details.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/includes/payment_gateway.php';

$orderId = $_GET['order_id'] ?? 0;

$orderInfo = null;
if ($orderId > 0) {
    $stmt = $conn->prepare("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderInfo = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        .payment-failed-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .failed-icon {
            font-size: 4rem;
            color: var(--red, #DC3545);
            margin-bottom: 25px;
        }
        .failed-icon i {
            font-size: 4rem;
        }
        h1 {
            color: var(--dark-navy, #0A192F);
            margin-bottom: 10px;
        }
        .failed-message {
            color: var(--text-secondary, #666);
            font-size: 1.1em;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .order-details-failed {
            background: var(--off-white, #f8f9fa);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        .order-details-failed h4 {
            margin-top: 0;
            color: var(--dark-navy, #0A192F);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #dee2e6);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .btn-retry {
            background: var(--primary-blue, #003366);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s;
            margin: 15px 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-retry:hover {
            background: var(--accent-gold, #DAA520);
        }
        .btn-home {
            background: transparent;
            color: var(--primary-blue, #003366);
            border: 2px solid var(--primary-blue, #003366);
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 15px 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-home:hover {
            background: var(--primary-blue, #003366);
            color: white;
        }
        .error-details {
            margin-top: 20px;
            padding: 15px;
            background: rgba(220, 53, 69, 0.05);
            border-radius: 8px;
            font-size: 0.9em;
            color: var(--text-secondary, #666);
            border-left: 4px solid var(--red, #DC3545);
        }
        @media (max-width: 768px) {
            .payment-failed-container {
                margin: 20px;
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-failed-container">
        <div class="failed-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1>Payment Failed</h1>
        <p class="failed-message">
            <?php if (isset($_GET['msg'])): ?>
                <?php echo htmlspecialchars($_GET['msg']); ?>
            <?php else: ?>
                There was an issue processing your payment. This could be due to:
            <?php endif; ?>
        </p>

        <?php if (!isset($_GET['msg'])): ?>
        <ul style="text-align: left; display: inline-block; color: var(--text-secondary, #666); margin-bottom: 30px;">
            <li style="margin-bottom: 8px;">Insufficient M-Pesa STK balance</li>
            <li style="margin-bottom: 8px;">Transaction cancelled by user</li>
            <li style="margin-bottom: 8px;">Network connectivity issues</li>
            <li style="margin-bottom: 8px;">Temporary service outage</li>
        </ul>
        <?php endif; ?>

        <?php if ($orderInfo): ?>
        <div class="order-details-failed">
            <h4><i class="fas fa-shopping-bag"></i> Order #<?php echo $orderInfo['id']; ?></h4>
            <div class="detail-row">
                <span>Customer</span>
                <span><?php echo htmlspecialchars(($orderInfo['first_name'] ?? '') . ' ' . ($orderInfo['last_name'] ?? '')); ?></span>
            </div>
            <div class="detail-row">
                <span>Email</span>
                <span><?php echo htmlspecialchars($orderInfo['email'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
                <span>Total Amount</span>
                <span><strong>KSh <?php echo number_format($orderInfo['total_amount'], 2); ?></strong></span>
            </div>
            <div class="detail-row">
                <span>Payment Status</span>
                <span style="color: var(--red, #DC3545); font-weight: 700;"><?php echo ucfirst($orderInfo['status'] ?? 'failed'); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin: 30px 0;">
            <?php if ($orderInfo): ?>
                <button class="btn-retry" onclick="retryPayment()">
                    <i class="fas fa-redo"></i> Retry Payment
                </button>
            <?php else: ?>
                <a href="checkout.php" class="btn-retry">
                    <i class="fas fa-redo"></i> Retry Checkout
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Return to Home
            </a>
        </div>

        <?php if (isset($_GET['debug']) && isset($_GET['error'])): ?>
        <div class="error-details">
            <strong>Technical Details:</strong><br>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <p style="margin-top: 30px; font-size: 0.9em; color: var(--text-secondary, #666);">
            <i class="fas fa-headset"></i> Need help? Contact us at 
            <a href="tel:+254700000000" style="color: var(--primary-blue, #003366);">+254 700 000 000</a>
            or <a href="mailto:support@mombasahamlets.com" style="color: var(--primary-blue, #003366);">support@mombasahamlets.com</a>
        </p>
    </div>

    <?php if ($orderInfo && $orderInfo['payment_method'] === 'mpesa_stk'): ?>
    <script>
        function retryPayment() {
            const orderId = <?php echo $orderInfo['id']; ?>;
            
            fetch('../backend/api/payment_status.php?order_id=' + orderId)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Re-initiate STK push
                        fetch('../backend/api/initiate_payment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                amount: <?php echo $orderInfo['total_amount']; ?>,
                                phone: '',  // User will need to re-enter
                                payment_method: 'mpesa_stk',
                                order_id: orderId,
                                reference: <?php echo json_encode($orderInfo['payment_reference']); ?>
                            })
                        })
                        .then(r => r.json())
                        .then(result => {
                            if (result.status === 'success') {
                                alert('STK Push re-sent! Please check your phone to complete the payment.');
                                window.location.href = '../backend/api/payment_status.php?order_id=' + orderId + '&checking=1';
                            } else {
                                alert('Failed to re-initiate payment: ' + result.message);
                            }
                        })
                        .catch(err => alert('Network error: ' + err.message));
                    }
                });
        }
    </script>
    <?php endif; ?>
</body>
</html>
