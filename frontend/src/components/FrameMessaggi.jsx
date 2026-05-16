/**
 * FrameMessaggi.jsx
 *
 * Box "messaggi e meteo" nella colonna sinistra — rimpiazza pages/frame_messaggi.inc.php
 * e il relativo includes/frame_messaggi.js.
 *
 * Contenuto:
 *   - Meteo: icone giorno/notte, temperature, vento con toggle "oggi/ieri"
 *   - Griglia 3×3 di icone di navigazione (mappa, aggiorna, messaggi, forum, ecc.)
 *   - Notifica messaggi privati: icona animata se ci sono messaggi non letti
 *   - Notifica chat off: icona animata se ci sono messaggi chat off non letti
 *   - Audio: suono sms.wav al primo arrivo di un nuovo messaggio (throttle 10 min)
 *
 * Aggiornamento real-time:
 *   - 'dm:update' via socket → aggiorna lo stato icona messaggi privati
 *   - 'chatoff:update' via socket → aggiorna lo stato icona chat off
 *
 * API:
 *   GET pages/api_global.php?op=meteo       → dati meteo (rigenera se scaduti)
 *   GET pages/api_global.php?op=getMessages → stato messaggi non letti
 *   GET pages/api_global.php?op=getChatOff  → stato chat off non letti
 *
 * Montaggio: via ct:ready su #frame-messaggi-container in frame_messaggi.inc.php
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
        { id: 'mappa',      href: `main.php?page=mappaclick&map_id=${mappa}`, img: 'icon_mappa.png',      alt: 'Mappa'      },
        { id: 'aggiorna',   href: `main.php?dir=${luogo}`,                    img: 'icon_aggiorna.png',   alt: 'Aggiorna'   },
        { id: 'messaggi',   href: 'main.php?page=messages_center&offset=0',   img: null,                  alt: 'Messaggi'   }, // img gestita da stato
        { id: 'forum',      href: 'main.php?page=forum',                      img: 'icon_forum.png',      alt: 'Forum'      },
        { id: 'uffici',     href: 'main.php?page=uffici',                     img: 'icon_uff.png',        alt: 'Uffici'     },
        { id: 'giocate',    href: 'main.php?page=role_recap',                 img: 'icon_doc.png',        alt: 'Giocate'    },
        { id: 'famiglie',   href: 'main.php?page=servizi_gilde',              img: 'icon_fam.png',        alt: 'Famiglie'   },
        { id: 'mestieri',   href: 'main.php?page=servizi_mestieri',           img: 'icon_job.png',        alt: 'Mestieri'   },
        { id: 'calendario', href: 'main.php?page=agenda_center',              img: calImg,                alt: 'Calendario' },
        { id: 'chatoff',    href: null,                                        img: null,                  alt: 'Chat Off',  chatoff: true },
        { id: 'gestione',   href: 'main.php?page=gestione',                   img: 'icon_strumenti.png',  alt: 'Gestione'   },
        { id: 'logout',     href: 'logout.php',                               img: 'icon_exit.png',       alt: 'Esci'       },
    ]
}

/** Base path delle icone */
const ICO = '../themes/crystal/imgs/icone/'

// ---------------------------------------------------------------------------
// COMPONENTE METEO — sezione meteo con toggle oggi/ieri
// ---------------------------------------------------------------------------

/**
 * Visualizza le condizioni meteo di un giorno (giorno + notte).
 *
 * @param {Object} props.data - Dati meteo del giorno (attuale o precedente)
 */
/**
 * Renderizza le due colonne giorno/notte del meteo.
 * NON aggiunge un wrapper: il div meteo_box è già nel componente padre.
 */
