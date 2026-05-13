import { useState, useEffect, useCallback } from 'react'

const HEARTBEAT_MS = 120_000

function getRaceIcon(id_razza) {
  const n = parseInt(id_razza, 10)
  if (n >= 1000  && n < 2000)  return 'Marte.png'
  if (n >= 2000  && n < 3000)  return 'Mercurio.png'
  if (n >= 3000  && n < 4000)  return 'Luna.png'
  if (n >= 4000  && n < 5000)  return 'Giove.png'
  if (n >= 5000  && n < 6000)  return 'Venere.png'
  if (n >= 6000  && n < 7000)  return 'Urano.png'
  if (n >= 7000  && n < 8000)  return 'Nettuno.png'
  if (n >= 8000  && n < 9000)  return 'Plutone.png'
  if (n >= 9000  && n < 10000) return 'Saturno.png'
  if (n >= 10000 && n < 11000) return 'Terra.png'
  if (n === 11000)              return 'Nebbia.png'
  return 'Png.png'
}

export default function OnlineUsers() {
  const [users, setUsers]           = useState([])
  const [totalOnline, setTotalOnline] = useState(0)

  const fetchPresenti = useCallback((qs = '') => {
    fetch('/pages/api_map.php?op=presenti' + qs)
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          setUsers(data.users)
          setTotalOnline(data.total_online)
        }
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

  const openMsg = (nome) => window.open(
    `/pages/mex_privati/multi_message.php?destinatari=${encodeURIComponent(nome)}`,
    'titolo',
    'width=650,height=600,resizable,status,scrollbars=1,location'
  )

  return (
    <div className="iframe_online">
      <div className="contenitore_presenti">
        {users.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '80px 10px', color: '#666', fontStyle: 'italic' }}>
            Nessun utente online
          </div>
        ) : (
          users.map(user => (
            <div key={user.nome} className="presente">
              <a href="javascript:;" onClick={() => openMsg(user.nome)}>
                <img src="/themes/crystal/imgs/race_presenti/Sms.png" alt="Messaggio" />
              </a>
              &nbsp;&nbsp;
              <img
                src={`/themes/crystal/imgs/race_presenti/${getRaceIcon(user.id_razza)}`}
                alt=""
              />
              &nbsp;&nbsp;
              <a
                href={`/main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`}
                target="_top"
                style={{ fontSize: '13px', fontFamily: 'DejaVu Serif', textTransform: 'capitalize', color: '#c0a49e', letterSpacing: '0px' }}
              >
                {user.nome}
              </a>
            </div>
          ))
        )}
      </div>
      <div className="link_presenti">
        <a href="/main.php?page=presenti_estesi" target="_top">
          <div className="page_title">
            <h2 className="presenti_title">
              {totalOnline} {totalOnline === 1 ? 'Utente Presente' : 'Utenti Presenti'}
            </h2>
          </div>
        </a>
      </div>
    </div>
  )
}
