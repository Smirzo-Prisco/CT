/**
 * ChatbotWidget.jsx — Widget chatbot AI Crystal Tokyo
 *
 * Pannello chat, senza piu' un proprio pulsante flottante: si apre tramite
 * l'icona "Assistente" nel ventaglio centrale dell'HUD (Hud.jsx),
 * che dispatcha l'evento 'ct:chatbot-open' ascoltato qui sotto. Si chiude
 * dal bottone di chiusura nell'header del pannello.
 *
 * Rate limit: C-token giornalieri per utente (applicato lato server).
 * Max 500 caratteri per domanda.
 * Barra C-token: verde → giallo → rosso al consumo.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'

const MAX_CHARS = 500
const API_BASE  = '/pages/api_chatbot.php'

/** Converte markdown base in HTML per la visualizzazione in chat */
function markdownToHtml(text) {
    return text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/^#{1,6}\s+(.+)$/gm, '<strong>$1</strong>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>')
}

export default function ChatbotWidget() {
    const [open, setOpen]           = useState(false)
    const [messages, setMessages]   = useState(() => {
        try { return JSON.parse(localStorage.getItem('ct-bot-history') ?? '[]') } catch { return [] }
    })
    const [input, setInput]         = useState('')
    const [loading, setLoading]     = useState(false)
    const [tokenData, setTokenData] = useState({ used: 0, limit: 5000 })
    const messagesEndRef            = useRef(null)

    // Apertura esterna (icona "Assistente" nel ventaglio centrale dell'HUD, vedi Hud.jsx)
    useEffect(() => {
        const onExternalOpen = () => setOpen(true)
        window.addEventListener('ct:chatbot-open', onExternalOpen)
        return () => window.removeEventListener('ct:chatbot-open', onExternalOpen)
    }, [])

    // ── Logica chatbot ────────────────────────────────────────────────────────
    useEffect(() => {
        fetch(`${API_BASE}?op=status`)
            .then(r => r.json())
            .then(data => {
                if (data.success) setTokenData({ used: data.tokens_used, limit: data.tokens_limit })
            })
            .catch(() => {})
    }, [])

    useEffect(() => {
        try { localStorage.setItem('ct-bot-history', JSON.stringify(messages.slice(-100))) } catch {}
    }, [messages])

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
                setMessages(prev => [...prev, { role: 'ai', text: markdownToHtml(data.risposta) }])
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
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage() }
    }

    const charsLeft = MAX_CHARS - input.length
    const canSend   = !loading && input.trim().length > 0 && !isExhausted

    if (!open) return null

    return (
        <div className="chatbot-widget">
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
                        <div className="chatbot-token-bar__fill" style={{ width: `${pct}%` }} />
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
                            <div className="chatbot-msg-icon"><i className="fas fa-robot" /></div>
                            <div className="chatbot-typing"><span /><span /><span /></div>
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
        </div>
    )
}
