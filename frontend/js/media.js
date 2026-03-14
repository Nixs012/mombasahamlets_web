// Media Gallery Functionality - Fetches from API with Pagination
document.addEventListener('DOMContentLoaded', function () {
    const mediaGrid = document.getElementById('media-grid');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const paginationContainer = document.getElementById('pagination-container');

    let currentFilter = 'all';
    let currentPage = 1;
    const limit = 8;

    // Fetch media from API
    async function fetchMedia(page = 1, category = 'all') {
        if (!mediaGrid) return;

        currentPage = page;
        currentFilter = category;
        mediaGrid.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading media...</div>';
        if (paginationContainer) paginationContainer.innerHTML = '';

        try {
            const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/media.php?page=${page}&limit=${limit}&category=${category}`);

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.data || result.data.length === 0) {
                mediaGrid.innerHTML = '<p style="text-align:center;padding:20px;color:#666;">No media items found in this category.</p>';
                return;
            }

            renderMedia(result.data);
            renderPagination(result.pagination);

            if (page > 1 || category !== 'all') {
                mediaGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            console.error('Failed to fetch media:', error);
            mediaGrid.innerHTML = '<p class="error-message">Could not load media. Please try again later.</p>';
        }
    }

    // Render media items
    function renderMedia(items) {
        const fallbackImagePath = '/mombasahamlets_web/frontend/images/logo1.jpeg';

        mediaGrid.innerHTML = items.map(media => {
            if (media.type === 'photo') {
                return createPhotoCard(media, fallbackImagePath);
            } else {
                return createVideoCard(media, fallbackImagePath);
            }
        }).join('');

        initializeLightbox();
        initializeVideoPlayers();
    }

    function createPhotoCard(media, fallbackImagePath) {
        let imageUrl = fallbackImagePath;
        if (media.image_url && media.image_url.trim()) {
            let imgPath = media.image_url.trim();
            imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
            imgPath = imgPath.replace(/^\/?frontend\//, '');
            imgPath = imgPath.replace(/^\//, '');
            if (imgPath && imgPath.length > 0) {
                const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
                imageUrl = `${base}/frontend/${imgPath}`.replace(/\/{2,}/g, '/');
            }
        }
        const date = media.media_date ? new Date(media.media_date).toLocaleDateString() : '';

        return `
            <div class="photo-card" data-category="${media.category}">
                <img src="${imageUrl}" alt="${media.title}" class="photo-image" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                <div class="photo-overlay">
                    <div class="photo-info">
                        <h3>${media.title}</h3>
                        <p>${media.description || ''}</p>
                    </div>
                </div>
                <div class="media-brand-label">MHFC</div>
            </div>
        `;
    }

    function createVideoCard(media, fallbackImagePath) {
        let thumbnailUrl = fallbackImagePath;
        let isUploadedVideo = false;
        let finalVideoUrl = media.video_url || '';

        if (media.image_url && media.image_url.trim()) {
            let imgPath = media.image_url.trim();
            imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
            imgPath = imgPath.replace(/^\/?frontend\//, '');
            imgPath = imgPath.replace(/^\//, '');

            if (imgPath && imgPath.length > 0) {
                const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
                const fullPath = `${base}/frontend/${imgPath}`.replace(/\/{2,}/g, '/');
                if (imgPath.match(/\.(mp4|webm|ogg|mov)$/i)) {
                    isUploadedVideo = true;
                    finalVideoUrl = fullPath;
                    thumbnailUrl = fallbackImagePath; // Use fallback image if no dedicated thumbnail
                } else {
                    thumbnailUrl = fullPath;
                }
            }
        }

        return `
            <div class="video-card" data-category="${media.category}" data-video-url="${finalVideoUrl}" data-is-uploaded="${isUploadedVideo}">
                <div class="video-thumbnail">
                    ${isUploadedVideo ?
                `<video src="${finalVideoUrl}" preload="metadata" muted class="video-preview"></video>` :
                `<img src="${thumbnailUrl}" alt="${media.title}" onerror="this.onerror=null; this.src='${fallbackImagePath}';">`
            }
                    <div class="video-play-hint"><i class="fas fa-play"></i></div>
                </div>
                <div class="video-info">
                    <h3>${media.title}</h3>
                    <p>${media.description || ''}</p>
                </div>
            </div>
        `;
    }

    function renderPagination(pagination) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        if (!pagination || pagination.pages <= 1) return;

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = `page-btn ${pagination.page === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.onclick = () => {
            if (pagination.page > 1) fetchMedia(pagination.page - 1, currentFilter);
        };
        paginationContainer.appendChild(prevBtn);

        // Page numbers
        for (let i = 1; i <= pagination.pages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${pagination.page === i ? 'active' : ''}`;
            pageBtn.innerText = i;
            pageBtn.onclick = () => fetchMedia(i, currentFilter);
            paginationContainer.appendChild(pageBtn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = `page-btn ${pagination.page === pagination.pages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.onclick = () => {
            if (pagination.page < pagination.pages) fetchMedia(pagination.page + 1, currentFilter);
        };
        paginationContainer.appendChild(nextBtn);
    }

    // Filter functionality
    function initializeFilters() {
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const filter = button.getAttribute('data-filter');
                fetchMedia(1, filter);
            });
        });
    }

    // Unified Lightbox functionality for photos and videos
    function initializeLightbox() {
        const mediaCards = document.querySelectorAll('.photo-card, .video-card');
        const lightbox = document.getElementById('lightbox');
        const lightboxBody = document.querySelector('.lightbox-body');
        const lightboxCaption = document.querySelector('.lightbox-caption');
        const lightboxClose = document.querySelector('.lightbox-close');
        const lightboxPrev = document.querySelector('.lightbox-prev');
        const lightboxNext = document.querySelector('.lightbox-next');

        if (!lightbox || !lightboxBody) return;

        let currentIndex = 0;
        const items = Array.from(mediaCards);

        mediaCards.forEach((card, index) => {
            card.onclick = () => {
                currentIndex = index;
                updateLightbox();
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            };
        });

        if (lightboxClose) {
            lightboxClose.onclick = () => {
                closeLightbox();
            };
        }

        if (lightboxPrev) {
            lightboxPrev.onclick = () => {
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                updateLightbox();
            };
        }

        if (lightboxNext) {
            lightboxNext.onclick = () => {
                currentIndex = (currentIndex + 1) % items.length;
                updateLightbox();
            };
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            lightboxBody.innerHTML = ''; // Clear content to stop videos
            document.body.style.overflow = '';
        }

        function updateLightbox() {
            const card = items[currentIndex];
            if (!card) return;

            lightboxBody.innerHTML = ''; // Reset body
            lightboxBody.classList.add('loading');

            const info = card.querySelector('.photo-info, .video-info');
            const title = info?.querySelector('h3')?.textContent || '';
            const description = info?.querySelector('p')?.textContent || '';
            const isVideo = card.classList.contains('video-card');

            if (isVideo) {
                const videoUrl = card.getAttribute('data-video-url');
                const isUploaded = card.getAttribute('data-is-uploaded') === 'true';

                if (isUploaded) {
                    const video = document.createElement('video');
                    video.src = videoUrl;
                    video.controls = true;
                    video.autoplay = true;
                    video.className = 'lightbox-video';
                    video.onloadeddata = () => lightboxBody.classList.remove('loading');
                    lightboxBody.appendChild(video);
                } else if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                    const videoId = extractYouTubeId(videoUrl);
                    if (videoId) {
                        const iframe = document.createElement('iframe');
                        iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                        iframe.className = 'lightbox-iframe';
                        iframe.setAttribute('allowfullscreen', 'true');
                        iframe.setAttribute('allow', 'autoplay; fullscreen');
                        iframe.onload = () => lightboxBody.classList.remove('loading');
                        lightboxBody.appendChild(iframe);
                    }
                } else if (videoUrl.includes('vimeo.com')) {
                    const videoId = extractVimeoId(videoUrl);
                    if (videoId) {
                        const iframe = document.createElement('iframe');
                        iframe.src = `https://player.vimeo.com/video/${videoId}?autoplay=1`;
                        iframe.className = 'lightbox-iframe';
                        iframe.setAttribute('allowfullscreen', 'true');
                        iframe.setAttribute('allow', 'autoplay; fullscreen');
                        iframe.onload = () => lightboxBody.classList.remove('loading');
                        lightboxBody.appendChild(iframe);
                    }
                } else {
                    lightboxBody.classList.remove('loading');
                    const link = document.createElement('a');
                    link.href = videoUrl;
                    link.target = '_blank';
                    link.className = 'lightbox-link';
                    link.textContent = 'View Video';
                    lightboxBody.appendChild(link);
                }
            } else {
                const img = card.querySelector('.photo-image');
                if (img) {
                    const lightboxImg = document.createElement('img');
                    lightboxImg.src = img.src;
                    lightboxImg.alt = img.alt;
                    lightboxImg.className = 'lightbox-image';
                    lightboxImg.onload = () => lightboxBody.classList.remove('loading');
                    lightboxBody.appendChild(lightboxImg);
                }
            }

            if (lightboxCaption) {
                lightboxCaption.textContent = `${title} ${description ? ' - ' + description : ''}`;
            }
        }

        lightbox.onclick = (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        };

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') lightboxPrev.click();
            if (e.key === 'ArrowRight') lightboxNext.click();
        });
    }

    // Video player functionality - removed legacy handlers as lightbox now handles it
    function initializeVideoPlayers() {
        // Handled by unified lightbox
    }

    function extractYouTubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    function extractVimeoId(url) {
        const regExp = /(?:vimeo)\.com.*(?:videos|video|channels|)\/([\d]+)/i;
        const match = url.match(regExp);
        return match ? match[1] : null;
    }

    // Initialize
    initializeFilters();
    fetchMedia(1, 'all');
});
