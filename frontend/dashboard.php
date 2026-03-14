<?php
require_once __DIR__ . '/../backend/includes/session_config.php';
// Removed PHP-only redirect to allow JWT/localStorage authentication to take precedence via JS.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        // CLIENT-SIDE AUTH CHECK: Redirect if no token is found in localStorage
        if (!localStorage.getItem('userToken')) {
            window.location.href = 'login.php?redirect=dashboard.php';
        }
    </script>
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
                <li><a href="matches.php"><i class="fas fa-calendar-alt"></i> Matches</a></li>
                <li><a href="players.php"><i class="fas fa-users"></i> Players</a></li>
                <li><a href="media.php"><i class="fas fa-photo-video"></i> Media</a></li>
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
                <li><a href="matches.php">Matches</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="media.php">Media</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="dashboard.php" class="active">My Dashboard</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
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

    <main>
        <div class="dashboard-header-bg">
            <div class="container dashboard-header">
                <h1 id="welcome-greeting" class="animate-pop-in">Welcome Back, <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'User'); ?>!</h1>
                <p class="animate-pop-in">Manage your shop orders and event tickets in one place.</p>
            </div>
        </div>

        <div class="container dashboard-content">
            <div class="dashboard-sections-grid">
                <!-- Orders Section -->
                <section class="dashboard-section section-orders">
                    <div class="section-header-row">
                        <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
                        <a href="shop.php" class="btn-link">Shop More <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="dashboard-card">
                        <div class="table-responsive">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="orders-list">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading orders...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Tickets Section -->
                <section class="dashboard-section section-tickets">
                    <div class="section-header-row">
                        <h2><i class="fas fa-ticket-alt"></i> My Event Tickets</h2>
                        <a href="events.php" class="btn-link">Browse Events <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="dashboard-card">
                        <div class="table-responsive">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Ticket Info</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tickets-list">
                                    <tr>
                                        <td colspan="3" class="text-center">Loading tickets...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js"></script>
    <script src="js/dashboard.js?v=1.3"></script>
</body>
</html>
