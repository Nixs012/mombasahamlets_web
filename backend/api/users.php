<?php
header("Content-Type: application/json; charset=UTF-8");

// Centralized session configuration for security hardening
require_once __DIR__ . '/../includes/session_config.php';

// Includes
@include_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/jwt_helper.php';

// Check if the connection variable $conn exists and is valid
if (!isset($conn) || (is_object($conn) && $conn->connect_error)) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

/**
 * Validate password strength on the backend
 * Requirements:
 * - At least 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one number
 * - At least one special character
 */
function validatePasswordStrengthBackend($password) {
    if (strlen($password) < 8) {
        return [
            'valid' => false,
            'message' => 'Password must be at least 8 characters long'
        ];
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return [
            'valid' => false,
            'message' => 'Password must contain at least one uppercase letter'
        ];
    }

    if (!preg_match('/[a-z]/', $password)) {
        return [
            'valid' => false,
            'message' => 'Password must contain at least one lowercase letter'
        ];
    }

    if (!preg_match('/\d/', $password)) {
        return [
            'valid' => false,
            'message' => 'Password must contain at least one number'
        ];
    }

    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        return [
            'valid' => false,
            'message' => 'Password must contain at least one special character (!@#$%^&* etc.)'
        ];
    }

    return [
        'valid' => true,
        'message' => 'Password strength is valid'
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        handlePost($conn);
        break;
    case 'GET':
        handleGet($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

/**
 * Handles PUT requests for updating user profile
 */
function handlePut($conn) {
    // 1. Verify Authentication
    $token = get_bearer_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $decoded = verify_jwt($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        return;
    }

    $userId = intval($decoded['id']);

    // 2. Parse Input (PUT data comes as raw body)
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }

    // 3. Extract and Sanitize Fields
    $firstName = isset($data['first_name']) ? trim($data['first_name']) : null;
    $lastName = isset($data['last_name']) ? trim($data['last_name']) : null;
    $phone = isset($data['phone_number']) ? trim($data['phone_number']) : null;
    $email = isset($data['email']) ? trim($data['email']) : null;
    $profilePicture = isset($data['profile_picture']) ? trim($data['profile_picture']) : null;
    
    // Password Change Fields
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    // 4. Build Update Query
    $updates = [];
    $types = "";
    $params = [];

    if ($firstName !== null) { $updates[] = "first_name = ?"; $types .= "s"; $params[] = $firstName; }
    if ($lastName !== null) { $updates[] = "last_name = ?"; $types .= "s"; $params[] = $lastName; }
    if ($phone !== null) { $updates[] = "phone_number = ?"; $types .= "s"; $params[] = $phone; }
    if ($email !== null) { $updates[] = "email = ?"; $types .= "s"; $params[] = $email; } // Enabled email update
    if ($profilePicture !== null) { $updates[] = "profile_picture = ?"; $types .= "s"; $params[] = $profilePicture; }

    // 5. Handle Password Change (if requested)
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            http_response_code(400);
            echo json_encode(['error' => 'Current password is required to set a new password']);
            return;
        }

        // Verify old password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $currentUser = $res->fetch_assoc();
        $stmt->close();

        if (!$currentUser || !password_verify($currentPassword, $currentUser['password'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Incorrect current password']);
            return;
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $types .= "s";
        $params[] = $hashedNewPassword;
    }

    // 6. Execute Update
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['message' => 'No changes provided']);
        return;
    }

    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $params[] = $userId;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $conn->error]);
        return;
    }

    // Robust Binding using References (mysqli requirement)
    $bindParams = [];
    $bindParams[] = & $types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = & $params[$i];
    }
    
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    if ($stmt->execute()) {
        $stmt->close();
        
        // Fetch updated user to return
        $stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, phone_number, profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $updatedUser = $res->fetch_assoc();
        
        http_response_code(200);

        // Generate new token with updated user details
        $payload = [
            'id' => $updatedUser['id'],
            'username' => $updatedUser['username'],
            'first_name' => $updatedUser['first_name'],
            'last_name' => $updatedUser['last_name'],
            'email' => $updatedUser['email'],
            'profile_picture' => $updatedUser['profile_picture'],
            'exp' => time() + (7 * 24 * 60 * 60) // 7 days
        ];
        $newToken = generate_jwt($payload);

        echo json_encode([
            'message' => 'Profile updated successfully', 
            'user' => $updatedUser,
            'token' => $newToken 
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile', 'details' => $stmt->error]);
    }
    // $stmt might be closed already if execute failed? No.
    // However, if we closed it in the success block, we check before closing again or rely on PHP cleanup.
    // The previous close() is inside the if(true).
}

/**
 * Handles POST requests for user registration and login
 */
function handlePost($conn) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if ($data === null || !is_array($data)) {
        // Fallback to form-encoded data if JSON is missing/invalid
        $data = $_POST ?? [];
    }
    
    if (!isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Action parameter is required']);
        return;
    }
    
    if ($data['action'] === 'register') {
        handleRegister($conn, $data);
    } elseif ($data['action'] === 'login') {
        handleLogin($conn, $data);
    } elseif ($data['action'] === 'check_email') {
        handleCheckEmail($conn, $data);
    } elseif ($data['action'] === 'check_username') {
        handleCheckUsername($conn, $data);
    } elseif ($data['action'] === 'send_verification') {
        handleSendVerification($conn, $data);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Handles user registration
 */
function handleRegister($conn, $data) {
    // Normalize input
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');

    // Validate required fields
    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }

    // Validate username format and length
    if (strlen($username) < 3 || strlen($username) > 20) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be between 3 and 20 characters']);
        return;
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username can only contain letters, numbers, underscores, hyphens, and periods']);
        return;
    }

    // Validate password strength (backend validation - CRITICAL for security)
    $passwordStrength = validatePasswordStrengthBackend($password);
    if (!$passwordStrength['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $passwordStrength['message']]);
        return;
    }

    if ($firstName === '') {
        $firstName = $username;
    }
    if ($lastName === '') {
        $lastName = 'User';
    }
    if ($email === '') {
        // Generate a placeholder but unique-ish email to satisfy NOT NULL/UNIQUE
        $host = $_SERVER['SERVER_NAME'] ?? 'local';
        $email = sprintf('%s+%s@%s.local', preg_replace('/[^a-zA-Z0-9]/', '', $username), uniqid(), $host);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        return;
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        $stmt->close();
        return;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, username, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement', 'details' => $conn->error]);
        return;
    }

    $stmt->bind_param("sssss", $firstName, $lastName, $email, $username, $hashedPassword);
    
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'User registered successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to register user']);
    }
    
    $stmt->close();
}

