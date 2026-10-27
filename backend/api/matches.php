<?php
// This file is now the authoritative API endpoint for matches.
header("Content-Type: application/json; charset=UTF-8");

// Include the database connection file.
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/site_helper.php';
require_once __DIR__ . '/../includes/admin_auth_helper.php';
require_once __DIR__ . '/../includes/file_upload_helper.php';

// Enforce admin authentication for POST, PUT, DELETE
require_admin_auth();

// Upload directory for match team logos
define('MATCH_LOGO_UPLOAD_DIR', __DIR__ . '/../../frontend/images/match-logos/');

/**
 * Handles file upload for match team logos.
 * Validates file type and size, stores safely.
 *
 * @param string $inputName The file input field name ('home_team_logo_file' or 'away_team_logo_file')
 * @return array ['path' => relative_path|null, 'error' => message|null]
 */
function handleLogoUpload($inputName) {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    $file = $_FILES[$inputName];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'File upload failed with error code ' . $file['error']];
    }

    // Validate file type (only allow PNG, JPG, JPEG, SVG, GIF)
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return ['path' => null, 'error' => 'Invalid file type. Only PNG, JPG, JPEG, SVG, GIF allowed.'];
    }

    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        return ['path' => null, 'error' => 'File too large. Maximum size is 2MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'match_logo_' . uniqid() . '.' . strtolower($extension);

    // Create upload directory if it doesn't exist
    if (!is_dir(MATCH_LOGO_UPLOAD_DIR)) {
        mkdir(MATCH_LOGO_UPLOAD_DIR, 0755, true);
    }

    // Generate safe path
    $uploadPath = MATCH_LOGO_UPLOAD_DIR . $filename;
    $relativePath = 'images/match-logos/' . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['path' => $relativePath, 'error' => null];
    }

    return ['path' => null, 'error' => 'Failed to move uploaded file.'];
}

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
            // Normalize: ensure home_team_logo and away_team_logo are always present
            // Fall back to legacy home_logo/away_logo columns
            if (empty($match['home_team_logo']) && !empty($match['home_logo'])) {
                $match['home_team_logo'] = $match['home_logo'];
            }
            if (empty($match['away_team_logo']) && !empty($match['away_logo'])) {
                $match['away_team_logo'] = $match['away_logo'];
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

        $whereClause = $where;
        unset($where);

        if ($page !== null || $limit !== null) {
            $page = $page ?? 1;
            $limit = $limit ?? 8;
            $offset = ($page - 1) * $limit;

            $countResult = $conn->query("SELECT COUNT(*) as total FROM matches $whereClause");
            $total = $countResult->fetch_assoc()['total'];

            // Sort logic matching frontend: live first, then upcoming (soonest), then finished (recent)
            $limitInt = (int)$limit;
            $offsetInt = (int)$offset;
            $query = "SELECT * FROM matches $whereClause ORDER BY 
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
                LIMIT $limitInt OFFSET $offsetInt";
            
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
                    // Normalize: ensure home_team_logo and away_team_logo are always present
                    if (empty($match['home_team_logo']) && !empty($match['home_logo'])) {
                        $match['home_team_logo'] = $match['home_logo'];
                    }
                    if (empty($match['away_team_logo']) && !empty($match['away_logo'])) {
                        $match['away_team_logo'] = $match['away_logo'];
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
            $query = "SELECT * FROM matches $whereClause ORDER BY match_date ASC";
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
                    // Normalize: ensure home_team_logo and away_team_logo are always present
                    if (empty($match['home_team_logo']) && !empty($match['home_logo'])) {
                        $match['home_team_logo'] = $match['home_logo'];
                    }
                    if (empty($match['away_team_logo']) && !empty($match['away_logo'])) {
                        $match['away_team_logo'] = $match['away_logo'];
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
    // Handle both JSON and multipart form data (for file uploads)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data provided.']);
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
    
    // Handle team logo uploads
    $homeTeamLogo = $data['home_team_logo'] ?? $data['home_logo'] ?? null;
    $awayTeamLogo = $data['away_team_logo'] ?? $data['away_logo'] ?? null;

    // Process file uploads if present
    if (isset($_FILES['home_team_logo_file'])) {
        $uploadResult = handleLogoUpload('home_team_logo_file');
        if ($uploadResult['error']) {
            http_response_code(400);
            echo json_encode(['error' => $uploadResult['error']]);
            return;
        }
        if ($uploadResult['path']) {
            $homeTeamLogo = $uploadResult['path'];
        }
    }
    if (isset($_FILES['away_team_logo_file'])) {
        $uploadResult = handleLogoUpload('away_team_logo_file');
        if ($uploadResult['error']) {
            http_response_code(400);
            echo json_encode(['error' => $uploadResult['error']]);
            return;
        }
        if ($uploadResult['path']) {
            $awayTeamLogo = $uploadResult['path'];
        }
    }

    $stmt = $conn->prepare("INSERT INTO matches (home_team, away_team, home_team_logo, away_team_logo, home_logo, away_logo, competition, match_date, status, home_score, away_score, venue, `round`, referee, attendance, preview_content, lineups, statistics, commentary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }

    $stmt->bind_param(
        "sssssssssssssssssssss",
        $home_team,
        $away_team,
        $homeTeamLogo,
        $awayTeamLogo,
        $homeTeamLogo, // Also populate legacy home_logo column
        $awayTeamLogo, // Also populate legacy away_logo column
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
    // Handle both JSON and multipart form data (for file uploads)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
    }
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid match ID']);
        return;
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data provided.']);
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
    $homeLogo = $data['home_team_logo'] ?? $data['home_logo'] ?? null;
    $awayLogo = $data['away_team_logo'] ?? $data['away_logo'] ?? null;

    // Process file uploads if present (update mode)
    if (isset($_FILES['home_team_logo_file'])) {
        $uploadResult = handleLogoUpload('home_team_logo_file');
        if ($uploadResult['error']) {
            http_response_code(400);
            echo json_encode(['error' => $uploadResult['error']]);
            return;
        }
        if ($uploadResult['path']) {
            $homeLogo = $uploadResult['path'];
        }
    }
    if (isset($_FILES['away_team_logo_file'])) {
        $uploadResult = handleLogoUpload('away_team_logo_file');
        if ($uploadResult['error']) {
            http_response_code(400);
            echo json_encode(['error' => $uploadResult['error']]);
            return;
        }
        if ($uploadResult['path']) {
            $awayLogo = $uploadResult['path'];
        }
    }

    $stmt = $conn->prepare("UPDATE matches SET home_team = ?, away_team = ?, home_team_logo = ?, away_team_logo = ?, home_logo = ?, away_logo = ?, competition = ?, match_date = ?, status = ?, home_score = ?, away_score = ?, venue = ?, `round` = ?, referee = ?, attendance = ?, preview_content = ?, lineups = ?, statistics = ?, commentary = ? WHERE id = ?");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }

    $stmt->bind_param(
        "sssssssssssssssssssi",
        $home_team,
        $away_team,
        $homeLogo,
        $awayLogo,
        $homeLogo,
        $awayLogo,
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