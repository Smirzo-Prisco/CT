/**
 * idleDetector.js — Rileva inattività utente e aggiorna lo stato "assente" sul server.
 *
 * L'utente è considerato assente se:
 *   - non interagisce con la pagina per IDLE_AFTER_MS (default 60s)
 *   - oppure il documento è nascosto (altra scheda / finestra minimizzata)
 *
 * Quando diventa assente:
 *   - chiama op=setIdle (assente=1) sul server
 *   - emette window event 'ct:idle' → OnlineUsers.jsx mette in pausa il heartbeat
 *   - avvia un ping lento (IDLE_PING_MS) per tenere ultimo_refresh aggiornato
 *     ed evitare che il personaggio scompaia dai presenti
 *
 * Quando torna attivo:
 *   - chiama op=setIdle (assente=0) sul server
 *   - emette window event 'ct:active' → OnlineUsers.jsx riprende il heartbeat
 *   - ferma il ping lento
 *
 * @module idleDetector
 */

/** Millisecondi di inattività prima di marcare l'utente come assente */
const IDLE_AFTER_MS = 60_000

/** Intervallo ping quando l'utente è assente (mantiene ultimo_refresh vivo) */
const IDLE_PING_MS  = 180_000

/** eventi DOM che segnalano attività */
const ACTIVITY_EVENTS = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll']

let idleTimer     = null   // setTimeout → scatta quando si diventa assenti
let idlePing      = null   // setInterval → ping lento mentre si è assenti
let currentlyIdle = false  // stato corrente

/**
 * Chiama il server per aggiornare il flag assente.
 * Aggiorna anche ultimo_refresh per evitare che il pg sparisca dai presenti.
 *
 * @param {boolean} idle
 */
function notifyServer(idle) {
    fetch('/pages/api_map.php?op=setIdle', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ idle }),
    }).catch(() => {})
}

/**
 * Aggiorna lo stato assente/presente, notifica server e componenti.
 *
 * @param {boolean} idle
 */
function setIdleState(idle) {
    if (idle === currentlyIdle) return
    currentlyIdle = idle

    notifyServer(idle)
    window.dispatchEvent(new CustomEvent(idle ? 'ct:idle' : 'ct:active'))

    if (idle) {
        // Ping lento per tenere ultimo_refresh vivo ogni 3 minuti
        idlePing = setInterval(() => notifyServer(true), IDLE_PING_MS)
    } else {
        clearInterval(idlePing)
        idlePing = null
    }
}

/**
 * Resetta il timer di inattività.
 * Chiamato ad ogni evento di attività DOM.
 */
function onActivity() {
    clearTimeout(idleTimer)
    if (currentlyIdle) setIdleState(false)
    idleTimer = setTimeout(() => setIdleState(true), IDLE_AFTER_MS)
}

/**
 * Inizializza il rilevamento inattività.
 * Va chiamato una sola volta da main.jsx.
 */
export function initIdleDetector() {
    ACTIVITY_EVENTS.forEach(e =>
        document.addEventListener(e, onActivity, { passive: true })
    )

    // Scheda nascosta → assente immediatamente
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearTimeout(idleTimer)
            setIdleState(true)
        } else {
            onActivity()
        }
    })

    // Avvia il timer dal momento del caricamento
    onActivity()
}
