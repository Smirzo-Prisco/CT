/**
 * FrameMessaggi.jsx
 *
 * Griglia icone di navigazione nella colonna sinistra.
 *
 * Contenuto:
 *   - Griglia 4×3 di icone di navigazione (mappa, aggiorna, messaggi, forum, ecc.)
 *   - Notifica messaggi privati: icona animata se ci sono messaggi non letti
 *   - Notifica chat off: icona animata se ci sono messaggi chat off non letti
 *   - Audio: suono sms.wav al primo arrivo di un nuovo messaggio (throttle 10 min)
 *
 * Aggiornamento real-time:
 *   - 'dm:update' via socket → aggiorna lo stato icona messaggi privati
 *   - 'chatoff:update' via socket → aggiorna lo stato icona chat off
 *
 * API:
 *   GET pages/api_global.php?op=getMessages → stato messaggi non letti
 *   GET pages/api_global.php?op=getChatOff  → stato chat off non letti
 *
 * Montaggio: via ct:ready su #frame-messaggi-container in left-right_frames.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

// ---------------------------------------------------------------------------
// GRIGLIA ICONE — configurazione statica dei 9 link di navigazione
// ---------------------------------------------------------------------------

/**
 * Costruisce la configurazione delle 12 icone di navigazione.
 * Riga 1: mappa, aggiorna, messaggi
 * Riga 2: forum, uffici, giocate
 * Riga 3: famiglie, mestieri, calendario
 * Riga 4: chatoff, gestione, esci
 *
 * @param {boolean} hasEvents - true se ci sono eventi oggi (icona calendario animata)
 * @param {boolean} isNotte   - true se è notte (18–6)
 */
function buildIcons(hasEvents, isNotte) {
    const mappa = window.CT_USER?.mappa ?? 1
    const luogo = window.CT_USER?.luogo ?? -1

    const calImg = hasEvents
        ? (isNotte ? 'icon_news_night.gif' : 'icon_news_night.gif')
        : 'icon_news.png'

    return [
        { id: 'mappa', href: `main.php?page=mappaclick&map_id=${mappa}`, img: 'icon_mappa.png', alt: 'Mappa' },
        { id: 'aggiorna', href: `main.php?dir=${luogo}`, img: 'icon_aggiorna.png', alt: 'Aggiorna' },
        { id: 'messaggi', href: 'main.php?page=messages_center&offset=0', img: null, alt: 'Messaggi' }, // img gestita da stato
        { id: 'forum', href: 'main.php?page=forum', img: 'icon_forum.png', alt: 'Forum' },
        { id: 'uffici', href: 'main.php?page=uffici', img: 'icon_uff.png', alt: 'Uffici' },
        { id: 'giocate', href: 'main.php?page=role_recap', img: 'icon_doc.png', alt: 'Giocate' },
        { id: 'famiglie', href: 'main.php?page=servizi_gilde', img: 'icon_fam.png', alt: 'Famiglie' },
        { id: 'mestieri', href: 'main.php?page=servizi_mestieri', img: 'icon_job.png', alt: 'Mestieri' },
        { id: 'calendario', href: 'main.php?page=agenda_center', img: calImg, alt: 'Calendario' },
        { id: 'chatoff', href: null, img: null, alt: 'Chat Off', chatoff: true },
        { id: 'gestione', href: 'main.php?page=gestione', img: 'icon_strumenti.png', alt: 'Gestione' },
        { id: 'logout', href: 'logout.php', img: 'icon_exit.png', alt: 'Esci' },
    ]
}

