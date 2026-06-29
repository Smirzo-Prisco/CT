/**
 * GestioneBot.jsx — Pannello admin gestione personaggi bot (sesso='b')
 *
 * Sezioni:
 *  - Password comune: aggiornamento bulk per tutti i bot
 *  - Lista bot: status online, badge DM non letti, selezione multipla
 *  - Bulk actions: porta online/offline, imposta orari (modal schedule)
 *  - Pannello DM: conversazioni del bot selezionato, thread e risposta
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import styles from './GestioneBot.module.css'

const GIORNI = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica']

function emptySchedule() {
    return GIORNI.map(() => ({ enabled: false, ora_inizio: '09:00', ora_fine: '18:00' }))
}

// ── ScheduleModal ─────────────────────────────────────────────────────────────

function ScheduleModal({ selected, onClose, onSaved }) {
    const [schedule, setSchedule] = useState(emptySchedule())
    const [saving, setSaving]     = useState(false)
    const [msg, setMsg]           = useState(null)

    const toggleDay = (i) =>
        setSchedule(s => s.map((d, idx) => idx === i ? { ...d, enabled: !d.enabled } : d))

    const setTime = (i, field, val) =>
        setSchedule(s => s.map((d, idx) => idx === i ? { ...d, [field]: val } : d))

    const save = async () => {
        const slots = schedule
            .map((d, i) => d.enabled ? { giorno: i, ora_inizio: d.ora_inizio, ora_fine: d.ora_fine } : null)
            .filter(Boolean)
        setSaving(true)
        const r = await fetch('/pages/api_bot.php?op=set_schedule', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bots: [...selected], schedule: slots }),
        })
        const d = await r.json()
        setSaving(false)
        if (d.success) { onSaved(); onClose() }
        else setMsg(d.message ?? 'Errore durante il salvataggio')
    }

    return (
        <div className={styles.modalOverlay}>
            <div className={styles.modal}>
                <h3>Imposta orari di presenza</h3>
                <p className={styles.modalSubtitle}>Bot: {[...selected].join(', ')}</p>
                <table className={styles.scheduleTable}>
                    <thead>
                        <tr>
                            <th>Giorno</th>
                            <th>Attivo</th>
                            <th>Dalle</th>
                            <th>Alle</th>
                        </tr>
                    </thead>
                    <tbody>
                        {GIORNI.map((g, i) => (
                            <tr key={i} className={schedule[i].enabled ? styles.dayActive : ''}>
                                <td>{g}</td>
                                <td>
                                    <input
                                        type="checkbox"
                                        checked={schedule[i].enabled}
                                        onChange={() => toggleDay(i)}
                                    />
                                </td>
                                <td>
                                    <input
                                        type="time"
                                        value={schedule[i].ora_inizio}
                                        disabled={!schedule[i].enabled}
                                        onChange={e => setTime(i, 'ora_inizio', e.target.value)}
                                    />
                                </td>
                                <td>
                                    <input
                                        type="time"
                                        value={schedule[i].ora_fine}
                                        disabled={!schedule[i].enabled}
                                        onChange={e => setTime(i, 'ora_fine', e.target.value)}
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {msg && <p className={styles.error}>{msg}</p>}
                <div className={styles.modalActions}>
                    <button onClick={onClose} className={styles.btnSecondary}>Annulla</button>
                    <button onClick={save} disabled={saving} className={styles.btnPrimary}>
                        {saving ? 'Salvataggio…' : 'Salva orari'}
                    </button>
                </div>
            </div>
        </div>
    )
}

// ── DmPanel ────────────────────────────────────────────────────────────────────

function DmPanel({ botNome, onClose }) {
    const [conversations, setConversations] = useState([])
    const [activeConv, setActiveConv]       = useState(null)
    const [messages, setMessages]           = useState([])
    const [replyText, setReplyText]         = useState('')
    const [sending, setSending]             = useState(false)
    const [loadingMsgs, setLoadingMsgs]     = useState(false)
    const messagesEndRef                    = useRef(null)

    useEffect(() => {
        fetch(`/pages/api_bot.php?op=get_dms&bot=${encodeURIComponent(botNome)}`)
            .then(r => r.json())
            .then(d => { if (d.success) setConversations(d.conversations ?? []) })
    }, [botNome])

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages])

    const openConversation = useCallback(async (conv) => {
        setActiveConv(conv)
        setLoadingMsgs(true)
        const r = await fetch(
            `/pages/api_bot.php?op=get_dms&bot=${encodeURIComponent(botNome)}&id_conv=${conv.id_conversazione}`
        )
        const d = await r.json()
        setLoadingMsgs(false)
        if (d.success) {
            setMessages(d.messages)
            setConversations(prev =>
                prev.map(c => c.id_conversazione === conv.id_conversazione ? { ...c, letto: 1 } : c)
            )
        }
    }, [botNome])

    const sendReply = async () => {
        if (!replyText.trim() || !activeConv || sending) return
        setSending(true)
        await fetch('/pages/api_bot.php?op=reply_dm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bot: botNome, destinatario: activeConv.altro_utente, testo: replyText.trim() }),
        })
        setReplyText('')
        setSending(false)
        // Ricarica i messaggi della conversazione
        const r = await fetch(
            `/pages/api_bot.php?op=get_dms&bot=${encodeURIComponent(botNome)}&id_conv=${activeConv.id_conversazione}`
        )
        const d = await r.json()
        if (d.success) setMessages(d.messages)
    }

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply() }
    }

    return (
        <div className={styles.dmPanel}>
            <div className={styles.dmHeader}>
                <h3>DM di {botNome}</h3>
                <button onClick={onClose} className={styles.closeBtn} title="Chiudi">✕</button>
            </div>
            <div className={styles.dmBody}>
                {/* Lista conversazioni */}
                <div className={styles.convList}>
                    {conversations.length === 0 && (
                        <p className={styles.empty} style={{ padding: '12px' }}>Nessun messaggio</p>
                    )}
                    {conversations.map(conv => (
                        <div
                            key={conv.id_conversazione}
                            className={`${styles.convItem} ${activeConv?.id_conversazione === conv.id_conversazione ? styles.convActive : ''}`}
                            onClick={() => openConversation(conv)}
                        >
                            <div className={styles.convUser}>
                                {!conv.letto && <span className={styles.unreadDot} />}
                                {conv.altro_utente}
                            </div>
                            <div className={styles.convPreview}>{conv.ultimo_testo}</div>
                            <div className={styles.convTime}>{conv.ultima_ora?.slice(0, 16)}</div>
                        </div>
                    ))}
                </div>

                {/* Thread messaggi */}
                <div className={styles.thread}>
                    {!activeConv && (
                        <p className={styles.empty} style={{ padding: '20px' }}>
                            Seleziona una conversazione
                        </p>
                    )}
                    {activeConv && (
                        <>
                            <div className={styles.threadHeader}>
                                Conversazione con <strong>{activeConv.altro_utente}</strong>
                            </div>
                            <div className={styles.messagesList}>
                                {loadingMsgs && <p className={styles.empty}>Caricamento…</p>}
                                {messages.map((m, i) => (
                                    <div
                                        key={i}
                                        className={`${styles.message} ${m.mittente === botNome ? styles.msgBot : styles.msgUser}`}
                                    >
                                        <span className={styles.msgMittente}>{m.mittente}</span>
                                        <span className={styles.msgTesto}>{m.testo}</span>
                                        <span className={styles.msgOra}>{m.ora_spedizione?.slice(0, 16)}</span>
                                    </div>
                                ))}
                                <div ref={messagesEndRef} />
                            </div>
                            <div className={styles.replyBar}>
                                <textarea
                                    value={replyText}
                                    onChange={e => setReplyText(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                    placeholder={`Rispondi come ${botNome}… (Invio per inviare, Shift+Invio per a capo)`}
                                    rows={2}
                                    className={styles.replyInput}
                                />
                                <button
                                    onClick={sendReply}
                                    disabled={sending || !replyText.trim()}
                                    className={styles.btnPrimary}
                                >
                                    {sending ? '…' : 'Invia'}
                                </button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    )
}

// ── GestioneBot ───────────────────────────────────────────────────────────────

export default function GestioneBot() {
    const [bots, setBots]                   = useState([])
    const [loading, setLoading]             = useState(true)
    const [error, setError]                 = useState(null)
    const [selected, setSelected]           = useState(new Set())
    const [passwordInput, setPasswordInput] = useState('')
    const [passwordMsg, setPasswordMsg]     = useState(null)
    const [showSchedule, setShowSchedule]   = useState(false)
    const [dmBot, setDmBot]                 = useState(null)
    const [actionMsg, setActionMsg]         = useState(null)

    const loadBots = useCallback(() => {
        setLoading(true)
        fetch('/pages/api_bot.php?op=list')
            .then(r => r.json())
            .then(d => {
                setLoading(false)
                if (d.success) setBots(d.bots ?? [])
                else setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => { setLoading(false); setError('Errore di rete') })
    }, [])

    useEffect(() => { loadBots() }, [loadBots])

    const toggleSelect = (nome) => {
        setSelected(prev => {
            const next = new Set(prev)
            if (next.has(nome)) next.delete(nome)
            else next.add(nome)
            return next
        })
    }

    const toggleSelectAll = () => {
        if (selected.size === bots.length && bots.length > 0) setSelected(new Set())
        else setSelected(new Set(bots.map(b => b.nome)))
    }

    const togglePresence = async (online) => {
        if (selected.size === 0) return
        await fetch('/pages/api_bot.php?op=toggle_presence', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bots: [...selected], online }),
        })
        setActionMsg(online ? 'Bot portati online.' : 'Bot portati offline.')
        setTimeout(() => setActionMsg(null), 3000)
        loadBots()
    }

    const setPassword = async () => {
        if (passwordInput.length < 6) {
            setPasswordMsg('Minimo 6 caratteri.')
            return
        }
        const r = await fetch('/pages/api_bot.php?op=set_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: passwordInput }),
        })
        const d = await r.json()
        setPasswordMsg(d.success ? 'Password aggiornata per tutti i bot.' : (d.message ?? 'Errore'))
        if (d.success) setPasswordInput('')
        setTimeout(() => setPasswordMsg(null), 4000)
    }

    const openDm = (nome) => {
        setDmBot(prev => prev === nome ? null : nome)
    }

    if (loading) return <div className={styles.wrap}><p>Caricamento…</p></div>
    if (error)   return <div className={styles.wrap}><p className={styles.error}>{error}</p></div>

    return (
        <div className={styles.wrap}>
            <h2 className={styles.title}>Gestione Bot</h2>

            {/* Password comune */}
            <section className={styles.section}>
                <h3>Password comune</h3>
                <div className={styles.passwordRow}>
                    <input
                        type="password"
                        value={passwordInput}
                        onChange={e => setPasswordInput(e.target.value)}
                        placeholder="Nuova password per tutti i bot"
                        className={styles.input}
                        onKeyDown={e => e.key === 'Enter' && setPassword()}
                    />
                    <button onClick={setPassword} className={styles.btnPrimary}>Aggiorna</button>
                </div>
                {passwordMsg && <p className={styles.feedback}>{passwordMsg}</p>}
            </section>

            {/* Bulk actions */}
            {selected.size > 0 && (
                <div className={styles.bulkBar}>
                    <span>{selected.size} {selected.size === 1 ? 'bot selezionato' : 'bot selezionati'}</span>
                    <button onClick={() => togglePresence(true)}  className={styles.btnOnline}>Porta Online</button>
                    <button onClick={() => togglePresence(false)} className={styles.btnOffline}>Porta Offline</button>
                    <button onClick={() => setShowSchedule(true)} className={styles.btnSecondary}>Imposta Orari</button>
                </div>
            )}
            {actionMsg && <p className={styles.feedback}>{actionMsg}</p>}

            {/* Lista bot */}
            <section className={styles.section}>
                <h3>Lista Bot</h3>
                {bots.length === 0 ? (
                    <p className={styles.empty}>
                        Nessun bot trovato. Crea personaggi con sesso=&apos;b&apos; nel database.
                    </p>
                ) : (
                    <table className={styles.table}>
                        <thead>
                            <tr>
                                <th>
                                    <input
                                        type="checkbox"
                                        checked={selected.size === bots.length && bots.length > 0}
                                        onChange={toggleSelectAll}
                                    />
                                </th>
                                <th>Nome</th>
                                <th>Razza</th>
                                <th>Mestiere</th>
                                <th>Status</th>
                                <th>DM</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            {bots.map(bot => (
                                <tr key={bot.nome} className={selected.has(bot.nome) ? styles.rowSelected : ''}>
                                    <td>
                                        <input
                                            type="checkbox"
                                            checked={selected.has(bot.nome)}
                                            onChange={() => toggleSelect(bot.nome)}
                                        />
                                    </td>
                                    <td>{bot.nome} {bot.cognome}</td>
                                    <td>{bot.nome_razza}</td>
                                    <td>{bot.nome_mestiere}</td>
                                    <td>
                                        <span className={bot.online ? styles.badgeOnline : styles.badgeOffline}>
                                            {bot.online ? 'Online' : 'Offline'}
                                        </span>
                                        {!!bot.paused && (
                                            <span className={styles.badgePaused}>(pausa)</span>
                                        )}
                                    </td>
                                    <td>
                                        {bot.unread_conv > 0 && (
                                            <span className={styles.badgeDm}>{bot.unread_conv}</span>
                                        )}
                                    </td>
                                    <td>
                                        <button
                                            onClick={() => openDm(bot.nome)}
                                            className={styles.btnSm}
                                        >
                                            {dmBot === bot.nome ? 'Chiudi DM' : 'Visualizza DM'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </section>

            {/* Pannello DM */}
            {dmBot && (
                <DmPanel
                    botNome={dmBot}
                    onClose={() => setDmBot(null)}
                />
            )}

            {/* Modal schedule */}
            {showSchedule && (
                <ScheduleModal
                    selected={selected}
                    onClose={() => setShowSchedule(false)}
                    onSaved={loadBots}
                />
            )}
        </div>
    )
}
