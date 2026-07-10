/**
 * Hud.jsx
 *
 * HUD immersivo che sostituisce le colonne laterali (framecontentLeft/Right):
 * due cerchi agli angoli (luogo a sinistra, personaggio a destra) collegati
 * a una topbar comune, ciascuno espandibile in un arco di icone di
 * navigazione reale. Le icone restano sempre visibili e cliccabili — ad
 * anello chiuso sono solo più piccole (l'intero arco si ridimensiona con un
 * transform:scale, non sparisce).
 *
 * Sostituisce (montati singolarmente in precedenza): InfoLocation,
 * PresentiBadge, OnlineUsers, ChattingOff, AnteprimaScheda, FrameMessaggi,
 * Meteo. OnlineUsers/ChattingOff/Meteo vengono riusati cosi' come sono
 * dentro i popover, invece di riscriverne la logica di fetch/socket.
 *
 * API riusate (invariate): api_map.php (current, presenti, ping, move,
 * changemap), api_global.php (getMessages, getOpenRoles, events_today,
 * saveSoundPrefs), api_scheda.php (profile fallback avatar).
 *
 * Montaggio: via ct:ready su #hud-container in header.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import OnlineUsers from './OnlineUsers'
import ChattingOff from './ChattingOff'
import Meteo from './Meteo'

export default function Hud({ isStaff }) {

    const nome = window.CT_USER?.login ?? ''
    const sesso = window.CT_USER?.sesso ?? 'm'
    const disponibile = window.CT_USER?.disponibile ?? 1

    const hudRef = useRef(null)

    // Stato aperto/chiuso degli anelli: persistito in localStorage cosi'
    // resta com'era anche dopo un reload di pagina (non solo tra un
    // ri-render e l'altro). Si chiude SOLO ri-cliccando l'anello stesso o
    // il logo centrale — mai cliccando fuori.
    const [leftOpen, setLeftOpenState] = useState(() => localStorage.getItem('ct_hud_left_open') === '1')
    const [rightOpen, setRightOpenState] = useState(() => localStorage.getItem('ct_hud_right_open') === '1')
    const setLeftOpen = useCallback(v => {
        setLeftOpenState(prev => {
            const next = typeof v === 'function' ? v(prev) : v
            localStorage.setItem('ct_hud_left_open', next ? '1' : '0')
            return next
        })
    }, [])
    const setRightOpen = useCallback(v => {
        setRightOpenState(prev => {
            const next = typeof v === 'function' ? v(prev) : v
            localStorage.setItem('ct_hud_right_open', next ? '1' : '0')
            return next
        })
    }, [])

    const [openPopover, setOpenPopover] = useState(null) // 'presence' | 'weather' | 'chatoff' | null

    // Arco centrale (uffici/manuali/gestione/esci): si apre al hover o al
    // click sul logo — non persistito, e' solo un menu rapido.
    const [centerOpen, setCenterOpen] = useState(false)

    // ── Luogo corrente (stesso pattern di InfoLocation.jsx) ────────────────
    const [location, setLocation] = useState(null)

    const fetchLocation = useCallback(() => {
        fetch('/pages/api_map.php?op=current')
            .then(r => r.json())
            .then(d => { if (d.success) setLocation(d) })
            .catch(err => console.error('[Hud] Errore luogo:', err))
    }, [])

    useEffect(() => {
        fetchLocation()
        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchLocation)
        window.addEventListener('ct:location-changed', fetchLocation)
        return () => {
            if (sock) sock.off('users:update', fetchLocation)
            window.removeEventListener('ct:location-changed', fetchLocation)
        }
    }, [fetchLocation])

    // ── Avatar + vitali personaggio (stesso pattern di AnteprimaScheda.jsx) ─
    const [avatar, setAvatar] = useState(() => (window.CT_USER?.url_img_chat ?? '').trim())
    const [stats, setStats] = useState({ salute: null, salute_max: null, integrita: null, integrita_max: null, livello: null })

    const fetchProfile = useCallback(() => {
        if (!nome) return
        fetch(`/pages/api_scheda.php?op=profile&pg=${encodeURIComponent(nome)}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return
                if (d.url_img_chat) setAvatar(prev => prev || d.url_img_chat.trim())
                setStats({
                    salute: d.salute, salute_max: d.salute_max,
                    integrita: d.integrita, integrita_max: d.integrita_max,
                    livello: d.statistiche?.livello ?? null,
                })
            })
            .catch(err => console.error('[Hud] Errore profilo:', err))
    }, [nome])

    useEffect(() => {
        fetchProfile()
        window.addEventListener('ct:location-changed', fetchProfile)
        return () => window.removeEventListener('ct:location-changed', fetchProfile)
    }, [fetchProfile])

    // ── Badge: messaggi privati (stesso pattern di FrameMessaggi.jsx) ──────
    const [hasNewMessages, setHasNewMessages] = useState(false)

    const fetchMessages = useCallback(() => {
        fetch('/pages/api_global.php?op=getMessages')
            .then(r => r.json())
            .then(d => { if (d.success) setHasNewMessages(d.hasNew) })
            .catch(err => console.error('[Hud] Errore messaggi:', err))
    }, [])

    useEffect(() => {
        fetchMessages()
        const sock = window.ctSocket
        if (sock) sock.on('dm:update', fetchMessages)
        return () => { if (sock) sock.off('dm:update', fetchMessages) }
    }, [fetchMessages])

    // ── Badge: giocate aperte ───────────────────────────────────────────────
    const [hasOpenRoles, setHasOpenRoles] = useState(false)

    const fetchOpenRoles = useCallback(() => {
        fetch('/pages/api_global.php?op=getOpenRoles')
            .then(r => r.json())
            .then(d => { if (d.success) setHasOpenRoles(d.has_open_roles) })
            .catch(err => console.error('[Hud] Errore giocate:', err))
    }, [])

    useEffect(() => {
        fetchOpenRoles()
        const sock = window.ctSocket
        if (sock) sock.on('role:update', fetchOpenRoles)
        return () => { if (sock) sock.off('role:update', fetchOpenRoles) }
    }, [fetchOpenRoles])

    // ── Badge: eventi calendario di oggi ────────────────────────────────────
    const [hasEvents, setHasEvents] = useState(false)

    useEffect(() => {
        fetch('/pages/api_global.php?op=events_today')
            .then(r => r.json())
            .then(d => { if (d.success) setHasEvents(d.has_events) })
            .catch(err => console.error('[Hud] Errore eventi:', err))
    }, [])

    // ── Badge: presenti nel luogo attuale ───────────────────────────────────
    const [presentiCount, setPresentiCount] = useState(0)

    const fetchPresentiCount = useCallback(() => {
        fetch('/pages/api_map.php?op=presenti')
            .then(r => r.json())
            .then(d => { if (d.success) setPresentiCount(d.users.length) })
            .catch(err => console.error('[Hud] Errore presenti:', err))
    }, [])

    useEffect(() => {
        fetchPresentiCount()
        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchPresentiCount)
        window.addEventListener('ct:location-changed', fetchPresentiCount)
        return () => {
            if (sock) sock.off('users:update', fetchPresentiCount)
            window.removeEventListener('ct:location-changed', fetchPresentiCount)
        }
    }, [fetchPresentiCount])

    // ── Logout (stesso pattern di FrameMessaggi.jsx) ────────────────────────
    const loggingOut = useRef(false)
    const handleLogout = useCallback(async e => {
        e.preventDefault()
        if (loggingOut.current) return
        loggingOut.current = true
        try {
            await fetch('/pages/api_auth.php?op=logout', { method: 'POST' })
        } finally {
            window.top.location.href = '/'
        }
    }, [])

    // ── Click fuori: richiude SOLO i popover, non gli anelli =====
    // Gli anelli si chiudono esclusivamente ri-cliccando se stessi o il logo
    // centrale (vedi toggleOwnRing/brandOrb onClick) — mai cliccando altrove.
    useEffect(() => {
        function onDocClick(e) {
            if (hudRef.current && !hudRef.current.contains(e.target)) {
                setOpenPopover(null)
            }
        }
        document.addEventListener('click', onDocClick)
        return () => document.removeEventListener('click', onDocClick)
    }, [])

    const togglePopover = (name) => (e) => {
        e.stopPropagation()
        setOpenPopover(p => (p === name ? null : name))
    }

    const mappaId = window.CT_USER?.mappa ?? 1
    const descrizioneTesto = (location?.descrizione ?? '').replace(/<[^>]+>/g, '')

    // Immagine del luogo: in stanza usa mappa.immagine (imgs/locations/), sulla
    // mappa generale usa l'immagine della zona (imgs/maps/) — stesso campo
    // che InfoLocation.jsx leggeva da api_map.php?op=current.
    const locationImg = location?.tipo === 'stanza' && location?.immagine
        ? `themes/crystal/imgs/locations/${location.immagine}`
        : location?.tipo === 'mappa' && location?.immagine_mappa
            ? `themes/crystal/imgs/maps/${location.immagine_mappa}`
            : null

    return (
        <div className="ct-hud" ref={hudRef}>
            <div className="ct-hud__topbar" aria-hidden="true" />

            {/* ============ ANELLO SINISTRO: LUOGO ============ */}
            <div className={`ct-hud__ring ct-hud__ring--left${leftOpen ? ' is-open' : ''}`}>
                <button type="button" className="ct-hud__thumb" onClick={() => setLeftOpen(v => !v)} title={location?.nome ?? 'Luogo'}>
                    {locationImg ? <img src={locationImg} alt={location?.nome ?? ''} /> : <i className="fa-solid fa-city" />}
                </button>

                <div className="ct-hud__arc">
                    <button type="button" className="ct-hud__icon" style={{ '--tx': '0px', '--ty': '122px' }}
                        title="Meteo" onClick={togglePopover('weather')}>
                        <i className="fa-solid fa-cloud-sun" />
                    </button>
                    <a className="ct-hud__icon" style={{ '--tx': '47px', '--ty': '113px' }}
                        href="main.php?page=forum" title="Forum">
                        <i className="fa-solid fa-comments" />
                    </a>
                    <button type="button" className="ct-hud__icon" style={{ '--tx': '86px', '--ty': '86px' }}
                        title="Presenti nel luogo" onClick={togglePopover('presence')}>
                        <i className="fa-solid fa-users" />
                        {presentiCount > 0 && <b className="ct-hud__pip">{presentiCount}</b>}
                    </button>
                    <button type="button" className="ct-hud__icon" style={{ '--tx': '113px', '--ty': '47px' }}
                        title="Chat off" onClick={togglePopover('chatoff')}>
                        <i className="fa-solid fa-comment-dots" />
                    </button>
                    <a className="ct-hud__icon" style={{ '--tx': '122px', '--ty': '0px' }}
                        href={`main.php?page=mappaclick&map_id=${mappaId}`} title="Vai alla mappa principale">
                        <i className="fa-solid fa-map-location-dot" />
                    </a>
                </div>

                <div className="ct-hud__panel ct-hud__panel--location">
                    <h3>{location?.nome ?? '…'}</h3>
                    {descrizioneTesto && <p>{descrizioneTesto.slice(0, 100)}{descrizioneTesto.length > 100 ? '…' : ''}</p>}
                </div>
            </div>

            {/* ============ ANELLO DESTRO: PERSONAGGIO ============ */}
            <div className={`ct-hud__ring ct-hud__ring--right${rightOpen ? ' is-open' : ''}`}>
                <button type="button" className="ct-hud__thumb" onClick={() => setRightOpen(v => !v)} title={nome}>
                    {avatar ? <img src={avatar} alt={nome} /> : <i className="fa-solid fa-user" />}
                </button>

                <div className="ct-hud__arc">
                    <a className="ct-hud__icon" style={{ '--tx': '0px', '--ty': '122px' }}
                        href="main.php?page=agenda_center" title="Calendario">
                        <i className="fa-solid fa-calendar-days" />
                        {hasEvents && <b className="ct-hud__pip ct-hud__pip--dot" />}
                    </a>
                    <a className="ct-hud__icon" style={{ '--tx': '-61px', '--ty': '106px' }}
                        href="main.php?page=role_recap" title="Giocate personali">
                        <i className="fa-solid fa-scroll" />
                        {hasOpenRoles && <b className="ct-hud__pip ct-hud__pip--dot" title="Giocata in corso" />}
                    </a>
                    <a className="ct-hud__icon" style={{ '--tx': '-106px', '--ty': '61px' }}
                        href="main.php?page=messages_center&offset=0" title="Messaggi">
                        <i className="fa-solid fa-envelope" />
                        {hasNewMessages && <b className="ct-hud__pip ct-hud__pip--dot" />}
                    </a>
                    <a className="ct-hud__icon" style={{ '--tx': '-122px', '--ty': '0px' }}
                        href={`main.php?page=scheda&pg=${encodeURIComponent(nome)}`} title="Vai alla scheda">
                        <i className="fa-solid fa-id-card" />
                    </a>
                </div>

                <div className="ct-hud__panel ct-hud__panel--char">
                    <div className="ct-hud__char-row">
                        <span className="ct-hud__char-name">
                            {nome} <i className={`fa-solid ${sesso === 'f' ? 'fa-venus' : 'fa-mars'}`} />
                        </span>
                        <span className={`ct-hud__char-badge${disponibile ? ' is-available' : ''}`}>
                            {disponibile ? 'Disponibile' : 'Occupato'}
                        </span>
                    </div>
                    <div className="ct-hud__vitals">
                        <span className="ct-hud__vital ct-hud__vital--hp" title="Salute">
                            <i className="fa-solid fa-heart" /> {stats.salute ?? '-'}/{stats.salute_max ?? '-'}
                        </span>
                        <span className="ct-hud__vital ct-hud__vital--integrity" title="Integrità">
                            <i className="fa-solid fa-shield-halved" /> {stats.integrita ?? '-'}/{stats.integrita_max ?? '-'}
                        </span>
                        <span className="ct-hud__vital ct-hud__vital--level" title="Livello">
                            <i className="fa-solid fa-star" /> Lv. {stats.livello ?? '-'}
                        </span>
                    </div>
                </div>
            </div>

            {/* ============ CENTRO: LOGO + ARCO RAPIDO ============ */}
            <div className={`ct-hud__center${centerOpen ? ' is-open' : ''}`}
                onMouseEnter={() => setCenterOpen(true)} onMouseLeave={() => setCenterOpen(false)}>
                <button type="button" className="ct-hud__brand" title="Mostra il menu" onClick={() => {
                    const open = !(leftOpen && rightOpen)
                    setLeftOpen(open)
                    setRightOpen(open)
                    setCenterOpen(v => !v)
                }}>
                    <span className="ct-hud__brand-ring" />
                    <span className="ct-hud__brand-mark">CT</span>
                </button>

                <div className="ct-hud__center-arc">
                    <a className="ct-hud__icon" style={{ '--tx': '-100px', '--ty': '45px' }}
                        href="main.php?page=servizi_gilde" title="Manuali">
                        <i className="fa-solid fa-book" />
                    </a>
                    <a className="ct-hud__icon" style={{ '--tx': '-35px', '--ty': '85px' }}
                        href="main.php?page=uffici" title="Uffici">
                        <i className="fa-solid fa-building-columns" />
                    </a>
                    {isStaff && (
                        <a className="ct-hud__icon" style={{ '--tx': '35px', '--ty': '85px' }}
                            href="main.php?page=gestione" title="Gestione">
                            <i className="fa-solid fa-screwdriver-wrench" />
                        </a>
                    )}
                    <a className="ct-hud__icon" style={{ '--tx': '100px', '--ty': '45px' }}
                        href="#" title="Esci" onClick={handleLogout}>
                        <i className="fa-solid fa-right-from-bracket" />
                    </a>
                </div>
            </div>

            {/* ============ POPOVER ============ */}
            {openPopover === 'presence' && (
                <div className="ct-hud__popover ct-hud__popover--presence" onClick={e => e.stopPropagation()}>
                    <div className="ct-hud__popover-head">
                        <span>Presenti qui</span>
                        <a href="main.php?page=presenti_estesi">Vedi tutti <i className="fa-solid fa-arrow-right" /></a>
                    </div>
                    <OnlineUsers />
                </div>
            )}

            {openPopover === 'weather' && (
                <div className="ct-hud__popover ct-hud__popover--weather" onClick={e => e.stopPropagation()}>
                    <Meteo />
                </div>
            )}

            {openPopover === 'chatoff' && (
                <div className="ct-hud__popover ct-hud__popover--chatoff" onClick={e => e.stopPropagation()}>
                    <ChattingOff />
                </div>
            )}
        </div>
    )
}
