/**
 * MapClick.jsx
 *
 * Componente React per la mappa di gioco — rimpiazza pages/mappaclick.inc.php.
 *
 * Funzionalità:
 *   - Mostra l'immagine della mappa (giorno/notte in base all'ora)
 *   - Overlay di hotspot cliccabili posizionati in percentuale sull'immagine
 *     (coordinate cx/cy in pixel dell'immagine naturale, da api_map.php?op=zones)
 *   - Badge sul pin con il totale presenti nella zona (somma dei conteggi
 *     stanza), per capire a colpo d'occhio quali distretti sono "vivi"
 *   - Click su un hotspot → popup con stanze della zona + conteggio utenti online
 *   - Click su una stanza → navigazione via main.php?dir=X (target _top)
 *   - Aggiornamento real-time dei conteggi tramite socket: 'users:update'
 *     (scoped alla propria room 'loc:N') + 'presenti:update' (globale, stesso
 *     evento usato da PresentiEstesi.jsx — necessario perché da qui si vedono
 *     TUTTE le zone, non solo la propria stanza)
 *   - Chiusura popup cliccando fuori o premendo il tasto ×
 *
 * Dati delle zone:
 *   Zone (nome, descrizione, coordinate pin, stanze) arrivano da
 *   api_map.php?op=zones, che legge mappa_click (una riga per zona, con
 *   larghezza/altezza riusate come cx/cy del pin e descrizione in un campo
 *   dedicato) e mappa (le stanze, via mappa.id_mappa = mappa_click.id_click).
 *   Prima erano una costante statica hardcoded qui (ZONES) — rimossa perché
 *   duplicava una relazione che il DB aveva già, ed era andata fuori sync
 *   (stanze in zona sbagliata, filename immagine non più validi, stanze
 *   mancanti). L'unica eccezione è ROPPONGI_EXTRA_ROOM sotto: un link a una
 *   pagina di servizio, non una stanza `mappa`, quindi non recuperabile dal
 *   DB con la stessa query.
 *
 * Montaggio: via AppRouter su #ct-app-content (Phase 4)
 *
 * Cambio mappa SPA:
 *   Quando la URL contiene ?map_id=X diverso da window.CT_USER.mappa
 *   (navigazione SPA — PHP non ha potuto aggiornare il DB),
 *   viene chiamata api_map.php?op=changemap (POST) per aggiornare
 *   ultima_mappa e notificare i socket, replicando esattamente ciò
 *   che fa main.php lines 24-29 in caso di visita diretta.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef, useMemo } from 'react'
import MapChatSearchModal from './MapChatSearchModal'

// Unica voce non presente in mappa_click/mappa: un link diretto a una pagina
// di servizio (non una stanza/chat), aggiunto lato client alla zona Roppongi
// dopo il fetch di api_map.php?op=zones.
const ROPPONGI_EXTRA_ROOM = {
    id: 'prenotazioni',
    nome: 'Prenotazioni',
    img: 'hotel_inn.png',
    link: { type: 'page', value: 'servizi_prenotazioni_prova' },
    count: 0,
}

// Pin dedicati per zona (SVG, sfondo gia' trasparente) al posto del
// medaglione generico map-pin.jpg. Chiave = zone.nome da api_map.php?op=zones.
const ZONE_PIN_IMAGES = {
    Shibuya: 'pin-shibuya.svg',
    Odaiba: 'pin-odaiba.svg',
    Fuji: 'pin-fuji.svg',
    Ueno: 'pin-ueno.svg',
    Shinjuku: 'pin-shinjuku.svg',
    Chiyoda: 'pin-chiyoda.svg',
    Roppongi: 'pin-roppongi.svg',
    Asakusa: 'pin-asakusa.svg',
    Tsukiji: 'pin-tsukiji.svg',
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function MapClick() {

    /**
     * ID mappa effettivo: preferisce il valore da URL (?map_id=X) rispetto a
     * CT_USER.mappa perché in navigazione SPA la sessione PHP non è stata
     * aggiornata e il CT_USER iniettato nel footer potrebbe essere stale.
     */
    const effectiveMapId = useMemo(() => {
        const p = new URLSearchParams(window.location.search)
        return parseInt(p.get('map_id') || '') || window.CT_USER?.mappa || 1
    }, [])

    /** Info sulla posizione corrente (is_notte) */
    const [mapInfo, setMapInfo] = useState(null)

    /** Zone della mappa corrente (pin + stanze), da api_map.php?op=zones. */
    const [zones, setZones] = useState([])

    /** ID zona con popup aperto (mappa_click.id_click). null = nessun popup. */
    const [openZone, setOpenZone] = useState(null)

    /** Modale di ricerca chat (vedi MapChatSearchModal.jsx) */
    const [searchOpen, setSearchOpen] = useState(false)

    /** Ref all'immagine mappa per leggere le dimensioni naturali reali */
    const imgRef = useRef(null)

    // La mappa e' l'unica pagina che deve occupare il 100% di #maincontent
    // (le altre restano al 90%, vedi _layout.scss) — qui aggiunge/rimuove la
    // classe che attiva l'eccezione.
    useEffect(() => {
        const el = document.getElementById('maincontent')
        el?.classList.add('ct-hud-map-page')
        return () => el?.classList.remove('ct-hud-map-page')
    }, [])

    /**
     * Dimensioni naturali REALI dell'immagine (px), lette da img.naturalWidth/Height.
     * Necessarie per calcolare le posizioni percentuali degli hotspot.
     * Non si usa il DB (larghezza/altezza) perché su mappa_click quei campi
     * sono adesso le coordinate cx/cy dei pin, non le dimensioni dell'immagine.
     */
    const [naturalSize, setNaturalSize] = useState({ w: 0, h: 0 })

    // ---------------------------------------------------------------------------
    // FETCH DATI
    // ---------------------------------------------------------------------------

    /**
     * Recupera info mappa (is_notte) da op=current.
     */
    const fetchMapInfo = useCallback(() => {
        fetch('/pages/api_map.php?op=current')
            .then(r => r.json())
            .then(data => { if (data.success) setMapInfo(data) })
            .catch(console.error)
    }, [])

    /**
     * Recupera zone + stanze (con nome/immagine/link/conteggio online) della
     * mappa corrente. Usa effectiveMapId (URL → CT_USER.mappa) per essere
     * corretto anche in navigazione SPA dove CT_USER.mappa potrebbe essere
     * stale. Chiamato al mount e ad ogni evento socket 'users:update' (il
     * conteggio online e' l'unica parte che cambia in tempo reale).
     */
    const fetchZones = useCallback(() => {
        fetch(`/pages/api_map.php?op=zones&map_id=${effectiveMapId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return
                setZones(data.zones.map(z =>
                    z.nome === 'Roppongi' ? { ...z, rooms: [...z.rooms, ROPPONGI_EXTRA_ROOM] } : z
                ))
            })
            .catch(console.error)
    }, [effectiveMapId])

    // ---------------------------------------------------------------------------
    // DIMENSIONI IMMAGINE RENDERIZZATA
    // ---------------------------------------------------------------------------

    /**
     * Legge le dimensioni naturali dell'immagine una volta caricata.
     * Viene chiamata su onLoad dell'immagine: in quel momento naturalWidth e
     * naturalHeight sono i pixel reali del file, indipendentemente dalla
     * dimensione renderizzata o dai valori nel DB.
     */
    const onImageLoad = useCallback(() => {
        const img = imgRef.current
        if (img && img.naturalWidth > 0) {
            setNaturalSize({ w: img.naturalWidth, h: img.naturalHeight })
        }
    }, [])

    // ---------------------------------------------------------------------------
    // EFFETTI
    // ---------------------------------------------------------------------------

    useEffect(() => {
        // Caricamento iniziale
        fetchMapInfo()
        fetchZones()

        // 'users:update' e' scoped alla room 'loc:N' di chi sta guardando (qui
        // 'loc:-1', essendo sulla mappa): copre solo i propri spostamenti, non
        // quelli altrui in zone diverse. 'presenti:update' e' invece globale
        // (stessa room 'global' usata da PresentiEstesi.jsx) — emesso su ogni
        // login/logout/spostamento di chiunque nel gioco: e' quello che rende
        // i conteggi sui pin davvero realtime per chi guarda la mappa intera.
        const sock = window.ctSocket
        if (sock) {
            sock.on('users:update', fetchZones)
            sock.on('presenti:update', fetchZones)
        }

        return () => {
            if (sock) {
                sock.off('users:update', fetchZones)
                sock.off('presenti:update', fetchZones)
            }
        }
    }, [fetchMapInfo, fetchZones])

    /**
     * Cambio mappa in navigazione SPA.
     *
     * In visita diretta PHP (main.php?page=mappaclick&map_id=X), PHP aggiorna
     * ultima_mappa in DB e CT_USER.mappa viene iniettato già aggiornato dal footer.
     * In navigazione SPA, invece, PHP non gira: se l'URL ha map_id diverso da
     * CT_USER.mappa, chiama api_map.php?op=changemap per replicare l'aggiornamento.
     */
    useEffect(() => {
        // Chiama sempre op=changemap al mount: imposta ultimo_luogo=-1 nel DB e
        // notifica i socket. In SPA mode PHP non gira, quindi anche quando il
        // map_id non cambia (stesso map, pg che torna dalla stanza alla mappa)
        // il luogo deve diventare -1. Se luogo era già -1, il server lo gestisce
        // come operazione idempotente. In full-page-reload PHP ha già aggiornato
        // il DB, ma la seconda chiamata è comunque innocua.
        fetch('/pages/api_map.php?op=changemap', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ map_id: effectiveMapId }),
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    if (window.CT_USER) {
                        window.CT_USER.mappa = effectiveMapId
                        window.CT_USER.luogo = -1
                    }
                    window.ctSocket?.emit('room:change', { newLuogo: -1 })
                }
            })
            .catch(console.error)
    }, []) // eslint-disable-line react-hooks/exhaustive-deps

    // Chiude il popup cliccando fuori dalla mappa
    useEffect(() => {
        if (!openZone) return
        const handler = () => setOpenZone(null)
        document.addEventListener('click', handler)
        return () => document.removeEventListener('click', handler)
    }, [openZone])

    // ---------------------------------------------------------------------------
    // NAVIGAZIONE
    // ---------------------------------------------------------------------------

    /**
     * Naviga verso una stanza (dir=X), una pagina di servizio (page=X) o
     * un'altra mappa (map_id=X, stessa risoluzione url di 'gotomap' in
     * api_map.php).
     *
     * In SPA mode (window.CT.navigate disponibile), PHP non gira:
     * per le stanze con dir chiama prima op=move via AJAX per aggiornare
     * DB, sessione e socket (stesso pattern usato da op=changemap al mount).
     * In full-page-reload, main.php?dir=X gestisce tutto lato PHP.
     *
     * @param {Object} room - Oggetto stanza da api_map.php?op=zones (o ROPPONGI_EXTRA_ROOM)
     */
    const navigate = useCallback(async (room) => {
        const { type, value } = room.link

        if (type === 'dir') {
            const url = `main.php?dir=${value}`
            if (window.CT?.navigate) {
                try {
                    await fetch('/pages/api_map.php?op=move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ dir: value }),
                    })
                } catch { /* ignora errori di rete */ }
                window.CT.navigate(url)
            } else {
                window.top.location.href = url
            }
            return
        }

        const url = type === 'map'
            ? `main.php?page=mappaclick&map_id=${value}`
            : `main.php?page=${value}`
        if (window.CT?.navigate) window.CT.navigate(url)
        else window.top.location.href = url
    }, [])

    // ---------------------------------------------------------------------------
    // POSIZIONAMENTO HOTSPOT
    // ---------------------------------------------------------------------------

    /**
     * Calcola la posizione CSS di un hotspot come percentuale sull'immagine.
     * Usa le dimensioni naturali REALI dell'immagine (naturalWidth/naturalHeight)
     * lette al caricamento — NON i valori del DB che potrebbero essere i default
     * errati (500×330 invece delle dimensioni reali del file).
     *
     * Non renderizza nulla finché l'immagine non è caricata (naturalSize.w === 0)
     * per evitare posizionamenti errati con dimensioni placeholder.
     *
     * @param {number} cx - Coordinata X centro hotspot (px nell'immagine naturale)
     * @param {number} cy - Coordinata Y centro hotspot (px nell'immagine naturale)
     * @returns {Object|null} Stile CSS con left/top in %, o null se non ancora pronto
     */
    const hotspotStyle = (cx, cy) => {
        if (naturalSize.w === 0) return null   // immagine non ancora caricata
        return {
            position: 'absolute',
            left: `${(cx / naturalSize.w) * 100}%`,
            top: `${(cy / naturalSize.h) * 100}%`,
            transform: 'translate(-50%, -50%)',
            cursor: 'pointer',
            zIndex: 10,
        }
    }

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    // Finché mapInfo non è arrivato non si sa se è giorno o notte: renderizzare
    // subito l'immagine di giorno (default implicito di is_notte undefined) la
    // mostrerebbe per un istante anche quando in realtà è notte, con un flash
    // visibile al cambio immagine appena arriva la risposta.
    if (!mapInfo) return <center><p>Caricamento mappa…</p></center>

    /** Sceglie l'immagine giorno o notte in base all'ora.
     *  ?v=mtime (da CT_ASSET_VERSIONS, vedi header.inc.php) forza il refresh
     *  della cache del browser/nginx quando il file viene sostituito — senza,
     *  l'immagine restava vecchia finche' non si svuotava la cache a mano. */
    const mapImgName = mapInfo.is_notte ? 'mappa_notte.png' : 'mappa_giorno.png'
    const mapImg = `themes/crystal/imgs/maps/${mapImgName}?v=${window.CT_ASSET_VERSIONS?.[mapImgName] ?? ''}`

    /**
     * Zona attualmente selezionata (se c'è un openZone).
     * Usata sia per evidenziare il pallino che per il pannello sotto.
     */
    const selectedZone = openZone ? zones.find(z => z.id === openZone) : null

    return (
        <div>
            {/* Pulsante ricerca chat — apre MapChatSearchModal (ricerca per
                parola chiave con autocomplete tra tutte le chat di gioco).
                Nascosto quando il pannello zona e' aperto: entrambi vivono
                in basso a sinistra/tutta larghezza e finirebbero sovrapposti
                (vedi .map-zone-panel, z-index:50, stessa area). */}
            {!openZone && (
                <button
                    type="button"
                    className="map-search-trigger"
                    onClick={() => setSearchOpen(true)}
                    title="Cerca chat"
                >
                    <i className="fas fa-magnifying-glass"></i>
                </button>
            )}

            {searchOpen && <MapChatSearchModal onClose={() => setSearchOpen(false)} />}

            {/* ---------------------------------------------------------------- */}
            {/* MAPPA — immagine con pallini hotspot                              */}
            {/* Il popup NON è dentro questo container per evitare overflow/clip  */}
            {/* ---------------------------------------------------------------- */}
            <center>
                {/* width:100% (non maxWidth) — deve riempire tutta la larghezza
                    disponibile (.ct-hud-map-page porta .output al 100%), non
                    solo arrivare fino alla dimensione naturale dell'immagine
                    (inline-block+maxWidth la lasciava piccola su schermi larghi). */}
                <div style={{ position: 'relative', width: '100%' }}>
                    <img
                        ref={imgRef}
                        src={mapImg}
                        alt="Mappa di Crystal Tokyo"
                        style={{ width: '100%', height: 'auto', display: 'block' }}
                        onLoad={onImageLoad}
                    />

                    {/* Pin cliccabili — solo dopo il caricamento dell'immagine.
                        Marker discreto (anello bianco pulsante/fluttuante +
                        etichetta in hover): l'immagine ha gia' un proprio
                        pallino disegnato per ogni zona, qui si aggiunge solo
                        l'affordance interattiva sopra. --float-delay sfasa il
                        galleggiamento cosi' i pin non si muovono in sincrono. */}
                    {zones.map((zone, i) => {
                        const style = hotspotStyle(zone.cx, zone.cy)
                        if (!style) return null
                        // Pin custom (SVG, gia' trasparente): niente mix-blend-mode:screen,
                        // quel trucco serve solo al medaglione generico (map-pin.jpg) per
                        // "rimuovere" il suo sfondo nero pieno.
                        const customPin = ZONE_PIN_IMAGES[zone.nome]
                        const pinSrc = customPin
                            ? `themes/crystal/imgs/maps/${customPin}`
                            : `themes/crystal/imgs/maps/map-pin.jpg?v=${window.CT_ASSET_VERSIONS?.['map-pin.jpg'] ?? ''}`
                        // Somma dei presenti in tutte le stanze della zona (gia'
                        // calcolati da api_map.php?op=zones): nessuna zona vuota
                        // mostra il badge, per distinguere a colpo d'occhio i
                        // distretti "vivi" da quelli deserti (vedi obiettivo).
                        const zoneCount = zone.rooms.reduce((sum, r) => sum + (r.count || 0), 0)
                        return (
                            <div
                                key={zone.id}
                                style={{ ...style, '--float-delay': `${(i % 5) * 0.5}s` }}
                                className={`map-pin${openZone === zone.id ? ' map-pin--active' : ''}`}
                                onClick={e => { e.stopPropagation(); setOpenZone(openZone === zone.id ? null : zone.id) }}
                                title={zone.nome}
                            >
                                <img className={`map-pin__ring${customPin ? ' map-pin__ring--custom' : ''}`} src={pinSrc} alt="" />
                                {zoneCount > 0 && <span className="map-pin__badge">{zoneCount}</span>}
                                <span className="map-pin__label">{zone.nome}</span>
                            </div>
                        )
                    })}
                </div>
            </center>

            {/* ---------------------------------------------------------------- */}
            {/* PANNELLO ZONA — fisso in basso, sopra un blur (vedi _map_click.scss) */}
            {/* ---------------------------------------------------------------- */}
            {selectedZone && (
                <div className="map-zone-panel">
                    <div className="map-zone-panel__head">
                        <strong>{selectedZone.nome}</strong>
                        <button className="map-zone-panel__close" onClick={() => setOpenZone(null)} aria-label="Chiudi">×</button>
                    </div>
                    <p>{selectedZone.desc}</p>

                    <div className="map-zone-panel__rooms">
                        {selectedZone.rooms.map(room => (
                            <span key={room.id} className="map-zone-room" onClick={() => navigate(room)}>
                                <span className="map-zone-room__avatar">
                                    <span className="map-zone-room__avatar-img">
                                        <img src={`/themes/crystal/imgs/maps/${room.img}`} alt="" />
                                    </span>
                                    {room.count > 0 && <span className="map-zone-room__badge">{room.count}</span>}
                                </span>
                                <span className="map-zone-room__name">{room.nome}</span>
                            </span>
                        ))}
                    </div>
                </div>
            )}
        </div>
    )
}
