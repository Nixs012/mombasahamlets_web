<?php
/**
 * payment-callback.php
 * Landing page for card payment callback/redirect.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/includes/payment_gateway.php';

$reference = $_GET['reference'] ?? $_GET['tx_ref'] ?? '';
$orderId = $_GET['order_id'] ?? 0;
$message = $_GET['message'] ?? '';

// Look up order by reference
if ($reference && !$orderId) {
    $stmt = $conn->prepare("SELECT id, status, payment_status, total_amount FROM orders WHERE payment_reference = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $orderId = $order['id'];
    }
    $stmt->close();
}

// Determine redirect target
if ($orderId > 0) {
    $stmt = $conn->prepare("SELECT status, payment_status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $paymentStatus = $order['payment_status'] ?? $order['status'];
        
        if ($paymentStatus === 'paid' || $paymentStatus === 'success') {
            header("Location: order-success.php?status=success&order_id=" . $orderId);
        } else {
            header("Location: payment-failed.php?order_id=" . $orderId . ($message ? '&msg=' . urlencode($message) : ''));
        }
    } else {
        header("Location: checkout.php");
    }
    $stmt->close();
} else {
    header("Location: checkout.php");
}
exit();
