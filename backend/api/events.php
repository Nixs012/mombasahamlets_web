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
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

/**
 * Handles GET requests to fetch all events or a single event by ID.
 */
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid event ID.']);
            return;
        }

        $stmt = $conn->prepare("SELECT *, (SELECT COUNT(*) FROM ticket_types WHERE event_id = events.id) as ticket_count FROM events WHERE id = ?");
        
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
            return;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();

        if ($event) {
            echo json_encode($event);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Event with ID $id not found."]);
        }
        $stmt->close();
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
            $limit = $limit ?? 8;
            $offset = ($page - 1) * $limit;

            $countResult = $conn->query("SELECT COUNT(*) as total FROM events $where");
            $total = $countResult->fetch_assoc()['total'];

            $query = "SELECT *, (SELECT COUNT(*) FROM ticket_types WHERE event_id = events.id) as ticket_count FROM events $where ORDER BY event_date DESC LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);

            if ($result) {
                $events = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode([
                    'data' => $events,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'pages' => (int)ceil($total / $limit)
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve events from the database.']);
            }
        } else {
            // Backward compatibility
            $query = "SELECT *, (SELECT COUNT(*) FROM ticket_types WHERE event_id = events.id) as ticket_count FROM events $where ORDER BY event_date DESC";
            $result = $conn->query($query);

            if ($result) {
                $events = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode($events);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve events from the database.']);
            }
        }
    }
}

/**
 * Handles POST requests to create a new event.
 */
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['title']) || empty($data['event_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: Title and Event Date.']);
        return;
    }

    $title = $data['title'];
    $description = $data['description'] ?? '';
    $event_date = $data['event_date'];
    $event_time = $data['event_time'] ?? null;
    $location = $data['location'] ?? null;
    $category = $data['category'] ?? 'general';
    $status = $data['status'] ?? 'scheduled';
    $image_url = $data['image_url'] ?? null;
    
    // Normalize image_url - remove frontend/ prefix if present
    if ($image_url) {
        $image_url = trim($image_url);
        $image_url = preg_replace('/^frontend\//', '', $image_url);
        $image_url = preg_replace('/^\/?frontend\//', '', $image_url);
        $image_url = ltrim($image_url, '/');
        if (empty($image_url)) {
            $image_url = null;
        }
    }

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, location, category, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }

    $stmt->bind_param("ssssssss", $title, $description, $event_date, $event_time, $location, $category, $status, $image_url);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        
        // Send notification to subscribers
        require_once __DIR__ . '/../mailer.php';
        $subject = "New Event: " . $title;
        $message = "Don't miss our upcoming event: " . $title . " on " . $event_date . ". <br>" . $description;
        sendNotificationToSubscribers($subject, $message, 'event');

        http_response_code(201); // Created
        echo json_encode(['message' => 'Event added successfully.', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add event to the database.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid event ID']);
        return;
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['title']) || empty($data['event_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: Title and Event Date.']);
        return;
    }

    $title = $data['title'];
    $description = $data['description'] ?? '';
    $event_date = $data['event_date'];
    $event_time = $data['event_time'] ?? null;
    $location = $data['location'] ?? null;
    $category = $data['category'] ?? 'general';
    $status = $data['status'] ?? 'scheduled';
    $image_url = $data['image_url'] ?? null;
    
    // Normalize image_url - remove frontend/ prefix if present
    if ($image_url) {
        $image_url = trim($image_url);
        $image_url = preg_replace('/^frontend\//', '', $image_url);
        $image_url = preg_replace('/^\/?frontend\//', '', $image_url);
        $image_url = ltrim($image_url, '/');
        if (empty($image_url)) {
            $image_url = null;
        }
    }

    $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, category = ?, status = ?, image_url = ? WHERE id = ?");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }
    
    $stmt->bind_param("ssssssssi", $title, $description, $event_date, $event_time, $location, $category, $status, $image_url, $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Event updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update event.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid event ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement.', 'details' => $conn->error]);
        return;
    }
    
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Event deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete event.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>
