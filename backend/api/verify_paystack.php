<?php
/**
 * verify_paystack.php
 * Verifies Paystack transaction and processes the order.
 */
require_once __DIR__ . '/../includes/session_config.php';
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/paystack_helper.php';
require_once __DIR__ . '/../includes/ticket_helper.php';

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['reference'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit();
}

$reference = $data['reference'];
$fullName = $data['full_name'] ?? '';
$phone = $data['phone'] ?? '';
$shippingAddress = $data['shipping_address'] ?? '';
$shippingCost = $data['shipping_cost'] ?? 0;
$userId = $_SESSION['user']['id'] ?? 0;

try {
    // 1. Verify Transaction with Paystack
    $verification = PaystackHelper::verifyTransaction($reference);

    if (!$verification->status || $verification->data->status !== 'success') {
        throw new Exception("Payment verification failed: " . ($verification->message ?? 'Unknown error'));
    }

    $verifiedAmount = $verification->data->amount / 100; // Convert kobo to KES
    $verifiedEmail = $verification->data->customer->email;

    // 2. Database Transaction
    $conn->begin_transaction();

    // Calculate Cart Total (to double check)
    // If it's a shop checkout, cart is in session
    // If it's a ticket checkout, we might have passed different metadata or session
    // For now, let's assume it's shop checkout if cart session exists
    $cart = $_SESSION['cart'] ?? [];
    $cartTotal = 0;
    foreach ($cart as $item) {
        $price = $item['sale_price'] ?? $item['price'] ?? 0;
        $cartTotal += $price * $item['quantity'];
    }

    $expectedTotal = $cartTotal + $shippingCost;

    // Verify that verifiedAmount matches expectedTotal
    // Allow 1 KES tolerance for rounding, but fail if greater
    if (abs($verifiedAmount - $expectedTotal) > 1.00) {
        // Log the discrepancy
        error_log(
            "Amount mismatch for order: verified=" . $verifiedAmount . 
            ", expected=" . $expectedTotal . 
            ", user_id=" . $userId
        );
        throw new Exception(
            'Payment verification failed: Amount mismatch. ' .
            'Verified: ' . $verifiedAmount . ' KES, Expected: ' . $expectedTotal . ' KES. ' .
            'Please contact support if this error persists.'
        );
    }

    // 3. Insert into orders
    $orderStatus = 'paid'; // Since we verified payment
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_cost, status, shipping_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iddss", $userId, $verifiedAmount, $shippingCost, $orderStatus, $shippingAddress);
    if (!$stmt->execute()) throw new Exception("Failed to insert order: " . $stmt->error);
    $orderId = $conn->insert_id;
    $stmt->close();

    // 4. Create Admin Notification for Order
    $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, reference_id) VALUES ('order', ?)");
    $notifStmt->bind_param("i", $orderId);
    $notifStmt->execute();
    $notifStmt->close();

    // 5. Insert into order_items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, event_id, ticket_type_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($cart as $item) {
        $productId = $item['id'] ?? null;
        $qty = $item['quantity'];
        $price = $item['sale_price'] ?? $item['price'] ?? 0;
        $subtotal = $price * $qty;
        $eventId = $item['event_id'] ?? null;
        $ticketTypeId = $item['ticket_type_id'] ?? null;
        
        $stmt->bind_param("iiidiii", $orderId, $productId, $qty, $price, $subtotal, $eventId, $ticketTypeId);
        if (!$stmt->execute()) throw new Exception("Failed to insert order item: " . $stmt->error);
    }
    $stmt->close();

    // 6. Create payment record
    $paymentStatus = 'success';
    $paymentMethod = 'Paystack';
    $stmt = $conn->prepare("INSERT INTO payments (order_id, reference, amount, payment_method, status, payment_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isdss", $orderId, $reference, $verifiedAmount, $paymentMethod, $paymentStatus);
    if (!$stmt->execute()) throw new Exception("Failed to create payment record: " . $stmt->error);
    $paymentId = $conn->insert_id;
    $stmt->close();

    // 7. Create Admin Notification for Payment
    $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, reference_id) VALUES ('payment', ?)");
    $notifStmt->bind_param("i", $paymentId);
    $notifStmt->execute();
    $notifStmt->close();

    // 8. Generate Tickets (if any items were tickets)
    TicketHelper::generateTicketsForOrder($orderId, $conn);

    // Commit Transaction
    $conn->commit();

    // 9. Clear Cart
    unset($_SESSION['cart']);

    echo json_encode([
        'status' => 'success',
        'redirect_url' => 'order-success.php?status=success&order_id=' . $orderId
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>
