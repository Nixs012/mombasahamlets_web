<?php
declare(strict_types=1);

// Use centralized session configuration for security hardening
require_once __DIR__ . '/session_config.php';

// Ensure database connection is available globally
require_once __DIR__ . '/../db.php';
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
}

/**
 * Update this list with the usernames and password hashes you want to allow.
 * This is deprecated in favor of database-backed authentication.
 */
// const ADMIN_USERS = [...];

function admin_is_logged_in(): bool
{
    return isset($_SESSION['admin_user_id']);
}

function admin_username(): ?string
{
    return $_SESSION['admin_username'] ?? null;
}

function admin_full_name(): ?string
{
    return $_SESSION['admin_full_name'] ?? null;
}

function admin_role(): ?string
{
    return $_SESSION['admin_role'] ?? null;
}

function admin_profile_pic(): string
{
    return $_SESSION['admin_profile_pic'] ?? '/mombasahamlets_web/frontend/images/29.jpg';
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function admin_attempt_login(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    global $conn;
    
    if (!isset($conn)) {
        error_log("Database connection not initialized in admin_attempt_login");
        return false;
    }

    try {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role, profile_pic FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['admin_user_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_profile_pic'] = $admin['profile_pic'] ?: '/mombasahamlets_web/frontend/images/29.jpg';
                $_SESSION['admin_login_time'] = time();

                // Update last login
                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $admin['id']);
                $updateStmt->execute();
                $updateStmt->close();

                return true;
            } else {
                error_log("Password mismatch for user: " . $username);
            }
        } else {
            error_log("User not found or multiple users found for: " . $username . " (Rows: " . $result->num_rows . ")");
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
    }

    return false;
}

function admin_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Generate a CSRF token and store it in the session
 * Should be called before rendering any form that performs sensitive operations
 * 
 * @return string The CSRF token to include in the form
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token against the one stored in the session
 * Uses hash_equals() for timing-safe comparison
 * 
 * @param string $token The CSRF token to verify
 * @return bool True if the token is valid, false otherwise
 */
function verify_csrf_token(string $token): bool {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

?>
