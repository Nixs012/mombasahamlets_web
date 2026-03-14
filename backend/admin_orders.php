<?php
/**
 * admin_orders.php
 * Admin page for managing customer orders.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

// Secure the page
admin_require_login();
$adminUser = admin_username() ?? 'Admin User';

// Fetch all orders with customer details using prepared statement
$query = "
    SELECT 
        o.id, 
        u.first_name, 
        u.last_name, 
        o.total_amount, 
        o.status, 
        o.shipping_address, 
        o.created_at 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <?php 
            $pageTitle = 'Order Management';
            include __DIR__ . '/includes/admin_header.php'; 
            ?>

            <div class="content-wrapper">
                <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
                    <div style="background: #e7f9f7; color: #2a9d8f; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> Order status updated successfully!
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h2>All Customer Orders</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Shipping Address</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <?php 
                                        $customerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
                                        echo htmlspecialchars($customerName ?: 'Guest / Unknown');
                                    ?>
                                </td>
                                <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php 
                                        $statusClass = 'status-pending';
                                        if ($order['status'] === 'paid') $statusClass = 'status-active';
                                        if ($order['status'] === 'completed') $statusClass = 'status-active';
                                        if ($order['status'] === 'cancelled') $statusClass = 'status-inactive';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="admin_order_view.php?order_id=<?php echo $order['id']; ?>" class="btn-action btn-edit" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="admin_order_update.php?order_id=<?php echo $order['id']; ?>" class="btn-action btn-edit" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">No orders found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
