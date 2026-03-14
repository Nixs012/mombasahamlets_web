<?php
/**
 * my_tickets.php
 * UI page for users to view their purchased tickets.
 */
require_once __DIR__ . '/../backend/includes/session_config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: login.php?redirect=my_tickets.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/my-tickets.css">
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
                <li><a href="my_tickets.php" class="active"><i class="fas fa-ticket-alt"></i> My Tickets</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Header -->
    <header class="header">
        <div class="logo">
            <a href="index.php"><img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo"></a>
        </div>
        
        <!-- Desktop Navigation -->
        <nav class="desktop-nav" aria-label="Main navigation">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="my_tickets.php" class="active">My Tickets</a></li>
                <li><a href="about.php">About</a></li>
                <li class="nav-auth">
                    <a href="profile.php" class="nav-register"><i class="fas fa-user-circle"></i> My Account</a>
                </li>
            </ul>
        </nav>
        
        <button class="mobile-menu-toggle" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <main>
        <section class="tickets-header">
            <div class="container">
                <h1 class="animate-pop-in">My Tickets</h1>
                <p class="animate-pop-in">All your upcoming matches and event access in one place.</p>
            </div>
        </section>

        <div class="tickets-container">
            <div id="tickets-loading" class="no-tickets">
                <div class="loading-spinner"></div>
                <p>Retrieving your tickets...</p>
            </div>

            <div id="tickets-grid" class="tickets-grid" style="display: none;">
                <!-- Tickets will be loaded here by JavaScript -->
            </div>

            <div id="no-tickets-message" class="no-tickets" style="display: none;">
                <i class="fas fa-ticket-alt"></i>
                <h2>No Tickets Found</h2>
                <p>You haven't purchased any tickets yet. Check out our upcoming events!</p>
                <div style="margin-top: 30px;">
                    <a href="events.php" class="btn btn-primary">Browse Events</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js"></script>
    <script src="js/my-tickets.js"></script>
</body>
</html>
