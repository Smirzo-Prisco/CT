import { useState, useEffect, useRef, useCallback } from 'react'
import { createPortal } from 'react-dom'

/** Percentuale (0-100) di value/max, sicura contro max<=0 e valori fuori range. */
const pct = (value, max) => max > 0 ? Math.max(0, Math.min(100, (value / max) * 100)) : 0

export default function ChatViewer() {
  const [messages, setMessages] = useState([])
  const lastIdRef = useRef(0)
  const bottomRef = useRef(null)
  const chatRef   = useRef(null)

  /** Popover statistiche pg aperto cliccando sull'avatar in chat: { nome, loading, data, error } */
  const [pgPopup, setPgPopup] = useState(null)

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
  // oppure sull'avatar circolare di un pg (data-pg, iniettato da api_chat.php) per il
  // popover salute/integrità/livello. I messaggi arrivano come HTML grezzo (dangerouslySetInnerHTML),
  // quindi non è possibile mettere onClick sui singoli elementi: serve delegation sul contenitore.
  const handleChatClick = useCallback((e) => {
    const editTarget = e.target.closest('[data-editable].chat_editable--active')
    if (editTarget) {
      if (typeof window.editAction === 'function') window.editAction(editTarget.dataset.raw ?? '', editTarget.id)
      return
    }

    const avatarTarget = e.target.closest('.chat_avatar_inline, .chat_avatar')
    const nome = avatarTarget?.dataset.pg
    if (!nome) return

    setPgPopup({ nome, loading: true, data: null, error: null })
    fetch(`pages/api_scheda.php?op=profile&pg=${encodeURIComponent(nome)}`)
      .then(r => r.json())
      .then(d => {
        if (d.success) setPgPopup({ nome, loading: false, data: d, error: null })
        else setPgPopup({ nome, loading: false, data: null, error: d.message || 'Personaggio non trovato' })
      })
      .catch(() => setPgPopup({ nome, loading: false, data: null, error: 'Errore di rete' }))
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

      {pgPopup && createPortal(
        <div className="pg-popover-overlay" onClick={() => setPgPopup(null)}>
          <div className="pg-popover" onClick={e => e.stopPropagation()}>
            <button type="button" className="pg-popover__close" onClick={() => setPgPopup(null)}>×</button>

            {pgPopup.loading && <p>Caricamento…</p>}
            {pgPopup.error && <p>{pgPopup.error}</p>}

            {pgPopup.data && (() => {
              const d = pgPopup.data
              const labels = d.config?.stat_names ?? {}
              const livello = d.statistiche?.livello
              return (
                <>
                  <h4 className="pg-popover__nome">{d.nome} {d.cognome}</h4>

                  <div className="pg-popover__stat">
                    <div className="pg-popover__stat-label">
                      <span>{labels.hitpoints ?? 'Salute'}</span>
                      <span>{d.salute}/{d.salute_max}</span>
                    </div>
                    <div className="pg-popover__track">
                      <div className="pg-popover__fill pg-popover__fill--salute" style={{ width: `${pct(d.salute, d.salute_max)}%` }} />
                    </div>
                  </div>

                  <div className="pg-popover__stat">
                    <div className="pg-popover__stat-label">
                      <span>{labels.integrita ?? 'Integrità'}</span>
                      <span>{d.integrita}/{d.integrita_max}</span>
                    </div>
                    <div className="pg-popover__track">
                      <div className="pg-popover__fill pg-popover__fill--integrita" style={{ width: `${pct(d.integrita, d.integrita_max)}%` }} />
                    </div>
                  </div>

                  {/* Il livello è visibile solo per il proprio pg o allo staff (stessa
                      regola di privacy di Scheda.jsx/api_scheda.php): per gli altri
                      personaggi l'endpoint non lo restituisce affatto. */}
                  {livello != null && <span className="pg-popover__livello">Livello {livello}</span>}
                </>
              )
            })()}
          </div>
        </div>,
        document.body
      )}
    </div>
  )
}
