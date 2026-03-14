<?php
/**
 * check_update.php
 * Endpoint to check for the latest content update timestamp.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow from frontend if needed

require_once __DIR__ . '/../db.php';

$query = "SELECT setting_value FROM site_settings WHERE setting_key = 'last_content_update'";
$result = $conn->query($query);

$timestamp = 0;
if ($result && $row = $result->fetch_assoc()) {
    $timestamp = (int)$row['setting_value'];
}

echo json_encode(['last_update' => $timestamp]);

if (isset($conn)) {
    $conn->close();
}
?>
