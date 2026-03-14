document.addEventListener('DOMContentLoaded', () => {
    loadSingleArticle();
});

/**
 * Fetches and displays a single news article based on the ID in the URL.
 */
async function loadSingleArticle() {
    // Get references to the DOM elements
    const loadingEl = document.getElementById('article-loading');
    const containerEl = document.getElementById('article-container');
    const errorEl = document.getElementById('article-error');

    // Get the article ID from the URL query string
    const urlParams = new URLSearchParams(window.location.search);
    const articleId = urlParams.get('id');

    // Show error if no ID is present
    if (!articleId) {
        showErrorState('No article ID provided.');
        return;
    }

    try {
        // Fetch the specific article from the backend
        const response = await fetch(`${window.API_URL}/news.php?id=${articleId}`);

        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('Article not found.');
            }
            throw new Error(`Failed to load article. Status: ${response.status}`);
        }

        const article = await response.json();

        // Populate the page with the fetched article data
        populateArticleData(article);

        // Hide loading and show the article content
        loadingEl.style.display = 'none';
        containerEl.style.display = 'block';

    } catch (error) {
        console.error('Error fetching single article:', error);
        showErrorState(error.message);
    }

    function populateArticleData(article) {
        document.title = `${article.title} - Mombasa Hamlets FC`;

        // Format the date
        const formattedDate = new Date(article.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Construct the image URL - normalize the path
        const fallbackImagePath = '/mombasahamlets_web/frontend/images/logo1.jpeg';
        let imageUrl = fallbackImagePath;
        if (article.image_url && article.image_url.trim()) {
            let imgPath = article.image_url.trim();
            // Remove unnecessary prefixes
            imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
            imgPath = imgPath.replace(/^\/?frontend\//, '');
            imgPath = imgPath.replace(/^\//, '');
            if (imgPath && imgPath.length > 0) {
                const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
                imageUrl = `${base}/frontend/${imgPath}`.replace(/\/{2,}/g, '/');
            }
        }

        // Populate the elements
        document.getElementById('article-title').textContent = article.title;
        document.getElementById('article-meta').textContent = `${article.category} | Published on ${formattedDate}`;

        const imageEl = document.getElementById('article-image');
        imageEl.src = imageUrl;
        imageEl.alt = article.title;
        // Fallback image if the primary one fails to load
        imageEl.onerror = () => {
            imageEl.src = fallbackImagePath;
            imageEl.onerror = null;
        };

        // The content from TinyMCE is already HTML, so we can set it directly
        document.getElementById('article-content').innerHTML = article.content;
    }

    function showErrorState(message) {
        loadingEl.style.display = 'none';
        containerEl.style.display = 'none';

        const errorContent = errorEl.querySelector('p');
        if (errorContent) {
            errorContent.textContent = message;
        }
        errorEl.style.display = 'block';
    }
}