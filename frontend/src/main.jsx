/**
 * main.jsx — Entry point del bundle React/Vite di Crystal Tokyo
 *
 * Questo file è il punto di ingresso del bundle Vite (frontend/src/).
 * Viene compilato da `npm run build` in themes/crystal/dist/ct-app.js
 * e caricato da footer.inc.php tramite <script type="module">.
 *
 * Funzionalità:
 * - Definisce window.CT, l'interfaccia globale usata dai file PHP per montare
 *   componenti React nei div della pagina senza conoscere i dettagli di Vite.
 * - Registra tutti i componenti disponibili nel registry.
 * - Al termine del caricamento, emette l'evento custom 'ct:ready' sul document,
 *   che i file PHP ascoltano per montare i componenti nel momento giusto.
 *
 * Per aggiungere un nuovo componente:
 *   1. Creare il file in frontend/src/components/
 *   2. Importarlo qui sotto
 *   3. Registrarlo con window.CT.register('NomeComponente', NomeComponente)
 *
 * Uso dal lato PHP (nei file .inc.php):
 *   <div id="mio-container"></div>
 *   <script>
 *     document.addEventListener('ct:ready', function() {
 *       CT.mount('NomeComponente', 'mio-container', { prop1: 'valore' });
 *     });
 *   </script>
 */

import { createRoot } from 'react-dom/client'
import { initIdleDetector } from './utils/idleDetector'
import { initScrollReflowFix } from './utils/scrollReflowFix'
import { initSocket } from './socket'

// Interceptor globale: risposta 401 = sessione scaduta → redirect al login.
// Controlla window.CT_USER: è definito solo quando l'utente è/era autenticato
// (header.inc.php lo inietta solo se $_SESSION['login'] è valorizzato).
// Senza il controllo, i 401 delle API chiamate sulla homepage (utente non loggato)
// causerebbero un reload infinito della pagina di login stessa.
;(function () {
    const _fetch = window.fetch
    window.fetch = async function (...args) {
        const res = await _fetch.apply(this, args)
        if (res.status === 401 && window.CT_USER) {
            window.top.location.href = '/'
        }
        return res
    }
})()

// Importazione di tutti i componenti registrati
import OnlineUsers     from './components/OnlineUsers'
import ChatViewer      from './components/ChatViewer'
import TargetSelector  from './components/TargetSelector'
import PresentiEstesi  from './components/PresentiEstesi'
import MessagesInbox   from './components/MessagesInbox'
import MapClick        from './components/MapClick'
import ChatShell       from './components/ChatShell'
import Forum           from './components/Forum'
import AppRouter, { MIGRATED_PAGES } from './AppRouter'
import ChattingOff     from './components/ChattingOff'
import Meteo           from './components/Meteo'
import Hud             from './components/Hud'
import ChatbotWidget   from './components/ChatbotWidget'
import GestioneBot    from './components/GestioneBot'
import GestioneManutenzione from './components/GestioneManutenzione'
import GestioneMestieri from './components/GestioneMestieri'
import GestioneLuoghiMestiere from './components/GestioneLuoghiMestiere'
import ContattaModerazione from './components/ContattaModerazione'
import SkillDescModal  from './components/SkillDescModal'

/**
 * Registry privato dei componenti.
 * Mappa nome (stringa) → classe/funzione React.
 * Viene popolato tramite window.CT.register().
 */
const registry = {}

/**
 * window.CT — interfaccia pubblica del bundle React.
 * Esposta globalmente per permettere ai file PHP di interagire
 * con i componenti React senza conoscere l'internals di Vite.
 */
window.CT = {

    /**
     * Monta un componente React registrato su un div esistente nel DOM.
     *
     * @param {string} name        - Nome del componente (es. 'ChatViewer')
     * @param {string} containerId - ID del div target nel DOM (es. 'pagina_chat')
     * @param {object} [props={}]  - Props opzionali da passare al componente
     */
    mount(name, containerId, props = {}) {
        const container = document.getElementById(containerId)
        if (!container) {
            console.warn(`[CT] div #${containerId} non trovato nel DOM`)
            return
        }
        const Component = registry[name]
        if (!Component) {
            console.warn(`[CT] componente "${name}" non registrato`)
            return
        }
        // Crea un root React 18 e renderizza il componente con le props fornite
        createRoot(container).render(<Component {...props} />)
    },

    /**
     * Registra un componente nel registry rendendolo disponibile per il mount.
     *
     * @param {string}   name      - Chiave identificativa del componente
     * @param {Function} component - Funzione/classe React del componente
     */
    register(name, component) {
        registry[name] = component
    },
}

