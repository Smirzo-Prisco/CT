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

import { useState, useEffect, useCallback, useMemo } from 'react'
import QuestRecapModal from './QuestRecapModal'

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

function StatCard({ icon, value, label, onClick, active }) {
    const clickable = typeof onClick === 'function'
    return (
        <div
            className={`stat-card${clickable ? ' stat-card--clickable' : ''}${active ? ' stat-card--active' : ''}`}
            onClick={onClick}
            role={clickable ? 'button' : undefined}
            tabIndex={clickable ? 0 : undefined}
        >
            <div className="stat-icon"><i className={icon}></i></div>
            <div className="stat-content">
                <div className="stat-value">{value}</div>
                <div className="stat-label">{label}</div>
            </div>
        </div>
    )
}

function GameCard({ game, canFlag, isStaff, onFlag, onAward, onReject, onOpenQuestRecap }) {
    const today = new Date().toISOString().split('T')[0]
    const isToday = game.data === today || new Date(game.data).toISOString().split('T')[0] === today
    const duration = calcDuration(game.oraInizio, game.oraFine)

    const flagIcon  = game.my_shin === 'awarded' ? 'fas fa-coins'
                    : game.my_shin === 'pending'  ? 'fas fa-bookmark'
                    : 'far fa-bookmark'
    const flagTitle = game.isQuest              ? 'Le giocate Quest non prevedono la richiesta shin'
                    : game.my_shin === 'awarded' ? 'Shin già assegnati'
                    : game.my_shin === 'pending'  ? 'Richiesta inviata — clicca per rimuovere'
                    : 'Richiedi shin per questa giocata'

    return (
        <div className={`game-card`}>
            <div className="game-header">
                <div className="game-header-top">
                    <div
                        className="game-place"
                        style={{ cursor: 'pointer' }}
                        onClick={() => navigate(`main.php?dir=${game.luogo_id}`)}
                    >
                        <i className={game.icona}></i>{game.luogo}
                    </div>
                    <div
                        className={`status-dot ${game.inCorso ? 'status-dot--attiva' : 'status-dot--chiusa'}`}
                        title={game.inCorso ? 'In corso' : 'Conclusa'}
                    ></div>
                </div>
                {game.isQuest && game.questRecapThreadId && game.questRecapTitolo && (
                    <div
                        className="game-quest-title"
                        title="Vai al resoconto della quest"
                        onClick={() => navigate(`main.php?page=forum&thread=${game.questRecapThreadId}`)}
                    >
                        <i className="fas fa-scroll"></i>{game.questRecapTitolo}
                    </div>
                )}
                <div className="game-header-actions">
                    {canFlag && !game.inCorso && (
                        <button
                            className={`game-flag-btn game-flag-btn--${game.my_shin}`}
                            title={flagTitle}
                            disabled={game.my_shin === 'awarded' || game.isQuest}
                            onClick={() => onFlag(game.id)}
                        >
                            <i className={flagIcon}></i>
                        </button>
                    )}
                    {isStaff && game.pending_count > 0 && (
                        <button
                            className="game-award-btn"
                            title={`${game.pending_count} richiesta/e shin — clicca per premiare`}
                            onClick={() => onAward([game.id])}
                        >
                            <i className="fas fa-coins"></i>
                            <span>{game.pending_count}</span>
                        </button>
                    )}
                    {isStaff && Object.entries(game.shin_status ?? {}).filter(([, s]) => s === 'pending').map(([nome]) => (
                        <button
                            key={nome}
                            className="game-shin-btn game-shin-btn--pending"
                            title={`Richiesta shin di ${nome} — clicca per rifiutare`}
                            onClick={() => onReject(game.id, nome)}
                        >
                            <i className="fas fa-bookmark"></i>
                        </button>
                    ))}
                    <button
                        className="game-log-btn"
                        title="Leggi la giocata"
                        onClick={() => navigate(`main.php?page=role_log&id=${game.id}`)}
                    >
                        <i className="fas fa-scroll"></i>
                    </button>
                    {isStaff && game.isQuest && (
                        <button
                            className="game-quest-recap-btn"
                            title="Assegna punti quest"
                            onClick={() => onOpenQuestRecap(game)}
                        >
                            <i className="fas fa-clipboard-check"></i>
                        </button>
                    )}
                </div>
            </div>

            <div className="game-body">
                <div className="game-date">
                    <i className="fas fa-calendar-alt"></i>
                    {formatDate(game.data)}
                    {isToday && <span style={{ color: 'var(--secondary-color)', fontWeight: 'bold' }}> (Oggi)</span>}
                </div>

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
                        {game.partecipanti.map(p => (
                            <div
                                key={p.nome}
                                className={`participant${p.isMaster ? ' participant--master' : ''}`}
                                style={{ cursor: 'pointer' }}
                                onClick={() => navigate(`main.php?page=scheda&pg=${encodeURIComponent(p.nome)}`)}
                            >
                                <i className="fas fa-user"></i>
                                <span>{p.nome}</span>
                                {p.isMaster && (
                                    <span className="participant-master-badge" title="Master della giocata">M</span>
                                )}
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
    const [pgList, setPgList]           = useState([])   // [{pg_name, id_gilda, gilda_nome}]
    const [selectedPg, setSelectedPg]   = useState('')
    const [filterGilda, setFilterGilda]     = useState('')
    const [filterLuogo, setFilterLuogo]     = useState('')
    const [filterData, setFilterData]       = useState('')
    const [filterFlagged, setFilterFlagged] = useState(false)
    const [filterQuest, setFilterQuest]     = useState(false)
    const [msg, setMsg]                     = useState(null)
    const [questRecapGame, setQuestRecapGame] = useState(null)

    // pg: nome specifico | 'all' | '' (utente corrente)
    // gilda: id numerico come stringa → carica tutte le giocate della razza
    const fetchRoles = useCallback((pg, gilda = null) => {
        setLoading(true)
        setError(null)
        let qs = ''
        if (gilda !== null) qs = `&gilda=${encodeURIComponent(gilda)}`
        else if (pg)        qs = `&pg=${encodeURIComponent(pg)}`
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

    // Razze/gilde distinte presenti tra i partecipanti, ordinate per nome
    const gildaList = useMemo(() => {
        const map = new Map()
        pgList.forEach(p => {
            if (!map.has(p.id_gilda)) map.set(p.id_gilda, p.gilda_nome)
        })
        return [...map.entries()]
            .sort((a, b) => a[1].localeCompare(b[1]))
            .map(([id, nome]) => ({ id, nome }))
    }, [pgList])

    // PG visibili nella dropdown in base alla razza selezionata.
    // "Tutte le razze" (filterGilda='') esclude id_gilda=0 (senza razza).
    const filteredPgList = useMemo(() => {
        if (filterGilda === '') return pgList.filter(p => p.id_gilda > 0)
        return pgList.filter(p => String(p.id_gilda) === filterGilda)
    }, [pgList, filterGilda])

    function applyFilter(pg) {
        setSelectedPg(pg)
        if (pg === 'race') fetchRoles('', filterGilda)
        else fetchRoles(pg)
    }

    function handleGildaChange(id_gilda) {
        setFilterGilda(id_gilda)
        if (id_gilda === '') {
            // Reset a "Tutte le razze": torna alle giocate dell'utente corrente
            setSelectedPg(currentUser)
            fetchRoles(currentUser)
        } else {
            // Razza selezionata: carica subito tutte le giocate della razza
            setSelectedPg('race')
            fetchRoles('', id_gilda)
        }
    }

    // Solo l'utente che guarda le proprie giocate può flaggare
    const canFlag = selectedPg === currentUser

    const toggleFlag = useCallback(async (id_role) => {
        try {
            const res  = await fetch('pages/api_roleSession.php?op=flagRole', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_role }),
            })
            const data = await res.json()
            if (data.success) {
                setRoles(prev => prev.map(r =>
                    Number(r.id) === Number(id_role)
                        ? { ...r, my_shin: data.action === 'flagged' ? 'pending' : 'none' }
                        : r
                ))
            } else {
                setMsg({ type: 'err', text: data.message })
            }
        } catch (e) {
            setMsg({ type: 'err', text: 'Errore di rete — riprova' })
        }
    }, [])

    const awardShin = useCallback(async (id_roles) => {
        setMsg(null)
        const res  = await fetch('pages/api_roleSession.php?op=awardShin', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_roles }),
        })
        const data = await res.json()
        setMsg({ type: data.success ? 'ok' : 'err', text: data.message })
        if (data.success) {
            const gilda = selectedPg === 'race' ? filterGilda : null
            const pg    = selectedPg === 'race' ? '' : selectedPg
            fetchRoles(pg, gilda)
        }
    }, [selectedPg, filterGilda, fetchRoles])

    const rejectShin = useCallback(async (id_role, pg_name) => {
        setMsg(null)
        const res  = await fetch('pages/api_roleSession.php?op=rejectShin', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_role, pg_name }),
        })
        const data = await res.json()
        if (data.success) {
            setRoles(prev => prev.map(r => {
                if (Number(r.id) !== Number(id_role)) return r
                const shin_status = { ...r.shin_status }
                delete shin_status[pg_name]
                return { ...r, shin_status, pending_count: Math.max(0, (r.pending_count ?? 0) - 1) }
            }))
        } else {
            setMsg({ type: 'err', text: data.message })
        }
    }, [])

    const closeQuestRecap = useCallback((success) => {
        setQuestRecapGame(null)
        if (success) setMsg({ type: 'ok', text: 'Quest pubblicata nella bacheca Resoconti e Quest.' })
    }, [])

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
        if (filterFlagged) {
            // Staff: giocate con almeno una richiesta shin pendente
            // Utente: giocate flaggate da lui (pending o awarded)
            if (isStaff) return r.pending_count > 0
            return r.my_shin !== 'none'
        }
        if (filterQuest && !r.isQuest) return false
        return true
    })

    // ── Statistiche aggregate (sui risultati filtrati) ───────────────────────

    const totalGames   = filtered.length
    const activeGames  = filtered.filter(r => r.inCorso).length
    const questGames   = filtered.filter(r => r.isQuest).length
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
                            <div className="filter-bar-group">
                                <label className="filter-label" htmlFor="gilda-select">
                                    <i className="fas fa-shield-alt"></i> Razza
                                </label>
                                <select
                                    id="gilda-select"
                                    className="filter-select"
                                    value={filterGilda}
                                    onChange={e => handleGildaChange(e.target.value)}
                                >
                                    <option value="">Tutte le razze</option>
                                    {gildaList.map(g => (
                                        <option key={g.id} value={String(g.id)}>{g.nome}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="filter-bar-group">
                                <label className="filter-label" htmlFor="pg-select">
                                    <i className="fas fa-user"></i> Giocatore
                                </label>
                                <select
                                    id="pg-select"
                                    className="filter-select"
                                    value={selectedPg}
                                    onChange={e => applyFilter(e.target.value)}
                                >
                                    {filterGilda !== '' && (
                                        <option value="race">
                                            Tutti — {gildaList.find(g => String(g.id) === filterGilda)?.nome ?? ''}
                                        </option>
                                    )}
                                    <option value={currentUser}>Le mie ({currentUser})</option>
                                    {filterGilda === '' && <option value="all">Tutte le giocate</option>}
                                    {filteredPgList
                                        .filter(p => p.pg_name !== currentUser)
                                        .map(p => <option key={p.pg_name} value={p.pg_name}>{p.pg_name}</option>)
                                    }
                                </select>
                            </div>
                        </div>
                    )}
                </header>

                <div className="stats">
                    <StatCard icon="fas fa-gamepad"        value={totalGames}   label="Giocate totali" />
                    <StatCard icon="fas fa-play-circle"    value={activeGames}  label="In corso" />
                    <StatCard icon="fas fa-scroll"         value={questGames}   label="Quest"
                        onClick={() => setFilterQuest(f => !f)} active={filterQuest} />
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
                        className={`filter-flag-toggle ${filterFlagged ? 'filter-flag-toggle--active' : ''}`}
                        onClick={() => setFilterFlagged(f => !f)}
                        title={isStaff ? 'Mostra solo giocate con richieste shin pendenti' : 'Mostra solo le mie giocate flaggate'}
                    >
                        <i className="fas fa-bookmark"></i>
                        {isStaff ? 'Shin pendenti' : 'Le mie richieste'}
                    </button>

                    <button
                        className="reset-btn"
                        onClick={() => { setFilterLuogo(''); setFilterData(''); setFilterFlagged(false); setFilterQuest(false) }}
                    >
                        <i className="fas fa-times"></i> Reset filtri
                    </button>

                    {isStaff && filtered.some(r => r.pending_count > 0) && (
                        <button
                            className="bulk-award-btn"
                            onClick={() => awardShin(filtered.filter(r => r.pending_count > 0).map(r => r.id))}
                        >
                            <i className="fas fa-coins"></i> Premia tutte ({filtered.filter(r => r.pending_count > 0).length})
                        </button>
                    )}
                </div>

                {msg && (
                    <div className={`recap-msg recap-msg--${msg.type}`}>
                        <i className={`fas fa-${msg.type === 'ok' ? 'check-circle' : 'exclamation-circle'}`}></i>
                        {msg.text}
                        <button className="recap-msg-close" onClick={() => setMsg(null)}>
                            <i className="fas fa-times"></i>
                        </button>
                    </div>
                )}

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
                        {filtered.map(game => (
                        <GameCard
                            key={game.id}
                            game={game}
                            canFlag={canFlag}
                            isStaff={isStaff}
                            onFlag={toggleFlag}
                            onAward={awardShin}
                            onReject={rejectShin}
                            onOpenQuestRecap={setQuestRecapGame}
                        />
                    ))}
                    </div>
                )}

                {questRecapGame && (
                    <QuestRecapModal game={questRecapGame} onClose={closeQuestRecap} />
                )}

            </div>
        </div>
    )
}
