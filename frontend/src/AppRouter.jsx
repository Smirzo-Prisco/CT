/**
 * AppRouter.jsx — Phase 3.1: routing client-side per le pagine migrate a React
 *
 * Questo componente legge i parametri della URL corrente e renderizza
 * il componente React appropriato, senza ricaricare la pagina.
 *
 * Navigazione:
 *   - Alla prima visita la pagina viene servita normalmente da PHP
 *   - Navigando tra pagine migrate (CT.navigate), solo il componente React
 *     viene sostituito — nessun reload PHP, nessun flash di schermo
 *   - Back/forward del browser funzionano tramite popstate
 *   - Pagine non migrate ricevono un normale reload (window.top.location.href)
 *
 * CSS specifici per pagina:
 *   Quando si naviga client-side verso una nuova pagina, i suoi CSS vengono
 *   iniettati dinamicamente nel <head> (solo se non già presenti).
 *
 * Pagine attualmente migrate (Phase 3.1):
 *   - forum
 *   - messages_center
 *   - presenti_estesi
 *
 * Pagine che richiedono ancora reload PHP (Phase 3.2+):
 *   - mappaclick (aggiorna ultima_mappa nel DB)
 *   - frame_chat / dir=X (aggiorna ultimo_luogo nel DB)
 *   - tutte le altre
 *
 * Montaggio: via ct:ready su #ct-app-content (inserito nelle pagine migrate).
 * window.CT.navigate() viene esposto globalmente per i link nelle pagine migrate.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

import Forum          from './components/Forum'
import MessagesInbox  from './components/MessagesInbox'
import PresentiEstesi from './components/PresentiEstesi'

// ---------------------------------------------------------------------------
// REGISTRAZIONE ROUTES
// Mappa nome pagina → { component, css[] }
// Aggiungere qui le nuove pagine man mano che vengono migrate.
// ---------------------------------------------------------------------------

/**
 * ROUTES — pagine migrate con il loro componente React e i CSS da caricare.
 * I CSS vengono iniettati dinamicamente alla prima navigazione verso la pagina.
 */
const ROUTES = {
    forum: {
        component: Forum,
        css: [
            '/themes/crystal/bacheca.css',
            '/themes/crystal/forum.css',
            '/themes/crystal/presenti.css',
        ],
    },
    messages_center: {
        component: MessagesInbox,
        css: [
            '/pages/mex_privati/new_sms.css',
        ],
    },
    presenti_estesi: {
        component: PresentiEstesi,
        css: [
            '/themes/crystal/anagrafe.css',
        ],
    },
}

/** Nomi delle pagine migrate — usati da CT.navigate per decidere se reload o pushState */
export const MIGRATED_PAGES = new Set(Object.keys(ROUTES))

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

/**
 * Inietta un CSS nel <head> se non è già presente.
 * Evita di caricare lo stesso file più volte durante la sessione.
 *
 * @param {string} href - Path assoluto del file CSS
 */
function injectCSS(href) {
    if (!document.querySelector(`link[href="${href}"]`)) {
        const link = document.createElement('link')
        link.rel  = 'stylesheet'
        link.href = href
        document.head.appendChild(link)
    }
}

/**
 * Legge i parametri della URL corrente.
 * @returns {{ page: string|null }} Parametri rilevanti per il routing
 */
function readParams() {
    const p = new URLSearchParams(window.location.search)
    return { page: p.get('page') }
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

/**
 * @param {boolean} props.isStaff - Passato dalla sessione PHP via CT.mount()
 */
export default function AppRouter({ isStaff = false }) {

    /** Parametri URL correnti — cambiano su popstate o CT.navigate */
    const [params, setParams] = useState(readParams)

    // Aggiorna i parametri quando l'utente usa back/forward del browser
    useEffect(() => {
        const onPopState = () => setParams(readParams())
        window.addEventListener('popstate', onPopState)
        return () => window.removeEventListener('popstate', onPopState)
    }, [])

    /**
     * Naviga a una nuova URL.
     * - Se la pagina è migrata: pushState + re-render React (nessun reload)
     * - Se la pagina non è migrata: reload PHP tradizionale
     *
     * @param {string} url - URL di destinazione (relativa o assoluta)
     */
    const navigate = useCallback((url) => {
        const target    = new URL(url, window.location.href)
        const targetPage = target.searchParams.get('page')

        if (targetPage && MIGRATED_PAGES.has(targetPage)) {
            // Navigazione React: cambia URL senza reload
            window.history.pushState({}, '', url)
            setParams(readParams())
        } else {
            // Pagina non migrata: reload PHP (target_top per uscire dai frame se necessario)
            window.top.location.href = url
        }
    }, [])

    // Espone CT.navigate globalmente — usato dai link nei componenti migrati
    useEffect(() => {
        if (window.CT) window.CT.navigate = navigate
    }, [navigate])

    // -----------------------------------------------------------------------
    // RENDERING
    // -----------------------------------------------------------------------

    const route = params.page ? ROUTES[params.page] : null

    // Pagina non migrata: AppRouter non renderizza nulla
    // (il PHP ha già renderizzato il contenuto via inc.php)
    if (!route) return null

    // Inietta i CSS della pagina se non già presenti (navigazione client-side)
    route.css.forEach(injectCSS)

    const Component = route.component

    return <Component isStaff={isStaff} />
}
