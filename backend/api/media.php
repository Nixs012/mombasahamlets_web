<?php
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
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM media WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            return;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $media = $result->fetch_assoc();
        if ($media) {
            echo json_encode($media);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Media with ID $id not found"]);
        }
        $stmt->close();
    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
        $categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';

        $conditions = [];
        if ($categoryFilter !== 'all') {
            if ($categoryFilter === 'photos') {
                $conditions[] = "type = 'photo'";
            } else if ($categoryFilter === 'videos') {
                $conditions[] = "type = 'video'";
            } else {
                $conditions[] = "category = '" . $conn->real_escape_string($categoryFilter) . "'";
            }
        }

        $where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

        if ($page !== null || $limit !== null) {
            $page = $page ?? 1;
            $limit = $limit ?? 8;
            $offset = ($page - 1) * $limit;

            $countResult = $conn->query("SELECT COUNT(*) as total FROM media $where");
            $total = $countResult->fetch_assoc()['total'];

            $query = "SELECT * FROM media $where ORDER BY (CASE WHEN type='photo' THEN 1 ELSE 2 END), created_at DESC LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);

            if ($result) {
                $media = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode([
                    'data' => $media,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'pages' => (int)ceil($total / $limit)
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve media from the database.']);
            }
        } else {
            // Backward compatibility
            $query = "SELECT * FROM media $where ORDER BY (CASE WHEN type='photo' THEN 1 ELSE 2 END), created_at DESC";
            $result = $conn->query($query);

            if ($result) {
                $media = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode($media);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve media from the database.']);
            }
        }
    }
}

function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }

    if (empty($data['title']) || empty($data['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: title and category.']);
        return;
    }

    $title = $data['title'];
    $description = $data['description'] ?? '';
    $category = $data['category'];
    $type = $data['type'] ?? 'photo';
    $media_date = $data['media_date'] ?? date('Y-m-d');
    $image_url = isset($data['image_url']) && $data['image_url'] !== '' && $data['image_url'] !== null 
        ? trim($data['image_url']) 
        : null;
    $video_url = isset($data['video_url']) && $data['video_url'] !== '' && $data['video_url'] !== null 
        ? trim($data['video_url']) 
        : null;

    // Normalize image_url - remove unnecessary prefixes
    if ($image_url) {
        $image_url = preg_replace('#^/mombasahamlets_web/#', '', $image_url);
        $image_url = preg_replace('#^/?frontend/#', '', $image_url);
        $image_url = ltrim($image_url, '/');
        if (empty($image_url)) {
            $image_url = null;
        }
    }

    $stmt = $conn->prepare("INSERT INTO media (title, description, category, type, media_date, image_url, video_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param("sssssss", $title, $description, $category, $type, $media_date, $image_url, $video_url);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(201);
        $new_id = $stmt->insert_id;
        echo json_encode(['message' => 'Media added successfully.', 'id' => $new_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add media to the database.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid media ID']);
        return;
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }

    if (empty($data['title']) || empty($data['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: title and category.']);
        return;
    }

    $title = $data['title'];
    $description = $data['description'] ?? '';
    $category = $data['category'];
    $type = $data['type'] ?? 'photo';
    $media_date = $data['media_date'] ?? date('Y-m-d');
    $image_url = isset($data['image_url']) && $data['image_url'] !== '' && $data['image_url'] !== null 
        ? trim($data['image_url']) 
        : null;
    $video_url = isset($data['video_url']) && $data['video_url'] !== '' && $data['video_url'] !== null 
        ? trim($data['video_url']) 
        : null;

    // Normalize image_url - remove unnecessary prefixes
    if ($image_url) {
        $image_url = preg_replace('#^/mombasahamlets_web/#', '', $image_url);
        $image_url = preg_replace('#^/?frontend/#', '', $image_url);
        $image_url = ltrim($image_url, '/');
        if (empty($image_url)) {
            $image_url = null;
        }
    }

    $stmt = $conn->prepare("UPDATE media SET title = ?, description = ?, category = ?, type = ?, media_date = ?, image_url = ?, video_url = ? WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param("sssssssi", $title, $description, $category, $type, $media_date, $image_url, $video_url, $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Media updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update media.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid media ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM media WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Media deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete media.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>

