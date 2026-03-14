<?php
/**
 * admin_order_update.php
 * Form to update the status of an order.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

// Secure the page
admin_require_login();
$adminUser = admin_username() ?? 'Admin User';

// 1. Get order_id from GET or POST parameters
$orderId = 0;
if (isset($_REQUEST['order_id'])) {
    $orderId = (int)$_REQUEST['order_id'];
} elseif (isset($_REQUEST['id'])) {
    $orderId = (int)$_REQUEST['id'];
}

$success = false;
$error = '';

if ($orderId <= 0) {
    die("Error: Invalid Order ID.");
}

// 2. Fetch current order status to pre-populate the dropdown
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Error: Order not found.");
}

// 3. Handle status update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token validation failed. Please try again.');
    }
    $newStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['pending', 'paid', 'completed', 'cancelled'];

    if (in_array($newStatus, $allowedStatuses)) {
        // Prepare SQL to update order status
        $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $orderId);
        if ($updateStmt->execute()) {
            // 4. If status is 'paid' or 'completed', generate tickets if they don't exist
            if ($newStatus === 'paid' || $newStatus === 'completed') {
                require_once __DIR__ . '/includes/ticket_helper.php';
                TicketHelper::generateTicketsForOrder($orderId, $conn);
            }

            // 5. Redirect back to admin_orders.php on success
            header("Location: admin_orders.php?update=success");
            exit();
        } else {
            $error = "Failed to update status: " . $conn->error;
        }
        $updateStmt->close();
    } else {
        $error = "Invalid status selected.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order #<?php echo $orderId; ?> - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="/mombasahamlets_web/frontend/images/29.jpg" alt="Mombasa Hamlets Logo">
                <h2>Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="admin_orders.php" class="active"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
                    <li><a href="admin_payments.php"><i class="fas fa-credit-card"></i> <span>Payments</span></a></li>
                    <li><a href="admin.php#news" data-tab="news"><i class="fas fa-newspaper"></i> <span>News</span></a></li>
                    <li><a href="admin.php#matches" data-tab="matches"><i class="fas fa-calendar-alt"></i> <span>Matches</span></a></li>
                    <li><a href="admin.php#players" data-tab="players"><i class="fas fa-users"></i> <span>Players</span></a></li>
                    <li><a href="admin.php#shop" data-tab="shop"><i class="fas fa-shopping-cart"></i> <span>Shop</span></a></li>
                    <li><a href="admin.php#events" data-tab="events"><i class="fas fa-calendar-check"></i> <span>Events</span></a></li>
                    <li><a href="admin.php#media" data-tab="media"><i class="fas fa-photo-video"></i> <span>Media</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <button class="menu-toggle" aria-label="Toggle Menu"><i class="fas fa-bars"></i></button>
                <h1>Update Order #<?php echo $orderId; ?></h1>
                <div class="header-actions">
                    <div class="user-pill">
                        <div class="user-avatar-wrapper">
                            <img src="/mombasahamlets_web/frontend/images/29.jpg" alt="Admin" onerror="this.src='https://via.placeholder.com/40x40/4361ee/white?text=A'; this.onerror=null;">
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars(admin_full_name() ?? 'Admin'); ?></span>
                            <span class="user-role"><?php echo htmlspecialchars(admin_role() ?? 'Administrator'); ?></span>
                        </div>
                    </div>
                    <button id="logout-button" class="btn-logout-circle" aria-label="Logout"><i class="fas fa-sign-out-alt"></i></button>
                    <a href="admin_order_view.php?order_id=<?php echo $orderId; ?>" class="btn btn-cancel" style="margin-left: 10px;">Back to Details</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="card">
                    <h2>Change Status</h2>
                    
                    <?php if ($success): ?>
                        <div style="background: #e7f9f7; color: #2a9d8f; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-check-circle"></i> Status updated successfully!
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div style="background: #fdf2f2; color: #DA291C; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="admin-form" style="max-width: 500px;">
                        <div class="form-group">
                            <label for="status">Select New Status</label>
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <select name="status" id="status" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $order['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" class="btn">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
