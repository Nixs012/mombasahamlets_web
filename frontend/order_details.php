<?php
/**
 * order_details.php
 * View for specific order details.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: login.php?redirect=order_details.php?id=" . ($_GET['id'] ?? ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/order-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <!-- Mobile Sidebar -->
    <div class="sidebar-overlay"></div>
    <aside class="mobile-sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="sidebar-logo">
            </div>
            <button class="sidebar-close" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="sidebar-nav" aria-label="Mobile navigation">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="news.php"><i class="fas fa-newspaper"></i> News</a></li>
                <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-check"></i> Events</a></li>
                <li><a href="dashboard.php" class="active"><i class="fas fa-columns"></i> My Dashboard</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                <li class="nav-auth-mobile">
                    <a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                </li>
            </ul>
        </nav>
    </aside>

    <header class="header">
        <div class="logo">
            <a href="index.php"><img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo"></a>
        </div>
        <nav class="desktop-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="dashboard.php" class="active">My Dashboard</a></li>
                <li><a href="about.php">About</a></li>
                <li class="nav-auth">
                    <a href="register.php" class="nav-register">Sign Up</a>
                    <span class="nav-separator">|</span>
                    <a href="login.php" class="nav-login">Login</a>
                </li>
            </ul>
        </nav>

        <button class="mobile-menu-toggle" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <main class="container order-details-container">
        <div class="back-nav">
            <a href="dashboard.php" class="btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div id="loading-state" class="text-center" style="padding: 100px 0;">
            <div class="loading-spinner"></div>
            <p>Loading order details...</p>
        </div>

        <div id="order-content" style="display: none;">
            <!-- Header Summary -->
            <div class="order-header-card">
                <div class="order-meta">
                    <p id="order-date-label"></p>
                    <h1 id="order-id-label"></h1>
                    <span id="order-status-badge" class="badge"></span>
                </div>
                <div class="order-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Print Invoice</button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="order-progress">
                <div class="stepper">
                    <div class="step" id="step-pending">
                        <div class="step-circle">1</div>
                        <div class="step-label">PENDING</div>
                    </div>
                    <div class="step" id="step-paid">
                        <div class="step-circle">2</div>
                        <div class="step-label">PAID</div>
                    </div>
                    <div class="step" id="step-completed">
                        <div class="step-circle">3</div>
                        <div class="step-label">COMPLETED</div>
                    </div>
                </div>
            </div>

            <div class="details-grid">
                <!-- Items List -->
                <div class="items-section">
                    <div class="section-padding" style="border-bottom: 1px solid #f1f5f9;">
                        <h3>Items in Order</h3>
                    </div>
                    <div id="items-list">
                        <!-- Items populated by JS -->
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="summary-sidebar">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span id="summary-subtotal">KSh 0</span>
                        </div>
                        <div class="summary-line">
                            <span>Shipping</span>
                            <span id="summary-shipping">KSh 0</span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span id="summary-total">KSh 0</span>
                        </div>
                    </div>

                    <div class="summary-card">
                        <h3>Delivery Info</h3>
                        <div class="info-item">
                            <label>Shipping Address</label>
                            <p id="display-address">N/A</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <h3>Payment Info</h3>
                        <div class="info-item">
                            <label>Method</label>
                            <p id="payment-method">N/A</p>
                        </div>
                        <div class="info-item">
                            <label>Reference</label>
                            <p id="payment-ref">N/A</p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <p id="payment-status">N/A</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="error-state" class="text-center" style="display: none; padding: 100px 0;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--primary);"></i>
            <h2>Oops! Order not found</h2>
            <p>We couldn't retrieve the details for this order. It might not belong to you or has been removed.</p>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </main>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js"></script>
    <script src="js/order-details.js"></script>
</body>
</html>
