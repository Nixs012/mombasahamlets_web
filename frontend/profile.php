<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Mombasa Hamlets</title>
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body data-page="profile">
    
    <div class="profile-container">
        <!-- Back to Home Link -->
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
        <a href="dashboard.php" class="back-link"><i class="fas fa-columns"></i> Back to Dashboard</a>

        <div class="profile-header-section">
            <h1 class="page-title">Profile</h1>
            <p class="page-subtitle">Manage your personal information and security settings.</p>
        </div>

        <div class="profile-card">
            <!-- Profile Picture Section -->
            <section class="form-section">
                <div class="section-header">
                    <h2>Profile picture</h2>
                    <span class="sub-text">PNG, JPEG under 150MB</span>
                </div>
                
                <div class="avatar-controls">
                    <img id="profile-avatar" src="images/logo1.jpeg" alt="Profile Picture" class="avatar-preview" onerror="this.src='https://via.placeholder.com/100x100/4361ee/ffffff?text=U';this.onerror=null;">
                    <div class="avatar-actions">
                        <button type="button" class="btn-upload" id="btn-trigger-upload">Upload new picture</button>
                        <button type="button" class="btn-delete" id="btn-delete-avatar">Delete</button>
                        <input type="file" id="avatar-upload" hidden accept="image/png, image/jpeg, image/jpg">
                    </div>
                </div>
            </section>

            <hr class="divider">

            <!-- Full Name Section -->
            <section class="form-section">
                <div class="section-header">
                    <h2>Full name</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first-name">First name</label>
                        <input type="text" id="first-name" placeholder="First name" pattern="^[A-Za-z\s'\-]+$" title="First name should only contain letters, spaces, hyphens and apostrophes">
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last name</label>
                        <input type="text" id="last-name" placeholder="Last name" pattern="^[A-Za-z\s'\-]+$" title="Last name should only contain letters, spaces, hyphens and apostrophes">
                    </div>
                </div>
            </section>

            <hr class="divider">

            <!-- Contact Section -->
            <section class="form-section">
                <div class="section-header">
                    <h2>Contact info</h2>
                    <span class="sub-text">Manage your contact details.</span>
                </div>
                
                <div class="form-group full-width">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <i class="far fa-envelope"></i>
                        <input type="email" id="email" placeholder="email@example.com">
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top: 15px;">
                    <label for="phone">Phone number</label>
                    <div class="input-with-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" placeholder="+254 700 000 000">
                    </div>
                </div>
            </section>

            <hr class="divider">

            <!-- Password Section -->
            <section class="form-section">
                <div class="section-header">
                    <h2>Password</h2>
                    <span class="sub-text">Modify your current password.</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="current-password">Current password</label>
                        <div class="input-with-icon right-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="current-password" placeholder="••••••••">
                            <i class="far fa-eye toggle-password"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-password">New password</label>
                        <div class="input-with-icon right-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new-password" placeholder="••••••••">
                            <i class="far fa-eye toggle-password"></i>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="divider">

            <!-- Actions -->
            <div class="form-actions">
                <div id="status-message" class="status-message"></div>
                <button type="button" class="btn-save" id="btn-save-changes">Save changes</button>
            </div>
        </div>
    </div>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/profile.js"></script>
</body>
</html>
