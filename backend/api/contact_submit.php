<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-CSRF-Token");

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/session_config.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name) && !empty($data->email) && !empty($data->subject) && !empty($data->message)) {

        // Verify CSRF token if session is active
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['csrf_token'])) {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data->csrf_token ?? '');
            if (!verify_csrf_token($csrfToken)) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "CSRF token validation failed."]);
                exit();
            }
        }

        $name    = $data->name;
        $email   = $data->email;
        $subject = $data->subject;
        $message = $data->message;

        // 1. Insert into contact_messages using a prepared statement
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            $stmt->close();

            // 2. Create Admin Notification — also using a prepared statement
            $notif_title   = "New Contact Message: " . $subject;
            $notif_content = "From: $name ($email)";
            $notif_link    = "admin.php?tab=messages&id=" . $message_id;

            $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, title, content, link) VALUES ('new_message', ?, ?, ?)");
            $notifStmt->bind_param("sss", $notif_title, $notif_content, $notif_link);
            $notifStmt->execute();
            $notifStmt->close();

            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Message sent successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Unable to send message.", "error" => $conn->error]);
            $stmt->close();
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
}
?>
