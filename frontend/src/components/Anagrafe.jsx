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

// Colori fissi per avatar e badge (derivati dai design token del progetto)
const AVATAR_COLORS = [
    '#9a6353', '#3a4f86', '#27ae60', '#e74c3c', '#f39c12',
    '#17a2b8', '#8e44ad', '#2c3e50', '#16a085', '#d35400',
]

const BADGE_PALETTES = [
    { bg: 'rgba(174,231,255,0.12)', border: '#AEE7FF', color: '#AEE7FF' },
    { bg: 'rgba(39,174,96,0.12)',   border: '#27ae60', color: '#27ae60' },
    { bg: 'rgba(231,76,60,0.12)',   border: '#e74c3c', color: '#e74c3c' },
    { bg: 'rgba(243,156,18,0.12)',  border: '#f39c12', color: '#f39c12' },
    { bg: 'rgba(154,99,83,0.12)',   border: '#9a6353', color: '#9a6353' },
    { bg: 'rgba(142,68,173,0.12)', border: '#8e44ad', color: '#8e44ad' },
    { bg: 'rgba(23,162,184,0.12)', border: '#17a2b8', color: '#17a2b8' },
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
    return { background: p.bg, borderColor: p.border, color: p.color }
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
                {search && (
                    <button className="anagrafe-search-clear" onClick={() => setSearch('')}>
                        × Azzera
                    </button>
                )}
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

                                {pg.razza?.nome && (
                                    <span
                                        className="anagrafe-row-badge"
                                        style={badgeStyle(pg.razza.nome)}
                                    >
                                        {pg.razza.nome}
                                    </span>
                                )}
                            </div>
                        )
                    })}
                </div>
            ))}

            {/* ── Link indietro ─────────────────────────────────────────────── */}
            <div className="panels_link" style={{ marginTop: '20px' }}>
                <span style={{ cursor: 'pointer' }} onClick={() => navigate('main.php?page=uffici')}>
                    Torna indietro
                </span>
            </div>

        </div>
    )
}
