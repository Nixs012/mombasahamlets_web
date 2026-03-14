<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players - Mombasa Hamlets FC</title>
    <meta name="description" content="Meet the players of Mombasa Hamlets Football Club">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body data-page="players">
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
                <li><a href="players.php" class="active"><i class="fas fa-users"></i> Players</a></li>
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
                <li><a href="players.php" class="active">Players</a></li>
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
        <!-- Players Hero Section -->
        <section class="players-hero">
            <div class="hero-content">
                <h1>Our Squad</h1>
                <p>Meet the talented players of Mombasa Hamlets FC</p>
            </div>
        </section>

        <!-- Players Container -->
        <section class="players-container">
            <!-- Filters and Search -->
            <div class="players-controls">
                <div class="search-box">
                    <input type="text" id="playerSearch" placeholder="Search players..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All Players</button>
                    <button class="filter-btn" data-filter="goalkeepers">Goalkeepers</button>
                    <button class="filter-btn" data-filter="defenders">Defenders</button>
                    <button class="filter-btn" data-filter="midfielders">Midfielders</button>
                    <button class="filter-btn" data-filter="forwards">Forwards</button>
                </div>
            </div>

            <!-- Players Grid -->
            <div class="players-grid" id="playersGrid">
                <!-- Players will be loaded here by JavaScript from API -->
                <div class="loading-spinner"></div>
            </div>

            <!-- Player Modal -->
            <div class="modal" id="playerModal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <div class="modal-body" id="modalBody">
                        <!-- Player details will be loaded here -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript Files -->
    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script src="js/utils.js"></script>
    <script type="module" src="js/main.js?v=2"></script>
    <script type="module" src="js/players.js?v=2"></script>
</body>
</html>
