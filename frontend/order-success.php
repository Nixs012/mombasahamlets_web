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
<?php
require_once __DIR__ . '/../config/asset_helper.php';
$pageTitle = 'Order Success - Mombasa Hamlets FC';
$pageDescription = 'Your Mombasa Hamlets FC order has been placed successfully. Order confirmation and next steps.';
$pageImage = 'images/logo1.webp';
include __DIR__ . '/includes/meta_head.php';
?>
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
