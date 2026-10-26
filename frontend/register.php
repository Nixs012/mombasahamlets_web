<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mombasa Hamlets</title>
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="css/login-register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="form-container">
        <div class="form-box">
            <div class="form-header">
                <img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo">
                <h2>Create Account</h2>
            </div>
            <form id="register-form" class="form">
                <div class="form-group">
                    <label for="first-name"><i class="fas fa-id-card"></i> First Name</label>
                    <input type="text" id="first-name" required pattern="^[A-Za-z\s'\-]+$" title="First name should only contain letters, spaces, hyphens and apostrophes">
                    <div id="first-name-feedback" class="validation-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="last-name"><i class="fas fa-id-card"></i> Last Name</label>
                    <input type="text" id="last-name" required pattern="^[A-Za-z\s'\-]+$" title="Last name should only contain letters, spaces, hyphens and apostrophes">
                    <div id="last-name-feedback" class="validation-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" required>
                    <div id="email-feedback" class="validation-feedback"></div>
                    <div id="email-verification" class="email-verification">
                        <button type="button" id="verify-email-btn" disabled>Verify Email</button>
                        <span id="email-status" class="email-status"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" required minlength="3" maxlength="20">
                    <div id="username-feedback" class="validation-feedback"></div>
                    <div id="username-suggestions" class="username-suggestions">
                        <small><strong>Suggestions:</strong></small>
                        <div id="suggestions-list" class="suggestions-list"></div>
                    </div>
                </div>
                <div class="form-group password-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                        <button type="button" id="suggest-password-btn" class="suggest-password-link">(Suggest Strong Password)</button>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" required>
                        <button type="button" class="toggle-password" id="toggle-password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div id="password-feedback" class="validation-feedback"></div>
                    <div id="password-strength" class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <div class="strength-text" id="strength-text">Password Strength: Weak</div>
                        <ul class="requirements">
                            <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters</li>
                            <li id="req-upper"><i class="fas fa-circle"></i> At least one uppercase letter (A-Z)</li>
                            <li id="req-lower"><i class="fas fa-circle"></i> At least one lowercase letter (a-z)</li>
                            <li id="req-number"><i class="fas fa-circle"></i> At least one number (0-9)</li>
                            <li id="req-special"><i class="fas fa-circle"></i> At least one special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group password-group">
                    <label for="confirm-password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm-password" required>
                        <button type="button" class="toggle-password" id="toggle-confirm-password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div id="confirm-password-feedback" class="validation-feedback"></div>
                </div>
                <button type="submit" class="btn-submit" id="submit-btn">Register</button>
                <div id="error-message" class="error-message"></div>
                <p class="form-footer">Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>
    <script src="js/api-config.js"></script>
    <script src="js/validation-utils.js"></script>
    <script src="js/register.js"></script>
</body>
</html>

