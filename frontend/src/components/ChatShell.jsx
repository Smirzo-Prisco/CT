/**
 * ChatShell.jsx
 *
 * Rimpiazza pages/frame_chat.inc.php come shell della chat di gioco.
 *
 * Flusso:
 *   1. Al mount chiama api_chat.php?op=shell → riceve stanza, preferenze,
 *      maxlength, visibilità pulsanti, oggetti, abilità, armi.
 *   2. Renderizza la struttura completa con visibilità corretta fin dal primo
 *      render — nessun FOUC (i pulsanti condizionali sono già corretti).
 *   3. Dopo il render: inietta chat.js e role_session.js se non già caricati,
 *      poi chiama window.initChatListeners() e window.initRoleSession() per
 *      attaccare gli event listener al DOM React appena montato.
 *   4. ChatViewer e TargetSelector rimangono componenti React figli.
 *
 * Nota CSS: le preferenze tipografiche (font/colori) vengono iniettate come
 * <style> dinamico perché richiedono selettori CSS (impossibile con inline).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'
import { createPortal } from 'react-dom'
import ChatViewer from './ChatViewer'
import TargetSelector from './TargetSelector'
import MasterPanel from './MasterPanel'
import ChatHelp from './ChatHelp'

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

/**
 * Carica uno script nel DOM solo se non è già presente (src check).
 * Resolve immediatamente se già caricato, altrimenti attende onload.
 * Usato per chat.js e role_session.js — vanno caricati DOPO il mount.
 */
function loadScriptOnce(src) {
    return new Promise((resolve) => {
        if (document.querySelector(`script[src="${src}"]`)) { resolve(); return }
        const s = document.createElement('script')
        s.src = src
        s.onload = resolve
        s.onerror = resolve   // non blocca il flusso in caso di errore
        document.body.appendChild(s)
    })
}

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI OPZIONI SELECT
// ---------------------------------------------------------------------------

/**
 * Renderizza le <option> per un array di oggetti/armi dell'inventario.
 * cariche = -1 → infinito; cariche > 1 → mostra quantità.
 */
function OggettoOptions({ items }) {
    return items.map(o => (
        <option key={o.id} value={o.id}>
            {o.nome}
            {o.cariche === -1 ? ' (∞)' : o.cariche > 1 ? ` (x ${o.cariche})` : ''}
        </option>
    ))
}

/**
 * Renderizza i <optgroup> delle abilità raggruppate per categoria.
 * Le abilità temporanee mostrano il numero di usi rimanenti.
 */
function AbilitaOptions({ abilita }) {
    return Object.entries(abilita).map(([cat, items]) =>
        items.length === 0 ? null : (
            <optgroup key={cat} label={cat}>
                {items.map(a => (
                    <option key={a.id} value={a.id} data-max-level={a.grado}>
                        {a.nome}
                        {cat === 'Skill Temporanee' ? ` (x${a.usi} usi)` : ''}
                    </option>
                ))}
            </optgroup>
        )
    )
}

// ---------------------------------------------------------------------------
// PANNELLO CURA DI EMERGENZA
// ---------------------------------------------------------------------------

