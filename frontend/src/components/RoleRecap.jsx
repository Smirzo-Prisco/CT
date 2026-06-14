/**
 * RoleRecap.jsx — Storico giocate del personaggio.
 *
 * Recupera le giocate via api_roleSession.php?op=getPgAllRoles e le mostra
 * in card con statistiche, stato (in corso / conclusa) e partecipanti.
 * Stili gestiti da _role_recap.scss (compilato in ct-styles.css) tramite
 * il wrapper #giocate-app.
 *
 * Navigazione SPA:
 *   - Clic sul luogo   → main.php?dir=X  (entra nella stanza via ChatShell)
 *   - Clic sul pg      → main.php?page=scheda (scheda personaggio)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

// ── Helpers ────────────────────────────────────────────────────────────────────

/** Formatta una stringa data in italiano (es. "venerdì 29 maggio 2026"). */
function formatDate(dateString) {
    if (!dateString) return ''
    const d = new Date(dateString)
    if (isNaN(d)) return dateString
    return d.toLocaleDateString('it-IT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

/** Calcola la durata tra due orari "HH:MM"; restituisce "In corso" se manca la fine. */
function calcDuration(start, end) {
    if (!start || !end) return 'In corso'
    const [sh, sm] = start.split(':').map(Number)
    const [eh, em] = end.split(':').map(Number)
    let diff = (eh * 60 + em) - (sh * 60 + sm)
    if (diff < 0) diff += 1440
    const h = Math.floor(diff / 60)
    const m = diff % 60
    if (h === 0) return `${m}m`
    if (m === 0) return `${h}h`
    return `${h}h ${m}m`
}

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Sotto-componenti ───────────────────────────────────────────────────────────

function StatCard({ icon, value, label }) {
    return (
        <div className="stat-card">
            <div className="stat-icon"><i className={icon}></i></div>
            <div className="stat-content">
                <div className="stat-value">{value}</div>
                <div className="stat-label">{label}</div>
            </div>
        </div>
    )
}

function GameCard({ game }) {
    const today = new Date().toISOString().split('T')[0]
    const isToday = game.data === today || new Date(game.data).toISOString().split('T')[0] === today
    const duration = calcDuration(game.oraInizio, game.oraFine)

    return (
        <div className={`game-card`}>
            <div className="game-header">
                <div>
                    <div
                        className="game-place"
                        style={{ cursor: 'pointer' }}
                        onClick={() => navigate(`main.php?dir=${game.luogo_id}`)}
                    >
                        <i className={game.icona}></i>{game.luogo}
                    </div>
                    <div className="game-date">
                        <i className="fas fa-calendar-alt"></i>
                        {formatDate(game.data)}
                        {isToday && <span style={{ color: 'var(--secondary-color)', fontWeight: 'bold' }}> (Oggi)</span>}
                    </div>
                </div>
                <div className={`status-badge ${game.inCorso ? 'status-in-corso' : 'status-conclusa'}`}>
                    {game.inCorso ? 'In corso' : 'Conclusa'}
                </div>
            </div>

            <div className="game-body">
                <div className="game-info">
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-clock"></i> Orario</span>
                        <span className="info-value">{game.oraInizio} – {game.oraFine || 'In corso'}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-hourglass-half"></i> Durata</span>
                        <span className="info-value">{duration}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-redo"></i> Turni totali</span>
                        <span className="info-value">{game.totTurni}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-user-friends"></i> Partecipanti</span>
                        <span className="info-value">{game.partecipanti.length}</span>
                    </div>
                </div>

                <div className="participants">
                    <div className="participants-title">
                        <i className="fas fa-users"></i>
                        Partecipanti ({game.partecipanti.length})
                    </div>
                    <div className="participants-list">
                        {game.partecipanti.map(pg => (
                            <div
                                key={pg}
                                className="participant"
                                style={{ cursor: 'pointer' }}
                                onClick={() => navigate(`main.php?page=scheda&pg=${encodeURIComponent(pg)}`)}
                            >
                                <i className="fas fa-user"></i>
                                <span>{pg}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    )
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function RoleRecap() {
    const [roles, setRoles]           = useState([])
    const [loading, setLoading]       = useState(true)
    const [error, setError]           = useState(null)
    const [isStaff, setIsStaff]       = useState(false)
    const [currentUser, setCurrentUser] = useState('')
    const [pgList, setPgList]         = useState([])
    const [selectedPg, setSelectedPg] = useState('')
    const [filterLuogo, setFilterLuogo] = useState('')
    const [filterData, setFilterData]   = useState('')

    const fetchRoles = useCallback((pg) => {
        setLoading(true)
        setError(null)
        const qs = pg ? `&pg=${encodeURIComponent(pg)}` : ''
        fetch(`pages/api_roleSession.php?op=getPgAllRoles${qs}`)
            .then(r => r.json())
            .then(d => {
                setRoles(d.roles ?? [])
                if (d.is_staff !== undefined) setIsStaff(d.is_staff)
                if (d.current_user) {
                    setCurrentUser(d.current_user)
                    setSelectedPg(prev => prev || d.current_user)
                }
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    useEffect(() => { fetchRoles('') }, [fetchRoles])

    useEffect(() => {
        if (!isStaff) return
        fetch('pages/api_roleSession.php?op=getRoleParticipants')
            .then(r => r.json())
            .then(d => { if (d.success) setPgList(d.pgs ?? []) })
            .catch(() => {})
    }, [isStaff])

    function applyFilter(pg) {
        setSelectedPg(pg)
        fetchRoles(pg)
    }

    // ── Valori unici per filtro luogo ────────────────────────────────────────

    const uniqueLocations = [...new Set(roles.map(r => r.luogo).filter(Boolean))].sort()

    // ── Ordinamento: in corso prima, poi per data decrescente ────────────────

    const sorted = [...roles].sort((a, b) => {
        if (a.inCorso && !b.inCorso) return -1
        if (!a.inCorso && b.inCorso) return 1
        return new Date(b.data) - new Date(a.data)
    })

    // ── Filtri client-side ───────────────────────────────────────────────────

    const now = new Date()
    const filtered = sorted.filter(r => {
        if (filterLuogo && r.luogo !== filterLuogo) return false
        if (filterData) {
            const d = new Date(r.data)
            const days = filterData === 'week' ? 7 : 30
            const cutoff = new Date(now)
            cutoff.setDate(cutoff.getDate() - days)
            if (d < cutoff) return false
        }
        return true
    })

    // ── Statistiche aggregate (sui risultati filtrati) ───────────────────────

    const totalGames   = filtered.length
    const activeGames  = filtered.filter(r => r.inCorso).length
    const totalPlayers = filtered.reduce((acc, r) => acc + r.partecipanti.length, 0)
    const avgTurns     = totalGames > 0 ? Math.round(filtered.reduce((acc, r) => acc + r.totTurni, 0) / totalGames) : 0

    // ── Render ───────────────────────────────────────────────────────────────

    return (
        <div id="giocate-app">
            <div className="container">

                <header>
                    <h1><i className="fas fa-dice"></i> Elenco Giocate</h1>
                    <p className="subtitle">Storico giocate per partecipante</p>
                    {isStaff && (
                        <div className="filter-bar">
                            <label className="filter-label" htmlFor="pg-select">
                                <i className="fas fa-user"></i> Giocatore
                            </label>
                            <select
                                id="pg-select"
                                className="filter-select"
                                value={selectedPg}
                                onChange={e => applyFilter(e.target.value)}
                            >
                                <option value={currentUser}>Le mie ({currentUser})</option>
                                <option value="all">Tutte le giocate</option>
                                {pgList
                                    .filter(p => p !== currentUser)
                                    .map(p => <option key={p} value={p}>{p}</option>)
                                }
                            </select>
                        </div>
                    )}
                </header>

                <div className="stats">
                    <StatCard icon="fas fa-gamepad"        value={totalGames}   label="Giocate totali" />
                    <StatCard icon="fas fa-play-circle"    value={activeGames}  label="In corso" />
                    <StatCard icon="fas fa-users"          value={totalPlayers} label="Partecipazioni" />
                    <StatCard icon="fas fa-hourglass-half" value={avgTurns}     label="Turni medi" />
                </div>

                <div className="filters">
                    <div className="filter-group">
                        <label className="filter-label" htmlFor="luogo-select">
                            <i className="fas fa-map-marker-alt"></i> Luogo
                        </label>
                        <select
                            id="luogo-select"
                            value={filterLuogo}
                            onChange={e => setFilterLuogo(e.target.value)}
                        >
                            <option value="">Tutti i luoghi</option>
                            {uniqueLocations.map(l => <option key={l} value={l}>{l}</option>)}
                        </select>
                    </div>

                    <div className="filter-group">
                        <label className="filter-label" htmlFor="data-select">
                            <i className="fas fa-calendar-alt"></i> Periodo
                        </label>
                        <select
                            id="data-select"
                            value={filterData}
                            onChange={e => setFilterData(e.target.value)}
                        >
                            <option value="">Tutto il periodo</option>
                            <option value="week">Ultima settimana</option>
                            <option value="month">Ultimo mese</option>
                        </select>
                    </div>

                    <button
                        className="reset-btn"
                        onClick={() => { setFilterLuogo(''); setFilterData('') }}
                    >
                        <i className="fas fa-times"></i> Reset filtri
                    </button>
                </div>

                {loading && (
                    <div className="no-results">
                        <i className="fas fa-spinner fa-spin"></i>
                        <h3>Caricamento in corso…</h3>
                    </div>
                )}

                {error && (
                    <div className="no-results">
                        <i className="fas fa-exclamation-triangle"></i>
                        <h3>Errore nel caricamento</h3>
                        <p>{error}</p>
                        <button onClick={() => fetchRoles(selectedPg)} style={{ marginTop: '16px', padding: '8px 20px', cursor: 'pointer' }}>
                            <i className="fas fa-redo"></i> Riprova
                        </button>
                    </div>
                )}

                {!loading && !error && filtered.length === 0 && (
                    <div className="no-results">
                        <i className="fas fa-search"></i>
                        <h3>Nessuna giocata trovata</h3>
                        <p>Prova a modificare i filtri</p>
                    </div>
                )}

                {!loading && !error && filtered.length > 0 && (
                    <div className="games-list">
                        {filtered.map(game => <GameCard key={game.id} game={game} />)}
                    </div>
                )}

            </div>
        </div>
    )
}
