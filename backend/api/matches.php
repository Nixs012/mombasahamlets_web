<?php
// This file is now the authoritative API endpoint for matches.
header("Content-Type: application/json; charset=UTF-8");

// Attempt to include the database connection file.
@include_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/site_helper.php';
require_once __DIR__ . '/../includes/admin_auth_helper.php';

// Enforce admin authentication for POST, PUT, DELETE
require_admin_auth();

// Check if the connection variable $conn exists and is valid.
if (!isset($conn) || (is_object($conn) && $conn->connect_error)) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['error' => 'Database connection failed. Please check server configuration.']);
    exit(); // Stop execution immediately.
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

/**
 * Handles GET requests to fetch all matches or a single match by ID.
 */
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid match ID.']);
            return;
        }

        $stmt = $conn->prepare("SELECT * FROM matches WHERE id = ?");
        
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
            return;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $match = $result->fetch_assoc();

        if ($match) {
            // Unpack combined lineups if present
            if (!empty($match['lineups'])) {
                $lineups = json_decode($match['lineups'], true);
                if (is_array($lineups)) {
                    $match['home_lineup'] = $lineups['home_lineup'] ?? null;
                    $match['away_lineup'] = $lineups['away_lineup'] ?? null;
                }
            }
            echo json_encode($match);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Match with ID $id not found."]);
        }
        $stmt->close();
    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

        $where = "";
        if ($statusFilter !== 'all') {
            if ($statusFilter === 'upcoming' || $statusFilter === 'scheduled') {
                $where = "WHERE status IN ('upcoming', 'scheduled')";
            } else {
                $where = "WHERE status = '" . $conn->real_escape_string($statusFilter) . "'";
            }
        }

        if ($page !== null || $limit !== null) {
            $page = $page ?? 1;
            $limit = $limit ?? 8;
            $offset = ($page - 1) * $limit;

            $countResult = $conn->query("SELECT COUNT(*) as total FROM matches $where");
            $total = $countResult->fetch_assoc()['total'];

            // Sort logic matching frontend: live first, then upcoming (soonest), then finished (recent)
            $query = "SELECT * FROM matches $where ORDER BY 
                CASE 
                    WHEN status = 'live' THEN 1 
                    WHEN status IN ('upcoming', 'scheduled') THEN 2 
                    WHEN status = 'finished' THEN 3 
                    ELSE 4 
                END ASC,
                CASE 
                    WHEN status = 'finished' THEN UNIX_TIMESTAMP(match_date) * -1
                    ELSE UNIX_TIMESTAMP(match_date)
                END ASC
                LIMIT $limit OFFSET $offset";
            
            $result = $conn->query($query);

            if ($result) {
                $matches = $result->fetch_all(MYSQLI_ASSOC);
                foreach ($matches as &$match) {
                    if (!empty($match['lineups'])) {
                        $lineups = json_decode($match['lineups'], true);
                        if (is_array($lineups)) {
                            $match['home_lineup'] = $lineups['home_lineup'] ?? null;
                            $match['away_lineup'] = $lineups['away_lineup'] ?? null;
                        }
                    }
                }
                unset($match);

                echo json_encode([
                    'data' => $matches,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'pages' => (int)ceil($total / $limit)
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve matches from the database.']);
            }
        } else {
            // Backward compatibility
            $query = "SELECT * FROM matches $where ORDER BY match_date ASC";
            $result = $conn->query($query);

            if ($result) {
                $matches = $result->fetch_all(MYSQLI_ASSOC);
                foreach ($matches as &$match) {
                    if (!empty($match['lineups'])) {
                        $lineups = json_decode($match['lineups'], true);
                        if (is_array($lineups)) {
                            $match['home_lineup'] = $lineups['home_lineup'] ?? null;
                            $match['away_lineup'] = $lineups['away_lineup'] ?? null;
                        }
                    }
                }
                unset($match);
                echo json_encode($matches);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve matches from the database.']);
            }
        }
    }
}

