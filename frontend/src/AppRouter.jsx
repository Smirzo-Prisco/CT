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
 * Pagine attualmente migrate:
 *   - forum, messages_center, presenti_estesi, mappaclick
 *   - dir=X  ← Phase 4b: cambio stanza via api_map.php?op=move (POST),
 *              poi renderizza ChatShell. dir < 0 = torna alla mappa.
 *
 * Pagine che richiedono ancora reload PHP:
 *   - tutto il resto (gestione, scheda, uffici, ecc.)
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
import MapClick       from './components/MapClick'
import ChatShell      from './components/ChatShell'
import Scheda         from './components/Scheda'
import SchedaSub      from './components/SchedaSub'
import SchedaSkills   from './components/SchedaSkills'
import SchedaTrans    from './components/SchedaTrans'
import SchedaModifica from './components/SchedaModifica'
import SchedaAffetti  from './components/SchedaAffetti'
import SchedaPunti    from './components/SchedaPunti'
import SchedaEquip    from './components/SchedaEquip'
import SchedaOggetti  from './components/SchedaOggetti'
import Gestione             from './components/Gestione'
import Uffici               from './components/Uffici'
import RoleRecap            from './components/RoleRecap'
import ElencoStaff          from './components/ElencoStaff'
import ElencoVolti          from './components/ElencoVolti'
import Anagrafe             from './components/Anagrafe'
import CambioPass           from './components/CambioPass'
import ScegliMestiere       from './components/ScegliMestiere'
import MercatoAbilita       from './components/MercatoAbilita'
import IncrementoParametri  from './components/IncrementoParametri'
import PrenotazioniProva    from './components/PrenotazioniProva'
import ServiziGilde         from './components/ServiziGilde'
import Statuto              from './components/Statuto'
import Documentazione       from './components/Documentazione'
import ChatHelp             from './components/ChatHelp'

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
            '/themes/crystal/messages_center.css',
        ],
    },
    presenti_estesi: {
        component: PresentiEstesi,
        css: [
            '/themes/crystal/anagrafe.css',
        ],
    },
    mappaclick: {
        component: MapClick,
        css: [
            '/themes/crystal/mappa_principale.css',
        ],
    },
    scheda: {
        component: Scheda,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_storia: {
        component: SchedaSub,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_dice: {
        component: SchedaSub,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_off: {
        component: SchedaSub,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_skills: {
        component: SchedaSkills,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_trans: {
        component: SchedaTrans,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_modifica: {
        component: SchedaModifica,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css', '/themes/crystal/mestieri.css'],
    },
    scheda_affetti: {
        component: SchedaAffetti,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css', '/themes/crystal/scheda_affetto.css', '/themes/crystal/volti.css'],
    },
    scheda_px: {
        component: SchedaPunti,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_px_shin: {
        component: SchedaPunti,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_px_mestiere: {
        component: SchedaPunti,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css'],
    },
    scheda_equip: {
        component: SchedaEquip,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css', '/themes/crystal/scheda_equip.css'],
    },
    scheda_oggetti: {
        component: SchedaOggetti,
        css: ['/themes/crystal/scheda.css','/themes/crystal/scheda_menu.css', '/themes/crystal/scheda_equip.css'],
    },
    gestione: {
        component: Gestione,
        css: [
            '/themes/crystal/uffici_nuovi.css',
        ],
    },
    uffici: {
        component: Uffici,
        css: [
            '/themes/crystal/uffici_layout.css',
        ],
    },
    role_recap: {
        component: RoleRecap,
        css: [],
    },
    elenco_staff: {
        component: ElencoStaff,
        css: ['/themes/crystal/famiglie.css'],
    },
    elenco_volti: {
        component: ElencoVolti,
        css: ['/themes/crystal/volti.css'],
    },
    anagrafe: {
        component: Anagrafe,
        css: ['/themes/crystal/famiglie.css'],
    },
    user_cambio_pass: {
        component: CambioPass,
        css: [],
    },
    scegli_mestiere: {
        component: ScegliMestiere,
        css: ['/themes/crystal/famiglie.css'],
    },
    mercato_abilita_atarashi: {
        component: MercatoAbilita,
        css: [],
    },
    incremento_parametri: {
        component: IncrementoParametri,
        css: ['/themes/crystal/incremento_parametri.css'],
    },
    servizi_prenotazioni_prova: {
        component: PrenotazioniProva,
        css: ['/themes/crystal/albergo.css'],
    },
    servizi_gilde: {
        component: ServiziGilde,
        css: ['/themes/crystal/famiglie.css'],
    },
    statuto_main: {
        component: Statuto,
        css: [
            '/themes/crystal/statuto_menu.css',
            '/themes/crystal/statuto.css',
        ],
    },
    documentazione_main: {
        component: Documentazione,
        css: [
            '/themes/crystal/documentazione_menu.css',
            '/themes/crystal/documentazione_spa.css',
        ],
    },
    chat_help: {
        component: ChatHelp,
        css: ['/themes/crystal/chat_help.css'],
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
 * Legge i parametri rilevanti dalla URL corrente.
 * `dir` è presente quando l'utente entra in una stanza (>= 0) o torna
 * alla mappa (< 0) tramite main.php?dir=X.
 */
function readParams() {
    const p      = new URLSearchParams(window.location.search)
    const dirRaw = p.get('dir')
    return {
        page: p.get('page'),
        dir:  dirRaw !== null ? parseInt(dirRaw, 10) : null,
        to:   p.get('to') ?? null,
    }
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
     * Naviga a una nuova URL — SPA o PHP reload a seconda della destinazione.
     *
     * Casi gestiti:
     *   ?page=X migrato   → pushState + re-render React
     *   ?dir=X (X >= 0)   → chiama api_map.php?op=move per aggiornare DB/sessione,
     *                        poi pushState + renderizza ChatShell
     *   ?dir=X (X < 0)    → tornare alla mappa (trattato come ?page=mappaclick)
     *   tutto il resto    → reload PHP tradizionale
     *
     * La funzione è async perché la chiamata move deve completarsi prima
     * di aggiornare la URL (altrimenti ChatShell legge una sessione stale).
     *
     * @param {string} url - URL di destinazione (relativa o assoluta)
     */
    const navigate = useCallback(async (url) => {
        const target     = new URL(url, window.location.href)
        const targetPage = target.searchParams.get('page')
        const targetDir  = target.searchParams.get('dir')

        // ── Pagina React migrata ──────────────────────────────────────────
        if (targetPage && MIGRATED_PAGES.has(targetPage)) {
            window.history.pushState({}, '', url)
            setParams(readParams())
            return
        }

        // ── Cambio stanza (dir=X) ─────────────────────────────────────────
        if (targetDir !== null) {
            const dir = parseInt(targetDir, 10)

            // dir < 0 → torna alla mappa (equivale a ?page=mappaclick)
            if (dir < 0) {
                window.history.pushState({}, '', 'main.php?page=mappaclick')
                setParams(readParams())
                return
            }

            // dir >= 0 → entra in stanza: aggiorna DB+sessione via API prima
            // di renderizzare ChatShell (altrimenti op=shell legge luogo sbagliato)
            try {
                const resp = await fetch('/pages/api_map.php?op=move', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ dir }),
                })
                const data = await resp.json()
                if (data.success) {
                    if (window.CT_USER) window.CT_USER.luogo = dir
                    // Aggiorna le room socket: la connessione esistente era
                    // iscritta a chat:${old} — deve passare a chat:${dir}
                    window.ctSocket?.emit('room:change', { newLuogo: dir })
                    window.history.pushState({}, '', url)
                    setParams(readParams())
                } else {
                    // Stanza privata o errore: fallback PHP
                    window.top.location.href = url
                }
            } catch {
                window.top.location.href = url
            }
            return
        }

        // ── Pagina non migrata: reload PHP ────────────────────────────────
        window.top.location.href = url
    }, [])

    // Espone CT.navigate globalmente — usato dai link nei componenti migrati
    useEffect(() => {
        if (window.CT) window.CT.navigate = navigate
    }, [navigate])

    // -----------------------------------------------------------------------
    // RENDERING
    // -----------------------------------------------------------------------

    const route  = params.page ? ROUTES[params.page] : null
    const isChat = params.dir !== null && !isNaN(params.dir) && params.dir >= 0

    // Pagina non migrata: AppRouter non renderizza nulla.
    // Il PHP ha già renderizzato il contenuto via inc.php.
    if (!route && !isChat) return null

    // ── Stanza chat (dir >= 0) ────────────────────────────────────────────
    // key={params.dir}: forza unmount+remount di ChatShell ad ogni cambio stanza,
    // così il nuovo mount chiama op=shell con la sessione aggiornata dal move.
    if (isChat) {
        injectCSS('/themes/crystal/mestieri.css')
        return <ChatShell key={params.dir} />
    }

    // ── Pagina React migrata (page=X) ─────────────────────────────────────
    route.css.forEach(injectCSS)
    const Component = route.component
    return <Component isStaff={isStaff} toPg={params.to} />
}
