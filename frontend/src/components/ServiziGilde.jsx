/**
 * ServiziGilde.jsx — Razze e mestieri del gioco.
 *
 * Vista lista:
 *   - Sezione Razze: elenco gilde con conteggio iscritti, link reliquia e statuto
 *   - Sezione(i) Mestieri: raggruppate per tipo, navigano a servizi_mestieri PHP
 *     passando from=servizi_gilde per il back button contestuale
 *
 * Vista dettaglio razza:
 *   - Gerarchia gradi, lista affiliati, statuto inline
 *
 * Stili: _servizi_mestieri.scss (classi .sm-*)
 *
 * API: api_servizi_gilde.php (op=getList, op=getGuild)
 */

import { useState, useEffect, useMemo } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Sezione Razze ─────────────────────────────────────────────────────────────

function GildeSection({ gilde, onSelectGilda }) {
    return (
        <section className="sm-section">
            <div className="sm-section-title">
                <i className="fas fa-shield-alt" /> Razze
            </div>
            <div className="sm-list">
                {gilde.map(g => (
                    <div key={g.id} className="sm-list-item">
                        <a
                            href="#"
                            className="sm-list-link"
                            onClick={e => { e.preventDefault(); onSelectGilda(g.id) }}
                        >
                            <i className="fas fa-shield-alt sm-list-icon" />
                            <span className="sm-list-name">{g.nome}</span>
                        </a>
                        <span className="sm-list-count">{g.count}</span>
                        <
                            href="#"
                            className="sm-list-statute"
                            title="Statuto"
                            onClick={e => { e.preventDefault(); navigate(`main.php?page=statuto_main&id=${g.id}`) }}
                        >
                            <i className="fas fa-scroll" />
                        </a>
                    </div>
                ))}
            </div>
        </section>
    )
}

// ── Sezioni Mestieri ──────────────────────────────────────────────────────────

function MestieriSections({ mestieri }) {
    const grouped = useMemo(() => {
        const map = []
        const seen = {}
        mestieri.forEach(m => {
            if (!seen[m.tipo]) {
                seen[m.tipo] = true
                map.push({ tipo: m.tipo, desc: m.tipo_desc, items: [] })
            }
            map[map.length - 1].items.push(m)
        })
        return map
    }, [mestieri])

    return grouped.map(group => (
        <section key={group.tipo} className="sm-section">
            <div className="sm-section-title">
                {group.desc.toLowerCase() === 'generici' ? 'Mestieri' : group.desc}
            </div>
            <div className="sm-list">
                {group.items.map(m => (
                    <div key={m.id} className="sm-list-item">
                        <a
                            href="#"
                            className="sm-list-link"
                            onClick={e => {
                                e.preventDefault()
                                navigate(`main.php?page=servizi_mestieri&id_mestiere=${m.id}&from=servizi_gilde`)
                            }}
                        >
                            <i className="fas fa-briefcase sm-list-icon" />
                            <span className="sm-list-name">{m.nome}</span>
                        </a>
                        <span className="sm-list-count">{m.count}</span>
                        {m.url_sito && (
                            <a
                                href="#"
                                className="sm-list-statute"
                                title="Statuto"
                                onClick={e => {
                                    e.preventDefault()
                                    navigate(`main.php?page=statuto_main&id2=${m.id}`)
                                }}
                            >
                                <i className="fas fa-scroll" />
                            </a>
                        )}
                    </div>
                ))}
            </div>
        </section>
    ))
}

// ── Dettaglio razza ───────────────────────────────────────────────────────────

