/**
 * MessagesInbox.jsx
 *
 * Inbox dei messaggi privati — rimpiazza pages/mex_privati/index.inc.php
 * e il routing in pages/messages_center.inc.php.
 *
 * Funzionalità:
 *   - Lista conversazioni suddivisa in tab ON / OFF
 *   - Apertura thread inline (nessun iframe, nessun reload)
 *   - Risposta rapida direttamente nel thread
 *   - Composizione nuovo messaggio con destinatario e tipo ON/OFF
 *   - Aggiornamento real-time tramite socket 'dm:update'
 *     (ricevuto quando arriva un nuovo messaggio o viene letto uno esistente)
 *
 * API utilizzate:
 *   GET  pages/api_messages.php?op=list          → lista conversazioni
 *   GET  pages/api_messages.php?op=read&...      → messaggi di un thread
 *   POST pages/api_messages.php?op=send          → invia messaggio
 *   GET  pages/api_messages.php?op=archive       → archivio personale
 *
 * Montaggio: via ct:ready su #messages-inbox-container in messages_center.inc.php
 *
 * Nota sui path immagini: i path usano '../themes/...' perché main.php è nella root
 * e la struttura URL attesa è la stessa del vecchio PHP.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import styles from './MessagesInbox.module.css'

// ---------------------------------------------------------------------------
// COSTANTI — path immagini (stesse del vecchio PHP)
// ---------------------------------------------------------------------------

const IMG = {
    /** Icona tab OFF con nuovi messaggi */
    offAcceso:  '../themes/crystal/imgs/sms/MessaggioOff_Acceso.gif',
    /** Icona tab OFF senza nuovi messaggi */
    offSpento:  '../themes/crystal/imgs/sms/MessaggioOff_Spento.png',
    /** Icona tab ON con nuovi messaggi */
    onAcceso:   '../themes/crystal/imgs/sms/MessaggioOn_Acceso.gif',
    /** Icona tab ON senza nuovi messaggi */
    onSpento:   '../themes/crystal/imgs/sms/MessaggioOn_Spento.png',
    /** Avatar placeholder per messaggi di gruppo */
    gruppo:     '../themes/crystal/imgs/sms/img_gruppo.jpg',
    /** Avatar placeholder per messaggi globali */
    globale:    '../themes/crystal/imgs/sms/img_globale.jpg',
}

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

/**
 * Formatta una data ISO in formato leggibile 'gg-mm-aaaa hh:mm'.
 * Se la conversazione è ON, converte l'anno nel calendario di gioco
 * (anno reale + 1053 per Crystal Tokyo).
 *
 * @param {string}  isoDate - Stringa data ISO dal DB
 * @param {boolean} ongame  - true se il messaggio è ON (anno di gioco)
 * @returns {string} Data formattata
 */
function formatDate(isoDate, ongame) {
    if (!isoDate) return ''
    const d = new Date(isoDate)
    if (ongame) d.setFullYear(d.getFullYear() + 1053)
    const dd = String(d.getDate()).padStart(2, '0')
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const yy = d.getFullYear()
    const hh = String(d.getHours()).padStart(2, '0')
    const mi = String(d.getMinutes()).padStart(2, '0')
    return `${dd}-${mm}-${yy} ${hh}:${mi}`
}

/**
 * Determina l'URL dell'avatar corretto in base al tipo e al mittente.
 * I mittenti speciali ('Segnalazione', 'Calendario') hanno icone dedicate.
 * Per le conversazioni individuali l'avatar viene da api_messages.php?op=list
 * (campo avatar_url, aggiunto nella risposta lato server).
 *
 * @param {Object} conv - Oggetto conversazione dalla lista
 * @returns {string} URL dell'avatar da mostrare
 */