/**
 * Handles user login
 */
function handleLogin($conn, $data) {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }
    
    // Get user from database
    $stmt = $conn->prepare("SELECT id, username, password, first_name, last_name, email, profile_picture FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        $stmt->close();
        return;
    }
    
    // Generate secure JWT
    $payload = [
        'id' => $user['id'],
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'profile_picture' => $user['profile_picture'] ?? null, // Ensure this column is selected in query
        'exp' => time() + (7 * 24 * 60 * 60) // 7 days
    ];
    
    $token = generate_jwt($payload);
    
    // Store user data in session for PHP-based pages (like dashboard.php)
    // Session is already started via session_config.php with secure settings
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'profile_picture' => $user['profile_picture'] ?? null
    ];
    
    http_response_code(200);
    echo json_encode([
        'message' => 'Login successful',
        'token' => $token,
        'user' => $_SESSION['user']
    ]);
    
    $stmt->close();
}

/**
 * Handles GET requests to verify token
 */
function handleGet($conn) {
    // Check Authorization header first (standard), then fallback to query param
    $token = get_bearer_token();
    if (!$token) {
        $token = $_GET['token'] ?? '';
    }
    
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token is required']);
        return;
    }
    
    $decoded = verify_jwt($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        return;
    }

    // Optional: Double check user still exists in DB
    $userId = intval($decoded['id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token data']);
        return;
    }

    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, email FROM users WHERE id = ?");
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement', 'details' => $conn->error]);
        return;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'User no longer exists']);
        return;
    }
    
    // Sync session for PHP-based pages
    // Session is already started via session_config.php with secure settings
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
    ];
    
    echo json_encode([
        'valid' => true,
        'user' => $user
    ]);
}

/**
 * Check if email is available
 */
function handleCheckEmail($conn, $data) {
    $email = trim($data['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email', 'available' => false]);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    echo json_encode([
        'available' => $result->num_rows === 0,
        'email' => $email
    ]);
}

/**
 * Check if username is available
 */
function handleCheckUsername($conn, $data) {
    $username = trim($data['username'] ?? '');

    if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid username', 'available' => false]);
        return;
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid username format', 'available' => false]);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    echo json_encode([
        'available' => $result->num_rows === 0,
        'username' => $username
    ]);
}

/**
 * Send verification email to user
 * Note: This is a placeholder. Implement actual email sending with your mail service
 */
function handleSendVerification($conn, $data) {
    $email = trim($data['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email', 'success' => false]);
        return;
    }

    // TODO: Implement actual email verification
    // For now, just return success
    // In production, you would:
    // 1. Generate a verification token
    // 2. Store it in database with expiration
    // 3. Send email with verification link

    echo json_encode([
        'success' => true,
        'message' => 'Verification email sent successfully',
        'email' => $email
    ]);

}

$conn->close();
?>

