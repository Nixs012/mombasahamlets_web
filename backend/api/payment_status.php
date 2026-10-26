<?php
/**
 * payment_status.php
 *
 * Queries the status of a payment by order_id or reference.
 * For M-Pesa, queries STK status using CheckoutRequestID.
 */
require_once __DIR__ . '/../includes/session_config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

header("Content-Type: application/json; charset=UTF-8");

$orderId = $_GET['order_id'] ?? null;
$reference = $_GET['reference'] ?? null;

if (!$orderId && !$reference) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Either order_id or reference is required']);
    exit();
}

try {
    if ($orderId) {
        $stmt = $conn->prepare("SELECT id, total_amount, payment_status, status, payment_reference, checkout_request_id, payment_method FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
    } else {
        $stmt = $conn->prepare("SELECT id, total_amount, payment_status, status, payment_reference, checkout_request_id, payment_method FROM orders WHERE payment_reference = ?");
        $stmt->bind_param("s", $reference);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }

    $response = [
        'status' => 'success',
        'order_id' => $order['id'],
        'payment_status' => $order['payment_status'] ?? $order['status'],
        'order_status' => $order['status'],
        'total_amount' => $order['total_amount'],
        'payment_method' => $order['payment_method'] ?? 'mpesa_stk'
    ];

    // If payment is still pending/processing, try to query provider for latest status
    if (in_array($order['status'], ['pending', 'processing']) && !empty($order['checkout_request_id'])) {
        $queryResult = PaymentGateway::queryStkStatus($order['checkout_request_id']);

        if ($queryResult['status'] === 'success' && $queryResult['paid']) {
            // Payment succeeded
            PaymentGateway::updatePaymentStatus($conn, $order['id'], 'paid', [
                'transaction_id' => $queryResult['receipt'] ?? '',
                'raw_response' => $queryResult
            ]);

            $response['payment_status'] = 'paid';
            $response['order_status'] = 'paid';
            $response['updated'] = true;
        } elseif ($queryResult['status'] === 'failed') {
            PaymentGateway::updatePaymentStatus($conn, $order['id'], 'failed', [
                'raw_response' => $queryResult
            ]);
            $response['payment_status'] = 'failed';
            $response['order_status'] = 'failed';
            $response['updated'] = true;
        } else {
            // Still pending
            $response['provider_status'] = $queryResult['status'];
            $response['result_desc'] = $queryResult['result_desc'] ?? '';
            $response['updated'] = false;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Payment status query error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
