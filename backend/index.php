<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_is_logged_in()) {
    header('Location: admin.php');
    exit;
}

$error = '';
$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token invalid');
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $usernameValue = $username;

    if (admin_attempt_login($username, $password)) {
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mombasa Hamlets</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="/mombasahamlets_web/frontend/images/29.jpg" alt="Mombasa Hamlets Logo" onerror="this.src='https://via.placeholder.com/80x80/4361ee/white?text=MH'; this.onerror=null;">
                <h2>Admin Login</h2>
                <p>Enter your credentials to access the dashboard.</p>
            </div>
            <form method="POST" class="login-form" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usernameValue); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                    <div class="error-message"></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>

