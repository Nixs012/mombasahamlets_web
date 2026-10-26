document.addEventListener('DOMContentLoaded', function () {
    initializeGlobalUI();
    initializePageSpecificModules();
    // Load latest news on homepage
    checkAndLoadNews();
    // Load official partners
    checkAndLoadPartners();
    // Load next match countdown
    loadNextMatch();
    // Load featured players
    loadFeaturedPlayers();
    // Load shop highlights
    loadFeaturedProducts();
    setupScrollAnimations();
    setupNewsletter();
});

/**
 * Initializes global UI components present on all pages.
 */
function initializeGlobalUI() {
    setupMobileSidebar();
    setupHeaderScrollEffect();
    setActiveLink();
    setupSmoothScrolling();
    setupCart();
    animateHeroElements();
    animateHeroElements();
}

/**
 * Dynamically loads and initializes modules for specific pages.
 */
function initializePageSpecificModules() {
    const page = document.body.dataset.page;
    if (!page) return;

    switch (page) {
        case 'contact':
            setupContactPage();
            break;
        // Add cases for other pages like 'about', 'shop' if they get specific JS
    }
}

/**
 * Sets up the mobile sidebar functionality.
 */
function setupMobileSidebar() {
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const sidebarClose = document.querySelector('.sidebar-close');

    if (!mobileToggle || !mobileSidebar || !sidebarOverlay || !sidebarClose) return;

    const toggleSidebar = () => {
        const isOpening = !mobileSidebar.classList.contains('active');
        mobileSidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open', isOpening);
        document.body.style.overflow = isOpening ? 'hidden' : '';
        mobileToggle.setAttribute('aria-expanded', isOpening);
        mobileSidebar.setAttribute('aria-hidden', !isOpening);
        isOpening ? sidebarClose.focus() : mobileToggle.focus();
    };

    [mobileToggle, sidebarClose, sidebarOverlay].forEach(el => el.addEventListener('click', toggleSidebar));
}

/**
 * Adds a scroll effect to the main header with throttling for performance.
 */
function setupHeaderScrollEffect() {
    const header = document.querySelector('.header');
    if (!header) return;

    let lastScroll = 0;
    let ticking = false;

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const currentScroll = window.scrollY;
                if (currentScroll <= 0) {
                    header.classList.remove('scrolled-up', 'scrolled-down');
                } else {
                    header.classList.toggle('scrolled-down', currentScroll > lastScroll && currentScroll > 50);
                    header.classList.toggle('scrolled-up', currentScroll < lastScroll);
                }
                lastScroll = currentScroll;
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

/**
 * Highlights the active navigation link based on the current page.
 */
function setActiveLink() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const links = document.querySelectorAll('.desktop-nav a, .sidebar-nav a');
    links.forEach(link => {
        const linkPath = (link.getAttribute('href') || '').split('/').pop();
        const isActive = linkPath === currentPath;
        link.classList.toggle('active', isActive);
        if (isActive) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
}

/**
 * Adds a pop-in animation to hero elements.
 */
function animateHeroElements() {
    document.querySelectorAll('.animate-pop-in').forEach((el, index) => {
        el.style.animationDelay = `${index * 0.2 + 0.2}s`;
    });
}

/**
 * Sets up accordion functionality for FAQ items.
 */
function setupFaq() {
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const item = question.parentElement;
            const answer = item.querySelector('.faq-answer');
            const icon = question.querySelector('i');
            const isActive = item.classList.toggle('active');

            answer.style.maxHeight = isActive ? answer.scrollHeight + "px" : null;
            if (icon) {
                icon.classList.toggle('fa-chevron-down', !isActive);
                icon.classList.toggle('fa-chevron-up', isActive);
            }
        });
    });
}

/**
 * Sets up the contact page functionality (form and FAQ).
 */
