import { useState, useEffect, useRef, useCallback } from 'react'

export default function ChatViewer() {
  const [messages, setMessages] = useState([])
  const lastIdRef = useRef(0)
  const bottomRef = useRef(null)

  /** true quando c'è una role attiva — nasconde la descrizione stanza */
  const [hasRole,  setHasRole]  = useState(false)
  /** Dati descrizione della stanza corrente (da api_map.php?op=current) */
  const [roomDesc, setRoomDesc] = useState(null)

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

        setHasRole(!!data.activeRole)

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

  /** Recupera la descrizione della stanza corrente */
  const fetchRoomDesc = useCallback(() => {
    fetch('/pages/api_map.php?op=current')
      .then(r => r.json())
      .then(d => {
        if (d.success && d.tipo === 'stanza') setRoomDesc(d)
        else setRoomDesc(null)
      })
      .catch(() => {})
  }, [])

  useEffect(() => {
    fetchMessages()
    fetchRoomDesc()

    const sock = window.ctSocket
    if (sock) {
      sock.on('chat:update', fetchMessages)
      // Aggiorna la descrizione quando il pg cambia stanza
      sock.on('users:update', fetchRoomDesc)
    }

    // Espone alle funzioni globali in chat.js
    window.refreshChat = fetchMessages
    window.clearChat   = () => { setMessages([]); lastIdRef.current = 0 }

    return () => {
      if (sock) {
        sock.off('chat:update', fetchMessages)
        sock.off('users:update', fetchRoomDesc)
      }
    }
  }, [fetchMessages, fetchRoomDesc])

  // Scroll automatico quando arrivano nuovi messaggi
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'auto' })
  }, [messages])

  return (
    <div className="chat_inner">

      {/* Descrizione stanza — visibile solo quando la chat è vuota e nessuna role è attiva */}
      {messages.length === 0 && !hasRole && roomDesc && (
        <div className="chat-room-desc">
          {roomDesc.descrizione_immagine && (
            <img
              className="chat-room-desc__img"
              src={`/themes/crystal/imgs/descrizioni/${roomDesc.descrizione_immagine}`}
              alt={roomDesc.nome}
            />
          )}
          <p className="chat-room-desc__nome">{roomDesc.nome}</p>
          {roomDesc.stato && (
            <p className="chat-room-desc__stato">Stato: {roomDesc.stato}</p>
          )}
          <div
            className="chat-room-desc__testo"
            dangerouslySetInnerHTML={{ __html: roomDesc.descrizione }}
          />
        </div>
      )}

      {messages.map((msg, i) => (
        <div key={msg.id ?? i} dangerouslySetInnerHTML={{ __html: msg.html }} />
      ))}
      <div ref={bottomRef} />
    </div>
  )
}