function GuildDetail({ gildaId, onBack }) {
    const [data, setData]       = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)

    useEffect(() => {
        setLoading(true)
        fetch(`pages/api_servizi_gilde.php?op=getGuild&id_gilda=${gildaId}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setData(d)
                else setError(d.message ?? 'Errore caricamento')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [gildaId])

    if (loading) return (
        <div className="sm-page">
            <div className="sm-state">
                <i className="fas fa-spinner fa-spin" />
                <p>Caricamento…</p>
            </div>
        </div>
    )
    if (error) return (
        <div className="sm-page">
            <div className="sm-state sm-state--error">
                <i className="fas fa-exclamation-triangle" />
                <p>{error}</p>
            </div>
        </div>
    )

    const { gilda, ruoli, affiliati } = data

    return (
        <div className="sm-page">
            <div className="sm-header">
                <button className="sm-back" onClick={onBack}>
                    <i className="fas fa-arrow-left" /> Razze e Mestieri
                </button>
                <h2 className="sm-title">{gilda.nome}</h2>
            </div>

            {ruoli.length > 0 && (
                <section className="sm-section">
                    <div className="sm-section-title">
                        <i className="fas fa-layer-group" /> Gerarchia
                    </div>
                    <div className="sm-rank-list">
                        {ruoli.map((r, i) => (
                            <div key={i} className={`sm-rank-item${r.capo ? ' sm-rank-item--top' : ''}`}>
                                <span className="sm-rank-badge">{i + 1}</span>
                                <div className="sm-img-box">
                                    {r.immagine
                                        ? <img src={`imgs/guilds/${r.immagine}`} alt={r.nome_ruolo} />
                                        : <i className="fas fa-medal" />
                                    }
                                </div>
                                <span className="sm-rank-name">{r.nome_ruolo}</span>
                                {r.capo && <i className="fas fa-crown sm-rank-crown" />}
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {affiliati.length > 0 && (
                <section className="sm-section">
                    <div className="sm-section-title">
                        <i className="fas fa-users" /> Membri
                    </div>
                    <div className="sm-member-list">
                        {affiliati.map((a, i) => (
                            <div key={i} className={`sm-member-item${a.capo || a.special ? ' sm-member-item--leader' : ''}`}>
                                <div className="sm-img-box">
                                    {a.immagine
                                        ? <img src={`imgs/guilds/${a.immagine}`} alt={a.nome_ruolo} />
                                        : <i className="fas fa-user" />
                                    }
                                </div>
                                <div className="sm-member-info">
                                    <a
                                        href="#"
                                        className="sm-member-name"
                                        onClick={e => {
                                            e.preventDefault()
                                            navigate(`main.php?page=scheda&pg=${encodeURIComponent(a.nome)}`)
                                        }}
                                    >
                                        {a.nome}{a.cognome ? ` ${a.cognome}` : ''}
                                    </a>
                                    <span className="sm-member-role">{a.nickname ?? a.nome_ruolo}</span>
                                </div>
                                {(a.capo || a.special) && <i className="fas fa-crown sm-member-crown" />}
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {gilda.statuto && (
                <section className="sm-section">
                    <div className="sm-section-title">
                        <i className="fas fa-scroll" /> Statuto
                    </div>
                    <div
                        className="sm-statute"
                        dangerouslySetInnerHTML={{ __html: gilda.statuto }}
                    />
                </section>
            )}
        </div>
    )
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function ServiziGilde() {
    const [loading, setLoading]          = useState(true)
    const [error, setError]              = useState(null)
    const [gilde, setGilde]              = useState([])
    const [mestieri, setMestieri]        = useState([])
    const [selectedGildaId, setSelected] = useState(null)

    useEffect(() => {
        fetch('pages/api_servizi_gilde.php?op=getList')
            .then(r => r.json())
            .then(d => {
                if (d.success) { setGilde(d.gilde); setMestieri(d.mestieri) }
                else setError(d.message ?? 'Errore caricamento')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    if (loading) return (
        <div className="sm-page">
            <div className="sm-state">
                <i className="fas fa-spinner fa-spin" />
                <p>Caricamento…</p>
            </div>
        </div>
    )
    if (error) return (
        <div className="sm-page">
            <div className="sm-state sm-state--error">
                <i className="fas fa-exclamation-triangle" />
                <p>{error}</p>
            </div>
        </div>
    )

    if (selectedGildaId !== null) {
        return <GuildDetail gildaId={selectedGildaId} onBack={() => setSelected(null)} />
    }

    return (
        <div className="sm-page">
            <div className="sm-header sm-header--list">
                <h2 className="sm-title">
                    <i className="fas fa-shield-alt" /> Razze e Mestieri
                </h2>
            </div>

            <a
                href="#"
                className="sg-banner"
                onClick={e => { e.preventDefault(); navigate('main.php?page=documentazione_main') }}
            >
                <i className="fas fa-book-open" /> AMBIENTAZIONE &amp; REGOLAMENTO
            </a>

            <GildeSection gilde={gilde} onSelectGilda={setSelected} />
            <MestieriSections mestieri={mestieri} />
        </div>
    )
}
