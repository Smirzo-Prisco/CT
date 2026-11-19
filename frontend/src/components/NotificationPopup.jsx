/**
 * NotificationPopup.jsx — Toast globale per il canale notifiche "Popup".
 *
 * Montato una volta in header.inc.php (stesso pattern di SkillDescModal/
 * ChatbotWidget), ascolta l'evento socket 'notification:popup' sulla room
 * personale dm:<nome> (gia' joinata da ogni socket connesso, vedi
 * nodeserver/server.js) e lo emesso lato server da notifyPopup() in
 * includes/custom_functions.inc.php — fire-and-forget, nessuna coda: se il pg
 * non e' online in quel momento il popup semplicemente non arriva.
 *
 * Comportamento focus (specifica utente): se la pagina ha il focus quando il
 * popup arriva, sparisce da solo dopo 5 secondi; se non ce l'ha, resta fisso
 * finche' l'utente non riporta il focus sulla pagina, poi parte il countdown
 * di 5 secondi. Chiudibile anche a mano in qualunque momento.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'
import { createPortal } from 'react-dom'
import styles from './NotificationPopup.module.css'

let nextId = 0

// Etichette leggibili per evento, mostrate come titoletto sopra il testo —
// senza, ogni popup sarebbe un blocco di testo anonimo indistinguibile dagli
// altri eventi.
const EVENT_LABELS = {
    commento_post_seguito:    'Nuovo commento',
    commento_post_commentato: 'Nuovo commento',
    commento_post_proprio:    'Nuovo commento',
    nuovo_dm:                 'Messaggio privato',
    chat_off_non_letta:       'Chattina off',
    calendario_nuovo_impegno: 'Calendario',
    ingresso_stanza:          'Stanza',
}

export default function NotificationPopup() {
    const [popups, setPopups] = useState([])
    const timersRef = useRef({}) // id -> timeout handle

    const dismiss = useCallback((id) => {
        setPopups(prev => prev.filter(p => p.id !== id))
        if (timersRef.current[id]) {
            clearTimeout(timersRef.current[id])
            delete timersRef.current[id]
        }
    }, [])

    const scheduleDismiss = useCallback((id) => {
        if (timersRef.current[id]) return // gia' schedulato, non riparte da capo
        timersRef.current[id] = setTimeout(() => dismiss(id), 5000)
    }, [dismiss])

    // Nuovo popup in arrivo dal socket
    useEffect(() => {
        const sock = window.ctSocket
        if (!sock) return

        const onPopup = (data) => {
            const id = ++nextId
            setPopups(prev => [...prev, {
                id,
                evento: data?.evento || '',
                testo: data?.testo || '',
            }])
            // Gia' in focus: parte subito il countdown. Altrimenti resta fisso
            // finche' l'utente non torna sulla pagina (vedi listener 'focus' sotto).
            if (document.hasFocus()) scheduleDismiss(id)
        }

        sock.on('notification:popup', onPopup)
        return () => sock.off('notification:popup', onPopup)
    }, [scheduleDismiss])

    // Focus riacquistato sulla pagina: fa partire il countdown per ogni popup
    // ancora in attesa (quelli gia' schedulati non vengono toccati, vedi guardia in scheduleDismiss).
    useEffect(() => {
        const onFocus = () => setPopups(prev => {
            prev.forEach(p => scheduleDismiss(p.id))
            return prev
        })
        window.addEventListener('focus', onFocus)
        return () => window.removeEventListener('focus', onFocus)
    }, [scheduleDismiss])

    // Pulizia timer pendenti allo smontaggio (mai in pratica, il componente
    // e' montato per tutta la vita della SPA, ma corretto farlo comunque).
    useEffect(() => () => {
        Object.values(timersRef.current).forEach(clearTimeout)
    }, [])

    if (popups.length === 0) return null

    return createPortal(
        <div className={styles.stack}>
            {popups.map(p => (
                <div key={p.id} className={styles.popup} role="status">
                    <i className={`fa-solid fa-bell ${styles.icon}`} />
                    <div className={styles.body}>
                        <span className={styles.label}>{EVENT_LABELS[p.evento] || 'Notifica'}</span>
                        <span className={styles.text}>{p.testo}</span>
                    </div>
                    <button type="button" className={styles.close} onClick={() => dismiss(p.id)} aria-label="Chiudi">
                        &#10005;
                    </button>
                </div>
            ))}
        </div>,
        document.body
    )
}
