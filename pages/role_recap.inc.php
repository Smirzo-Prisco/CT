
<style>
    /* TEMA SCURO - Stili incapsulati per l'app giocate */
    #giocate-app {
        --primary-dark: #181c31;
        --primary-darker: #121626;
        --primary-darkest: #0d101f;
        --secondary-color: #ce846f;
        --secondary-light: #e09e8d;
        --accent-color: #f0b27a;
        --text-light: #e0e0e0;
        --text-medium: #b0b0b0;
        --text-dark: #8a8a8a;
        --card-bg: #1e2238;
        --card-border: #2a2f4a;
        --input-bg: #252a40;
        --input-border: #343a52;
        --success-color: #4CAF50;
        --warning-color: #FF9800;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.35);
        --radius: 10px;
        --transition: all 0.3s ease;
        
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--primary-darker);
        color: var(--text-light);
        line-height: 1.6;
        padding: 20px;
        min-height: 100vh;
    }

    #giocate-app * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    #giocate-app .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header */
    #giocate-app header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--secondary-color);
    }

    #giocate-app h1 {
        color: var(--text-light);
        margin-bottom: 10px;
        font-size: 2.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    #giocate-app h1 i {
        color: var(--secondary-color);
    }

    #giocate-app .subtitle {
        color: var(--text-medium);
        font-size: 1.1rem;
    }

    /* Filtri */
    #giocate-app .filters {
        background-color: var(--card-bg);
        padding: 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 25px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
        border: 1px solid var(--card-border);
    }

    #giocate-app .filter-group {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 180px;
    }

    #giocate-app .filter-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-light);
        font-size: 0.95rem;
    }

    #giocate-app select {
        padding: 12px 15px;
        border: 1px solid var(--input-border);
        border-radius: var(--radius);
        font-size: 1rem;
        background-color: var(--input-bg);
        color: var(--text-light);
        transition: var(--transition);
        cursor: pointer;
    }

    #giocate-app select:hover {
        border-color: var(--secondary-color);
    }

    #giocate-app select:focus {
        outline: none;
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 3px rgba(206, 132, 111, 0.2);
    }

    #giocate-app .reset-btn {
        padding: 12px 20px;
        background-color: var(--primary-dark);
        color: var(--text-light);
        border: 1px solid var(--input-border);
        border-radius: var(--radius);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #giocate-app .reset-btn:hover {
        background-color: var(--secondary-color);
        color: var(--primary-darkest);
        border-color: var(--secondary-color);
    }

    /* Statistiche */
    #giocate-app .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    #giocate-app .stat-card {
        background-color: var(--card-bg);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: var(--transition);
        border: 1px solid var(--card-border);
    }

    #giocate-app .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
        border-color: var(--secondary-color);
    }

    #giocate-app .stat-icon {
        width: 50px;
        height: 50px;
        background-color: rgba(206, 132, 111, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--secondary-color);
        font-size: 1.5rem;
    }

    #giocate-app .stat-content {
        flex: 1;
    }

    #giocate-app .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-light);
    }

    #giocate-app .stat-label {
        font-size: 0.9rem;
        color: var(--text-medium);
    }

    /* Badge di stato */
    #giocate-app .status-badge {
        display: inline-block;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #giocate-app .status-in-corso {
        background-color: rgba(76, 175, 80, 0.2);
        color: var(--success-color);
        border: 1px solid rgba(76, 175, 80, 0.3);
    }

    #giocate-app .status-conclusa {
        background-color: rgba(140, 140, 140, 0.2);
        color: var(--text-medium);
        border: 1px solid rgba(140, 140, 140, 0.3);
    }

    /* Lista giocate */
    #giocate-app .games-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    /* Card giocata */
    #giocate-app .game-card {
        background-color: var(--card-bg);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border: 1px solid var(--card-border);
        position: relative;
    }

    #giocate-app .game-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
    }

    #giocate-app .game-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-hover);
        border-color: var(--secondary-color);
    }

    #giocate-app .game-header {
        background-color: rgba(24, 28, 49, 0.7);
        color: var(--text-light);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    #giocate-app .game-place {
        font-size: 1.3rem;
        font-weight: 700;
        flex: 1;
    }

    #giocate-app .game-place i {
        color: var(--secondary-color);
        margin-right: 8px;
    }

    #giocate-app .game-date {
        font-size: 1rem;
        color: var(--text-medium);
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    #giocate-app .game-body {
        padding: 20px;
    }

    #giocate-app .game-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    #giocate-app .info-item {
        display: flex;
        flex-direction: column;
    }

    #giocate-app .info-label {
        font-size: 0.85rem;
        color: var(--text-medium);
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    #giocate-app .info-label i {
        color: var(--secondary-color);
    }

    #giocate-app .info-value {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--text-light);
    }

    /* Partecipanti */
    #giocate-app .participants {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--card-border);
    }

    #giocate-app .participants-title {
        font-weight: 600;
        margin-bottom: 12px;
        color: var(--text-light);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #giocate-app .participants-title i {
        color: var(--secondary-color);
    }

    #giocate-app .participants-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    #giocate-app .participant {
        background-color: rgba(206, 132, 111, 0.15);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        border: 1px solid rgba(206, 132, 111, 0.2);
    }

    #giocate-app .participant:hover {
        background-color: rgba(206, 132, 111, 0.25);
        border-color: rgba(206, 132, 111, 0.4);
    }

    #giocate-app .participant i {
        color: var(--secondary-light);
    }

    /* Nessun risultato */
    #giocate-app .no-results {
        text-align: center;
        padding: 50px 20px;
        background-color: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        grid-column: 1 / -1;
        border: 1px solid var(--card-border);
    }

    #giocate-app .no-results i {
        font-size: 3.5rem;
        color: var(--primary-dark);
        margin-bottom: 15px;
    }

    #giocate-app .no-results h3 {
        color: var(--text-light);
        margin-bottom: 10px;
    }

    #giocate-app .no-results p {
        color: var(--text-medium);
    }

    /* Scrollbar personalizzata */
    #giocate-app ::-webkit-scrollbar {
        width: 8px;
    }

    #giocate-app ::-webkit-scrollbar-track {
        background: var(--primary-dark);
    }

    #giocate-app ::-webkit-scrollbar-thumb {
        background: var(--secondary-color);
        border-radius: 4px;
    }

    #giocate-app ::-webkit-scrollbar-thumb:hover {
        background: var(--secondary-light);
    }

    /* Responsive */
    @media (max-width: 992px) {
        #giocate-app .games-list {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        }
    }

    @media (max-width: 768px) {
        #giocate-app {
            padding: 15px;
        }
        
        #giocate-app .games-list {
            grid-template-columns: 1fr;
        }
        
        #giocate-app .game-info {
            grid-template-columns: 1fr;
        }
        
        #giocate-app .filters {
            flex-direction: column;
            align-items: stretch;
        }
        
        #giocate-app .filter-group {
            min-width: 100%;
        }
        
        #giocate-app .reset-btn {
            justify-content: center;
        }
        
        #giocate-app .stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        #giocate-app h1 {
            font-size: 1.8rem;
        }
        
        #giocate-app .game-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        #giocate-app .stats {
            grid-template-columns: 1fr;
        }
        
        #giocate-app .game-info {
            grid-template-columns: 1fr;
        }
    }
