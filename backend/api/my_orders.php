<?php
/**
 * my_orders.php
 * Fetches shop orders belonging to the authenticated user.
 */
require_once '../db.php';
require_once '../includes/session_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$userId = $_SESSION['user']['id'];

try {
    $query = "SELECT DISTINCT
                o.id as order_id,
                o.total_amount,
                o.status as order_status,
                o.created_at as order_date
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              WHERE o.user_id = ? AND oi.product_id IS NOT NULL
              ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode($orders);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'details' => $e->getMessage()]);
}

$conn->close();
?>
