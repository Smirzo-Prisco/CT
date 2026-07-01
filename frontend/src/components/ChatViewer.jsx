import { useState, useEffect, useRef, useCallback } from 'react'

export default function ChatViewer() {
  const [messages, setMessages] = useState([])
  const lastIdRef = useRef(0)
  const bottomRef = useRef(null)
  const chatRef   = useRef(null)

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

        if (data.play && (window.CT_USER?.soundPrefs?.chat ?? 1)) new Audio('../sounds/beep.wav').play().catch(() => {})
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

  // Dopo ogni aggiornamento dei messaggi: marca solo l'ultimo [data-editable] come attivo.
  // La classe chat_editable--active abilita il cursore di edit e l'handler di click.
  useEffect(() => {
    const container = chatRef.current
    if (!container) return
    const editables = container.querySelectorAll('[data-editable]')
    editables.forEach(el => el.classList.remove('chat_editable--active'))
    if (editables.length > 0) editables[editables.length - 1].classList.add('chat_editable--active')
  }, [messages])

  // Event delegation: click su qualsiasi [data-editable].chat_editable--active apre il modale
  const handleChatClick = useCallback((e) => {
    const target = e.target.closest('[data-editable].chat_editable--active')
    if (!target) return
    if (typeof window.editAction === 'function') window.editAction(target.dataset.raw ?? '', target.id)
  }, [])

  useEffect(() => {
    fetchMessages()
    fetchRoomDesc()

    window.refreshChat = fetchMessages
    window.clearChat   = () => { setMessages([]); lastIdRef.current = 0 }

    const sock = window.ctSocket
    let onChatEdit = null

    if (sock) {
      // chat:edit = azione modificata: full-refresh perché il messaggio modificato
      // ha id <= lastIdRef e non verrebbe incluso in un fetch incrementale
      onChatEdit = () => { setMessages([]); lastIdRef.current = 0; fetchMessages() }
      sock.on('chat:update', fetchMessages)
      sock.on('users:update', fetchRoomDesc)
      sock.on('chat:edit',   onChatEdit)
    }

    return () => {
      if (sock) {
        sock.off('chat:update', fetchMessages)
        sock.off('users:update', fetchRoomDesc)
        if (onChatEdit) sock.off('chat:edit', onChatEdit)
      }
    }
  }, [fetchMessages, fetchRoomDesc])

  // Scroll automatico quando arrivano nuovi messaggi
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'auto' })
  }, [messages])

  return (
    <div className="chat_inner" ref={chatRef} onClick={handleChatClick}>

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
