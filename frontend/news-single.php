<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Article - Mombasa Hamlets FC</title>
    <meta name="description" content="Read the latest news from Mombasa Hamlets FC">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Single article specific styles */
        .article-hero {
            background: linear-gradient(rgba(26, 92, 52, 0.9), rgba(26, 92, 52, 0.8));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .article-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .article-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .article-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem 3rem;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }
        
        .article-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        
        .article-content p {
            margin-bottom: 1.5rem;
        }
        
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .article-content blockquote {
            border-left: 4px solid #1a5c34;
            padding-left: 1.5rem;
            margin: 2rem 0;
            font-style: italic;
            color: #555;
        }
        
        .back-to-news {
            display: inline-block;
            margin-top: 3rem;
            padding: 10px 20px;
            background-color: #1a5c34;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .back-to-news:hover {
            background-color: #2e8b57;
        }
        
        .article-loading {
            text-align: center;
            padding: 3rem;
            font-size: 1.2rem;
            color: #666;
        }
        
        #article-error {
            text-align: center;
            padding: 3rem;
            color: #d32f2f;
        }
        
        @media (max-width: 768px) {
            .article-hero h1 {
                font-size: 2rem;
            }
            
            .article-hero {
                padding: 2rem 1rem;
            }
            
            .article-container {
                padding: 0 1rem 2rem;
            }
            
            .article-image {
                height: 250px;
            }
            
            .article-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
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

