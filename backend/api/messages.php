<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-CSRF-Token");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/session_config.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow logged in admins
if (!admin_is_logged_in()) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $messages = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    
    echo json_encode($messages);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Verify CSRF token
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->csrf_token) && !verify_csrf_token($data->csrf_token)) {
        http_response_code(403);
        echo json_encode(["message" => "CSRF token validation failed."]);
        exit();
    }

    // Update status using a prepared statement
    if (!empty($data->id) && !empty($data->status)) {
        $id = (int)$data->id;
        $status = $data->status;

        $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["message" => "Unable to prepare statement.", "error" => $conn->error]);
            exit();
        }

        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Message status updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update status.", "error" => $conn->error]);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Verify CSRF token
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->csrf_token) && !verify_csrf_token($data->csrf_token)) {
        http_response_code(403);
        echo json_encode(["message" => "CSRF token validation failed."]);
        exit();
    }

    // Delete message using a prepared statement
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["message" => "Unable to prepare statement.", "error" => $conn->error]);
            exit();
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Message deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to delete message.", "error" => $conn->error]);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Missing ID."]);
    }
}
?>
