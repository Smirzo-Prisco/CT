/**
 * ChattingOff.jsx
 *
 * Pannello chattina off integrato in fondo alla colonna sinistra.
 * Rimpiazza il vecchio popup chattina_off.php + jQuery polling.
 *
 * Contenuto:
 *   - Log messaggi scrollabile (HTML grezzo da log.html)
 *   - Campo input + invio per scrivere un messaggio
 *   - Pulsante "Pulisci" visibile solo allo staff
 *
 * Aggiornamento real-time:
 *   - Ascolta 'chatoff:update' via socket: ricarica il log ad ogni
 *     nuovo messaggio senza più il polling jQuery ogni 2.5s
 *
 * API: pages/api_chatoff.php
 *   GET  ?op=messages → log HTML + cancella chat_letta per il pg
 *   POST ?op=send     → invia messaggio e notifica via socket
 *   POST ?op=clear    → svuota log (solo staff)
 *
 * Montaggio: via ct:ready su #chattina-off-container in left-right_frames.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'

export default function ChattingOff() {

    /** Contenuto HTML del log (renderizzato con dangerouslySetInnerHTML) */
    const [html, setHtml] = useState('')

    /** Testo corrente nel campo input */
    const [text, setText] = useState('')

    const boxRef  = useRef(null)
    const inputRef = useRef(null)

    const isStaff = !!(window.CT_USER?.admin || window.CT_USER?.moderatore || window.CT_USER?.master)

    /**
     * Recupera il log dalla API e marca i messaggi come letti.
     * Chiamato al mount e ad ogni evento socket 'chatoff:update'.
     */
    const fetchMessages = useCallback(() => {
        fetch('/pages/api_chatoff.php?op=messages')
            .then(r => r.json())
            .then(d => { if (d.success) setHtml(d.html) })
            .catch(err => console.error('[ChattingOff] Errore fetch:', err))
    }, [])

    useEffect(() => {
        fetchMessages()
        const sock = window.ctSocket
        if (sock) sock.on('chatoff:update', fetchMessages)
        return () => { if (sock) sock.off('chatoff:update', fetchMessages) }
    }, [fetchMessages])

    /** Invia un messaggio e ricarica il log */
    const send = useCallback(() => {
        const t = text.trim()
        if (!t) return
        setText('')
        fetch('/pages/api_chatoff.php?op=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: t }),
        })
            .then(r => r.json())
            .then(d => { if (d.success) fetchMessages() })
            .catch(err => console.error('[ChattingOff] Errore send:', err))
    }, [text, fetchMessages])

    /** Svuota il log (solo staff) */
    const clear = useCallback(() => {
        fetch('/pages/api_chatoff.php?op=clear', { method: 'POST' })
            .then(r => r.json())
            .then(d => { if (d.success) setHtml('') })
            .catch(err => console.error('[ChattingOff] Errore clear:', err))
    }, [])

    return (
        <div className="chatoff-panel">

            <div className="chatoff-title">
                Chat Off
                {isStaff && (
                    <button className="chatoff-clear-btn" onClick={clear} title="Svuota log">
                        ✕
                    </button>
                )}
            </div>

            {/* Log messaggi */}
            <div
                ref={boxRef}
                className="chatoff-box"
                dangerouslySetInnerHTML={{ __html: html || '<span class="chatoff-empty">Nessun messaggio</span>' }}
            />

            {/* Input invio */}
            <div className="chatoff-input-row">
                <input
                    ref={inputRef}
                    type="text"
                    className="chatoff-input"
                    value={text}
                    onChange={e => setText(e.target.value)}
                    onKeyDown={e => e.key === 'Enter' && send()}
                    placeholder="Scrivi..."
                    maxLength={500}
                />
                <button className="chatoff-send-btn" onClick={send}>→</button>
            </div>

        </div>
    )
}
