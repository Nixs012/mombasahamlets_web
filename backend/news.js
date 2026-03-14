import { showNotification } from './admin.js';
const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

export function initNews() {
    const newsForm = document.getElementById('news-form');

    if (newsForm) {
        newsForm.addEventListener('submit', handleNewsSubmit);
        newsForm.addEventListener('reset', () => {
            delete newsForm.dataset.editingId;
            const newsTabH2 = document.querySelector('#news-tab h2');
            if (newsTabH2) newsTabH2.textContent = 'Add News Article';
            // Clear image preview
            const imageInput = document.getElementById('image');
            if (imageInput) {
                const preview = imageInput.parentNode.querySelector('img');
                if (preview) preview.src = '';
            }
        });
    }

    // Initial fetch if the tab is already active on load
    const newsTab = document.getElementById('news-tab');
    if (newsTab && newsTab.classList.contains('active')) {
        fetchNews();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'news') {
            fetchNews();
        }
    });
}

async function fetchNews() {
    const newsTableBody = document.querySelector('#news-tab .admin-table tbody');
    if (!newsTableBody) return;

    // Show loading state
    newsTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading news...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/news.php`);
        if (!response.ok) throw new Error('Failed to fetch news');
        const newsItems = await response.json();

        newsTableBody.innerHTML = '';

        if (!newsItems || newsItems.length === 0) {
            newsTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#666;">No news articles found. Add one using the form above.</td></tr>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/29.jpg';

        newsItems.forEach(article => {
            // Build image URL
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

            const row = `
                <tr>
                    <td>
                        <img src="${imageUrl}" alt="${article.title}" class="table-news-image" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                    </td>
                    <td>${article.title}</td>
                    <td>${article.category}</td>
                    <td>${new Date(article.created_at).toLocaleDateString()}</td>
                    <td class="actions">
                        <button class="btn-edit" data-id="${article.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" data-id="${article.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            newsTableBody.insertAdjacentHTML('beforeend', row);
        });

        document.querySelectorAll('#news-tab .btn-edit').forEach(btn => {
            btn.addEventListener('click', () => handleEdit(btn.dataset.id));
        });
        document.querySelectorAll('#news-tab .btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleDelete(btn.dataset.id));
        });
    } catch (error) {
        console.error('Error fetching news:', error);
        newsTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> Failed to load news. Please try again.</td></tr>';
        showNotification('Could not load news articles.', 'error');
    }
}

async function handleNewsSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const articleId = form.dataset.editingId;

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    let imageUrl = document.getElementById('image').value;
    // Normalize image_url - remove unnecessary prefixes
    if (imageUrl) {
        imageUrl = imageUrl.replace(/^\/mombasahamlets_web\//, '');
        imageUrl = imageUrl.replace(/^\/?frontend\//, '');
        imageUrl = imageUrl.replace(/^\//, '');
        if (imageUrl.length === 0) {
            imageUrl = null;
        }
    }

    const articleData = {
        title: document.getElementById('title').value,
        image_url: imageUrl,
        content: document.getElementById('content').value, // Assuming a content field exists
        summary: document.getElementById('summary').value,
        category: document.getElementById('category').value,
        status: 'published'
    };

    const method = articleId ? 'PUT' : 'POST';
    const url = articleId ? `${API_URL}/news.php?id=${articleId}` : `${API_URL}/news.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(articleData)
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save article');
        }

        showNotification(`Article ${articleId ? 'updated' : 'added'} successfully!`, 'success');
        form.reset();
        delete form.dataset.editingId;
        const newsTabH2 = document.querySelector('#news-tab h2');
        if (newsTabH2) newsTabH2.textContent = 'Add News Article';
        // Clear image preview if exists
        const imageInput = document.getElementById('image');
        if (imageInput) {
            const preview = imageInput.parentNode.querySelector('img');
            if (preview) preview.src = '';
        }
        // Refresh the list after a short delay
        setTimeout(() => {
            fetchNews();
        }, 300);
    } catch (error) {
        console.error('Error saving article:', error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

async function handleEdit(id) {
    try {
        const response = await fetch(`${API_URL}/news.php?id=${id}`);
        if (!response.ok) throw new Error('Failed to fetch article');
        const article = await response.json();

        const form = document.getElementById('news-form');
        form.dataset.editingId = id;
        document.getElementById('title').value = article.title;
        document.getElementById('image').value = article.image_url;
        document.getElementById('summary').value = article.summary;
        document.getElementById('content').value = article.content;
        document.getElementById('category').value = article.category;
        document.querySelector('#news-tab h2').textContent = 'Edit News Article';
        form.scrollIntoView({ behavior: 'smooth' });
    } catch (error) {
        console.error('Error fetching article for edit:', error);
    }
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this article?')) return;

    try {
        const response = await fetch(`${API_URL}/news.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete article');
        showNotification('Article deleted successfully!', 'success'); // Changed from alert
        fetchNews();
    } catch (error) {
        console.error('Error deleting article:', error);
        showNotification(`Could not delete article: ${error.message}`, 'error'); // Changed from alert
    }
}
