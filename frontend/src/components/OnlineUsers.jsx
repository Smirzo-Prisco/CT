import { useEffect, useRef } from 'react'

const HEARTBEAT_MS = 120_000

/** Ping silenzioso: aggiorna solo ultimo_refresh, nessun re-render */
function sendPing() {
  fetch('/pages/api_map.php?op=ping').catch(() => {})
}

// Lista presenti passata da Hud.jsx (fetch condivisa con il badge conteggio,
// vedi fetchPresenti li') invece di una fetch propria verso lo stesso
// api_map.php?op=presenti — qui restano solo l'heartbeat di sessione e il
// rendering, unica parte non duplicata altrove.
export default function OnlineUsers({ users = [], isStaff = false }) {
  const heartbeatRef = useRef(null)

  useEffect(() => {
    heartbeatRef.current = setInterval(sendPing, HEARTBEAT_MS)

    const onIdle = () => {
      clearInterval(heartbeatRef.current)
      heartbeatRef.current = null
    }
    const onActive = () => {
      if (!heartbeatRef.current) {
        heartbeatRef.current = setInterval(sendPing, HEARTBEAT_MS)
      }
    }
    window.addEventListener('ct:idle', onIdle)
    window.addEventListener('ct:active', onActive)

    return () => {
      clearInterval(heartbeatRef.current)
      window.removeEventListener('ct:idle', onIdle)
      window.removeEventListener('ct:active', onActive)
    }
  }, [])

  const openMsg = (nome) => window.CT.navigate(`main.php?page=messages_center&to=${encodeURIComponent(nome)}`)

  return (
    <div className="iframe_online">
      <div className="contenitore_presenti">
        {users.length === 0 ? (
          <div className="centered">
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
                  <img src={`/${user.gruppo_img}`} alt="" />
                )}
                &nbsp;&nbsp;
                {morto && <i className="fa-solid fa-skull pg-morto-icon" title="Morto" />}
                <a
                  href={`/main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`}
                  target="_top"
                  className="online-user-link presenti-nome-cell"
                >
                  {user.nome}
                </a>
                {isStaff && user.sesso === 'b' && (
                  <span className="badge-bot">B</span>
                )}
              </div>
            )
          })
        )}
      </div>
    </div>
  )
}
