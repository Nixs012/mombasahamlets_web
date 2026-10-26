<?php
require_once __DIR__ . '/db.php';
if (!isset($conn)) exit(1);

$res = $conn->query("SHOW COLUMNS FROM matches");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
$conn->close();
?>