function CuraPanel({ onClose, isOspedale }) {
    const [loadingGiorn, setLoadingGiorn]     = useState(false)
    const [feedbackGiorn, setFeedbackGiorn]   = useState(null)

    const [punti, setPunti]           = useState(10)
    const [loading, setLoading]       = useState(false)
    const [feedback, setFeedback]     = useState(null)

    const [target, setTarget]               = useState('')
    const [loadingStaff, setLoadingStaff]   = useState(false)
    const [feedbackStaff, setFeedbackStaff] = useState(null)

    const handleCuraGiornaliera = useCallback(() => {
        setLoadingGiorn(true)
        setFeedbackGiorn(null)
        fetch('/pages/api_chat.php?op=curaPgGiornaliera', { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                if (data.success)
                    setFeedbackGiorn(`+${data.punti_effettivi} PS. Salute: ${data.nuova_salute}.`)
                else
                    setFeedbackGiorn(data.message)
            })
            .catch(() => setFeedbackGiorn('Errore di rete.'))
            .finally(() => setLoadingGiorn(false))
    }, [])

    const handleApplica = useCallback(() => {
        const p = Math.max(1, Math.min(90, punti))
        setLoading(true)
        setFeedback(null)
        fetch('/pages/api_chat.php?op=curaPg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ punti: p }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success)
                    setFeedback(`+${data.punti_effettivi} PS. Salute attuale: ${data.nuova_salute}/100.`)
                else
                    setFeedback(data.message)
            })
            .catch(() => setFeedback('Errore di rete.'))
            .finally(() => setLoading(false))
    }, [punti])

    const handleCuraAltro = useCallback(() => {
        if (!target.trim()) return
        setLoadingStaff(true)
        setFeedbackStaff(null)
        fetch('/pages/api_chat.php?op=curaAltroPg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ target: target.trim() }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setFeedbackStaff(`${target}: +${data.punti_effettivi} PS. Salute: ${data.nuova_salute}/100.`)
                    setTarget('')
                } else {
                    setFeedbackStaff(data.message)
                }
            })
            .catch(() => setFeedbackStaff('Errore di rete.'))
            .finally(() => setLoadingStaff(false))
    }, [target])

    return (
        <div className="cura-panel">
            <div className="cura-panel__header">
                <span>Ospedale</span>
                <button className="cura-panel__close" onClick={onClose} aria-label="Chiudi">✕</button>
            </div>

            {/* ── Cura giornaliera ── */}
            <div className="cura-panel__daily">
                <div className="cura-panel__daily-label">Cura giornaliera</div>
                <div className="cura-panel__controls">
                    <button
                        className="cura-panel__btn"
                        onClick={handleCuraGiornaliera}
                        disabled={loadingGiorn}
                    >
                        {loadingGiorn ? '...' : 'Ricevi +10 PS'}
                    </button>
                </div>
                {feedbackGiorn && <div className="cura-panel__feedback">{feedbackGiorn}</div>}
            </div>

            {/* ── Cura di emergenza ── */}
            <div className="cura-panel__section">
            <div className="cura-panel__info">
                La cura di emergenza consente il recupero momentaneo dei punti salute specificati.
                A partire da domani, all&rsquo;ingresso in una nuova giocata, al personaggio verranno
                tolti gli stessi punti salute più il 30% di essi.
            </div>
            <div className="cura-panel__controls">
                <input
                    type="number"
                    className="cura-panel__input"
                    min={1}
                    max={90}
                    value={punti}
                    onChange={e => {
                        const v = parseInt(e.target.value, 10)
                        setPunti(isNaN(v) ? 1 : Math.max(1, Math.min(90, v)))
                    }}
                />
                <button
                    className="cura-panel__btn"
                    onClick={handleApplica}
                    disabled={loading}
                >
                    {loading ? '...' : 'Applica cura'}
                </button>
            </div>
            {feedback && <div className="cura-panel__feedback">{feedback}</div>}

            {isOspedale && (
                <div className="cura-panel__staff">
                    <div className="cura-panel__staff-title">Cura paziente</div>
                    <div className="cura-panel__controls">
                        <input
                            type="text"
                            className="cura-panel__target"
                            placeholder="Nome personaggio"
                            value={target}
                            onChange={e => setTarget(e.target.value)}
                            onKeyDown={e => { if (e.key === 'Enter') handleCuraAltro() }}
                        />
                        <button
                            className="cura-panel__btn"
                            onClick={handleCuraAltro}
                            disabled={loadingStaff || !target.trim()}
                        >
                            {loadingStaff ? '...' : 'Assegna 25 PS'}
                        </button>
                    </div>
                    {feedbackStaff && <div className="cura-panel__feedback">{feedbackStaff}</div>}
                </div>
            )}
            </div>{/* end cura-panel__section */}
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function ChatShell() {

    /** Dati shell: stanza, pulsanti, oggetti, abilità, preferenze */
    const [shell, setShell] = useState(null)

    /** Messaggio di errore se l'API fallisce */
    const [error, setError] = useState(null)

    /**
     * Stato reattivo della role attiva nella stanza.
     * Inizializzato da shell.has_active_role, poi aggiornato via socket
     * 'role:update' — così il bottone pannello compare/scompare in tempo
     * reale quando una role inizia o termina, senza ricaricare la pagina.
     */
    const [roleActive, setRoleActive] = useState(false)

    /** Tab attiva nel pannello GDR: 'dice' | 'skills' | 'writing' */
    const [activeTab, setActiveTab] = useState('dice')

    /** Visibilità del pannello GDR — gestita via state per garantire il re-render */
    const [panelOpen, setPanelOpen] = useState(false)

    /** Visibilità del pannello master (solo staff) */
    const [masterPanelOpen, setMasterPanelOpen] = useState(false)

    /** Visibilità del pannello cura di emergenza */
    const [showCuraPanel, setShowCuraPanel] = useState(false)

    /** Mostra la guida chat come overlay (preserva il testo nel form) */
    const [showHelp, setShowHelp] = useState(false)

    /** Ref allo <style> iniettato per le preferenze tipografiche */
    const styleRef = useRef(null)

    /**
     * Lista degli attacchi in arrivo per cui il pg corrente deve scegliere
     * come rispondere. Ogni elemento: { id_fight, attacker, car, choices }.
     * Vengono aggiunti dal socket 'combat:attack_incoming' e rimossi dopo
     * la risposta o quando il turno viene elaborato.
     */
    const [attackPrompts, setAttackPrompts] = useState([])

    /**
     * Prompt di chiusura turno: compare quando tutti i pg hanno dichiarato
     * le loro azioni e il server richiede conferma via 'combat:close_turn'.
     * Null se non c'è nulla da confermare, { id_role } altrimenti.
     */
    const [closeTurnPrompt, setCloseTurnPrompt] = useState(null)

    // -----------------------------------------------------------------------
    // FETCH DATI SHELL
    // -----------------------------------------------------------------------

    /**
     * Al mount chiede all'API tutti i dati necessari a costruire la shell.
     * Il component non renderizza nulla finché non arrivano (null state) —
     * nessun flash di contenuto provvisorio.
     * Inizializza anche roleActive dal valore has_active_role restituito.
     */
    useEffect(() => {
        fetch('/pages/api_chat.php?op=shell')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    setShell(d)
                    setRoleActive(d.has_active_role ?? false)
                } else setError(d.error || 'Errore caricamento chat')
            })
            .catch(() => setError('Errore di rete'))
    }, [])

    /**
     * Espone window.updateRoleActive per permettere a role_session.js di
     * aggiornare roleActive direttamente dopo addPgToRole/quitRole, senza
     * aspettare il round-trip del socket 'role:update'.
     */
    useEffect(() => {
        window.updateRoleActive = setRoleActive
        return () => { delete window.updateRoleActive }
    }, [])

    /**
     * Espone window.openChatPanel / window.closeChatPanel affinché chat.js e
     * role_session.js possano aprire/chiudere il pannello GDR tramite React
     * state invece di manipolare style.display direttamente. Senza questo,
     * il DOM e panelOpen divergono: la prossima setPanelOpen(true) diventa
     * un no-op e il pannello mostra contenuto vuoto.
     */
    useEffect(() => {
        window.openChatPanel = () => setPanelOpen(true)
        window.closeChatPanel = () => setPanelOpen(false)
        return () => { delete window.openChatPanel; delete window.closeChatPanel }
    }, [])

    /**
     * Ascolta l'evento socket 'role:update' per aggiornare roleActive in
     * tempo reale quando una role inizia o termina nella stanza corrente.
     *
     * Quando un pg avvia una role (addPgToRole), il server emette 'role:update'.
     * Ri-controlliamo lo stato chiamando getRolePgs: se restituisce utenti
     * la role è attiva (mostro il pannello); se empty è terminata (nascondo).
     */
    useEffect(() => {
        const sock = window.ctSocket
        if (!sock) return

        const onRoleUpdate = () => {
            fetch('/pages/api_roleSession.php?op=getRolePgs', { method: 'POST' })
                .then(r => r.json())
                .then(d => setRoleActive(d.success && (d.users?.length ?? 0) > 0))
                .catch(() => { })
        }

        sock.on('role:update', onRoleUpdate)
        return () => sock.off('role:update', onRoleUpdate)
    }, [])

    /**
     * Al mount (o dopo un refresh), recupera gli attacchi pendenti del turno
     * corrente a cui il pg non ha ancora risposto e ripristina i pulsanti difesa.
     */
    useEffect(() => {
        if (!shell) return
        fetch('/pages/api_chat.php?op=pending_attacks', { method: 'POST' })
            .then(r => r.json())
            .then(d => {
                if (!d.success || !d.attacks?.length) return
                setAttackPrompts(d.attacks)
            })
            .catch(() => { })
    }, [shell])

    /**
     * Al mount (o dopo un refresh), controlla se il pg deve ancora confermare
     * la chiusura del turno e ripristina il prompt se necessario.
     */
    useEffect(() => {
        if (!shell) return
        fetch('/pages/api_chat.php?op=pending_close_turn', { method: 'POST' })
            .then(r => r.json())
            .then(d => {
                if (d.success && d.pending) setCloseTurnPrompt({ id_role: d.id_role })
            })
            .catch(() => { })
    }, [shell])

    /**
     * Ascolta 'combat:attack_incoming': aggiunge un prompt di difesa per ogni
     * attacco che coinvolge il pg corrente come bersaglio.
     * Dipende da shell perché ha bisogno di shell.login.
     */
    useEffect(() => {
        if (!shell) return
        const sock = window.ctSocket
        if (!sock) return
        const myLogin = shell.login

        const onAttackIncoming = (data) => {
            if (!data?.targets?.[myLogin]) return
            const myInfo = data.targets[myLogin]
            setAttackPrompts(prev => {
                if (prev.some(p => p.id_fight === data.id_fight)) return prev
                return [...prev, {
                    id_fight: data.id_fight,
                    attacker: data.attacker,
                    car: data.car,
                    choices: myInfo.choices,
                }]
            })
        }

        sock.on('combat:attack_incoming', onAttackIncoming)
        return () => sock.off('combat:attack_incoming', onAttackIncoming)
    }, [shell])

    /**
     * Ascolta 'combat:close_turn': il server invia l'evento direttamente alla
     * room personale dm:login, quindi non serve filtrare per pg_name.
     */
    useEffect(() => {
        if (!shell) return
        const sock = window.ctSocket
        if (!sock) return

        const onCloseTurn = (data) => {
            setCloseTurnPrompt({ id_role: data.id_role })
        }

        sock.on('combat:close_turn', onCloseTurn)
        return () => sock.off('combat:close_turn', onCloseTurn)
    }, [shell])

    /**
     * Fallback: quando arriva chat:update durante una role attiva, ri-controlla
     * se c'è un prompt di chiusura turno pendente non ancora mostrato.
     * Recupera i casi in cui il socket event è stato perso (rete instabile, tab
     * in background, ecc.).
     */
    useEffect(() => {
        if (!shell || !roleActive) return
        const sock = window.ctSocket
        if (!sock) return

        const onChatUpdate = () => {
            if (closeTurnPrompt) return // prompt già visibile, niente da fare
            fetch('/pages/api_chat.php?op=pending_close_turn', { method: 'POST' })
                .then(r => r.json())
                .then(d => { if (d.success && d.pending) setCloseTurnPrompt({ id_role: d.id_role }) })
                .catch(() => { })
        }

        sock.on('chat:update', onChatUpdate)
        return () => sock.off('chat:update', onChatUpdate)
    }, [shell, roleActive, closeTurnPrompt])

    /** Conferma la chiusura del turno e rimuove il prompt. */
    const handleCloseTurn = useCallback((id_role) => {
        setCloseTurnPrompt(null)
        fetch('/pages/api_roleSession.php?op=closePgTurn', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_role, suss_id: 0, pgName: shell?.login }),
        })
            .then(r => r.json())
            .then(d => { if (!d.success) console.error('[handleCloseTurn]', d.message) })
            .catch(err => console.error('[handleCloseTurn]', err))
    }, [shell])

    /** Invia la scelta di difesa al server e aggiorna i prompt rimanenti. */
    const handleDefenseChoice = useCallback((id_fight, scelta) => {
        // Aggiornamento ottimistico in base alla scelta:
        //   scudo  → rimuove TUTTI i prompt (lo scudo occupa l'intera azione del turno)
        //   dado   → rimuove il prompt corrente e toglie 'scudo' dagli altri
        //            (dopo aver lanciato un dado non si può più usare lo scudo)
        //   subisce → rimuove solo il prompt corrente, gli altri restano invariati
        setAttackPrompts(prev => {
            if (scelta === 'scudo') return []
            const remaining = prev.filter(p => p.id_fight !== id_fight)
            if (scelta === 'dado') {
                return remaining.map(p => ({
                    ...p,
                    choices: p.choices.filter(c => c !== 'scudo'),
                }))
            }
            return remaining
        })
        fetch('/pages/api_chat.php?op=risposta_immediata', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_fight, scelta }),
        })
            .then(r => r.json())
            .then(d => { if (!d.success) console.error('[handleDefenseChoice]', d.message) })
            .catch(err => console.error('[handleDefenseChoice]', err))
    }, [])

    // -----------------------------------------------------------------------
    // STILE TIPOGRAFICO PERSONALIZZATO
    // -----------------------------------------------------------------------

    /**
     * Inietta un <style> con le preferenze tipografiche del personaggio.
     * Viene fatto via DOM (non inline) perché i selettori CSS come
     * '.chat_row_P .chat_msg' non sono esprimibili con style={{...}}.
     * L'elemento <style> viene rimosso all'unmount del componente.
     */
    useEffect(() => {
        if (!shell?.preferenze) return
        const p = shell.preferenze
        const sel = '.chat_row_P .chat_msg'
        let css = ''
        if (p.font) css += `${sel} { font-family: ${p.font} !important; }`
        if (p.colore_testo) css += `${sel} { color: ${p.colore_testo} !important; }`
        if (p.grandezza) css += `${sel} { font-size: ${p.grandezza} !important; }`
        if (p.interlinea) css += `${sel} { line-height: ${p.interlinea} !important; }`
        if (p.colore_dialogo) css += `.chat_row_P font { color: ${p.colore_dialogo} !important; }`
        if (!css) return

        styleRef.current = document.createElement('style')
        styleRef.current.textContent = css
        document.head.appendChild(styleRef.current)

        return () => { styleRef.current?.remove(); styleRef.current = null }
    }, [shell])

    // -----------------------------------------------------------------------
    // CARICAMENTO SCRIPT LEGACY + RE-INIT DOM
    // -----------------------------------------------------------------------

    /**
     * Carica chat.js e role_session.js se non ancora presenti nel DOM,
     * poi chiama le funzioni di init esposte globalmente.
     *
     * In caricamento PHP tradizionale, DOMContentLoaded li inizializza già.
     * In navigazione SPA, DOMContentLoaded è già scattato — window.initChatListeners()
     * e window.initRoleSession() riattaccano i listener al DOM appena renderizzato.
     */
    useEffect(() => {
        if (!shell) return

        Promise.all([
            loadScriptOnce('/includes/chat.js'),
            loadScriptOnce('/includes/role_session.js'),
        ]).then(() => {
            window.initChatListeners?.()
            window.initRoleSession?.()

            // Fix race condition: ChatViewer potrebbe aver già chiamato fetchMessages
            // e ricevuto activeRole, ma gdrSetSessionActive non era ancora definita
            // (script non ancora caricati). Riallineiamo lo stato dell'indicatore role
            // usando has_active_role dalla shell (già corretto al momento del fetch).
            window.gdrSetSessionActive?.(shell.has_active_role)
        })
    }, [shell])

    // -----------------------------------------------------------------------
    // GUARD: CARICAMENTO / ERRORE
    // -----------------------------------------------------------------------

    if (error) return <div className="warning">{error}</div>

    // Finché i dati non arrivano non renderizza nulla — evita qualsiasi flash
    if (!shell) return null

    const { stanza, pulsanti, oggetti, abilita, creatura, maxlength, submit_label, luogo, login } = shell

    // Il pannello GDR (dadi/skill/armi) è visibile a:
    // - staff (sempre)
    // - tutti gli utenti quando c'è una role attiva nella stanza
    // roleActive è reattivo: si aggiorna via socket 'role:update'
    const showPanelBtn = pulsanti.is_staff || roleActive

    // -----------------------------------------------------------------------
    // RENDERING
    // -----------------------------------------------------------------------

    return (
        <>
            <div className="pagina_frame_chat">
                {showHelp && (
                    <div className="chat-help-overlay">
                        <ChatHelp onBack={() => setShowHelp(false)} />
                    </div>
                )}
                <div className="page_body">

                    {/* Accesso negato a stanza privata */}
                    {!stanza.allowance && (
                        <div className="warning">Stanza privata: accesso negato.</div>
                    )}

                    {/* ============================================================ */}
                    {/* VIEWER MESSAGGI — componente React già migrato               */}
                    {/* ============================================================ */}
                    <div id="pagina_chat" className="chat_box">
                        <ChatViewer />
                    </div>

                    {/* ============================================================ */}
                    {/* PROMPT DIFESA IMMEDIATA — compare quando si è bersaglio      */}
                    {/* ============================================================ */}
                    {attackPrompts.map(prompt => (
                        <div key={prompt.id_fight} className="attack-prompt">
                            <span className="attack-prompt-text">
                                <b>{prompt.attacker}</b> ti attacca con <b>{prompt.car}</b> — come rispondi?
                            </span>
                            <div className="attack-prompt-buttons">
                                {prompt.choices.includes('dado') && (
                                    <button type="button" className="attack-prompt-btn btn-dado"
                                        onClick={() => handleDefenseChoice(prompt.id_fight, 'dado')}>
                                        🎲 Dado
                                    </button>
                                )}
                                {prompt.choices.includes('scudo') && (
                                    <button type="button" className="attack-prompt-btn btn-scudo"
                                        onClick={() => handleDefenseChoice(prompt.id_fight, 'scudo')}>
                                        🛡 Scudo
                                    </button>
                                )}
                                {prompt.choices.includes('subisce') && (
                                    <button type="button" className="attack-prompt-btn btn-subisce"
                                        onClick={() => handleDefenseChoice(prompt.id_fight, 'subisce')}>
                                        💔 Subisci
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}

                    {/* ============================================================ */}
                    {/* PROMPT CHIUSURA TURNO — compare quando tutti hanno agito     */}
                    {/* ============================================================ */}
                    {closeTurnPrompt && (
                        <div className="attack-prompt">
                            <span className="attack-prompt-text">
                                Prima di concludere il tuo turno, accertati di non dover effettuare lanci.
                            </span>
                            <div className="attack-prompt-buttons">
                                <button type="button" className="attack-prompt-btn btn-dado"
                                    onClick={() => handleCloseTurn(closeTurnPrompt.id_role)}>
                                    ✅ Chiudi il turno
                                </button>
                            </div>
                        </div>
                    )}

                    {/* ============================================================ */}
                    {/* FORM INSERIMENTO AZIONE                                      */}
                    {/* ============================================================ */}
                    <div className="panels_box">
                        {showCuraPanel && (
                            <CuraPanel
                                onClose={() => setShowCuraPanel(false)}
                                isOspedale={!!pulsanti.is_ospedale}
                            />
                        )}
                        <div className="form_chat">
                            <form method="post" id="chat_form_messages">

                                {/* Riga 1: tag (sinistra) + submit (destra) */}
                                <div className="chat-submit-row">
                                    <input type="text" name="action_tag" className="action-tag" maxLength={30} placeholder="TAG max 30" />
                                    <div className="chat-action-controls">
                                        <input type="hidden" id="id_role" defaultValue="" />
                                        <span className="gdr-char-counter">
                                            <span id="rimanenti">0</span> caratteri
                                        </span>
                                        {showPanelBtn && (
                                            <a href="#" id="openPanelBtn" onClick={e => {
                                                e.preventDefault()
                                                setPanelOpen(true)
                                            }}>
                                                <img title="Pannello GDR" src="themes/crystal/imgs/chat/chat_panel.png" className="chat_icon" />
                                            </a>
                                        )}
                                        {pulsanti.is_staff && (
                                            <a href="#" onClick={e => { e.preventDefault(); setMasterPanelOpen(true) }}
                                                title="Pannello Master"
                                                style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: '24px', height: '24px', background: 'linear-gradient(135deg,#7b2d8b,#4a1060)', borderRadius: '50%', color: '#e8c9f5', fontWeight: 'bold', fontSize: '13px', fontFamily: 'serif', textDecoration: 'none', boxShadow: '0 0 5px rgba(180,80,220,0.5)', cursor: 'pointer' }}>
                                                M
                                            </a>
                                        )}
                                        {pulsanti.show_cura && (
                                            <a href="#" onClick={e => { e.preventDefault(); setShowCuraPanel(v => !v) }}
                                               title="Cura di Emergenza">
                                                <img src="themes/crystal/imgs/chat/cura.png" alt="Cura di Emergenza" className="chat_icon" />
                                            </a>
                                        )}
                                        {/* <a href="#" onClick={(e) => { e.preventDefault(); setShowHelp(true) }}>
                                            <img src="themes/crystal/imgs/chat/help.png" alt="Info" className="chat_icon" />
                                        </a> */}
                                        <div className="gdr-session-status inactive" id="gdrSessionStatus">
                                            <div className="gdr-pulse-dot"></div>
                                            <i id="quitRole"
                                                onClick={() => window.quitRole?.(login)}
                                                className="fa-solid fa-power-off"
                                                style={{ cursor: 'pointer', display: 'none', fontSize: '16px' }}></i>
                                            <i id="pgRolePlaying"
                                                onClick={() => document.getElementById('pgRolePlayingPanel').style.display = 'flex'}
                                                className="fa-solid fa-users"
                                                style={{ cursor: 'pointer', display: 'none', fontSize: '16px' }}></i>
                                            <i id="addPgToRoleBtn"
                                                onClick={() => window.addPgToRole?.()}
                                                className="fa-solid fa-play chat-avvia-btn"></i>
                                        </div>
                                    </div>
                                    <input type="submit" value={submit_label} />
                                </div>

                                {/* Riga 2: textarea */}
                                <textarea
                                    className="chat_textarea"
                                    name="message"
                                    id="message"
                                    placeholder="Scrivi la tua azione"
                                    maxLength={maxlength}
                                    onKeyUp={(e) => {
                                        window.conta?.(e.target)
                                        if (pulsanti.can_master_msg) window.masterMessageLength?.(e.target, maxlength)
                                    }}
                                />
                            </form>
                        </div>
                    </div>

                </div>
                {/* Portal: solo le modali full-screen (position:fixed) vanno in document.body
             per evitare il clipping di #maincontent (position:fixed + overflow:auto).
             chatPanel e editAction-modal restano qui dentro (position:relative). */}
                {createPortal(<>

                    {/* ================================================================ */}
                    {/* MODALE — Avvio role (selezione utenti)                          */}
                    {/* ================================================================ */}
                    <div id="userSearchPopup" className="user-search-popup__overlay"
                        style={{
                            position: 'fixed', top: 0, left: 'var(--sidebar-modal-offset, 260px)', right: 'var(--sidebar-modal-offset, 260px)', bottom: 0,
                            width: 'auto', display: 'none', justifyContent: 'center',
                            alignItems: 'center', zIndex: 10000
                        }}>
                        <div className="user-search-popup__container">
                            <div className="user-search-popup__header">
                                <h3 className="user-search-popup__title">Seleziona Utenti</h3>
                                <button id="closePopup" className="user-search-popup__close-btn">&times;</button>
                            </div>
                            <div className="user-search-popup__search-section">
                                <input type="text" id="userSearch" className="user-search-popup__search-input" placeholder="Cerca utenti..." autoComplete="off" />
                                <div className="user-search-popup__loading">Ricerca in corso</div>
                                <div id="autocompleteResults" className="user-search-popup__autocomplete-list"></div>
                            </div>
                            <div className="user-search-popup__selected-section">
                                <h4 className="user-search-popup__selected-title">Utenti Selezionati:</h4>
                                <div id="selectedUsersList" className="user-search-popup__selected-list">
                                    <div className="user-search-popup__empty-message">Nessun utente selezionato</div>
                                </div>
                            </div>
                            <div className="user-search-popup__footer">
                                <button id="confirmSelection" className="user-search-popup__confirm-btn" disabled>Conferma</button>
                                <button id="cancelSelection" className="user-search-popup__cancel-btn">Annulla</button>
                            </div>
                        </div>
                    </div>

                    {/* ================================================================ */}
                    {/* MODALE — Personaggi giocanti nella role                         */}
                    {/* ================================================================ */}
                    <div id="pgRolePlayingPanel" className="user-search-popup__overlay"
                        style={{
                            position: 'fixed', top: 0, left: 'var(--sidebar-modal-offset, 260px)', right: 'var(--sidebar-modal-offset, 260px)', bottom: 0,
                            width: 'auto', display: 'none', justifyContent: 'center',
                            alignItems: 'center', zIndex: 10000
                        }}>
                        <div className="user-search-popup__container">
                            <div className="user-search-popup__header">
                                <h3 className="user-search-popup__title">Personaggi giocanti</h3>
                                <button id="closePopupAdd" className="user-search-popup__close-btn">&times;</button>
                            </div>
                            <div className="pg-role-body">
                                <div id="simpleUsersTable">
                                    <div className="pg-role-header">
                                        <div>Nome</div>
                                        <div>Giocante</div>
                                        <div>Turno inviato</div>
                                        <div>Turno chiuso</div>
                                        <div>Azione</div>
                                    </div>
                                    <div id="pgRolePlayingList" className="pg-role-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* ================================================================ */}
                    {/* PANNELLO GDR — in portal per uscire dal flex container          */}
                    {/* position:fixed con le stesse coordinate di pagina_frame_chat    */}
                    {/* ================================================================ */}
                    <div className="gdr-modal-overlay" id="chatPanel"
                        style={{
                            display: panelOpen ? 'flex' : 'none', position: 'fixed', top: 0,
                            left: 'var(--sidebar-modal-offset, 260px)', right: 'var(--sidebar-modal-offset, 260px)', bottom: 0
                        }}>
                        <div className="gdr-panel-container">

                            {/* Header pannello con pulsanti di azione */}
                            <div className="gdr-header">
                                <div className="gdr-logo">Pannello Gestione GDR</div>
                                <div className="gdr-main-controls">
                                    <a href="chat_save.proc.php" target="_blank" className="gdr-control-btn" title="Salva giocata">
                                        <img src="themes/crystal/imgs/chat/blocca_salva.png" alt="Salva" />
                                    </a>

                                    {/* Backchat: visibile solo se esperienza > 19 — no FOUC */}
                                    {pulsanti.show_backchat && (
                                        <a href="#" onClick={(e) => { e.preventDefault(); window.toggleBackChat?.(e.currentTarget) }} className="gdr-control-btn">
                                            <img id="backChatToggle"
                                                title={pulsanti.backchat_on ? 'Disattiva Backchat' : 'Attiva Backchat'}
                                                src={`themes/crystal/imgs/chat/Backchat${pulsanti.backchat_on ? 'ON' : 'OFF'}.png`} />
                                        </a>
                                    )}


                                    {/* Scacchiera: solo admin/master, non in ospedale */}
                                    {pulsanti.show_scacchiera && (
                                        <a href="#" onClick={(e) => { e.preventDefault(); window.open(`pages/chess.inc.php?id=${luogo}`, 'Log', 'fullscreen,toolbar=no') }} className="gdr-control-btn">
                                            <img src="themes/crystal/imgs/chess/scacchiera.png" alt="Scacchiera" />
                                        </a>
                                    )}

                                    {/* Scrittura libera */}
                                    <a href="#" id="gdrOpenTextareaButton" className="gdr-control-btn" title="Scrittura Libera">
                                        <img src="themes/crystal/imgs/chat/writer.png" alt="Scrittura Libera" />
                                    </a>
                                </div>
                                <button className="gdr-close-btn" id="gdrCloseBtn" onClick={() => setPanelOpen(false)}>&times;</button>
                            </div>

                            {/* Tab bar */}
                            <div className="gdr-tabs">
                                <div className={`gdr-tab${activeTab === 'dice' ? ' active' : ''}`} id="defaultOpen"
                                    onClick={() => setActiveTab('dice')}>D20 e Oggetti</div>
                                <div className={`gdr-tab${activeTab === 'skills' ? ' active' : ''}`}
                                    onClick={() => setActiveTab('skills')}>Abilità e Armi</div>
                                <div className={`gdr-tab${activeTab === 'writing' ? ' active' : ''}`}
                                    onClick={() => setActiveTab('writing')}>Scrittura</div>
                            </div>

                            {/* ── TAB: DADI E TIRI ─────────────────────────────────── */}
                            <div className={`gdr-tab-content${activeTab === 'dice' ? ' active' : ''}`} id="dice-tab">
                                <div className="gdr-grid">
                                    {/* Dado generico */}
                                    <div className="gdr-card">
                                        <div className="gdr-card-title">Dado Generico</div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="dado">Tipo di Dado</label>
                                            <select className="gdr-select" name="dado" id="dado">
                                                <option value="0">Dado da</option>
                                                {Array.from({ length: 100 }, (_, i) => i + 1).map(n => (
                                                    <option key={n} value={n}>{n}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <button className="gdr-button gdr-btn-success" onClick={() => window.tiraDadoGenericoChat?.()}>Tira Dado Generico</button>
                                    </div>

                                    {/* Oggetti */}
                                    <div className="gdr-card">
                                        <div className="gdr-card-title">Oggetti</div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="objChat">Oggetto</label>
                                            <select className="gdr-select" id="objChat" name="id_item">
                                                <option value="0">Usa oggetto</option>
                                                {oggetti.curativi.length > 0 && (
                                                    <optgroup label="Medicine">
                                                        <OggettoOptions items={oggetti.curativi} />
                                                    </optgroup>
                                                )}
                                                {oggetti.potenziamenti.length > 0 && (
                                                    <optgroup label="Potenziamenti">
                                                        <OggettoOptions items={oggetti.potenziamenti} />
                                                    </optgroup>
                                                )}
                                                {oggetti.magici.length > 0 && (
                                                    <optgroup label="Oggetti Magici">
                                                        <OggettoOptions items={oggetti.magici} />
                                                    </optgroup>
                                                )}
                                                {oggetti.standard.length > 0 && (
                                                    <optgroup label="Oggetti Generici">
                                                        <OggettoOptions items={oggetti.standard} />
                                                    </optgroup>
                                                )}
                                            </select>
                                        </div>
                                        <button className="gdr-button gdr-btn-success" onClick={() => window.usaOggettoChat?.()}>Usa Oggetto</button>
                                    </div>
                                </div>
                            </div>

                            {/* ── TAB: ABILITÀ E ARMI ──────────────────────────────── */}
                            <div className={`gdr-tab-content${activeTab === 'skills' ? ' active' : ''}`} id="skills-tab">
                                <div className="gdr-master-panel">
                                    <div className="gdr-master-panel-title">Attenzione!</div>
                                    <p>È possibile selezionare una quantità di bersagli pari al livello selezionato dell'abilità.</p>
                                </div>
                                <div className="gdr-grid">
                                    {/* Bersaglio — TargetSelector React */}
                                    <div className="gdr-card">
                                        <div className="gdr-card-title">Bersaglio (*)</div>
                                        <div id="user-selection-box">
                                            <TargetSelector />
                                        </div>
                                    </div>

                                    {/* Abilità */}
                                    <div className="gdr-card">
                                        <div className="gdr-card-title">Abilità</div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="chat_skill">Abilità</label>
                                            <select className="gdr-select" id="chat_skill" name="magie">
                                                <option value="0">Usa skill</option>
                                                <AbilitaOptions abilita={abilita} />
                                            </select>
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="livello_skill">Livello Abilità</label>
                                            <select className="gdr-select" id="livello_skill" name="livello">
                                                <option value="0">0</option>
                                            </select>
                                        </div>
                                        <button className="gdr-button gdr-btn-success" onClick={() => window.tiraSkillChat?.()}>Usa Abilità</button>
                                    </div>

                                    {/* Armi */}
                                    <div className="gdr-card">
                                        <div className="gdr-card-title">Attacchi</div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="tipo_attacco">Tipo di attacco</label>
                                            <select className="gdr-select" id="tipo_attacco" name="arma">
                                                <option value="0">Attacca</option>
                                                <optgroup label="Intervento fisico">
                                                    <option value="attacco_fisico">Attacca fisicamente</option>
                                                    <option value="devia_attacco">Devia l&apos;attacco di</option>
                                                </optgroup>
                                                {oggetti.armi.length > 0 && (
                                                    <optgroup label="Armi">
                                                        <OggettoOptions items={oggetti.armi} />
                                                    </optgroup>
                                                )}
                                                {creatura && (
                                                    <option value="creatura">Attacca con creatura</option>
                                                )}
                                            </select>
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="arma_body">Parte del Corpo</label>
                                            <select className="gdr-select" id="arma_body" name="target_area">
                                                <option value="volto">Volto</option>
                                                <option value="pancia">Pancia</option>
                                                <option value="gamba">Gamba</option>
                                                <option value="punto_vitale">Punto vitale</option>
                                            </select>
                                        </div>
                                        <button className="gdr-button gdr-btn-success" onClick={() => window.usaAttaccoChat?.()}>Usa Arma</button>
                                    </div>
                                </div>
                            </div>

                            {/* ── TAB: SCRITTURA ───────────────────────────────────── */}
                            <div className={`gdr-tab-content${activeTab === 'writing' ? ' active' : ''}`} id="writing-tab">
                                <div className="gdr-grid">
                                    <div className="gdr-card">
                                        <div className="gdr-card-title">Imposta caratteri</div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="caratteri">Limite Caratteri Azione</label>
                                            <input className="gdr-input" name="caratteri" id="caratteri" placeholder="Limite caratteri" />
                                        </div>
                                        <button className="gdr-button gdr-btn-success" onClick={() => window.setCharLimit?.()}>Imposta Limite</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </>, document.body)}

                {/* ================================================================ */}
                {/* PANNELLO MASTER — overlay separato, solo staff                  */}
                {/* ================================================================ */}
                {pulsanti.is_staff && (
                    <MasterPanel
                        isOpen={masterPanelOpen}
                        onClose={() => setMasterPanelOpen(false)}
                        showPulisci={pulsanti.show_pulisci}
                    />
                )}

                {/* ================================================================ */}
                {/* MODALE — Modifica azione                                        */}
                {/* ================================================================ */}
                <div className="pg-edit-container" id="editAction-modal" role="dialog" aria-modal="true">
                    <div className="modal-content">
                        <span className="close" id="closePgModal" onClick={() => document.getElementById('editAction-modal').style.display = 'none'}>&times;</span>
                        <h2>Modifica Azione</h2>
                        <textarea id="edit_action_textarea" rows={10}></textarea>
                    </div>
                    <input type="hidden" id="edit_action_id" defaultValue="" />
                    <div className="actions">
                        <button onClick={() => window.saveEditAction?.()}>Salva modifiche</button>
                    </div>
                </div>

            </div>
        </>
    )
}
