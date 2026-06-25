import { useState, useEffect, useCallback, useRef } from 'react'
import shared from './shared.module.css'

const HEARTBEAT_MS = 120_000

/** Ping silenzioso: aggiorna solo ultimo_refresh, nessun re-render */
function sendPing() {
  fetch('/pages/api_map.php?op=ping').catch(() => {})
}

export default function OnlineUsers() {
  const [users, setUsers] = useState([])

  const fetchPresenti = useCallback(() => {
    fetch('/pages/api_map.php?op=presenti')
      .then(r  => r.json())
      .then(data => { if (data.success) setUsers(data.users) })
      .catch(console.error)
  }, [])

  const heartbeatRef = useRef(null)
  const isIdleRef    = useRef(false)

  useEffect(() => {
    fetchPresenti()
    heartbeatRef.current = setInterval(sendPing, HEARTBEAT_MS)

    const sock = window.ctSocket
    // Non chiamare fetchPresenti se l'utente è idle: op=presenti resetta
    // disponibile=1 nel DB, annullando lo stato assente.
    if (sock) sock.on('users:update', () => { if (!isIdleRef.current) fetchPresenti() })

    const onIdle   = () => {
      isIdleRef.current = true
      clearInterval(heartbeatRef.current)
      heartbeatRef.current = null
    }
    const onActive = () => {
      isIdleRef.current = false
      if (!heartbeatRef.current) {
        heartbeatRef.current = setInterval(sendPing, HEARTBEAT_MS)
      }
    }
    window.addEventListener('ct:idle',   onIdle)
    window.addEventListener('ct:active', onActive)

    return () => {
      clearInterval(heartbeatRef.current)
      if (sock) sock.off('users:update')
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
          users.map(user => {
            const morto = user.salute === 0
            return (
              <div key={user.nome} className={`presente${!user.disponibile ? ' presente-assente' : ''}${morto ? ' pg-morto' : ''}`}>
                <a href="#" onClick={e => { e.preventDefault(); openMsg(user.nome) }}>
                  <img src="/themes/crystal/imgs/race_presenti/Sms.png" alt="Messaggio" />
                </a>
                &nbsp;&nbsp;
                {user.gruppo_img && (
                  <img src={`/${user.gruppo_img}`} alt="" style={{ height: '17px', width: 'auto' }} />
                )}
                &nbsp;&nbsp;
                {morto && <i className="fa-solid fa-skull pg-morto-icon" title="Morto" />}
                <a
                  href={`/main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`}
                  target="_top"
                  className="online-user-link"
                >
                  {user.nome}
                </a>
              </div>
            )
          })
        )}
      </div>
    </div>
  )
}
