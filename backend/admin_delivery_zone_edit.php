<?php
/**
 * admin_delivery_zone_edit.php
 * Admin page for editing a single delivery zone.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

// Secure the page
admin_require_login();
$pageTitle = 'Edit Delivery Zone';

$zoneId = intval($_GET['id'] ?? 0);
if ($zoneId <= 0) {
    die("Error: Invalid Zone ID.");
}

// Fetch the zone
$stmt = $conn->prepare("SELECT id, name, fee, description, active FROM delivery_zones WHERE id = ?");
$stmt->bind_param("i", $zoneId);
$stmt->execute();
$result = $stmt->get_result();
$zone = $result->fetch_assoc();
$stmt->close();

if (!$zone) {
    die("Error: Delivery zone not found.");
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token validation failed.');
    }

    $name = trim($_POST['name'] ?? '');
    $fee = floatval($_POST['fee'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if ($name === '') {
        $message = 'Zone name is required.';
        $messageType = 'error';
    } else {
        // Check for duplicate name (excluding this zone)
        $checkStmt = $conn->prepare("SELECT id FROM delivery_zones WHERE name = ? AND id != ?");
        $checkStmt->bind_param("si", $name, $zoneId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $message = 'Another delivery zone with this name already exists.';
            $messageType = 'error';
        } else {
            $updateStmt = $conn->prepare("UPDATE delivery_zones SET name = ?, fee = ?, description = ?, active = ? WHERE id = ?");
            $updateStmt->bind_param("sdsii", $name, $fee, $description, $active, $zoneId);
            if ($updateStmt->execute()) {
                $message = 'Delivery zone updated successfully.';
                $messageType = 'success';
                
                // Refresh zone data
                $stmt = $conn->prepare("SELECT id, name, fee, description, active FROM delivery_zones WHERE id = ?");
                $stmt->bind_param("i", $zoneId);
                $stmt->execute();
                $result = $stmt->get_result();
                $zone = $result->fetch_assoc();
                $stmt->close();
            } else {
                $message = 'Failed to update delivery zone: ' . $conn->error;
                $messageType = 'error';
            }
            $updateStmt->close();
        }
        $checkStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Delivery Zone - Admin Panel</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/includes/admin_header.php'; ?>

            <div class="content-wrapper">
                <div class="card">
                    <h2>Edit Delivery Zone: <?php echo htmlspecialchars($zone['name']); ?></h2>
                    
                    <?php if (!empty($message)): ?>
                        <div style="background: <?php echo $messageType === 'success' ? '#e7f9f7' : '#fdf2f2'; ?>; color: <?php echo $messageType === 'success' ? '#2a9d8f' : '#DC3545'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="admin-form" style="max-width: 600px;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="form-group">
                            <label for="name">Zone Name *</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($zone['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="fee">Delivery Fee (KSh) *</label>
                            <input type="number" id="fee" name="fee" required min="0" step="0.01" value="<?php echo htmlspecialchars($zone['fee']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="active">Status</label>
                            <select id="active" name="active">
                                <option value="1" <?php echo $zone['active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo !$zone['active'] ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($zone['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn">Update Delivery Zone</button>
                            <a href="admin_delivery_zones.php" class="btn btn-cancel">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
