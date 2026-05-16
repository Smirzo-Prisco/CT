/**
 * MenuIcons.jsx
 *
 * Box icone nella colonna destra — rimpiazza pages/menu_icons.inc.php.
 * Mostra 3 icone affiancate: Famiglie, Mestieri, Calendario.
 *
 * Effetto hover: due immagini sovrapposte — al passaggio del mouse,
 * quella superiore (.top) diventa trasparente rivelando quella interna
 * (.inner). Effetto CSS tramite CSS Module, senza dipendenza da hover.css.
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
import styles from './MenuIcons.module.css'

const ICO = '../themes/crystal/imgs/icone/'

export default function MenuIcons() {

    const [hasEvents, setHasEvents] = useState(false)

    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    useEffect(() => {
        fetch('/pages/api_global.php?op=events_today')
            .then(r => r.json())
            .then(d => { if (d.success) setHasEvents(d.has_events) })
            .catch(err => console.error('[MenuIcons] Errore eventi:', err))
    }, [])

    const icons = [
        {
            id:    'famiglie',
            href:  '../main.php?page=servizi_gilde',
            title: 'Famiglie',
            top:   `${ICO}icon_fam.png`,
            inner: isNotte ? `${ICO}icon_fam_night.gif` : `${ICO}icon_fam_hover.gif`,
        },
        {
            id:    'mestieri',
            href:  '../main.php?page=servizi_mestieri',
            title: 'Mestieri',
            top:   `${ICO}icon_job.png`,
            inner: isNotte ? `${ICO}icon_job_night.gif` : `${ICO}icon_job_hover.gif`,
        },
        {
            id:    'tv',
            href:  '../main.php?page=agenda_center',
            title: 'Calendario',
            top:   hasEvents ? `${ICO}icon_news_night.gif`  : `${ICO}icon_news.png`,
            inner: hasEvents
                ? (isNotte ? `${ICO}icon_news_night.gif` : `${ICO}icon_news_hover.gif`)
                : `${ICO}icon_news.png`,
        },
    ]

    return (
        <div className={styles.row}>
            {icons.map(icon => (
                <div key={icon.id} className={styles.item}>
                    <a href={icon.href} target="_top" title={icon.title}>
                        <img src={icon.top}   className={styles.top}   alt={icon.title} />
                        <img src={icon.inner} className={styles.inner} alt={icon.title} />
                    </a>
                </div>
            ))}
        </div>
    )
}
