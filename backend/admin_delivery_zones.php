<?php
/**
 * admin_delivery_zones.php
 * Admin page for managing delivery zones.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/delivery_zone_helper.php';

// Secure the page
admin_require_login();
$adminUser = admin_username() ?? 'Admin User';

$pageTitle = 'Delivery Zones';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token validation failed.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_zone' || $action === 'update_zone') {
        $name = trim($_POST['name'] ?? '');
        $fee = floatval($_POST['fee'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $message = 'Zone name is required.';
            $messageType = 'error';
        } else {
            // Check if delivery_zones table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'delivery_zones'");
            if ($tableCheck && $tableCheck->num_rows === 0) {
                $message = 'Delivery zones table does not exist. Please run the migration.';
                $messageType = 'error';
            } else {
                if ($action === 'add_zone') {
                    // INSERT
                    $checkStmt = $conn->prepare("SELECT id FROM delivery_zones WHERE name = ?");
                    $checkStmt->bind_param("s", $name);
                    $checkStmt->execute();
                    if ($checkStmt->get_result()->num_rows > 0) {
                        $message = 'A delivery zone with this name already exists.';
                        $messageType = 'error';
                    } else {
                        $stmt = $conn->prepare("INSERT INTO delivery_zones (name, fee, description, active) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("sdsi", $name, $fee, $description, $active);
                        if ($stmt->execute()) {
                            $message = 'Delivery zone added successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to add delivery zone: ' . $conn->error;
                            $messageType = 'error';
                        }
                        $stmt->close();
                    }
                    $checkStmt->close();
                } else {
                    // UPDATE
                    $id = intval($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        $message = 'Invalid zone ID.';
                        $messageType = 'error';
                    } else {
                        $stmt = $conn->prepare("UPDATE delivery_zones SET name = ?, fee = ?, description = ?, active = ? WHERE id = ?");
                        $stmt->bind_param("sdsii", $name, $fee, $description, $active, $id);
                        if ($stmt->execute()) {
                            $message = 'Delivery zone updated successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update delivery zone: ' . $conn->error;
                            $messageType = 'error';
                        }
                        $stmt->close();
                    }
                }
            }
        }
    } elseif ($action === 'delete_zone') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM delivery_zones WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Delivery zone deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete delivery zone: ' . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'toggle_active') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE delivery_zones SET active = !active WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Zone status updated.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update zone status.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Fetch all delivery zones
$zones = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'delivery_zones'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id, name, fee, description, active, created_at FROM delivery_zones ORDER BY fee ASC, name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $zones[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Zones - Admin Panel</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .zone-form-container { background: var(--off-white, #f8f9fa); border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .zone-form h3 { margin-top: 0; color: var(--dark-navy, #0A192F); }
        .zone-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .zone-form-row .form-group { margin-bottom: 0; }
        .form-row-full { grid-column: 1 / -1; }
        .zone-table { width: 100%; border-collapse: collapse; }
        .zone-table th, .zone-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color, #dee2e6); }
        .zone-table th { background: var(--dark-navy, #0A192F); color: white; font-weight: 600; }
        .zone-table tbody tr:hover { background: var(--off-white, #f8f9fa); }
        .status-toggle { cursor: pointer; }
        .status-yes { color: var(--kit-green, #006600); font-weight: 700; }
        .status-no { color: var(--red, #DC3545); font-weight: 700; }
        .fee-badge { background: var(--primary-blue, #003366); color: white; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/includes/admin_header.php'; ?>

            <div class="content-wrapper">
                <?php if (!empty($message)): ?>
                    <div style="background: <?php echo $messageType === 'success' ? '#e7f9f7' : '#fdf2f2'; ?>; color: <?php echo $messageType === 'success' ? '#2a9d8f' : '#DC3545'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Add/Edit Zone Form -->
                <div class="zone-form-container">
                    <h2>Delivery Zones</h2>
                    <p>Manage your delivery zones and their associated fees.</p>
                    
                    <!-- Note about table existence -->
                    <?php if (empty($zones)): ?>
                        <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            The delivery_zones table doesn't exist yet. Please <strong>run migration_20261027_delivery_zones.sql</strong> to create the table.
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="zone-form" style="margin-top: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="add_zone">
                        <div class="form-group form-row-full">
                            <label for="name">Zone Name *</label>
                            <input type="text" id="name" name="name" required placeholder="e.g. Mombasa Island, Outside Mombasa" style="width: 100%;">
                        </div>
                        <div class="zone-form-row">
                            <div class="form-group">
                                <label for="fee">Delivery Fee (KSh) *</label>
                                <input type="number" id="fee" name="fee" required min="0" step="0.01" placeholder="150.00">
                            </div>
                            <div class="form-group">
                                <label for="active">Status</label>
                                <select id="active" name="active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group form-row-full">
                            <label for="description">Description (Optional)</label>
                            <textarea id="description" name="description" rows="3" placeholder="Description of this delivery zone" style="width: 100%;"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn" style="background: var(--primary-blue, #003366); color: white;">Add Delivery Zone</button>
                        </div>
                    </form>
                </div>

                <!-- Existing Zones Table -->
                <?php if (!empty($zones)): ?>
                <div class="card">
                    <h2>Existing Delivery Zones</h2>
                    <table class="zone-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Fee</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($zones as $zone): ?>
                            <tr>
                                <td><?php echo $zone['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($zone['name']); ?></strong></td>
                                <td><span class="fee-badge">KSh <?php echo number_format($zone['fee'], 2); ?></span></td>
                                <td><?php echo htmlspecialchars($zone['description'] ?? '-'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?php echo $zone['id']; ?>">
                                        <button type="submit" class="status-toggle" title="Toggle Active">
                                            <i class="fas <?php echo $zone['active'] ? 'fa-toggle-on status-yes' : 'fa-toggle-off status-no'; ?>"></i>
                                            <?php echo $zone['active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($zone['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="admin_delivery_zone_edit.php?id=<?php echo $zone['id']; ?>" class="btn-action btn-edit" title="Edit Zone">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this zone?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="delete_zone">
                                            <input type="hidden" name="id" value="<?php echo $zone['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete" title="Delete Zone">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
