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

import { useState, useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import ChatViewer    from './ChatViewer'
import TargetSelector from './TargetSelector'

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
        s.src     = src
        s.onload  = resolve
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

    /** Ref allo <style> iniettato per le preferenze tipografiche */
    const styleRef = useRef(null)

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
                .catch(() => {})
        }

        sock.on('role:update', onRoleUpdate)
        return () => sock.off('role:update', onRoleUpdate)
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
        if (p.font)           css += `${sel} { font-family: ${p.font} !important; }`
        if (p.colore_testo)   css += `${sel} { color: ${p.colore_testo} !important; }`
        if (p.grandezza)      css += `${sel} { font-size: ${p.grandezza} !important; }`
        if (p.interlinea)     css += `${sel} { line-height: ${p.interlinea} !important; }`
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
                {/* FORM INSERIMENTO AZIONE                                      */}
                {/* ============================================================ */}
                <div className="panels_box">
                    <div className="form_chat">
                        <form method="post" id="chat_form_messages">
                            <div className="form_row">
                                <div className="casella_chat">

                                    {/* Riga superiore: contatore, bottone pannello, help, tag */}
                                    <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', width:'100%' }}>
                                        <span className="gdr-char-counter">
                                            <span id="rimanenti">0</span> caratteri
                                        </span>

                                        {/* Bottone apertura pannello GDR:
                                            - id="openPanelBtn" usato da chat.js (livello modulo, riga ~497)
                                            - visibile a staff sempre e a non-staff solo con role attiva
                                              (no FOUC: la condizione è già corretta al primo render) */}
                                        {showPanelBtn && (
                                            <a href="#" id="openPanelBtn">
                                                <img title="Pannello GDR" src="themes/crystal/imgs/chat/chat_panel.png" className="chat_icon" />
                                            </a>
                                        )}

                                        <a href="#" onClick={(e) => { e.preventDefault(); window.open('chat_help.proc.php','Help','toolbar=no,width=500,height=500') }}>
                                            <img src="themes/crystal/imgs/chat/help.png" alt="Info" className="chat_icon" />
                                        </a>
                                        <input type="text" name="action_tag" className="action-tag" maxLength={30} placeholder="TAG max 30" />
                                    </div>

                                    {/* Riga inferiore: status role, submit */}
                                    <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', width:'100%', gap:'10px' }}>
                                        <input type="hidden" id="id_role" defaultValue="" />

                                        {/* Indicatore stato role con pulsanti avvio/abbandono */}
                                        <div className="gdr-session-status inactive" id="gdrSessionStatus">
                                            <div className="gdr-pulse-dot"></div>
                                            <span className="gdr-status-text">
                                                <span id="roleInProgress" style={{ display:'none' }}>Role in Corso...</span>
                                            </span>
                                            <div className="gdr-animated-border"></div>
                                            <i id="quitRole"
                                               onClick={() => window.quitRole?.(login)}
                                               className="fa-solid fa-power-off"
                                               style={{ cursor:'pointer', display:'none', fontSize:'16px' }}></i>
                                            <i id="pgRolePlaying"
                                               onClick={() => document.getElementById('pgRolePlayingPanel').style.display='block'}
                                               className="fa-solid fa-users"
                                               style={{ cursor:'pointer', display:'none', fontSize:'16px' }}></i>
                                            <i id="addPgToRoleBtn"
                                               onClick={() => window.addPgToRole?.()}
                                               className="fa-solid fa-play"
                                               style={{ cursor:'pointer', color:'green' }}> Avvia!</i>
                                        </div>

                                        <input type="submit" value={submit_label} />
                                    </div>
                                </div>
                            </div>

                            {/* Textarea — onKeyUp chiama le funzioni di chat.js se già caricate */}
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
        </div>

        {/*
          * createPortal: le modali vengono renderizzate come figli diretti di
          * document.body, fuori dalla gerarchia DOM di #maincontent.
          *
          * Motivo: #maincontent ha position:fixed + overflow:auto.
          * In Chrome (e altri browser moderni), i discendenti position:fixed di un
          * ancestor con overflow non-visible vengono clippati da quell'ancestor,
          * anche se position:fixed dovrebbe posizionarsi rispetto al viewport.
          * Con il portal, le modali escono dalla catena DOM e si comportano
          * correttamente (coprono l'intero viewport).
          */}
        {createPortal(<>

            {/* ================================================================ */}
            {/* MODALE — Avvio role (selezione utenti)                          */}
            {/* ================================================================ */}
            <div id="userSearchPopup" className="user-search-popup__overlay">
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
            <div id="pgRolePlayingPanel" className="user-search-popup__overlay">
                <div className="user-search-popup__container">
                    <div className="user-search-popup__header">
                        <h3 className="user-search-popup__title">Personaggi giocanti</h3>
                        <button id="closePopupAdd" className="user-search-popup__close-btn">&times;</button>
                    </div>
                    <div style={{ padding:'12px' }}>
                        <div id="simpleUsersTable">
                            <div style={{ display:'grid', gridTemplateColumns:'1fr 60px 60px 60px 80px', gap:'8px', padding:'6px 8px', backgroundColor:'rgba(42,63,118,0.3)', borderRadius:'6px', marginBottom:'4px', fontSize:'0.85rem' }}>
                                <div>Nome</div>
                                <div style={{ textAlign:'center' }}>Giocante</div>
                                <div style={{ textAlign:'center' }}>Turno inviato</div>
                                <div style={{ textAlign:'center' }}>Turno chiuso</div>
                                <div style={{ textAlign:'center' }}>Azione</div>
                            </div>
                            <div id="pgRolePlayingList" style={{ maxHeight:'220px', overflowY:'auto' }}></div>
                        </div>
                    </div>
                </div>
            </div>

            {/* ================================================================ */}
            {/* PANNELLO GDR — dadi, abilità, armi, master, scrittura            */}
            {/* ================================================================ */}
            <div className="gdr-modal-overlay" id="chatPanel" style={{ display:'none' }}>
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

                            {/* Cura pg: solo in ospedale (luogo=25) con condizioni di salute */}
                            {pulsanti.show_cura && (
                                <a href="#" onClick={(e) => { e.preventDefault(); window.curaPg?.() }} className="gdr-control-btn">
                                    <img src="themes/crystal/imgs/chat/cura.png" alt="Cura pg" />
                                </a>
                            )}

                            {/* Pulisci chat: solo staff */}
                            {pulsanti.show_pulisci && (
                                <a href="#" onClick={(e) => { e.preventDefault(); window.pulisciChat?.() }} className="gdr-control-btn">
                                    <img src="themes/crystal/imgs/chat/pulisci_chat.png" alt="Pulisci chat" />
                                </a>
                            )}

                            {/* Scacchiera: solo admin/master, non in ospedale */}
                            {pulsanti.show_scacchiera && (
                                <a href="#" onClick={(e) => { e.preventDefault(); window.open(`pages/chess.inc.php?id=${luogo}`, 'Log', 'fullscreen,toolbar=no') }} className="gdr-control-btn">
                                    <img src="themes/crystal/imgs/chess/scacchiera.png" alt="Scacchiera" />
                                </a>
                            )}
                        </div>
                        <button className="gdr-close-btn" id="gdrCloseBtn">&times;</button>
                    </div>

                    {/* Tab bar */}
                    <div className="gdr-tabs">
                        <div className="gdr-tab active" id="defaultOpen" data-tab="dice">Dadi e Tiri</div>
                        <div className="gdr-tab" data-tab="skills">Abilità e Armi</div>
                        {pulsanti.is_staff && (
                            <div className="gdr-tab" data-tab="master" onClick={() => window.getPngRolePlaying?.()}>Gestione Master</div>
                        )}
                        <div className="gdr-tab" data-tab="writing">Scrittura</div>
                    </div>

                    {/* ── TAB: DADI E TIRI ─────────────────────────────────── */}
                    <div className="gdr-tab-content active" id="dice-tab">
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
                    <div className="gdr-tab-content" id="skills-tab">
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
                                        <optgroup label="Attacco fisico">
                                            <option value="attacco_fisico">Attacco fisico</option>
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

                    {/* ── TAB: GESTIONE MASTER (solo staff) ────────────────── */}
                    {pulsanti.is_staff && (
                        <div className="gdr-tab-content" id="master-tab">
                            <div className="gdr-master-panel">
                                <div className="gdr-master-panel-title">Area Master</div>
                                <p>Questa area è riservata ai Master per la gestione dei personaggi e PNG.</p>
                            </div>
                            <button className="btn" id="endTurn" onClick={() => window.closeTurn?.()}>Chiudi turno</button>
                            <br /><br />
                            <div className="gdr-grid">
                                {/* Modifica Personaggio */}
                                <div className="gdr-card">
                                    <div className="gdr-card-title">Modifica Personaggio</div>
                                    <div className="gdr-form-group">
                                        <label className="gdr-label" htmlFor="nome_personaggio">Personaggio</label>
                                        <select className="gdr-select" name="nome_personaggio" id="nome_personaggio">
                                            <option value="pg1">Personaggio 1</option>
                                            <option value="pg2">Personaggio 2</option>
                                            <option value="pg3">Personaggio 3</option>
                                        </select>
                                    </div>
                                    <div id="modificaParametri">
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="note_fato">Note del Fato</label>
                                            <textarea className="gdr-textarea" name="note_fato" id="note_fato" rows={3}></textarea>
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="particolari">Particolari</label>
                                            <textarea className="gdr-textarea" name="particolari" id="particolari" rows={3}></textarea>
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="salute">Salute</label>
                                            <input className="gdr-input" type="number" name="salute" id="salute" />
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="integrita">Integrità (0-10)</label>
                                            <input className="gdr-input" type="number" name="integrita" min={0} max={10} id="integrita" />
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="notorieta">Notorietà (0-100)</label>
                                            <input className="gdr-input" type="number" name="notorieta" min={0} max={100} id="notorieta" />
                                        </div>
                                        <div className="gdr-form-group">
                                            <label className="gdr-label" htmlFor="soldi">Soldi</label>
                                            <input className="gdr-input" type="number" name="soldi" min={0} id="soldi" />
                                        </div>
                                        <button className="gdr-button gdr-btn-success" onClick={() => window.gdrEditMasterPgChat?.()}>Modifica Personaggio</button>
                                    </div>
                                </div>

                                {/* Gestione PNG */}
                                <div className="gdr-card">
                                    <div className="gdr-card-title">Gestione PNG</div>
                                    <div className="gdr-form-group" style={{ flex:1 }}>
                                        <label className="gdr-label" htmlFor="pngNew" style={{ flex:1 }}>Nome PNG</label>
                                        <input className="gdr-input" id="pngNew" placeholder="Nome PNG" style={{ flex:2 }} />
                                    </div>
                                    <div className="gdr-form-group" style={{ flex:1 }}>
                                        <label className="gdr-label">Destrezza PNG</label>
                                        <input className="gdr-input" id="pngNewDestrezza" placeholder="Destrezza" />
                                    </div>
                                    <button className="gdr-button gdr-btn-success" onClick={() => window.newMasterPng?.()}>Aggiungi</button>
                                    <div className="gdr-card-title"></div>
                                    <div className="gdr-form-group">
                                        <label className="gdr-label" htmlFor="pngName">PNG attivi</label>
                                        <select className="gdr-select" id="pngName"></select>
                                    </div>
                                    <div className="gdr-form-group">
                                        <label className="gdr-label" htmlFor="pngMessage">Azione PNG</label>
                                        <textarea className="gdr-textarea" id="pngMessage" placeholder="Azione PNG" rows={3}></textarea>
                                    </div>
                                    <div className="gdr-form-group">
                                        <label className="gdr-label" htmlFor="pngCar">Caratteristica PNG</label>
                                        <select className="gdr-select" id="pngCar">
                                            <option value="destrezza">Usa destrezza</option>
                                            <option value="potere">Usa potere</option>
                                            <option value="mente">Usa mente</option>
                                            <option value="tempra">Usa tempra</option>
                                        </select>
                                    </div>
                                    <div className="gdr-form-group">
                                        <label className="gdr-label" htmlFor="pngBonus">Bonus caratteristica PNG</label>
                                        <input className="gdr-input" id="pngBonus" placeholder="Bonus sul dado" />
                                    </div>
                                    <button className="gdr-button gdr-btn-success" onClick={() => window.newMasterPngAction?.()}>Invia</button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* ── TAB: SCRITTURA ───────────────────────────────────── */}
                    <div className="gdr-tab-content" id="writing-tab">
                        <div className="gdr-grid">
                            <div className="gdr-card">
                                <div className="gdr-card-title">Imposta caratteri</div>
                                <div className="gdr-form-group">
                                    <label className="gdr-label" htmlFor="caratteri">Limite Caratteri Azione</label>
                                    <input className="gdr-input" name="caratteri" id="caratteri" placeholder="Limite caratteri" />
                                </div>
                                <button className="gdr-button gdr-btn-success" onClick={() => window.setCharLimit?.()}>Imposta Limite</button>
                            </div>
                            <div className="gdr-card">
                                <div className="gdr-card-title">Scrittura Libera</div>
                                <button className="gdr-button gdr-btn-warning" id="gdrOpenTextareaButton">
                                    Apri Scrittura Libera in Popup
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {/* ================================================================ */}
            {/* MODALE — Modifica azione                                        */}
            {/* ================================================================ */}
            <div className="pg-edit-container" id="editAction-modal" role="dialog" aria-modal="true">
                <div className="modal-content">
                    <span className="close" id="closePgModal">&times;</span>
                    <h2>Modifica Azione</h2>
                    <textarea id="edit_action_textarea" rows={10}></textarea>
                </div>
                <input type="hidden" id="edit_action_id" defaultValue="" />
                <div className="actions">
                    <button onClick={() => window.saveEditAction?.()}>Salva modifiche</button>
                </div>
            </div>

        </>, document.body)}
        </>
    )
}
