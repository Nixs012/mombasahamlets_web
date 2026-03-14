<?php
/**
 * sync_cart.php
 * Bridges the frontend (localStorage + JWT) with the backend session.
 * This is called before redirecting to checkout.php.
 */
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/session_config.php';

require_once __DIR__ . '/../includes/jwt_helper.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data. Cart is required.']);
    exit();
}

$cart = $data['cart'];
$token = $data['token'] ?? '';

// 1. Sync Cart
$_SESSION['cart'] = $cart;

// 2. Sync User Info (if token provided)
if (!empty($token)) {
    $decoded = verify_jwt($token);
    if ($decoded) {
        $_SESSION['user'] = $decoded;
    } else {
        // Token provided but invalid - clear user session to be safe
        unset($_SESSION['user']);
    }
} else {
    // No token provided - guest or not logged in
    unset($_SESSION['user']);
}

echo json_encode([
    'success' => true,
    'message' => 'Cart and session synchronized.',
    'item_count' => count($cart)
]);
