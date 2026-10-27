<?php
require_once __DIR__ . '/../config/asset_helper.php';
$pageTitle = 'Login - Mombasa Hamlets FC';
$pageDescription = 'Login to your Mombasa Hamlets FC account. Access your dashboard, tickets, and order history.';
$pageImage = 'images/logo1.webp';
$extraCss = ['css/login-register.css'];
include __DIR__ . '/includes/meta_head.php';
?>
<body data-page="login">
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
                <div class="form-group password-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" required>
                        <button type="button" class="toggle-password" id="toggle-password"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                 <button type="submit" class="btn-submit">Login</button>
                 <div id="error-message" class="error-message"></div>
                 <p class="form-footer">Don't have an account? <a href="register.php">Register here</a></p>
             </form>
         </div>
     </div>
     <script src="js/api-config.js"></script>
     <script src="js/login.js"></script>
 </body>
</html>