</style>

<div id="giocate-app">
    <div class="container">
        <header>
            <h1><i class="fas fa-dice"></i> Elenco Giocate</h1>
            <p class="subtitle">Visualizza tutte le giocate con dettagli, partecipanti e stato</p>
        </header>
        <!-- Filtri --
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label" for="status-filter">Stato:</label>
                <select id="status-filter">
                    <option value="all">Tutte le giocate</option>
                    <option value="in-corso">In corso</option>
                    <option value="conclusa">Concluse</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="date-filter">Data:</label>
                <select id="date-filter">
                    <option value="all">Tutte le date</option>
                    <option value="today">Oggi</option>
                    <option value="week">Questa settimana</option>
                    <option value="month">Questo mese</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="place-filter">Chat di gioco:</label>
                <select id="place-filter">
                    <option value="all">Tutte le chat</option>
                    <option value="telegram">Telegram</option>
                    <option value="discord">Discord</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="altro">Altro</option>
                </select>
            </div>
            <button id="reset-filters" class="reset-btn">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
        -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="total-games">0</div>
                    <div class="stat-label">Giocate totali</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="active-games">0</div>
                    <div class="stat-label">In corso</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="total-players">0</div>
                    <div class="stat-label">Partecipazioni</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="avg-turns">0</div>
                    <div class="stat-label">Turni medi</div>
                </div>
            </div>
        </div>

        <div class="games-list" id="games-container">
            <!-- Le giocate verranno caricate qui dinamicamente -->
        </div>
    </div>
</div>

