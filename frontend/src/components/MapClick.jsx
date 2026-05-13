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
 * Montaggio: via ct:ready su #map-container in mappaclick.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'

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
            { dir: 50,  img: 'faro.png'          },
            { dir: 2,   img: 'porto.png'          },
            { dir: 3,   img: 'ponte.png'          },
            { dir: 22,  img: 'spiaggia.png'       },
            { dir: 32,  img: 'regno_di_caos.png'  },
        ],
    },
    {
        id: 'menu2',
        name: 'Monte Fuji',
        cx: 462, cy: 141,
        desc: 'Il Monte Fuji (富士山 Fuji-san) è il vulcano più alto di tutto il Giappone. Luogo dalla bellezza paesaggistica straordinaria, è considerato uno dei luoghi sacri più importanti.',
        rooms: [
            { dir: 9,   img: 'bosco.png'          },
            { dir: 28,  img: 'terme.png'           },
            { dir: 11,  img: 'stella_prima.png'    },
            { dir: 428, img: 'nikigori.png'        },
            { dir: 10,  img: 'altri_luoghi.png'    },
        ],
    },
    {
        id: 'menu3',
        name: 'Ueno',
        cx: 446, cy: 289,
        desc: 'Ueno (上野) è il quartiere in cui risiedono i più importanti musei e parchi di tutta la città. Densamente popolato soprattutto durante la fioritura dei sakura.',
        rooms: [
            { dir: 43,  img: 'giardini_dei_fiori_del_male.png' },
            { dir: 16,  img: 'luna_park.png'                   },
            { dir: 4,   img: 'parco_di_ueno.png'               },
            { dir: 12,  img: 'periferia_nord.png'              },
            { dir: 7,   img: 'zoo.png'                         },
        ],
    },
    {
        id: 'menu4',
        name: 'Shinjuku',
        cx: 47, cy: 298,
        desc: 'Shinjuku (新宿区) è il più importante e trafficato nodo di trasporto urbano della metropoli.',
        rooms: [
            { dir: 27,  img: 'villa_lancaster.png'   },
            { dir: 14,  img: 'secret_pandora.png'    },
            { dir: 38,  img: 'stazione.png'          },
            { dir: 18,  img: 'zona_malfamata.png'    },
        ],
    },
    {
        id: 'menu5',
        name: 'Chiyoda',
        cx: 330, cy: 365,
        desc: 'Chiyoda (千代田) è il centro amministrativo di Tokyo dentro cui è possibile trovare, oltre che molte istituzioni governative, il famoso Palazzo di Cristallo.',
        rooms: [
            { dir: 36,  img: 'corte.png'                 },
            { dir: 25,  img: 'ospedale.png'              },
            { dir: 17,  img: 'palazzo_di_cristallo.png'  },
            // Chitoku Academy: compare solo di notte nel vecchio PHP (mostrata per prima di notte)
            { dir: 26,  img: 'chitoku_academy.png', nightOnly: false },
        ],
    },
    {
        id: 'menu6',
        name: 'Roppongi',
        cx: 161, cy: 320,
        desc: 'Roppongi (六本木) è nota per l\'ingente numero di locali notturni e, per questo, meta di numerosi turisti ed espatriati occidentali.',
        rooms: [
            { page: 'servizi_mercato',              img: 'centro_commerciale.png' },
            { dir: 13,                              img: 'gatto_nero.png'         },
            { page: 'servizi_prenotazioni_prova',   img: 'hotel_inn.png'          },
            { dir: 47,                              img: 'terrazza_panoramica.png' },
            { dir: 35,                              img: 'tokyo_tower.png'        },
        ],
    },
    {
        id: 'menu7',
        name: 'Shibuya',
        cx: 80, cy: 405,
        desc: 'Shibuya (渋谷) è la zona più conosciuta e affollata di tutta la capitale giapponese, perennemente illuminata da megaschermi e luci. È il quartiere preferito dai giovani.',
        rooms: [
            { dir: 23,  img: 'centro.png'                    },
            { dir: 24,  img: 'magic_shop.png'                },
            { dir: 30,  img: 'Tae.png'                       },
            { dir: 33,  img: 'harajuku.png'                  },
            { dir: 8,   img: 'zona_residenziale_ovest.png'   },
        ],
    },
    {
        id: 'menu8',
        name: 'Asakusa',
        cx: 581, cy: 238,
        desc: 'Asakusa (浅草) viene spesso associata alla zona spirituale. Dominata da templi e santuari shinto, è possibile notare persone vestite con abiti tradizionali.',
        rooms: [
            { dir: 34,  img: 'cimitero.png'              },
            { dir: 19,  img: 'santuario_di_cosmos.png'   },
            { dir: 31,  img: 'reggia_lunare.png'         },
            { dir: 48,  img: 'zona_residenziale_est.png' },
        ],
    },
    {
        id: 'menu9',
        name: 'Tsukiji',
        cx: 674, cy: 295,
        desc: 'Tsukiji (築地) deve la sua fama al celeberrimo mercato del pesce. A seguito di un terremoto non ancora compreso, molta della zona è costituita da palazzi abbandonati.',
        rooms: [
            { dir: 21,  img: 'fiume.png'               },
            { dir: 49,  img: 'palazzo_abbandonato.png' },
            { dir: 37,  img: 'periferia_sud.png'       },
            { dir: 20,  img: 'quartier_generale.png'   },
        ],
    },
]

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function MapClick() {

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
     * Chiamato al mount e ad ogni evento socket 'users:update'.
     */
    const fetchRoomCounts = useCallback(() => {
        const mapId = window.CT_USER?.mappa ?? 1
        fetch(`/pages/api_map.php?op=rooms&map_id=${mapId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return
                const counts = {}
                data.rooms.forEach(r => { counts[r.id] = r.utenti_online })
                setOnlineCounts(counts)
            })
            .catch(console.error)
    }, [])

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
     * Usa window.top per uscire dai frame e caricare il contenuto principale.
     *
     * @param {Object} room - Oggetto stanza dalla costante ZONES
     */
    const navigate = (room) => {
        if (room.dir !== undefined) {
            window.top.location.href = `main.php?dir=${room.dir}`
        } else if (room.page) {
            window.top.location.href = `main.php?page=${room.page}`
        }
    }

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
            position:  'absolute',
            left:      `${(cx / naturalSize.w) * 100}%`,
            top:       `${(cy / naturalSize.h) * 100}%`,
            transform: 'translate(-50%, -50%)',
            cursor:    'pointer',
            zIndex:    10,
        }
    }

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    /** Sceglie l'immagine giorno o notte in base all'ora */
    const mapImg = mapInfo?.is_notte
        ? 'themes/crystal/imgs/maps/mappa_notte.png'
        : 'themes/crystal/imgs/maps/mappa_giorno.png'

    return (
        <center>
            {/*
              * Wrapper con position:relative per i hotspot assoluti.
              * overflow:hidden evita che hotspot fuori immagine (se le coordinate
              * fossero errate) causino scroll orizzontale della pagina.
              */}
            <div style={{ position: 'relative', display: 'inline-block', maxWidth: '100%', overflow: 'hidden' }}>

                {/* Immagine mappa — responsive */}
                <img
                    ref={imgRef}
                    src={mapImg}
                    alt="Mappa di Crystal Tokyo"
                    style={{ maxWidth: '100%', height: 'auto', display: 'block' }}
                    onLoad={onImageLoad}
                />

                {/*
                  * HOTSPOT — solo i pallini, senza popup al loro interno.
                  * Il popup è un sibling separato nel container (vedi sotto).
                  * Renderizzati solo dopo che l'immagine è caricata (naturalSize.w > 0).
                  */}
                {ZONES.map(zone => {
                    const style = hotspotStyle(zone.cx, zone.cy)
                    if (!style) return null
                    return (
                        <div
                            key={zone.id}
                            style={style}
                            onClick={e => { e.stopPropagation(); setOpenZone(openZone === zone.id ? null : zone.id) }}
                            onMouseEnter={() => setOpenZone(zone.id)}
                            title={zone.name}
                        >
                            <div style={{
                                width: '14px',
                                height: '14px',
                                borderRadius: '50%',
                                backgroundColor: openZone === zone.id ? '#ffcc00' : 'rgba(255,255,255,0.7)',
                                border: '2px solid rgba(0,0,0,0.5)',
                                boxShadow: '0 0 6px rgba(0,0,0,0.6)',
                                transition: 'background-color 0.2s',
                            }} />
                        </div>
                    )
                })}

                {/*
                  * POPUP — sibling dei pallini, non figlio.
                  * È position:absolute relativo al container (l'immagine), non al pallino.
                  * La posizione si calcola con le stesse percentuali dell'hotspot ma con
                  * un offset per non sovrapporsi al pallino, spostandosi verso l'interno
                  * della mappa in base alla zona (sinistra/destra, alto/basso).
                  */}
                {openZone && (() => {
                    const zone = ZONES.find(z => z.id === openZone)
                    if (!zone || naturalSize.w === 0) return null

                    /** Posizione del pallino in percentuale sull'immagine */
                    const leftPct = (zone.cx / naturalSize.w) * 100
                    const topPct  = (zone.cy / naturalSize.h) * 100

                    /**
                     * Posizionamento smart: allinea il popup verso l'interno della mappa
                     * per evitare che esca dai bordi.
                     * - Zone sulla destra (leftPct > 55): popup a sinistra del pallino
                     * - Zone in basso  (topPct  > 55): popup sopra il pallino
                     */
                    const isRight  = leftPct > 55
                    const isBottom = topPct  > 55

                    const popupStyle = {
                        visibility: 'visible',
                        position:   'absolute',
                        left:       isRight  ? 'auto'               : `calc(${leftPct}% + 12px)`,
                        right:      isRight  ? `calc(${100 - leftPct}% + 12px)` : 'auto',
                        top:        isBottom ? 'auto'               : `calc(${topPct}% - 10px)`,
                        bottom:     isBottom ? `calc(${100 - topPct}% + 12px)` : 'auto',
                        zIndex:     500,
                        // mappa_principale.css ha transform: translate(-170%, -50%)
                        // che spostava il popup di 170% a sinistra (logica del vecchio
                        // approccio mouse-position). Con il posizionamento percentuale
                        // relativo all'immagine, quel transform deve essere annullato.
                        transform:  'none',
                    }

                    return (
                        <div
                            className="menu_mappa"
                            style={popupStyle}
                            onClick={e => e.stopPropagation()}
                        >
                            <ul className="Stile1">
                                <p>{zone.desc}</p>

                                {/* Icone stanze con badge utenti online */}
                                {zone.rooms.map((room, i) => {
                                    const count = room.dir ? (onlineCounts[room.dir] || 0) : 0
                                    return (
                                        <span
                                            key={i}
                                            style={{ position: 'relative', display: 'inline-block', margin: '2px' }}
                                            onClick={() => navigate(room)}
                                        >
                                            <img
                                                src={`/themes/crystal/imgs/maps/${room.img}`}
                                                style={{ cursor: 'pointer', display: 'block' }}
                                                alt=""
                                                border="0"
                                            />
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
                            </ul>
                            <span
                                className="close-location-modal"
                                onClick={e => { e.stopPropagation(); setOpenZone(null) }}
                            >
                                ×
                            </span>
                        </div>
                    )
                })()}
            </div>
        </center>
    )
}
