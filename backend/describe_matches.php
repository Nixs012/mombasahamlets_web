<?php
require_once __DIR__ . '/db.php';
if (!isset($conn)) {
    echo "Database connection failed.\n";
    exit(1);
}

$result = $conn->query('DESCRIBE matches');
if ($result) {
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
$conn->close();
?>
