<?php
/**
 * my_tickets.php
 * Fetches tickets belonging to the authenticated user.
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
    // Auto-generate missing tickets for PAID/COMPLETED orders
    $generateQuery = "SELECT id FROM orders WHERE user_id = ? AND (status = 'paid' OR status = 'completed')";
    $genStmt = $conn->prepare($generateQuery);
    $genStmt->bind_param("i", $userId);
    $genStmt->execute();
    $ordersToGen = $genStmt->get_result();
    if ($ordersToGen->num_rows > 0) {
        require_once '../includes/ticket_helper.php';
        while ($o = $ordersToGen->fetch_assoc()) {
            TicketHelper::generateTicketsForOrder($o['id'], $conn);
        }
    }
    $genStmt->close();

    // Join tickets with events, ticket_types, and orders to get full info
    // We filter by orders.user_id to ensure the user only sees their own tickets
    // We use a UNION to get both generated tickets AND pending ticket items from orders
    $query = "
        -- 1. Actual generated tickets
        SELECT 
            t.id as ticket_id,
            t.ticket_code,
            t.qr_code,
            t.status as ticket_status,
            t.created_at as issued_at,
            e.title as event_title,
            e.event_date,
            e.location,
            e.image_url,
            tt.name as ticket_type_name,
            tt.price as ticket_price,
            o.id as order_id,
            tt.id as ticket_type_id
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN ticket_types tt ON t.ticket_type_id = tt.id
        JOIN orders o ON t.order_id = o.id
        WHERE o.user_id = ?

        UNION ALL

        -- 2. Pending ticket items from orders where tickets haven't been generated yet
        SELECT 
            0 as ticket_id,
            'PENDING' as ticket_code,
            NULL as qr_code,
            UPPER(o.status) as ticket_status,
            o.created_at as issued_at,
            e.title as event_title,
            e.event_date,
            e.location,
            e.image_url,
            tt.name as ticket_type_name,
            tt.price as ticket_price,
            o.id as order_id,
            tt.id as ticket_type_id
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN events e ON oi.event_id = e.id
        JOIN ticket_types tt ON oi.ticket_type_id = tt.id
        LEFT JOIN tickets t ON (t.order_id = oi.order_id AND t.ticket_type_id = oi.ticket_type_id)
        WHERE o.user_id = ? 
          AND oi.event_id IS NOT NULL 
          AND t.id IS NULL -- Only if ticket hasn't been generated
        
        ORDER BY issued_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }

    echo json_encode($tickets);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'details' => $e->getMessage()]);
}

$conn->close();
?>
