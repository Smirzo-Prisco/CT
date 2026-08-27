/**
 * Meteo.jsx
 *
 * Meteo compatto per il pannello del cerchio sinistro dell'HUD (Hud.jsx):
 * sostituisce nome/descrizione quando il personaggio e' sulla mappa generale
 * (non in una stanza specifica). Icone Font Awesome invece delle vecchie
 * immagini bitmap, in linea con lo stile navy+oro dell'HUD (usa le stesse
 * custom property --gold/--text/--muted, ereditate da .ct-hud in _hud.scss).
 *
 * API: GET pages/api_global.php?op=meteo
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

// Condizione (giorno_img/notte_img restituiti da api_global.php) → icona Font Awesome
const WEATHER_ICONS = {
    sole: 'fa-sun',
    nuvoloso: 'fa-cloud',
    sole_nuvoloso: 'fa-cloud-sun',
    pioggia: 'fa-cloud-rain',
    temporale: 'fa-cloud-bolt',
    neve: 'fa-snowflake',
    sole_nebbia: 'fa-smog',
    mezza_luna: 'fa-moon',
    luna_nuvoloso: 'fa-cloud-moon',
    luna_nebbia: 'fa-smog',
}

function weatherIcon(cond) {
    return WEATHER_ICONS[cond] ?? 'fa-cloud-sun'
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

    const data = showYesterday ? meteo.precedente : meteo.attuale

    return (
        <div className="ct-meteo">
            <div className="ct-meteo__head">
                <span>{showYesterday ? 'Meteo di ieri' : 'Meteo di oggi'}</span>
                {data.data && <div className="ct-meteo__data">{data.data}</div>}
                <button
                    type="button"
                    className="ct-meteo__toggle"
                    onClick={() => setShowYesterday(v => !v)}
                    title={showYesterday ? 'Mostra oggi' : 'Mostra ieri'}
                >
                    <i className="fa-solid fa-clock-rotate-left" />
                </button>
            </div>

            <div className="ct-meteo__row">
                <div className="ct-meteo__col">
                    <i className={`fa-solid ${weatherIcon(data.giorno_img)} ct-meteo__icon`} />
                    <span className="ct-meteo__temp">{data.temp_max}°</span>
                    <span className="ct-meteo__wind"><i className="fa-solid fa-wind" /> {data.vento_giorno}</span>
                </div>
                <div className="ct-meteo__col">
                    <i className={`fa-solid ${weatherIcon(data.notte_img)} ct-meteo__icon`} />
                    <span className="ct-meteo__temp">{data.temp_min}°</span>
                    <span className="ct-meteo__wind"><i className="fa-solid fa-wind" /> {data.vento_notte}</span>
                </div>
            </div>
        </div>
    )
}
