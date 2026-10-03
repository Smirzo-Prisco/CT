/**
 * SplitPane.jsx — shell responsiva a due colonne (lista + dettaglio)
 *
 * Componente "core" riusabile: incorpora solo l'architettura di layout
 * (altezza ancorata a #maincontent, due colonne affiancate su desktop, una
 * sola visibile su mobile in base a "view") già corretta nel redesign di
 * MessagesInbox. Testata, azioni e contenuto delle colonne restano a chi lo
 * usa — ogni area del sistema personalizza SplitPane col proprio markup,
 * senza duplicare lo scheletro responsive/scroll.
 *
 * @param {'list'|'detail'} view   - Quale colonna mostrare su mobile (su desktop sono sempre entrambe visibili)
 * @param {React.ReactNode} list   - Contenuto della colonna sinistra (lista)
 * @param {React.ReactNode} detail - Contenuto della colonna destra (dettaglio)
 * @param {string} [className]    - Classi extra sul contenitore radice
 */
import styles from './SplitPane.module.scss'

export default function SplitPane({ view = 'list', list, detail, className = '' }) {
    const viewClass = view === 'detail' ? styles.viewDetail : styles.viewList
    return (
        <div className={`${styles.shell} ${viewClass} ${className}`}>
            <div className={styles.listCol}>{list}</div>
            <div className={styles.detailCol}>{detail}</div>
        </div>
    )
}

/**
 * Area di scroll interna di una colonna (lista conversazioni, griglia di
 * card, messaggi di un thread, ...). Da affiancare come fratello flex
 * all'header della colonna (mai annidarla dentro un header sticky).
 *
 * @param {boolean} [padBottomMobile] - true per lasciare spazio ai pulsanti hamburger fissi su mobile
 */
SplitPane.ScrollArea = function ScrollArea({ children, className = '', padBottomMobile = false }) {
    const padClass = padBottomMobile ? styles.scrollAreaPadMobile : ''
    return (
        <div className={`${styles.scrollArea} ${padClass} ${className}`}>
            {children}
        </div>
    )
}
