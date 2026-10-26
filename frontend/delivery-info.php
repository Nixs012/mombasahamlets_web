<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Info - Mombasa Hamlets FC</title>
    <meta name="description"
        content="Find delivery information for Mombasa Hamlets FC online store. Delivery fees, zones, pickup points, and shipping timelines.">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body data-page="delivery">
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
        <section class="hero">
            <div class="hero-content">
                <h1>Delivery Information</h1>
                <p>Find out how we deliver your Mombasa Hamlets FC merchandise</p>
            </div>
        </section>

        <!-- Delivery Content -->
        <div class="terms-container">
            <div class="last-updated">Last Updated: January 1, <?php echo date('Y'); ?></div>

            <div class="important-note">
                <p><strong>We deliver across Kenya!</strong> All delivery fees below are final and clearly
                    displayed at checkout.</p>
            </div>

            <div class="policy-section">
                <h2>Delivery Zones & Fees</h2>
                <table style="width:100%; border-collapse: collapse; margin: 20px 0;">
                    <thead>
                        <tr style="background: var(--dark-navy, #0A192F); color: white;">
                            <th style="padding: 12px; text-align: left;">Zone</th>
                            <th style="padding: 12px; text-align: left;">Delivery Fee</th>
                            <th style="padding: 12px; text-align: left;">Est. Delivery Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border: 1px solid var(--border-color, #ddd);">
                            <td style="padding: 12px; border: 1px solid var(--border-color, #ddd);">Mombasa
                                Island</td>
                            <td style="padding: 12px; border: 1px solid var(--border-color, #ddd); font-weight:
                                bold; color: var(--accent-gold, #DAA520);">KSh 150</td>
                            <td style="padding: 12px; border: 1px solid var(--border-color, #ddd);">1-2 business
                                days</td>
                        </tr>
                        <tr style="border: 1px solid var(--border-color, #ddd);">
                            <td style="padding: 12px; border: 1px solid var(--border-color, #ddd);">Outside
                                Mombasa</td>
                            <td style="padding: 12px; border: 1px solid var(--border-color, #ddd); font-weight:
                                bold; color: var(--accent-gold, #DAA520);">KSh 300</td>
                            <td style="padding: 12px; border: 1px solid var(--border-color, #ddd);">3-5 business
                                days</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="policy-section">
                <h2>Pickup Point</h2>
                <p>Customers who prefer to collect their orders in person may choose the <strong>Islandmifts</strong>
                    pickup point at checkout. Please select "Pickup at Islandmifts" as the delivery option. You
                    will be notified via email when your order is ready for collection.</p>
            </div>

            <div class="policy-section">
                <h2>Delivery Process</h2>
                <ol>
                    <li><strong>Order Confirmation:</strong> You will receive an email confirming your order
                        immediately after payment.</li>
                    <li><strong>Order Processing:</strong> Our team prepares your order for shipment within 1
                        business day.</li>
                    <li><strong>Dispatch Notification:</strong> You will receive an email with tracking details once
                        your order has been dispatched.</li>
                    <li><strong>Delivery:</strong> Your order will be delivered to the address you provided at
                        checkout.</li>
                </ol>
            </div>

            <div class="policy-section">
                <h2>Delivery Partners</h2>
                <p>We partner with <strong>Sendy, Uber, and Bolt</strong> (for Mombasa Island) and <strong>
                        Gokada and local courier services</strong> (for deliveries outside Mombasa). All
                    deliveries are tracked and insured.</p>
            </div>

            <div class="policy-section">
                <h2>Delivery Hours</h2>
                <p>
                    <ul>
                        <li><strong>Mombasa Island:</strong> Monday-Friday 9:00 AM - 6:00 PM, Saturday 10:00 AM -
                            2:00 PM</li>
                        <li><strong>Outside Mombasa:</strong> Courier deliveries are typically Monday-Friday
                            8:00 AM - 5:00 PM</li>
                        <li><strong>Islandmifts Pickup:</strong> Monday-Friday 10:00 AM - 7:00 PM, Saturday 10:00
                            AM - 4:00 PM</li>
                    </ul>
                </p>
            </div>

            <div class="policy-section">
                <h2>Failed Delivery Attempts</h2>
                <p>If you are not available to receive your order, the courier will attempt delivery again.
                    Additional delivery attempts may incur a small fee. You will be contacted by the courier
                    directly to arrange an alternative delivery time.</p>
            </div>

            <div class="policy-section">
                <h2>Contact Us</h2>
                <p>If you have any questions about delivery, please contact us:</p>
                <ul>
                    <li>Email: <a href="mailto:tickets@mombasahamletsfc.co.ke">tickets@mombasahamletsfc.co.ke</a>
                    </li>
                    <li>Phone: +254 723 456 789</li>
                    <li>Address: Mombasa Hamlets Stadium, Nyali Road, Mombasa, Kenya</li>
                </ul>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script src="js/main.js" defer></script>
</body>

</html>