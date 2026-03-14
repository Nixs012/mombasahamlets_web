let currentFilter = 'all';
let currentSearch = '';
let currentPage = 1;
const limit = 8;
let paginationContainer;

function initPlayers() {
    console.log("Initializing players page...");

    const playersGrid = document.getElementById('playersGrid');
    if (playersGrid) {
        paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-container';
        playersGrid.after(paginationContainer);
    }

    fetchPlayers(1, 'all', '');
    setupFilters();
    setupSearch();
    setupModal();
}

async function fetchPlayers(page = 1, filter = 'all', searchTerm = '') {
    const playersGrid = document.getElementById('playersGrid');
    if (!playersGrid) return;

    currentPage = page;
    currentFilter = filter;
    currentSearch = searchTerm;

    playersGrid.innerHTML = '<div class="loading-spinner"></div><p>Loading players...</p>';
    if (paginationContainer) paginationContainer.innerHTML = '';

    try {
        const response = await fetch(`${window.API_URL}/players.php?page=${page}&limit=${limit}&position=${filter}&search=${encodeURIComponent(searchTerm)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();

        if (result.data) {
            displayPlayers(result.data);
            renderPagination(result.pagination);
            if (Array.isArray(result.data) && result.data.length) {
                window.__playersMaxId = Math.max(...result.data.map(p => Number(p.id) || 0));
            }
        } else {
            // Fallback
            displayPlayers(result);
        }

        if (page > 1 || filter !== 'all' || searchTerm !== '') {
            playersGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (error) {
        console.error('Error fetching players:', error);
        playersGrid.innerHTML = `
            <div class="error-message">
                <p><strong>Could not load player data.</strong></p>
                <p>Please ensure the backend server is running and accessible.</p>
            </div>`;
    }
}

function displayPlayers(players) {
    const playersGrid = document.getElementById('playersGrid');
    if (!playersGrid) return;

    if (!players || players.length === 0) {
        playersGrid.innerHTML = `
            <div class="players-empty">
                <i class="fas fa-search"></i>
                <h3>No players found</h3>
                <p>Try adjusting your search or filter criteria.</p>
            </div>
        `;
    } else {
        playersGrid.innerHTML = players.map(player => createPlayerCard(player)).join('');
    }
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
        if (pagination.page > 1) fetchPlayers(pagination.page - 1, currentFilter, currentSearch);
    };
    paginationContainer.appendChild(prevBtn);

    // Page numbers
    for (let i = 1; i <= pagination.pages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `page-btn ${pagination.page === i ? 'active' : ''}`;
        pageBtn.innerText = i;
        pageBtn.onclick = () => fetchPlayers(i, currentFilter, currentSearch);
        paginationContainer.appendChild(pageBtn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = `page-btn ${pagination.page === pagination.pages ? 'disabled' : ''}`;
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.onclick = () => {
        if (pagination.page < pagination.pages) fetchPlayers(pagination.page + 1, currentFilter, currentSearch);
    };
    paginationContainer.appendChild(nextBtn);
}

// Create player card HTML
function createPlayerCard(player) {
    const positionClass = player.position.toLowerCase();
    const base = (window.PROJECT_BASE || '').replace(/\/+$/, '');
    const imageUrl = player.image_url ? `${base}/frontend/${player.image_url.replace(/^\/?frontend\//, '').replace(/^\//, '')}`.replace(/\/{2,}/g, '/') : 'images/logo1.jpeg';

    return `
        <div class="player-card" data-player-id="${player.id}" data-position="${positionClass}">
            <div class="player-image-container">
                <img src="${imageUrl}" alt="${player.first_name} ${player.last_name}" class="player-image" onerror="this.src='images/logo1.jpeg'">
                <div class="player-overlay">
                    <div class="player-info-bottom">
                        <div class="player-jersey-number">${player.jersey_number || ''}</div>
                        <div class="player-full-name">
                            <span class="first-name">${player.first_name}</span>
                            <span class="last-name">${player.last_name}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="player-card-details">
                <div class="player-position">${player.position}</div>
                <div class="player-mini-stats">
                    <div class="stat-item">
                        <span class="val">${player.appearances || 0}</span>
                        <span class="lbl">Apps</span>
                    </div>
                    <div class="stat-item">
                        <span class="val">${player.position && player.position.toLowerCase() === 'goalkeeper' ? (player.clean_sheets || 0) : (player.goals || 0)}</span>
                        <span class="lbl">${player.position && player.position.toLowerCase() === 'goalkeeper' ? 'CS' : 'Goals'}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Setup filter buttons
function setupFilters() {
    const filterButtons = document.querySelectorAll('.filter-buttons .filter-btn');
    if (!filterButtons.length) return;

    filterButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();

            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            const filter = button.getAttribute('data-filter');
            const searchTerm = document.getElementById('playerSearch').value.toLowerCase();
            fetchPlayers(1, filter, searchTerm);
        });
    });
}

// Setup search functionality
function setupSearch() {
    const searchInput = document.getElementById('playerSearch');
    if (!searchInput) return;

    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = searchInput.value;
            const activeFilter = document.querySelector('.filter-buttons .filter-btn.active');
            const filter = activeFilter ? activeFilter.getAttribute('data-filter') : 'all';
            fetchPlayers(1, filter, searchTerm);
        }, 300);
    });
}

// Setup modal functionality
function setupModal() {
    const modal = document.getElementById('playerModal');
    const closeBtn = document.querySelector('.close-modal');

    if (!modal || !closeBtn) {
        console.error("Modal elements not found");
        return;
    }

    // Add click event to player cards
    document.addEventListener('click', (event) => {
        const card = event.target.closest('.player-card');
        if (card && card.dataset.playerId) {
            const playerId = parseInt(card.getAttribute('data-player-id'));
            showPlayerModal(playerId);
        }
    });

    closeBtn.addEventListener('click', () => {
        closeModal();
    });

    // Close modal when clicking outside
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
}

function closeModal() {
    const modal = document.getElementById('playerModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

// Show player modal
async function showPlayerModal(playerId) {
    const modal = document.getElementById('playerModal');
    if (!modal) {
        console.error("Player modal container not found");
        return;
    }

    const modalBody = modal.querySelector('#modalBody');
    if (!modalBody) return;

    // Show loading spinner while fetching full details
    modalBody.innerHTML = '<div class="loading-spinner"></div>';
    modal.style.display = 'block';
    document.body.classList.add('modal-open');

    try {
        const response = await fetch(`${window.API_URL}/players.php?id=${playerId}`);
        if (!response.ok) {
            throw new Error(`Failed to fetch player details. Status: ${response.status}`);
        }
        const player = await response.json();

        if (player) {
            modalBody.innerHTML = createPlayerModalContent(player);
        } else {
            throw new Error('Player data not found.');
        }
    } catch (error) {
        console.error('Error in showPlayerModal:', error);
        modalBody.innerHTML = `<div class="error-message"><p>${error.message}</p></div>`;
    }
}

// Create player modal content
function createPlayerModalContent(player) {
    const dobDate = player.dob ? new Date(player.dob) : null;
    const joinedDate = player.joined ? new Date(player.joined) : null; // Assuming 'joined' field exists in DB for this
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const imageUrl = player.image_url ? `/mombasahamlets_web/frontend/${player.image_url.replace(/^\/?frontend\//, '')}` : 'images/logo1.jpeg';

    return `
            <div class="player-details">
                <div class="player-details-image-container">
                    <img src="${imageUrl}" alt="${player.first_name} ${player.last_name}" class="player-details-image" onerror="this.src='images/logo1.jpeg'">
                    <div class="player-details-number">${player.jersey_number || 'N/A'}</div>
                </div>
                <h2 class="player-details-name">${player.first_name} ${player.last_name}</h2>
                <p class="player-details-position">${player.position}</p>
                <div class="player-info-grid">
                    <div class="player-info-item"><div class="info-label">Nationality</div><div class="info-value">${player.nationality || 'N/A'}</div></div>
                    <div class="player-info-item"><div class="info-label">Date of Birth</div><div class="info-value">${dobDate ? dobDate.toLocaleDateString() : 'N/A'}</div></div>
                    <div class="player-info-item"><div class="info-label">Age</div><div class="info-value">${player.age || 'N/A'}</div></div>
                    <div class="player-info-item"><div class="info-label">Joined Club</div><div class="info-value">${joinedDate ? joinedDate.toLocaleDateString('en-US', options) : 'N/A'}</div></div>

                </div>
                <p class="player-bio">${player.bio || 'No biography available.'}</p>

                <div class="player-stats-detailed">
                    <h4>Season Statistics</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-value">${player.appearances || 0}</span>
                            <span class="stat-label">Apps</span>
                        </div>
                        ${player.position && player.position.toLowerCase() === 'goalkeeper' ? `
                        <div class="stat-item">
                            <span class="stat-value">${player.clean_sheets || 0}</span>
                            <span class="stat-label">Clean Sheets</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${player.saves || 0}</span>
                            <span class="stat-label">Saves</span>
                        </div>
                        ` : `
                        <div class="stat-item">
                            <span class="stat-value">${player.goals || 0}</span>
                            <span class="stat-label">Goals</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${player.assists || 0}</span>
                            <span class="stat-label">Assists</span>
                        </div>
                        `}

                    </div>
                </div>
            </div>
        `;
}

// Start when DOM is loaded
document.addEventListener('DOMContentLoaded', initPlayers);

// Poll updates endpoint every 15s and refresh players if new items exist
/*
    // Removed polling for updates.php as it's not directly related to player display
    // and was causing 404 errors. If needed, this should be in a separate, more generic utility.
    (function startPlayersPolling() {
        const pollInterval = 15000;
        async function poll() {
            try {
                const resp = await fetch((window.API_URL || '/backend/api') + '/updates.php');
                if (!resp.ok) return;
                const data = await resp.json();
                const playersMax = data.players ? Number(data.players.max_id || 0) : 0;
                const localMax = Number(window.__playersMaxId || 0);
                if (playersMax > localMax) {
                    window.__playersMaxId = playersMax;
                    fetchPlayers();
                }
            } catch (e) { // ignore polling errors
                console.warn('Polling for updates.php failed:', e);
            }
        }
        // setInterval(poll, pollInterval); // Disabled for now
    })();
*/