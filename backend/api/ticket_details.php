<?php
/**
 * ticket_details.php
 * Fetches full details of a specific ticket for the authenticated user.
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
$ticketId = isset($_GET['id']) ? $_GET['id'] : null;
$ticketCode = $_GET['code'] ?? null;
$orderId = $_GET['order_id'] ?? null;
$typeId = $_GET['type_id'] ?? null;

if ($ticketId === null && !$ticketCode && (!$orderId || !$typeId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID, Code, or Order/Type IDs are required']);
    exit();
}

try {
    // 2. Fetch Ticket Details & Verify Ownership
    // We try to find a real ticket first
    $query = "SELECT 
                t.id as ticket_id,
                t.ticket_code,
                t.qr_code,
                t.status as ticket_status,
                t.created_at as issued_at,
                e.title as event_title,
                e.event_date,
                e.event_time,
                e.location,
                e.image_url as event_image,
                tt.name as ticket_type_name,
                tt.price as ticket_price,
                o.id as order_id,
                o.created_at as order_date
              FROM tickets t
              JOIN events e ON t.event_id = e.id
              JOIN ticket_types tt ON t.ticket_type_id = tt.id
              JOIN orders o ON t.order_id = o.id
              WHERE ((t.id = ? AND t.id != 0) OR t.ticket_code = ?) AND o.user_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $ticketId, $ticketCode, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 && ($ticketId == 0 || $ticketId === null) && $orderId && $typeId) {
        // Fallback: This might be a PROCESSING ticket (only in order_items)
        // First, check if we should try to generate tickets now (e.g. if order was just paid)
        $orderCheck = $conn->prepare("SELECT status FROM orders WHERE id = ?");
        $orderCheck->bind_param("i", $orderId);
        $orderCheck->execute();
        $oStatus = $orderCheck->get_result()->fetch_assoc()['status'] ?? '';
        $orderCheck->close();

        if (strtolower($oStatus) === 'paid' || strtolower($oStatus) === 'completed') {
            require_once '../includes/ticket_helper.php';
            TicketHelper::generateTicketsForOrder($orderId, $conn);
            
            // Re-run the primary query to see if we now have a real ticket
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isi", $ticketId, $ticketCode, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        if ($result->num_rows === 0) {
            // Still no real ticket, show the processing fallback
            $query = "SELECT 
                        0 as ticket_id,
                        'PENDING' as ticket_code,
                        NULL as qr_code,
                        UPPER(o.status) as ticket_status,
                        o.created_at as issued_at,
                        e.title as event_title,
                        e.event_date,
                        e.event_time,
                        e.location,
                        e.image_url as event_image,
                        tt.name as ticket_type_name,
                        tt.price as ticket_price,
                        o.id as order_id,
                        o.created_at as order_date
                      FROM order_items oi
                      JOIN orders o ON oi.order_id = o.id
                      JOIN events e ON oi.event_id = e.id
                      JOIN ticket_types tt ON oi.ticket_type_id = tt.id
                      WHERE o.id = ? AND tt.id = ? AND o.user_id = ?
                      LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $orderId, $typeId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
        }
    }

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Ticket not found or access denied']);
        exit();
    }
    
    $ticketData = $result->fetch_assoc();
    echo json_encode($ticketData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'details' => $e->getMessage()]);
}

$conn->close();
?>
