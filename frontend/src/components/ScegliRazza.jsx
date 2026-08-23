/**
 * ScegliRazza.jsx — Selezione e abbandono della razza (gilda) del personaggio.
 *
 * Le card in "Razze disponibili" sono sempre cliccabili (anche se il PG è già
 * affiliato) e aprono il dettaglio della razza (gerarchia + membri), stesso
 * meccanismo di ScegliMestiere.jsx per i mestieri: navigazione via
 * ?id_gilda=X (pushState/popstate, non React Router), sezione dettaglio con
 * classi .sm-* (_servizi_mestieri.scss, mai duplicate qui). L'affiliazione
 * ("Entra nella razza") si conferma dal dettaglio, non più inline sulla card.
 * Se il PG ha già una razza: mostra la razza corrente con il pulsante
 * "Abbandona Razza" (con pannello di conferma che elenca le conseguenze).
 *
 * API: pages/api_gilda.php
 *   op=getGuildState  GET  → { pg, guilds }
 *   op=detail         GET  → { gilda, ruoli, affiliati }             — dettaglio razza
 *   op=joinGuild      POST { id_gilda }
 *   op=leaveGuild     POST {}
 *
 * Stili: _scegli_razza.scss (scoped su #scegli-razza-app, griglia razze)
 *        _servizi_mestieri.scss (scoped su .sm-page, dettaglio razza)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

function leggiIdGilda() {
    const p = new URLSearchParams(window.location.search)
    const v = parseInt(p.get('id_gilda') ?? '', 10)
    return Number.isFinite(v) && v >= 1 && v <= 7 ? v : null
}

// ── Griglia delle razze ───────────────────────────────────────────────────────

function GuildCard({ guild, isCurrent, onOpen }) {
    return (
        <div className={`sr-guild-card${isCurrent ? ' sr-guild-card--selected' : ''}`} onClick={() => onOpen(guild.id)}>
            <div className="sr-guild-img">
                <img
                    src={guild.immagine ? `imgs/guilds/${guild.immagine}` : 'imgs/guilds/standard_gilda.png'}
                    alt={guild.nome}
                    onError={e => { e.currentTarget.src = 'imgs/guilds/standard_gilda.png' }}
                />
            </div>
            <div className="sr-guild-name">{guild.nome}</div>
            {isCurrent ? (
                <div className="sr-guild-role">
                    <i className="fas fa-star"></i> La tua razza
                </div>
            ) : guild.ruolo_nome && (
                <div className="sr-guild-role">
                    <i className="fas fa-star"></i> {guild.ruolo_nome}
                </div>
            )}
            <button
                type="button"
                className="sr-btn sr-btn--statute"
                onClick={e => { e.stopPropagation(); navigate(`main.php?page=statuto_main&id=${guild.id}`) }}
            >
                <i className="fas fa-book-open"></i> Statuto
            </button>
        </div>
    )
}

// ── Dettaglio razza — gerarchia + membri (stesso layout di MestiereDetail) ───

function RazzaDetail({ id, currentGildaId, onBack, onJoined }) {
    const [data, setData] = useState(null)
    const [error, setError] = useState(null)
    const [joining, setJoining] = useState(false)
    const [joinError, setJoinError] = useState(null)

    useEffect(() => {
        setData(null)
        setError(null)
        fetch(`pages/api_gilda.php?op=detail&id_gilda=${id}`)
            .then(r => r.json())
            .then(d => { if (d.success) setData(d); else setError(d.message ?? 'Errore sconosciuto') })
            .catch(e => setError(e.message))
    }, [id])

    const entra = async () => {
        if (!window.confirm('Confermare l\'affiliazione a questa razza? Non sarà possibile sceglierne un\'altra finché non la abbandoni.')) return
        setJoining(true)
        setJoinError(null)
        try {
            const r = await fetch('pages/api_gilda.php?op=joinGuild', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id_gilda: id }),
            })
            const d = await r.json()
            setJoining(false)
            if (d.success) onJoined()
            else setJoinError(d.message ?? 'Errore nella conferma')
        } catch (e) {
            setJoining(false)
            setJoinError(e.message)
        }
    }

    const isCurrent = currentGildaId === id

    return (
        <div className="sm-page">
            <div className="sm-header">
                <button type="button" className="sm-back" onClick={onBack}>
                    <i className="fas fa-arrow-left" /> Indietro
                </button>
                {data && <h2 className="sm-title">{data.gilda.nome}</h2>}
            </div>

            {error && (
                <div className="sm-state sm-state--error">
                    <i className="fas fa-exclamation-triangle" />
                    <p>{error}</p>
                </div>
            )}

            {!error && !data && (
                <div className="sm-state">
                    <i className="fas fa-spinner fa-spin" />
                    <p>Caricamento…</p>
                </div>
            )}

            {data && (
                <>
                    {data.ruoli.length > 0 && (
                        <section className="sm-section">
                            <div className="sm-section-title">
                                <i className="fas fa-layer-group" /> Gerarchia
                            </div>
                            <div className="sm-rank-list">
                                {data.ruoli.map((r, i) => (
                                    <div className={`sm-rank-item${r.capo ? ' sm-rank-item--top' : ''}`} key={r.id_ruolo}>
                                        <span className="sm-rank-badge">{i + 1}</span>
                                        <div className="sm-img-box">
                                            {r.immagine
                                                ? <img src={`imgs/guilds/${r.immagine}`} alt={r.nome_ruolo} />
                                                : <i className="fas fa-medal" />}
                                        </div>
                                        <span className="sm-rank-name">{r.nome_ruolo}</span>
                                        {r.capo ? <i className="fas fa-crown sm-rank-crown" /> : null}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {data.affiliati.length > 0 && (
                        <section className="sm-section">
                            <div className="sm-section-title">
                                <i className="fas fa-users" /> Membri
                            </div>
                            <div className="sm-member-list">
                                {data.affiliati.map(a => (
                                    <div className={`sm-member-item${a.capo ? ' sm-member-item--leader' : ''}`} key={a.personaggio}>
                                        <div className="sm-img-box">
                                            {a.immagine
                                                ? <img src={`imgs/guilds/${a.immagine}`} alt={a.nome_ruolo} />
                                                : <i className="fas fa-user" />}
                                        </div>
                                        <div className="sm-member-info">
                                            <a href={`main.php?page=scheda&pg=${encodeURIComponent(a.personaggio)}`} className="sm-member-name">
                                                {a.personaggio} {a.cognome}
                                            </a>
                                            <span className="sm-member-role">{a.nome_ruolo}</span>
                                        </div>
                                        {a.capo ? <i className="fas fa-crown sm-member-crown" /> : null}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    <section className="sm-section">
                        {joinError && <p className="sm-error-text">{joinError}</p>}
                        {isCurrent ? (
                            <p className="sm-field-note">Questa è la tua razza attuale.</p>
                        ) : currentGildaId ? (
                            <p className="sm-field-note">Sei già affiliato a un'altra razza: abbandonala per poterne scegliere una nuova.</p>
                        ) : (
                            <button type="button" className="btn btn--primary" onClick={entra} disabled={joining}>
                                <i className="fas fa-check-circle" /> {joining ? 'Conferma in corso…' : 'Entra nella razza'}
                            </button>
                        )}
                    </section>

                    <section className="sm-section">
                        <a href={`main.php?page=statuto_main&id=${id}`} className="btn btn--secondary">
                            <i className="fas fa-book-open" /> Statuto
                        </a>
                    </section>
                </>
            )}
        </div>
    )
}

// ── Pannello razza corrente ───────────────────────────────────────────────────

function CurrentGuildPanel({ pg, onLeave }) {
    const [confirmOpen, setConfirmOpen] = useState(false)

    return (
        <div className="sr-current">
            <div className="sr-current-header">
                <i className="fas fa-shield-alt"></i> La tua razza attuale
            </div>
            <div className="sr-current-body">
                <img
                    className="sr-current-img"
                    src={pg.gilda_immagine ? `imgs/guilds/${pg.gilda_immagine}` : 'imgs/guilds/standard_gilda.png'}
                    alt={pg.gilda_nome}
                    onError={e => { e.currentTarget.src = 'imgs/guilds/standard_gilda.png' }}
                />
                <div className="sr-current-name">{pg.gilda_nome}</div>
                <p className="sr-current-info">
                    Sei già affiliato a questa razza. Per cambiare affiliazione
                    devi prima abbandonarla.
                </p>

                {!confirmOpen && (
                    <button
                        className="sr-btn sr-btn--leave"
                        onClick={() => setConfirmOpen(true)}
                    >
                        <i className="fas fa-door-open"></i> Abbandona Razza
                    </button>
                )}

                {confirmOpen && (
                    <div className="sr-confirm">
                        <div className="sr-confirm-title">
                            <i className="fas fa-exclamation-triangle"></i>
                            Sei sicuro di voler abbandonare la tua razza?
                        </div>
                        <ul className="sr-confirm-list">
                            <li>Tornerai ad essere <strong>senza razza</strong></li>
                            <li>Perderai tutti i punti <strong>shin</strong> non spesi</li>
                            <li>I punti spesi per le <strong>caratteristiche</strong> di razza verranno azzerati</li>
                            <li>Tutte le <strong>skill di razza</strong> verranno rimosse</li>
                            <li>Lo <strong>storico spese</strong> verrà cancellato</li>
                        </ul>
                        <div className="sr-confirm-actions">
                            <button
                                className="sr-btn sr-btn--danger"
                                onClick={onLeave}
                            >
                                <i className="fas fa-check"></i> Confermo, abbandono
                            </button>
                            <button
                                className="sr-btn sr-btn--cancel"
                                onClick={() => setConfirmOpen(false)}
                            >
                                <i className="fas fa-times"></i> Annulla
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function ScegliRazza() {
    const [state, setState]     = useState(null)   // { pg, guilds }
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)
    const [msg, setMsg]         = useState(null)   // { type: 'ok'|'err', text }
    const [busy, setBusy]       = useState(false)
    const [idGilda, setIdGilda] = useState(leggiIdGilda)

    const fetchState = useCallback(() => {
        setLoading(true)
        setError(null)
        setMsg(null)
        fetch('pages/api_gilda.php?op=getGuildState')
            .then(r => r.json())
            .then(d => {
                if (d.success) setState(d)
                else setError(d.message ?? 'Errore nel caricamento')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    useEffect(() => { fetchState() }, [fetchState])

    // Torna/avanti del browser sull'URL con ?id_gilda=X (impostato da
    // openDetail sotto) — stesso pattern di ScegliMestiere.jsx per ?id_mestiere=X.
    useEffect(() => {
        const onPop = () => setIdGilda(leggiIdGilda())
        window.addEventListener('popstate', onPop)
        return () => window.removeEventListener('popstate', onPop)
    }, [])

    const openDetail = (id) => {
        window.history.pushState({}, '', `main.php?page=scegli_razza&id_gilda=${id}`)
        setIdGilda(id)
    }
    const closeDetail = () => {
        window.history.pushState({}, '', 'main.php?page=scegli_razza')
        setIdGilda(null)
    }
    const onJoined = () => {
        closeDetail()
        fetchState()
    }

    const doAction = useCallback(async (op, payload = {}) => {
        setBusy(true)
        setMsg(null)
        try {
            const res  = await fetch(`pages/api_gilda.php?op=${op}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            })
            const data = await res.json()
            setMsg({ type: data.success ? 'ok' : 'err', text: data.message })
            if (data.success) fetchState()
        } catch (e) {
            setMsg({ type: 'err', text: e.message })
        } finally {
            setBusy(false)
        }
    }, [fetchState])

    if (loading) return (
        <div id="scegli-razza-app">
            <div className="sr-state">
                <i className="fas fa-spinner fa-spin"></i>
                <p>Caricamento…</p>
            </div>
        </div>
    )

    if (error) return (
        <div id="scegli-razza-app">
            <div className="sr-state sr-state--error">
                <i className="fas fa-exclamation-triangle"></i>
                <p>{error}</p>
            </div>
        </div>
    )

    const { pg, guilds } = state
    const hasGuild = pg.id_gilda > 0

    if (idGilda !== null) {
        return (
            <RazzaDetail
                id={idGilda}
                currentGildaId={hasGuild ? pg.id_gilda : null}
                onBack={closeDetail}
                onJoined={onJoined}
            />
        )
    }

    return (
        <div id="scegli-razza-app">

            <header className="sr-header">
                <button className="sr-back" onClick={() => navigate('main.php?page=uffici')}>
                    <i className="fas fa-arrow-left"></i> Uffici
                </button>
                <div className="sr-title">
                    <h1><i className="fas fa-shield-alt"></i> Scegli Razza</h1>
                    <p className="sr-subtitle">Seleziona la tua razza</p>
                </div>
            </header>

            {msg && (
                <div className={`sr-msg sr-msg--${msg.type}`}>
                    <i className={`fas fa-${msg.type === 'ok' ? 'check-circle' : 'exclamation-circle'}`}></i>
                    {msg.text}
                </div>
            )}

            {hasGuild && (
                <CurrentGuildPanel
                    pg={pg}
                    onLeave={() => !busy && doAction('leaveGuild')}
                />
            )}

            <section className="sr-section">
                <h2 className="sr-section-title">
                    <i className="fas fa-list"></i> Razze disponibili
                </h2>
                <div className="sr-grid">
                    {guilds.map(g => (
                        <GuildCard
                            key={g.id}
                            guild={g}
                            isCurrent={hasGuild && pg.id_gilda === g.id}
                            onOpen={openDetail}
                        />
                    ))}
                </div>
            </section>

        </div>
    )
}
