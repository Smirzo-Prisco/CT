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
 * incorporato direttamente nel pannello sinistro (non piu' in un popover) e
 * mostrato sempre, in stanza o sulla mappa generale — il nome/descrizione
 * del luogo restano invece nella modale (vedi showLocationDesc sotto).
 *
 * Il logo centrale apre un terzo arco (manuali/uffici/gestione/esci) al
 * hover o al click — vedi .ct-hud__center-arc.
 *
 * API riusate (invariate): api_map.php (current, presenti, ping, move,
 * changemap), api_global.php (getMessages, getOpenRoles, events_today,
 * getChatOff, getForumUnread, saveSoundPrefs), api_scheda.php (profile:
 * avatar, salute/integrita/livello/razza per il pannello del personaggio).
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
import OnboardingTour from './OnboardingTour'
import useHudBadges from '../hooks/useHudBadges'
import useWeatherEffect from '../hooks/useWeatherEffect'

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

// Su mobile .ct-hud__ring ha un transform:scale() (per rimpicciolire il
// cerchio a riposo) — un transform su un antenato crea un nuovo containing
// block per i figli position:fixed, che quindi si ancorano al box (minuscolo,
// scalato) dell'anello invece che al viewport: la scheda a comparsa dal
// basso finiva cosi' schiacciata vicino al cerchio invece di coprire tutto
// lo schermo. .ct-hud__center non ha questo problema (nessun transform sul
// suo contenitore), per questo il menu centrale ha sempre funzionato mentre
// quelli laterali no. Fix: portal su document.body, fuori dal contenitore
// trasformato, quando aperto su mobile (stessa tecnica di showLocationDesc).
function HudArcSheet({ isMobile, isOpen, onClose, children }) {
    if (!isMobile) {
        return (
            <>
                <div className="ct-hud__sheet-backdrop" onClick={onClose} />
                {children}
            </>
        )
    }
    if (!isOpen) return null
    return createPortal(
        <>
            <div className="ct-hud__sheet-backdrop ct-hud__sheet-backdrop--standalone" onClick={onClose} />
            {children}
        </>,
        document.body
    )
}

export default function Hud({ isStaff }) {

    const nome = window.CT_USER?.login ?? ''

    const hudRef = useRef(null)

    // Sotto i 680px gli anelli diventano schede a comparsa (vedi _hud.scss):
    // stessa soglia qui, per far si' che cliccare i CERCHI stessi (non solo
    // il logo centrale) apra/chiuda la propria scheda in modo indipendente
    // — su desktop/tablet i cerchi restano invece dedicati a modale luogo/
    // scheda personaggio (vedi thumb onClick sotto), col ventaglio radiale
    // normale (non la scheda a griglia, le cui regole CSS esistono solo
    // sotto questa soglia).
    const isNarrowQuery = () => window.matchMedia('(max-width: 680px)').matches
    const [isNarrow, setIsNarrow] = useState(isNarrowQuery)
    useEffect(() => {
        const mq = window.matchMedia('(max-width: 680px)')
        const onChange = () => setIsNarrow(isNarrowQuery())
        mq.addEventListener('change', onChange)
        return () => mq.removeEventListener('change', onChange)
    }, [])

    // Dispositivi senza hover reale (touch), a prescindere dalla larghezza:
    // un tablet e' spesso piu' largo di 680px (resta quindi il ventaglio
    // radiale normale, isNarrow false) ma senza mouse il logo centrale — che
    // su desktop apre il proprio arco SOLO al passaggio del mouse, vedi
    // onMouseEnter/Leave piu' sotto — sarebbe irraggiungibile. Usato SOLO li:
    // a differenza di un primo tentativo, non deve toccare il layout degli
    // anelli laterali (accordion/portal/griglia), pena rompere il
    // posizionamento delle icone su tablet (le regole CSS di quel layout
    // esistono solo sotto i 680px). Vedi conversazione di progetto del
    // 2026-07-31.
    const isTouchQuery = () => window.matchMedia('(hover: none), (pointer: coarse)').matches
    const [isTouch, setIsTouch] = useState(isTouchQuery)
    useEffect(() => {
        const mq = window.matchMedia('(hover: none), (pointer: coarse)')
        const onChange = () => setIsTouch(isTouchQuery())
        mq.addEventListener('change', onChange)
        return () => mq.removeEventListener('change', onChange)
    }, [])

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
    const [stats, setStats] = useState({ salute: null, salute_max: null, integrita: null, integrita_max: null, livello: null, gilda: null, gruppoImg: null, gruppoNome: null })

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
                    // Stessa icona/stessa priorita' della lista estesa dei presenti
                    // (PresentiEstesi.jsx/api_map.php, campo gruppo_img): ruolo
                    // nella gilda di norma, inclinazione se il pg ne ha una.
                    gruppoImg: d.gruppo_img,
                    gruppoNome: d.gruppo_nome,
                })
            })
            .catch(err => console.error('[Hud] Errore profilo:', err))
    }, [nome])

    useEffect(() => {
        fetchProfile()
        window.addEventListener('ct:location-changed', fetchProfile)
        return () => window.removeEventListener('ct:location-changed', fetchProfile)
    }, [fetchProfile])

    // ── Badge: messaggi privati, chat off, forum, giocate, eventi ───────────
    const { hasNewMessages, hasNewChatOff, hasNewForum, hasOpenRoles, hasEvents, clearChatOff } = useHudBadges()

    // ── Presenti nel luogo attuale: badge conteggio + lista nel popover ─────
    // Un'unica fetch condivisa (props a OnlineUsers) invece delle due fetch
    // indipendenti di prima verso lo stesso op=presenti — una per il badge
    // qui, una per la lista dentro OnlineUsers.jsx quando il popover si apre.
    const [presentiUsers, setPresentiUsers] = useState([])
    const [presentiIsStaff, setPresentiIsStaff] = useState(false)
    const [presentiTotal, setPresentiTotal] = useState(0) // online sul sito intero, non solo qui — vedi badge accanto a "Vedi tutti"
    const presentiIdleRef = useRef(false)

    const fetchPresenti = useCallback(() => {
        fetch('/pages/api_map.php?op=presenti')
            .then(r => r.json())
            .then(d => {
                if (!d.success) return
                setPresentiUsers(d.users)
                setPresentiIsStaff(!!d.is_staff)
                setPresentiTotal(d.total_online ?? 0)
            })
            .catch(err => console.error('[Hud] Errore presenti:', err))
    }, [])

    useEffect(() => {
        fetchPresenti()
        const sock = window.ctSocket
        // op=presenti ha un side-effect (resetta disponibile=1 nel DB): non
        // richiamarlo se il pg e' idle, altrimenti lo si marca per sbaglio
        // come attivo (stesso guard prima in OnlineUsers.jsx).
        const onUsersUpdate = () => { if (!presentiIdleRef.current) fetchPresenti() }
        if (sock) sock.on('users:update', onUsersUpdate)
        window.addEventListener('ct:location-changed', fetchPresenti)
        const onIdle = () => { presentiIdleRef.current = true }
        const onActive = () => { presentiIdleRef.current = false }
        window.addEventListener('ct:idle', onIdle)
        window.addEventListener('ct:active', onActive)
        return () => {
            if (sock) sock.off('users:update', onUsersUpdate)
            window.removeEventListener('ct:location-changed', fetchPresenti)
            window.removeEventListener('ct:idle', onIdle)
            window.removeEventListener('ct:active', onActive)
        }
    }, [fetchPresenti])

    const presentiCount = presentiUsers.length

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

    // ── Mobile: ogni cerchio apre/chiude la propria scheda in modo indipendente,
    // a discapito delle altre due (solo una alla volta, come un accordion) —
    // su desktop i tre cerchi restano dedicati a modale luogo/scheda/arco
    // rapido invece di aprire un menu proprio.
    const toggleMobileMenu = which => {
        setLeftOpen(which === 'left' && !leftOpen)
        setRightOpen(which === 'right' && !rightOpen)
        setCenterOpen(which === 'center' && !centerOpen)
    }

    // ── Icona chatbot: apre il widget flottante (gia' montato altrove) ──────
    const openChatbot = e => {
        e.preventDefault()
        window.dispatchEvent(new CustomEvent('ct:chatbot-open'))
        closeMobileMenus()
    }

    // ── Mobile: la scheda a comparsa resta aperta finche' non la si
    // richiude a mano — richiuderla anche alla scelta di una voce (link o
    // bottone) evita che resti in mezzo allo schermo dopo la navigazione o
    // l'apertura di un popover/il widget assistente.
    const closeMobileMenus = () => {
        if (!isNarrow) return
        setLeftOpen(false)
        setRightOpen(false)
        setCenterOpen(false)
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
        // Aprire il popover monta <ChattingOff/>, che chiama subito
        // ?op=messages e cancella chat_letta lato server — riflettiamo lo
        // stesso "letto" anche sul badge dell'icona senza aspettare un
        // giro di socket (che qui non arriverebbe comunque, essendo
        // un'azione locale e non un nuovo messaggio da altri).
        if (name === 'chatoff') clearChatOff()
        // Su mobile l'arco/lo sfondo scuro dell'anello aperto sono in
        // portal su document.body con z-index molto alto (598, vedi
        // _hud.scss) per stare sopra la scheda a griglia — ma questo li
        // teneva sopra anche al popover (z-index piu' basso, scoped dentro
        // .ct-hud), intercettando il tap su "Vedi tutti"/i link di
        // ChattingOff invece di raggiungerli: il popover appariva ma non
        // si riusciva a interagirci. Chiudendo l'anello, il portal si
        // smonta e il popover resta l'unica cosa sopra.
        closeMobileMenus()
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

    // Immagine del luogo: in stanza usa mappa.immagine (imgs/locations/).
    // Sulla mappa generale usa sempre l'illustrazione giorno/notte vera e
    // propria (la stessa di MapClick.jsx) invece del campo mappa_click.immagine
    // (location.immagine_mappa): quel campo puo' non essere popolato/valido a
    // seconda della riga di mappa_click associata alla mappa corrente, e
    // un'immagine mancante mostrava il testo alt enorme al posto del cerchio.
    // ?v=mtime (CT_ASSET_VERSIONS, vedi header.inc.php) forza il refresh della
    // cache quando il file cambia — vedi stesso commento in MapClick.jsx.
    const locationImg = location?.tipo === 'stanza' && location?.immagine
        ? `themes/crystal/imgs/locations/${location.immagine}`
        : location?.tipo === 'mappa'
            ? (() => {
                const n = location.is_notte ? 'mappa_notte.png' : 'mappa_giorno.png'
                return `themes/crystal/imgs/maps/${n}?v=${window.CT_ASSET_VERSIONS?.[n] ?? ''}`
            })()
            : null

    useEffect(() => { setLocationImgFailed(false) }, [locationImg])

    // Giorno/notte segue location.is_notte, gia' usato sopra per scegliere
    // mappa_giorno/notte.png.
    const weatherFx = useWeatherEffect(location?.is_notte)

    return (
        <div className="ct-hud" ref={hudRef}>
            {/* --expanded quando entrambi gli anelli sono aperti dal cerchio
                centrale (unico modo in cui leftOpen/rightOpen sono veri
                insieme su desktop): la linea segue la miniatura tornata a
                piena dimensione. Solo la topbar si muove, non il contenuto. */}
            <div className={`ct-hud__topbar${leftOpen && rightOpen ? ' ct-hud__topbar--expanded' : ''}`} aria-hidden="true" />

            {/* ============ ANELLO SINISTRO: LUOGO ============ */}
            <div className={`ct-hud__ring ct-hud__ring--left${leftOpen ? ' is-open' : ''}`}>
                <button type="button" className="ct-hud__thumb" onClick={() => isNarrow ? toggleMobileMenu('left') : setShowLocationDesc(true)} title={location?.nome ?? 'Luogo'}>
                    {locationImg && !locationImgFailed
                        ? <img src={locationImg} alt={location?.nome ?? ''} onError={() => setLocationImgFailed(true)} />
                        : <i className="fa-solid fa-city" />
                    }
                    {locationImg && !locationImgFailed && weatherFx &&
                        <span className={`ct-hud__weather-fx ct-hud__weather-fx--${weatherFx}`} aria-hidden="true">
                            {/* Layer "medio" (terza banda) per pioggia/temporale/nuvoloso: un
                                elemento DOM vero invece di un terzo pseudo-elemento (solo
                                ::before/::after disponibili, gia' usati per gli altri due) —
                                per la pioggia serve a ruotare indipendentemente dalla
                                vignettatura di sfondo, per le nuvole a dare una terza banda
                                verticale (alta/centrale/bassa) indipendente dalle altre due. */}
                            {(weatherFx === 'rain' || weatherFx === 'storm' || weatherFx === 'cloud') &&
                                <span className="ct-hud__weather-fx-mid" />
                            }
                        </span>
                    }
                </button>

                {/* Sempre visibile (non fa parte del ventaglio): torna rapidamente
                    alla propria stanza da qualunque pagina della SPA (es. dal
                    forum) senza dover navigare a mano. Solo se si e' in una
                    stanza specifica — sulla mappa generale non c'e' una chat a
                    cui tornare (li' c'e' gia' l'icona Mappa nel ventaglio). */}
                {location?.tipo === 'stanza' && (
                    <div className="ct-hud__ring-badge">
                        <a className="ct-hud__ring-badge__btn" href={`main.php?dir=${location.luogo}`} title="Torna alla tua chat">
                            <i className="fa-solid fa-location-arrow" />
                        </a>
                    </div>
                )}

                <HudArcSheet isMobile={isNarrow} isOpen={leftOpen} onClose={() => setLeftOpen(false)}>
                    <div className="ct-hud__arc">
                        <a className="ct-hud__icon" style={arcIcon(0, 87)} data-tour="hud-forum"
                            href="main.php?page=forum" title="Forum" onClick={closeMobileMenus}>
                            <i className="fa-solid fa-list-ul" />
                            {hasNewForum && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(0, 87)} />}
                        </a>
                        <button type="button" className="ct-hud__icon" style={arcIcon(43, 75)} data-tour="hud-presenti"
                            title="Presenti" onClick={togglePopover('presence')}>
                            <i className="fa-solid fa-users" />
                            {presentiCount > 0 && <b className="ct-hud__pip" style={pipOffset(43, 75)}>{presentiCount}</b>}
                        </button>
                        <button type="button" className="ct-hud__icon" style={arcIcon(75, 43)} data-tour="hud-chatoff"
                            title="Chat off" onClick={togglePopover('chatoff')}>
                            <i className="fa-solid fa-comment-dots" />
                            {hasNewChatOff && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(75, 43)} />}
                        </button>
                        <a className="ct-hud__icon" style={arcIcon(87, 0)} data-tour="hud-mappa"
                            href={`main.php?page=mappaclick&map_id=${mappaId}`} title="Mappa" onClick={closeMobileMenus}>
                            <i className="fa-solid fa-map-location-dot" />
                        </a>
                    </div>
                </HudArcSheet>

                {/* Sempre il meteo, in stanza o sulla mappa generale — non piu'
                    condizionato al tipo di luogo (prima, entrando in una
                    stanza/chat, veniva sostituito da nome+descrizione). */}
                <div className="ct-hud__panel ct-hud__panel--location">
                    <Meteo />
                </div>
            </div>

            {/* ============ ANELLO DESTRO: PERSONAGGIO ============ */}
            <div className={`ct-hud__ring ct-hud__ring--right${rightOpen ? ' is-open' : ''}`}>
                <button type="button" className="ct-hud__thumb" data-tour="hud-scheda" onClick={() => isNarrow ? toggleMobileMenu('right') : goToOwnScheda()} title={nome}>
                    {avatar ? <img src={avatar} alt={nome} /> : <i className="fa-solid fa-user" />}
                </button>

                <HudArcSheet isMobile={isNarrow} isOpen={rightOpen} onClose={() => setRightOpen(false)}>
                    <div className="ct-hud__arc">
                        {/* "La mia scheda" solo su schermi stretti: su desktop/
                            tablet il tap sul cerchio stesso ci porta gia'
                            direttamente (goToOwnScheda), ma sulla scheda a
                            griglia stretta il tap apre questo menu — senza
                            questa icona non ci sarebbe alcun modo di
                            raggiungere la propria scheda da li'. */}
                        {isNarrow && (
                            <a className="ct-hud__icon" style={arcIcon(0, 87)}
                                href={`main.php?page=scheda&pg=${encodeURIComponent(nome)}`} title="La mia scheda" onClick={closeMobileMenus}>
                                <i className="fa-solid fa-id-card" />
                            </a>
                        )}
                        <a className="ct-hud__icon" style={arcIcon(...(isNarrow ? [-33, 80] : [0, 87]))} data-tour="hud-calendario"
                            href="main.php?page=agenda_center" title="Calendario" onClick={closeMobileMenus}>
                            <i className="fa-solid fa-calendar-days" />
                            {hasEvents && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(...(isNarrow ? [-33, 80] : [0, 87]))} />}
                        </a>
                        <a className="ct-hud__icon" style={arcIcon(...(isNarrow ? [-62, 62] : [-43, 75]))} data-tour="hud-giocate"
                            href="main.php?page=role_recap" title="Giocate" onClick={closeMobileMenus}>
                            <i className="fa-solid fa-scroll" />
                            {hasOpenRoles && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(...(isNarrow ? [-62, 62] : [-43, 75]))} title="Giocata in corso" />}
                        </a>
                        <a className="ct-hud__icon" style={arcIcon(...(isNarrow ? [-80, 33] : [-75, 43]))} data-tour="hud-messaggi"
                            href="main.php?page=messages_center&offset=0" title="Messaggi" onClick={closeMobileMenus}>
                            <i className="fa-solid fa-envelope" />
                            {hasNewMessages && <b className="ct-hud__pip ct-hud__pip--dot" style={pipOffset(...(isNarrow ? [-80, 33] : [-75, 43]))} />}
                        </a>
                    </div>
                </HudArcSheet>

                <div className="ct-hud__panel ct-hud__panel--char">
                    <div className="ct-hud__char-row">
                        <span className="ct-hud__char-name">
                            {nome} {stats.gruppoImg
                                ? <img className="ct-hud__char-race-icon" src={stats.gruppoImg} alt={stats.gruppoNome ?? ''} />
                                : <i className="fa-solid fa-person" title="Senza razza" />
                            }
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

            {/* Modale descrizione luogo — portal su document.body per non
                restare intrappolata nello stacking context fixed dell'HUD. */}
            {showLocationDesc && location && createPortal(
                <div className="ct-hud__desc-overlay" onClick={() => setShowLocationDesc(false)}>
                    <div className="ct-hud__desc-modal" onClick={e => e.stopPropagation()}>
                        <div className="ct-hud__desc-modal-header">
                            <span>
                                <i className="fa-solid fa-location-dot" style={{ marginRight: 8 }} />
                                {location.nome}
                            </span>
                            <button onClick={() => setShowLocationDesc(false)} aria-label="Chiudi">×</button>
                        </div>
                        <div className="ct-hud__desc-modal-body">
                            {location.descrizione_immagine && (
                                <img
                                    src={`/themes/crystal/imgs/descrizioni/${location.descrizione_immagine}`}
                                    alt={location.nome}
                                    className="ct-hud__desc-modal-img"
                                />
                            )}
                            <div dangerouslySetInnerHTML={{ __html: location.descrizione }} />
                        </div>
                    </div>
                </div>,
                document.body
            )}

            {/* ============ CENTRO: LOGO + ARCO RAPIDO ============ */}
            {/* Hover solo se c'e' un vero mouse: su touch (telefono O tablet)
                deve aprirsi SOLO al click, altrimenti resterebbe
                irraggiungibile — alcuni browser touch simulano comunque un
                mouseenter al tap, che altrimenti aggirerebbe la mutua
                esclusivita' di toggleMobileMenu sotto i 680px. */}
            {/* --expanded segue leftOpen/rightOpen (non centerOpen, che e'
                solo l'hover del proprio ventaglio): stessa condizione della
                topbar, cosi' la linea dorata continua a tagliare a meta'
                anche il logo centrale quando i cerchi si aprono/chiudono. */}
            <div className={`ct-hud__center${centerOpen ? ' is-open' : ''}${leftOpen && rightOpen ? ' ct-hud__center--expanded' : ''}`}
                onMouseEnter={() => !isTouch && setCenterOpen(true)}
                onMouseLeave={() => !isTouch && setCenterOpen(false)}>
                <button type="button" className="ct-hud__brand" title="Mostra il menu" onClick={() => {
                    if (isNarrow) { toggleMobileMenu('center'); return }
                    // Anelli laterali: si aprono/chiudono insieme al click sul
                    // logo (unico modo su desktop, dove i cerchi stessi restano
                    // dedicati a modale luogo/scheda). Il proprio ventaglio
                    // (manuali/uffici/...) resta invece un fatto di hover — ma
                    // su touch l'hover non esiste, quindi li' il click deve
                    // pilotare anche quello, altrimenti resterebbe
                    // irraggiungibile (vedi isTouch sopra).
                    const open = !(leftOpen && rightOpen)
                    setLeftOpen(open)
                    setRightOpen(open)
                    if (isTouch) setCenterOpen(open)
                }}>
                    <span className="ct-hud__brand-ring" />
                    <img className="ct-hud__brand-mark" src="/imgs/favicon.ico" alt="Crystal Tokyo" />
                </button>

                {/* Solo mobile (vedi _hud.scss): sfondo scuro dietro la scheda a
                    comparsa dal basso, tocca per chiuderla. */}
                <div className="ct-hud__sheet-backdrop" onClick={() => setCenterOpen(false)} />

                <div className="ct-hud__center-arc">
                    <a className="ct-hud__icon" style={arcIcon(-60, 0)} data-tour="hud-manuali"
                        href="main.php?page=servizi_gilde" title="Manuali" onClick={closeMobileMenus}>
                        <i className="fa-solid fa-book" />
                    </a>
                    <a className="ct-hud__icon" style={arcIcon(-30, 52)} data-tour="hud-uffici"
                        href="main.php?page=uffici" title="Uffici" onClick={closeMobileMenus}>
                        <i className="fa-solid fa-building-columns" />
                    </a>
                    <a className="ct-hud__icon" style={arcIcon(30, 52)} data-tour="hud-assistente"
                        href="#" title="Assistente" onClick={openChatbot}>
                        <i className="fa-solid fa-robot" />
                    </a>
                    {/* Riga superiore (ty=0) invece che in basso insieme a Uffici/Assistente:
                        li' le tre icone si sovrapponevano (40px di larghezza contro 30px di
                        passo). Qui c'e' spazio solo verso il logo, quindi resta stretta fra
                        il bordo del logo (58px, raggio 29) ed Esci, spostato fuori a sua
                        volta — margini verificati per non toccare ne' l'uno ne' l'altro. */}
                    {isStaff && (
                        <a className="ct-hud__icon" style={arcIcon(56, 0)}
                            href="main.php?page=gestione" title="Gestione" onClick={closeMobileMenus}>
                            <i className="fa-solid fa-screwdriver-wrench" />
                        </a>
                    )}
                    <a className="ct-hud__icon" style={arcIcon(100, 0)}
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
                        <a href="main.php?page=presenti_estesi">
                            <span className="ct-hud__popover-total">{presentiTotal}</span>
                            Vedi tutti <i className="fa-solid fa-arrow-right" />
                        </a>
                    </div>
                    <OnlineUsers users={presentiUsers} isStaff={presentiIsStaff} />
                </div>
            )}

            {openPopover === 'chatoff' && (
                <div className="ct-hud__popover ct-hud__popover--chatoff" onClick={handlePopoverClick}>
                    <ChattingOff />
                </div>
            )}

            {/* Tour guidato (Crystal Bot): evidenzia le icone qui sopra, quindi
                deve vivere dentro Hud.jsx (unico posto dove esistono davvero
                nel DOM) — riceve l'apertura degli anelli come prop invece di
                un'API globale, essendone gia' un figlio diretto. */}
            <OnboardingTour onOpenRing={side => {
                if (side === 'left') setLeftOpen(true)
                else if (side === 'right') setRightOpen(true)
                else if (side === 'center') setCenterOpen(true)
            }} />
        </div>
    )
}
