/**
 * FrameMessaggi.jsx
 *
 * Griglia icone di navigazione nella colonna destra.
 *
 * Contenuto:
 *   - Griglia 3×N di icone di navigazione (mappa, aggiorna, messaggi, forum, ecc.)
 *   - Notifica messaggi privati: icona animata se ci sono messaggi non letti
 *   - Audio: suono sms.wav al primo arrivo di un nuovo messaggio (throttle 10 min)
 *     Rispetta la preferenza window.CT_USER.soundPrefs.dm
 *
 * Aggiornamento real-time:
 *   - 'dm:update' via socket → aggiorna lo stato icona messaggi privati
 *
 * API:
 *   GET pages/api_global.php?op=getMessages → stato messaggi non letti
 *   GET pages/api_global.php?op=events_today → se ci sono eventi oggi
 *
 * Montaggio: via ct:ready su #frame-messaggi-container in left-right_frames.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'

// ---------------------------------------------------------------------------
// GRIGLIA ICONE
// ---------------------------------------------------------------------------

function buildIcons(hasEvents, hasOpenRoles) {
    const mappa = window.CT_USER?.mappa ?? 1

    const calImg  = hasEvents    ? 'icon_news_night.gif' : 'icon_news.png'
    const roleImg = hasOpenRoles ? 'icon_doc_night.gif'  : 'icon_doc.png'

    return [
        { id: 'mappa', href: `main.php?page=mappaclick&map_id=${mappa}`, img: 'icon_mappa.png', alt: 'Mappa' },
        { id: 'famiglie', href: 'main.php?page=servizi_gilde', img: 'icon_fam.png', alt: 'Manuali' },
        { id: 'messaggi', href: 'main.php?page=messages_center&offset=0', img: null, alt: 'Messaggi' },
        { id: 'forum', href: 'main.php?page=forum', img: 'icon_forum.png', alt: 'Forum' },
        { id: 'uffici', href: 'main.php?page=uffici', img: 'icon_uff.png', alt: 'Uffici' },
        { id: 'giocate', href: 'main.php?page=role_recap', img: roleImg, alt: 'Giocate' },
        { id: 'calendario', href: 'main.php?page=agenda_center', img: calImg, alt: 'Calendario' },
        { id: 'gestione', href: 'main.php?page=gestione', img: 'icon_strumenti.png', alt: 'Gestione' },
        { id: 'logout', href: null, img: 'icon_exit.png', alt: 'Esci' },
    ]
}

const ICO = '../themes/crystal/imgs/icone/'

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function FrameMessaggi() {

    const [hasNewMessages, setHasNewMessages] = useState(false)
    const [hasEvents, setHasEvents]           = useState(false)
    const [hasOpenRoles, setHasOpenRoles]     = useState(false)
    const loggingOut                          = useRef(false)

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

    useEffect(() => {
        fetch('/pages/api_global.php?op=events_today')
            .then(r => r.json())
            .then(d => { if (d.success) setHasEvents(d.has_events) })
            .catch(err => console.error('[FrameMessaggi] Errore eventi:', err))
    }, [])

    const fetchOpenRoles = useCallback(() => {
        fetch('/pages/api_global.php?op=getOpenRoles')
            .then(r => r.json())
            .then(d => { if (d.success) setHasOpenRoles(d.has_open_roles) })
            .catch(err => console.error('[FrameMessaggi] Errore open roles:', err))
    }, [])

    useEffect(() => {
        fetchOpenRoles()
        const sock = window.ctSocket
        if (sock) sock.on('role:update', fetchOpenRoles)
        return () => { if (sock) sock.off('role:update', fetchOpenRoles) }
    }, [fetchOpenRoles])

    const fetchMessages = useCallback(() => {
        fetch('/pages/api_global.php?op=getMessages')
            .then(r => r.json())
            .then(d => {
                if (!d.success) return
                setHasNewMessages(d.hasNew)

                if (d.hasNew && d.allowAudio && (window.CT_USER?.soundPrefs?.dm ?? 1)) {
                    const now = Date.now()
                    const last = parseInt(localStorage.getItem('last_audio_play') || '0', 10)
                    if (now - last > 600_000) {
                        new Audio('../sounds/sms.wav').play().catch(() => { })
                        localStorage.setItem('last_audio_play', String(now))
                    }
                }
            })
            .catch(err => console.error('[FrameMessaggi] Errore messaggi:', err))
    }, [])

    useEffect(() => {
        fetchMessages()
        const sock = window.ctSocket
        if (sock) sock.on('dm:update', fetchMessages)
        return () => { if (sock) sock.off('dm:update', fetchMessages) }
    }, [fetchMessages])

    const msgIcon = hasNewMessages
        ? `${ICO}icon_day_newmex.gif`
        : `${ICO}icona_base_mex.png`

    const ICONS = buildIcons(hasEvents, hasOpenRoles)

    return (
        <div id="gridPanel">
            <div className="grid">
                {ICONS.map(icon => (
                    <div key={icon.id} className="grid-item">
                        {icon.id === 'messaggi' ? (
                            <a id="message-link" className="icon-link" href={icon.href} title={icon.alt}>
                                <img src={msgIcon} alt={icon.alt} />
                            </a>
                        ) : icon.id === 'logout' ? (
                            <a className="icon-link" href="#" title={icon.alt}
                               onClick={handleLogout}>
                                <img src={`${ICO}${icon.img}`} alt={icon.alt} />
                            </a>
                        ) : (
                            <a className="icon-link" href={icon.href} title={icon.alt}>
                                <img src={`${ICO}${icon.img}`} alt={icon.alt} />
                            </a>
                        )}
                        <span className="icon-label">{icon.alt}</span>
                    </div>
                ))}
            </div>
        </div>
    )
}
