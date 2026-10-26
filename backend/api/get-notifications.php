<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-CSRF-Token");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/session_config.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow logged in admins
if (!admin_is_logged_in()) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Fetch unread count
    $count_query = "SELECT COUNT(*) as unread_count FROM admin_notifications WHERE is_read = 0";
    $count_result = $conn->query($count_query);
    $unread_count = $count_result->fetch_assoc()['unread_count'];

    // 2. Fetch latest notifications
    $list_query = "SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10";
    $list_result = $conn->query($list_query);
    
    $notifications = [];
    while ($row = $list_result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "unread_count" => (int)$unread_count,
        "notifications" => $notifications
    ]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Verify CSRF token
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->csrf_token) && !verify_csrf_token($data->csrf_token)) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "CSRF token validation failed."]);
        exit();
    }

    // 3. Mark notification as read using a prepared statement
    if (!empty($data->id)) {
        $id = (int)$data->id;

        $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Unable to prepare statement.", "error" => $conn->error]);
            exit();
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Notification updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Unable to update notification.", "error" => $conn->error]);
        }
        $stmt->close();
    } else {
        // Mark all as read
        $query = "UPDATE admin_notifications SET is_read = 1";

        if ($conn->query($query)) {
            echo json_encode(["status" => "success", "message" => "Notification updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Unable to update notification.", "error" => $conn->error]);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
}
?>
