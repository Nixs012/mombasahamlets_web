<?php
/**
 * ticket_details.php
 * View for specific ticket details and QR code.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: login.php?redirect=ticket_details.php?id=" . ($_GET['id'] ?? ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/ticket-details.css">
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

    <header class="header no-print">
        <div class="logo">
            <a href="index.php"><img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo"></a>
        </div>
        <nav class="desktop-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
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

    <main class="container ticket-details-page">
        <div class="ticket-header no-print">
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <div class="header-actions">
                <button id="download-ticket" class="btn btn-primary"><i class="fas fa-download"></i> Download QR</button>
            </div>
        </div>

        <div id="loading-state" class="text-center" style="padding: 100px 0;">
            <div class="loading-spinner"></div>
            <p>Loading ticket information...</p>
        </div>

        <div id="ticket-content" class="ticket-container" style="display: none;">
            <!-- Premium Ticket UI -->
            <div class="premium-ticket" id="ticket-visual-element">
                <div class="ticket-image-section">
                    <img id="event-banner" src="images/logo1.jpeg" alt="Event Banner">
                    <div class="ticket-overlay">
                        <h1 id="event-title">Event Title</h1>
                        <p id="event-date-str">Event Date & Time</p>
                    </div>
                </div>

                <div class="ticket-body">
                    <div class="ticket-info-grid">
                        <div class="info-box">
                            <label>Venue</label>
                            <p id="event-location">Location Address</p>
                        </div>
                        <div class="info-box">
                            <label>Ticket Type</label>
                            <p id="ticket-type">Standard Entry</p>
                        </div>
                        <div class="info-box">
                            <label>Price</label>
                            <p id="ticket-price">KSh 0.00</p>
                        </div>
                        <div class="info-box">
                            <label>Order Date</label>
                            <p id="order-date">Date here</p>
                        </div>
                    </div>

                    <div class="qr-download-section">
                        <div class="qr-wrapper">
                            <img id="ticket-qr" src="" alt="Ticket QR Code">
                        </div>
                        <span id="ticket-status" class="ticket-status-pill">UNUSED</span>
                    </div>
                </div>

                <div class="ticket-footer-strip">
                    <div class="ticket-brand">
                        <img src="images/logo1.jpeg" alt="Logo" style="height: 30px; width: auto;">
                        <span style="font-weight: 800; margin-left:10px;">MHFC</span>
                    </div>
                    <div class="ticket-id-tag" id="ticket-code-display">MHFC-XXXXXX</div>
                </div>
            </div>

            <div class="download-options no-print">
                <p class="text-center" style="color: #64748b; font-size: 0.9rem;">
                    Present this QR code at the entrance for verification.
                </p>
            </div>
        </div>

        <div id="error-state" class="text-center" style="display: none; padding: 100px 0;">
            <i class="fas fa-ticket-alt" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 20px;"></i>
            <h2>Ticket Not Found</h2>
            <p>We couldn't find this ticket or you don't have permission to view it.</p>
            <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </main>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js"></script>
    <script src="js/ticket-details.js?v=1.3"></script>
</body>
</html>
