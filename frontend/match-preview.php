<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details - Mombasa Hamlets FC</title>
    <meta name="description" content="Match details, lineups, and commentary for Mombasa Hamlets FC">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .match-details-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: -50px; /* Overlap hero */
            position: relative;
            z-index: 10;
        }
        .details-header { text-align: center; margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 2rem; }
        .score-board { display: flex; justify-content: space-around; align-items: center; font-size: 2rem; font-weight: bold; }
        .team-display { display: flex; flex-direction: column; align-items: center; gap: 1rem; width: 40%; }
        .team-display img { width: 100px; height: 100px; object-fit: contain; }
        .score-display { font-size: 3rem; color: var(--red); }
        .match-meta { text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; }
        .lineups-section, .stats-section, .report-section { background: #f9f9f9; padding: 1.5rem; border-radius: 8px; }
        h3 { color: var(--dark-red); margin-bottom: 1rem; border-bottom: 2px solid #ddd; padding-bottom: 0.5rem; }
        .lineup-list { list-style: none; }
        .lineup-list li { padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .lineup-list li:last-child { border-bottom: none; }
        @media (max-width: 768px) {
            .details-grid { grid-template-columns: 1fr; }
            .score-board { flex-direction: column; gap: 2rem; }
            .team-display { width: 100%; }
        }
    </style>
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
