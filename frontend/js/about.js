document.addEventListener('DOMContentLoaded', async () => {
    const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

    // Fetch General Settings
    try {
        const res = await fetch(`${apiUrl}/settings.php`);
        const settings = await res.json();

        if (settings.about_hero_title) document.querySelector('.about-hero h1').textContent = settings.about_hero_title;
        if (settings.about_hero_subtitle) document.querySelector('.about-hero p').textContent = settings.about_hero_subtitle;

        if (settings.about_history) {
            const historyText = document.querySelector('.history-text');
            if (historyText) {
                // Split by newlines and wrap in paragraphs if it's just plain text
                const paragraphs = settings.about_history.split('\n').filter(p => p.trim());
                historyText.innerHTML = paragraphs.map(p => `<p>${p}</p>`).join('');
            }
        }

        if (settings.about_history_image) {
            const historyImg = document.querySelector('.history-image img');
            if (historyImg) {
                let imgPath = settings.about_history_image.trim();
                // Normalize path: remove leading slashes and project prefix if any
                imgPath = imgPath.replace(/^\//, '')
                    .replace(/^mombasahamlets_web\//, '')
                    .replace(/^frontend\//, '');

                // If the path is just a filename, assume it's in images/uploads
                if (!imgPath.includes('/')) {
                    imgPath = 'images/uploads/' + imgPath;
                }

                const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
                historyImg.src = `${base}/frontend/${imgPath}`.replace(/\/{2,}/g, '/');
            }
        }

        if (settings.about_mission) document.querySelector('.mv-card:nth-child(1) p').textContent = settings.about_mission;
        if (settings.about_vision) document.querySelector('.mv-card:nth-child(2) p').textContent = settings.about_vision;
        if (settings.about_values) document.querySelector('.mv-card:nth-child(3) p').textContent = settings.about_values;

    } catch (e) { console.error('Error loading about settings:', e); }

    // Fetch Achievements
    try {
        const res = await fetch(`${apiUrl}/achievements.php`);
        const items = await res.json();
        const grid = document.querySelector('.trophies-grid');
        if (grid && items.length > 0) {
            grid.innerHTML = items.map(item => `
                <div class="trophy-item">
                    <i class="${item.icon}"></i>
                    <h3>${item.title}</h3>
                    <p>${item.years}</p>
                </div>
            `).join('');
        }
    } catch (e) { console.error('Error loading achievements:', e); }

    // Fetch Management
    try {
        const res = await fetch(`${apiUrl}/management.php`);
        const items = await res.json();
        const grid = document.querySelector('.management-grid');
        if (grid && items.length > 0) {
            grid.innerHTML = items.map(item => `
                <div class="manager-card">
                    <img src="${item.image_url || 'images/logo1.jpeg'}" alt="${item.name}" class="manager-image">
                    <h3>${item.name}</h3>
                    <p>${item.role}</p>
                    <p>${item.bio || ''}</p>
                </div>
            `).join('');
        }
    } catch (e) { console.error('Error loading management:', e); }
});
