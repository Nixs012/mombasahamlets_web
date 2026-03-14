async function fetchMatchDetails(id) {
    const loading = document.getElementById('match-loading');
    const content = document.getElementById('match-content');

    // Debug info
    const apiUrl = window.API_URL;
    if (!apiUrl) {
        showError('System Error: API Configuration is missing. Please refresh the page.');
        return;
    }

    try {
        const fetchUrl = `${apiUrl}/matches.php?id=${id}`;
        // console.log('Fetching', fetchUrl); 

        const response = await fetch(fetchUrl);
        if (!response.ok) {
            throw new Error(`Server returned status: ${response.status}`);
        }

        const match = await response.json();
        if (!match || match.error) {
            throw new Error(match.error || 'Match not found');
        }

        renderMatch(match);
        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'block';
    } catch (error) {
        console.error('Error:', error);
        if (loading) loading.style.display = 'none';
        showError(`Could not load match details.<br><small>${error.message}</small>`);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const matchId = urlParams.get('id');

    if (!matchId) {
        showError('No match specified.');
        return;
    }

    fetchMatchDetails(matchId);
});

function isMombasa(name) {
    if (!name) return false;
    const n = name.toLowerCase();
    return n.includes('mombasa') || n.includes('hamlets');
}

function renderMatch(match) {
    const content = document.getElementById('match-content');

    // Date Parsing
    const safeDateStr = String(match.match_date).replace(' ', 'T');
    const dateObj = new Date(safeDateStr);
    const dateStr = !isNaN(dateObj.getTime())
        ? dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
        : match.match_date;
    const timeStr = !isNaN(dateObj.getTime())
        ? dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
        : '';

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

        return fullUrl;
    }

    // Team Data
    const homeTeam = match.home_team || 'Home Team';
    const awayTeam = match.away_team || 'Away Team';
    const genericLogo = 'images/logo1.jpeg';
    const homeLogo = getImageUrl(match.home_logo);
    const awayLogo = getImageUrl(match.away_logo);

    const score = (match.status === 'finished' || match.status === 'live') && match.home_score !== null
        ? `<span class="score-display">${match.home_score} - ${match.away_score}</span>`
        : `<span class="score-display">VS</span>`;

    // Status Badge
    let statusClass = 'status-upcoming';
    let statusText = match.status;
    if (match.status === 'live') statusClass = 'status-live';
    if (match.status === 'finished') statusClass = 'status-finished';

    // Lineups Parsing
    function formatLineup(lineupData) {
        if (!lineupData) return '<p>No lineup available.</p>';

        let html = '';
        try {
            const parsed = typeof lineupData === 'string' ? JSON.parse(lineupData) : lineupData;

            // Handle new format {starting: [], subs: []}
            if (parsed.starting || parsed.subs) {
                if (parsed.starting && parsed.starting.length > 0) {
                    html += `<h5 style="margin-top:10px; margin-bottom:5px; color:#555;">Starting XI</h5>`;
                    html += `<ul class="lineup-list">${parsed.starting.map(p => `<li><span>${p.number || ''}</span> <span>${p.name}</span></li>`).join('')}</ul>`;
                }
                if (parsed.subs && parsed.subs.length > 0) {
                    html += `<h5 style="margin-top:10px; margin-bottom:5px; color:#555;">Substitutes</h5>`;
                    html += `<ul class="lineup-list">${parsed.subs.map(p => `<li><span>${p.number || ''}</span> <span>${p.name}</span></li>`).join('')}</ul>`;
                }
            }
            // Handle legacy format (Array)
            else if (Array.isArray(parsed)) {
                html += `<ul class="lineup-list">${parsed.map(p => `<li><span>${p.number || ''}</span> <span>${p.name}</span></li>`).join('')}</ul>`;
            } else {
                html = `<p>${lineupData}</p>`;
            }
        } catch (e) {
            html = `<p>${lineupData}</p>`;
        }
        return html || '<p>No lineup available.</p>';
    }

    const homeLineupHTML = formatLineup(match.home_lineup);
    const awayLineupHTML = formatLineup(match.away_lineup);


    content.innerHTML = `
        <div class="match-details-card">
            <div class="details-header">
                <div class="match-meta">
                    <span class="competition">${match.competition || 'Friendly Match'}</span> | 
                    <span class="date">${dateStr} ${timeStr}</span> | 
                    <span class="venue">${match.venue || 'TBA'}</span>
                </div>
                
                <div class="score-board" style="margin-top: 2rem;">
                    <div class="team-display">
                        <img src="${homeLogo}" alt="${homeTeam}">
                        <span>${homeTeam}</span>
                    </div>
                    ${score}
                    <div class="team-display">
                        <img src="${awayLogo}" alt="${awayTeam}">
                        <span>${awayTeam}</span>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <span class="match-status ${statusClass}" style="padding: 5px 15px; border-radius: 20px; color: white; background: var(--red); font-size: 0.8rem;">
                        ${statusText ? statusText.toUpperCase() : 'SCHEDULED'}
                    </span>
                </div>
            </div>

            <div class="details-grid">
                <div class="lineups-section">
                    <h3>Team Lineups</h3>
                    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <h4>${homeTeam}</h4>
                            ${homeLineupHTML}
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <h4>${awayTeam}</h4>
                            ${awayLineupHTML}
                        </div>
                    </div>
                </div>

                <div class="report-section">
                    <h3>Match Report</h3>
                    <div class="report-content">
                        ${match.preview_content || match.match_report || '<p>No preview or report available yet.</p>'}
                    </div>
                </div>
            </div>

            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="matches.php" class="btn" style="background-color: var(--dark-red); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: bold; transition: all 0.3s ease;">
                    <i class="fas fa-arrow-left"></i> Back to Matches
                </a>
            </div>
        </div>
    `;
}

function showError(msg) {
    const content = document.getElementById('match-content');
    const loading = document.getElementById('match-loading');
    if (loading) loading.style.display = 'none';
    if (content) {
        content.innerHTML = `<div class="error-message" style="text-align: center; color: red;">${msg}</div>`;
        content.style.display = 'block';
    }
}