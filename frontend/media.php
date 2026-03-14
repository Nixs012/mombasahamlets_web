<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media - Mombasa Hamlets FC</title>
    <meta name="description" content="Official media gallery of Mombasa Hamlets Football Club">
    <link rel="stylesheet" href="css/style.css?v=5">
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
                <li><a href="matches.php"><i class="fas fa-calendar-alt"></i> Matches</a></li>
                <li><a href="players.php"><i class="fas fa-users"></i> Players</a></li>
                <li><a href="media.php" class="active"><i class="fas fa-photo-video"></i> Media</a></li>
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
                <li><a href="media.php" class="active">Media</a></li>
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
        <!-- Hero Section -->
        <section class="hero media-hero">
            <div class="hero-content">
                <h1 class="animate-pop-in">Media Gallery</h1>
                <p class="animate-pop-in">Behind the scenes and match highlights</p>
            </div>
        </section>

        <!-- Media Filters -->
        <section class="media-filters" aria-labelledby="media-filters-heading">
            <h2 id="media-filters-heading" class="visually-hidden">Media Filters</h2>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All Media</button>
                <button class="filter-btn" data-filter="photos">Photos</button>
                <button class="filter-btn" data-filter="videos">Videos</button>
                <button class="filter-btn" data-filter="matches">Match Highlights</button>
                <button class="filter-btn" data-filter="training">Training</button>
            </div>
        </section>

        <!-- Media Grid Section -->
        <section class="media-section" aria-labelledby="media-heading">
            <h2 id="media-heading" class="visually-hidden">Media Items</h2>
            <div class="media-grid" id="media-grid">
                <!-- Media items will be populated by JavaScript from API -->
                <div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading media...</div>
            </div>
            <div id="pagination-container" class="pagination-container"></div>
        </section>

        <!-- The "More Media" button is replaced by pagination -->
        <div class="load-more-container" style="display:none;">
            <button class="load-more-btn">Load More Media</button>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Lightbox Modal -->
    <div class="lightbox" id="lightbox">
        <div class="lightbox-content">
            <button class="lightbox-close" aria-label="Close lightbox">
                <i class="fas fa-times"></i>
            </button>
            <button class="lightbox-nav lightbox-prev" aria-label="Previous image">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="lightbox-nav lightbox-next" aria-label="Next image">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="lightbox-body">
                <!-- Content (img or video) will be injected by JS -->
            </div>
            <div class="lightbox-caption"></div>
        </div>
    </div>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/media.js?v=5"></script>
    <script type="module" src="js/main.js?v=5"></script>
</body>
</html>
