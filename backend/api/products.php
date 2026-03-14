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
 * Handles GET requests for products.
 * Fetches all products. The frontend will handle filtering and sorting.
 */
function handleGet($conn) {
    // If a limit is passed, use pagination. Otherwise, fetch all products for the admin panel.
    if (isset($_GET['limit'])) {
        $limit = intval($_GET['limit']);
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        $query = "SELECT * FROM shop ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // No limit provided, fetch all products (for the admin panel)
        $query = "SELECT * FROM shop ORDER BY id DESC";
        $result = $conn->query($query); // This is line 37
    }


    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
        // Convert numeric types to actual numbers for JavaScript
        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            if (isset($product['sale_price']) && $product['sale_price'] !== null) {
                $product['sale_price'] = floatval($product['sale_price']);
            }
        }
        echo json_encode($products);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Failed to retrieve products from the database.']);
    }

    // Close statement
    if (isset($stmt)) $stmt->close(); // Only close if it was created

}

/**
 * Handles POST requests to create a new product.
 */
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['name']) || !isset($data['price']) || empty($data['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, price, and category.']);
        return;
    }

    // Prepare data with defaults for optional fields
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $price = floatval($data['price']);
    $sale_price = isset($data['sale_price']) && $data['sale_price'] !== '' ? floatval($data['sale_price']) : null;
    $category = $data['category'];
    // Handle image_url - can be null, empty string, or a valid path
    $image_url = isset($data['image_url']) && $data['image_url'] !== '' && $data['image_url'] !== null 
        ? trim($data['image_url']) 
        : null;
    $sizes = $data['sizes'] ?? '';
    $stock_quantity = isset($data['stock_quantity']) ? intval($data['stock_quantity']) : 0;

    $stmt = $conn->prepare("INSERT INTO shop (name, description, price, sale_price, category, image_url, sizes, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddsssi", $name, $description, $price, $sale_price, $category, $image_url, $sizes, $stock_quantity);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        
        // Send notification to subscribers
        require_once __DIR__ . '/../mailer.php';
        $subject = "New Product: " . $name;
        $message = "New arrival in our shop: " . $name . " for only KES " . $price . ". <br>" . $description;
        sendNotificationToSubscribers($subject, $message, 'product');

        http_response_code(201); // Created
        $new_id = $stmt->insert_id;
        echo json_encode(['message' => 'Product added successfully.', 'id' => $new_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add product to the database.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID']);
        return;
    }

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data provided.']);
        return;
    }

    // Basic validation
    if (empty($data['name']) || !isset($data['price']) || empty($data['category'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, price, and category.']);
        return;
    }

    // Prepare data with defaults for optional fields
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $price = floatval($data['price']);
    $sale_price = isset($data['sale_price']) && $data['sale_price'] !== '' ? floatval($data['sale_price']) : null;
    $category = $data['category'];
    // Handle image_url - can be null, empty string, or a valid path
    $image_url = isset($data['image_url']) && $data['image_url'] !== '' && $data['image_url'] !== null 
        ? trim($data['image_url']) 
        : null;
    $sizes = $data['sizes'] ?? '';
    $stock_quantity = isset($data['stock_quantity']) ? intval($data['stock_quantity']) : 0;

    $stmt = $conn->prepare("UPDATE shop SET name = ?, description = ?, price = ?, sale_price = ?, category = ?, image_url = ?, sizes = ?, stock_quantity = ? WHERE id = ?");
    $stmt->bind_param("ssddsssii", $name, $description, $price, $sale_price, $category, $image_url, $sizes, $stock_quantity, $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Product updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update product.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM shop WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        update_site_timestamp($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Product deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete product.', 'details' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>