// --------------------------------------------------------------------------------------------
// REGISTRAZIONE DEI COMPONENTI
// Aggiungere qui ogni nuovo componente creato in frontend/src/components/
// --------------------------------------------------------------------------------------------

/** Box utenti online nella colonna laterale — si aggiorna via socket users:update */
window.CT.register('OnlineUsers', OnlineUsers)

/** Visualizzatore messaggi chat di gioco — si aggiorna via socket chat:update */
window.CT.register('ChatViewer', ChatViewer)

/** Selezione bersagli nel pannello skill/armi — si aggiorna via socket users:update */
window.CT.register('TargetSelector', TargetSelector)

/** Lista completa presenti con avatar/razza/famiglia — si aggiorna via socket users:update */
window.CT.register('PresentiEstesi', PresentiEstesi)

/** Inbox messaggi privati: lista conversazioni + thread + risposta — si aggiorna via socket dm:update */
window.CT.register('MessagesInbox', MessagesInbox)

/** Mappa di gioco con hotspot zone, popup stanze e conteggi online real-time */
window.CT.register('MapClick', MapClick)

/** Shell completa della chat di gioco: form, pannello GDR, modali role (Phase 4b) */
window.CT.register('ChatShell', ChatShell)

/** Forum (Araldo): sezioni → thread → lettura → composizione */
window.CT.register('Forum', Forum)

/** Chattina off integrata in fondo alla colonna sinistra — si aggiorna via socket chatoff:update */
window.CT.register('ChattingOff', ChattingOff)

/** Box meteo nella colonna destra — toggle oggi/ieri */
window.CT.register('Meteo', Meteo)

/** HUD immersivo: due anelli (luogo/personaggio) + topbar, sostituisce le colonne laterali */
window.CT.register('Hud', Hud)

/** Chatbot AI floating widget — bottom-right, 5 domande/giorno, max 500 char */
window.CT.register('ChatbotWidget', ChatbotWidget)

/** Pannello admin gestione bot (sesso='b'): lista, schedule, DM */
window.CT.register('GestioneBot', GestioneBot)

/** Pannello admin manutenzione DB: pulizia log/chat/messaggi, personaggi assenti/cancellati, blacklist */
window.CT.register('GestioneManutenzione', GestioneManutenzione)

/** Pannello admin gestione mestieri e ruoli: lista, form CRUD, upload immagini */
window.CT.register('GestioneMestieri', GestioneMestieri)

/** Pannello admin associazione mestiere -> luogo (sostituisce gli array hardcoded) */
window.CT.register('GestioneLuoghiMestiere', GestioneLuoghiMestiere)

/** Form + cronologia richieste di moderazione, con pannello staff integrato */
window.CT.register('ContattaModerazione', ContattaModerazione)

/** Modale globale descrizione abilità — pilotato via window.CT.openSkillDesc() */
window.CT.register('SkillDescModal', SkillDescModal)

/**
 * AppRouter — Phase 3.1: router client-side per le pagine migrate.
 * Legge ?page=X dalla URL e renderizza il componente giusto senza reload.
 * Espone window.CT.navigate() per la navigazione tra pagine migrate.
 */
window.CT.register('AppRouter', AppRouter)

/**
 * CT.navigate — naviga a una URL.
 * Se la pagina di destinazione è migrata: pushState + re-render React.
 * Se non è migrata: reload PHP (window.top.location.href).
 * Viene sovrascritto da AppRouter quando montato (versione completa).
 * Questa è la versione fallback per pagine che non montano AppRouter.
 *
 * @param {string} url - URL di destinazione
 */
window.CT.navigate = (url) => { window.top.location.href = url }

/**
 * CT.openSkillDesc — apre il modale globale di descrizione abilità
 * (SkillDescModal, montato una volta in header.inc.php).
 * Chiamabile sia da JS legacy (onclick nelle pagine .inc.php non ancora
 * migrate) sia da componenti React.
 *
 * @param {number|string|{nome: string, descrizione: string}} arg
 *   Un id abilità (fa fetch di nome+descrizione da api_global.php),
 *   oppure un oggetto già con nome/descrizione noti (nessun fetch).
 */
