// backend/media.js
import { showNotification } from './admin.js';

const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

// Function to fetch and display media
async function fetchMedia() {
    const mediaTableBody = document.querySelector('#media-tab .admin-table tbody');
    if (!mediaTableBody) return;

    // Show loading state
    mediaTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading media...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/media.php`);
        if (!response.ok) throw new Error('Failed to fetch media');
        const mediaItems = await response.json();

        mediaTableBody.innerHTML = ''; // Clear loading state

        if (!mediaItems || mediaItems.length === 0) {
            mediaTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;">No media items found. Add one using the form above.</td></tr>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/29.jpg';

        mediaItems.forEach(media => {
            // Build image URL
            let imageUrl = fallbackImagePath;
            if (media.image_url && media.image_url.trim()) {
                let imgPath = media.image_url.trim();
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');
                if (imgPath && imgPath.length > 0) {
                    imageUrl = `/mombasahamlets_web/frontend/${imgPath}`;
                }
            }

            let mediaContent = '';
            const isVideo = (media.type === 'video') ||
                (imageUrl.toLowerCase().match(/\.(mp4|webm|ogg|mov)$/));

            if (isVideo) {
                mediaContent = `<div class="table-media-image" style="display:flex;align-items:center;justify-content:center;background:#f0f0f0;color:#666;">
                    <i class="fas fa-video" style="font-size:20px;"></i>
                </div>`;
            } else {
                mediaContent = `<img src="${imageUrl}" alt="${media.title}" class="table-media-image" onerror="this.onerror=null; this.src='${fallbackImagePath}';">`;
            }

            const row = `
                <tr>
                    <td>
                        ${mediaContent}
                    </td>
                    <td>${media.title}</td>
                    <td>${media.category}</td>
                    <td>${media.type || 'photo'}</td>
                    <td>${new Date(media.media_date || media.created_at).toLocaleDateString()}</td>
                    <td class="actions">
                        <button class="btn-edit" data-id="${media.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" data-id="${media.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            mediaTableBody.insertAdjacentHTML('beforeend', row);
        });

        // Re-attach event listeners after rendering
        document.querySelectorAll('#media-tab .btn-edit').forEach(btn => {
            btn.addEventListener('click', () => handleEdit(mediaItems.find(m => m.id == btn.dataset.id)));
        });
        document.querySelectorAll('#media-tab .btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleDelete(btn.dataset.id));
        });

    } catch (error) {
        console.error('Error fetching media:', error);
        mediaTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> Failed to load media. Please try again.</td></tr>';
        showNotification('Could not load media.', 'error');
    }
}

// Function to handle form submission for adding/editing media
async function handleMediaSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const mediaId = form.dataset.editingId;

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    const mediaData = {
        title: document.getElementById('media-title').value,
        description: document.getElementById('media-description').value,
        category: document.getElementById('media-category').value,
        type: document.getElementById('media-type').value,
        media_date: document.getElementById('media-date').value,
        image_url: document.getElementById('media-image-url').value || null,
        video_url: document.getElementById('media-video-url').value || null
    };

    const method = mediaId ? 'PUT' : 'POST';
    const url = mediaId ? `${API_URL}/media.php?id=${mediaId}` : `${API_URL}/media.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(mediaData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save media');
        }

        showNotification(`Media ${mediaId ? 'updated' : 'added'} successfully!`, 'success');
        form.reset();
        delete form.dataset.editingId; // Clear editing state
        const mediaTabH2 = document.querySelector('#media-tab h2');
        if (mediaTabH2) mediaTabH2.textContent = 'Media Management';
        const mediaFormTitle = document.getElementById('media-form-title');
        if (mediaFormTitle) mediaFormTitle.textContent = 'Add New Media';
        // Hide video URL field if not video type
        const videoUrlGroup = document.getElementById('media-video-url-group');
        if (videoUrlGroup) videoUrlGroup.style.display = 'none';
        // Clear image preview if exists
        const imageInput = document.getElementById('media-image-url');
        if (imageInput) {
            const preview = imageInput.parentNode.querySelector('img');
            if (preview) preview.src = '';
        }
        // Refresh the list after a short delay
        setTimeout(() => {
            fetchMedia();
        }, 300);
    } catch (error) {
        console.error('Error saving media:', error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

// Function to handle editing media
function handleEdit(media) {
    const form = document.getElementById('media-form');
    form.dataset.editingId = media.id;
    document.getElementById('media-form-title').textContent = 'Edit Media';

    document.getElementById('media-title').value = media.title || '';
    document.getElementById('media-description').value = media.description || '';
    document.getElementById('media-category').value = media.category || 'photos';
    document.getElementById('media-type').value = media.type || 'photo';
    document.getElementById('media-date').value = media.media_date ? media.media_date.split('T')[0] : (media.created_at ? media.created_at.split('T')[0] : '');
    document.getElementById('media-image-url').value = media.image_url || '';
    document.getElementById('media-video-url').value = media.video_url || '';

    // Show/hide video URL field based on type
    const videoUrlGroup = document.getElementById('media-video-url-group');
    const mediaType = document.getElementById('media-type').value;
    if (videoUrlGroup) {
        videoUrlGroup.style.display = mediaType === 'video' ? 'block' : 'none';
    }

    form.scrollIntoView({ behavior: 'smooth' });
}

// Function to handle deleting media
async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this media item?')) return;

    try {
        const response = await fetch(`${API_URL}/media.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete media');
        showNotification('Media deleted successfully!', 'success');
        await fetchMedia(); // Refresh the list
    } catch (error) {
        console.error('Error deleting media:', error);
        showNotification(`Could not delete the media: ${error.message}`, 'error');
    }
}

export function initMedia() {
    const mediaForm = document.getElementById('media-form');
    const mediaTab = document.getElementById('media-tab');

    if (mediaForm) {
        mediaForm.addEventListener('submit', handleMediaSubmit);

        // Show/hide video URL field based on media type
        const mediaTypeSelect = document.getElementById('media-type');
        const videoUrlGroup = document.getElementById('media-video-url-group');
        if (mediaTypeSelect && videoUrlGroup) {
            mediaTypeSelect.addEventListener('change', function () {
                videoUrlGroup.style.display = this.value === 'video' ? 'block' : 'none';
            });
        }

        // Cancel button
        const cancelBtn = document.getElementById('cancel-media-edit');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                mediaForm.reset();
                delete mediaForm.dataset.editingId;
                const mediaFormTitle = document.getElementById('media-form-title');
                if (mediaFormTitle) mediaFormTitle.textContent = 'Add New Media';
                if (videoUrlGroup) videoUrlGroup.style.display = 'none';
            });
        }
    }

    // Initial fetch if the tab is already active on load
    if (mediaTab && mediaTab.classList.contains('active')) {
        fetchMedia();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'media') {
            fetchMedia();
        }
    });
}

