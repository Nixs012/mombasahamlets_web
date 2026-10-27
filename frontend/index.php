<?php
require_once __DIR__ . '/../config/asset_helper.php';
$pageTitle = 'Mombasa Hamlets FC - Official Website';
$pageDescription = 'Welcome to Mombasa Hamlets Football Club official website. Latest news, fixtures, results, and merchandise from Mombasa, Kenya.';
$pageImage = 'images/logo1.webp';
include __DIR__ . '/includes/meta_head.php';
?>
<body data-page="home">
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
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="news.php"><i class="fas fa-newspaper"></i> News</a></li>
                <li><a href="matches.php"><i class="fas fa-calendar-alt"></i> Matches</a></li>
                <li><a href="players.php"><i class="fas fa-users"></i> Players</a></li>
                <li><a href="media.php"><i class="fas fa-photo-video"></i> Media</a></li>
                <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-check"></i> Events</a></li>
                <li><a href="dashboard.php"><i class="fas fa-columns"></i> My Dashboard</a></li>
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
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="matches.php">Matches</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="media.php">Media</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="dashboard.php">My Dashboard</a></li>
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
        <!-- Hero Section with Next Match Countdown -->
        <section class="hero home-hero" aria-labelledby="hero-title">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 id="hero-title" class="hero-title animate-pop-in">Mombasa Hamlets FC</h1>
                <p class="hero-subtitle animate-pop-in-delayed">The Theatre of Dreams</p>

                <!-- Next Match Countdown -->
                <div class="next-match-countdown animate-pop-in-delayed-2" id="next-match-countdown">
                    <div class="countdown-label">
                        <i class="fas fa-clock"></i> Next Match
                    </div>
                    <div class="countdown-timer" id="countdown-timer">
                        <div class="countdown-item">
                            <span class="countdown-value" id="countdown-days">--</span>
                            <span class="countdown-label-item">Days</span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value" id="countdown-hours">--</span>
                            <span class="countdown-label-item">Hours</span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value" id="countdown-minutes">--</span>
                            <span class="countdown-label-item">Minutes</span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value" id="countdown-seconds">--</span>
                            <span class="countdown-label-item">Seconds</span>
                        </div>
                    </div>
                    <div class="next-match-info" id="next-match-info">
                        <span class="next-match-teams" id="next-match-teams"></span>
                        <span class="next-match-venue" id="next-match-venue"></span>
                    </div>
                    <a href="matches.php" class="btn btn-primary btn-lg animate-pop-in-delayed-3">
                        <i class="fas fa-calendar-alt"></i> View All Matches
                    </a>
                </div>
            </div>
        </section>

        <!-- Latest News Section -->
        <section class="latest-news-section" aria-labelledby="latest-news-heading">
            <div class="container">
                <h2 id="latest-news-heading" class="section-title">
                    <span class="section-title-main">Latest News</span>
                </h2>
                <div class="news-grid-home" id="latest-news-grid">
                    <!-- News articles will be dynamically loaded here from API -->
                    <div class="news-card-skeleton" aria-label="Loading news">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line"></div>
                        </div>
                    </div>
                    <div class="news-card-skeleton">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line"></div>
                        </div>
                    </div>
                    <div class="news-card-skeleton">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line"></div>
                        </div>
                    </div>
                    <div class="news-card-skeleton">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line"></div>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <a href="news.php" class="btn btn-secondary">More News</a>
                </div>
            </div>
        </section>

        <!-- Featured Players Section -->
        <section class="featured-players-section" aria-labelledby="featured-players-heading">
            <div class="container">
                <h2 id="featured-players-heading" class="section-title">
                    <span class="section-title-main">Featured Players</span>
                </h2>
                <div class="players-grid-home" id="featured-players-grid">
                    <!-- Players will be dynamically loaded here from API -->
                    <div class="player-card-skeleton" aria-label="Loading players">
                        <div class="skeleton-player-image"></div>
                        <div class="skeleton-player-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="player-card-skeleton">
                        <div class="skeleton-player-image"></div>
                        <div class="skeleton-player-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="player-card-skeleton">
                        <div class="skeleton-player-image"></div>
                        <div class="skeleton-player-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="player-card-skeleton">
                        <div class="skeleton-player-image"></div>
                        <div class="skeleton-player-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <a href="players.php" class="btn btn-secondary">Meet the Team</a>
                </div>
            </div>
        </section>

        <!-- Shop Highlights Section -->
        <section class="shop-highlights-section" aria-labelledby="shop-highlights-heading">
            <div class="container">
                <h2 id="shop-highlights-heading" class="section-title">
                    <span class="section-title-main">Official Club Merchandise</span>
                </h2>
                <div class="shop-highlights-grid" id="shop-highlights-grid">
                    <div class="shop-product-skeleton" aria-label="Loading merchandise">
                        <div class="skeleton-product-image"></div>
                        <div class="skeleton-product-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="shop-product-skeleton">
                        <div class="skeleton-product-image"></div>
                        <div class="skeleton-product-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="shop-product-skeleton">
                        <div class="skeleton-product-image"></div>
                        <div class="skeleton-product-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="shop-product-skeleton">
                        <div class="skeleton-product-image"></div>
                        <div class="skeleton-product-info">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <a href="shop.php" class="btn btn-primary btn-shine">
                        <i class="fas fa-shopping-bag"></i> Visit the Shop
                    </a>
                </div>
            </div>
        </section>

        <!-- Sponsors Section -->
        <section class="sponsors-section" aria-labelledby="sponsors-heading">
            <div class="container">
                <h2 id="sponsors-heading" class="section-title">
                    <span class="section-title-main">Official Partners</span>
                </h2>
                <div class="sponsors-strip" id="partners-strip">
                    <!-- Partners will be dynamically loaded as scrolling marquee -->
                    <div class="sponsors-scroll">
                        <i class="fas fa-spinner fa-spin"></i> Loading Partners...
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter" aria-labelledby="newsletter-heading">
            <div class="container">
                <h2 id="newsletter-heading">Never Miss an Event</h2>
                <p>Subscribe to our newsletter and be the first to know about upcoming matches, special events, and ticket sales.</p>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" id="newsletterEmail" placeholder="Your email address" aria-label="Your email address" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
                <div id="newsletterMessage" style="display: none; margin-top: 10px; font-weight: bold;"></div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js?v=2" defer></script>
    
</body>
</html>