/** Base path delle icone */
const ICO = '../themes/crystal/imgs/icone/'

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function FrameMessaggi() {

    /** true se ci sono messaggi privati non letti */
    const [hasNewMessages, setHasNewMessages] = useState(false)

    /** true se ci sono messaggi chat off non letti */
    const [hasNewChatOff, setHasNewChatOff] = useState(false)

    /** true se l'audio è abilitato nelle impostazioni del gioco */
    const [allowAudio, setAllowAudio] = useState(false)

    /** true se ci sono eventi/appuntamenti oggi */
    const [hasEvents, setHasEvents] = useState(false)

    /** true se è notte (18–6) */
    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    useEffect(() => {
        fetch('/pages/api_global.php?op=events_today')
            .then(r => r.json())
            .then(d => { if (d.success) setHasEvents(d.has_events) })
            .catch(err => console.error('[FrameMessaggi] Errore eventi:', err))
    }, [])

    // ---------------------------------------------------------------------------
    // FETCH STATO MESSAGGI
    // ---------------------------------------------------------------------------

    /**
     * Recupera lo stato dei messaggi privati non letti.
     * Gestisce anche l'audio: suona sms.wav alla prima notifica dopo 10 minuti.
     */
    const fetchMessages = useCallback(() => {
        fetch('/pages/api_global.php?op=getMessages')
            .then(r => r.json())
            .then(d => {
                if (!d.success) return
                setHasNewMessages(d.hasNew)
                setAllowAudio(d.allowAudio)

                // Riproduce il suono solo se ci sono messaggi nuovi,
                // l'audio è abilitato, e non è già stato riprodotto negli ultimi 10 minuti
                if (d.hasNew && d.allowAudio) {
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

    /**
     * Recupera lo stato della chat off (messaggi non letti).
     */
    const fetchChatOff = useCallback(() => {
        fetch('/pages/api_global.php?op=getChatOff')
            .then(r => r.json())
            .then(d => { if (d.success) setHasNewChatOff(d.hasNew) })
            .catch(err => console.error('[FrameMessaggi] Errore chatoff:', err))
    }, [])

    // ---------------------------------------------------------------------------
    // SOCKET — aggiornamento real-time notifiche
    // ---------------------------------------------------------------------------

    useEffect(() => {
        // Caricamento iniziale
        fetchMessages()
        fetchChatOff()

        const sock = window.ctSocket
        if (sock) {
            // Aggiorna icona messaggi privati ad ogni notifica DM
            sock.on('dm:update', fetchMessages)
            // Aggiorna icona chat off ad ogni notifica chatoff
            sock.on('chatoff:update', fetchChatOff)
        }

        return () => {
            if (sock) {
                sock.off('dm:update', fetchMessages)
                sock.off('chatoff:update', fetchChatOff)
            }
        }
    }, [fetchMessages, fetchChatOff])

    // ---------------------------------------------------------------------------
    // ICONE MESSAGGI E CHAT OFF
    // ---------------------------------------------------------------------------

    /**
     * Scegli l'immagine dell'icona messaggi in base alla presenza di nuovi messaggi.
     * Icona animata (.gif) se ci sono messaggi non letti.
     */
    const msgIcon = hasNewMessages
        ? `${ICO}icon_day_newmex.gif`
        : `${ICO}icona_base_mex.png`

    /**
     * Scegli l'immagine dell'icona chat off in base alla presenza di nuovi messaggi.
     * Icona animata (.gif) se ci sono messaggi chat off non letti.
     */
    const chatOffIcon = hasNewChatOff
        ? `${ICO}icon_chat_off_night.gif`
        : `${ICO}icon_chat_off.png`

    /** Configurazione icone con src dinamico per messaggi, chatoff e calendario */
    const ICONS = buildIcons(hasEvents, isNotte)

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    return (
        <>
            <div id="gridPanel" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', marginTop: '10px' }}>
                <div className="grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', gridAutoRows: '50px' }}>
                    {ICONS.map(icon => (
                        <div key={icon.id} className="grid-item" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                            {icon.chatoff ? (
                                /* Chat Off: scrolla al pannello in fondo alla colonna */
                                <a
                                    id="chatoff-link"
                                    href="javascript:;"
                                    onClick={() => document.getElementById('chattina-off-container')?.scrollIntoView({ behavior: 'smooth' })}
                                >
                                    <img src={chatOffIcon} alt={icon.alt} />
                                </a>
                            ) : icon.id === 'messaggi' ? (
                                /* Messaggi: icona dinamica (animata se nuovi messaggi) */
                                <a id="message-link" href={icon.href}>
                                    <img src={msgIcon} alt={icon.alt} title={icon.alt} />
                                </a>
                            ) : (
                                /* Icona standard */
                                <a href={icon.href} target={icon.id === 'logout' ? '_top' : undefined}>
                                    <img src={`${ICO}${icon.img}`} alt={icon.alt} />
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </>
    )
}