function MeteoBox({ data }) {
    if (!data) return null
    return (
        <>
            <div className="meteo_colonna_sx">
                <div className="meteo_img">
                    <img src={`../themes/crystal/imgs/meteo/${data.giorno_img}.png`} alt="Giorno" className="meteo_immagine" />
                </div>
                <div className="meteo_temp">Max: <span className="temp_max">{data.temp_max}°C</span></div>
                <div className="meteo_vento">Vento: <span>{data.vento_giorno}</span></div>
            </div>
            <div className="meteo_colonna_dx">
                <div className="meteo_img">
                    <img src={`../themes/crystal/imgs/meteo/${data.notte_img}.png`} alt="Notte" className="meteo_immagine" />
                </div>
                <div className="meteo_temp">Min: <span className="temp_min">{data.temp_min}°C</span></div>
                <div className="meteo_vento">Vento: <span>{data.vento_notte}</span></div>
            </div>
        </>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function FrameMessaggi() {

    /** Dati meteo: { attuale, precedente } */
    const [meteo,          setMeteo]          = useState(null)

    /** true = mostra meteo di ieri, false = oggi */
    const [showYesterday,  setShowYesterday]  = useState(false)

    /** true se ci sono messaggi privati non letti */
    const [hasNewMessages, setHasNewMessages] = useState(false)

    /** true se ci sono messaggi chat off non letti */
    const [hasNewChatOff,  setHasNewChatOff]  = useState(false)

    /** true se l'audio è abilitato nelle impostazioni del gioco */
    const [allowAudio,     setAllowAudio]     = useState(false)

    /** true se ci sono eventi/appuntamenti oggi */
    const [hasEvents,      setHasEvents]      = useState(false)

    /** true se è notte (18–6) */
    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    // ---------------------------------------------------------------------------
    // FETCH METEO
    // ---------------------------------------------------------------------------

    /**
     * Carica i dati meteo dall'API.
     * Il meteo cambia al massimo una volta al giorno (gestito server-side),
     * quindi basta caricarlo al mount senza aggiornamenti socket.
     */
    useEffect(() => {
        fetch('/pages/api_global.php?op=meteo')
            .then(r => r.json())
            .then(d => { if (d.success) setMeteo(d) })
            .catch(err => console.error('[FrameMessaggi] Errore meteo:', err))
    }, [])

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
                    const now  = Date.now()
                    const last = parseInt(localStorage.getItem('last_audio_play') || '0', 10)
                    if (now - last > 600_000) {
                        new Audio('../sounds/sms.wav').play().catch(() => {})
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
            sock.on('dm:update',      fetchMessages)
            // Aggiorna icona chat off ad ogni notifica chatoff
            sock.on('chatoff:update', fetchChatOff)
        }

        return () => {
            if (sock) {
                sock.off('dm:update',      fetchMessages)
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
            {/* ================================================================ */}
            {/* SEZIONE METEO                                                    */}
            {/* ================================================================ */}
            <div className="news">
                {/* Titolo meteo con toggle oggi/ieri */}
                <div id="meteo_titolo" className="meteo_titolo">
                    {showYesterday ? 'METEO DI IERI' : 'METEO OGGI'}
                </div>

                {/* Meteo attuale */}
                <div className={`meteo_box meteo_attuale${showYesterday ? ' ' : ' '}`}
                     style={{ display: showYesterday ? 'none' : 'flex' }}>
                    <MeteoBox data={meteo?.attuale} />
                </div>

                {/* Meteo precedente (ieri) */}
                <div className="meteo_box meteo_precedente"
                     style={{ display: showYesterday ? 'flex' : 'none' }}>
                    <MeteoBox data={meteo?.precedente} />
                </div>

                {/* Freccia toggle oggi/ieri */}
                <div className="meteo_freccia">
                    <img
                        src="../themes/crystal/imgs/forum/freccia_giu.png"
                        alt="Switch Meteo"
                        className="switch_meteo"
                        onClick={() => setShowYesterday(v => !v)}
                    />
                </div>
            </div>

            {/* ================================================================ */}
            {/* GRIGLIA ICONE 3×3                                                */}
            {/* I CSS del gridPanel erano inline nel vecchio PHP:                */}
            {/* li ristabiliamo come stili inline React.                         */}
            {/* ================================================================ */}
            <div id="gridPanel" style={{ display:'flex', justifyContent:'center', alignItems:'center', marginTop:'10px', marginLeft:'22px' }}>
                <div className="grid" style={{ display:'grid', gridTemplateColumns:'repeat(3, 1fr)', gap:'12px', gridAutoRows:'60px', padding:'10px' }}>
                    {ICONS.map(icon => (
                        <div key={icon.id} className="grid-item" style={{ display:'flex', justifyContent:'center', alignItems:'center' }}>
                            {icon.chatoff ? (
                                /* Chat Off: apre popup */
                                <a
                                    id="chatoff-link"
                                    href="javascript:;"
                                    onClick={() => window.open('../chattina_off.php', 'chat', 'width=650,height=600,resizable,status,scrollbars=1')}
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
