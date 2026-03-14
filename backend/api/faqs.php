<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_helper.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    admin_require_login();
}

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        $result = $conn->query("SELECT * FROM faqs ORDER BY display_order ASC");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode($items);
    }
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO faqs (question, answer, display_order) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $data['question'], $data['answer'], $data['display_order']);
    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'FAQ added', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $conn->error]);
    }
} 
elseif ($method === 'PUT') {
    $id = (int)$_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, display_order = ? WHERE id = ?");
    $stmt->bind_param("ssii", $data['question'], $data['answer'], $data['display_order'], $id);
    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'FAQ updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $conn->error]);
    }
} 
elseif ($method === 'DELETE') {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        update_site_timestamp($conn);
        echo json_encode(['message' => 'FAQ deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $conn->error]);
    }
}
?>
