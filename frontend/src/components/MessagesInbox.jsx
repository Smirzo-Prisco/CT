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
            style={{ textDecoration: 'none' }}
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
                        {conv.non_letto && <span style={{ color: '#e74c3c', marginLeft: '6px' }}>●</span>}
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
 */
function ThreadView({ messages, conv, loading, replyText, setReplyText, sending, onSend, onBack }) {
    /** Ref per lo scroll automatico all'ultimo messaggio */
    const bottomRef = useRef(null)

    // Scrolla in fondo ogni volta che arrivano nuovi messaggi
    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages])

    if (loading) return <div style={{ padding: '20px', color: '#aaa' }}>Caricamento messaggi...</div>

    return (
        <div className="thread-container" style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>

            {/* Header del thread con nome contatto e pulsante torna indietro */}
            <div className="thread-header" style={{ borderBottom: '1px solid #333', padding: '10px' }}>
                <button onClick={onBack} style={{ marginRight: '10px', cursor: 'pointer' }}>←</button>
                <strong>{conv.display_name}</strong>
                <span style={{ marginLeft: '8px', fontSize: '0.85em', color: '#aaa' }}>
                    {conv.ongame ? '[ON]' : '[OFF]'}
                    {conv.tipo === 'gruppo' ? ' · Gruppo' : ''}
                    {conv.tipo === 'globale' ? ' · Globale' : ''}
                </span>
            </div>

            {/*
              * Lista messaggi: altezza massima fissa con scroll interno.
              * Senza max-height il div cresce con il contenuto e scrolla tutta la pagina.
              * calc(100vh - 180px) lascia spazio per header e form risposta.
              */}
            <div className="thread-messages" style={{ overflowY: 'auto', maxHeight: 'calc(100vh - 180px)', padding: '10px' }}>
                {messages.length === 0 && (
                    <p style={{ color: '#aaa', fontStyle: 'italic' }}>Nessun messaggio.</p>
                )}
                {messages.map((msg, i) => (
                    <div
                        key={i}
                        className="thread-message"
                        style={{
                            display: 'flex',
                            gap: '10px',
                            marginBottom: '12px',
                            alignItems: 'flex-start',
                        }}
                    >
                        {/* Avatar mittente */}
                        {msg.avatar && (
                            <img
                                src={msg.avatar}
                                alt={msg.mittente}
                                style={{ width: '40px', height: '40px', borderRadius: '4px', flexShrink: 0 }}
                            />
                        )}

                        {/* Corpo del messaggio */}
                        <div style={{ flex: 1 }}>
                            <div style={{ fontSize: '0.8em', color: '#aaa', marginBottom: '2px' }}>
                                <strong>{msg.mittente}</strong>
                                {' · '}
                                {formatDate(msg.ora, msg.ongame)}
                            </div>
                            {/* Il testo può contenere HTML dal sistema (formattazione) */}
                            <div
                                className="thread-message-text"
                                dangerouslySetInnerHTML={{ __html: msg.testo }}
                            />
                        </div>
                    </div>
                ))}
                {/* Anchor per lo scroll automatico */}
                <div ref={bottomRef} />
            </div>

            {/* Form di risposta (nascosto per messaggi globali) */}
            {conv.tipo !== 'globale' && (
                <div className="thread-reply" style={{ borderTop: '1px solid #333', padding: '10px', display: 'flex', gap: '8px' }}>
                    <textarea
                        value={replyText}
                        onChange={e => setReplyText(e.target.value)}
                        placeholder="Scrivi una risposta..."
                        rows="3"
                        style={{ flex: 1, resize: 'vertical' }}
                        // Invio con Ctrl+Enter per comodità
                        onKeyDown={e => { if (e.ctrlKey && e.key === 'Enter') onSend() }}
                    />
                    <button
                        onClick={onSend}
                        disabled={sending || !replyText.trim()}
                        style={{ alignSelf: 'flex-end', cursor: 'pointer' }}
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
 *
 * @param {Function} props.onSend   - Callback con { destinatario, testo, ongame }
 * @param {Function} props.onCancel - Callback per annullare
 * @param {boolean}  props.sending  - true durante l'invio
 */
function ComposeView({ onSend, onCancel, sending }) {
    const [dest,   setDest]   = useState('')
    const [testo,  setTesto]  = useState('')
    const [ongame, setOngame] = useState(0)

    const handleSend = () => {
        if (!dest.trim() || !testo.trim()) return
        onSend({ destinatario: dest.trim(), testo, ongame })
    }

    return (
        <div style={{ padding: '20px' }}>
            <h3>Nuovo Messaggio</h3>

            <div style={{ marginBottom: '10px' }}>
                <label style={{ display: 'block', marginBottom: '4px' }}>Destinatario</label>
                <input
                    type="text"
                    value={dest}
                    onChange={e => setDest(e.target.value)}
                    placeholder="Nome personaggio"
                    style={{ width: '100%' }}
                />
            </div>

            <div style={{ marginBottom: '10px' }}>
                <label style={{ display: 'block', marginBottom: '4px' }}>Tipo</label>
                <select value={ongame} onChange={e => setOngame(parseInt(e.target.value))}>
                    <option value={0}>OFF (fuori gioco)</option>
                    <option value={1}>ON (in gioco)</option>
                </select>
            </div>

            <div style={{ marginBottom: '10px' }}>
                <label style={{ display: 'block', marginBottom: '4px' }}>Messaggio</label>
                <textarea
                    value={testo}
                    onChange={e => setTesto(e.target.value)}
                    rows="5"
                    style={{ width: '100%', resize: 'vertical' }}
                />
            </div>

            <div style={{ display: 'flex', gap: '8px' }}>
                <button onClick={handleSend} disabled={sending || !dest.trim() || !testo.trim()}>
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

export default function MessagesInbox() {

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

    // Ref alla conversazione aperta: usato dal listener socket per aggiornare
    // il thread senza passarlo come dipendenza all'useEffect (evita ri-registrazioni)
    const selectedConvRef = useRef(null)
    useEffect(() => { selectedConvRef.current = selectedConv }, [selectedConv])

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
                if (data.success) setConversations(data.conversations)
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

        // op=read: segna come letto + restituisce messaggi + emette dm:update al self
        const qs = conv.tipo === 'individuale'
            ? `conversazione_id=${conv.conversazione_id}`
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
            ? `conversazione_id=${conv.conversazione_id}&silent=1`
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
            sock.on('dm:update', () => {
                // Aggiorna sempre la lista (icone non letti, ordine conversazioni)
                fetchList()
                // Se c'è un thread aperto: aggiorna silenziosamente i messaggi
                // (silent=1 → no mark-as-read → no dm:update emesso → nessun loop)
                fetchThreadSilent()
            })
        }

        return () => { if (sock) sock.off('dm:update') }
    }, [fetchList, fetchThreadSilent])

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
            <div className="container">
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
                                <p style={{ padding: '10px', color: '#aaa' }}>Caricamento...</p>
                            ) : convOff.length === 0 ? (
                                <p style={{ padding: '10px', color: '#aaa', fontStyle: 'italic' }}>Nessun messaggio OFF.</p>
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
                                <p style={{ padding: '10px', color: '#aaa' }}>Caricamento...</p>
                            ) : convOn.length === 0 ? (
                                <p style={{ padding: '10px', color: '#aaa', fontStyle: 'italic' }}>Nessun messaggio ON.</p>
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
            <div className="container">
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
                    />
                </div>
            </div>
        )
    }

    // --- VISTA COMPOSIZIONE ---
    return (
        <div className="container">
            <div className="main-content" style={{ width: '100%' }}>
                <ComposeView
                    onSend={sendNew}
                    onCancel={() => setView('list')}
                    sending={sending}
                />
            </div>
        </div>
    )
}
