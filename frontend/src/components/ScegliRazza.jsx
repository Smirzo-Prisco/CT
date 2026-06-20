/**
 * ScegliRazza.jsx — Selezione e abbandono della razza (gilda) del personaggio.
 *
 * Se il PG non ha una razza: mostra la griglia di tutte le razze disponibili
 * (gilde da 1 a 7) con il pulsante "Entra".
 * Se il PG ha già una razza: mostra la razza corrente con il pulsante
 * "Abbandona Razza" (con pannello di conferma che elenca le conseguenze).
 *
 * API: pages/api_gilda.php
 *   op=getGuildState  GET  → { pg, guilds }
 *   op=joinGuild      POST { id_gilda }
 *   op=leaveGuild     POST {}
 *
 * Stili: _scegli_razza.scss (scoped su #scegli-razza-app)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Griglia delle razze ───────────────────────────────────────────────────────

function GuildCard({ guild, onJoin, disabled }) {
    const [confirmOpen, setConfirmOpen] = useState(false)

    return (
        <div className={`sr-guild-card ${disabled ? 'sr-guild-card--disabled' : ''}`}>
            <div className="sr-guild-img">
                <img
                    src={guild.immagine ? `imgs/guilds/${guild.immagine}` : 'imgs/guilds/standard_gilda.png'}
                    alt={guild.nome}
                    onError={e => { e.currentTarget.src = 'imgs/guilds/standard_gilda.png' }}
                />
            </div>
            <div className="sr-guild-name">{guild.nome}</div>
            {guild.ruolo_nome && (
                <div className="sr-guild-role">
                    <i className="fas fa-star"></i> {guild.ruolo_nome}
                </div>
            )}
            {!disabled && !confirmOpen && (
                <button
                    className="sr-btn sr-btn--join"
                    onClick={() => setConfirmOpen(true)}
                >
                    <i className="fas fa-plus-circle"></i> Entra
                </button>
            )}
            {!disabled && confirmOpen && (
                <div className="sr-confirm">
                    <div className="sr-confirm-title">
                        <i className="fas fa-exclamation-triangle"></i>
                        Entrare in <strong>{guild.nome}</strong>?
                    </div>
                    <div className="sr-confirm-actions">
                        <button
                            className="sr-btn sr-btn--join"
                            onClick={() => { setConfirmOpen(false); onJoin(guild) }}
                        >
                            <i className="fas fa-check"></i> Confermo
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
            {disabled && (
                <span className="sr-guild-locked">
                    <i className="fas fa-lock"></i> Non disponibile
                </span>
            )}
            <button
                className="sr-btn sr-btn--statute"
                onClick={() => navigate(`main.php?page=statuto_main&id=${guild.id}`)}
            >
                <i className="fas fa-book-open"></i> Statuto
            </button>
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
                            disabled={hasGuild}
                            onJoin={g => !busy && doAction('joinGuild', { id_gilda: g.id })}
                        />
                    ))}
                </div>
            </section>

        </div>
    )
}