function getAvatarUrl(conv) {
    if (conv.tipo === 'gruppo')  return IMG.gruppo
    if (conv.tipo === 'globale') return IMG.globale
    // Mittenti speciali di sistema
    if (conv.ultimo_mittente === 'Segnalazione') return '../pages/msg/img/segnalazione.png'
    if (conv.ultimo_mittente === 'Calendario')   return '../pages/msg/img/calendario.png'
    // Individuale: avatar restituito dall'API (url_img_chat del contatto)
    return conv.avatar_url || ''
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: singola voce nella lista conversazioni
// ---------------------------------------------------------------------------

/**
 * Card di una singola conversazione nella sidebar.
 *
 * @param {Object}   props.conv       - Dati della conversazione
 * @param {boolean}  props.isSelected - true se questa è la conversazione aperta
 * @param {Function} props.onClick    - Callback al click
 */
function ConvItem({ conv, isSelected, onClick }) {
    /** Anteprima testo: max 30 caratteri senza HTML */
    const preview = (conv.ultimo_testo || '').replace(/<[^>]*>/g, '').slice(0, 30) + (conv.ultimo_testo?.length > 30 ? '...' : '')

    return (
        <a
            href="#"
            onClick={e => { e.preventDefault(); onClick(conv) }}
            className={styles.noDecoration}
        >
            <div className={`message-item ${isSelected ? 'selected' : ''}`}>

                {/* Avatar del contatto/gruppo */}
                <img
                    src={getAvatarUrl(conv) || `../themes/crystal/imgs/race_presenti/Png.png`}
                    alt="Avatar"
                    className={conv.non_letto ? 'unread-avatar' : ''}
                />

                {/* Dettagli: nome, data, anteprima testo */}
                <div className="message-details">
                    <p className="sender">
                        {conv.display_name}
                        {/* Punto indicatore nuovo messaggio */}
                        {conv.non_letto && <span className={styles.unreadDot}>●</span>}
                    </p>
                    <p className="date">{formatDate(conv.ora, conv.ongame)}</p>
                    <p className="preview">{preview || '(nessun testo)'}</p>
                </div>
            </div>
        </a>
    )
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: thread di una conversazione aperta
// ---------------------------------------------------------------------------

/**
 * Vista del thread: mostra tutti i messaggi e il form di risposta.
 *
 * @param {Array}    props.messages     - Messaggi del thread
 * @param {Object}   props.conv         - Conversazione corrente
 * @param {boolean}  props.loading      - true durante il fetch
 * @param {string}   props.replyText    - Testo nel campo risposta
 * @param {Function} props.setReplyText - Setter del testo risposta
 * @param {boolean}  props.sending      - true durante l'invio
 * @param {Function} props.onSend       - Callback per inviare la risposta
 * @param {Function} props.onBack       - Callback per tornare alla lista (mobile)
 * @param {Function} props.onDelete     - Callback per eliminare la conversazione
 * @param {Function} props.onDeleteMsgs - Callback ([{mittente,ora}]) per eliminare messaggi selezionati
 */
function ThreadView({ messages, conv, loading, replyText, setReplyText, sending, onSend, onBack, onDelete, onDeleteMsgs }) {
    /** Ref per lo scroll automatico all'ultimo messaggio */
    const bottomRef = useRef(null)
    /** Ref alla textarea di risposta: usato per recuperare il focus dopo l'invio */
    const textareaRef = useRef(null)
    /** Traccia il valore precedente di sending per intercettare la transizione true→false */
    const prevSendingRef = useRef(false)
    const [selectMode, setSelectMode] = useState(false)
    const [selected,   setSelected]   = useState(new Set())

    // Scrolla in fondo ogni volta che arrivano nuovi messaggi
    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages])

    // Rimette il focus sulla textarea non appena l'invio si conclude
    useEffect(() => {
        if (prevSendingRef.current && !sending) {
            textareaRef.current?.focus()
        }
        prevSendingRef.current = sending
    }, [sending])

    // Reset selezione al cambio di conversazione
    useEffect(() => {
        setSelectMode(false)
        setSelected(new Set())
    }, [conv])

    const msgKey = (msg) => `${msg.mittente}|${msg.ora}`

    const toggleSelect = (msg) => {
        const k = msgKey(msg)
        setSelected(prev => {
            const next = new Set(prev)
            if (next.has(k)) next.delete(k)
            else next.add(k)
            return next
        })
    }

    const exitSelectMode = () => {
        setSelectMode(false)
        setSelected(new Set())
    }

    const handleDeleteSelected = () => {
        if (selected.size === 0) return
        const messaggi = [...selected].map(k => {
            const sep = k.indexOf('|')
            return { mittente: k.slice(0, sep), ora: k.slice(sep + 1) }
        })
        onDeleteMsgs(messaggi)
        exitSelectMode()
    }

    if (loading) return <div className={styles.loading}>Caricamento messaggi...</div>

    return (
        <div className={`thread-container ${styles.threadWrap}`}>

            {/* Header: normale → [← nome badge] [Seleziona][Elimina conv]; selezione → [←] [Elimina N][Annulla] */}
            <div className="thread-header">
                <div className={styles.threadHeaderRow}>
                    <div>
                        <button onClick={onBack} className={styles.backBtn}>←</button>
                        {!selectMode && (
                            <>
                                <strong>{conv.display_name}</strong>
                                <span className={styles.onlineBadge}>
                                    {conv.ongame ? '[ON]' : '[OFF]'}
                                    {conv.tipo === 'gruppo' ? ' · Gruppo' : ''}
                                    {conv.tipo === 'globale' ? ' · Globale' : ''}
                                </span>
                            </>
                        )}
                    </div>
                    <div className={styles.headerActions}>
                        {selectMode ? (
                            <>
                                <button
                                    onClick={handleDeleteSelected}
                                    disabled={selected.size === 0}
                                    className={styles.deleteConvBtn}
                                >
                                    {selected.size > 0 ? `Elimina (${selected.size})` : 'Elimina'}
                                </button>
                                <button onClick={exitSelectMode} className={styles.cancelSelectBtn}>
                                    Annulla
                                </button>
                            </>
                        ) : conv.tipo !== 'globale' && (
                            <>
                                <button onClick={() => setSelectMode(true)} className={styles.selectModeBtn}>
                                    Seleziona
                                </button>
                                <button
                                    onClick={() => { if (confirm('Eliminare questa conversazione?')) onDelete() }}
                                    className={styles.deleteConvBtn}
                                    title="Elimina conversazione"
                                >
                                    Elimina
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/*
              * Lista messaggi: altezza massima fissa con scroll interno.
              * Senza max-height il div cresce con il contenuto e scrolla tutta la pagina.
              * calc(100vh - 180px) lascia spazio per header e form risposta.
              */}
            <div className="thread-messages">
                {messages.length === 0 && (
                    <p className={styles.emptyMsg}>Nessun messaggio.</p>
                )}
                {messages.map((msg, i) => {
                    const k = msgKey(msg)
                    const isSel = selected.has(k)
                    return (
                        <div
                            key={i}
                            className={[
                                'thread-message',
                                styles.msgRow,
                                selectMode ? styles.selectableMsgRow : '',
                                isSel      ? styles.selectedMsgRow   : '',
                            ].filter(Boolean).join(' ')}
                            onClick={selectMode ? () => toggleSelect(msg) : undefined}
                        >
                            {/* Checkbox visibile in modalità selezione */}
                            {selectMode && (
                                <input
                                    type="checkbox"
                                    checked={isSel}
                                    onChange={() => toggleSelect(msg)}
                                    className={styles.msgCheckbox}
                                    onClick={e => e.stopPropagation()}
                                />
                            )}

                            {/* Avatar mittente */}
                            {msg.avatar && (
                                <img
                                    src={msg.avatar}
                                    alt={msg.mittente}
                                    className={styles.msgAvatar}
                                />
                            )}

                            {/* Corpo del messaggio */}
                            <div className={styles.msgBody}>
                                <div className={styles.msgMeta}>
                                    <strong>{msg.mittente}</strong>
                                    {' · '}
                                    {formatDate(msg.ora, msg.ongame)}
                                </div>
                                <div
                                    className="thread-message-text"
                                    dangerouslySetInnerHTML={{ __html: msg.testo }}
                                />
                            </div>
                        </div>
                    )
                })}
                <div ref={bottomRef} />
            </div>

            {/* Form di risposta — nascosto per globali e durante la selezione */}
            {conv.tipo !== 'globale' && !selectMode && (
                <div className="thread-reply">
                    <textarea
                        ref={textareaRef}
                        value={replyText}
                        onChange={e => setReplyText(e.target.value)}
                        placeholder="Scrivi una risposta..."
                        rows="3"
                        className={styles.replyTextarea}
                        // Invio con Ctrl+Enter per comodità
                        onKeyDown={e => { if (e.ctrlKey && e.key === 'Enter') onSend() }}
                    />
                    <button
                        onClick={onSend}
                        disabled={sending || !replyText.trim()}
                        className={styles.sendBtn}
                    >
                        {sending ? '...' : 'Invia'}
                    </button>
                </div>
            )}
        </div>
    )
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: form composizione nuovo messaggio
// ---------------------------------------------------------------------------

/**
 * Form per scrivere un nuovo messaggio a un destinatario qualsiasi.
 * Se l'utente ha permessi elevati mostra un dropdown per l'invio massivo.
 *
 * @param {Function} props.onSend     - Callback con { destinatario, testo, ongame }
 * @param {Function} props.onSendMass - Callback con { tipo, messaggio, ongame, destinatari? }
 * @param {Function} props.onCancel   - Callback per annullare
 * @param {boolean}  props.sending    - true durante l'invio
 * @param {Object}   props.perms      - Permessi utente { admin, master, capogilda, capomestiere }
 */
function ComposeView({ onSend, onSendMass, onCancel, sending, defaultDest = '', perms = {} }) {
    const [dest,     setDest]     = useState(defaultDest)
    const [testo,    setTesto]    = useState('')
    const [ongame,   setOngame]   = useState(0)
    const [massType, setMassType] = useState('---')

    const hasMassPerms = perms.admin || perms.master || perms.capogilda || perms.capomestiere
    const isMassMode   = massType !== '---'
    const isMultiplo   = massType === 'multiplo'

    const massOptions = []
    if (perms.admin || perms.master || perms.capogilda || perms.capomestiere)
        massOptions.push({ value: 'multiplo',        label: 'Multiplo (nomi separati da virgola)' })
    if (perms.admin || perms.master)
        massOptions.push({ value: 'presenti',        label: 'Tutti i presenti' })
    if (perms.admin)
        massOptions.push({ value: 'broadcast',       label: 'Tutti i personaggi' })
    if (perms.admin || perms.master || perms.capogilda)
        massOptions.push({ value: 'capogilda',       label: 'Tutti i capogilda' })
    if (perms.admin || perms.master || perms.capomestiere)
        massOptions.push({ value: 'capomestiere',    label: 'Tutti i capomestiere' })
    if (perms.admin || perms.capogilda)
        massOptions.push({ value: 'gilda',           label: 'Tutta la famiglia' })
    if (perms.admin || perms.capomestiere)
        massOptions.push({ value: 'tutto_mestiere',  label: 'Tutto il mestiere' })
    if (perms.admin || perms.master)
        massOptions.push({ value: 'tutto_inclinati', label: 'Tutti gli inclinati' })

    const canSend = !!testo.trim() && (isMassMode ? (!isMultiplo || !!dest.trim()) : !!dest.trim())

    const handleSend = () => {
        if (!canSend || sending) return
        if (isMassMode) {
            const payload = { tipo: massType, messaggio: testo, ongame }
            if (isMultiplo) payload.destinatari = dest
            onSendMass(payload)
        } else {
            onSend({ destinatario: dest.trim(), testo, ongame })
        }
    }

    return (
        <div className={styles.composeWrap}>
            <h3>Nuovo Messaggio</h3>

            {hasMassPerms && (
                <div className={styles.formField}>
                    <label className={styles.formLabel}>Destinatari</label>
                    <select value={massType} onChange={e => setMassType(e.target.value)}>
                        <option value="---">— Singolo —</option>
                        {massOptions.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>
            )}

            {(!isMassMode || isMultiplo) && (
                <div className={styles.formField}>
                    <label className={styles.formLabel}>
                        {isMultiplo ? 'Nomi (separati da virgola)' : 'Destinatario'}
                    </label>
                    <input
                        type="text"
                        value={dest}
                        onChange={e => setDest(e.target.value)}
                        placeholder={isMultiplo ? 'Nome1, Nome2, Nome3...' : 'Nome personaggio'}
                        className={styles.fullWidth}
                    />
                </div>
            )}

            <div className={styles.formField}>
                <label className={styles.formLabel}>Tipo</label>
                <select value={ongame} onChange={e => setOngame(parseInt(e.target.value))}>
                    <option value={0}>OFF (fuori gioco)</option>
                    <option value={1}>ON (in gioco)</option>
                </select>
            </div>

            <div className={styles.formField}>
                <label className={styles.formLabel}>Messaggio</label>
                <textarea
                    value={testo}
                    onChange={e => setTesto(e.target.value)}
                    rows="5"
                    className={styles.fullResize}
                />
            </div>

            <div className={styles.formActions}>
                <button onClick={handleSend} disabled={sending || !canSend}>
                    {sending ? 'Invio...' : 'Invia'}
                </button>
                <button onClick={onCancel}>Annulla</button>
            </div>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function MessagesInbox({ toPg = null }) {

    // --- stato lista ---
    /** Tutte le conversazioni dalla API */
    const [conversations, setConversations] = useState([])
    /** Tab attiva: 'on' | 'off' — default OFF come nel vecchio PHP */
    const [activeTab, setActiveTab] = useState('off')
    /** true durante il primo caricamento della lista */
    const [loadingList, setLoadingList] = useState(true)

    // --- stato thread ---
    /** Conversazione attualmente aperta, o null se nessuna */
    const [selectedConv, setSelectedConv] = useState(null)
    /** Messaggi del thread aperto */
    const [messages, setMessages] = useState([])
    /** true mentre il thread sta caricando */
    const [loadingThread, setLoadingThread] = useState(false)

    // --- permessi utente (restituiti da op=list) ---
    const [perms, setPerms] = useState({})

    // --- stato risposta / composizione ---
    /** Testo nel campo risposta rapida */
    const [replyText, setReplyText] = useState('')
    /** true durante l'invio di una risposta o nuovo messaggio */
    const [sending, setSending] = useState(false)
    /**
     * Vista attiva:
     *   'list'    — lista conversazioni a piena larghezza
     *   'thread'  — thread aperto a piena larghezza
     *   'compose' — form nuovo messaggio
     */
    const [view, setView] = useState('list')
    /** Destinatario pre-compilato nella vista compose (da ?to= URL param) */
    const [composeDest, setComposeDest] = useState('')

    // Ref alla conversazione aperta: usato dal listener socket per aggiornare
    // il thread senza passarlo come dipendenza all'useEffect (evita ri-registrazioni)
    const selectedConvRef = useRef(null)
    useEffect(() => { selectedConvRef.current = selectedConv }, [selectedConv])

    // Ultimo valore di toPg già gestito: evita di riaprire la stessa conversazione
    // se il componente ri-renderizza senza che la prop sia cambiata
    const lastHandledTo = useRef(null)

    // ---------------------------------------------------------------------------
    // FETCH LISTA
    // ---------------------------------------------------------------------------

    /**
     * Ricarica la lista delle conversazioni dall'API.
     * Chiamato al mount e ad ogni evento socket 'dm:update'.
     */
    const fetchList = useCallback(() => {
        fetch('/pages/api_messages.php?op=list')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setConversations(data.conversations)
                    setPerms(data.perms ?? {})
                }
                setLoadingList(false)
            })
            .catch(err => {
                console.error('[MessagesInbox] Errore lista:', err)
                setLoadingList(false)
            })
    }, [])

    // ---------------------------------------------------------------------------
    // APERTURA THREAD
    // ---------------------------------------------------------------------------

    /**
     * Apre una conversazione e carica i messaggi del thread.
     * Segna automaticamente come letta la conversazione (tramite op=read).
     *
     * @param {Object} conv - Oggetto conversazione dalla lista
     */
    const openConversation = useCallback((conv) => {
        setSelectedConv(conv)
        setView('thread')      // passa alla vista thread a piena larghezza
        setLoadingThread(true)
        setMessages([])
        setReplyText('')

        // op=read: segna come letto + restituisce messaggi + emette dm:update al self.
        // Per le conversazioni individuali passa ongame in modo che il server filtri
        // solo i messaggi del tipo corretto (gestisce anche le conversazioni miste legacy).
        const qs = conv.tipo === 'individuale'
            ? `conversazione_id=${conv.conversazione_id}&ongame=${conv.ongame ? 1 : 0}`
            : `gruppo_id=${conv.gruppo_id}`

        fetch(`/pages/api_messages.php?op=read&${qs}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) setMessages(data.messages)
                setLoadingThread(false)
                fetchList() // aggiorna la lista per riflettere lo stato "letto"
            })
            .catch(err => {
                console.error('[MessagesInbox] Errore thread:', err)
                setLoadingThread(false)
            })
    }, [fetchList])

    /**
     * Aggiorna silenziosamente il thread aperto senza segnarlo come letto e senza
     * emettere dm:update. Usato dal listener socket per il real-time del thread:
     * evita il loop dm:update → re-read → dm:update → ...
     *
     * Usa ?silent=1: l'API restituisce i messaggi ma non aggiorna il DB.
     */
    const fetchThreadSilent = useCallback(() => {
        const conv = selectedConvRef.current
        if (!conv) return
        const qs = conv.tipo === 'individuale'
            ? `conversazione_id=${conv.conversazione_id}&ongame=${conv.ongame ? 1 : 0}&silent=1`
            : `gruppo_id=${conv.gruppo_id}&silent=1`
        fetch(`/pages/api_messages.php?op=read&${qs}`)
            .then(r => r.json())
            .then(data => { if (data.success) setMessages(data.messages) })
            .catch(console.error)
    }, [])

    // ---------------------------------------------------------------------------
    // SOCKET — aggiornamento real-time
    // ---------------------------------------------------------------------------

    useEffect(() => {
        // Caricamento iniziale della lista
        fetchList()

        const sock = window.ctSocket
        if (sock) {
            // Handler salvato in variabile: sock.off() senza riferimento specifico
            // rimuoverebbe TUTTI i listener dm:update (incluso quello di FrameMessaggi)
            const dmHandler = () => {
                fetchList()
                fetchThreadSilent()
            }
            sock.on('dm:update', dmHandler)
            return () => { sock.off('dm:update', dmHandler) }
        }
    }, [fetchList, fetchThreadSilent])

    // ---------------------------------------------------------------------------
    // AUTO-OPEN DA ?to= URL PARAM
    // ---------------------------------------------------------------------------

    /**
     * Dopo il caricamento iniziale della lista, legge il parametro ?to= dalla URL.
     * Se esiste una conversazione individuale con quel nome la apre direttamente;
     * altrimenti passa alla vista compose con il destinatario pre-compilato.
     * Eseguito una sola volta al mount grazie ad autoOpenHandled.
     */
    useEffect(() => {
        if (loadingList || !toPg || toPg === lastHandledTo.current) return
        lastHandledTo.current = toPg
        const match = conversations.find(
            c => c.tipo === 'individuale' && c.display_name.toLowerCase() === toPg.toLowerCase()
        )
        if (match) {
            openConversation(match)
        } else {
            setComposeDest(toPg)
            setView('compose')
        }
    }, [loadingList, conversations, openConversation, toPg])

    // ---------------------------------------------------------------------------
    // INVIO RISPOSTA
    // ---------------------------------------------------------------------------

    /**
     * Invia una risposta al thread aperto.
     * Dopo l'invio ricarica il thread e svuota il campo.
     */
    const sendReply = () => {
        if (!replyText.trim() || !selectedConv || sending) return
        setSending(true)

        // Costruisce il payload in base al tipo di conversazione
        const body = selectedConv.tipo === 'individuale'
            ? {
                destinatario:    selectedConv.display_name,
                messaggio:       replyText,
                conversazione_id: selectedConv.conversazione_id,
                ongame:          selectedConv.ongame ? 1 : 0,
              }
            : {
                gruppo_id: selectedConv.gruppo_id,
                messaggio: replyText,
                ongame:    selectedConv.ongame ? 1 : 0,
              }

        fetch('/pages/api_messages.php?op=send', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setReplyText('')
                    // Ricarica il thread dopo l'invio (il socket dm:update arriverà,
                    // ma meglio forzare subito il refresh per responsività)
                    fetchThreadSilent()
                } else {
                    alert(data.message || 'Errore nell\'invio')
                }
            })
            .catch(err => console.error('[MessagesInbox] Errore invio:', err))
            .finally(() => setSending(false))
    }

    // ---------------------------------------------------------------------------
    // INVIO NUOVO MESSAGGIO
    // ---------------------------------------------------------------------------

    /**
     * Invia un messaggio a un nuovo destinatario dalla vista compose.
     *
     * @param {Object} params - { destinatario, testo, ongame }
     */
    const sendNew = ({ destinatario, testo, ongame }) => {
        if (sending) return
        setSending(true)

        fetch('/pages/api_messages.php?op=send', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ destinatario, messaggio: testo, ongame }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setView('list')  // torna alla lista dopo l'invio
                    fetchList()
                } else {
                    alert(data.message || 'Errore nell\'invio')
                }
            })
            .catch(err => console.error('[MessagesInbox] Errore nuovo messaggio:', err))
            .finally(() => setSending(false))
    }

    // ---------------------------------------------------------------------------
    // INVIO MASSIVO
    // ---------------------------------------------------------------------------

    /**
     * Invia un messaggio a un gruppo di destinatari tramite op=sendMass.
     *
     * @param {Object} params - { tipo, messaggio, ongame, destinatari? }
     */
    const sendMass = ({ tipo, messaggio, ongame, destinatari }) => {
        if (sending) return
        setSending(true)

        fetch('/pages/api_messages.php?op=sendMass', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ tipo, messaggio, ongame, destinatari }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setView('list')
                    fetchList()
                    alert(data.message)
                } else {
                    alert(data.message || 'Errore nell\'invio')
                }
            })
            .catch(err => console.error('[MessagesInbox] Errore invio massivo:', err))
            .finally(() => setSending(false))
    }

    // ---------------------------------------------------------------------------
    // ELIMINAZIONE MESSAGGI SELEZIONATI
    // ---------------------------------------------------------------------------

    const handleDeleteMsgs = useCallback((messaggi) => {
        if (!selectedConv) return
        const body = selectedConv.tipo === 'individuale'
            ? { conversazione_id: selectedConv.conversazione_id, messaggi }
            : { gruppo_id: selectedConv.gruppo_id, messaggi }

        fetch('/pages/api_messages.php?op=delete_msgs', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) fetchThreadSilent()
                else alert(data.message || 'Errore durante l\'eliminazione')
            })
            .catch(console.error)
    }, [selectedConv, fetchThreadSilent])

    // ---------------------------------------------------------------------------
    // ELIMINAZIONE CONVERSAZIONE
    // ---------------------------------------------------------------------------

    /**
     * Elimina la conversazione aperta dal punto di vista dell'utente corrente.
     * Per le conversazioni individuali, se anche l'altro utente l'ha eliminata
     * i messaggi sms vengono rimossi dal server.
     */
    const handleDeleteConv = useCallback(() => {
        if (!selectedConv) return
        const body = selectedConv.tipo === 'individuale'
            ? { conversazione_id: selectedConv.conversazione_id }
            : { gruppo_id: selectedConv.gruppo_id }

        fetch('/pages/api_messages.php?op=delete_conv', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setView('list')
                    setSelectedConv(null)
                    setMessages([])
                    fetchList()
                } else {
                    alert(data.message || 'Errore durante l\'eliminazione')
                }
            })
            .catch(console.error)
    }, [selectedConv, fetchList])

    // ---------------------------------------------------------------------------
    // DATI DERIVATI
    // ---------------------------------------------------------------------------

    /** Conversazioni ON (ongame = true o globale) */
    const convOn  = conversations.filter(c => c.ongame  || c.is_globale)
    /** Conversazioni OFF */
    const convOff = conversations.filter(c => !c.ongame && !c.is_globale)

    /** true se ci sono messaggi non letti nella tab ON */
    const hasNewOn  = convOn.some(c => c.non_letto)
    /** true se ci sono messaggi non letti nella tab OFF */
    const hasNewOff = convOff.some(c => c.non_letto)

    /** Conversazioni da mostrare nella sidebar in base alla tab attiva */
    const displayed = activeTab === 'on' ? convOn : convOff

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    /**
     * Layout a colonna singola con tre viste esclusive:
     *   'list'    → lista conversazioni a piena larghezza
     *   'thread'  → thread aperto a piena larghezza + pulsante back
     *   'compose' → form nuovo messaggio + pulsante back
     *
     * Il .container di new_sms.css è display:flex, ma qui mostriamo una sola
     * colonna alla volta, quindi usa width:100% su ciascuna vista.
     */

    // --- VISTA LISTA ---
    if (view === 'list') {
        return (
            <div id="messages-center-app" className="container">
                <div className="sidebar" style={{ width: '100%' }}>

                    {/* Intestazione */}
                    <div className="header">
                        <div className="header-container">
                            <h1 className="header-title">Messaggi Privati</h1>
                        </div>
                    </div>

                    {/* Toggle tab ON / OFF */}
                    <div className="toggle-buttons">
                        <div
                            className={`toggle-button ${activeTab === 'off' ? 'active' : ''}`}
                            onClick={() => setActiveTab('off')}
                            title="Messaggi OFF"
                        >
                            <img src={hasNewOff ? IMG.offAcceso : IMG.offSpento} alt="Messaggi Off" />
                        </div>
                        <div
                            className={`toggle-button ${activeTab === 'on' ? 'active' : ''}`}
                            onClick={() => setActiveTab('on')}
                            title="Messaggi ON"
                        >
                            <img src={hasNewOn ? IMG.onAcceso : IMG.onSpento} alt="Messaggi On" />
                        </div>
                    </div>

                    {/* Lista conversazioni — loading / vuota / popolata */}
                    <div className="messages-list" id="messages-list">
                        <div
                            className="message-section"
                            id="messages-off"
                            style={{ display: activeTab === 'off' ? 'block' : 'none' }}
                        >
                            {loadingList ? (
                                <p style={{ padding: '10px', color: 'var(--color-text-muted)' }}>Caricamento...</p>
                            ) : convOff.length === 0 ? (
                                <p style={{ padding: '10px', color: 'var(--color-text-muted)', fontStyle: 'italic' }}>Nessun messaggio OFF.</p>
                            ) : (
                                convOff.map(conv => (
                                    <ConvItem key={`${conv.tipo}-${conv.conversazione_id}`} conv={conv} isSelected={false} onClick={openConversation} />
                                ))
                            )}
                        </div>
                        <div
                            className="message-section active"
                            id="messages-on"
                            style={{ display: activeTab === 'on' ? 'block' : 'none' }}
                        >
                            {loadingList ? (
                                <p style={{ padding: '10px', color: 'var(--color-text-muted)' }}>Caricamento...</p>
                            ) : convOn.length === 0 ? (
                                <p style={{ padding: '10px', color: 'var(--color-text-muted)', fontStyle: 'italic' }}>Nessun messaggio ON.</p>
                            ) : (
                                convOn.map(conv => (
                                    <ConvItem key={`${conv.tipo}-${conv.conversazione_id}`} conv={conv} isSelected={false} onClick={openConversation} />
                                ))
                            )}
                        </div>
                    </div>

                    {/* Azioni in fondo */}
                    <div className="bottom-bar">
                        <button id="new-message-button" onClick={() => setView('compose')}>
                            Nuovo Messaggio
                        </button>
                    </div>
                </div>
            </div>
        )
    }

    // --- VISTA THREAD ---
    if (view === 'thread' && selectedConv) {
        return (
            <div id="messages-center-app" className="container">
                <div className="main-content" style={{ width: '100%', display: 'flex', flexDirection: 'column' }}>
                    <ThreadView
                        messages={messages}
                        conv={selectedConv}
                        loading={loadingThread}
                        replyText={replyText}
                        setReplyText={setReplyText}
                        sending={sending}
                        onSend={sendReply}
                        onBack={() => { setView('list'); setSelectedConv(null) }}
                        onDelete={handleDeleteConv}
                        onDeleteMsgs={handleDeleteMsgs}
                    />
                </div>
            </div>
        )
    }

    // --- VISTA COMPOSIZIONE ---
    return (
        <div id="messages-center-app" className="container">
            <div className="main-content" style={{ width: '100%' }}>
                <ComposeView
                    onSend={sendNew}
                    onSendMass={sendMass}
                    onCancel={() => setView('list')}
                    sending={sending}
                    defaultDest={composeDest}
                    perms={perms}
                />
            </div>
        </div>
    )
}
