<?php
/**
 * payment_webhook.php
 *
 * Receives M-Pesa STK Push and Card Payment callbacks/webhooks.
 * Verifies transaction signature and amount against the order.
 * Handles successful, failed, cancelled, and duplicate callbacks.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/payment_gateway.php';
require_once __DIR__ . '/../includes/ticket_helper.php';

header("Content-Type: application/json; charset=UTF-8");

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CALLBACK_AUTH'] ?? $_SERVER['HTTP_SIGNATURE'] ?? $_SERVER['HTTP_X_MPESA_SIGNATURE'] ?? '';

// Log incoming webhook
PaymentGateway::logTransaction([
    'event' => 'webhook_received',
    'headers' => getallheaders(),
    'body' => $rawBody,
    'signature' => $signature
]);

// 1. Verify webhook signature (HMAC)
if (PAYMENT_ENV === 'sandbox') {
    // In sandbox, signature verification may be relaxed but we still attempt verification
    $isValid = PaymentGateway::verifyWebhookSignature($rawBody, $signature);
    if (!$isValid && empty($signature)) {
        // Allow webhook without signature in sandbox if content-type check passes
        PaymentGateway::logTransaction([
            'event' => 'webhook_no_signature_sandbox',
            'message' => 'No signature provided, proceeding in sandbox mode'
        ]);
    } elseif (!$isValid) {
        PaymentGateway::logTransaction([
            'event' => 'webhook_invalid_signature',
            'message' => 'Signature verification failed'
        ]);
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit();
    }
} else {
    // Production: require valid signature
    if (!PaymentGateway::verifyWebhookSignature($rawBody, $signature)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit();
    }
}

// 2. Parse the callback payload (M-Pesa STK Push validation format)
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    // Could be a GET callback with query parameters
    $payload = $_GET;
}

PaymentGateway::logTransaction([
    'event' => 'webhook_payload_parsed',
    'payload' => $payload
]);

// M-Pesa callback structure:
// {
//     "Body": {
//         "stkCallback": {
//             "merchantRequestID": "...",
//             "checkoutRequestID": "...",
//             "resultCode": 0,  // 0 or 00 = success, 1 = failed, 103900 = cancelled
//             "resultDesc": "...",
//             "callbackMetadata": {...}
//         }
//     }
// }

$checkoutRequestId = '';
$reference = '';
$amount = 0;
$orderId = 0;
$status = 'pending';
$transactionId = '';

// Handle M-Pesa STK callback format
if (isset($payload['Body']['stkCallback'])) {
    $stkCallback = $payload['Body']['stkCallback'];
    $checkoutRequestId = $stkCallback['checkoutRequestID'] ?? '';
    $resultCode = $stkCallback['resultCode'] ?? '';
    $resultDesc = $stkCallback['resultDesc'] ?? '';

    // Extract order_id from accountReference (set during STK Push request)
    // accountReference is the order_id we set
    // We need to find the order via checkout_request_id
    $stmt = $conn->prepare("SELECT id, reference, total_amount FROM orders WHERE checkout_request_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $checkoutRequestId);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    $order = $orderResult->fetch_assoc();
    $stmt->close();

    if (!$order) {
        PaymentGateway::logTransaction([
            'event' => 'webhook_order_not_found',
            'checkout_request_id' => $checkoutRequestId
        ]);
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }

    $orderId = $order['id'];
    $reference = $order['payment_reference'];
    $amount = $order['total_amount'];

    // Determine status from resultCode
    if ($resultCode == '0' || $resultCode == '00') {
        $status = 'success';
        // Extract transaction details from callbackMetadata if present
        if (isset($stkCallback['callbackMetadata']['Item'])) {
            foreach ($stkCallback['callbackMetadata']['Item'] as $item) {
                if ($item['Name'] === 'MpesaReceiptCode') {
                    $transactionId = $item['Value'];
                } elseif ($item['Name'] === 'Amount') {
                    $amount = $item['Value']; // Use verified amount from provider
                }
            }
        }
    } elseif ($resultCode == '1' || $resultCode == '103900') {
        $status = 'cancelled';
    } else {
        $status = 'failed';
    }

    // 3. Check for duplicate callbacks
    if (PaymentGateway::isDuplicateTransaction($conn, $reference)) {
        PaymentGateway::logTransaction([
            'event' => 'webhook_duplicate',
            'reference' => $reference,
            'order_id' => $orderId,
            'status' => $status
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Payment already processed']);
        exit();
    }

} elseif (isset($payload['event']) && isset($payload['data'])) {
    // Handle Card Payment provider webhook format
    $event = $payload['event'];
    $data = $payload['data'];

    $reference = $data['reference'] ?? '';
    $amount = ($data['amount'] ?? 0) / 100; // Convert cents to KES
    $transactionId = $data['transaction'] ?? $data['id'] ?? '';

    $stmt = $conn->prepare("SELECT id, total_amount FROM orders WHERE payment_reference = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    $order = $orderResult->fetch_assoc();
    $stmt->close();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }

    $orderId = $order['id'];

    // Map event types to status
    $statusMap = [
        'charge.success' => 'success',
        'charge.failed' => 'failed',
        'charge.cancelled' => 'cancelled'
    ];
    $status = $statusMap[$event] ?? 'pending';

    if (PaymentGateway::isDuplicateTransaction($conn, $reference)) {
        echo json_encode(['status' => 'success', 'message' => 'Payment already processed']);
        exit();
    }

} else {
    // Unknown webhook format — try to extract reference directly
    $reference = $payload['reference'] ?? $payload['data']['reference'] ?? '';
    if (empty($reference)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid webhook format']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, total_amount FROM orders WHERE payment_reference = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    $order = $orderResult->fetch_assoc();
    $stmt->close();

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }
    $orderId = $order['id'];
    $amount = $order['total_amount'];
    // Status should be provided in payload
    $status = $payload['status'] ?? $payload['data']['status'] ?? 'pending';
}

// 4. Validate amount against order total (prevent tampering)
if (!PaymentGateway::validateAmount($conn, $orderId, $amount)) {
    PaymentGateway::logTransaction([
        'event' => 'webhook_amount_mismatch',
        'order_id' => $orderId,
        'expected' => $order['total_amount'],
        'received' => $amount
    ]);
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Amount mismatch: order expects ' . $order['total_amount'] . ', received ' . $amount
    ]);
    exit();
}

// 5. Update order status based on payment
if ($status === 'success') {
    // Mark order as paid
    PaymentGateway::updatePaymentStatus($conn, $orderId, 'paid', [
        'transaction_id' => $transactionId,
        'raw_response' => $payload,
        'amount' => $amount
    ]);

    // Generate tickets if this was a ticket order
    TicketHelper::generateTicketsForOrder($orderId, $conn);

    // Create admin notification
    $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, reference_id) VALUES ('order', ?)");
    $notifStmt->bind_param("i", $orderId);
    $notifStmt->execute();
    $notifStmt->close();

    $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, reference_id) VALUES ('payment', ?)");
    $notifStmt->bind_param("i", $orderId);
    $notifStmt->execute();
    $notifStmt->close();

    PaymentGateway::logTransaction([
        'event' => 'payment_success_processed',
        'order_id' => $orderId,
        'reference' => $reference,
        'transaction_id' => $transactionId
    ]);
} else {
    // Failed or cancelled
    $updateStatus = ($status === 'cancelled') ? 'cancelled' : 'failed';
    PaymentGateway::updatePaymentStatus($conn, $orderId, $updateStatus, [
        'raw_response' => $payload
    ]);

    PaymentGateway::logTransaction([
        'event' => 'payment_failed_processed',
        'order_id' => $orderId,
        'reference' => $reference,
        'status' => $status
    ]);
}

// 6. Record payment transaction
PaymentGateway::recordPayment($conn, [
    'order_id' => $orderId,
    'reference' => $reference,
    'amount' => $amount,
    'payment_method' => 'mpesa_stk',
    'status' => $status === 'success' ? 'success' : 'failed',
    'checkout_request_id' => $checkoutRequestId,
    'raw_response' => $payload
]);

// Return success
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Payment status updated',
    'order_id' => $orderId,
    'payment_status' => $status
]);
?>
