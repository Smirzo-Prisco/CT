/**
 * Anagrafe.jsx — Lista personaggi con statistiche per razza, ricerca e filtro alfabetico.
 *
 * Struttura:
 *   1. Barra statistiche: totale personaggi + conteggio per razza (ruolo/famiglia)
 *   2. Campo di ricerca istantaneo (filtra per nome e cognome)
 *   3. Alfabeto: Tutti + A–Z (lettere senza personaggi appaiono disabilitate)
 *   4. Lista risultati raggruppata per lettera con avatar, nome, sottotitolo e badge razza
 *
 * Tutto il filtraggio è client-side: i personaggi vengono caricati una volta sola
 * da op=getAll e poi filtrati in memoria.
 *
 * API: api_anagrafe.php (op=getStats, op=getAll)
 * Stili: _anagrafe.scss (iniettato via main.scss nel bundle)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useMemo } from 'react'

const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')

// Colori originali leggermente schiariti per leggibilità su sfondo scuro
const AVATAR_COLORS = [
    '#c07a68', '#5572b0', '#35cc72', '#f07060', '#f5b030',
    '#25bcd4', '#b058d0', '#5080a0', '#22c4a8', '#f07020',
]

// Badge senza bordo: sfondo leggero + testo acceso ma non violento
const BADGE_PALETTES = [
    { bg: 'rgba(174,231,255,0.12)', color: '#AEE7FF' },
    { bg: 'rgba(53,204,114,0.12)',  color: '#35cc72' },
    { bg: 'rgba(240,112,96,0.12)',  color: '#f07060' },
    { bg: 'rgba(245,176,48,0.12)',  color: '#f5b030' },
    { bg: 'rgba(192,122,104,0.12)', color: '#c07a68' },
    { bg: 'rgba(176,88,208,0.12)',  color: '#b058d0' },
    { bg: 'rgba(37,188,212,0.12)',  color: '#25bcd4' },
]

// ── Utilità ───────────────────────────────────────────────────────────────────

function hashStr(str) {
    let h = 0
    for (let i = 0; i < str.length; i++) h = str.charCodeAt(i) + ((h << 5) - h)
    return Math.abs(h)
}

const avatarColor = (nome) => AVATAR_COLORS[hashStr(nome || '') % AVATAR_COLORS.length]
const badgeStyle  = (nome) => {
    const p = BADGE_PALETTES[hashStr(nome || '') % BADGE_PALETTES.length]
    return { background: p.bg, color: p.color }
}
const statColor   = (i)    => AVATAR_COLORS[i % AVATAR_COLORS.length]

function initials(nome, cognome) {
    return ((nome?.[0] ?? '') + (cognome?.[0] ?? '')).toUpperCase()
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    if (isNaN(d)) return dateStr
    return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function Anagrafe() {
    const [stats, setStats]       = useState(null)
    const [allChars, setAllChars] = useState([])
    const [letter, setLetter]     = useState(null)   // null = Tutti
    const [search, setSearch]     = useState('')
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)

    // Carica statistiche e personaggi in parallelo al mount
    useEffect(() => {
        Promise.all([
            fetch('pages/api_anagrafe.php?op=getStats').then(r => r.json()),
            fetch('pages/api_anagrafe.php?op=getAll').then(r => r.json()),
        ])
            .then(([sData, aData]) => {
                if (sData.success) setStats(sData)
                if (aData.success) setAllChars(aData.personaggi)
                if (!sData.success || !aData.success)
                    setError(sData.message ?? aData.message ?? 'Errore caricamento dati')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    // Lettere che contengono almeno un personaggio (per disabilitare le altre)
    const activeLetters = useMemo(() => {
        const s = new Set()
        allChars.forEach(pg => { if (pg.nome?.[0]) s.add(pg.nome[0].toUpperCase()) })
        return s
    }, [allChars])

    // Filtro per lettera + ricerca (entrambi client-side)
    const filtered = useMemo(() => {
        let list = allChars
        if (letter)
            list = list.filter(pg => pg.nome?.[0]?.toUpperCase() === letter)
        if (search.trim()) {
            const q = search.trim().toLowerCase()
            list = list.filter(pg =>
                pg.nome?.toLowerCase().includes(q) ||
                pg.cognome?.toLowerCase().includes(q)
            )
        }
        return list
    }, [allChars, letter, search])

    // Raggruppamento alfabetico dei risultati filtrati
    const grouped = useMemo(() => {
        const map = {}
        filtered.forEach(pg => {
            const k = pg.nome?.[0]?.toUpperCase() ?? '?'
            ;(map[k] ??= []).push(pg)
        })
        return Object.entries(map).sort(([a], [b]) => a.localeCompare(b))
    }, [filtered])

    if (loading) return <p><i className="fas fa-spinner fa-spin" /> Caricamento…</p>
    if (error)   return <p className="error">Errore: {error}</p>

    return (
        <div className="anagrafe">

            {/* ── Link indietro (in cima, sempre a sinistra) ────────────────── */}
            <div className="link_back link_back--left">
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>

            {/* ── Barra statistiche ─────────────────────────────────────────── */}
            {stats && (
                <div className="anagrafe-stats">
                    <div className="anagrafe-stat-card">
                        <div className="stat-count">{stats.total}</div>
                        <div className="stat-label">personaggi</div>
                    </div>
                    {stats.razze.map((r, i) => (
                        <div key={r.nome || i} className="anagrafe-stat-card">
                            <div className="stat-count" style={{ color: statColor(i) }}>{r.count}</div>
                            <div className="stat-label">{r.nome || '—'}</div>
                        </div>
                    ))}
                </div>
            )}

            {/* ── Ricerca ───────────────────────────────────────────────────── */}
            <div className="anagrafe-search-wrap">
                <input
                    className="anagrafe-search-input"
                    type="text"
                    placeholder="Cerca per nome, cognome…"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                />
                <button
                    className={`anagrafe-search-clear${!search ? ' inactive' : ''}`}
                    onClick={() => setSearch('')}
                >
                    × Azzera
                </button>
            </div>

            {/* ── Alfabeto ──────────────────────────────────────────────────── */}
            <div className="anagrafe-alphabet">
                <button
                    className={`anagrafe-letter-btn${letter === null ? ' active' : ''}`}
                    onClick={() => setLetter(null)}
                >
                    Tutti
                </button>
                {ALPHABET.map(l => (
                    <button
                        key={l}
                        className={[
                            'anagrafe-letter-btn',
                            letter === l ? 'active' : '',
                            !activeLetters.has(l) ? 'disabled' : '',
                        ].join(' ').trim()}
                        onClick={() => activeLetters.has(l) && setLetter(l === letter ? null : l)}
                    >
                        {l}
                    </button>
                ))}
            </div>

            {/* ── Risultati ─────────────────────────────────────────────────── */}
            {filtered.length === 0 && (
                <p className="anagrafe-empty">Nessun personaggio trovato.</p>
            )}

            {grouped.map(([groupLetter, chars]) => (
                <div key={groupLetter}>
                    <div className="anagrafe-section-header">{groupLetter}</div>
                    {chars.map(pg => {
                        const parts = [
                            pg.mestiere?.nome || null,
                            `livello ${pg.livello}`,
                            formatDate(pg.ultimoRefresh),
                        ].filter(Boolean)

                        return (
                            <div
                                key={pg.nome}
                                className="anagrafe-row"
                                onClick={() => navigate(`main.php?page=scheda&pg=${encodeURIComponent(pg.nome)}`)}
                            >
                                <div
                                    className="anagrafe-avatar"
                                    style={{ background: avatarColor(pg.nome) }}
                                >
                                    {initials(pg.nome, pg.cognome)}
                                </div>

                                <div className="anagrafe-row-info">
                                    <div className="anagrafe-row-name">{pg.nome} {pg.cognome}</div>
                                    <div className="anagrafe-row-sub">{parts.join(' — ')}</div>
                                </div>

                                {pg.gilda?.nome && (
                                    <span
                                        className="anagrafe-row-badge"
                                        style={badgeStyle(pg.gilda.nome)}
                                    >
                                        {pg.gilda.nome}
                                    </span>
                                )}
                            </div>
                        )
                    })}
                </div>
            ))}


        </div>
    )
}
