/**
 * MapClick.jsx
 *
 * Componente React per la mappa di gioco — rimpiazza pages/mappaclick.inc.php.
 *
 * Funzionalità:
 *   - Mostra l'immagine della mappa (giorno/notte in base all'ora)
 *   - Overlay di 9 hotspot cliccabili posizionati in percentuale sull'immagine
 *     (le coordinate derivano dai valori <area> del vecchio PHP, scalate alle
 *     dimensioni naturali dell'immagine restituite da api_map.php?op=current)
 *   - Click su un hotspot → popup con stanze della zona + conteggio utenti online
 *   - Click su una stanza → navigazione via main.php?dir=X (target _top)
 *   - Aggiornamento real-time del conteggio utenti tramite socket 'users:update'
 *   - Chiusura popup cliccando fuori o premendo il tasto ×
 *
 * Dati delle zone:
 *   Le 9 zone (nome, descrizione, stanze) sono hardcoded come costante ZONES,
 *   equivalente ai 9 div.menu_mappa del vecchio PHP. Le dimensioni naturali
 *   dell'immagine vengono lette dall'API e usate per scalare i hotspot.
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

// ---------------------------------------------------------------------------
// DATI STATICI DELLE ZONE
// Equivalente ai 9 div.menu_mappa del vecchio PHP.
//
// cx, cy = coordinate del centro dell'<area> nell'immagine naturale
//          (calcolate come media di x1,x2 e y1,y2 dei vecchi <area> coords)
// rooms  = stanze nella zona; link può essere 'dir' (ID stanza) o 'page' (pagina)
// nightOnly = stanza visibile solo di notte (come in Chiyoda nel vecchio PHP)
// ---------------------------------------------------------------------------
const ZONES = [
    {
        id: 'menu1',
        name: 'Odaiba',
        cx: 706, cy: 390,
        desc: 'Odaiba (お台場) è una grande isola artificiale collocata a Est della città. Meta preferita di molti turisti, è nota soprattutto per il famoso lungomare.',
        rooms: [
            { dir: 50, img: 'faro.png' },
            { dir: 2, img: 'porto.png' },
            { dir: 3, img: 'ponte.png' },
            { dir: 22, img: 'spiaggia.png' },
            { dir: 32, img: 'regno_di_caos.png' },
        ],
    },
    {
        id: 'menu2',
        name: 'Monte Fuji',
        cx: 462, cy: 141,
        desc: 'Il Monte Fuji (富士山 Fuji-san) è il vulcano più alto di tutto il Giappone. Luogo dalla bellezza paesaggistica straordinaria, è considerato uno dei luoghi sacri più importanti.',
        rooms: [
            { dir: 9, img: 'bosco.png' },
            { dir: 28, img: 'terme.png' },
            { dir: 11, img: 'stella_prima.png' },
            { dir: 428, img: 'nikigori.png' },
            { dir: 10, img: 'altri_luoghi.png' },
        ],
    },
    {
        id: 'menu3',
        name: 'Ueno',
        cx: 446, cy: 289,
        desc: 'Ueno (上野) è il quartiere in cui risiedono i più importanti musei e parchi di tutta la città. Densamente popolato soprattutto durante la fioritura dei sakura.',
        rooms: [
            { dir: 43, img: 'giardini_dei_fiori_del_male.png' },
            { dir: 16, img: 'luna_park.png' },
            { dir: 4, img: 'parco_di_ueno.png' },
            { dir: 12, img: 'periferia_nord.png' },
            { dir: 7, img: 'zoo.png' },
        ],
    },
    {
        id: 'menu4',
        name: 'Shinjuku',
        cx: 47, cy: 298,
        desc: 'Shinjuku (新宿区) è il più importante e trafficato nodo di trasporto urbano della metropoli.',
        rooms: [
            { dir: 27, img: 'villa_lancaster.png' },
            { dir: 14, img: 'secret_pandora.png' },
            { dir: 38, img: 'stazione.png' },
            { dir: 18, img: 'zona_malfamata.png' },
        ],
    },
    {
        id: 'menu5',
        name: 'Chiyoda',
        cx: 330, cy: 365,
        desc: 'Chiyoda (千代田) è il centro amministrativo di Tokyo dentro cui è possibile trovare, oltre che molte istituzioni governative, il famoso Palazzo di Cristallo.',
        rooms: [
            { dir: 36, img: 'corte.png' },
            { dir: 25, img: 'ospedale.png' },
            { dir: 17, img: 'palazzo_di_cristallo.png' },
            // Chitoku Academy: compare solo di notte nel vecchio PHP (mostrata per prima di notte)
            { dir: 26, img: 'chitoku_academy.png', nightOnly: false },
        ],
    },
    {
        id: 'menu6',
        name: 'Roppongi',
        cx: 161, cy: 320,
        desc: 'Roppongi (六本木) è nota per l\'ingente numero di locali notturni e, per questo, meta di numerosi turisti ed espatriati occidentali.',
        rooms: [
            // { page: 'servizi_mercato', img: 'centro_commerciale.png' },
            { dir: 13, img: 'gatto_nero.png' },
            { page: 'servizi_prenotazioni_prova', img: 'hotel_inn.png' },
            { dir: 47, img: 'terrazza_panoramica.png' },
            { dir: 35, img: 'tokyo_tower.png' },
        ],
    },
    {
        id: 'menu7',
        name: 'Shibuya',
        cx: 80, cy: 405,
        desc: 'Shibuya (渋谷) è la zona più conosciuta e affollata di tutta la capitale giapponese, perennemente illuminata da megaschermi e luci. È il quartiere preferito dai giovani.',
        rooms: [
            { dir: 23, img: 'centro.png' },
            { dir: 24, img: 'magic_shop.png' },
            { dir: 30, img: 'Tae.png' },
            { dir: 33, img: 'harajuku.png' },
            { dir: 8, img: 'zona_residenziale_ovest.png' },
        ],
    },
    {
        id: 'menu8',
        name: 'Asakusa',
        cx: 581, cy: 238,
        desc: 'Asakusa (浅草) viene spesso associata alla zona spirituale. Dominata da templi e santuari shinto, è possibile notare persone vestite con abiti tradizionali.',
        rooms: [
            { dir: 34, img: 'cimitero.png' },
            { dir: 19, img: 'santuario_di_cosmos.png' },
            { dir: 31, img: 'reggia_lunare.png' },
            { dir: 48, img: 'zona_residenziale_est.png' },
        ],
    },
    {
        id: 'menu9',
        name: 'Tsukiji',
        cx: 674, cy: 295,
        desc: 'Tsukiji (築地) deve la sua fama al celeberrimo mercato del pesce. A seguito di un terremoto non ancora compreso, molta della zona è costituita da palazzi abbandonati.',
        rooms: [
            { dir: 21, img: 'fiume.png' },
            { dir: 49, img: 'palazzo_abbandonato.png' },
            { dir: 37, img: 'periferia_sud.png' },
            { dir: 20, img: 'quartier_generale.png' },
        ],
    },
]

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

    /**
     * Conteggio utenti online per stanza: { [dir]: numero }
     * Popolato da api_map.php?op=rooms e aggiornato via socket 'users:update'.
     */
    const [onlineCounts, setOnlineCounts] = useState({})

    /** ID zona con popup aperto, es. 'menu1'. null = nessun popup. */
    const [openZone, setOpenZone] = useState(null)

    /** Ref all'immagine mappa per leggere le dimensioni naturali reali */
    const imgRef = useRef(null)

    /**
     * Dimensioni naturali REALI dell'immagine (px), lette da img.naturalWidth/Height.
     * Necessarie per calcolare le posizioni percentuali degli hotspot.
     * Non si usa il DB (larghezza/altezza) perché il valore del DB potrebbe essere
     * il default errato (500×330) invece delle dimensioni effettive dell'immagine.
     */
    const [naturalSize, setNaturalSize] = useState({ w: 0, h: 0 })

    // ---------------------------------------------------------------------------
    // FETCH DATI
    // ---------------------------------------------------------------------------

    /**
     * Recupera info mappa (is_notte, larghezza, altezza) da op=current.
     * Le dimensioni naturali servono per scalare i hotspot correttamente.
     */
    const fetchMapInfo = useCallback(() => {
        fetch('/pages/api_map.php?op=current')
            .then(r => r.json())
            .then(data => { if (data.success) setMapInfo(data) })
            .catch(console.error)
    }, [])

    /**
     * Recupera il conteggio utenti online per ogni stanza della mappa corrente.
     * Usa effectiveMapId (URL → CT_USER.mappa) per essere corretto anche in
     * navigazione SPA dove CT_USER.mappa potrebbe essere stale.
     * Chiamato al mount e ad ogni evento socket 'users:update'.
     */
    const fetchRoomCounts = useCallback(() => {
        fetch(`/pages/api_map.php?op=rooms&map_id=${effectiveMapId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return
                const counts = {}
                data.rooms.forEach(r => { counts[r.id] = r.utenti_online })
                setOnlineCounts(counts)
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
        fetchRoomCounts()

        // Aggiorna conteggi ad ogni spostamento di utenti nella mappa
        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchRoomCounts)

        return () => { if (sock) sock.off('users:update', fetchRoomCounts) }
    }, [fetchMapInfo, fetchRoomCounts])

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
                if (d.success && window.CT_USER) window.CT_USER.mappa = effectiveMapId
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
     * Naviga verso una stanza (dir=X) o una pagina (page=X).
     *
     * In SPA mode (window.CT.navigate disponibile), PHP non gira:
     * per le stanze con dir chiama prima op=move via AJAX per aggiornare
     * DB, sessione e socket (stesso pattern usato da op=changemap al mount).
     * In full-page-reload, main.php?dir=X gestisce tutto lato PHP.
     *
     * @param {Object} room - Oggetto stanza dalla costante ZONES
     */
    const navigate = useCallback(async (room) => {
        if (room.dir !== undefined) {
            const url = `main.php?dir=${room.dir}`
            if (window.CT?.navigate) {
                try {
                    await fetch('/pages/api_map.php?op=move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ dir: room.dir }),
                    })
                } catch { /* ignora errori di rete */ }
                window.CT.navigate(url)
            } else {
                window.top.location.href = url
            }
        } else if (room.page) {
            const url = `main.php?page=${room.page}`
            if (window.CT?.navigate) window.CT.navigate(url)
            else window.top.location.href = url
        }
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

    /** Sceglie l'immagine giorno o notte in base all'ora */
    const mapImg = mapInfo?.is_notte
        ? 'themes/crystal/imgs/maps/mappa_notte.png'
        : 'themes/crystal/imgs/maps/mappa_giorno.png'

    /**
     * Zona attualmente selezionata (se c'è un openZone).
     * Usata sia per evidenziare il pallino che per il pannello sotto.
     */
    const selectedZone = openZone ? ZONES.find(z => z.id === openZone) : null

    return (
        <div>
            {/* ---------------------------------------------------------------- */}
            {/* MAPPA — immagine con pallini hotspot                              */}
            {/* Il popup NON è dentro questo container per evitare overflow/clip  */}
            {/* ---------------------------------------------------------------- */}
            <center>
                <div style={{ position: 'relative', display: 'inline-block', maxWidth: '100%' }}>
                    <img
                        ref={imgRef}
                        src={mapImg}
                        alt="Mappa di Crystal Tokyo"
                        style={{ maxWidth: '100%', height: 'auto', display: 'block' }}
                        onLoad={onImageLoad}
                    />

                    {/* Pallini cliccabili — solo dopo il caricamento dell'immagine */}
                    {ZONES.map(zone => {
                        const style = hotspotStyle(zone.cx, zone.cy)
                        if (!style) return null
                        return (
                            <div
                                key={zone.id}
                                style={style}
                                onClick={e => { e.stopPropagation(); setOpenZone(openZone === zone.id ? null : zone.id) }}
                                title={zone.name}
                            >
                                <div style={{
                                    width: '23px', height: '23px',
                                    borderRadius: '50%',
                                    backgroundColor: 'transparent',
                                }} />
                            </div>
                        )
                    })}
                </div>
            </center>

            {/* ---------------------------------------------------------------- */}
            {/* PANNELLO ZONA — appare SOTTO la mappa in flusso normale.          */}
            {/* Non usa position:absolute dentro il container, quindi non viene   */}
            {/* mai tagliato dall'overflow né dalla dimensione del frame.         */}
            {/* ---------------------------------------------------------------- */}
            {selectedZone && (
                <div className="menu_mappa" style={{
                    visibility: 'visible',
                    position: 'relative',   // flusso normale, non absolute
                    transform: 'none',       // annulla il translate(-170%,-50%) del CSS
                    width: '100%',
                    maxWidth: '100%',
                    margin: '6px 0 0 0',
                    boxSizing: 'border-box',
                    textAlign: 'center',
                }}>
                    {/* Nome zona e descrizione */}
                    <strong style={{ display: 'block', marginBottom: '4px', fontSize: '1em' }}>
                        {selectedZone.name}
                    </strong>
                    <p style={{ margin: '0 0 8px', fontSize: '0.85em', lineHeight: '1.4' }}>
                        {selectedZone.desc}
                    </p>

                    {/* Stanze — griglia flex, le immagini scalano con il contenitore */}
                    <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '6px' }}>
                        {selectedZone.rooms.map((room, i) => {
                            const count = room.dir ? (onlineCounts[room.dir] || 0) : 0
                            return (
                                <span
                                    key={i}
                                    style={{ position: 'relative', display: 'inline-block', cursor: 'pointer' }}
                                    onClick={() => navigate(room)}
                                >
                                    <img
                                        src={`/themes/crystal/imgs/maps/${room.img}`}
                                        style={{ display: 'block', maxWidth: '100%', height: 'auto' }}
                                        alt=""
                                        border="0"
                                    />
                                    {/* Badge utenti online */}
                                    {count > 0 && (
                                        <span style={{
                                            position: 'absolute', top: '-4px', right: '-4px',
                                            background: '#e74c3c', color: '#fff',
                                            borderRadius: '50%', fontSize: '10px', fontWeight: 'bold',
                                            minWidth: '16px', height: '16px',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                                            pointerEvents: 'none',
                                        }}>
                                            {count}
                                        </span>
                                    )}
                                </span>
                            )
                        })}
                    </div>

                    {/* Chiudi pannello */}
                    <span
                        className="close-location-modal"
                        onClick={() => setOpenZone(null)}
                        style={{ display: 'block', marginTop: '8px', cursor: 'pointer' }}
                    >
                        ×
                    </span>
                </div>
            )}
        </div>
    )
}
