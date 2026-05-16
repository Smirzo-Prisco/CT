


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
    const api_file = 'pages/api_roleSession.php';
    const param = 'op';
    // URL dell'API per recuperare le giocate
    const API_URL = api_file + '?' + param + '=getPgAllRoles';
    
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
        fetch(api_file + '?' + param + '=quitRole')
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