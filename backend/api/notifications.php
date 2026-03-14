<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow logged in admins to access notifications
if (!admin_is_logged_in()) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch unread notifications
    $query = "SELECT * FROM admin_notifications WHERE is_read = 0 ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    
    echo json_encode($notifications);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    // Mark as read
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id)) {
        $id = mysqli_real_escape_string($conn, $data->id);
        $query = "UPDATE admin_notifications SET is_read = 1 WHERE id = '$id'";
    } else {
        // Mark all as read
        $query = "UPDATE admin_notifications SET is_read = 1";
    }

    if ($conn->query($query)) {
        echo json_encode(["message" => "Notification updated."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Unable to update notification.", "error" => $conn->error]);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
}
?>
