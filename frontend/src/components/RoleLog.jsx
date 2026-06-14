/**
 * RoleLog.jsx — Visualizzatore log di una singola giocata.
 *
 * URL: main.php?page=role_log&id=X
 * Legge i messaggi via api_roleSession.php?op=getRoleLog&id=X
 * e li mostra in stile leggibile, analago a chat_save.proc.php.
 *
 * Tipi messaggio gestiti:
 *   P / A  — azione personaggio
 *   M      — master screen
 *   G      — fato globale
 *   X      — moderazione
 *   N      — nota di sistema
 *   C / D / O — messaggi di sistema
 *   S      — sussurro (solo se mittente/destinatario/staff)
 *   Z      — off
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

function formatDate(dateString) {
    if (!dateString) return ''
    const d = new Date(dateString)
    if (isNaN(d)) return dateString
    return d.toLocaleDateString('it-IT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

// ── Rendering singolo messaggio ───────────────────────────────────────────────

function Message({ msg }) {
    const { tipo, mittente, destinatario, ora, testo } = msg

    switch (tipo) {
        case 'P':
        case 'A':
            return (
                <div className={`rl-msg rl-msg--${tipo.toLowerCase()}`}>
                    <span className="rl-time">{ora}</span>
                    <span className="rl-sender">{mittente}</span>
                    {destinatario && <span className="rl-tag"> [{destinatario}]</span>}
                    <span className="rl-sep">: </span>
                    <span className="rl-text" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        case 'M':
            return (
                <div className="rl-msg rl-msg--master">
                    <div className="rl-master-legend">{ora} — Master Screen</div>
                    <div className="rl-master-body" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        case 'G':
            return (
                <div className="rl-msg rl-msg--global">
                    <div className="rl-global-legend">{ora} — Fato Globale</div>
                    <div className="rl-master-body" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        case 'X':
            return (
                <div className="rl-msg rl-msg--mod">
                    <div className="rl-mod-legend">{ora} — Moderazione</div>
                    <div className="rl-master-body" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        case 'N':
            return (
                <div className="rl-msg rl-msg--nota">
                    <span className="rl-time">{ora}</span>
                    {destinatario && <span className="rl-nota-target">{destinatario} </span>}
                    <span className="rl-text" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        case 'S':
            return (
                <div className="rl-msg rl-msg--whisper">
                    <span className="rl-time">{ora}</span>
                    <span className="rl-sender">{mittente}</span>
                    <span className="rl-sep"> → </span>
                    <span className="rl-tag">{destinatario}</span>
                    <span className="rl-sep">: </span>
                    <span className="rl-text" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        case 'Z':
            return (
                <div className="rl-msg rl-msg--off">
                    <span className="rl-sender">{mittente}</span>
                    <span className="rl-sep"> scrive in OFF: </span>
                    <span className="rl-text" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )

        default: // C, D, O e altri messaggi di sistema
            return (
                <div className="rl-msg rl-msg--system">
                    <span className="rl-time">{ora}</span>
                    <span className="rl-text" dangerouslySetInnerHTML={{ __html: testo }} />
                </div>
            )
    }
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function RoleLog() {
    const id = new URLSearchParams(window.location.search).get('id')

    const [role, setRole]         = useState(null)
    const [messages, setMessages] = useState([])
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)

    useEffect(() => {
        if (!id) { setError('ID giocata mancante'); setLoading(false); return }
        fetch(`pages/api_roleSession.php?op=getRoleLog&id=${encodeURIComponent(id)}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.message ?? 'Errore sconosciuto')
                setRole(d.role)
                setMessages(d.messages ?? [])
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [id])

    const endRef = useRef(null)
    const [showTop, setShowTop] = useState(false)

    useEffect(() => {
        const onScroll = () => setShowTop(window.scrollY > 300)
        window.addEventListener('scroll', onScroll)
        return () => window.removeEventListener('scroll', onScroll)
    }, [])

    if (loading) return (
        <div id="role-log-app">
            <div className="rl-loading">
                <i className="fas fa-spinner fa-spin"></i>
                <p>Caricamento giocata…</p>
            </div>
        </div>
    )

    if (error) return (
        <div id="role-log-app">
            <div className="rl-error">
                <i className="fas fa-exclamation-triangle"></i>
                <p>{error}</p>
                <button onClick={() => navigate('main.php?page=role_recap')}>
                    <i className="fas fa-arrow-left"></i> Torna alle giocate
                </button>
            </div>
        </div>
    )

    return (
        <div id="role-log-app">
            <div className="rl-container">

                <header className="rl-header">
                    <button className="rl-back" onClick={() => navigate('main.php?page=role_recap')}>
                        <i className="fas fa-arrow-left"></i> Giocate
                    </button>

                    <div className="rl-title">
                        <h1><i className="fas fa-scroll"></i> {role.luogo}</h1>
                        <p className="rl-subtitle">{formatDate(role.data)} — {role.oraInizio}{role.oraFine ? ` → ${role.oraFine}` : ' (in corso)'}</p>
                    </div>

                    <div className="rl-meta">
                        <span><i className="fas fa-redo"></i> {role.totTurni} turni</span>
                        <span><i className="fas fa-users"></i> {role.partecipanti.join(', ')}</span>
                        {role.inCorso && <span className="rl-badge-active">In corso</span>}
                    </div>
                </header>

                <div className="rl-log">
                    {messages.length === 0 && (
                        <div className="rl-empty">
                            <i className="fas fa-inbox"></i>
                            <p>Nessun messaggio disponibile per questa giocata.</p>
                        </div>
                    )}
                    {messages.map((msg, i) => <Message key={i} msg={msg} />)}
                    <div ref={endRef} />
                </div>

            </div>

        </div>

        {showTop && (
            <button
                className="rl-back-to-top"
                onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
                title="Torna in cima"
            >
                <i className="fas fa-chevron-up"></i>
            </button>
        )}
    </div>
    )
}