window.CT.openSkillDesc = (arg) => {
    window.dispatchEvent(new CustomEvent('ct:skill-desc-open', {
        detail: (typeof arg === 'object' && arg !== null) ? arg : { id: arg },
    }))
}

// --------------------------------------------------------------------------------------------
// EVENTO ct:ready — segnala ai file PHP che il bundle è pronto
// I file PHP ascoltano questo evento per montare i componenti al momento giusto,
// evitando race condition tra il caricamento del modulo e il parsing del DOM.
// --------------------------------------------------------------------------------------------
initSocket()
document.dispatchEvent(new CustomEvent('ct:ready'))
initIdleDetector()
initScrollReflowFix()

// ---------------------------------------------------------------------------
// PHASE 3.2 / 4b — Intercettore click globale per navigazione React
//
// Intercetta i click su link che puntano a pagine migrate (page=X) o a
// cambi stanza (dir=X), chiama CT.navigate() invece del reload PHP.
//
// Funziona solo quando CT.navigate è la versione completa (AppRouter montato).
// Sulle pagine non migrate, CT.navigate fa il reload PHP normale (fallback).
//
// true = capture phase: si attiva PRIMA di stopPropagation.
// Necessario perché left-right_frames.php chiama e.stopPropagation() sul click
// della colonna sinistra per il toggle mobile, bloccando il bubbling normale.
// ---------------------------------------------------------------------------
document.addEventListener('click', function(e) {
    const link = e.target.closest('a')
    if (!link || !link.href) return

    // Blocca la navigazione default solo per href="#" (non e' un link reale).
    // e.preventDefault() impedisce al browser di aggiungere "#" all'URL (che può scatenare
    // popstate e uscire dalla stanza), ma NON blocca gli onclick/handler già attaccati al link.
    // href="javascript:..." NON va bloccato qui: a differenza di "#" non tocca mai la URL/history,
    // e per i link legacy senza un handler onclick separato E' l'unico modo in cui l'azione parte
    // (bloccarne il default la disattiva silenziosamente, es. i pulsanti "Indietro" con
    // href="javascript:history.back()" nelle pagine gestione_*.inc.php).
    const rawHref = link.getAttribute('href') ?? ''
    if (!rawHref || rawHref === '#' || rawHref.startsWith('#')) {
        e.preventDefault()
        return
    }

    try {
        const url  = new URL(link.href)

        // Ignora link esterni (domini diversi, mailto:, ecc.)
        if (url.hostname !== window.location.hostname) return

        const page = url.searchParams.get('page')
        const dir  = url.searchParams.get('dir')

        // Intercetta se: pagina React migrata (page=X) o cambio stanza (dir=X)
        const isMigratedPage = page && MIGRATED_PAGES.has(page)
        const isDirNav       = dir !== null   // dir=X (>= 0 = stanza, < 0 = mappa)

        if (isMigratedPage || isDirNav) {
            e.preventDefault()
            window.CT.navigate(url.pathname + url.search)
        }
    } catch {
        // URL non valida: lascia agire il browser normalmente
    }
}, true)

// ---------------------------------------------------------------------------
// AUTO-GROW TEXTAREA — funziona su desktop e mobile
// Espande la textarea con il contenuto eliminando il bisogno del drag handle
// (inutilizzabile su touch). Usa event delegation su document.
// ---------------------------------------------------------------------------
function autoGrow(el) {
    el.style.height = 'auto'
    el.style.height = el.scrollHeight + 'px'
}
// Crescita al typing
document.addEventListener('input', e => {
    if (e.target.tagName === 'TEXTAREA') autoGrow(e.target)
}, true)
// Inizializza textarea già nel DOM al caricamento (form PHP pre-compilati)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea').forEach(autoGrow)
})
// Inizializza textarea aggiunte dopo (componenti React montati dopo DOMContentLoaded)
new MutationObserver(mutations => {
    for (const m of mutations)
        for (const node of m.addedNodes)
            if (node.nodeType === 1) {
                if (node.tagName === 'TEXTAREA') autoGrow(node)
                node.querySelectorAll?.('textarea').forEach(autoGrow)
            }
}).observe(document.body, { childList: true, subtree: true })

console.log('[CT] bundle caricato — componenti registrati:', Object.keys(registry))
