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
import styles from './Forum.module.css'

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
    // Safari/iOS non accetta "YYYY-MM-DD HH:MM:SS" — serve la T come separatore ISO 8601
    const d = new Date(iso.replace(' ', 'T'))
    return d.toLocaleDateString('it-IT') + ' ' + d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: singola riga thread nella lista
// ---------------------------------------------------------------------------

/**
 * Riga di un thread nell'elenco di una sezione.
 * Struttura identica al vecchio visit.inc.php:
 *   STATO | TOPIC (titolo+data) | AUTORE | RISPOSTE (n + ultima) | [AZIONI staff]
 *
 * @param {Object}   props.thread    - Dati del thread
 * @param {Function} props.onClick   - Callback al click per aprire il thread
 * @param {boolean}  props.isStaff   - true = mostra pulsanti azioni staff
 * @param {Function} props.onAction  - Callback ({ action, threadId }) per azioni staff
 */
function ThreadRow({ thread, onClick, isStaff, onAction }) {

    /** Blocca la propagazione per non aprire il thread al click sui pulsanti */
    const action = (e, type) => {
        e.stopPropagation()
        onAction && onAction({ action: type, thread })
    }

    return (
        <tr className={styles.clickable} onClick={() => onClick(thread)}>

            {/* STATO: verde = aperto, rosso = chiuso */}
            <td className={styles.statusCell}>
                <img
                    src={thread.chiuso
                        ? '/themes/crystal/imgs/forum/topic_chiuso.png'
                        : '/themes/crystal/imgs/forum/topic_aperto.png'}
                    alt={thread.chiuso ? 'Chiuso' : 'Aperto'}
                    className={styles.statusIcon}
                    title={thread.chiuso ? 'Thread chiuso' : 'Thread aperto'}
                />
            </td>

            {/* TOPIC: titolo + data creazione */}
            <td className={styles.topicCell}>
                <div className={`forum_post_title ${!thread.letto ? styles.unreadTitle : ''}`}>
                    {!thread.letto && <span className={styles.unreadDot} title="Non letto" />}
                    {thread.titolo}
                </div>
                <div className="forum_date_small">{formatDate(thread.data)}</div>
            </td>

            {/* AUTORE */}
            <td className={styles.authorCell}>
                <div className="forum_date_big_right">{thread.autore}</div>
            </td>

            {/* RISPOSTE: numero + data ultima risposta */}
            <td className={styles.repliesCell}>
                <div className="forum_date_big_right">{thread.n_risposte} Risposte</div>
                {thread.n_risposte > 0 && (
                    <div className={`forum_date_big ${styles.dateNoIndent}`}>
                        Ultima: {formatDate(thread.data_ultimo_messaggio)}
                    </div>
                )}
            </td>

            {/* AZIONI staff: importante, chiudi/apri, elimina */}
            {isStaff && (
                <td className={styles.actionsCell}>
                    <img
                        src={thread.importante
                            ? '/themes/crystal/imgs/forum/freccia_giu.png'
                            : '/themes/crystal/imgs/forum/freccia_su.png'}
                        alt="Importante"
                        title={thread.importante ? 'Rimuovi da importanti' : 'Segna come importante'}
                        className={styles.actionIcon}
                        onClick={e => action(e, 'toggle_important')}
                    />
                    <img
                        src={thread.chiuso
                            ? '/themes/crystal/imgs/forum/lucchetto_aperto.png'
                            : '/themes/crystal/imgs/forum/lucchetto_chiuso.png'}
                        alt={thread.chiuso ? 'Apri' : 'Chiudi'}
                        title={thread.chiuso ? 'Apri post' : 'Chiudi post'}
                        className={styles.actionIcon}
                        onClick={e => action(e, 'toggle_close')}
                    />
                    <img
                        src="/themes/crystal/imgs/forum/cancella_topic.png"
                        alt="Elimina"
                        title="Elimina thread"
                        className={styles.actionIcon}
                        onClick={e => {
                            e.stopPropagation()
                            if (confirm('Eliminare questo thread e tutte le risposte?')) {
                                onAction && onAction({ action: 'delete_thread', thread })
                            }
                        }}
                    />
                </td>
            )}
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
        <table className={`customTable ${styles.postCard}`}>
            <tbody>
                <tr>
                    {/* Colonna avatar + autore */}
                    <td className={`forum_main_post_author ${styles.authorColumn}`}>
                        {msg.avatar && (
                            <img
                                src={msg.avatar}
                                className={`img_forum_avatar ${styles.avatarImg}`}
                                alt={msg.autore}
                            />
                        )}
                        <div className={`forum_post_author ${styles.authorName}`}>{msg.autore}</div>
                        <div className={`forum_date_big ${styles.authorDate}`}>{formatDate(msg.data)}</div>
                    </td>

                    {/* Colonna messaggio */}
                    <td className={`forum_main_post_message ${styles.messageColumn}`}>
                        {isFirst && msg.titolo && (
                            <div className={`header_thread ${styles.threadTitle}`}>{msg.titolo}</div>
                        )}
                        <div className="forum_post_message" dangerouslySetInnerHTML={{ __html: msg.messaggio }} />
                        {msg.edit_note && (
                            <div className={`forum_post_modify ${styles.editNote}`}>{msg.edit_note}</div>
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
 * Toolbar BBCode per la composizione dei messaggi.
 * Ogni pulsante avvolge il testo selezionato nel tag BBCode corrispondente.
 * Se non c'è selezione, inserisce i tag con il cursore in mezzo.
 *
 * @param {React.RefObject} textareaRef - Ref alla textarea di composizione
 * @param {Function} onChange           - Setter del valore testuale
 */
function BBCodeToolbar({ textareaRef, onChange }) {
    /**
     * Inserisce un tag BBCode intorno al testo selezionato (o al cursore).
     * @param {string} openTag  - Tag di apertura, es. '[b]'
     * @param {string} closeTag - Tag di chiusura, es. '[/b]'
     */
    const insert = (openTag, closeTag) => {
        const ta = textareaRef.current
        if (!ta) return
        const start = ta.selectionStart
        const end   = ta.selectionEnd
        const val   = ta.value
        const selected = val.substring(start, end)
        const newVal = val.substring(0, start) + openTag + selected + closeTag + val.substring(end)
        onChange(newVal)
        // Riposiziona il cursore dopo i tag inseriti
        setTimeout(() => {
            ta.focus()
            const curPos = start + openTag.length + selected.length + closeTag.length
            ta.setSelectionRange(curPos, curPos)
        }, 0)
    }

    /** Stile base per i pulsanti BBCode */
    const btn = (label, openTag, closeTag, color) => (
        <span
            key={label}
            onClick={() => insert(openTag, closeTag)}
            className={styles.toolbarBtn}
            style={color ? { color } : undefined}
            title={`${openTag}...${closeTag}`}
        >
            {label}
        </span>
    )

    return (
        <div className={styles.toolbar}>
            <span className={styles.toolbarLabel}>formattazione testo:</span>
            {btn('B',       '[b]',          '[/b]',          '#fff')}
            {btn('I',       '[i]',          '[/i]',          '#ccc')}
            <span className={styles.toolbarBtnU} onClick={() => insert('[u]', '[/u]')}>U</span>
            {btn('CENTRO',  '[center]',     '[/center]')}
            {btn('IMG',     '[img]',        '[/img]')}
            {btn('URL',     '[url]',        '[/url]')}
            <br />
            {btn('SPOILER', '[spoiler]',    '[/spoiler]')}
            <br style={{ marginBottom: '4px' }} />
            {btn('AZZURRO', '[color=blue]', '[/color]', '#5599ff')}
            {btn('ROSSO',   '[color=red]',  '[/color]', '#ff5555')}
            {btn('VERDE',   '[color=green]','[/color]', '#55cc55')}
            {btn('GIALLO',  '[color=yellow]','[/color]','#ffcc00')}
        </div>
    )
}

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
    const [titolo,  setTitolo]  = useState('')
    const [testo,   setTesto]   = useState('')
    const [anonimo, setAnonimo] = useState(false)
    const textareaRef = useRef(null)

    useEffect(() => {
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
        return <p className={styles.closedNote}>Thread chiuso — non è possibile rispondere.</p>
    }

    return (
        <div className={styles.formContainer}>
            <h4 className={styles.formTitle}>{isNew ? 'Nuova discussione' : 'Rispondi'}</h4>

            {isNew && (
                <div className={styles.titleField}>
                    <input
                        type="text"
                        value={titolo}
                        onChange={e => setTitolo(e.target.value)}
                        placeholder="Titolo della discussione"
                        className={styles.fullWidth}
                    />
                </div>
            )}

            <BBCodeToolbar textareaRef={textareaRef} onChange={setTesto} />

            <textarea
                ref={textareaRef}
                value={testo}
                onChange={e => setTesto(e.target.value)}
                placeholder="Scrivi il tuo messaggio..."
                rows="6"
                className={styles.fullWidthResize}
            />

            <div className={styles.formOptions}>
                <label className={styles.anonLabel}>
                    <input
                        type="checkbox"
                        checked={anonimo}
                        onChange={e => setAnonimo(e.target.checked)}
                        className={styles.anonCheckbox}
                    />
                    Anonimo
                </label>
                <button onClick={handleSubmit} disabled={sending || !testo.trim() || (isNew && !titolo.trim())}>
                    {sending ? 'Invio...' : 'Invia'}
                </button>
                {onCancel && <button onClick={onCancel}>Annulla</button>}
            </div>
        </div>
    )
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: tabella punti assegnati (visibile solo a master/admin)
// ---------------------------------------------------------------------------

/**
 * Tabella che mostra i punti (exp, shin, notorietà) assegnati ai partecipanti
 * di un thread quest. Corrisponde alla "sezione valutazioni" del vecchio read.inc.php.
 *
 * @param {Array} props.puntiList - Array di { nome, exp, shin, notorieta, commento }
 */
function PuntiTable({ puntiList }) {
    if (!puntiList || puntiList.length === 0) return null
    return (
        <table className={`customTable ${styles.puntiTable}`}>
            <thead>
                <tr className="second_header">
                    <td className={styles.theadColor}>Personaggio</td>
                    <td className={styles.theadColor}>Esperienza</td>
                    <td className={styles.theadColor}>Shin</td>
                    <td className={styles.theadColor}>Notorietà</td>
                    <td className={styles.theadColor}>Commento</td>
                </tr>
            </thead>
            <tbody>
                {puntiList.map((p, i) => (
                    <tr key={i}>
                        <td>
                            <a href={`main.php?page=scheda&pg=${encodeURIComponent(p.nome)}`}
                               className={styles.puntiName}>
                                {p.nome}
                            </a>
                        </td>
                        <td>{p.exp !== 0 ? p.exp : '—'}</td>
                        <td>{p.shin !== 0 ? p.shin : '—'}</td>
                        <td>{p.notorieta !== 0 ? p.notorieta : '—'}</td>
                        <td>{p.commento || ''}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    )
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: form inserimento quest
// ---------------------------------------------------------------------------

/**
 * Opzioni tipologia quest (identiche al vecchio composer_quest.inc.php).
 */
const TIPOLOGIE_QUEST = [
    'Assegnazione Esperienza o Notorietà',
    'Quest Singola', 'Quest di Gilda', 'Evento',
    'Prima parte', 'Seconda parte', 'Terza parte',
    'Quarta parte', 'Quinta parte', 'Sesta parte',
    'Settima parte', 'Ottava parte', 'Nona parte',
    'Finale',
]

/**
 * Form strutturato per la creazione di un resoconto quest.
 * Mostra i campi standard (titolo, tipologia, partecipanti, ecc.)
 * e, se la sezione ha punti > 0, 20 righe per assegnare XP ai pg.
 *
 * @param {Object}   props.section  - Sezione corrente (con campo punti)
 * @param {boolean}  props.sending  - true durante l'invio
 * @param {Function} props.onSubmit - Callback con i dati del form
 * @param {Function} props.onCancel - Callback per tornare indietro
 */
function ComposeQuest({ section, sending, onSubmit, onCancel }) {
    const [titolo,      setTitolo]      = useState('')
    const [tipologia,   setTipologia]   = useState(TIPOLOGIE_QUEST[0])
    const [partec,      setPartec]      = useState('')
    const [location,    setLocation]    = useState('')
    const [riassunto,   setRiassunto]   = useState('')
    const [conseguenze, setConseguenze] = useState('')
    const [note,        setNote]        = useState('')
    const [valutazioni, setValutazioni] = useState('')

    /** 20 righe vuote per i punti partecipanti */
    const [pgPunti, setPgPunti] = useState(() =>
        Array.from({ length: 20 }, () => ({ nome: '', exp: 0, shin: 0, notorieta: 0, mestiere: 0 }))
    )

    const showPunti = (section?.punti ?? 0) > 0

    /** -5 … +10 step 0.5 (come il PHP: j da -10 a 20, value = j/2) */
    const expOpts = Array.from({ length: 31 }, (_, i) => (i - 10) / 2)
    /** -10 … +10 step 1 */
    const notOpts = Array.from({ length: 21 }, (_, i) => i - 10)

    const updatePg = (i, field, value) =>
        setPgPunti(prev => { const n = [...prev]; n[i] = { ...n[i], [field]: value }; return n })

    const handleSubmit = () => {
        if (!titolo.trim()) return
        onSubmit({
            titolo, tipologia, partecipanti: partec,
            location, riassunto, conseguenze, note, valutazioni,
            partecipanti_punti: showPunti ? pgPunti.filter(p => p.nome.trim()) : [],
        })
    }

    /** Stile comune per input/select nei campi quest */
    const inputStyle = { width: '100%', boxSizing: 'border-box' }

    return (
        <div className="pagina_forum">
            <div className={styles.backBar}>
                <button onClick={onCancel}>← {section?.nome}</button>
            </div>

            <table className="customTable" style={{ width: '100%' }}>
                <tbody>
                    <tr><td colSpan="2" className={styles.questHeader}>INSERIMENTO QUEST</td></tr>

                    <tr className="second_header"><td colSpan="2"><b>TITOLO QUEST</b></td></tr>
                    <tr><td colSpan="2">
                        <input type="text" value={titolo} onChange={e => setTitolo(e.target.value)}
                            placeholder="Inserire titolo quest" className="ares" style={inputStyle} />
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>TIPOLOGIA QUEST</b></td></tr>
                    <tr><td colSpan="2">
                        <select value={tipologia} onChange={e => setTipologia(e.target.value)} className="ares">
                            {TIPOLOGIE_QUEST.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>PARTECIPANTI</b></td></tr>
                    <tr><td colSpan="2">
                        <input type="text" value={partec} onChange={e => setPartec(e.target.value)}
                            placeholder="Inserire partecipanti" className="ares" style={inputStyle} />
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>LOCATION</b></td></tr>
                    <tr><td colSpan="2">
                        <input type="text" value={location} onChange={e => setLocation(e.target.value)}
                            placeholder="Inserire location" className="ares" style={inputStyle} />
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>RIASSUNTO QUEST</b></td></tr>
                    <tr><td colSpan="2">
                        <textarea value={riassunto} onChange={e => setRiassunto(e.target.value)}
                            placeholder="Inserire riassunto quest (max 500 caratteri)"
                            className="ares" rows="4" style={inputStyle} />
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>CONSEGUENZE QUEST</b></td></tr>
                    <tr><td colSpan="2">
                        <textarea value={conseguenze} onChange={e => setConseguenze(e.target.value)}
                            placeholder="Inserire conseguenze quest (eventi, conseguenze pg, alterazioni ambientazioni ecc)"
                            className="ares" rows="4" style={inputStyle} />
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>NOTE</b></td></tr>
                    <tr><td colSpan="2">
                        <textarea value={note} onChange={e => setNote(e.target.value)}
                            placeholder="Inserire note quest (punti salute, potenziamento oggetti di gilda, skill acquisite, oggetti ecc)"
                            className="ares" rows="4" style={inputStyle} />
                    </td></tr>

                    <tr className="second_header"><td colSpan="2"><b>VALUTAZIONI</b></td></tr>
                    <tr><td colSpan="2">
                        <textarea value={valutazioni} onChange={e => setValutazioni(e.target.value)}
                            placeholder="Inserire valutazioni pg"
                            className="ares" rows="4" style={inputStyle} />
                    </td></tr>
                </tbody>
            </table>

            {/* Righe punti partecipanti — solo per sezioni con punti > 0 */}
            {showPunti && (
                <table className={`customTable ${styles.pgPuntiTable}`}>
                    <thead>
                        <tr className="second_header">
                            <td>Pg</td>
                            <td>Exp</td>
                            <td>Shin</td>
                            <td>Notorietà</td>
                            <td>P Mestiere</td>
                        </tr>
                    </thead>
                    <tbody>
                        {pgPunti.map((pg, i) => (
                            <tr key={i}>
                                <td>
                                    <input type="text" value={pg.nome}
                                        onChange={e => updatePg(i, 'nome', e.target.value)}
                                        className="ares" style={{ width: '150px' }} />
                                </td>
                                <td>
                                    <select value={pg.exp} onChange={e => updatePg(i, 'exp', parseFloat(e.target.value))} className="ares">
                                        {expOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                    </select>
                                </td>
                                <td>
                                    <select value={pg.shin} onChange={e => updatePg(i, 'shin', parseFloat(e.target.value))} className="ares">
                                        {expOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                    </select>
                                </td>
                                <td>
                                    <select value={pg.notorieta} onChange={e => updatePg(i, 'notorieta', parseInt(e.target.value))} className="ares">
                                        {notOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                    </select>
                                </td>
                                <td>
                                    <select value={pg.mestiere} onChange={e => updatePg(i, 'mestiere', parseFloat(e.target.value))} className="ares">
                                        {expOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                    </select>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}

            <div className={styles.buttonsBar} style={{ marginTop: '10px' }}>
                <button onClick={handleSubmit} disabled={sending || !titolo.trim()}>
                    {sending ? 'Invio...' : 'Invia Quest'}
                </button>
                <button onClick={onCancel}>Annulla</button>
            </div>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function Forum({ isStaff = false }) {

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
    /** Testo di ricerca nella lista thread */
    const [searchQuery,     setSearchQuery]     = useState('')

    /** Punti assegnati nel thread corrente (da Punti table, solo per master/admin) */
    const [puntiList,       setPuntiList]       = useState([])

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
                if (data.success) {
                    setMessages(data.messages)
                    setPuntiList(data.punti_list ?? [])
                }
                setLoadingRead(false)
            })
            .catch(err => { console.error('[Forum] Errore lettura:', err); setLoadingRead(false) })
    }, [])

    // Caricamento iniziale delle sezioni al mount del componente
    useEffect(() => { fetchSections() }, [fetchSections])

    // Aggiornamento real-time via socket: 'forum:update' arriva quando
    // qualcuno pubblica un post. Aggiorna la vista corrente di conseguenza.
    useEffect(() => {
        const sock = window.ctSocket
        if (!sock) return

        const handle = ({ araldo_id, thread_id }) => {
            if (view === 'sections') {
                fetchSections()
            } else if (view === 'threads' && currentSection?.id === araldo_id) {
                fetchThreads(currentSection.id, page)
            } else if (view === 'read' && currentThread?.id === thread_id) {
                fetchThread(currentThread.id)
            }
        }

        sock.on('forum:update', handle)
        return () => sock.off('forum:update', handle)
    }, [view, currentSection, currentThread, page, fetchSections, fetchThreads, fetchThread])

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

    /**
     * Gestisce le azioni staff sui thread (importante, chiudi, elimina).
     * Chiama l'endpoint corrispondente e ricarica la lista thread.
     *
     * @param {Object} params - { action: string, thread: Object }
     */
    const handleThreadAction = ({ action, thread }) => {
        const endpoint = `/pages/api_forum.php?op=${action}`
        fetch(endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ thread: thread.id, araldo: currentSection?.id }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) fetchThreads(currentSection.id, page)
                else alert(data.message || 'Errore')
            })
            .catch(console.error)
    }

    /**
     * Segna come letti tutti i thread di tutte le sezioni accessibili.
     * Chiama op=readall con araldo=0 (globale), poi ricarica le sezioni
     * per aggiornare i badge non-letti.
     */
    const markAllRead = () => {
        fetch('/pages/api_forum.php?op=readall', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ araldo: 0 }),
        })
            .then(r => r.json())
            .then(data => { if (data.success) fetchSections() })
            .catch(console.error)
    }

    /** Torna alla lista thread della sezione corrente */
    const backToThreads = () => {
        setView('threads')
        setCurrentThread(null)
        setMessages([])
        setPuntiList([])
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

    /**
     * Invia un resoconto quest nella sezione corrente.
     * @param {Object} formData - Tutti i campi del form ComposeQuest
     */
    const sendNewQuest = (formData) => {
        if (sending) return
        setSending(true)
        fetch('/pages/api_forum.php?op=post_quest', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ araldo: currentSection?.id, padre: -1, ...formData }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
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
                <div className={styles.buttonsBar}>
                    <button onClick={markAllRead}>Leggi tutto</button>
                </div>
                {loadingSections ? (
                    <p className={styles.loadingText}>Caricamento sezioni...</p>
                ) : (
                    /*
                     * Struttura identica al vecchio forum PHP:
                     * - Un'unica tabella per tutti i gruppi
                     * - Riga header di gruppo a larghezza intera (third_header)
                     * - Riga per ogni sezione: [icona letto] [nome] [descrizione]
                     * - Nessuna ripetizione di "Sezione / Descrizione / Non letti"
                     */
                    <table className={`customTable ${styles.sectionsTable}`}>
                        <tbody>
                            {Object.entries(sections).map(([tipo, secs]) => (
                                <>
                                    {/*
                                      * Header di gruppo: third_header dà lo sfondo corretto via CSS.
                                      * Il colore arancione va aggiunto inline perché third_header colora
                                      * solo i tag <a> per default, non il testo puro nei <td>.
                                      */}
                                    <tr key={`h-${tipo}`} className="third_header" style={{ backgroundImage: "url('/themes/crystal/imgs/presenti/barra_mappa_chat.png')" }}>
                                        {/*
                                          * backgroundImage inline forza il background-image anche quando
                                          * la specificità CSS di customTable potrebbe sovrastare presenti.css.
                                          * Il colore arancione va aggiunto inline perché third_header colora
                                          * solo i tag <a> nel CSS, non il testo puro dei <td>.
                                          */}
                                        <td colSpan="3" className={styles.groupHeaderCell}>{tipo}</td>
                                    </tr>

                                    {/* Righe delle sezioni: [🌙 nome] [descrizione] [badge] */}
                                    {secs.map(sec => (
                                        <tr key={sec.id} className={`mappa ${styles.clickable}`} onClick={() => openSection(sec)}>
                                            <td className={styles.sectionNameCell}>
                                                {sec.non_letti > 0 && <span className={styles.unreadMoon}>🌙</span>}
                                                {sec.nome}
                                                {sec.non_letti > 0 && <span className={styles.unreadBadge}>{sec.non_letti}</span>}
                                            </td>
                                            <td className={styles.sectionDescCell}>{sec.descrizione}</td>
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
        /** Filtra i thread in base al testo di ricerca (locale, sui thread già caricati) */
        const filteredThreads = searchQuery.trim()
            ? threads.filter(t =>
                t.titolo.toLowerCase().includes(searchQuery.toLowerCase()) ||
                t.autore.toLowerCase().includes(searchQuery.toLowerCase())
              )
            : threads

        return (
            <div className="pagina_forum">
                <div className={styles.searchBar}>
                    <input
                        value={searchQuery}
                        onChange={e => setSearchQuery(e.target.value)}
                        placeholder="Cerca per termine, titolo o autore"
                        className={styles.searchInput}
                    />
                    <button onClick={() => setSearchQuery(searchQuery)}>cerca</button>
                </div>

                <div className={styles.backBar}>
                    <button onClick={backToSections}>← Torna indietro</button>
                    <span className={styles.sectionHeading}>{currentSection?.nome}</span>
                </div>
                <div className={styles.buttonsBar}>
                    <button onClick={() => setView(isStaff && currentSection?.tipo === 1 ? 'compose_quest' : 'compose')}>
                        {isStaff && currentSection?.tipo === 1 ? 'Nuova Quest' : 'Nuovo Messaggio'}
                    </button>
                </div>

                {loadingThreads ? (
                    <p className={styles.loadingSmall}>Caricamento thread...</p>
                ) : (
                    <>
                        <table className="customTable" style={{ width: '100%' }}>
                            <thead>
                                {/*
                                  * second_header ha color:#a7a7a8 nel CSS — aggiunto color inline
                                  * per forzare il colore arancione come nel vecchio forum.
                                  */}
                                <tr className="second_header">
                                    <td className={styles.theadCenter}>STATO</td>
                                    <td className={styles.theadColor}>TOPIC</td>
                                    <td className={styles.theadColor} style={{ textAlign: 'center' }}>AUTORE</td>
                                    <td className={styles.theadColor} style={{ textAlign: 'center' }}>RISPOSTE</td>
                                    {isStaff && <td className={styles.theadColor}></td>}
                                </tr>
                            </thead>
                            <tbody>
                                {filteredThreads.length === 0 ? (
                                    <tr><td colSpan={isStaff ? 5 : 4} className={styles.emptyState}>Nessuna discussione.</td></tr>
                                ) : (
                                    filteredThreads.map(t => (
                                        <ThreadRow
                                            key={t.id}
                                            thread={t}
                                            onClick={openThread}
                                            isStaff={isStaff}
                                            onAction={handleThreadAction}
                                        />
                                    ))
                                )}
                            </tbody>
                        </table>

                        {/* Paginazione */}
                        {totalPages > 1 && (
                            <div className={`pagination ${styles.pagination}`}>
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

                        <div className={styles.backBar}>
                            <button onClick={backToSections}>← Torna indietro</button>
                        </div>
                    </>
                )}
            </div>
        )
    }

    // --- VISTA INSERIMENTO QUEST ---
    if (view === 'compose_quest') {
        return (
            <ComposeQuest
                section={currentSection}
                sending={sending}
                onSubmit={sendNewQuest}
                onCancel={() => setView('threads')}
            />
        )
    }

    // --- VISTA COMPOSIZIONE NUOVO THREAD ---
    if (view === 'compose') {
        return (
            <div className="pagina_forum">
                <div className={styles.backBar}>
                    <button onClick={() => setView('threads')}>← {currentSection?.nome}</button>
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
                <div className={styles.backBar}>
                    <button onClick={backToThreads}>← {currentSection?.nome}</button>
                </div>

                {loadingRead ? (
                    <p className={styles.loadingSmall}>Caricamento messaggi...</p>
                ) : (
                    <>
                        {/* Tutti i messaggi del thread */}
                        {messages.map((msg, i) => (
                            <div key={msg.id}>
                                <PostCard msg={msg} isFirst={i === 0} />
                                {/* Tabella punti partecipanti — visibile solo a master/admin, solo sul post radice */}
                                {i === 0 && <PuntiTable puntiList={puntiList} />}
                            </div>
                        ))}

                        {/* Form risposta */}
                        <PostForm
                            isNew={false}
                            chiuso={threadClosed}
                            sending={sending}
                            onSubmit={sendReply}
                        />

                        <div className={styles.backBar}>
                            <button onClick={backToThreads}>← {currentSection?.nome}</button>
                        </div>
                    </>
                )}
            </div>
        )
    }

    return null
}