function setupContactPage() {
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;

            const csrfTokenInput = document.querySelector('#contactForm input[name="csrf_token"]');
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };
            if (csrfTokenInput) {
                formData.csrf_token = csrfTokenInput.value;
            }

            try {
                const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
                const response = await fetch(`${apiUrl}/send-message.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfTokenInput ? csrfTokenInput.value : ''
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Thank you for your message! We will get back to you soon.');
                    this.reset();
                } else {
                    alert('Error: ' + (result.message || 'Unable to send message.'));
                }
            } catch (error) {
                console.error('Contact form submission error:', error);
                alert('An error occurred. Please try again later.');
            } finally {
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }
    setupFaq(); // FAQs are on the contact page
}

/**
 * Enables smooth scrolling for on-page anchor links.
 */
/**
 * Enables smooth scrolling for on-page anchor links.
 */
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            e.preventDefault();
            const target = document.querySelector(href);
            target?.scrollIntoView({ behavior: 'smooth' });
        });
    });
}

/**
 * Sets up shopping cart functionality.
 */
function setupCart() {
    const cartIcon = document.querySelector('.cart-icon');
    const cartSidebar = document.querySelector('.cart-sidebar');
    const cartOverlay = document.querySelector('.cart-overlay');
    const cartClose = document.querySelector('.cart-close');

    if (!cartIcon || !cartSidebar || !cartOverlay || !cartClose) return;

    const toggleCart = () => {
        const isActive = cartSidebar.classList.toggle('active');
        cartOverlay.classList.toggle('active', isActive);
        document.body.classList.toggle('no-scroll', isActive);
    };

    cartIcon.addEventListener('click', toggleCart);
    cartOverlay.addEventListener('click', toggleCart);
    cartClose.addEventListener('click', toggleCart);
}

/**
 * Sets up FAQ toggle functionality.
 */
function setupFAQToggle() {
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const item = question.parentElement;
            const answer = item.querySelector('.faq-answer');
            const icon = question.querySelector('i');

            item.classList.toggle('active');
            if (item.classList.contains('active')) {
                answer.style.maxHeight = answer.scrollHeight + "px";
                if (icon) {
                    icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                }
            } else {
                answer.style.maxHeight = null;
                if (icon) {
                    icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            }
        });
    });
}

/**
 * Loads and displays the latest 4 news articles on the homepage
 */
async function loadLatestNews() {
    const newsGrid = document.getElementById('latest-news-grid');
    if (!newsGrid) {
        console.log('Latest news grid not found');
        return;
    }

    try {
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        console.log('Fetching news from:', `${apiUrl}/news.php`);
        const response = await fetch(`${apiUrl}/news.php`);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const articles = await response.json();
        console.log('Received articles:', articles.length);

        if (!Array.isArray(articles) || articles.length === 0) {
            newsGrid.innerHTML = '<p>No news articles available at the moment.</p>';
            return;
        }

        // Articles are already sorted by created_at DESC from the API
        // Display only the latest 4 articles
        const latestArticles = articles.slice(0, 4);

        if (latestArticles.length === 0) {
            newsGrid.innerHTML = '<p>No news articles available at the moment.</p>';
            return;
        }

        console.log('Displaying', latestArticles.length, 'latest articles');
        const fallbackImagePath = '/mombasahamlets_web/frontend/images/logo1.jpeg';

        newsGrid.innerHTML = latestArticles.map(article => {
            let imageUrl = fallbackImagePath;
            if (article.image_url && article.image_url.trim()) {
                let imgPath = article.image_url.trim();
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');
                if (imgPath && imgPath.length > 0) {
                    imageUrl = `/mombasahamlets_web/frontend/${imgPath}`;
                }
            }

            const date = new Date(article.created_at);
            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            return `
                <article class="news-article" data-category="${article.category || 'general'}">
                    <img src="${imageUrl}" alt="${article.title}" class="article-image" loading="lazy" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                    <div class="article-content" style="contain: content;">
                        <div class="article-date">${formattedDate} | ${article.category || 'General'}</div>
                        <h2 class="article-title">${article.title}</h2>
                        <p class="article-excerpt">${article.summary || article.content?.substring(0, 150) + '...' || 'Read more about this story.'}</p>
                        <a href="news-single.php?id=${article.id}" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
            `;
        }).join('');
    } catch (error) {
        console.error('Failed to load latest news:', error);
        newsGrid.innerHTML = '<p>Could not load latest news. Please try again later.</p>';
    }
}

/**
 * Loads and displays official partners on the homepage
 */
async function loadPartners() {
    const partnersStrip = document.getElementById('partners-strip');
    if (!partnersStrip) return;

    try {
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        const response = await fetch(`${apiUrl}/partners.php?status=Active`);

        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        const partners = await response.json();
        if (!Array.isArray(partners) || partners.length === 0) {
            partnersStrip.innerHTML = '<p style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.7);">No official partners available at the moment.</p>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/logo1.jpeg';

        // Create marquee strip by duplicating partners for continuous scroll
        const partnerItems = partners.map(partner => {
            let logoUrl = fallbackImagePath;
            if (partner.logo_url && partner.logo_url.trim()) {
                let imgPath = partner.logo_url.trim();
                if (/^https?:\/\//i.test(imgPath)) {
                    logoUrl = imgPath;
                } else {
                    imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                    imgPath = imgPath.replace(/^\/?frontend\//, '');
                    imgPath = imgPath.replace(/^\//, '');

                    if (imgPath && !imgPath.startsWith('images/')) {
                        imgPath = 'images/' + imgPath;
                    }

                    if (imgPath) {
                        logoUrl = `/mombasahamlets_web/frontend/${imgPath}`;
                    }
                }
            }

            const websiteUrl = partner.website_url ? (partner.website_url.startsWith('http') ? partner.website_url : `https://${partner.website_url}`) : '#';

            return `
                <div class="sponsor-marquee-item">
                    <a href="${websiteUrl}" target="_blank" rel="noopener noreferrer">
                        <img src="${logoUrl}" alt="${partner.name}" loading="lazy"
                             onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                    </a>
                </div>
            `;
        }).join('');

        // Duplicate items for continuous scrolling effect
        const allItems = partnerItems + partnerItems;
        partnersStrip.innerHTML = `<div class="sponsors-marquee-container">${allItems}</div>`;

        // Auto-pause marquee on hover
        const container = partnersStrip.querySelector('.sponsors-marquee-container');
        if (container) {
            container.addEventListener('mouseenter', () => container.style.animationPlayState = 'paused');
            container.addEventListener('mouseleave', () => container.style.animationPlayState = 'running');
        }
    }

        // Trigger observer after content is added
        setupScrollAnimations();

    } catch (error) {
        console.error('Failed to load partners:', error);
        partnersGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #e63946; padding: 2rem;">Could not load official partners. Please try again later.</p>';
    }
}

