document.addEventListener('DOMContentLoaded', function () {
    initializeGlobalUI();
    initializePageSpecificModules();
    // Load latest news on homepage
    checkAndLoadNews();
    // Load official partners
    checkAndLoadPartners();
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

            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };

            try {
                const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
                const response = await fetch(`${apiUrl}/send-message.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
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
    const partnersGrid = document.getElementById('partners-grid');
    if (!partnersGrid) return;

    try {
        const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
        const response = await fetch(`${apiUrl}/partners.php?status=Active`);

        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        const partners = await response.json();
        if (!Array.isArray(partners) || partners.length === 0) {
            partnersGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: rgba(255,255,255,0.5); padding: 2rem;">No official partners available at the moment.</p>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/logo1.jpeg';

        partnersGrid.innerHTML = partners.map((partner, index) => {
            let logoUrl = fallbackImagePath;
            if (partner.logo_url && partner.logo_url.trim()) {
                let imgPath = partner.logo_url.trim();
                // If it's a full URL, use it
                if (/^https?:\/\//i.test(imgPath)) {
                    logoUrl = imgPath;
                } else {
                    // Clean up paths that might start with / or the project root
                    imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                    imgPath = imgPath.replace(/^\/?frontend\//, '');
                    imgPath = imgPath.replace(/^\//, '');

                    // If it doesn't already start with images/, and it's not empty, it's likely in images/
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
                <div class="sponsor-item-wrapper reveal-on-scroll" style="transition-delay: ${index * 100}ms">
                    <a href="${websiteUrl}" target="_blank" class="sponsor-item" ${partner.website_url ? '' : 'style="cursor: default; pointer-events: auto;" onclick="return false;"'}>
                        <img src="${logoUrl}" alt="${partner.name}" loading="lazy" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                        <span class="sponsor-category">${partner.category}</span>
                    </a>
                </div>
            `;
        }).join('');

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