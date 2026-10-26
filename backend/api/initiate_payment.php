<?php
/**
 * initiate_payment.php
 *
 * Initiates a payment via M-Pesa STK Push or Card Payment (sandbox mode).
 * Supports both shop checkout and ticket checkout.
 */
require_once __DIR__ . '/../includes/session_config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data']);
    exit();
}

// Required fields
$required = ['amount', 'phone', 'payment_method'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: {$field}"]);
        exit();
    }
}

$amount         = floatval($data['amount']);
$phone          = $data['phone'];
$paymentMethod  = $data['payment_method'];
$email          = $data['email'] ?? '';
$fullName       = $data['full_name'] ?? '';
$shippingAddr   = $data['shipping_address'] ?? '';
$deliveryZoneId = $data['delivery_zone_id'] ?? null;
$orderId        = $data['order_id'] ?? null;

// Generate a unique reference before creating the order
$reference = PaymentGateway::generateReference('MP');

try {
    $conn->begin_transaction();

    // 1. Create the order in "pending" status
    $orderStatus = 'pending';

    // Check if delivery_zone_id column exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_zone_id'");
    $hasZoneColumn = $columnCheck && $columnCheck->num_rows > 0;

    $userId = $_SESSION['user']['id'] ?? 0;

    if ($hasZoneColumn) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_cost, shipping_address, delivery_zone_id, status, payment_status, payment_reference) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("iddssis", $userId, $amount, $data['shipping_cost'] ?? 0, $shippingAddr, $deliveryZoneId ?: null, $orderStatus, $reference);
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_cost, status, shipping_address, payment_status, payment_reference) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("iddsss", $userId, $amount, $data['shipping_cost'] ?? 0, $orderStatus, $shippingAddr, $reference);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    $orderId = $conn->insert_id;
    $stmt->close();

    // 2. Insert order items (from session cart for shop, or from provided items for tickets)
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, event_id, ticket_type_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $cart = $_SESSION['cart'] ?? [];
    if (!empty($data['items'])) {
        $cart = $data['items'];
    }

    if (!empty($cart)) {
        foreach ($cart as $item) {
            $productId = $item['id'] ?? null;
            $qty = $item['quantity'] ?? 1;
            $price = $item['sale_price'] ?? $item['price'] ?? $item['unit_price'] ?? 0;
            $eventId = $item['event_id'] ?? null;
            $ticketTypeId = $item['ticket_type_id'] ?? null;
            $subtotal = $price * $qty;
            $stmt->bind_param("iiidiii", $orderId, $productId, $qty, $price, $subtotal, $eventId, $ticketTypeId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert order item: " . $stmt->error);
            }
        }
    }
    $stmt->close();

    // 3. Log payment attempt
    $paymentLogId = PaymentGateway::recordPayment($conn, [
        'order_id' => $orderId,
        'reference' => $reference,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'status' => 'pending',
        'checkout_request_id' => null,
        'raw_response' => null
    ]);

    // 4. Initiate the actual payment
    if ($paymentMethod === 'mpesa_stk') {
        $paymentResult = PaymentGateway::initiateStkPush([
            'phone' => $phone,
            'amount' => $amount,
            'reference' => $reference,
            'callbackURL' => PAYMENT_WEBHOOK_URL,
            'accountNumber' => (string)$orderId
        ]);

        if ($paymentResult['status'] !== 'success') {
            // Update order status to failed
            PaymentGateway::updatePaymentStatus($conn, $orderId, 'failed', [
                'raw_response' => $paymentResult
            ]);
            $conn->rollback();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'M-Pesa STK Push failed: ' . $paymentResult['message'],
                'order_id' => $orderId
            ]);
            exit();
        }

        // Update payment log with checkout request ID
        $updateStmt = $conn->prepare("UPDATE payments SET checkout_request_id = ?, status = 'pending' WHERE order_id = ?");
        $updateStmt->bind_param("si", $paymentResult['checkout_request_id'], $orderId);
        $updateStmt->execute();
        $updateStmt->close();

        // Also update order table
        $updateOrder = $conn->prepare("UPDATE orders SET checkout_request_id = ?, payment_status = 'processing' WHERE id = ?");
        $updateOrder->bind_param("si", $paymentResult['checkout_request_id'], $orderId);
        $updateOrder->execute();
        $updateOrder->close();

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'order_id' => $orderId,
            'reference' => $reference,
            'checkout_request_id' => $paymentResult['checkout_request_id'],
            'payment_method' => 'mpesa_stk',
            'amount' => $amount,
            'message' => 'STK Push initiated. Check your phone to complete payment.'
        ]);

    } elseif ($paymentMethod === 'card') {
        $cardResult = PaymentGateway::initiateCardPayment([
            'email' => $email,
            'amount' => $amount,
            'currency' => PAYMENT_CURRENCY,
            'callback_url' => PAYMENT_CALLBACK_URL,
            'order_id' => $orderId
        ]);

        if ($cardResult['status'] !== 'success') {
            PaymentGateway::updatePaymentStatus($conn, $orderId, 'failed', [
                'raw_response' => $cardResult
            ]);
            $conn->rollback();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Card payment initiation failed: ' . $cardResult['message'],
                'order_id' => $orderId
            ]);
            exit();
        }

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'order_id' => $orderId,
            'reference' => $reference,
            'payment_url' => $cardResult['payment_url'],
            'access_code' => $cardResult['access_code'] ?? '',
            'payment_method' => 'card',
            'amount' => $amount,
            'message' => 'Redirecting to payment page...'
        ]);

    } else {
        throw new Exception("Unsupported payment method: {$paymentMethod}");
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment initiation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
