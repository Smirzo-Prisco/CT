import { createRoot } from 'react-dom/client'

/**
 * Registry dei componenti React disponibili.
 * Ogni componente viene aggiunto qui man mano che migriamo
 * dalla vecchia implementazione PHP+CDN a questo bundle.
 */
const registry = {}

/**
 * window.CT — interfaccia globale usata dai file PHP per interagire
 * con il bundle React senza dover conoscere i dettagli di Vite.
 *
 * Uso da PHP:
 *   <div id="pagina_chat"></div>
 *   <script>CT.mount('ChatViewer', 'pagina_chat', { luogo: 5 })</script>
 */
window.CT = {
  /**
   * Monta un componente registrato su un div esistente nel DOM.
   * @param {string} name       - Nome del componente (es. 'ChatViewer')
   * @param {string} containerId - ID del div target (es. 'pagina_chat')
   * @param {object} props      - Props da passare al componente
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
    createRoot(container).render(<Component {...props} />)
  },

  /**
   * Registra un componente nel registry.
   * Ogni file di componente chiama questo all'importazione.
   */
  register(name, component) {
    registry[name] = component
  },
}

// Importa qui i componenti man mano che vengono migrati.
// Ogni import aggiunge automaticamente il componente al registry.
// Esempio (verrà aggiunto nello step 2.2):
// import './components/OnlineUsers'

console.log('[CT] bundle caricato — componenti registrati:', Object.keys(registry))
