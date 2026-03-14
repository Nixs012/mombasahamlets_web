<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Mombasa Hamlets FC</title>
    <meta name="description" content="Upcoming events for Mombasa Hamlets Football Club">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/event-single.css">
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
                <li><a href="media.php"><i class="fas fa-photo-video"></i> Media</a></li>
                <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                <li><a href="events.php" class="active"><i class="fas fa-calendar-check"></i> Events</a></li>
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
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="matches.php">Matches</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="media.php">Media</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="events.php" class="active">Events</a></li>
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
        <section class="page-hero">
            <div class="container hero-content">
                <h1 class="animate-pop-in">Club Events</h1>
                <p class="animate-pop-in">Join us for exciting events throughout the season</p>
            </div>
        </section>

        <section class="events-container" aria-labelledby="events-heading">
            <div class="container">
                <h2 id="events-heading" class="section-title">Upcoming Events</h2>
                
                <div class="events-filter">
                    <button class="filter-btn active" data-filter="all">All Events</button>
                    <button class="filter-btn" data-filter="matches">Matches</button>
                    <button class="filter-btn" data-filter="community">Community</button>
                    <button class="filter-btn" data-filter="meet-greet">Meet & Greet</button>
                    <button class="filter-btn" data-filter="special">Special Events</button>
                </div>
                
                <div class="events-grid" id="eventsGrid">
                    <!-- Events will be dynamically loaded here by JavaScript from API -->
                    <p class="loading-message">Loading events...</p>
                </div>
            </div>
        </section>


    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Event Modals -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span id="modalClose" class="close">&times;</span>
            <h2 id="modalEventTitle">Event Tickets</h2>
            <div id="modalBody">
                <!-- Content loaded by JS -->
            </div>
        </div>
    </div>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script type="module" src="js/main.js?v=2"></script>
    <script type="module" src="js/event.js"></script>
</body>
</html>

