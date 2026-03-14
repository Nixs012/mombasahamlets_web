document.addEventListener('DOMContentLoaded', () => {
    const matchesList = document.getElementById('matchesList');
    if (matchesList) {
        // Create pagination container
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-container';
        matchesList.after(paginationContainer);
        window.__paginationContainer = paginationContainer;

        fetchMatches(1, 'all');
        setupFilters();
    }
});

let currentFilter = 'all';
let currentPage = 1;
const limit = 8;

function isMombasa(name) {
    if (!name) return false;
    const n = name.toLowerCase();
    return n.includes('mombasa') || n.includes('hamlets');
}

async function fetchMatches(page = 1, filter = 'all') {
    const matchesList = document.getElementById('matchesList');
    const loading = document.getElementById('matchesLoading');
    const paginationContainer = window.__paginationContainer;

    if (loading) loading.style.display = 'flex';
    matchesList.innerHTML = '';
    if (paginationContainer) paginationContainer.innerHTML = '';

    currentPage = page;
    currentFilter = filter;

    try {
        const response = await fetch(`${window.API_URL}/matches.php?page=${page}&limit=${limit}&status=${filter}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();

        if (result.data) {
            displayMatches(result.data);
            renderPagination(result.pagination);
            if (Array.isArray(result.data) && result.data.length) {
                window.__matchesMaxId = Math.max(...result.data.map(m => Number(m.id) || 0));
            }
        } else {
            // Fallback for non-paginated response
            displayMatches(result);
        }

        if (page > 1 || filter !== 'all') {
            matchesList.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (error) {
        console.error('Error fetching matches:', error);
        matchesList.innerHTML = `
            <div class="error-message">
                <p><strong>Could not load match data.</strong></p>
                <p>Please ensure the backend is running and try again.</p>
            </div>`;
    } finally {
        if (loading) loading.style.display = 'none';
    }
}

function displayMatches(matches) {
    const matchesList = document.getElementById('matchesList');

    if (!matches || matches.length === 0) {
        matchesList.innerHTML = `
            <div class="matches-empty">
                <i class="fas fa-calendar-times"></i>
                <h3>No matches found</h3>
                <p>There are no matches scheduled matching this criteria at the moment.</p>
            </div>`;
        return;
    }

    matchesList.innerHTML = matches.map(match => {
        try {
            return createMatchCard(match);
        } catch (e) {
            console.error("Error creating match card:", e, match);
            return ''; // Skip invalid cards
        }
    }).join('');
}

function renderPagination(pagination) {
    const container = window.__paginationContainer;
    if (!container) return;
    container.innerHTML = '';

    if (!pagination || pagination.pages <= 1) return;

    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = `page-btn ${pagination.page === 1 ? 'disabled' : ''}`;
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.onclick = () => {
        if (pagination.page > 1) fetchMatches(pagination.page - 1, currentFilter);
    };
    container.appendChild(prevBtn);

    // Page numbers
    for (let i = 1; i <= pagination.pages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `page-btn ${pagination.page === i ? 'active' : ''}`;
        pageBtn.innerText = i;
        pageBtn.onclick = () => fetchMatches(i, currentFilter);
        container.appendChild(pageBtn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = `page-btn ${pagination.page === pagination.pages ? 'disabled' : ''}`;
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.onclick = () => {
        if (pagination.page < pagination.pages) fetchMatches(pagination.page + 1, currentFilter);
    };
    container.appendChild(nextBtn);
}

function createMatchCard(match) {
    const safeDateStr = String(match.match_date).replace(' ', 'T');
    const matchDate = new Date(safeDateStr);

    let formattedDate = 'TBA';
    let formattedTime = 'TBA';

    if (!isNaN(matchDate.getTime())) {
        formattedDate = matchDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        formattedTime = matchDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function getImageUrl(path) {
        const fallback = 'images/logo1.jpeg';
        if (!path || !path.trim()) return fallback;

        let imgPath = path.trim();
        if (imgPath.startsWith('http')) return imgPath;

        // Clean up common path artifacts
        imgPath = imgPath.replace(/^\/?mombasahamlets_web\//, '');
        imgPath = imgPath.replace(/^\/?frontend\//, '');
        imgPath = imgPath.replace(/^\//, '');

        if (!imgPath) return fallback;

        // Use absolute path relative to project base
        const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
        const fullUrl = `${base}/frontend/${imgPath}`.replace(/\/{2,}/g, '/');

        // Debug
        // console.log(`Path: ${path} -> ${fullUrl}`);

        return fullUrl;
    }

    const homeTeam = match.home_team || 'Home Team';
    const awayTeam = match.away_team || 'Away Team';
    const genericLogo = 'images/logo1.jpeg';

    const homeLogo = getImageUrl(match.home_logo);
    const awayLogo = getImageUrl(match.away_logo);

    let venueIndicator = '';
    if (isMombasa(homeTeam)) venueIndicator = '(H)';
    else if (isMombasa(awayTeam)) venueIndicator = '(A)';

    const score = (match.status === 'finished' || match.status === 'live') && match.home_score !== null
        ? `${match.home_score} - ${match.away_score}`
        : 'VS';

    const statusInfo = getStatusInfo(match);
    const matchId = match.id || 0;

    return `
        <div class="match-card" data-status="${match.status}">
            <div class="match-header"> 
                <span class="match-competition">${match.competition || 'Friendly'}</span>
                <span class="match-date">${formattedDate} • ${formattedTime}</span>
            </div>
            <div class="match-content">
                <div class="team team-home">
                    <img src="${homeLogo}" alt="${homeTeam}" class="team-logo">
                    <span class="team-name">${homeTeam}</span>
                </div>
                <div class="match-vs">
                    <div class="match-score">${score}</div>
                    <div class="match-status ${statusInfo.class}">${statusInfo.text}</div>
                </div>
                <div class="team team-away">
                    <img src="${awayLogo}" alt="${awayTeam}" class="team-logo">
                    <span class="team-name">${awayTeam}</span>
                </div>
            </div>
            <div class="match-info">
                <span class="match-venue"> 
                    <i class="fas fa-map-marker-alt"></i> ${match.venue || 'TBA'} ${venueIndicator}
                </span>
                <a href="match-preview.php?id=${matchId}" class="match-preview">
                    ${match.status === 'finished' ? 'Match Report' : 'Preview'} <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    `;
}

function getStatusInfo(match) {
    switch (match.status) {
        case 'live':
            return { text: match.minute ? `${match.minute}'` : 'LIVE', class: 'status-live' };
        case 'upcoming':
        case 'scheduled':
            return { text: 'UPCOMING', class: 'status-upcoming' };
        case 'finished':
            return { text: 'FT', class: 'status-finished' };
        default:
            return { text: 'SCHEDULED', class: 'status-upcoming' };
    }
}

function setupFilters() {
    const filterButtons = document.querySelectorAll('.matches-filters .filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const filter = button.dataset.filter;
            fetchMatches(1, filter);
        });
    });
}
