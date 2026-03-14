<?php
header("Content-Type: application/json; charset=UTF-8");

// Attempt to include the database connection file.
include_once __DIR__ . '/../db.php';
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
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

/**
 * Handles GET requests for players.
 * Fetches all players or a single player by ID.
 */
function handleGet($conn) {
    // Check if a specific player ID is requested
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if ($id <= 0) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Invalid player ID.']);
            return;
        }

        // Fetch a single player for the modal view
        $stmt = $conn->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();

        if ($player) {
            // Calculate age from date of birth
            if ($player['dob']) {
                $birthDate = new DateTime($player['dob']);
                $today = new DateTime('today');
                $player['age'] = $birthDate->diff($today)->y;
            } else {
                $player['age'] = null;
            }
            echo json_encode($player);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['error' => "Player with ID $id not found."]);
        }
        $stmt->close();

    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
        $positionFilter = isset($_GET['position']) ? $_GET['position'] : 'all';
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $conditions = [];
        if ($positionFilter !== 'all') {
            switch ($positionFilter) {
                case 'goalkeepers': $conditions[] = "position = 'Goalkeeper'"; break;
                case 'defenders': $conditions[] = "position = 'Defender'"; break;
                case 'midfielders': $conditions[] = "position = 'Midfielder'"; break;
                case 'forwards': $conditions[] = "position = 'Forward'"; break;
            }
        }
        if (!empty($search)) {
            $s = $conn->real_escape_string($search);
            $conditions[] = "(first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR position LIKE '%$s%' OR jersey_number LIKE '%$s%')";
        }

        $where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

        if ($page !== null || $limit !== null) {
            $page = $page ?? 1;
            $limit = $limit ?? 8;
            $offset = ($page - 1) * $limit;

            $countResult = $conn->query("SELECT COUNT(*) as total FROM players $where");
            $total = $countResult->fetch_assoc()['total'];

            $query = "SELECT * FROM players $where ORDER BY position, last_name, first_name LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);

            if ($result) {
                $players = $result->fetch_all(MYSQLI_ASSOC);
                foreach ($players as &$player) {
                    if ($player['dob']) {
                        $birthDate = new DateTime($player['dob']);
                        $today = new DateTime('today');
                        $player['age'] = $birthDate->diff($today)->y;
                    } else {
                        $player['age'] = null;
                    }
                }
                echo json_encode([
                    'data' => $players,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'pages' => (int)ceil($total / $limit)
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve players from the database.']);
            }
        } else {
            // Backward compatibility
            $query = "SELECT * FROM players $where ORDER BY position, last_name, first_name";
            $result = $conn->query($query);

            if ($result) {
                $players = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode($players);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve players from the database.']);
            }
        }
    }
}

/**
 * Handles POST requests (creating or updating a player).
 */
function handlePost($conn)
{
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received player data: " . print_r($data, true)); // DEBUG: Log data received by PHP

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['position'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: first_name, last_name, and position.']);
        return;
    }

    // Prepare data with defaults for optional fields
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $position = $data['position'];
    $jersey_number = isset($data['jersey_number']) && $data['jersey_number'] !== '' ? intval($data['jersey_number']) : null;
    
    // Normalize image_url - remove frontend/ prefix if present
    $image_url = $data['image_url'] ?? null;
    if ($image_url) {
        $image_url = trim($image_url);
        // Remove frontend/ prefix if present
        $image_url = preg_replace('/^frontend\//', '', $image_url);
        $image_url = preg_replace('/^\/?frontend\//', '', $image_url);
        // Remove leading slash
        $image_url = ltrim($image_url, '/');
        // If empty after cleaning, set to null
        if (empty($image_url)) {
            $image_url = null;
        }
    }
    
    $nationality = $data['nationality'] ?? null;
    $dob = $data['dob'] ?? null;
    $joined = $data['joined'] ?? null;
    $bio = $data['bio'] ?? '';
    
    // Statistics
    $appearances = intval($data['appearances'] ?? 0);
    $goals = intval($data['goals'] ?? 0);
    $assists = intval($data['assists'] ?? 0);
    $clean_sheets = intval($data['clean_sheets'] ?? 0);
    $saves = intval($data['saves'] ?? 0);

    // Check if an ID is present to determine if it's an update or insert
    $id = !empty($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    if ($id > 0) {
        // This is an UPDATE
        $stmt = $conn->prepare("UPDATE players SET first_name=?, last_name=?, position=?, jersey_number=?, image_url=?, nationality=?, dob=?, joined=?, bio=?, appearances=?, goals=?, assists=?, clean_sheets=?, saves=? WHERE id=?");
        
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
            return;
        }
        
        $stmt->bind_param("sssisssssiiiiii", $first_name, $last_name, $position, $jersey_number, $image_url, $nationality, $dob, $joined, $bio, $appearances, $goals, $assists, $clean_sheets, $saves, $id);
        $success_message = 'Player updated successfully.';
    } else {
        // This is an INSERT
        $stmt = $conn->prepare("INSERT INTO players (first_name, last_name, position, jersey_number, image_url, nationality, dob, joined, bio, appearances, goals, assists, clean_sheets, saves) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
            return;
        }
        
        $stmt->bind_param("sssisssssiiiii", $first_name, $last_name, $position, $jersey_number, $image_url, $nationality, $dob, $joined, $bio, $appearances, $goals, $assists, $clean_sheets, $saves);
        $success_message = 'Player added successfully.';
    }

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        if ($id === 0) {
            http_response_code(201); // Created
            echo json_encode(['message' => $success_message, 'id' => $stmt->insert_id]);
        } else {
            http_response_code(200); // OK
            echo json_encode(['message' => $success_message]);
        }
    } else {
        http_response_code(500);
        // Provide a more detailed error for debugging.
        echo json_encode(['error' => 'Failed to add player to the database.', 'details' => $stmt->error, 'errno' => $stmt->errno]);
    }

    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid player ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM players WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Player deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete player.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

if (isset($conn)) {
    $conn->close();
}
?>