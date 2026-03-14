<?php
header("Content-Type: application/json; charset=UTF-8");

// Includes
@include_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/jwt_helper.php';
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
        $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $article = $result->fetch_assoc();
        if ($article) {
            echo json_encode($article);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Article with ID $id not found"]);
        }
    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
        $categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';

        $where = "";
        if ($categoryFilter !== 'all') {
            $where = "WHERE category = '" . $conn->real_escape_string($categoryFilter) . "'";
        }

        if ($page !== null || $limit !== null) {
            $page = $page ?? 1;
            $limit = $limit ?? 9;
            $offset = ($page - 1) * $limit;

            $countResult = $conn->query("SELECT COUNT(*) as total FROM news $where");
            $total = $countResult->fetch_assoc()['total'];

            $query = "SELECT id, title, summary, category, image_url, created_at FROM news $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);
            $articles = $result->fetch_all(MYSQLI_ASSOC);

            echo json_encode([
                'data' => $articles,
                'pagination' => [
                    'total' => (int)$total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => (int)ceil($total / $limit)
                ]
            ]);
        } else {
            // Backward compatibility
            $result = $conn->query("SELECT id, title, summary, category, image_url, created_at FROM news $where ORDER BY created_at DESC");
            $articles = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($articles);
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

    if (empty($data['title']) || empty($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required article data (title, content).']);
        return;
    }

    // Accept either `image` or `image_url` from client
    if (empty($data['image_url']) && !empty($data['image'])) {
        $data['image_url'] = $data['image'];
    }
    $data['image_url'] = !empty($data['image_url']) ? $data['image_url'] : null;
    $data['summary'] = isset($data['summary']) ? $data['summary'] : '';
    $data['category'] = isset($data['category']) ? $data['category'] : '';
    $data['status'] = isset($data['status']) ? $data['status'] : 'published';

    $stmt = $conn->prepare("INSERT INTO news (title, summary, content, category, image_url, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $data['title'], $data['summary'], $data['content'], $data['category'], $data['image_url'], $data['status']);
    
    if ($stmt->execute()) {
        update_site_timestamp($conn);
        
        // Send notification to subscribers
        require_once __DIR__ . '/../mailer.php';
        $subject = "New News: " . $data['title'];
        $message = "Check out our latest news: " . $data['title'] . ". <br>" . $data['summary'];
        sendNotificationToSubscribers($subject, $message, 'news');

        http_response_code(201);
        echo json_encode(['message' => 'Article added successfully', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add article']);
    }
    $stmt->close();
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid article ID']);
        return;
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }

    if (empty($data['title']) || empty($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required article data (title, content).']);
        return;
    }

    // Accept either `image` or `image_url` from client
    if (empty($data['image_url']) && !empty($data['image'])) {
        $data['image_url'] = $data['image'];
    }
    $data['image_url'] = !empty($data['image_url']) ? $data['image_url'] : null;
    $data['summary'] = isset($data['summary']) ? $data['summary'] : '';
    $data['category'] = isset($data['category']) ? $data['category'] : '';
    $data['status'] = isset($data['status']) ? $data['status'] : 'published';

    $stmt = $conn->prepare("UPDATE news SET title = ?, summary = ?, content = ?, category = ?, image_url = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $data['title'], $data['summary'], $data['content'], $data['category'], $data['image_url'], $data['status'], $id);
    
    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Article updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update article']);
    }
    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid article ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Article deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete article']);
    }
    $stmt->close();
}

if (isset($conn)) {
    $conn->close();
}
?>