/**
 * Setup scroll animations for elements with 'reveal-on-scroll' class
 */
function setupScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Once it's visible, we don't need to observe it anymore
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal-on-scroll:not(.visible)').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Checks if the partners grid exists and loads partners
 */
function checkAndLoadPartners() {
    const partnersGrid = document.getElementById('partners-grid');
    if (partnersGrid) {
        loadPartners();
    }
}

// Load latest news on homepage - check if the news grid exists
function checkAndLoadNews() {
    // Simply check if the element exists - if it does, we're on the homepage
    const newsGrid = document.getElementById('latest-news-grid');
    if (newsGrid) {
        loadLatestNews();
    }
}

/**
 * Sets up the newsletter subscription form.
 */
function setupNewsletter() {
    const form = document.getElementById('newsletterForm');
    const emailInput = document.getElementById('newsletterEmail');
    const messageDiv = document.getElementById('newsletterMessage');

    if (!form || !emailInput || !messageDiv) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const email = emailInput.value.trim();

        if (!email) return;

        // Visual feedback - disable button
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Subscribing...';
        messageDiv.style.display = 'none';

        try {
            const response = await fetch('/mombasahamlets_web/backend/subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email })
            });

            const data = await response.json();

            if (response.ok) {
                messageDiv.textContent = data.message;
                messageDiv.style.color = '#4caf50'; // Green for success
                form.reset();
            } else {
                messageDiv.textContent = data.message || 'An error occurred. Please try again.';
                messageDiv.style.color = '#f44336'; // Red for error
            }

        } catch (error) {
            console.error('Newsletter error:', error);
            messageDiv.textContent = 'Network error. Please try again later.';
            messageDiv.style.color = '#f44336';
        } finally {
            messageDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;

            // Clear success message after 5 seconds
            if (messageDiv.style.color === 'rgb(76, 175, 80)' || messageDiv.style.color === '#4caf50') {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        }
    });
}

/**
 * Loads the next upcoming match and sets up a countdown timer
 */
