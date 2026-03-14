<?php
header("Content-Type: application/json; charset=UTF-8");

// Attempt to include the database connection file.
@include_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/site_helper.php';
require_once __DIR__ . '/../includes/admin_auth_helper.php';

// Enforce admin authentication for POST, PUT, DELETE
require_admin_auth();

// Check if the connection variable $conn exists and is valid.
if (!isset($conn) || (is_object($conn) && $conn->connect_error)) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['error' => 'Database connection failed. Please check server configuration.']);
    exit(); // Stop execution immediately.
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

/**
 * Handles GET requests to fetch all partners or partners by status.
 */
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM partners WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $partner = $result->fetch_assoc();

        if ($partner) {
            echo json_encode($partner);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Partner not found."]);
        }
        $stmt->close();
    } else {
        $status = $_GET['status'] ?? null;
        if ($status) {
            $stmt = $conn->prepare("SELECT * FROM partners WHERE status = ? ORDER BY display_order ASC, name ASC");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $query = "SELECT * FROM partners ORDER BY display_order ASC, name ASC";
            $result = $conn->query($query);
        }

        if ($result) {
            $partners = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($partners);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve partners.']);
        }
    }
}

/**
 * Handles POST requests to create a new partner.
 */
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name']) || empty($data['category']) || empty($data['logo_url'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: Name, Category, and Logo.']);
        return;
    }

    $name = $data['name'];
    $category = $data['category'];
    $logo_url = $data['logo_url'];
    $website_url = $data['website_url'] ?? null;
    $status = $data['status'] ?? 'Active';
    $display_order = intval($data['display_order'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO partners (name, category, logo_url, website_url, status, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $name, $category, $logo_url, $website_url, $status, $display_order);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(201);
        echo json_encode(['message' => 'Partner added successfully.', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add partner.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

/**
 * Handles PUT requests to update a partner.
 */
function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0 || empty($data['name']) || empty($data['category']) || empty($data['logo_url'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID or missing required fields.']);
        return;
    }

    $name = $data['name'];
    $category = $data['category'];
    $logo_url = $data['logo_url'];
    $website_url = $data['website_url'] ?? null;
    $status = $data['status'] ?? 'Active';
    $display_order = intval($data['display_order'] ?? 0);

    $stmt = $conn->prepare("UPDATE partners SET name = ?, category = ?, logo_url = ?, website_url = ?, status = ?, display_order = ? WHERE id = ?");
    $stmt->bind_param("sssssii", $name, $category, $logo_url, $website_url, $status, $display_order, $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'Partner updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update partner.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

/**
 * Handles DELETE requests.
 */
function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid partner ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM partners WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'Partner deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete partner.']);
    }
    $stmt->close();
}

$conn->close();
?>
