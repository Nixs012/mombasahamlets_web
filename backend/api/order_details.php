<?php
/**
 * order_details.php
 * Fetches full details of a specific order for the authenticated user.
 */
require_once '../db.php';
require_once '../includes/session_config.php';

header('Content-Type: application/json');

// 1. Auth Check
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$userId = $_SESSION['user']['id'];
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

try {
    // 2. Fetch Order Metadata & Verify Ownership
    $orderQuery = "SELECT id, total_amount, shipping_cost, status, shipping_address, created_at FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Order not found or access denied']);
        exit();
    }
    
    $orderData = $orderResult->fetch_assoc();
    $stmt->close();

    // 3. Fetch Order Items (Including Shop products and Event info)
    $itemsQuery = "SELECT 
                    oi.*, 
                    p.name as product_name, 
                    p.image_url as product_image,
                    e.title as event_title,
                    tt.name as ticket_type_name
                   FROM order_items oi
                   LEFT JOIN shop p ON oi.product_id = p.id
                   LEFT JOIN events e ON oi.event_id = e.id
                   LEFT JOIN ticket_types tt ON oi.ticket_type_id = tt.id
                   WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $itemsResult = $stmt->get_result();
    $items = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    // 4. Fetch Payment Info
    $payQuery = "SELECT status as payment_status, payment_method, reference, payment_date FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($payQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 5. Response
    echo json_encode([
        'order' => $orderData,
        'items' => $items,
        'payment' => $payment
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'details' => $e->getMessage()]);
}

$conn->close();
?>
