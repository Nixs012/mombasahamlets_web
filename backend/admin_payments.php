<?php
/**
 * admin_payments.php
 * Admin page for viewing all payment transactions.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

// Secure the page
admin_require_login();
$adminUser = admin_username() ?? 'Admin User';

// Fetch all payments using prepared statement
$query = "
    SELECT 
        p.id, 
        p.order_id, 
        p.reference, 
        p.amount, 
        p.payment_method, 
        p.status, 
        p.payment_date 
    FROM payments p 
    ORDER BY p.payment_date DESC, p.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <?php 
            $pageTitle = 'Payment Transactions';
            include __DIR__ . '/includes/admin_header.php'; 
            ?>

            <div class="content-wrapper">
                <div class="card">
                    <h2>Recent Payments</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $targetId = $_GET['id'] ?? 0;
                                foreach ($payments as $payment): 
                                    $isTarget = ($payment['id'] == $targetId);
                            ?>
                            <tr <?php echo $isTarget ? 'style="background: rgba(67, 97, 238, 0.1); border-left: 4px solid var(--primary);"' : ''; ?> id="payment-<?php echo $payment['id']; ?>">
                                <td>#<?php echo $payment['id']; ?></td>
                                <td>
                                    <a href="admin_order_view.php?order_id=<?php echo $payment['order_id']; ?>" style="color: var(--primary); text-decoration: underline;">
                                        #<?php echo $payment['order_id']; ?>
                                    </a>
                                </td>
                                <td><code><?php echo htmlspecialchars($payment['reference']); ?></code></td>
                                <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo strtoupper($payment['payment_method']); ?></td>
                                <td>
                                    <?php 
                                        $rawStatus = strtoupper($payment['status']);
                                        $statusClass = 'status-pending';
                                        if (strpos($rawStatus, 'COMPLETED') !== false || strpos($rawStatus, 'SUCCESS') !== false) {
                                            $statusClass = 'status-active';
                                        } elseif (strpos($rawStatus, 'FAILED') !== false) {
                                            $statusClass = 'status-inactive';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($rawStatus); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $payment['payment_date'] ? date('M d, Y H:i', strtotime($payment['payment_date'])) : 'Pending'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">No payments recorded yet.</td>
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
