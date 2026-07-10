/**
 * ChatbotWidget.jsx — Widget chatbot AI Crystal Tokyo
 *
 * Floating action button trascinabile che apre un pannello chat.
 * La posizione viene salvata in localStorage e ripristinata alla visita successiva.
 * Un drag > 5px viene rilevato e non scatta l'open/close del pannello.
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
const FAB_SIZE  = 52   // px — corrisponde a width/height di .chatbot-fab in CSS

/** Carica posizione da localStorage, clampata al viewport corrente */
function loadPos() {
    try {
        const s = localStorage.getItem('ct-bot-pos')
        if (s) {
            const p = JSON.parse(s)
            if (typeof p.bottom === 'number' && typeof p.right === 'number') {
                return {
                    right:  Math.max(0, Math.min(window.innerWidth  - FAB_SIZE, p.right)),
                    bottom: Math.max(0, Math.min(window.innerHeight - FAB_SIZE, p.bottom)),
                }
            }
        }
    } catch {}
    return null   // null = lascia governare il CSS
}

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

    // ── Posizione FAB ──────────────────────────────────────────────────────────
    // null = usa il CSS (posizione di default), altrimenti { bottom, right } in px
    const [pos, setPos] = useState(loadPos)
    const posRef = useRef(pos)
    useEffect(() => { posRef.current = pos }, [pos])

    // Persiste la posizione in localStorage
    useEffect(() => {
        if (pos) {
            try { localStorage.setItem('ct-bot-pos', JSON.stringify(pos)) } catch {}
        }
    }, [pos])

    // Stato interno del drag (ref per evitare re-render durante il movimento)
    const dragRef    = useRef(null)   // { startX, startY, startRight, startBottom, moved }
    const didDragRef = useRef(false)  // true se l'ultimo pointerdown ha prodotto un drag

    // ── Avvio drag ─────────────────────────────────────────────────────────────
    const onPointerDown = useCallback((e) => {
        if (e.button !== undefined && e.button !== 0) return  // solo tasto sinistro

        const clientX = e.touches ? e.touches[0].clientX : e.clientX
        const clientY = e.touches ? e.touches[0].clientY : e.clientY

        // Legge la posizione reale del widget dal DOM (funziona sia con CSS che con inline style)
        const widgetEl = e.currentTarget.closest('.chatbot-widget')
        const rect     = widgetEl.getBoundingClientRect()
        const vw       = window.innerWidth
        const vh       = window.innerHeight

        dragRef.current = {
            startX:      clientX,
            startY:      clientY,
            startRight:  vw - rect.right,    // offset dal bordo destro del viewport
            startBottom: vh - rect.bottom,   // offset dal bordo inferiore del viewport
            moved:       false,
        }
        didDragRef.current = false
    }, [])

    // ── Movimento e fine drag (listener su document, montati una volta) ────────
    useEffect(() => {
        const onMove = (e) => {
            if (!dragRef.current) return

            const clientX = e.touches ? e.touches[0].clientX : e.clientX
            const clientY = e.touches ? e.touches[0].clientY : e.clientY

            const dx = clientX - dragRef.current.startX
            const dy = clientY - dragRef.current.startY

            // Soglia 5px per distinguere il tap dal drag
            if (!dragRef.current.moved && Math.abs(dx) < 5 && Math.abs(dy) < 5) return
            dragRef.current.moved = true

            // right  aumenta spostandosi verso sinistra  (dx negativo)
            // bottom aumenta spostandosi verso l'alto    (dy negativo)
            const newRight  = dragRef.current.startRight  - dx
            const newBottom = dragRef.current.startBottom - dy

            const vw = window.innerWidth
            const vh = window.innerHeight

            setPos({
                right:  Math.max(0, Math.min(vw - FAB_SIZE, newRight)),
                bottom: Math.max(0, Math.min(vh - FAB_SIZE, newBottom)),
            })

            e.preventDefault()   // blocca lo scroll su mobile durante il drag
        }

        const onEnd = () => {
            if (!dragRef.current) return
            if (dragRef.current.moved) didDragRef.current = true
            dragRef.current = null
        }

        document.addEventListener('mousemove', onMove)
        document.addEventListener('mouseup',   onEnd)
        document.addEventListener('touchmove', onMove, { passive: false })
        document.addEventListener('touchend',  onEnd)

        return () => {
            document.removeEventListener('mousemove', onMove)
            document.removeEventListener('mouseup',   onEnd)
            document.removeEventListener('touchmove', onMove)
            document.removeEventListener('touchend',  onEnd)
        }
    }, [])   // nessuna dipendenza: accede solo a ref

    // ── Click FAB: apre/chiude solo se non è stato un drag ───────────────────
    const handleFabClick = () => {
        if (didDragRef.current) { didDragRef.current = false; return }
        setOpen(prev => !prev)
    }

    // Apertura esterna (icona "Assistente" nell'arco destro dell'HUD, vedi Hud.jsx)
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

    // Stile inline applicato solo quando l'utente ha spostato il FAB (override CSS)
    const widgetStyle = pos
        ? { bottom: `${pos.bottom}px`, right: `${pos.right}px`, transform: 'none' }
        : {}

    return (
        <div className="chatbot-widget" style={widgetStyle}>
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
            )}

            {/* FAB — onMouseDown/onTouchStart avviano il drag */}
            <button
                className={`chatbot-fab ${open ? 'chatbot-fab--open' : ''}`}
                onMouseDown={onPointerDown}
                onTouchStart={onPointerDown}
                onClick={handleFabClick}
                aria-label={open ? 'Chiudi chatbot' : 'Apri Crystal Bot'}
                title="Crystal Bot — trascina per spostare"
            >
                <i className={`fas ${open ? 'fa-times' : 'fa-robot'}`} />
            </button>
        </div>
    )
}
