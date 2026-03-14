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

        // 1. Insert into contact_messages
        $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";

        if ($conn->query($query)) {
            $message_id = $conn->insert_id;
            
            // 2. Automatically insert into admin_notifications
            $notif_query = "INSERT INTO admin_notifications (type, reference_id) VALUES ('message', '$message_id')";
            $conn->query($notif_query);

            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Message sent successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Unable to send message.", "error" => $conn->error]);
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