/**
 * Handles POST requests to create a new match.
 */
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['home_team']) || empty($data['away_team']) || empty($data['match_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: Home Team, Away Team, and Date.']);
        return;
    }

    $home_team = $data['home_team'];
    $away_team = $data['away_team'];
    $competition = $data['competition'] ?? '';
    $match_date = $data['match_date'];
    $status = $data['status'] ?? 'scheduled';
    $home_score = isset($data['home_score']) && $data['home_score'] !== '' ? $data['home_score'] : null;
    $away_score = isset($data['away_score']) && $data['away_score'] !== '' ? $data['away_score'] : null;

    // Map to existing table columns
    $venue = !empty($data['venue']) ? $data['venue'] : 'TBA';
    $round = $data['round'] ?? '';
    $referee = $data['referee'] ?? '';
    $attendance = isset($data['attendance']) && $data['attendance'] !== '' ? $data['attendance'] : null;
    $preview_content = $data['match_report'] ?? ($data['preview_content'] ?? '');
    // Combine home/away lineup into a single JSON column if provided
    $lineups = null;
    if (!empty($data['home_lineup']) || !empty($data['away_lineup'])) {
        $lineups = json_encode([
            'home_lineup' => $data['home_lineup'] ?? null,
            'away_lineup' => $data['away_lineup'] ?? null,
        ]);
    } else {
        $lineups = $data['lineups'] ?? null;
    }
    $statistics = $data['statistics'] ?? null;
    $commentary = $data['commentary'] ?? null;
    $home_logo = $data['home_logo'] ?? null;
    $away_logo = $data['away_logo'] ?? null;

    $stmt = $conn->prepare("INSERT INTO matches (home_team, away_team, home_logo, away_logo, competition, match_date, status, home_score, away_score, venue, `round`, referee, attendance, preview_content, lineups, statistics, commentary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }

    $stmt->bind_param(
        "sssssssssssssssss",
        $home_team,
        $away_team,
        $home_logo,
        $away_logo,
        $competition,
        $match_date,
        $status,
        $home_score,
        $away_score,
        $venue,
        $round,
        $referee,
        $attendance,
        $preview_content,
        $lineups,
        $statistics,
        $commentary
    );

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        
        // Send notification to subscribers
        require_once __DIR__ . '/../mailer.php';
        $subject = "Upcoming Match: " . $home_team . " vs " . $away_team;
        $message = "Get ready for the match between " . $home_team . " and " . $away_team . " on " . $match_date . ".";
        sendNotificationToSubscribers($subject, $message, 'match');

        http_response_code(201); // Created
        echo json_encode(['message' => 'Match added successfully.', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add match to the database.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid match ID']);
        return;
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['home_team']) || empty($data['away_team']) || empty($data['match_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: Home Team, Away Team, and Date.']);
        return;
    }

    $home_team = $data['home_team'];
    $away_team = $data['away_team'];
    $competition = $data['competition'] ?? '';
    $match_date = $data['match_date'];
    $status = $data['status'] ?? 'scheduled';
    $home_score = isset($data['home_score']) && $data['home_score'] !== '' ? $data['home_score'] : null;
    $away_score = isset($data['away_score']) && $data['away_score'] !== '' ? $data['away_score'] : null;

    $venue = !empty($data['venue']) ? $data['venue'] : 'TBA';
    $round = $data['round'] ?? '';
    $referee = $data['referee'] ?? '';
    $attendance = isset($data['attendance']) && $data['attendance'] !== '' ? $data['attendance'] : null;
    $preview_content = $data['match_report'] ?? ($data['preview_content'] ?? '');
    $lineups = null;
    if (!empty($data['home_lineup']) || !empty($data['away_lineup'])) {
        $lineups = json_encode([
            'home_lineup' => $data['home_lineup'] ?? null,
            'away_lineup' => $data['away_lineup'] ?? null,
        ]);
    } else {
        $lineups = $data['lineups'] ?? null;
    }
    $statistics = $data['statistics'] ?? null;
    $commentary = $data['commentary'] ?? null;
    $home_logo = $data['home_logo'] ?? null;
    $away_logo = $data['away_logo'] ?? null;

    $stmt = $conn->prepare("UPDATE matches SET home_team = ?, away_team = ?, home_logo = ?, away_logo = ?, competition = ?, match_date = ?, status = ?, home_score = ?, away_score = ?, venue = ?, `round` = ?, referee = ?, attendance = ?, preview_content = ?, lineups = ?, statistics = ?, commentary = ? WHERE id = ?");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }
    
    $stmt->bind_param(
        "sssssssssssssssssi",
        $home_team,
        $away_team,
        $home_logo,
        $away_logo,
        $competition,
        $match_date,
        $status,
        $home_score,
        $away_score,
        $venue,
        $round,
        $referee,
        $attendance,
        $preview_content,
        $lineups,
        $statistics,
        $commentary,
        $id
    );

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Match updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update match.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid match ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Match deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete match.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>