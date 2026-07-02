/**
 * Gestione.jsx — Pannello di gestione staff (Phase SPA)
 *
 * Fetch: api_gestione.php?op=menu
 * Il backend filtra le card in base ai permessi della sessione corrente:
 * admin, master, moderatore, capogilda, capomestiere, custode, magic.
 *
 * I link alle sotto-pagine (gestione_personaggio, gestione_gilde, ecc.) causano
 * ancora un reload PHP perché quelle pagine non sono ancora migrate al SPA.
 *
 * Layout: card-based dashboard, stessa struttura del blocco "nuovo" in
 * gestione.inc.php (usa uffici_nuovi.css per gli stili).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

/**
 * Naviga senza reload quando la pagina di destinazione è già migrata alla SPA
 * (window.CT.navigate lo capisce da solo confrontando MIGRATED_PAGES); per
 * tutto il resto (es. gestione.php, tool di staff non ancora migrati) fa comunque
 * il reload tradizionale — nessuna regressione, ma da qui in avanti ogni nuova
 * pagina migrata smette automaticamente di ricaricare, senza toccare questo file.
 */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

/**
 * Singola card della dashboard: icona Font Awesome + lista voci.
 * Le voci con url='#' sono solo informative (es. statistiche) e non navigano.
 */
function GestioneCard({ card }) {
    return (
        <div className="card">
            <i className={`fa-solid ${card.icon}`}></i>
            <ul className="sub-menu">
                {card.voci.map((voce, i) => (
                    <li key={i}>
                        {voce.url === '#'
                            ? <span>{voce.label}</span>
                            : <a href={voce.url} onClick={e => { e.preventDefault(); navigate(voce.url) }}>{voce.label}</a>
                        }
                    </li>
                ))}
            </ul>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function Gestione() {
    const [menu, setMenu]   = useState(null)
    const [error, setError] = useState(null)

    useEffect(() => {
        fetch('/pages/api_gestione.php?op=menu')
            .then(r => r.json())
            .then(d => {
                if (d.success) setMenu(d.menu)
                else           setError(d.message ?? 'Errore nel caricamento del pannello')
            })
            .catch(() => setError('Errore di rete'))
    }, [])

    if (error) {
        return (
            <div className="pagina_gestione">
                <div className="error">{error}</div>
            </div>
        )
    }

    if (!menu) {
        return (
            <div className="pagina_gestione">
                <div>Caricamento pannello…</div>
            </div>
        )
    }

    return (
        <div className="pagina_gestione">
            <header>⚙️ Pannello di Gestione</header>
            <div className="dashboard">
                {menu.map(card => (
                    <GestioneCard key={card.key} card={card} />
                ))}
            </div>
        </div>
    )
}
