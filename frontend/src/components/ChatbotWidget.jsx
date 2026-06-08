/**
 * ChatbotWidget.jsx — Widget chatbot AI Crystal Tokyo
 *
 * Floating action button in basso che apre un pannello chat.
 * Rate limit: C-token giornalieri per utente (applicato lato server).
 * Max 500 caratteri per domanda.
 * Barra C-token: verde → giallo → rosso al consumo.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'

const MAX_CHARS = 500
const API_BASE  = '/pages/api_chatbot.php'

export default function ChatbotWidget() {
    const [open, setOpen]         = useState(false)
    const [messages, setMessages] = useState([])
    const [input, setInput]       = useState('')
    const [loading, setLoading]   = useState(false)
    const [tokenData, setTokenData] = useState({ used: 0, limit: 5000 })
    const messagesEndRef           = useRef(null)

    // Carica stato C-token all'avvio
    useEffect(() => {
        fetch(`${API_BASE}?op=status`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setTokenData({ used: data.tokens_used, limit: data.tokens_limit })
                }
            })
            .catch(() => {})
    }, [])

    // Scroll automatico in fondo ai messaggi
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages, loading])

    const isExhausted = tokenData.used >= tokenData.limit
    const pct         = Math.min(100, Math.round((tokenData.used / tokenData.limit) * 100))

    const tokenBarClass = pct >= 80 ? 'danger' : pct >= 50 ? 'warning' : 'ok'

    const sendMessage = useCallback(async () => {
        const q = input.trim()
        if (!q || loading || isExhausted) return

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
                setTokenData({ used: data.tokens_used, limit: data.tokens_limit })
            } else {
                setMessages(prev => [...prev, { role: 'error', text: data.message }])
            }
        } catch {
            setMessages(prev => [...prev, { role: 'error', text: 'Errore di rete. Riprova.' }])
        } finally {
            setLoading(false)
        }
    }, [input, loading, isExhausted])

    const handleKey = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault()
            sendMessage()
        }
    }

    const charsLeft = MAX_CHARS - input.length
    const canSend   = !loading && input.trim().length > 0 && !isExhausted

    return (
        <div className="chatbot-widget">
            {open && (
                <div className="chatbot-panel">
                    {/* Header */}
                    <div className="chatbot-header">
                        <span className="chatbot-title">
                            <i className="fas fa-robot" /> Crystal Bot
                        </span>
                        <button
                            className="chatbot-close"
                            onClick={() => setOpen(false)}
                            aria-label="Chiudi"
                        >
                            <i className="fas fa-times" />
                        </button>
                    </div>

                    {/* Barra C-token */}
                    <div className="chatbot-token-bar-wrap">
                        <div className={`chatbot-token-bar chatbot-token-bar--${tokenBarClass}`}>
                            <div
                                className="chatbot-token-bar__fill"
                                style={{ width: `${pct}%` }}
                            />
                        </div>
                        <span className={`chatbot-token-label chatbot-token-label--${tokenBarClass}`}>
                            {isExhausted
                                ? 'C-token esauriti'
                                : `${tokenData.used.toLocaleString()} / ${tokenData.limit.toLocaleString()} C-token`
                            }
                        </span>
                    </div>

                    {/* Messaggi */}
                    <div className="chatbot-messages">
                        {messages.length === 0 && (
                            <div className="chatbot-empty">
                                Ciao! Sono Crystal Bot, l&apos;assistente di Crystal Tokyo.<br />
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
                                {msg.role === 'ai'
                                    ? <div className="chatbot-msg-text" dangerouslySetInnerHTML={{ __html: msg.text }} />
                                    : <div className="chatbot-msg-text">{msg.text}</div>
                                }
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
                                placeholder={isExhausted ? 'C-token esauriti per oggi.' : 'Scrivi una domanda… (Invio per inviare)'}
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
                aria-label={open ? 'Chiudi chatbot' : 'Apri Crystal Bot'}
                title="Crystal Bot"
            >
                <i className={`fas ${open ? 'fa-times' : 'fa-robot'}`} />
            </button>
        </div>
    )
}
