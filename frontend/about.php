<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Mombasa Hamlets FC</title>
    <meta name="description" content="Learn about Mombasa Hamlets FC - our history, mission, values, and achievements">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/about.css">
</head>

<body data-page="about">
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
                <li><a href="dashboard.php"><i class="fas fa-columns"></i> My Dashboard</a></li>
                <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i> About</a></li>
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
                <li><a href="dashboard.php">My Dashboard</a></li>
                <li><a href="about.php" class="active">About</a></li>
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
        <section class="about-hero">
            <h1>About Mombasa Hamlets FC</h1>
            <p>Pride, Passion, and Perseverance since 1995</p>
        </section>

        <section class="about-section">
            <h2>Our History</h2>
            <div class="history-content">
                <div class="history-text">
                    <p>Mombasa Hamlets FC was founded in 1995 by a group of local football enthusiasts who wanted to
                        create a community-focused football club that would nurture local talent and represent Mombasa
                        with pride.</p>
                    <p>Starting from humble beginnings in the local leagues, the club steadily rose through the ranks,
                        earning promotion to the National Super League in 2008 and finally to the Kenyan Premier League
                        in 2015.</p>
                    <p>Our name "Hamlets" reflects our philosophy - though we come from small communities, together we
                        form a powerful force. The club has always been about more than just football; it's about
                        community development, youth empowerment, and creating opportunities.</p>
                    <p>Today, Mombasa Hamlets FC stands as a symbol of coastal football excellence, known for our
                        attractive style of play and commitment to developing young talent.</p>
                </div>
                <div class="history-image">
                    <img src="images/16.jpg" alt="Mombasa Hamlets early days">
                </div>
            </div>
        </section>

        <section class="mission-vision">
            <div class="mv-container">
                <div class="mv-card">
                    <i class="fas fa-bullseye"></i>
                    <h3>Our Mission</h3>
                    <p>To develop exceptional football talent while promoting sportsmanship, community values, and
                        healthy living through the beautiful game. We aim to be a leading football institution that
                        provides opportunities for youth development and represents Mombasa with distinction at national
                        and international levels.</p>
                </div>
                <div class="mv-card">
                    <i class="fas fa-eye"></i>
                    <h3>Our Vision</h3>
                    <p>To become East Africa's most respected football club, renowned for developing world-class talent,
                        playing attractive football, and making a positive impact in our community. We envision a future
                        where every young footballer in the coastal region sees Mombasa Hamlets as their pathway to
                        success.</p>
                </div>
                <div class="mv-card">
                    <i class="fas fa-handshake"></i>
                    <h3>Our Values</h3>
                    <p>Excellence, Integrity, Community, Passion, and Innovation. These values guide everything we do -
                        from our youth academy to our first-team operations. We believe in playing with heart,
                        respecting our opponents, and always giving back to the community that supports us.</p>
                </div>
            </div>
        </section>

        <section class="achievements">
            <h2>Club Achievements</h2>
            <div class="trophies-grid">
                <div class="trophy-item">
                    <i class="fas fa-trophy"></i>
                    <h3>Coastal League Champions</h3>
                    <p>2005, 2007, 2010</p>
                </div>
                <div class="trophy-item">
                    <i class="fas fa-trophy"></i>
                    <h3>FKF Cup Winners</h3>
                    <p>2018, 2021</p>
                </div>
                <div class="trophy-item">
                    <i class="fas fa-trophy"></i>
                    <h3>Super Cup Champions</h3>
                    <p>2019</p>
                </div>
                <div class="trophy-item">
                    <i class="fas fa-medal"></i>
                    <h3>KPL Top 4 Finish</h3>
                    <p>2020, 2022</p>
                </div>
            </div>
        </section>

        <section class="management">
            <h2>Club Management</h2>
            <div class="management-grid">
                <div class="manager-card">
                    <img src="images/logo1.jpeg" alt="Club Chairman" class="manager-image">
                    <h3>Abdullahi Mohammed</h3>
                    <p>Club Chairman</p>
                    <p>Leading the club since 2012, Abdullahi has been instrumental in our professionalization and
                        growth.</p>
                </div>
                <div class="manager-card">
                    <img src="images/logo1.jpeg" alt="Head Coach" class="manager-image">
                    <h3>James Okoth</h3>
                    <p>Head Coach</p>
                    <p>Former Harambee Stars midfielder who joined us in 2020 and revolutionized our playing style.</p>
                </div>
                <div class="manager-card">
                    <img src="images/academy.jpg" alt="Academy Director" class="manager-image">
                    <h3>Martin Okoth</h3>
                    <p>Academy Director</p>
                    <p>Pioneering women's football development and overseeing our youth system since 2015.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <h2>Become Part of Our Story</h2>
            <p>Join the Hamlets family as a fan, partner, or player</p>
            <div class="cta-buttons">
                <a href="shop.php" class="cta-btn primary">Get Merchandise</a>
                <a href="contact.php" class="cta-btn">Contact Us</a>
                <a href="events.php" class="cta-btn">Buy Tickets</a>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script src="js/about.js"></script>
    <script type="module" src="js/main.js?v=2" defer></script>
</body>

</html>
