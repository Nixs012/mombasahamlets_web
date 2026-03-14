<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mombasa Hamlets</title>
    <link rel="stylesheet" href="css/login-register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .validation-feedback {
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .validation-feedback.show {
            display: block;
        }
        .validation-feedback.valid {
            color: #28a745;
        }
        .validation-feedback.invalid {
            color: #dc3545;
        }
        .validation-feedback.warning {
            color: #ffc107;
        }
        .password-strength {
            margin-top: 8px;
        }
        .strength-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
            background-color: #dc3545;
        }
        .strength-fill.weak {
            width: 25%;
            background-color: #dc3545;
        }
        .strength-fill.fair {
            width: 50%;
            background-color: #ffc107;
        }
        .strength-fill.good {
            width: 75%;
            background-color: #17a2b8;
        }
        .strength-fill.strong {
            width: 100%;
            background-color: #28a745;
        }
        .strength-text {
            font-size: 11px;
            font-weight: bold;
        }
        .strength-text.weak { color: #dc3545; }
        .strength-text.fair { color: #ffc107; }
        .strength-text.good { color: #17a2b8; }
        .strength-text.strong { color: #28a745; }
        .requirements {
            font-size: 11px;
            margin-top: 8px;
            list-style: none;
            padding: 0;
        }
        .requirements li {
            padding: 3px 0;
            margin: 3px 0;
        }
        .requirements li:before {
            content: "○ ";
            margin-right: 5px;
            color: #ccc;
        }
        .requirements li.met {
            color: #28a745;
        }
        .requirements li.met:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
        .email-verification {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        .email-verification button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .email-verification button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .email-verification button:hover:not(:disabled) {
            background-color: #0056b3;
        }
        .email-status {
            font-size: 12px;
            padding: 6px 0;
        }
        .username-suggestions {
            margin-top: 8px;
        }
        .suggestions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        .suggestion-btn {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s;
        }
        .suggestion-btn:hover {
            background-color: #e0e0e0;
            border-color: #007bff;
        }
        .form-group .input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            background: none;
            border: none;
            color: #666;
            font-size: 14px;
        }
        .form-group.password-group input {
            padding-right: 35px;
        }
    </style>
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
                    <div id="email-verification" class="email-verification" style="display: none;">
                        <button type="button" id="verify-email-btn" disabled>Verify Email</button>
                        <span id="email-status" class="email-status"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" required minlength="3" maxlength="20">
                    <div id="username-feedback" class="validation-feedback"></div>
                    <div id="username-suggestions" class="username-suggestions" style="display: none;">
                        <small><strong>Suggestions:</strong></small>
                        <div id="suggestions-list" class="suggestions-list"></div>
                    </div>
                </div>
                <div class="form-group password-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                        <button type="button" id="suggest-password-btn" style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 12px; margin-left: 5px;">(Suggest Strong Password)</button>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" required>
                        <button type="button" class="toggle-password" id="toggle-password" style="display: none;"><i class="fas fa-eye"></i></button>
                    </div>
                    <div id="password-feedback" class="validation-feedback"></div>
                    <div id="password-strength" class="password-strength" style="display: none;">
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
                        <button type="button" class="toggle-password" id="toggle-confirm-password" style="display: none;"><i class="fas fa-eye"></i></button>
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

