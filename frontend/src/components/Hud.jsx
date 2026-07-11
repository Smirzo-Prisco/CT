/**
 * Hud.jsx
 *
 * HUD immersivo che sostituisce le colonne laterali (framecontentLeft/Right):
 * due cerchi agli angoli (luogo a sinistra, personaggio a destra) collegati
 * a una topbar comune, ciascuno con un arco di icone di navigazione reale.
 * Le icone restano sempre visibili e cliccabili — ad anello chiuso sono
 * solo più piccole (l'intero arco si ridimensiona con un transform:scale,
 * non sparisce).
 *
 * L'apertura/chiusura dei due anelli (arco + pannello info) e' comandata
 * SOLO dal logo centrale, non dai cerchi stessi: cliccare il cerchio
 * sinistro apre la modale descrizione del luogo (stessa di InfoLocation.jsx),
 * cliccare il cerchio destro va direttamente alla propria scheda.
 *
 * Sostituisce (montati singolarmente in precedenza): InfoLocation,
 * PresentiBadge, OnlineUsers, ChattingOff, AnteprimaScheda, FrameMessaggi,
 * Meteo. OnlineUsers/ChattingOff vengono riusati cosi' come sono dentro i
 * popover, invece di riscriverne la logica di fetch/socket. Meteo invece e'
 * incorporato direttamente nel pannello sinistro (non piu' in un popover):
 * sostituisce nome/descrizione quando si e' sulla mappa generale.
 *
 * Il logo centrale apre un terzo arco (manuali/uffici/gestione/esci) al
 * hover o al click — vedi .ct-hud__center-arc.
 *
 * API riusate (invariate): api_map.php (current, presenti, ping, move,
 * changemap), api_global.php (getMessages, getOpenRoles, events_today,
 * saveSoundPrefs), api_scheda.php (profile: avatar, salute/integrita/
 * livello/razza per il pannello del personaggio).
 *
 * Montaggio: via ct:ready su #hud-container in header.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import { createPortal } from 'react-dom'
import OnlineUsers from './OnlineUsers'
import ChattingOff from './ChattingOff'
import Meteo from './Meteo'

// Posiziona un'icona sull'arco: tx/ty sono le coordinate (px) rispetto al
// centro dell'anello/logo.
const arcIcon = (tx, ty) => ({ '--tx': `${tx}px`, '--ty': `${ty}px` })

// Sposta il badge di notifica verso l'esterno dell'arco, nella stessa
// direzione radiale dell'icona (tx/ty) invece che in un angolo fisso: cosi'
// non finisce mai sopra l'icona vicina, qualunque sia la sua posizione.
const pipOffset = (tx, ty, dist = 24) => {
    const mag = Math.hypot(tx, ty) || 1
    return { '--px': `${(tx / mag) * dist}px`, '--py': `${(ty / mag) * dist}px` }
}

export default function Hud({ isStaff }) {

    const nome = window.CT_USER?.login ?? ''
    const sesso = window.CT_USER?.sesso ?? 'm'

    const hudRef = useRef(null)

    // Palette scelta in Preferenze.jsx > Colori land (localStorage, solo
    // client) — riapplicata qui ad ogni pagina perche' Hud.jsx e' l'unico
    // componente sempre montato; senza, tornerebbe al default a ogni reload.
    useEffect(() => {
        const saved = localStorage.getItem('ct_hud_palette')
        if (saved) document.body.dataset.palette = saved
    }, [])

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

    const [openPopover, setOpenPopover] = useState(null) // 'presence' | 'chatoff' | null

    // Arco centrale (uffici/manuali/gestione/esci): si apre al hover o al
    // click sul logo — non persistito, e' solo un menu rapido. E' anche
    // l'unico punto che apre/chiude gli anelli laterali (i cerchi stessi non
    // si ingrandiscono piu' cliccandoli, vedi thumb onClick sotto).
    const [centerOpen, setCenterOpen] = useState(false)

    // Modale descrizione luogo (stessa di InfoLocation.jsx, mai rimossa da
    // _layout.scss) — aperta cliccando il cerchio sinistro.
    const [showLocationDesc, setShowLocationDesc] = useState(false)

    // Fallback se il file dell'immagine del cerchio sinistro non esiste
    // (il campo DB puo' puntare a un file mancante): senza, il browser
    // mostra il testo alt a piena dimensione dentro il cerchio.
    const [locationImgFailed, setLocationImgFailed] = useState(false)

    // Ripulisce un attributo di un tentativo precedente (restringeva
    // #maincontent quando un pannello era aperto): riduceva il contenuto
    // troppo drasticamente e restava "incollato" se leftOpen/rightOpen
    // arrivavano gia' a true da localStorage. Vedi _hud.scss per la
    // soluzione attuale (pannello/popover piu' vicini all'anello).
    useEffect(() => {
        delete document.body.dataset.hudLeftExpanded
        delete document.body.dataset.hudRightExpanded
    }, [])

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
    const [stats, setStats] = useState({ salute: null, salute_max: null, integrita: null, integrita_max: null, livello: null, gilda: null })

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
                    // La "razza" del personaggio ora e' la gilda (gilda.nome), non
                    // piu' la vecchia tabella razza.
                    gilda: d.nome_gilda,
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

    // ── Cerchio destro: apre direttamente la propria scheda ─────────────────
    const goToOwnScheda = useCallback(() => {
        const url = `main.php?page=scheda&pg=${encodeURIComponent(nome)}`
        if (window.CT?.navigate) window.CT.navigate(url)
        else window.top.location.href = url
    }, [nome])

    // ── Icona chatbot: apre il widget flottante (gia' montato altrove) ──────
    const openChatbot = e => {
        e.preventDefault()
        window.dispatchEvent(new CustomEvent('ct:chatbot-open'))
    }

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

    // Click su un link dentro un popover (es. un personaggio nella lista
    // presenti): il popover deve chiudersi, non solo lasciare che il link
    // faccia il suo (navigazione o SPA). Gli altri click dentro il popover
    // restano fermati qui (non devono richiuderlo tramite onDocClick).
    const handlePopoverClick = e => {
        e.stopPropagation()
        if (e.target.closest('a')) setOpenPopover(null)
    }

    const mappaId = window.CT_USER?.mappa ?? 1
    const descrizioneTesto = (location?.descrizione ?? '').replace(/<[^>]+>/g, '')

    // Immagine del luogo: in stanza usa mappa.immagine (imgs/locations/).
    // Sulla mappa generale usa sempre l'illustrazione giorno/notte vera e
    // propria (la stessa di MapClick.jsx) invece del campo mappa_click.immagine
    // (location.immagine_mappa): quel campo puo' non essere popolato/valido a
    // seconda della riga di mappa_click associata alla mappa corrente, e
    // un'immagine mancante mostrava il testo alt enorme al posto del cerchio.
    const locationImg = location?.tipo === 'stanza' && location?.immagine
        ? `themes/crystal/imgs/locations/${location.immagine}`
        : location?.tipo === 'mappa'
            ? `themes/crystal/imgs/maps/${location.is_notte ? 'mappa_notte.png' : 'mappa_giorno.png'}`
            : null

    useEffect(() => { setLocationImgFailed(false) }, [locationImg])

    return (
        <div className="ct-hud" ref={hudRef}>
            <div className="ct-hud__topbar" aria-hidden="true" />

            {/* ============ ANELLO SINISTRO: LUOGO ============ */}
            <div className={`ct-hud__ring ct-hud__ring--left${leftOpen ? ' is-open' : ''}`}>
                <button type="button" className="ct-hud__thumb" onClick={() => setShowLocationDesc(true)} title={location?.nome ?? 'Luogo'}>
                    {locationImg && !locationImgFailed
                        ? <img src={locationImg} alt={location?.nome ?? ''} onError={() => setLocationImgFailed(true)} />
                        : <i className="fa-solid fa-city" />
                    }
                </button>

                {/* Solo mobile (vedi _hud.scss): sfondo scuro dietro la scheda a
                    comparsa dal basso, tocca per chiuderla. */}
                <div className="ct-hud__sheet-backdrop" onClick={() => setLeftOpen(false)} />

                <div className="ct-hud__arc">
                    <a className="ct-hud__icon" style={arcIcon(0, 100)}
                        href="main.php?page=forum" title="Forum">
                        <i className="fa-solid fa-comments" />
                    </a>
                    <button type="button" className="ct-hud__icon" style={arcIcon(50, 87)}
                        title="Presenti nel luogo" onClick={togglePopover('presence')}>
                        <i className="fa-solid fa-users" />
                        {presentiCount > 0 && <b className="ct-hud__pip" style={pipOffset(50, 87)}>{presentiCount}</b>}
                    </button>
                    <button type="button" className="ct-hud__icon" style={arcIcon(87, 50)}
                        title="Chat off" onClick={togglePopover('chatoff')}>
                        <i className="fa-solid fa-comment-dots" />
                    </button>
                    <a className="ct-hud__icon" style={arcIcon(100, 0)}
                        href={`main.php?page=mappaclick&map_id=${mappaId}`} title="Vai alla mappa principale">
                        <i className="fa-solid fa-map-location-dot" />
                    </a>
                </div>

                <div className="ct-hud__panel ct-hud__panel--location">
                    {location?.tipo === 'mappa'
                        ? <Meteo />
                        : (
                            <>
                                <h3>{location?.nome ?? '…'}</h3>
                                {descrizioneTesto && <p>{descrizioneTesto.slice(0, 100)}{descrizioneTesto.length > 100 ? '…' : ''}</p>}
                            </>
                        )
                    }
                </div>
            </div>

            {/* ============ ANELLO DESTRO: PERSONAGGIO ============ */}
            <div className={`ct-hud__ring ct-hud__ring--right${rightOpen ? ' is-open' : ''}`}>
                <button type="button" className="ct-hud__thumb" onClick={goToOwnScheda} title={nome}>
                    {avatar ? <img src={avatar} alt={nome} /> : <i className="fa-solid fa-user" />}
                </button>

                {/* Solo mobile (vedi _hud.scss): sfondo scuro dietro la scheda a
                    comparsa dal basso, tocca per chiuderla. */}
                <div className="ct-hud__sheet-backdrop" onClick={() => setRightOpen(false)} />

                <div className="ct-hud__arc">
                    <a className="ct-hud__icon" style={arcIcon(0, 100)}
                        href="main.php?page=agenda_center" title="Calendario">
                        <i className="fa-solid fa-calendar-days" />
                        {hasEvents && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(0, 100)} />}
                    </a>
                    <a className="ct-hud__icon" style={arcIcon(-50, 87)}
                        href="main.php?page=role_recap" title="Giocate personali">
                        <i className="fa-solid fa-scroll" />
                        {hasOpenRoles && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(-50, 87)} title="Giocata in corso" />}
                    </a>
                    <a className="ct-hud__icon" style={arcIcon(-87, 50)}
                        href="main.php?page=messages_center&offset=0" title="Messaggi">
                        <i className="fa-solid fa-envelope" />
                        {hasNewMessages && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(-87, 50)} />}
                    </a>
                    <a className="ct-hud__icon" style={arcIcon(-100, 0)}
                        href="#" title="Assistente" onClick={openChatbot}>
                        <i className="fa-solid fa-robot" />
                    </a>
                </div>

                <div className="ct-hud__panel ct-hud__panel--char">
                    <div className="ct-hud__char-row">
                        <span className="ct-hud__char-name">
                            {nome} <i className={`fa-solid ${sesso === 'f' ? 'fa-venus' : 'fa-mars'}`} />
                        </span>
                        <span className="ct-hud__char-badge">{stats.gilda || 'Senza razza'}</span>
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

            {/* Modale descrizione luogo (stessa di InfoLocation.jsx) — portal su
                document.body per non restare intrappolata nello stacking
                context fixed dell'HUD. */}
            {showLocationDesc && location && createPortal(
                <div className="info-location-overlay" onClick={() => setShowLocationDesc(false)}>
                    <div className="info-location-modal" onClick={e => e.stopPropagation()}>
                        <div className="info-location-modal-header">
                            <span>
                                <i className="fa-solid fa-location-dot" style={{ marginRight: 8 }} />
                                {location.nome}
                            </span>
                            <button onClick={() => setShowLocationDesc(false)} aria-label="Chiudi">×</button>
                        </div>
                        <div className="info-location-modal-body">
                            {location.descrizione_immagine && (
                                <img
                                    src={`/themes/crystal/imgs/descrizioni/${location.descrizione_immagine}`}
                                    alt={location.nome}
                                    className="info-location-modal-img"
                                />
                            )}
                            <div dangerouslySetInnerHTML={{ __html: location.descrizione }} />
                        </div>
                    </div>
                </div>,
                document.body
            )}

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
                    <img className="ct-hud__brand-mark" src="/imgs/favicon.ico" alt="Crystal Tokyo" />
                </button>

                <div className="ct-hud__center-arc">
                    <a className="ct-hud__icon" style={arcIcon(-60, 0)}
                        href="main.php?page=servizi_gilde" title="Manuali">
                        <i className="fa-solid fa-book" />
                    </a>
                    {/* Senza Gestione (non-staff) Uffici prende la posizione centrale
                        del ventaglio, cosi' non resta uno spazio vuoto al suo posto. */}
                    <a className="ct-hud__icon" style={isStaff ? arcIcon(-30, 52) : arcIcon(0, 60)}
                        href="main.php?page=uffici" title="Uffici">
                        <i className="fa-solid fa-building-columns" />
                    </a>
                    {isStaff && (
                        <a className="ct-hud__icon" style={arcIcon(30, 52)}
                            href="main.php?page=gestione" title="Gestione">
                            <i className="fa-solid fa-screwdriver-wrench" />
                        </a>
                    )}
                    <a className="ct-hud__icon" style={arcIcon(60, 0)}
                        href="#" title="Esci" onClick={handleLogout}>
                        <i className="fa-solid fa-right-from-bracket" />
                    </a>
                </div>
            </div>

            {/* ============ POPOVER ============ */}
            {openPopover === 'presence' && (
                <div className="ct-hud__popover ct-hud__popover--presence" onClick={handlePopoverClick}>
                    <div className="ct-hud__popover-head">
                        <span>Presenti qui</span>
                        <a href="main.php?page=presenti_estesi">Vedi tutti <i className="fa-solid fa-arrow-right" /></a>
                    </div>
                    <OnlineUsers />
                </div>
            )}

            {openPopover === 'chatoff' && (
                <div className="ct-hud__popover ct-hud__popover--chatoff" onClick={handlePopoverClick}>
                    <ChattingOff />
                </div>
            )}
        </div>
    )
}
