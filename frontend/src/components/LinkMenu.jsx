/**
 * LinkMenu.jsx
 *
 * Menu di navigazione nella colonna sinistra — rimpiazza pages/link_menu.inc.php.
 *
 * Contenuto:
 *   - Select di navigazione rapida mappa/stanza (gotomap):
 *       fetcha da api_map.php?op=gotomap, si aggiorna via socket users:update
 *       così l'opzione selezionata riflette sempre la posizione corrente
 *   - Lista link dal menu di configurazione (passata come props da PHP):
 *       immagini con hover effect (onMouseEnter/onMouseLeave)
 *
 * Props (passate da link_menu.inc.php via CT.mount):
 *   @param {Array}  menuItems    - Voci di menu da config.inc.php
 *   @param {string} menuTitle    - Titolo del menu (o stringa vuota)
 *   @param {string} theme        - Nome del tema corrente
 *   @param {boolean} showGotomap - Mostra il select gotomap (da $PARAMETERS['mode']['gotomap_list'])
 *
 * Montaggio: via inline script in link_menu.inc.php (non via ct:ready generico
 * perché ha bisogno dei props dalla sessione PHP).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'

export default function LinkMenu({ menuItems = [], menuTitle = '', theme = 'crystal', showGotomap = true }) {

    /**
     * Struttura del select gotomap:
     * [{ id, nome, stanze: [{ id, nome, url }] }]
     */
    const [maps,     setMaps]     = useState([])

    /** ID mappa corrente — aggiornato via socket per tenere l'opzione selezionata corretta */
    const [curMappa, setCurMappa] = useState(window.CT_USER?.mappa ?? -1)

    /** ID luogo corrente — aggiornato via socket */
    const [curLuogo, setCurLuogo] = useState(window.CT_USER?.luogo ?? -1)

    /**
     * Stato hover per le immagini del menu (immagine normale vs hover).
     * Mappa: { key: true/false } dove true = mouse sopra
     */
    const [hovered, setHovered] = useState({})

    /** Navigazione via select: usa CT.navigate se disponibile */
    const handleSelect = (url) => {
        if (url && window.CT?.navigate) window.CT.navigate(url)
        else if (url) window.top.location.href = url
    }

    // ---------------------------------------------------------------------------
    // FETCH GOTOMAP
    // ---------------------------------------------------------------------------

    /**
     * Recupera la lista mappa+stanze per il select di navigazione.
     * Aggiorna anche la posizione corrente (mappa, luogo) per tenere
     * l'opzione selezionata sincronizzata con lo spostamento del personaggio.
     */
    const fetchGotomap = useCallback(() => {
        fetch('/pages/api_map.php?op=gotomap')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    setMaps(d.maps)
                    setCurMappa(d.mappa)
                    setCurLuogo(d.luogo)
                }
            })
            .catch(err => console.error('[LinkMenu] Errore gotomap:', err))
    }, [])

    useEffect(() => {
        if (showGotomap) fetchGotomap()

        // Aggiorna il select ad ogni spostamento del personaggio
        const sock = window.ctSocket
        if (sock && showGotomap) sock.on('users:update', fetchGotomap)
        return () => { if (sock && showGotomap) sock.off('users:update', fetchGotomap) }
    }, [showGotomap, fetchGotomap])

    // ---------------------------------------------------------------------------
    // RENDERING
    // ---------------------------------------------------------------------------

    return (
        <div className="pagina_link_menu">

            {/* Select navigazione rapida mappa/stanza */}
            {showGotomap && maps.length > 0 && (
                <select
                    id="gotomap"
                    onChange={e => handleSelect(e.target.value)}
                    value={
                        // Valore selezionato: URL della stanza corrente o della mappa corrente
                        curLuogo >= 0
                            ? `main.php?dir=${curLuogo}`
                            : `main.php?page=mappaclick&map_id=${curMappa}`
                    }
                >
                    {maps.map(map => (
                        <>
                            {/* Opzione mappa (livello superiore) */}
                            <option
                                key={`map-${map.id}`}
                                value={`main.php?page=mappaclick&map_id=${map.id}`}
                                className="map"
                            >
                                {map.nome}
                            </option>
                            {/* Opzioni stanze (livello inferiore con freccia) */}
                            {map.stanze.map(stanza => (
                                <option
                                    key={`stanza-${stanza.id}`}
                                    value={stanza.url}
                                >
                                    {`» ${stanza.nome}`}
                                </option>
                            ))}
                        </>
                    ))}
                </select>
            )}

            {/* Titolo menu (se presente) */}
            {menuTitle && (
                <div className="page_title">
                    <h2>{menuTitle}</h2>
                </div>
            )}

            {/* Voci di menu con hover effect */}
            <div className="page_body">
                {menuItems.map((item, i) => {
                    const key      = `link_menu_${i}`
                    const isHover  = !!hovered[key]
                    const hasHover = !!item.image_file_onclick

                    /** Percorso immagine base o hover */
                    const imgSrc = hasHover && isHover
                        ? `/themes/${theme}/imgs/menu/${item.image_file_onclick}`
                        : `/themes/${theme}/imgs/menu/${item.image_file}`

                    return (
                        <div key={key} className="link_menu">
                            <a
                                href={item.url}
                                id={key}
                                onMouseEnter={() => setHovered(h => ({ ...h, [key]: true }))}
                                onMouseLeave={() => setHovered(h => ({ ...h, [key]: false }))}
                            >
                                {item.image_file ? (
                                    <img
                                        className="menu_img"
                                        src={imgSrc}
                                        alt={item.text}
                                        title={item.text}
                                        style={{ transition: 'opacity 0.3s' }}
                                    />
                                ) : (
                                    item.text
                                )}
                            </a>
                        </div>
                    )
                })}
            </div>
        </div>
    )
}
