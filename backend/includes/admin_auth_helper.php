<?php
/**
 * admin_auth_helper.php
 * Centralized authentication for administrative API actions.
 */

// Use centralized session configuration for security hardening
require_once __DIR__ . '/session_config.php';

require_once __DIR__ . '/jwt_helper.php';

/**
 * Checks if the current requester is an authorized administrator.
 * Supports both Session (for existing admin panel) and JWT (for API clients).
 * 
 * @return bool
 */
function is_admin_authorized() {
    // 1. Check Session (used by the existing PHP-based admin panel)
    if (isset($_SESSION['admin_user_id'])) {
        return true;
    }

    // 2. Check JWT (useful for future-proofing or cross-domain API calls)
    $token = get_bearer_token();
    if ($token) {
        $decoded = verify_jwt($token);
        
        // Verify that the token contains an ID and that ID exists in admin_users table
        if ($decoded && isset($decoded['id'])) {
            global $conn;
            
            // Ensure database connection is available
            if (!isset($conn)) {
                require_once __DIR__ . '/../db.php';
            }
            
            // Verify that the decoded user ID exists in the admin_users table
            $stmt = $conn->prepare("SELECT id FROM admin_users WHERE id = ? LIMIT 1");
            if (!$stmt) {
                error_log("Failed to prepare admin check statement: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param('i', $decoded['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $isAdmin = $result->num_rows === 1;
            $stmt->close();
            
            return $isAdmin;
        }
    }

    return false;
}

/**
 * Enforces admin authentication for sensitive API methods.
 * Sends a 401 Unauthorized response and exits if not authorized.
 */
function require_admin_auth() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Always allow GET for public visibility (unless a specific API should be private)
    if ($method === 'GET') {
        return;
    }

    if (!is_admin_authorized()) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => 'Administrative privileges are required for this action.'
        ]);
        exit();
    }
}
