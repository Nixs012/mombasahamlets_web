<?php
/**
 * backend/api/delivery_zones.php
 * API endpoint for CRUD operations on delivery zones.
 *
 * GET:
 *   - No params: returns all active delivery zones (public)
 *   - ?all=1: returns all zones including inactive (admin only for POST)
 *   - ?id={id}: returns single zone (public)
 *
 * POST (admin only):
 *   - Create new zone or update existing zone
 *
 * DELETE (admin only):
 *   - ?id={id}: soft-delete (deactivate) a zone
 */

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

/**
 * GET handler
 * Returns active zones (public) or all zones when ?all=1 (admin only)
 */
function handleGet($conn) {
    // For admin requests, allow fetching all zones including inactive
    $all = isset($_GET['all']) && $_GET['all'] == 1;
    
    if (isset($_GET['id'])) {
        // Fetch single zone
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT id, name, fee, description, active, created_at, updated_at FROM delivery_zones WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($all && is_admin_authorized()) {
        // Admin: fetch all zones
        $stmt = $conn->prepare("SELECT id, name, fee, description, active, created_at, updated_at FROM delivery_zones ORDER BY name ASC");
    } else {
        // Public: fetch only active zones
        $stmt = $conn->prepare("SELECT id, name, fee, description, active, created_at, updated_at FROM delivery_zones WHERE active = 1 ORDER BY fee ASC, name ASC");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    if (isset($_GET['id']) && $result->num_rows === 1) {
        // Single zone response
        echo json_encode($result->fetch_assoc());
    } else {
        // Multiple zones response
        $zones = [];
        while ($row = $result->fetch_assoc()) {
            $zones[] = $row;
        }
        echo json_encode($zones);
    }
}

/**
 * POST handler
 * Creates a new zone or updates an existing one
 * Expected JSON: { id: optional, name, fee, description, active }
 */
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['name'], $data['fee'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name and fee are required']);
        return;
    }

    $name = trim($data['name']);
    $fee = floatval($data['fee']);
    $description = isset($data['description']) ? trim($data['description']) : null;
    $active = isset($data['active']) ? intval($data['active']) : 1;

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Zone name cannot be empty']);
        return;
    }

    if ($fee < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Fee cannot be negative']);
        return;
    }

    // Check if updating existing zone or creating new
    if (isset($data['id']) && $data['id']) {
        // UPDATE
        $id = intval($data['id']);
        
        // Check if zone exists
        $checkStmt = $conn->prepare("SELECT id FROM delivery_zones WHERE id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Delivery zone not found']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        $stmt = $conn->prepare("UPDATE delivery_zones SET name = ?, fee = ?, description = ?, active = ? WHERE id = ?");
        $stmt->bind_param("sdsii", $name, $fee, $description, $active, $id);
        
        if ($stmt->execute()) {
            update_site_timestamp($conn);
            echo json_encode([
                'message' => 'Delivery zone updated successfully',
                'id' => $id,
                'name' => $name,
                'fee' => $fee
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update delivery zone', 'details' => $conn->error]);
        }
    } else {
        // INSERT
        // Check for duplicate name
        $checkStmt = $conn->prepare("SELECT id FROM delivery_zones WHERE name = ?");
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'A delivery zone with this name already exists']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        $stmt = $conn->prepare("INSERT INTO delivery_zones (name, fee, description, active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $name, $fee, $description, $active);
        
        if ($stmt->execute()) {
            update_site_timestamp($conn);
            echo json_encode([
                'message' => 'Delivery zone added successfully',
                'id' => $stmt->insert_id,
                'name' => $name,
                'fee' => $fee
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add delivery zone', 'details' => $conn->error]);
        }
    }

    $stmt->close();
}

/**
 * DELETE handler
 * Soft-deletes (deactivates) a zone
 * ?id={id}
 */
function handleDelete($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing zone ID']);
        return;
    }

    $id = intval($_GET['id']);
    
    // Soft delete: set active = 0
    $stmt = $conn->prepare("UPDATE delivery_zones SET active = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            update_site_timestamp($conn);
            echo json_encode(['message' => 'Delivery zone deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Delivery zone not found or already deleted']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete delivery zone']);
    }
    
    $stmt->close();
}
