<?php
require_once __DIR__ . '/../config/asset_helper.php';
$pageTitle = 'News - Mombasa Hamlets FC';
$pageDescription = 'Latest news and updates from Mombasa Hamlets Football Club. Breaking news, transfers, and match reports.';
$pageImage = 'images/logo1.webp';
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
                <li><a href="matches.php"><i class="fas fa-calendar-alt"></i> Matches</a></li>
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
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li class="nav-auth">
                    <a href="register.php" class="nav-register">Sign Up</a>
                    <span class="nav-separator">|</span>
                    <a href="login.php" class="nav-login">Login</a>
                </li>
            </ul>
        </nav>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <main>
        <!-- Loading State -->
        <div id="article-loading" class="article-loading">
            <i class="fas fa-spinner fa-spin"></i> Loading article...
        </div>
        
        <!-- Article Container (initially hidden) -->
        <div id="article-container" style="display: none;">
            <section class="article-hero">
                <div class="container">
                    <h1 id="article-title"></h1>
                    <p id="article-meta"></p>
                </div>
            </section>

            <div class="article-container">
                <div class="article-meta">
                    <span>By <span id="article-author">Club Reporter</span></span>

                </div>
                
                <img id="article-image" src="" alt="" class="article-image">
                
                <div class="article-content" id="article-content"></div>
                
                <a href="news.php" class="back-to-news">&larr; Back to News</a>
            </div>
        </div>
        
        <!-- Error State (initially hidden) -->
        <div id="article-error" style="display: none;">
            <div style="text-align: center; padding: 3rem;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #d32f2f; margin-bottom: 1rem;"></i>
                <h2>Article Not Found</h2>
                <p>The requested article could not be found.</p>
                <a href="news.php" class="back-to-news">Return to News</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- API Configuration -->
    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <!-- Main script for global UI (like mobile menu) -->
    <script type="module" src="js/main.js"></script>
    <!-- Script to fetch and display the single article -->
    <script src="js/news-single.js"></script>
</body>
</html>

