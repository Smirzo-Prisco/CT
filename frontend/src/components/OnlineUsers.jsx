import { useState, useEffect, useCallback } from 'react'
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

  useEffect(() => {
    fetchPresenti()

    const heartbeat = setInterval(() => fetchPresenti(), HEARTBEAT_MS)

    const sock = window.ctSocket
    if (sock) sock.on('users:update', () => fetchPresenti())

    return () => {
      clearInterval(heartbeat)
      if (sock) sock.off('users:update')
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
            <div key={user.nome} className="presente">
              <a href="#" onClick={e => { e.preventDefault(); openMsg(user.nome) }}>
                <img src="/themes/crystal/imgs/race_presenti/Sms.png" alt="Messaggio" />
              </a>
              &nbsp;&nbsp;
              {user.gruppo_img && (
                <img src={`/themes/crystal/${user.gruppo_img}`} alt="" width="20" height="20" />
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
