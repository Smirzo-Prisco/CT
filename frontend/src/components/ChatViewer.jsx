import { useState, useEffect, useRef, useCallback } from 'react'

export default function ChatViewer() {
  const [messages, setMessages] = useState([])
  const lastIdRef = useRef(0)
  const bottomRef = useRef(null)

  const fetchMessages = useCallback(() => {
    fetch('pages/api_chat.php?op=get_chat_messages', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ last: lastIdRef.current }),
    })
      .then(r => r.json())
      .then(data => {
        if (data.status !== 'ok' || !Array.isArray(data.messages)) return

        // Aggiorna elementi fuori dal componente (role status, charLimit, panel)
        const setEl = (id, fn) => { const el = document.getElementById(id); if (el) fn(el) }
        setEl('id_role', el => { el.value = data.activeRole ?? '' })
        if (typeof gdrSetSessionActive === 'function') gdrSetSessionActive(data.activeRole)
        if (data.charLimit > 0) setEl('message', el => { el.maxLength = data.charLimit })
        setEl('quitRole',     el => { el.style.display = data.canQuit    ? 'block' : 'none' })
        setEl('openPanelBtn', el => { el.style.display = data.canUsePanel ? 'block' : 'none' })
        setEl('pgRolePlaying', el => { el.style.display = data.activeRole ? 'block' : 'none' })
        setEl('addPgToRoleBtn', el => { el.style.display = data.canQuit  ? 'none'  : 'block' })
        window.isInRole = data.canQuit

        if (data.messages.length > 0) {
          const withHtml = data.messages.filter(m => m.html)
          if (withHtml.length > 0) {
            setMessages(prev => [...prev, ...withHtml])
            const last = data.messages[data.messages.length - 1]
            if (last?.id) lastIdRef.current = parseInt(last.id, 10)
          }
        }

        if (data.play) new Audio('../sounds/beep.wav').play().catch(() => {})
      })
      .catch(console.error)
  }, [])

  useEffect(() => {
    fetchMessages()

    const sock = window.ctSocket
    if (sock) sock.on('chat:update', fetchMessages)

    // Espone alle funzioni globali in chat.js
    window.refreshChat = fetchMessages
    window.clearChat   = () => { setMessages([]); lastIdRef.current = 0 }

    return () => { if (sock) sock.off('chat:update', fetchMessages) }
  }, [fetchMessages])

  // Scroll automatico quando arrivano nuovi messaggi
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'auto' })
  }, [messages])

  return (
    <div className="chat_inner">
      {messages.map((msg, i) => (
        <div key={msg.id ?? i} dangerouslySetInnerHTML={{ __html: msg.html }} />
      ))}
      <div ref={bottomRef} />
    </div>
  )
}
