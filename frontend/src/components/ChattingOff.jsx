/**
 * ChattingOff.jsx
 *
 * Pannello chattina off integrato in fondo alla colonna sinistra.
 * Rimpiazza il vecchio popup chattina_off.php + jQuery polling.
 *
 * Contenuto:
 *   - Log messaggi scrollabile (HTML grezzo da log.html)
 *   - Campo input + invio per scrivere un messaggio
 *   - Indicatore "sta scrivendo" in tempo reale via socket
 *   - Pulsante "Pulisci" visibile solo allo staff
 *
 * Aggiornamento real-time:
 *   - 'chatoff:update' via socket → ricarica il log
 *   - 'chatoff:typing' via socket → mostra chi sta scrivendo
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

    const [html, setHtml]               = useState('')
    const [text, setText]               = useState('')
    const [typingUsers, setTypingUsers] = useState(new Set())
    const [hasNew, setHasNew]           = useState(false)

    const boxRef            = useRef(null)
    const inputRef          = useRef(null)
    const typingEmitTimeout = useRef(null)   // timeout per emettere lo stop del proprio typing
    const typingUserTimeout = useRef({})     // { nome: timeoutId } per auto-rimuovere chi ha smesso

    const isStaff = !!(window.CT_USER?.admin || window.CT_USER?.moderatore || window.CT_USER?.master)

    // ---------------------------------------------------------------------------
    // FETCH LOG
    // ---------------------------------------------------------------------------

    const fetchMessages = useCallback(() => {
        fetch('/pages/api_chatoff.php?op=messages')
            .then(r => r.json())
            .then(d => { if (d.success) setHtml(d.html) })
            .catch(err => console.error('[ChattingOff] Errore fetch:', err))
    }, [])

    useEffect(() => {
        fetchMessages()
        const sock = window.ctSocket
        if (!sock) return
        const handleUpdate = () => { setHasNew(true); fetchMessages() }
        sock.on('chatoff:update', handleUpdate)
        return () => { sock.off('chatoff:update', handleUpdate) }
    }, [fetchMessages])

    // ---------------------------------------------------------------------------
    // TYPING INDICATOR — ricezione
    // ---------------------------------------------------------------------------

    useEffect(() => {
        const sock = window.ctSocket
        if (!sock) return

        const handleTyping = ({ nome, typing }) => {
            // Cancella il timeout di sicurezza precedente per questo utente
            clearTimeout(typingUserTimeout.current[nome])

            if (typing) {
                setTypingUsers(prev => new Set([...prev, nome]))
                // Safety net: rimuove automaticamente dopo 4s senza aggiornamenti
                typingUserTimeout.current[nome] = setTimeout(() => {
                    setTypingUsers(prev => { const s = new Set(prev); s.delete(nome); return s })
                }, 4000)
            } else {
                setTypingUsers(prev => { const s = new Set(prev); s.delete(nome); return s })
            }
        }

        sock.on('chatoff:typing', handleTyping)
        return () => sock.off('chatoff:typing', handleTyping)
    }, [])

    // ---------------------------------------------------------------------------
    // INPUT
    // ---------------------------------------------------------------------------

    const handleInputChange = useCallback((e) => {
        setText(e.target.value)

        const sock = window.ctSocket
        if (!sock) return

        sock.emit('chatoff:typing', true)

        clearTimeout(typingEmitTimeout.current)
        typingEmitTimeout.current = setTimeout(() => {
            sock.emit('chatoff:typing', false)
        }, 3000)
    }, [])

    // ---------------------------------------------------------------------------
    // SEND / CLEAR
    // ---------------------------------------------------------------------------

    const send = useCallback(() => {
        const t = text.trim()
        if (!t) return
        setText('')

        // Smetti di segnalare che stai scrivendo
        clearTimeout(typingEmitTimeout.current)
        if (window.ctSocket) window.ctSocket.emit('chatoff:typing', false)

        fetch('/pages/api_chatoff.php?op=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: t }),
        })
            .then(r => r.json())
            .then(d => { if (d.success) fetchMessages() })
            .catch(err => console.error('[ChattingOff] Errore send:', err))
    }, [text, fetchMessages])

    const clear = useCallback(() => {
        fetch('/pages/api_chatoff.php?op=clear', { method: 'POST' })
            .then(r => r.json())
            .then(d => { if (d.success) setHtml('') })
            .catch(err => console.error('[ChattingOff] Errore clear:', err))
    }, [])

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    const names = [...typingUsers]
    const typingText = names.length === 0 ? null
        : names.length === 1 ? `${names[0]} sta scrivendo...`
        : `${names.join(', ')} stanno scrivendo...`

    return (
        <div
            className={`chatoff-panel${hasNew ? ' chatoff-panel--new' : ''}`}
            onFocus={() => setHasNew(false)}
            onClick={() => setHasNew(false)}
        >

            <div className="chatoff-title">
                Chat Off
                {isStaff && (
                    <button className="chatoff-clear-btn" onClick={clear} title="Svuota log">✕</button>
                )}
            </div>

            <div
                ref={boxRef}
                className="chatoff-box"
                dangerouslySetInnerHTML={{ __html: html || '<span class="chatoff-empty">Nessun messaggio</span>' }}
            />

            {typingText && (
                <div className="chatoff-typing">{typingText}</div>
            )}

            <div className="chatoff-input-row">
                <input
                    ref={inputRef}
                    type="text"
                    className="chatoff-input"
                    value={text}
                    onChange={handleInputChange}
                    onKeyDown={e => e.key === 'Enter' && send()}
                    placeholder="Scrivi..."
                    maxLength={500}
                />
                <button className="chatoff-send-btn" onClick={send}>→</button>
            </div>

        </div>
    )
}
