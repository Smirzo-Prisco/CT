/**
 * InfoLocation.jsx
 *
 * Box informazioni luogo corrente — rimpiazza pages/info_location.inc.php.
 * Mostrato nella colonna sinistra del layout di gioco.
 *
 * Contenuto:
 *   - Immagine del luogo (variante giorno/notte)
 *   - Nome luogo + anno di gioco (cliccabile → apre descrizione)
 *   - Stato e descrizione in marquee
 *
 * Aggiornamento real-time:
 *   - Ascolta 'users:update' via socket: si aggiorna ogni volta che il
 *     personaggio cambia stanza o mappa (il server emette questo evento
 *     in api_map.php op=move e op=changemap, e in main.php per ?dir=X)
 *
 * API: GET pages/api_map.php?op=current
 *   Restituisce nome, descrizione, stato, immagine, is_notte, anno,
 *   has_new_news e (se sulla mappa) le dimensioni dell'immagine mappa.
 *
 * Montaggio: via ct:ready su #info-location-container in info_location.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import shared from './shared.module.css'

export default function InfoLocation() {

    /** Dati del luogo corrente restituiti da op=current */
    const [data, setData] = useState(null)

    /**
     * Recupera le informazioni sul luogo corrente dall'API.
     * Chiamato al mount e ad ogni evento socket 'users:update'.
     */
    const fetchLocation = useCallback(() => {
        fetch('/pages/api_map.php?op=current')
            .then(r => r.json())
            .then(d => { if (d.success) setData(d) })
            .catch(err => console.error('[InfoLocation] Errore:', err))
    }, [])

    useEffect(() => {
        fetchLocation()

        // Si aggiorna ad ogni spostamento (il server emette users:update
        // quando il personaggio entra/esce da una stanza o cambia mappa)
        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchLocation)

        return () => { if (sock) sock.off('users:update', fetchLocation) }
    }, [fetchLocation])

    // Apre la descrizione del luogo nel modal iframe del layout
    const openDescription = () => {
        if (data?.luogo >= 0) {
            if (typeof changeFrame === 'function') {
                changeFrame(`pages/descrizione_chat.php?id=${data.luogo}`)
            }
            const modal = document.getElementById('id01')
            if (modal) modal.style.display = 'block'
        }
    }

    // ---------------------------------------------------------------------------
    // Rendering
    // ---------------------------------------------------------------------------

    if (!data) {
        return <div className="pagina_info_location"><div className={shared.muted}>...</div></div>
    }

    /**
     * Immagine del luogo:
     * - Stanza: themes/crystal/imgs/locations/{immagine}
     * - Mappa: mostrare un placeholder (la mappa ha la sua pagina)
     */
    const imgSrc = data.tipo === 'stanza'
        ? `/themes/crystal/imgs/locations/${data.immagine || 'ingresso.png'}`
        : `/themes/crystal/imgs/locations/ingresso.png`

    return (
        <div className="pagina_info_location">

            {/* Titolo: nome luogo */}
            <div className="page_title">
                <h2>{data.nome}</h2>
            </div>

            <div className="page_body">

                {/* Immagine del luogo (classe giorno/notte) */}
                <div className={data.is_notte ? 'info_image_night' : 'info_image'}>
                    <img
                        src={imgSrc}
                        className="immagine_luogo"
                        title={data.descrizione || ''}
                        alt={data.nome}
                    />
                </div>

                {/* Nome luogo + anno di gioco — cliccabile per aprire la descrizione */}
                <div className="info-location-year">
                    {`Anno ${data.anno}`}
                    <a
                        href="#"
                        onClick={e => { e.preventDefault(); openDescription() }}
                    >{data.nome}</a>
                </div>
            </div>
        </div>
    )
}
