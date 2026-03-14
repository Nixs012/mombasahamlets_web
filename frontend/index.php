<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Mombasa Hamlets FC</title>
    <meta name="description" content="Welcome to the official website of Mombasa Hamlets Football Club. Get the latest news, fixtures, player info, and more.">
    <link rel="stylesheet" href="css/style.css">
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
        <!-- Hero Section -->
        <section class="hero home-hero">
            <div class="hero-content">
                <h1 class="animate-pop-in">Mombasa Hamlets FC</h1>
                <p class="animate-pop-in">The Theatre of Dreams</p>
                <a href="matches.php" class="btn animate-pop-in">Next Match</a>
            </div>
        </section>

        <!-- Latest News Section -->
        <section class="latest-news-section" aria-labelledby="latest-news-heading">
            <div class="container">
                <h2 id="latest-news-heading" class="section-title">Latest News</h2>
                <div class="news-grid-home" id="latest-news-grid">
                    <!-- News articles will be dynamically loaded here from API -->
                    <p class="loading-message">Loading latest news...</p>
                </div>
                <div class="text-center">
                    <a href="news.php" class="btn btn-secondary">More News</a>
                </div>
            </div>
        </section>

        <!-- Sponsors Section -->
        <section class="sponsors-section" aria-labelledby="sponsors-heading">
            <div class="container">
                <h2 id="sponsors-heading" class="section-title">Official Partners</h2>
                <div class="sponsors-grid" id="partners-grid">
                    <!-- Partners will be dynamically loaded here -->
                    <div class="loading-partners" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin"></i> Loading Partners...
                    </div>
                </div>
            </div>
        </section>

        <!-- Shop CTA Section -->
        <section class="shop-cta-section">
            <div class="container">
                <div class="shop-cta-content">
                    <h2>Official Club Merchandise</h2>
                    <p>Show your support for the Hamlets by getting the latest kits, training wear, and accessories.</p>
                    <a href="shop.php" class="btn btn-primary">Visit the Shop</a>
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

