/**
 * FrameMessaggi.jsx
 *
 * Griglia icone di navigazione nella colonna destra.
 *
 * Contenuto:
 *   - Griglia 3×N di icone di navigazione (mappa, aggiorna, messaggi, forum, ecc.)
 *   - Notifica messaggi privati: icona animata se ci sono messaggi non letti
 *   - Audio: suono sms.wav al primo arrivo di un nuovo messaggio (throttle 10 min)
 *   - Pannello preferenze suoni: toggle DM / chat / musica schede, persistito su DB
 *
 * Aggiornamento real-time:
 *   - 'dm:update' via socket → aggiorna lo stato icona messaggi privati
 *
 * API:
 *   GET  pages/api_global.php?op=getMessages       → stato messaggi non letti
 *   GET  pages/api_global.php?op=events_today      → se ci sono eventi oggi
 *   POST pages/api_global.php?op=saveSoundPrefs    → salva preferenze suoni
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
        { id: 'famiglie', href: 'main.php?page=servizi_gilde', img: 'icon_fam.png', alt: 'Info' },
        { id: 'messaggi', href: 'main.php?page=messages_center&offset=0', img: null, alt: 'Messaggi' },
        { id: 'forum', href: 'main.php?page=forum', img: 'icon_forum.png', alt: 'Forum' },
        { id: 'uffici', href: 'main.php?page=uffici', img: 'icon_uff.png', alt: 'Uffici' },
        { id: 'giocate', href: 'main.php?page=role_recap', img: roleImg, alt: 'Giocate' },
        { id: 'calendario', href: 'main.php?page=agenda_center', img: calImg, alt: 'Calendario' },
        { id: 'gestione', href: 'main.php?page=gestione', img: 'icon_strumenti.png', alt: 'Gestione' },
        { id: 'logout', href: 'logout.php', img: 'icon_exit.png', alt: 'Esci' },
    ]
}

const ICO = '../themes/crystal/imgs/icone/'

// ---------------------------------------------------------------------------
// PANNELLO PREFERENZE SUONI
// ---------------------------------------------------------------------------

function SoundPanel({ prefs, onToggle }) {
    return (
        <div className="sound-panel">
            <div className="sound-panel__title">Preferenze suoni</div>
            <label className="sound-panel__row">
                <span>Messaggi privati</span>
                <input type="checkbox" checked={!!prefs.dm}
                    onChange={() => onToggle('dm')} />
            </label>
            <label className="sound-panel__row">
                <span>Chat di gioco</span>
                <input type="checkbox" checked={!!prefs.chat}
                    onChange={() => onToggle('chat')} />
            </label>
            <label className="sound-panel__row">
                <span>Musica schede</span>
                <input type="checkbox" checked={!!prefs.scheda}
                    onChange={() => onToggle('scheda')} />
            </label>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function FrameMessaggi() {

    const [hasNewMessages, setHasNewMessages] = useState(false)
    const [hasEvents, setHasEvents]           = useState(false)
    const [hasOpenRoles, setHasOpenRoles]     = useState(false)
    const [showSoundPanel, setShowSoundPanel] = useState(false)
    const [soundPrefs, setSoundPrefs] = useState(() => ({
        dm:     window.CT_USER?.soundPrefs?.dm     ?? 1,
        chat:   window.CT_USER?.soundPrefs?.chat   ?? 1,
        scheda: window.CT_USER?.soundPrefs?.scheda ?? 1,
    }))

    const panelRef = useRef(null)

    // Chiude il pannello cliccando fuori
    useEffect(() => {
        if (!showSoundPanel) return
        function handleOutside(e) {
            if (panelRef.current && !panelRef.current.contains(e.target)) {
                setShowSoundPanel(false)
            }
        }
        document.addEventListener('mousedown', handleOutside)
        return () => document.removeEventListener('mousedown', handleOutside)
    }, [showSoundPanel])

    const handleToggle = useCallback((key) => {
        setSoundPrefs(prev => {
            const next = { ...prev, [key]: prev[key] ? 0 : 1 }
            // Aggiorna CT_USER in-place così gli altri componenti leggono il valore aggiornato
            if (window.CT_USER) window.CT_USER.soundPrefs = next
            // Notifica gli altri componenti React (es. Scheda) tramite evento DOM
            document.dispatchEvent(new CustomEvent('ct:soundprefs:update', { detail: next }))
            // Persiste su DB (fire-and-forget)
            fetch('/pages/api_global.php?op=saveSoundPrefs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(next),
            }).catch(() => {})
            return next
        })
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

    // Icona speaker: pieno se almeno un suono è attivo, barrato se tutti muti
    const anySoundOn = soundPrefs.dm || soundPrefs.chat || soundPrefs.scheda
    const speakerIcon = anySoundOn ? 'fa-volume-high' : 'fa-volume-xmark'

    return (
        <div id="gridPanel">
            <div className="grid">
                {ICONS.map(icon => (
                    <div key={icon.id} className="grid-item">
                        {icon.id === 'messaggi' ? (
                            <a id="message-link" className="icon-link" href={icon.href} title={icon.alt}>
                                <img src={msgIcon} alt={icon.alt} />
                            </a>
                        ) : (
                            <a className="icon-link" href={icon.href} title={icon.alt}
                               target={icon.id === 'logout' ? '_top' : undefined}>
                                <img src={`${ICO}${icon.img}`} alt={icon.alt} />
                            </a>
                        )}
                        <span className="icon-label">{icon.alt}</span>
                    </div>
                ))}

                {/* Icona toggle suoni */}
                <div key="suoni" className="grid-item" ref={panelRef}>
                    <button
                        className={`icon-link sound-toggle-btn${!anySoundOn ? ' sound-toggle-btn--muted' : ''}`}
                        title="Preferenze suoni"
                        onClick={() => setShowSoundPanel(v => !v)}
                    >
                        <i className={`fa-solid ${speakerIcon}`}></i>
                    </button>
                    <span className="icon-label">Suoni</span>
                    {showSoundPanel && (
                        <SoundPanel prefs={soundPrefs} onToggle={handleToggle} />
                    )}
                </div>
            </div>
        </div>
    )
}
