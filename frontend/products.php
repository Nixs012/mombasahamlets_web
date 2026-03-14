<?php
header("Content-Type: application/json; charset=UTF-8");

// Attempt to include the database connection file.
@include_once __DIR__ . '/../db.php';

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
    // The frontend will handle filtering, so we send all products.
    // We order by created_at to make "Newest First" the default.
    $query = "SELECT * FROM products ORDER BY created_at DESC";
    
    $result = $conn->query($query);

    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
        // Convert numeric types to actual numbers for JavaScript
        foreach ($products as &$product) {
            $product['price'] = floatval($product['price']);
            if ($product['sale_price']) {
                $product['sale_price'] = floatval($product['sale_price']);
            }
        }
        echo json_encode($products);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Failed to retrieve products from the database.']);
    }
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
    $image_url = $data['image_url'] ?? null;
    $sizes = $data['sizes'] ?? '';
    $stock_quantity = isset($data['stock_quantity']) ? intval($data['stock_quantity']) : 0;

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, sale_price, category, image_url, sizes, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddsssi", $name, $description, $price, $sale_price, $category, $image_url, $sizes, $stock_quantity);

    if ($stmt->execute()) {
        http_response_code(201); // Created
        $new_id = $stmt->insert_id;
        echo json_encode(['message' => 'Product added successfully.', 'id' => $new_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add product to the database.', 'details' => $stmt->error]);
    }
}

$conn->close();
?>
