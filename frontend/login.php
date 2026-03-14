<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mombasa Hamlets</title>
    <link rel="stylesheet" href="css/login-register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="form-container">
        <div class="form-box">
            <div class="form-header">
                <img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo">
                <h2>User Login</h2>
            </div>
            <form id="login-form" class="form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" required>
                </div>
                <button type="submit" class="btn-submit">Login</button>
                <div id="error-message" class="error-message"></div>
                <p class="form-footer">Don't have an account? <a href="register.php">Register here</a></p>
            </form>
        </div>
    </div>
    <script src="js/api-config.js"></script>
    <script type="module" src="js/login.js"></script>
</body>
</html>

