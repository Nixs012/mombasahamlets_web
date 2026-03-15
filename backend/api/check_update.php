<?php
/**
 * check_update.php
 * Returns the timestamp of the last content update for the auto-refresh feature.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $key = 'last_content_update';
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $last_update = 0;
    if ($row = $result->fetch_assoc()) {
        $last_update = (int)$row['setting_value'];
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'last_update' => $last_update
    ]);
} catch (Exception $e) {
    // Return 0 on error to avoid breaking the frontend check
    echo json_encode([
        'status' => 'error',
        'last_update' => 0,
        'message' => $e->getMessage()
    ]);
}
?>
