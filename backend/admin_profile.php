<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();

$error = '';
$success = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for all POST requests
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token validation failed. Please try again.');
    }
    
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        
        if ($full_name === '' || $username === '') {
            $error = 'Full name and username are required.';
        } else {
            // Update in DB
            global $conn;
            $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, username = ? WHERE id = ?");
            $stmt->bind_param("ssi", $full_name, $username, $_SESSION['admin_user_id']);
            if ($stmt->execute()) {
                $_SESSION['admin_full_name'] = $full_name;
                $_SESSION['admin_username'] = $username;
                $success = 'Profile updated successfully.';
            } else {
                $error = 'Failed to update profile: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            global $conn;
            $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $updateStmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $new_hash, $_SESSION['admin_user_id']);
                if ($updateStmt->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Failed to change password.';
                }
                $updateStmt->close();
            } else {
                $error = 'Current password is incorrect.';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_avatar'])) {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $fileSize = $_FILES['avatar']['size'];
            $fileType = $_FILES['avatar']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = __DIR__ . '/../frontend/images/avatars/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profilePath = '/mombasahamlets_web/frontend/images/avatars/' . $newFileName;
                    global $conn;
                    $stmt = $conn->prepare("UPDATE admin_users SET profile_pic = ? WHERE id = ?");
                    $stmt->bind_param("si", $profilePath, $_SESSION['admin_user_id']);
                    if ($stmt->execute()) {
                        $_SESSION['admin_profile_pic'] = $profilePath;
                        $success = 'Avatar updated successfully.';
                        $admin['profile_pic'] = $profilePath;
                    } else {
                        $error = 'Database update failed.';
                    }
                    $stmt->close();
                } else {
                    $error = 'There was an error moving the uploaded file.';
                }
            } else {
                $error = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions);
            }
        } else {
            $error = 'No file uploaded or upload error.';
        }
    }
}

// Get current user data
global $conn;
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_user_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Mombasa Hamlets</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
        }
        .profile-form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .large-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid var(--primary);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-save {
            background: #4361ee;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        .btn-save:hover {
            background: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .tab-nav {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .tab-btn {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: -2px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <?php 
        $pageTitle = 'Admin Profile';
        include __DIR__ . '/includes/admin_header.php'; 
        ?>

        <section class="admin-section">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <div class="profile-card">
                    <img src="<?php echo htmlspecialchars(admin_profile_pic()); ?>" alt="Profile avatar" class="large-avatar" onerror="this.src='/mombasahamlets_web/frontend/images/29.jpg';">
                    <h3><?php echo htmlspecialchars($admin['username']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($admin['role']); ?></p>
                    <hr>
                    <p><small>Last login: <?php echo $admin['last_login'] ? date('M j, Y H:i', strtotime($admin['last_login'])) : 'Never'; ?></small></p>
                </div>

                <div class="profile-form-card">
                    <div class="tab-nav">
                        <div class="tab-btn active" onclick="showTab('edit-profile')">Personal Info</div>
                        <div class="tab-btn" onclick="showTab('security')">Security</div>
                        <div class="tab-btn" onclick="showTab('avatar-tab')">Avatar</div>
                    </div>

                    <div id="edit-profile" class="tab-content active">
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            </div>
                            <button type="submit" class="btn-save">Save Changes</button>
                        </form>
                    </div>

                    <div id="security" class="tab-content">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn-save">Save changes</button>
                        </form>
                    </div>

                    <div id="avatar-tab" class="tab-content">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="update_avatar" value="1">
                            <div class="form-group">
                                <label>Choose Profile Picture</label>
                                <input type="file" name="avatar" accept="image/*" required>
                            </div>
                            <button type="submit" class="btn-save">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    </div>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) selectedTab.classList.add('active');
            
            const btn = event.currentTarget;
            btn.classList.add('active');
            
            // Scroll tab button into view on mobile
            if (window.innerWidth <= 1024) {
                btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        }

        // Initialize sidebar toggle (for desktop/mobile consistency)
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
