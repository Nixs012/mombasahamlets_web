<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_helper.php';

// Allow GET for everyone (frontend), but require admin for other methods
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    admin_require_login();
}

if ($method === 'GET') {
    // Return all settings
    $result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode($settings);
} 
elseif ($method === 'POST' || $method === 'PUT') {
    // Update settings (batch)
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $conn->begin_transaction();
    try {
        foreach ($data as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
            $stmt->close();
        }
        update_site_timestamp($conn);
        $conn->commit();
        echo json_encode(['message' => 'Settings updated successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
