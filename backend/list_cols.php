<?php
@include_once 'c:/wamp64/www/mombasahamlets_web/backend/db.php';
if (!isset($conn)) exit(1);

$res = $conn->query("SHOW COLUMNS FROM matches");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
$conn->close();
?>
