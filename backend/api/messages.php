<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';
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
    // Update status
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->id) && !empty($data->status)) {
        $id = mysqli_real_escape_string($conn, $data->id);
        $status = mysqli_real_escape_string($conn, $data->status);
        $query = "UPDATE contact_messages SET status = '$status' WHERE id = '$id'";
        
        if ($conn->query($query)) {
            echo json_encode(["message" => "Message status updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update status.", "error" => $conn->error]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete message
    if (isset($_GET['id'])) {
        $id = mysqli_real_escape_string($conn, $_GET['id']);
        $query = "DELETE FROM contact_messages WHERE id = '$id'";
        
        if ($conn->query($query)) {
            echo json_encode(["message" => "Message deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to delete message.", "error" => $conn->error]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Missing ID."]);
    }
}
?>
