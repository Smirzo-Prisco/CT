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

// Importazione di tutti i componenti registrati
import OnlineUsers     from './components/OnlineUsers'
import ChatViewer      from './components/ChatViewer'
import TargetSelector  from './components/TargetSelector'
import PresentiEstesi  from './components/PresentiEstesi'
import MessagesInbox   from './components/MessagesInbox'
import MapClick        from './components/MapClick'
import Forum           from './components/Forum'
import AppRouter, { MIGRATED_PAGES } from './AppRouter'

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

/** Forum (Araldo): sezioni → thread → lettura → composizione */
window.CT.register('Forum', Forum)

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

// --------------------------------------------------------------------------------------------
// EVENTO ct:ready — segnala ai file PHP che il bundle è pronto
// I file PHP ascoltano questo evento per montare i componenti al momento giusto,
// evitando race condition tra il caricamento del modulo e il parsing del DOM.
// --------------------------------------------------------------------------------------------
document.dispatchEvent(new CustomEvent('ct:ready'))

// ---------------------------------------------------------------------------
// PHASE 3.2 — Intercettore click globale per navigazione React
//
// Ascolta tutti i click sull'intera pagina (menu incluso).
// Se il link punta a una pagina migrata, chiama CT.navigate() invece
// di fare un reload PHP completo — la navigazione diventa istantanea.
//
// Funziona solo quando CT.navigate è la versione completa (AppRouter montato).
// Sulle pagine non migrate, CT.navigate fa il reload PHP normale (fallback).
// ---------------------------------------------------------------------------
document.addEventListener('click', function(e) {
    // Risale il DOM fino a trovare il tag <a> (gestisce click su figli dell'<a>)
    const link = e.target.closest('a')
    if (!link || !link.href) return

    try {
        const url = new URL(link.href)

        // Ignora link a domini diversi (link esterni, mailto:, ecc.)
        if (url.hostname !== window.location.hostname) return

        const page = url.searchParams.get('page')

        // Se la pagina di destinazione è migrata, intercetta il click
        if (page && MIGRATED_PAGES.has(page)) {
            e.preventDefault()
            window.CT.navigate(url.pathname + url.search)
        }
    } catch {
        // URL non valida o altro errore: lascia agire il browser normalmente
    }
}, false)

console.log('[CT] bundle caricato — componenti registrati:', Object.keys(registry))
