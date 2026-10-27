<?php
require_once __DIR__ . '/../config/asset_helper.php';
$pageTitle = 'Match Preview - Mombasa Hamlets FC';
$pageDescription = 'Preview of the upcoming Mombasa Hamlets FC match. Team news, formations, and prediction.';
$pageImage = 'images/logo1.webp';
$extraCss = ['css/match-preview.css'];
include __DIR__ . '/includes/meta_head.php';
?>
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
                <li><a href="matches.php" class="active"><i class="fas fa-calendar-alt"></i> Matches</a></li>
                <li><a href="players.php"><i class="fas fa-users"></i> Players</a></li>
                <li><a href="media.php"><i class="fas fa-photo-video"></i> Media</a></li>
                <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-check"></i> Events</a></li>
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
            <img src="images/logo1.jpeg" alt="Mombasa Hamlets Logo" class="header-logo">
        </div>
        <nav class="desktop-nav" aria-label="Main navigation">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="matches.php" class="active">Matches</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="media.php">Media</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="events.php">Events</a></li>
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
        <section class="hero matches-hero" style="min-height: 40vh;">
            <div class="hero-content">
                <h1>Match Center</h1>
                <p>Detailed statistics and report</p>
            </div>
        </section>

        <div class="container" style="max-width: 1000px; margin-bottom: 4rem;">
            <div id="match-loading" style="text-align: center; padding: 3rem;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading match details...</p>
            </div>
            
            <div id="match-content" style="display: none;">
                <!-- Content injected by JS -->
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js"></script>
    <script src="js/match-preview.js?v=2"></script>
</body>
</html>
