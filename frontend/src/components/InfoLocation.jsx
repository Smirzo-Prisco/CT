/**
 * InfoLocation.jsx
 *
 * Box informazioni luogo corrente — rimpiazza pages/info_location.inc.php.
 * Mostrato nella colonna sinistra del layout di gioco.
 *
 * Contenuto:
 *   - Immagine del luogo (variante giorno/notte)
 *   - Nome luogo + anno di gioco (cliccabile → torna al luogo corrente)
 *   - Icona pin a sinistra del nome, icona info a destra → modale descrizione
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

    /** Visibilità della modale descrizione luogo */
    const [showDesc, setShowDesc] = useState(false)

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

        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchLocation)

        // Aggiornamento diretto quando la SPA sposta il pg in una nuova stanza
        // (AppRouter dispatcha questo evento dopo op=move, senza attendere il socket)
        window.addEventListener('ct:location-changed', fetchLocation)

        return () => {
            if (sock) sock.off('users:update', fetchLocation)
            window.removeEventListener('ct:location-changed', fetchLocation)
        }
    }, [fetchLocation])

    // ---------------------------------------------------------------------------
    // Rendering
    // ---------------------------------------------------------------------------

    if (!data) {
        return <div className="pagina_info_location"><div className={shared.muted}>...</div></div>
    }

    /** Naviga al luogo corrente via CT.navigate (SPA) o href diretto.
     *  Funziona sia per stanze che per mappe. */
    const goToRoom = (e) => {
        e.preventDefault()
        const url = `main.php?dir=${data.luogo}`
        if (window.CT?.navigate) window.CT.navigate(url)
        else window.top.location.href = url
    }

    return (
        <div className="pagina_info_location">

            <div className="page_body">

                {/* Anno + nome luogo */}
                <div className="info-location-year">
                    <span className="info-location-anno">{`Anno ${data.anno}`}</span>
                    <div className="info-location-name-row">

                        {/* Pin sinistra — cliccabile, porta al luogo corrente */}
                        <a href={`main.php?dir=${data.luogo}`} onClick={goToRoom} className="info-location-pin-link" title="Vai al luogo">
                            <i className="fa-solid fa-location-dot info-location-pin" />
                        </a>

                        {/* Nome — cliccabile sia in stanza che in mappa */}
                        <a href={`main.php?dir=${data.luogo}`} onClick={goToRoom} className="info-location-name">
                            {data.nome}
                        </a>

                        {/* Info destra — apre modale descrizione */}
                        {data.descrizione && (
                            <button
                                className="info-location-desc-btn"
                                onClick={() => setShowDesc(true)}
                                title="Descrizione del luogo"
                            >
                                <i className="fa-solid fa-circle-info" />
                            </button>
                        )}

                    </div>
                </div>
            </div>

            {/* Modale descrizione luogo */}
            {showDesc && (
                <div className="info-location-overlay" onClick={() => setShowDesc(false)}>
                    <div className="info-location-modal" onClick={e => e.stopPropagation()}>
                        <div className="info-location-modal-header">
                            <span>
                                <i className="fa-solid fa-location-dot" style={{ marginRight: 8 }} />
                                {data.nome}
                            </span>
                            <button onClick={() => setShowDesc(false)} aria-label="Chiudi">×</button>
                        </div>
                        <div className="info-location-modal-body">
                            {data.descrizione_immagine && (
                                <img
                                    src={`/themes/crystal/imgs/descrizioni/${data.descrizione_immagine}`}
                                    alt={data.nome}
                                    className="info-location-modal-img"
                                />
                            )}
                            <div dangerouslySetInnerHTML={{ __html: data.descrizione }} />
                        </div>
                    </div>
                </div>
            )}

        </div>
    )
}
