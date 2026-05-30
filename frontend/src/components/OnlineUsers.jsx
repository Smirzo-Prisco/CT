import { useState, useEffect, useCallback, useRef } from 'react'
import shared from './shared.module.css'

const HEARTBEAT_MS = 120_000

export default function OnlineUsers() {
  const [users, setUsers] = useState([])

  const fetchPresenti = useCallback((qs = '') => {
    fetch('/pages/api_map.php?op=presenti' + qs)
      .then(r => r.json())
      .then(data => {
        if (data.success) setUsers(data.users)
      })
      .catch(console.error)
  }, [])

  const heartbeatRef = useRef(null)

  useEffect(() => {
    fetchPresenti()
    heartbeatRef.current = setInterval(fetchPresenti, HEARTBEAT_MS)

    const sock = window.ctSocket
    if (sock) sock.on('users:update', fetchPresenti)

    // Pausa heartbeat quando l'utente è assente; ripresa quando torna attivo
    const onIdle   = () => { clearInterval(heartbeatRef.current); heartbeatRef.current = null }
    const onActive = () => {
      if (!heartbeatRef.current) {
        fetchPresenti()
        heartbeatRef.current = setInterval(fetchPresenti, HEARTBEAT_MS)
      }
    }
    window.addEventListener('ct:idle',   onIdle)
    window.addEventListener('ct:active', onActive)

    return () => {
      clearInterval(heartbeatRef.current)
      if (sock) sock.off('users:update', fetchPresenti)
      window.removeEventListener('ct:idle',   onIdle)
      window.removeEventListener('ct:active', onActive)
    }
  }, [fetchPresenti])

  const openMsg = (nome) => window.CT.navigate(`main.php?page=messages_center&to=${encodeURIComponent(nome)}`)

  return (
    <div className="iframe_online">
      <div className="contenitore_presenti">
        {users.length === 0 ? (
          <div className={shared.centered}>
            Nessun utente online
          </div>
        ) : (
          users.map(user => (
            <div key={user.nome} className={`presente${user.assente ? ' presente-assente' : ''}`}>
              <a href="#" onClick={e => { e.preventDefault(); openMsg(user.nome) }}>
                <img src="/themes/crystal/imgs/race_presenti/Sms.png" alt="Messaggio" />
              </a>
              &nbsp;&nbsp;
              {user.gruppo_img && (
                <img src={`/${user.gruppo_img}`} alt="" style={{ height: '14px', width: 'auto' }} />
              )}
              &nbsp;&nbsp;
              <a
                href={`/main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`}
                target="_top"
                className="online-user-link"
              >
                {user.nome}
              </a>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
