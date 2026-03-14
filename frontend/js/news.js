document.addEventListener('DOMContentLoaded', () => {
    const newsGrid = document.querySelector('.news-grid');
    const categoryButtons = document.querySelectorAll('.category-btn');

    // Use existing pagination container or create one
    let paginationContainer = document.getElementById('pagination-container');
    if (!paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.id = 'pagination-container';
        paginationContainer.className = 'pagination-container';
        if (newsGrid) {
            newsGrid.after(paginationContainer);
        }
    }

    let currentPage = 1;
    let currentCategory = 'all';
    const limit = 9;

    /**
     * Fetches news articles from the backend API and displays them.
     */
    async function fetchAndDisplayNews(page = 1, category = 'all') {
        if (!newsGrid) return;

        currentPage = page;
        currentCategory = category;
        newsGrid.innerHTML = '<p class="loading-message">Loading latest news...</p>';
        paginationContainer.innerHTML = '';

        try {
            const response = await fetch(`${window.API_URL}/news.php?page=${page}&limit=${limit}&category=${category}`);

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.data || result.data.length === 0) {
                newsGrid.innerHTML = '<p>No news articles found at the moment. Please check back later.</p>';
                return;
            }

            renderArticles(result.data);
            renderPagination(result.pagination);

            // Scroll to top of grid on page change
            if (page > 1 || category !== 'all') {
                newsGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

        } catch (error) {
            console.error('Failed to fetch news:', error);
            newsGrid.innerHTML = '<p class="error-message">Could not load news articles. Please try again later.</p>';
        }
    }

    /**
     * Renders an array of article objects into the news grid.
     */
    function renderArticles(articles) {
        newsGrid.innerHTML = '';
        articles.forEach(article => {
            const articleElement = document.createElement('article');
            articleElement.className = 'news-article';
            articleElement.dataset.category = article.category.toLowerCase().replace(/\s+/g, '-');

            const formattedDate = new Date(article.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const fallbackImagePath = '/mombasahamlets_web/frontend/images/logo1.jpeg';
            let imageUrl = fallbackImagePath;
            if (article.image_url && article.image_url.trim()) {
                let imgPath = article.image_url.trim();
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');
                if (imgPath && imgPath.length > 0) {
                    const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
                    imageUrl = `${base}/frontend/${imgPath}`.replace(/\/{2,}/g, '/');
                }
            }

            articleElement.innerHTML = `
                <img src="${imageUrl}" alt="" class="article-image" onerror="this.src='${fallbackImagePath}'; this.onerror=null;">
                <div class="article-content">
                    <div class="article-date"></div>
                    <h2 class="article-title"></h2>
                    <p class="article-excerpt"></p>
                    <a href="news-single.php?id=${article.id}" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            `;

            // Safe assignments
            articleElement.querySelector('.article-date').textContent = `${formattedDate} | ${article.category}`;
            articleElement.querySelector('.article-title').textContent = article.title;
            articleElement.querySelector('.article-excerpt').textContent = article.summary;
            articleElement.querySelector('.article-image').alt = article.title;

            newsGrid.appendChild(articleElement);
        });
    }

    /**
     * Renders pagination controls.
     */
    function renderPagination(pagination) {
        paginationContainer.innerHTML = '';
        if (!pagination || pagination.pages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = `page-btn ${pagination.page === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.onclick = () => {
            if (pagination.page > 1) fetchAndDisplayNews(pagination.page - 1, currentCategory);
        };
        paginationContainer.appendChild(prevBtn);

        // Page numbers
        for (let i = 1; i <= pagination.pages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${pagination.page === i ? 'active' : ''}`;
            pageBtn.innerText = i;
            pageBtn.onclick = () => fetchAndDisplayNews(i, currentCategory);
            paginationContainer.appendChild(pageBtn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = `page-btn ${pagination.page === pagination.pages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.onclick = () => {
            if (pagination.page < pagination.pages) fetchAndDisplayNews(pagination.page + 1, currentCategory);
        };
        paginationContainer.appendChild(nextBtn);
    }

    // Add click listeners to category filter buttons
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            const category = button.dataset.category;
            fetchAndDisplayNews(1, category);
        });
    });

    // Initial fetch
    fetchAndDisplayNews(1, 'all');
});
