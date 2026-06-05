/**
 * ChatbotWidget.jsx — Widget chatbot AI Crystal Tokyo
 *
 * Floating action button in basso a destra che apre un pannello chat.
 * Rate limit: 5 domande/giorno per utente (applicato lato server).
 * Max 500 caratteri per domanda.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'

const MAX_CHARS = 500
const API_BASE  = '/pages/api_chatbot.php'

export default function ChatbotWidget() {
    const [open, setOpen]           = useState(false)
    const [messages, setMessages]   = useState([])
    const [input, setInput]         = useState('')
    const [loading, setLoading]     = useState(false)
    const [remaining, setRemaining] = useState(null)
    const messagesEndRef             = useRef(null)

    // Carica quante domande rimangono oggi all'avvio
    useEffect(() => {
        fetch(`${API_BASE}?op=status`)
            .then(r => r.json())
            .then(data => { if (data.success) setRemaining(data.remaining) })
            .catch(() => {})
    }, [])

    // Scroll automatico in fondo ai messaggi
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages, loading])

    const sendMessage = useCallback(async () => {
        const q = input.trim()
        if (!q || loading || remaining === 0) return

        setMessages(prev => [...prev, { role: 'user', text: q }])
        setInput('')
        setLoading(true)

        try {
            const resp = await fetch(`${API_BASE}?op=ask`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ domanda: q }),
            })
            const data = await resp.json()
            if (data.success) {
                setMessages(prev => [...prev, { role: 'ai', text: data.risposta }])
                setRemaining(data.remaining)
            } else {
                setMessages(prev => [...prev, { role: 'error', text: data.message }])
            }
        } catch {
            setMessages(prev => [...prev, { role: 'error', text: 'Errore di rete. Riprova.' }])
        } finally {
            setLoading(false)
        }
    }, [input, loading, remaining])

    const handleKey = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault()
            sendMessage()
        }
    }

    const charsLeft    = MAX_CHARS - input.length
    const isExhausted  = remaining === 0
    const canSend      = !loading && input.trim().length > 0 && !isExhausted

    return (
        <div className="chatbot-widget">
            {open && (
                <div className="chatbot-panel">
                    {/* Header */}
                    <div className="chatbot-header">
                        <span className="chatbot-title">
                            <i className="fas fa-robot" /> Crystal Tokyo AI
                        </span>
                        {remaining !== null && (
                            <span className={`chatbot-remaining ${isExhausted ? 'chatbot-remaining--empty' : ''}`}>
                                {isExhausted ? 'Domande esaurite' : `${remaining}/5 rimaste`}
                            </span>
                        )}
                        <button
                            className="chatbot-close"
                            onClick={() => setOpen(false)}
                            aria-label="Chiudi"
                        >
                            <i className="fas fa-times" />
                        </button>
                    </div>

                    {/* Messaggi */}
                    <div className="chatbot-messages">
                        {messages.length === 0 && (
                            <div className="chatbot-empty">
                                Ciao! Sono l&apos;assistente di Crystal Tokyo.<br />
                                Puoi chiedermi informazioni sul regolamento e sull&apos;ambientazione.
                            </div>
                        )}
                        {messages.map((msg, i) => (
                            <div key={i} className={`chatbot-msg chatbot-msg--${msg.role}`}>
                                <div className="chatbot-msg-icon">
                                    {msg.role === 'ai'    && <i className="fas fa-robot" />}
                                    {msg.role === 'user'  && <i className="fas fa-user" />}
                                    {msg.role === 'error' && <i className="fas fa-exclamation-triangle" />}
                                </div>
                                <div className="chatbot-msg-text">{msg.text}</div>
                            </div>
                        ))}
                        {loading && (
                            <div className="chatbot-msg chatbot-msg--ai">
                                <div className="chatbot-msg-icon">
                                    <i className="fas fa-robot" />
                                </div>
                                <div className="chatbot-typing">
                                    <span /><span /><span />
                                </div>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <div className="chatbot-input-area">
                        <div className="chatbot-input-wrap">
                            <textarea
                                className="chatbot-input"
                                value={input}
                                onChange={e => setInput(e.target.value.slice(0, MAX_CHARS))}
                                onKeyDown={handleKey}
                                placeholder={isExhausted ? 'Domande esaurite per oggi.' : 'Scrivi una domanda… (Invio per inviare)'}
                                disabled={loading || isExhausted}
                                rows={2}
                            />
                            <span className={`chatbot-chars ${charsLeft <= 50 ? 'chatbot-chars--warn' : ''}`}>
                                {charsLeft}
                            </span>
                        </div>
                        <button
                            className="chatbot-send"
                            onClick={sendMessage}
                            disabled={!canSend}
                            aria-label="Invia"
                        >
                            {loading
                                ? <i className="fas fa-spinner fa-spin" />
                                : <i className="fas fa-paper-plane" />
                            }
                        </button>
                    </div>
                </div>
            )}

            {/* FAB */}
            <button
                className={`chatbot-fab ${open ? 'chatbot-fab--open' : ''}`}
                onClick={() => setOpen(prev => !prev)}
                aria-label={open ? 'Chiudi chatbot' : 'Apri assistente Crystal Tokyo'}
                title="Assistente Crystal Tokyo"
            >
                <i className={`fas ${open ? 'fa-times' : 'fa-robot'}`} />
            </button>
        </div>
    )
}
