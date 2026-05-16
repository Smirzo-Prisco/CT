/**
 * Meteo.jsx
 *
 * Box meteo nella colonna destra — estratto da FrameMessaggi.jsx.
 *
 * Contenuto:
 *   - Icone giorno/notte con temperature e vento
 *   - Toggle "oggi/ieri" tramite freccia
 *
 * API: GET pages/api_global.php?op=meteo
 *
 * Montaggio: via ct:ready su #meteo-container in left-right_frames.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

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

export default function Meteo() {

    const [meteo, setMeteo] = useState(null)
    const [showYesterday, setShowYesterday] = useState(false)

    useEffect(() => {
        fetch('/pages/api_global.php?op=meteo')
            .then(r => r.json())
            .then(d => { if (d.success) setMeteo(d) })
            .catch(err => console.error('[Meteo] Errore:', err))
    }, [])

    if (!meteo) return null

    return (
        <div className="news">
            <div id="meteo_titolo" className="meteo_titolo">
                {showYesterday ? 'METEO DI IERI' : 'METEO OGGI'}
            </div>

            <div className="meteo_box meteo_attuale" style={{ display: showYesterday ? 'none' : 'flex' }}>
                <MeteoBox data={meteo.attuale} />
            </div>

            <div className="meteo_box meteo_precedente" style={{ display: showYesterday ? 'flex' : 'none' }}>
                <MeteoBox data={meteo.precedente} />
            </div>

            <div className="meteo_freccia">
                <img
                    src="../themes/crystal/imgs/forum/freccia_giu.png"
                    alt="Switch Meteo"
                    className="switch_meteo"
                    onClick={() => setShowYesterday(v => !v)}
                />
            </div>
        </div>
    )
}
