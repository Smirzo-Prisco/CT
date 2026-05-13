/**
 * Forum.jsx
 *
 * Componente React per il forum di gioco (Araldo) — rimpiazza pages/forum.inc.php
 * e le sue sotto-pagine in pages/forum/*.inc.php.
 *
 * Navigazione interna a 4 viste:
 *   'sections' → lista sezioni raggruppate per tipo (ONGAME, INFO, ecc.)
 *   'threads'  → lista thread di una sezione, con paginazione e badge non letti
 *   'read'     → thread completo con tutte le risposte e form di risposta inline
 *   'compose'  → form per creare un nuovo thread in una sezione
 *
 * API utilizzate (pages/api_forum.php):
 *   GET  ?op=sections             → sezioni accessibili all'utente
 *   GET  ?op=threads&araldo=X     → thread di una sezione (paginati)
 *   GET  ?op=read&thread=X        → thread + risposte (segna come letto)
 *   POST ?op=post                 → nuovo thread o risposta
 *   POST ?op=markread             → segna thread come letto
 *
 * Montaggio: via ct:ready su #forum-container in forum.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

/**
 * Formatta una data ISO in formato leggibile 'gg-mm-aaaa hh:mm'.
 * @param {string} iso - Stringa data dal DB
 * @returns {string} Data formattata
 */
