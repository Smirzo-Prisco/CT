import { createRoot } from 'react-dom/client'
import OnlineUsers from './components/OnlineUsers'
import ChatViewer from './components/ChatViewer'

const registry = {}

window.CT = {
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

  register(name, component) {
    registry[name] = component
  },
}

window.CT.register('OnlineUsers', OnlineUsers)
window.CT.register('ChatViewer', ChatViewer)

document.dispatchEvent(new CustomEvent('ct:ready'))
console.log('[CT] bundle caricato — componenti registrati:', Object.keys(registry))