<script>
    // URL dell'API per recuperare le giocate
    const API_URL = 'pages/api_chat.php?op=getPgAllRoles'; // SOSTITUIRE con URL reale
    
    // Variabile per memorizzare le giocate recuperate
    let giocate = [];

    // Funzione per recuperare le giocate dall'API
    async function fetchGiocate() {
        const container = document.getElementById('games-container');
        
        // Mostra indicatore di caricamento
        container.innerHTML = `
            <div class="loading" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--secondary-color); margin-bottom: 20px;"></i>
                <p>Caricamento giocate in corso...</p>
            </div>
        `;
        
        try {
            // Esegui la chiamata API
            const response = await fetch(API_URL, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
            
            if (!response.ok) { throw new Error(`Errore HTTP: ${response.status}`); }
            
            const data = await response.json();
            
            // Assicurati che i dati siano nel formato corretto
            giocate = data.roles.map(item => ({
                id: item.id || item._id,
                luogo: item.luogo || item.chat || item.nome,
                luogo_id: item.luogo_id,
                data: item.data || item.dataGiocata,
                oraInizio: item.oraInizio || item.inizio,
                oraFine: item.oraFine || item.fine,
                totTurni: item.totTurni || item.turni || 0,
                partecipanti: item.partecipanti || item.giocatori || [],
                inCorso: item.inCorso !== undefined ? item.inCorso : (item.stato === 'in-corso'),
                icona: getIconByPlatform(item.luogo || item.chat || '')
            }));
            
            // Inizializza la visualizzazione
            renderGames(giocate);
            
        } catch (error) {
            console.error('Errore nel recupero delle giocate:', error);
            
            container.innerHTML = `
                <div class="error" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                    <i class="fas fa-exclamation-triangle fa-3x" style="color: #ff6b6b; margin-bottom: 20px;"></i>
                    <h3>Errore nel caricamento delle giocate</h3>
                    <p>${error.message}</p>
                    <button id="retry-btn" style="margin-top: 20px; padding: 10px 20px; background-color: var(--secondary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-redo"></i> Riprova
                    </button>
                </div>
            `;
        }
    }

    // Funzione per determinare l'icona in base alla piattaforma
    function getIconByPlatform(luogo) {
        const luogoLower = luogo.toLowerCase();
        if (luogoLower.includes('telegram')) return 'fas fa-paper-plane';
        if (luogoLower.includes('discord')) return 'fab fa-discord';
        if (luogoLower.includes('whatsapp')) return 'fab fa-whatsapp';
        return 'fas fa-globe';
    }

    // Funzione per formattare la data in formato italiano
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return date.toLocaleDateString('it-IT', options);
    }

    // Funzione per calcolare la durata della giocata
    function calculateDuration(startTime, endTime) {
        if (!startTime || !endTime) return "In corso";
        
        const start = startTime.split(":");
        const end = endTime.split(":");
        
        const startMinutes = parseInt(start[0]) * 60 + parseInt(start[1]);
        const endMinutes = parseInt(end[0]) * 60 + parseInt(end[1]);
        
        let durationMinutes = endMinutes - startMinutes;
        if (durationMinutes < 0) durationMinutes += 24 * 60;
        
        const hours = Math.floor(durationMinutes / 60);
        const minutes = durationMinutes % 60;
        
        if (hours === 0) return `${minutes}m`;
        if (minutes === 0) return `${hours}h`;
        return `${hours}h ${minutes}m`;
    }

    // Funzione per aggiornare le statistiche
    function updateStats(games) {
        const totalGames = games.length;
        const activeGames = games.filter(game => game.inCorso).length;
        
        let totalPlayers = 0;
        let totalTurns = 0;
        
        games.forEach(game => {
            totalPlayers += game.partecipanti.length;
            totalTurns += game.totTurni;
        });
        
        const avgTurns = totalGames > 0 ? Math.round(totalTurns / totalGames) : 0;
        
        document.getElementById('total-games').textContent = totalGames;
        document.getElementById('active-games').textContent = activeGames;
        document.getElementById('total-players').textContent = totalPlayers;
        document.getElementById('avg-turns').textContent = avgTurns;
    }

    // Funzione per creare il markup di una giocata
    function createGameCard(game) {
        const statusClass = game.inCorso ? 'status-in-corso' : 'status-conclusa';
        const statusText = game.inCorso ? 'In corso' : 'Conclusa';
        // const btnQuitRole = game.inCorso ? '<button onclick="quitRole();">X</button>' : '';
        const duration = calculateDuration(game.oraInizio, game.oraFine);
        const formattedDate = formatDate(game.data);
        const oggi = new Date().toISOString().split('T')[0];
        const isToday = game.data === oggi;
        
        return `
            <div class="game-card" data-status="${game.inCorso ? 'in-corso' : 'conclusa'}" data-date="${game.data}" data-place="${game.luogo.toLowerCase()}">
                <div class="game-header">
                    <div>
                        <a href="main.php?dir=${game.luogo_id}">
                            <div class="game-place"><i class="${game.icona}"></i>${game.luogo}</div>
                        </a>
                        <div class="game-date">
                            <i class="fas fa-calendar-alt"></i>
                            ${formattedDate} ${isToday ? '<span style="color: var(--secondary-color); font-weight: bold;">(Oggi)</span>' : ''}
                        </div>
                    </div>
                    <div class="status-badge ${statusClass}">${statusText}</div>
                </div>
                <div class="game-body">
                    <div class="game-info">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-clock"></i> Orario</span>
                            <span class="info-value">${game.oraInizio} - ${game.oraFine || 'In corso'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-hourglass-half"></i> Durata</span>
                            <span class="info-value">${duration}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-redo"></i> Turni totali</span>
                            <span class="info-value">${game.totTurni}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-user-friends"></i> Partecipanti</span>
                            <span class="info-value">${game.partecipanti.length}</span>
                        </div>
                    </div>
                    
                    <div class="participants">
                        <div class="participants-title">
                            <i class="fas fa-users"></i>
                            Partecipanti (${game.partecipanti.length})
                        </div>
                        <div class="participants-list">
                            ${game.partecipanti.map(participant => `
                                <a href="main.php?page=scheda&pg=${participant}">
                                    <div class="participant">
                                        <i class="fas fa-user"></i>
                                        <span>${participant}</span>
                                    </div>
                                </a>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function quitRole() {
        fetch('pages/api_chat.php?op=quitRole')
        .then(res => res.json())
        .then(data => {
            if (data.success) fetchGiocate();
            else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
    }

    // Funzione per visualizzare tutte le giocate
    function renderGames(games) {
        const container = document.getElementById('games-container');
        
        if (games.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Nessuna giocata trovata</h3>
                    <p>Non ci sono giocate disponibili al momento</p>
                </div>
            `;
        } else {
            // Ordinamento: prima quelle in corso, poi per data (più recenti prima)
            const sortedGames = [...games].sort((a, b) => {
                if (a.inCorso && !b.inCorso) return -1;
                if (!a.inCorso && b.inCorso) return 1;
                return new Date(b.data) - new Date(a.data);
            });
            
            container.innerHTML = sortedGames.map(game => createGameCard(game)).join('');
        }
        
        updateStats(games);
    }

    // Funzione per filtrare le giocate
    function filterGames() {
        if (giocate.length === 0) return;
        
        const statusFilter = document.getElementById('status-filter').value;
        const dateFilter = document.getElementById('date-filter').value;
        const placeFilter = document.getElementById('place-filter').value;
        
        let filtered = giocate;
        
        // Filtro per stato
        if (statusFilter !== 'all') {
            filtered = filtered.filter(game => {
                if (statusFilter === 'in-corso') return game.inCorso;
                if (statusFilter === 'conclusa') return !game.inCorso;
                return true;
            });
        }
        
        // Filtro per data
        if (dateFilter !== 'all') {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            filtered = filtered.filter(game => {
                const gameDate = new Date(game.data);
                
                if (dateFilter === 'today') {
                    return gameDate.getTime() === today.getTime();
                }
                
                if (dateFilter === 'week') {
                    const weekAgo = new Date(today);
                    weekAgo.setDate(today.getDate() - 7);
                    return gameDate >= weekAgo && gameDate <= today;
                }
                
                if (dateFilter === 'month') {
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(today.getMonth() - 1);
                    return gameDate >= monthAgo && gameDate <= today;
                }
                
                return true;
            });
        }
        
        // Filtro per luogo (chat di gioco)
        if (placeFilter !== 'all') {
            filtered = filtered.filter(game => {
                const luogo = game.luogo.toLowerCase();
                if (placeFilter === 'telegram') return luogo.includes('telegram');
                if (placeFilter === 'discord') return luogo.includes('discord');
                if (placeFilter === 'whatsapp') return luogo.includes('whatsapp');
                if (placeFilter === 'altro') return !luogo.includes('telegram') && !luogo.includes('discord') && !luogo.includes('whatsapp');
                return true;
            });
        }
        
        renderGames(filtered);
    }

    // Funzione per resettare i filtri
    function resetFilters() {
        document.getElementById('status-filter').value = 'all';
        document.getElementById('date-filter').value = 'all';
        document.getElementById('place-filter').value = 'all';
        filterGames();
    }

    // Inizializzazione della pagina
    document.addEventListener('DOMContentLoaded', function() {
        // Recupera le giocate dall'API
        fetchGiocate();
        
        // Aggiungi event listeners ai filtri
        document.getElementById('status-filter').addEventListener('change', filterGames);
        document.getElementById('date-filter').addEventListener('change', filterGames);
        document.getElementById('place-filter').addEventListener('change', filterGames);
        document.getElementById('reset-filters').addEventListener('click', resetFilters);
    });
</script>