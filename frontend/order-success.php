<?php
require_once __DIR__ . '/../backend/includes/session_config.php';
$orderId = $_GET['order_id'] ?? 'Unknown';
$status = $_GET['status'] ?? 'unknown';
$message = $_GET['message'] ?? '';

$title = "Order Placed Successfully!";
$icon = "fa-check-circle";
$iconColor = "#2a9d8f";
$description = "Thank you for your purchase. Your order ID is <span class='order-id'>#" . htmlspecialchars($orderId) . "</span>.";
$subtext = "You will receive a confirmation email shortly.";

if ($status === 'failed') {
    $title = "Payment Failed";
    $icon = "fa-times-circle";
    $iconColor = "#DA291C";
    $description = "We couldn't process your payment. " . htmlspecialchars($message);
    $subtext = "Please try again or contact support if the issue persists.";
} elseif ($status === 'cancelled') {
    $title = "Payment Cancelled";
    $icon = "fa-exclamation-circle";
    $iconColor = "#f4a261";
    $description = "The payment process was cancelled.";
    $subtext = "Your items are still in your cart (if session persisted) or you can re-order from the shop.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .success-container { max-width: 600px; margin: 100px auto; text-align: center; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .success-icon { font-size: 80px; color: <?php echo $iconColor; ?>; margin-bottom: 20px; }
        .success-title { font-size: 2em; margin-bottom: 15px; color: #333; }
        .order-id { font-weight: 700; color: #DA291C; }
        .btn-home { display: inline-block; background: #DA291C; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none; margin-top: 30px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas <?php echo $icon; ?> success-icon"></i>
        <h1 class="success-title"><?php echo $title; ?></h1>
        <p><?php echo $description; ?></p>
        <p><?php echo $subtext; ?></p>
        <a href="shop.php" class="btn-home"><?php echo ($status === 'success' ? 'Return to Shop' : 'Try Again'); ?></a>
    </div>

    <!-- Clear localStorage cart on SUCCESS only -->
    <?php if ($status === 'success'): ?>
    <script>
        localStorage.removeItem('cart');
    </script>
    <?php endif; ?>
</body>
</html>
