<?php
@include_once 'c:/wamp64/www/mombasahamlets_web/backend/db.php';
if (!isset($conn)) {
    echo "Database connection failed.\n";
    exit(1);
}

$result = $conn->query('SELECT id, home_team, away_team, home_logo, away_logo FROM matches ORDER BY id DESC LIMIT 5');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo "Match ID: " . $row['id'] . "\n";
        echo "Home Team: " . $row['home_team'] . " - Logo: " . ($row['home_logo'] ?? 'NULL') . "\n";
        echo "Away Team: " . $row['away_team'] . " - Logo: " . ($row['away_logo'] ?? 'NULL') . "\n";
        echo "-------------------\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
$conn->close();
?>
