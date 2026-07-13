/**
 * useWeatherEffect.js
 *
 * Condizione meteo corrente (api_global.php?op=meteo) tradotta nell'effetto
 * grafico da sovrapporre (stesse chiavi di WEATHER_ICONS in Meteo.jsx) —
 * riusato sia dal cerchio-luogo dell'HUD (Hud.jsx) sia dalla mappa grande
 * (MapClick.jsx). Fetch separata da Meteo.jsx: quel componente viene
 * montato anche da solo altrove.
 *
 * @param {boolean} isNotte - Sceglie condizione giorno/notte (stesso flag
 *   gia' usato per scegliere mappa_giorno/notte.png o l'immagine luogo).
 * @returns {string|null} Una delle chiavi di WEATHER_EFFECTS, o null se il
 *   meteo non e' ancora arrivato o la condizione non ha un effetto dedicato.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

// Solo le condizioni con un effetto visivo distintivo hanno una voce: cielo
// sereno notturno resta senza overlay, l'immagine da sola basta.
const WEATHER_EFFECTS = {
    sole: 'sun',
    sole_nuvoloso: 'cloud',
    nuvoloso: 'cloud',
    pioggia: 'rain',
    temporale: 'storm',
    neve: 'snow',
    sole_nebbia: 'fog',
    luna_nuvoloso: 'cloud',
    luna_nebbia: 'fog',
}

export default function useWeatherEffect(isNotte) {
    const [meteoData, setMeteoData] = useState(null)

    useEffect(() => {
        fetch('/pages/api_global.php?op=meteo')
            .then(r => r.json())
            .then(d => { if (d.success) setMeteoData(d) })
            .catch(() => { })
    }, [])

    if (!meteoData) return null
    return WEATHER_EFFECTS[isNotte ? meteoData.attuale.notte_img : meteoData.attuale.giorno_img] ?? null
}
