<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/site_helper.php';
require_once __DIR__ . '/../includes/admin_auth_helper.php';

// Enforce admin authentication for POST, PUT, DELETE
require_admin_auth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($conn) {
    if (isset($_GET['event_id'])) {
        $event_id = intval($_GET['event_id']);
        $stmt = $conn->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC");
        $stmt->bind_param("i", $event_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM ticket_types ORDER BY event_id, price ASC");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $ticket_types = [];
    while ($row = $result->fetch_assoc()) {
        $ticket_types[] = $row;
    }
    echo json_encode($ticket_types);
}

function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['event_id'], $data['name'], $data['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $event_id = intval($data['event_id']);
    $name = trim($data['name']);
    $price = floatval($data['price']);
    $max_quantity = isset($data['max_quantity']) ? intval($data['max_quantity']) : 0;
    $status = isset($data['status']) ? trim($data['status']) : 'active';

    // Prevent duplicates for (event_id, name)
    $check = $conn->prepare("SELECT id FROM ticket_types WHERE event_id = ? AND name = ?");
    $check->bind_param("is", $event_id, $name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Ticket type already exists for this event']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO ticket_types (event_id, name, price, max_quantity, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdis", $event_id, $name, $price, $max_quantity, $status);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'Ticket type added successfully', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add ticket type', 'details' => $conn->error]);
    }
}

function handleDelete($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ticket type ID']);
        return;
    }

    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM ticket_types WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'Ticket type deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete ticket type']);
    }
}
