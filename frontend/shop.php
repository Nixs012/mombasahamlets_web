<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Mombasa Hamlets FC</title>
    <meta name="description" content="Official merchandise and apparel for Mombasa Hamlets FC fans.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shop.css?v=7">
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
                <li><a href="shop.php" class="active"><i class="fas fa-shopping-bag"></i> Shop</a></li>
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
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="matches.php">Matches</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="media.php">Media</a></li>
                <li><a href="shop.php" class="active">Shop</a></li>
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

        <!-- Shopping Cart Icon -->
        <div class="cart-icon-container">
            <button class="cart-icon" aria-label="Shopping cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cart-count">0</span>
            </button>
        </div>
    </header>

    <main>
        <!-- Hero Section for Shop Page -->
        <section class="hero shop-hero">
            <div class="hero-content">
                <h1 class="animate-pop-in">Official Club Store</h1>
                <p class="animate-pop-in">Get your official Mombasa Hamlets gear and support the team!</p>
            </div>
        </section>

        <!-- Shop Container -->
        <section class="shop-container" aria-labelledby="shop-heading">
            <div class="container">
                <h2 id="shop-heading" class="section-title">All Products</h2>

                <!-- Filters and Sorting -->
                <div class="shop-controls">
                    <div class="category-filters">
                        <button class="category-link active" data-category="all">All</button>
                        <button class="category-link" data-category="jerseys">Jerseys</button>
                        <button class="category-link" data-category="apparel">Apparel</button>
                        <button class="category-link" data-category="accessories">Accessories</button>
                        <button class="category-link" data-category="sale">Sale</button>
                    </div>
                    <div class="sort-controls">
                        <label for="sort-by" class="sr-only">Sort by:</label>
                        <select id="sort-by" class="sort-select">
                            <option value="newest">Newest</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="products-grid" id="productsGrid">
                    <!-- Products will be loaded here by JavaScript from API -->
                </div>
            </div>
        </section>
    </main>

    <!-- Cart Sidebar -->
    <div class="cart-overlay"></div>
    <aside class="cart-sidebar">
        <div class="cart-header">
            <h3>Shopping Cart</h3>
            <button class="cart-close" aria-label="Close cart">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-content">
            <ul id="cart-items" class="cart-items">
                <!-- Cart items will be populated here -->
            </ul>
            <div class="cart-footer">
                <div class="cart-total">
                    <strong>Total: KSh <span id="cart-total">0.00</span></strong>
                </div>
                <button class="btn-checkout">Proceed to Checkout</button>
            </div>
        </div>
    </aside>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <button class="modal-close" id="closeQuickView" aria-label="Close Quick View">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-content">
                <div class="modal-image">
                    <img id="modalProductImage" src="" alt="Product Image">
                </div>
                <div class="modal-details">
                    <p id="modalProductCategory" class="product-category"></p>
                    <h2 id="modalProductName" class="product-name"></h2>
                    <div id="modalProductPrice" class="product-price"></div>
                    <p id="modalProductDesc" class="product-description"></p>
                    
                    <div id="modalProductOptions" class="product-options-modal">
                        <!-- Size selector will be injected here -->
                    </div>
                    
                    <div class="modal-actions">
                        <button id="modalAddToCart" class="btn-primary">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/shop.js?v=7"></script>
    <script type="module" src="js/main.js?v=2"></script>
</body>
</html>
