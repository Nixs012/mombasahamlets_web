<?php
header('Content-Type: application/json');
require_once 'db.php';

// Allow from any origin (or restricting to your domain)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if email is provided
    if (!isset($data['email']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }

    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check if email already exists
    $checkQuery = "SELECT id FROM subscribers WHERE email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Email is already subscribed']);
        exit;
    }

    // Insert email
    $insertQuery = "INSERT INTO subscribers (email) VALUES (?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>
