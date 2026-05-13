/**
 * MenuIcons.jsx
 *
 * Box icone nella colonna destra — rimpiazza pages/menu_icons.inc.php.
 * Mostra 3 icone affiancate: Famiglie, Mestieri, Calendario.
 *
 * Effetto hover: due immagini sovrapposte — al passaggio del mouse,
 * quella superiore (#pic) diventa trasparente rivelando quella interna
 * (#pic-inner). È un effetto puramente CSS (hover.css: #pic:hover { opacity:0 }).
 *
 * Varianti giorno/notte:
 *   - Giorno: hover con *.gif animata
 *   - Notte: icona notturna *.gif (non animata)
 *
 * Calendario: mostra gif animata se ci sono eventi o appuntamenti oggi
 * (verifica via api_global.php?op=events_today).
 *
 * Montaggio: via ct:ready su #menu-icons-container in menu_icons.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

/** Base path delle icone */
const ICO = '../themes/crystal/imgs/icone/'

export default function MenuIcons() {

    /** true se ci sono eventi/appuntamenti oggi per il personaggio corrente */
    const [hasEvents, setHasEvents] = useState(false)

    /** true se è notte (orario 18–6) */
    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    useEffect(() => {
        // Verifica eventi oggi per decidere se animare l'icona Calendario
        fetch('/pages/api_global.php?op=events_today')
            .then(r => r.json())
            .then(d => { if (d.success) setHasEvents(d.has_events) })
            .catch(err => console.error('[MenuIcons] Errore eventi:', err))
    }, [])

    /**
     * Configurazione delle 3 icone.
     * - pic: immagine "base" visibile di default
     * - inner: immagine rivelata al passaggio del mouse (o variante notte)
     */
    const icons = [
        {
            id:    'famiglie',
            href:  '../main.php?page=servizi_gilde',
            title: 'Famiglie',
            pic:   `${ICO}icon_fam.png`,
            inner: isNotte ? `${ICO}icon_fam_night.gif` : `${ICO}icon_fam_hover.gif`,
        },
        {
            id:    'mestieri',
            href:  '../main.php?page=servizi_mestieri',
            title: 'Mestieri',
            pic:   `${ICO}icon_job.png`,
            inner: isNotte ? `${ICO}icon_job_night.gif` : `${ICO}icon_job_hover.gif`,
        },
        {
            id:    'tv',
            href:  '../main.php?page=agenda_center',
            title: 'Calendario',
            // Il Calendario mostra gif animata se ci sono eventi oggi
            pic:   hasEvents ? `${ICO}icon_news_night.gif`  : `${ICO}icon_news.png`,
            inner: hasEvents
                ? (isNotte ? `${ICO}icon_news_night.gif` : `${ICO}icon_news_hover.gif`)
                : `${ICO}icon_news.png`,
        },
    ]

    return (
        <div className="iframe_icone">
            <table>
                <tbody>
                    <tr>
                        <td>
                            {icons.map(icon => (
                                <div key={icon.id} id={icon.id}>
                                    <div id="pic-wrapper">
                                        <a href={icon.href} target="_top">
                                            {/* Immagine superiore: opacity→0 su :hover (CSS hover.css) */}
                                            <img src={icon.pic}   id="pic"       title={icon.title} alt={icon.title} />
                                            {/* Immagine interna: sempre visibile sotto, rivelata al hover */}
                                            <img src={icon.inner} id="pic-inner" title={icon.title} alt={icon.title} />
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    )
}