function formatDate(iso) {
    if (!iso) return ''
    const d = new Date(iso)
    return d.toLocaleDateString('it-IT') + ' ' + d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: singola riga thread nella lista
// ---------------------------------------------------------------------------

/**
 * Riga di un thread nell'elenco di una sezione.
 * Mostra titolo, autore, data ultima risposta, numero risposte e badge "non letto".
 *
 * @param {Object}   props.thread   - Dati del thread
 * @param {Function} props.onClick  - Callback al click per aprire il thread
 */
function ThreadRow({ thread, onClick }) {
    return (
        <tr
            className={thread.letto ? 'presente' : 'presente'}
            style={{ cursor: 'pointer' }}
            onClick={() => onClick(thread)}
        >
            {/* Titolo e indicatore non letto */}
            <td>
                <div className="forum_post_title">
                    {!thread.letto && (
                        <span style={{ color: '#e74c3c', marginRight: '6px', fontSize: '10px' }}>●</span>
                    )}
                    {thread.titolo}
                    {thread.chiuso && (
                        <span style={{ marginLeft: '6px', fontSize: '10px', color: '#aaa' }}>[chiuso]</span>
                    )}
                </div>
                <div className="forum_date_small">{thread.autore}</div>
            </td>

            {/* Numero risposte */}
            <td style={{ textAlign: 'center', whiteSpace: 'nowrap' }}>
                <div className="forum_date_big_right">{thread.n_risposte}</div>
            </td>

            {/* Data ultima risposta */}
            <td style={{ whiteSpace: 'nowrap' }}>
                <div className="forum_date_big">{formatDate(thread.data_ultimo_messaggio)}</div>
            </td>
        </tr>
    )
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: singolo messaggio/post nel thread
// ---------------------------------------------------------------------------

/**
 * Post di un thread (sia il messaggio originale che le risposte).
 * Mostra avatar, autore, data e testo del messaggio.
 *
 * @param {Object} props.msg    - Dati del messaggio
 * @param {boolean} props.isFirst - true se è il messaggio padre del thread
 */
function PostCard({ msg, isFirst }) {
    return (
        <table
            className={`customTable`}
            style={{ marginBottom: '10px', width: '100%' }}
        >
            <tbody>
                <tr>
                    {/* Colonna avatar + autore */}
                    <td className="forum_main_post_author" style={{ verticalAlign: 'top', padding: '10px', minWidth: '80px' }}>
                        {msg.avatar && (
                            <img
                                src={msg.avatar}
                                className="img_forum_avatar"
                                alt={msg.autore}
                                style={{ display: 'block', marginBottom: '6px' }}
                            />
                        )}
                        <div className="forum_post_author" style={{ fontSize: '12px', color: '#ce846f', textAlign: 'center' }}>
                            {msg.autore}
                        </div>
                        <div className="forum_date_big" style={{ paddingLeft: 0, textAlign: 'center' }}>
                            {formatDate(msg.data)}
                        </div>
                    </td>

                    {/* Colonna messaggio */}
                    <td className="forum_main_post_message" style={{ verticalAlign: 'top', padding: '10px' }}>
                        {/* Titolo solo per il post padre */}
                        {isFirst && msg.titolo && (
                            <div className="header_thread" style={{ marginBottom: '10px' }}>
                                {msg.titolo}
                            </div>
                        )}

                        {/* Corpo del messaggio — può contenere HTML (BBCode convertito lato server) */}
                        <div
                            className="forum_post_message"
                            dangerouslySetInnerHTML={{ __html: msg.messaggio }}
                        />

                        {/* Nota modifica se presente */}
                        {msg.edit_note && (
                            <div className="forum_post_modify" style={{ marginTop: '10px', fontSize: '11px', color: '#aaa' }}>
                                {msg.edit_note}
                            </div>
                        )}
                    </td>
                </tr>
            </tbody>
        </table>
    )
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: form risposta / nuovo thread
// ---------------------------------------------------------------------------

/**
 * Form per inviare una risposta a un thread o creare un nuovo thread.
 *
 * @param {boolean}  props.isNew      - true = nuovo thread, false = risposta
 * @param {boolean}  props.chiuso     - true = thread chiuso (form disabilitato)
 * @param {boolean}  props.sending    - true durante l'invio
 * @param {Function} props.onSubmit   - Callback ({ titolo, messaggio, anonimo })
 * @param {Function} props.onCancel   - Callback per annullare (solo per nuovi thread)
 */
function PostForm({ isNew, chiuso, sending, onSubmit, onCancel }) {
    const [titolo,   setTitolo]   = useState('')
    const [testo,    setTesto]    = useState('')
    const [anonimo,  setAnonimo]  = useState(false)
    const textareaRef = useRef(null)

    useEffect(() => {
        // Porta il focus sulla textarea alla prima apertura del form
        if (textareaRef.current) textareaRef.current.focus()
    }, [])

    const handleSubmit = () => {
        if (!testo.trim()) return
        if (isNew && !titolo.trim()) return
        onSubmit({ titolo, messaggio: testo, anonimo: anonimo ? 1 : 0 })
        setTitolo('')
        setTesto('')
    }

    if (chiuso) {
        return (
            <p style={{ color: '#aaa', fontStyle: 'italic', textAlign: 'center', margin: '20px 0' }}>
                Thread chiuso — non è possibile rispondere.
            </p>
        )
    }

    return (
        <div style={{ marginTop: '20px', padding: '10px', background: '#111423', borderRadius: '6px' }}>
            <h4 style={{ color: '#ce846f', marginBottom: '10px' }}>
                {isNew ? 'Nuova discussione' : 'Rispondi'}
            </h4>

            {/* Titolo — solo per nuovi thread */}
            {isNew && (
                <div style={{ marginBottom: '8px' }}>
                    <input
                        type="text"
                        value={titolo}
                        onChange={e => setTitolo(e.target.value)}
                        placeholder="Titolo della discussione"
                        style={{ width: '100%', boxSizing: 'border-box' }}
                    />
                </div>
            )}

            {/* Corpo del messaggio */}
            <textarea
                ref={textareaRef}
                value={testo}
                onChange={e => setTesto(e.target.value)}
                placeholder="Scrivi il tuo messaggio..."
                rows="6"
                style={{ width: '100%', boxSizing: 'border-box', resize: 'vertical' }}
            />

            {/* Opzioni e pulsanti */}
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginTop: '8px', flexWrap: 'wrap' }}>
                <label style={{ fontSize: '12px', color: '#aaa', cursor: 'pointer' }}>
                    <input
                        type="checkbox"
                        checked={anonimo}
                        onChange={e => setAnonimo(e.target.checked)}
                        style={{ marginRight: '4px' }}
                    />
                    Anonimo
                </label>

                <button
                    onClick={handleSubmit}
                    disabled={sending || !testo.trim() || (isNew && !titolo.trim())}
                    style={{ cursor: 'pointer' }}
                >
                    {sending ? 'Invio...' : 'Invia'}
                </button>

                {onCancel && (
                    <button onClick={onCancel} style={{ cursor: 'pointer' }}>
                        Annulla
                    </button>
                )}
            </div>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function Forum() {

    // --- vista attiva ---
    /**
     * Vista corrente del forum:
     *   'sections' → lista sezioni (vista iniziale)
     *   'threads'  → thread di una sezione
     *   'read'     → lettura di un thread
     *   'compose'  → composizione nuovo thread
     */
    const [view, setView] = useState('sections')

    // --- dati ---
    /** Sezioni del forum raggruppate per tipo: { tipoLabel: [sezione, ...] } */
    const [sections,        setSections]        = useState({})
    /** Threads della sezione corrente */
    const [threads,         setThreads]         = useState([])
    /** Totale thread per la paginazione */
    const [totalThreads,    setTotalThreads]    = useState(0)
    /** Messaggi (padre + risposte) del thread corrente */
    const [messages,        setMessages]        = useState([])
    /** Sezione selezionata { id, nome } */
    const [currentSection,  setCurrentSection]  = useState(null)
    /** Thread selezionato { id, titolo, chiuso } */
    const [currentThread,   setCurrentThread]   = useState(null)
    /** Pagina corrente nella lista thread (1-based) */
    const [page,            setPage]            = useState(1)

    // --- stati UI ---
    const [loadingSections, setLoadingSections] = useState(true)
    const [loadingThreads,  setLoadingThreads]  = useState(false)
    const [loadingRead,     setLoadingRead]     = useState(false)
    const [sending,         setSending]         = useState(false)

    // ---------------------------------------------------------------------------
    // FETCH
    // ---------------------------------------------------------------------------

    /** Carica la lista delle sezioni accessibili all'utente */
    const fetchSections = useCallback(() => {
        setLoadingSections(true)
        fetch('/pages/api_forum.php?op=sections')
            .then(r => r.json())
            .then(data => {
                if (data.success) setSections(data.sections)
                setLoadingSections(false)
            })
            .catch(err => { console.error('[Forum] Errore sezioni:', err); setLoadingSections(false) })
    }, [])

    /**
     * Carica i thread di una sezione.
     * @param {number} araldoId - ID della sezione
     * @param {number} pg       - Pagina (1-based)
     */
    const fetchThreads = useCallback((araldoId, pg = 1) => {
        setLoadingThreads(true)
        fetch(`/pages/api_forum.php?op=threads&araldo=${araldoId}&offset=${pg}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setThreads(data.threads)
                    setTotalThreads(data.totale)
                    setPage(data.pagina)
                }
                setLoadingThreads(false)
            })
            .catch(err => { console.error('[Forum] Errore thread:', err); setLoadingThreads(false) })
    }, [])

    /**
     * Carica il thread completo con tutte le risposte.
     * Segna automaticamente il thread come letto (logica in api_forum.php).
     *
     * @param {number} threadId - ID del thread padre
     */
    const fetchThread = useCallback((threadId) => {
        setLoadingRead(true)
        fetch(`/pages/api_forum.php?op=read&thread=${threadId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) setMessages(data.messages)
                setLoadingRead(false)
            })
            .catch(err => { console.error('[Forum] Errore lettura:', err); setLoadingRead(false) })
    }, [])

    // Caricamento iniziale delle sezioni al mount del componente
    useEffect(() => { fetchSections() }, [fetchSections])

    // ---------------------------------------------------------------------------
    // NAVIGAZIONE
    // ---------------------------------------------------------------------------

    /** Apre una sezione mostrando la lista dei thread */
    const openSection = (section) => {
        setCurrentSection(section)
        setView('threads')
        fetchThreads(section.id, 1)
    }

    /** Apre un thread per la lettura */
    const openThread = (thread) => {
        setCurrentThread(thread)
        setView('read')
        fetchThread(thread.id)
    }

    /** Torna alla lista sezioni */
    const backToSections = () => {
        setView('sections')
        setCurrentSection(null)
        setThreads([])
        // Ricarica le sezioni per aggiornare i badge non letti
        fetchSections()
    }

    /** Torna alla lista thread della sezione corrente */
    const backToThreads = () => {
        setView('threads')
        setCurrentThread(null)
        setMessages([])
        // Ricarica i thread per aggiornare lo stato letto
        if (currentSection) fetchThreads(currentSection.id, page)
    }

    // ---------------------------------------------------------------------------
    // INVIO POST
    // ---------------------------------------------------------------------------

    /**
     * Invia una risposta al thread aperto.
     * @param {Object} params - { titolo, messaggio, anonimo }
     */
    const sendReply = ({ messaggio, anonimo }) => {
        if (sending) return
        setSending(true)
        fetch('/pages/api_forum.php?op=post', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                araldo:    currentSection?.id,
                padre:     currentThread?.id,
                messaggio,
                anonimo,
            }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) fetchThread(currentThread.id) // ricarica il thread
                else alert(data.message || 'Errore nell\'invio')
            })
            .catch(console.error)
            .finally(() => setSending(false))
    }

    /**
     * Invia un nuovo thread nella sezione corrente.
     * @param {Object} params - { titolo, messaggio, anonimo }
     */
    const sendNewThread = ({ titolo, messaggio, anonimo }) => {
        if (sending) return
        setSending(true)
        fetch('/pages/api_forum.php?op=post', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                araldo:  currentSection?.id,
                padre:   -1,
                titolo,
                messaggio,
                anonimo,
            }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Torna ai thread e apre direttamente il nuovo thread
                    setView('threads')
                    fetchThreads(currentSection.id, 1)
                } else alert(data.message || 'Errore nell\'invio')
            })
            .catch(console.error)
            .finally(() => setSending(false))
    }

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    // --- VISTA SEZIONI ---
    if (view === 'sections') {
        return (
            <div className="pagina_forum">
                {loadingSections ? (
                    <p style={{ padding: '20px', color: '#aaa' }}>Caricamento sezioni...</p>
                ) : (
                    /*
                     * Struttura identica al vecchio forum PHP:
                     * - Un'unica tabella per tutti i gruppi
                     * - Riga header di gruppo a larghezza intera (third_header)
                     * - Riga per ogni sezione: [icona letto] [nome] [descrizione]
                     * - Nessuna ripetizione di "Sezione / Descrizione / Non letti"
                     */
                    <table className="customTable" style={{ width: '100%' }}>
                        <tbody>
                            {Object.entries(sections).map(([tipo, secs]) => (
                                <>
                                    {/* Riga header del gruppo */}
                                    <tr key={`h-${tipo}`} className="third_header">
                                        <td colSpan="3" style={{ textAlign: 'center', padding: '6px 10px', textTransform: 'uppercase', fontWeight: 'bold' }}>
                                            {tipo}
                                        </td>
                                    </tr>

                                    {/* Righe delle sezioni */}
                                    {secs.map(sec => (
                                        <tr
                                            key={sec.id}
                                            className="presente"
                                            style={{ cursor: 'pointer' }}
                                            onClick={() => openSection(sec)}
                                        >
                                            {/* Icona luna per thread non letti, come nel vecchio forum */}
                                            <td style={{ width: '24px', padding: '6px 6px 6px 10px', textAlign: 'center' }}>
                                                {sec.non_letti > 0 && (
                                                    <span title={`${sec.non_letti} thread non letti`} style={{ fontSize: '14px' }}>🌙</span>
                                                )}
                                            </td>

                                            {/* Nome sezione */}
                                            <td style={{ padding: '6px 10px', textTransform: 'uppercase' }}>
                                                <strong style={{ color: '#ce846f', letterSpacing: '1px' }}>{sec.nome}</strong>
                                            </td>

                                            {/* Descrizione */}
                                            <td style={{ padding: '6px 10px', fontSize: '11px', color: '#a7a7a8', textTransform: 'uppercase', letterSpacing: '1px' }}>
                                                {sec.descrizione}
                                            </td>
                                        </tr>
                                    ))}
                                </>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        )
    }

    // --- VISTA THREAD LIST ---
    if (view === 'threads') {
        const totalPages = Math.ceil(totalThreads / 20)
        return (
            <div className="pagina_forum">
                {/* Navigazione + titolo sezione */}
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px', flexWrap: 'wrap', gap: '8px' }}>
                    <div>
                        <button onClick={backToSections} style={{ cursor: 'pointer', marginRight: '10px' }}>← Sezioni</button>
                        <strong style={{ color: '#ce846f', fontSize: '14px' }}>{currentSection?.nome}</strong>
                    </div>
                    <button onClick={() => setView('compose')} style={{ cursor: 'pointer' }}>
                        + Nuova discussione
                    </button>
                </div>

                {loadingThreads ? (
                    <p style={{ color: '#aaa' }}>Caricamento thread...</p>
                ) : (
                    <>
                        <table className="customTable" style={{ width: '100%' }}>
                            <thead>
                                <tr className="second_header">
                                    <td>Discussione</td>
                                    <td style={{ textAlign: 'center', width: '60px' }}>Risposte</td>
                                    <td style={{ width: '130px' }}>Ultimo post</td>
                                </tr>
                            </thead>
                            <tbody>
                                {threads.length === 0 ? (
                                    <tr><td colSpan="3" style={{ padding: '20px', color: '#aaa', textAlign: 'center' }}>Nessuna discussione.</td></tr>
                                ) : (
                                    threads.map(t => (
                                        <ThreadRow key={t.id} thread={t} onClick={openThread} />
                                    ))
                                )}
                            </tbody>
                        </table>

                        {/* Paginazione */}
                        {totalPages > 1 && (
                            <div className="pagination" style={{ display: 'flex', gap: '4px', flexWrap: 'wrap', marginTop: '10px' }}>
                                {Array.from({ length: totalPages }, (_, i) => i + 1).map(p => (
                                    <a
                                        key={p}
                                        href="#"
                                        className={p === page ? 'active' : ''}
                                        onClick={e => { e.preventDefault(); fetchThreads(currentSection.id, p) }}
                                    >
                                        {p}
                                    </a>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        )
    }

    // --- VISTA COMPOSIZIONE NUOVO THREAD ---
    if (view === 'compose') {
        return (
            <div className="pagina_forum">
                <div style={{ marginBottom: '10px' }}>
                    <button onClick={() => setView('threads')} style={{ cursor: 'pointer' }}>
                        ← {currentSection?.nome}
                    </button>
                </div>
                <PostForm
                    isNew={true}
                    chiuso={false}
                    sending={sending}
                    onSubmit={sendNewThread}
                    onCancel={() => setView('threads')}
                />
            </div>
        )
    }

    // --- VISTA LETTURA THREAD ---
    if (view === 'read') {
        const threadClosed = messages[0]?.chiuso ?? false
        return (
            <div className="pagina_forum">
                {/* Navigazione */}
                <div style={{ marginBottom: '10px' }}>
                    <button onClick={backToThreads} style={{ cursor: 'pointer' }}>
                        ← {currentSection?.nome}
                    </button>
                </div>

                {loadingRead ? (
                    <p style={{ color: '#aaa' }}>Caricamento messaggi...</p>
                ) : (
                    <>
                        {/* Tutti i messaggi del thread */}
                        {messages.map((msg, i) => (
                            <PostCard key={msg.id} msg={msg} isFirst={i === 0} />
                        ))}

                        {/* Form risposta */}
                        <PostForm
                            isNew={false}
                            chiuso={threadClosed}
                            sending={sending}
                            onSubmit={sendReply}
                        />
                    </>
                )}
            </div>
        )
    }

    return null
}
