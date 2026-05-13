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
import OnlineUsers    from './components/OnlineUsers'
import ChatViewer     from './components/ChatViewer'
import TargetSelector from './components/TargetSelector'

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

// --------------------------------------------------------------------------------------------
// EVENTO ct:ready — segnala ai file PHP che il bundle è pronto
// I file PHP ascoltano questo evento per montare i componenti al momento giusto,
// evitando race condition tra il caricamento del modulo e il parsing del DOM.
// --------------------------------------------------------------------------------------------
document.dispatchEvent(new CustomEvent('ct:ready'))

console.log('[CT] bundle caricato — componenti registrati:', Object.keys(registry))
