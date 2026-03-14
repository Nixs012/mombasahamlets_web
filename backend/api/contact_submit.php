<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name) && !empty($data->email) && !empty($data->subject) && !empty($data->message)) {
        
        $name = mysqli_real_escape_string($conn, $data->name);
        $email = mysqli_real_escape_string($conn, $data->email);
        $subject = mysqli_real_escape_string($conn, $data->subject);
        $message = mysqli_real_escape_string($conn, $data->message);

        $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";

        if ($conn->query($query)) {
            $message_id = $conn->insert_id;
            
            // Create Admin Notification
            $notif_title = "New Contact Message: " . $subject;
            $notif_content = "From: $name ($email)";
            $notif_link = "admin.php?tab=messages&id=" . $message_id;
            
            $notif_query = "INSERT INTO admin_notifications (type, title, content, link) VALUES ('new_message', '$notif_title', '$notif_content', '$notif_link')";
            $conn->query($notif_query);

            http_response_code(201);
            echo json_encode(["message" => "Message sent successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to send message.", "error" => $conn->error]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
}
?>
