<?php
/**
 * admin_order_view.php
 * View full details for a specific order.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

// Secure the page
admin_require_login();
$adminUser = admin_username() ?? 'Admin User';

$orderId = $_GET['order_id'] ?? 0;

if ($orderId <= 0) {
    die("Error: Invalid Order ID provided.");
}

// 1. Fetch Order & User Info
$orderQuery = "
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found.");
}

// 2. Fetch Order Items
$itemsQuery = "
    SELECT oi.*, s.name as product_name 
    FROM order_items oi 
    JOIN shop s ON oi.product_id = s.id 
    WHERE oi.order_id = ?
";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. Fetch Payment Info
$payQuery = "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($payQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $orderId; ?> - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .detail-item { padding: 15px; background: rgba(0,0,0,0.02); border-radius: 8px; }
        .detail-label { font-weight: 700; color: var(--gray); font-size: 0.8rem; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .detail-value { font-size: 1.1rem; color: var(--dark); }
        .order-items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .order-items-table th { text-align: left; padding: 12px; background: var(--gray-light); }
        .order-items-table td { padding: 12px; border-bottom: 1px solid var(--gray-light); }
    </style>
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
                <h1>Order Details #<?php echo $orderId; ?></h1>
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
                    <a href="admin_orders.php" class="btn btn-cancel" style="margin-left: 10px;">Back to List</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="card">
                    <h2>Customer & Shipping Info</h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Customer Name</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Customer Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Status</span>
                            <span class="detail-value"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Date</span>
                            <span class="detail-value"><?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-item" style="grid-column: span 2;">
                            <span class="detail-label">Shipping Address</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                        </div>
                    </div>

                    <h3>Payment Summary</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Payment Status</span>
                            <span class="detail-value"><?php echo $payment ? ucfirst($payment['status']) : 'No payment record'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Transaction Ref</span>
                            <span class="detail-value"><?php echo $payment ? htmlspecialchars($payment['reference']) : 'N/A'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value"><?php echo $payment ? htmlspecialchars($payment['payment_method']) : 'N/A'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Amount</span>
                            <span class="detail-value">KSh <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Purchased Items</h2>
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>KSh <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>KSh <?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" style="text-align: right;">Final Total:</th>
                                <th>KSh <?php echo number_format($order['total_amount'], 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <a href="admin_order_update.php?order_id=<?php echo $orderId; ?>" class="btn">Update Order Status</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