async function loadNextMatch() {
    const countdownEl = document.getElementById('next-match-countdown');
    const timerEl = document.getElementById('countdown-timer');
    const teamsEl = document.getElementById('next-match-teams');
    const venueEl = document.getElementById('next-match-venue');

    if (!countdownEl || !timerEl) return;

    try {
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        const response = await fetch(`${apiUrl}/matches.php?status=upcoming&limit=1`);
        const result = await response.json();
        const matches = Array.isArray(result) ? result : (result.data || []);

        if (matches.length > 0) {
            const match = matches[0];
            if (teamsEl) teamsEl.textContent = `${match.home_team} vs ${match.away_team}`;
            if (venueEl) venueEl.textContent = match.venue || 'TBA';

            const matchDate = new Date(match.match_date);
            startCountdown(matchDate);
        } else {
            if (teamsEl) teamsEl.textContent = 'No upcoming matches scheduled';
            if (timerEl) timerEl.innerHTML = '<span>No match scheduled</span>';
        }
    } catch (error) {
        console.error('Failed to load next match:', error);
        if (timerEl) timerEl.innerHTML = '<span>Check back soon</span>';
    }
}

/**
 * Starts a countdown timer to a specific date
 */
function startCountdown(targetDate) {
    const updateCountdown = () => {
        const now = new Date();
        const diff = targetDate - now;

        if (diff <= 0) {
            document.getElementById('countdown-days').textContent = '00';
            document.getElementById('countdown-hours').textContent = '00';
            document.getElementById('countdown-minutes').textContent = '00';
            document.getElementById('countdown-seconds').textContent = '00';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById('countdown-days').textContent = String(days).padStart(2, '0');
        document.getElementById('countdown-hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('countdown-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('countdown-seconds').textContent = String(seconds).padStart(2, '0');
    };

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

/**
 * Loads and displays featured players on the homepage
 */
async function loadFeaturedPlayers() {
    const playersGrid = document.getElementById('featured-players-grid');
    if (!playersGrid) return;

    try {
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        const response = await fetch(`${apiUrl}/players.php?status=active&limit=4`);
        const result = await response.json();
        const players = Array.isArray(result) ? result : (result.data || []);

        if (!players.length) {
            playersGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #777;">No featured players at the moment.</p>';
            return;
        }

        const fallbackImage = '/mombasahamlets_web/frontend/images/logo1.jpeg';

        playersGrid.innerHTML = players.slice(0, 4).map(player => `
            <div class="player-card" data-player-id="${player.id}">
                <div class="player-image-container">
                    <img src="${player.photo_url || fallbackImage}"
                         alt="${player.first_name} ${player.last_name} - Mombasa Hamlets FC Player"
                         class="player-photo"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='${fallbackImage}';">
                </div>
                <div class="player-info">
                    <div class="player-number">#${player.jersey_number || '--'}</div>
                    <h3 class="player-name">${player.first_name} ${player.last_name}</h3>
                    <div class="player-position">${player.position || 'Position'}</div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Failed to load featured players:', error);
        playersGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #777;">Could not load featured players. Please try again later.</p>';
    }
}

/**
 * Loads and displays featured products on the homepage
 */
async function loadFeaturedProducts() {
    const productsGrid = document.getElementById('shop-highlights-grid');
    if (!productsGrid) return;

    try {
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        const response = await fetch(`${apiUrl}/products.php?featured=true&limit=4`);
        const result = await response.json();
        const products = Array.isArray(result) ? result : (result.data || []);

        if (!products.length) {
            productsGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #777;">No products available at the moment.</p>';
            return;
        }

        const fallbackImage = '/mombasahamlets_web/frontend/images/logo1.jpeg';

        productsGrid.innerHTML = products.slice(0, 4).map(product => `
            <div class="shop-product-card" data-product-id="${product.id}">
                <div class="product-image-container">
                    <img src="${product.image_url || fallbackImage}"
                         alt="${product.name} - KSh ${product.price}"
                         class="product-image"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='${fallbackImage}';">
                    ${product.featured ? '<span class="product-badge">Featured</span>' : ''}
                </div>
                <div class="product-info">
                    <h3 class="product-title">${product.name}</h3>
                    <div class="product-price">
                        <span class="price">KSh ${product.price}</span>
                        ${product.sale_price ? `<span class="sale-price">KSh ${product.sale_price}</span>` : ''}
                    </div>
                    <button class="quick-view-btn" onclick="quickView(${product.id})" aria-label="Quick view ${product.name}">
                        <i class="fas fa-eye"></i> Quick View
                    </button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Failed to load featured products:', error);
        productsGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #777;">Could not load products. Please try again later.</p>';
    }
}

/**
 * Opens quick view modal for a product
 */
function quickView(productId) {
    // Redirect to product page - to be enhanced with a modal later
    window.location.href = `shop.php?product=${productId}`;
}