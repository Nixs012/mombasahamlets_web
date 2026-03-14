<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Mombasa Hamlets FC</title>
    <meta name="description"
        content="Get in touch with Mombasa Hamlets FC - contact information, location, and inquiry forms">
    <link rel="stylesheet" href="css/style.css?v=faq_fix">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body data-page="contact">
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
        <section class="hero">
            <div class="hero-content">
                <h1>Contact Mombasa Hamlets FC</h1>
                <p>We'd love to hear from you - get in touch with our team</p>
                <a href="#contact-form" class="btn">Send a Message</a>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section">
            <h2>Get In Touch</h2>
            <div class="contact-container">
                <div class="contact-info">
                    <h3>Contact Information</h3>

                    <div class="contact-detail" id="contact-address">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Stadium Address</h4>
                            <p>Mombasa Hamlets Stadium<br>Nyali Road, Mombasa<br>Kenya</p>
                        </div>
                    </div>

                    <div class="contact-detail" id="contact-phone">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h4>Phone Numbers</h4>
                            <p>Main Office: +254 723 456 789<br>Ticketing: +254 700 123 456<br>Academy: +254 711 987 654
                            </p>
                        </div>
                    </div>

                    <div class="contact-detail" id="contact-email">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email Addresses</h4>
                            <p>General: info@mombasahamletsfc.co.ke<br>Ticket Sales:
                                tickets@mombasahamletsfc.co.ke<br>Partnerships: partnerships@mombasahamletsfc.co.ke</p>
                        </div>
                    </div>

                    <div class="contact-detail" id="contact-hours">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Office Hours</h4>
                            <p>Monday-Friday: 9:00 AM - 5:00 PM<br>Saturday: 10:00 AM - 2:00 PM<br>Sunday: Closed</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form" id="contact-form">
                    <h3>Send Us a Message</h3>
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" class="form-control" required>
                                <option value="">Select a subject</option>
                                <option value="ticketing">Ticketing Inquiry</option>
                                <option value="partnership">Partnership Opportunity</option>
                                <option value="academy">Academy Program</option>
                                <option value="media">Media Inquiry</option>
                                <option value="general">General Question</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message">Your Message</label>
                            <textarea id="message" class="form-control" required></textarea>
                        </div>

                        <button type="submit" class="btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Social Section -->
        <section class="social-section">
            <h2>Join Our Community</h2>
            <p>Follow us for the latest updates, behind-the-scenes content, and exclusive offers</p>
            <div class="social-links-large">
                <a href="https://www.facebook.com/share/17xAkw7a8P/?mibextid=wwXIfr" class="social-link-large" aria-label="Follow us on Facebook" target="_blank" rel="noopener noreferrer">
                    <div class="social-icon facebook">
                        <i class="fab fa-facebook-f"></i>
                    </div>
                    <span>Facebook</span>
                </a>

                <a href="#" class="social-link-large" aria-label="Follow us on Twitter" target="_blank" rel="noopener noreferrer">
                    <div class="social-icon twitter">
                        <i class="fab fa-twitter"></i>
                    </div>
                    <span>Twitter</span>
                </a>

                <a href="https://www.instagram.com/mombasa_hamlets_fc?igsh=MXNtMnd3a21pZXpxZQ%3D%3D&utm_source=qr" class="social-link-large" aria-label="Follow us on Instagram" target="_blank" rel="noopener noreferrer">
                    <div class="social-icon instagram">
                        <i class="fab fa-instagram"></i>
                    </div>
                    <span>Instagram</span>
                </a>

                <a href="https://www.tiktok.com/@mombasa.hamlets.f?_r=1&_t=ZM-931GLvsNsIF" class="social-link-large" aria-label="Follow us on TikTok" target="_blank" rel="noopener noreferrer">
                    <div class="social-icon tiktok">
                        <i class="fab fa-tiktok"></i>
                    </div>
                    <span>TikTok</span>
                </a>

                <a href="https://youtube.com/@mombasahamletsfc?si=QsRvXsxN5NV4K1yA" class="social-link-large" aria-label="Follow us on YouTube" target="_blank" rel="noopener noreferrer">
                    <div class="social-icon youtube">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <span>YouTube</span>
                </a>
            </div>
        </section>

        <!-- Map Section -->
        <section class="map-section">
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.272223050761!2d39.69875727482046!3d-4.054629396002852!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x184012e6e33bc38b%3A0xcb1ed61683ec092!2sNyali%2C%20Mombasa!5e0!3m2!1sen!2ske!4v1691596483176!5m2!1sen!2ske"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <h2>Frequently Asked Questions</h2>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How can I purchase match tickets?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Tickets can be purchased online through our website, at the stadium ticket office, or at
                        authorized retail partners. We recommend buying in advance for popular matches as they often
                        sell out.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I join the youth academy?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We hold open trials twice a year for various age groups. Please check our website for trial dates
                        or email our academy director at academy@mombasahamletsfc.co.ke for more information.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What are the stadium policies?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Our stadium has a clear bag policy, and all attendees are subject to security screening. Outside
                        food and drinks are not permitted. For a complete list of stadium policies, please visit our FAQ
                        page.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How can my company become a sponsor?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We offer various sponsorship packages tailored to different business needs. Please contact our
                        partnerships team at partnerships@mombasahamletsfc.co.ke to discuss opportunities.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Do you offer stadium tours?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! We offer guided stadium tours on non-match days. Tours must be booked in advance through our
                        website or by calling our office. Group discounts are available.</p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/api-config.js"></script>
    <script src="js/auth-notification.js"></script>
    <script src="js/contact.js?v=faq_fix"></script>
    <script type="module" src="js/main.js?v=2" defer></script>
</body>

</html>
