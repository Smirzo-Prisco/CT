/**
 * Uffici.jsx — Pagina Uffici (SPA)
 *
 * Lista categorizzata dei servizi disponibili al personaggio,
 * divisi in tre sezioni: Personaggio, Servizi, Account.
 * Nessuna fetch — tutti i link sono noti a build-time.
 *
 * @author Crystal Tokyo Dev
 */

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ---------------------------------------------------------------------------
// DATI STATICI
// ---------------------------------------------------------------------------

const SECTIONS = [
    {
        key: 'personaggio',
        label: 'Personaggio',
        items: [
            { label: 'Abilità',         icon: 'fa-bolt',      url: 'main.php?page=mercato_abilita_atarashi' },
            { label: 'Parametri',        icon: 'fa-chart-bar', url: 'main.php?page=incremento_parametri'     },
            { label: 'Anagrafe',         icon: 'fa-id-card',   url: 'main.php?page=anagrafe'                 },
            { label: 'Scegli Razza',     icon: 'fa-dragon',    url: 'main.php?page=scegli_razza'             },
            { label: 'Scegli Mestiere',  icon: 'fa-hammer',    url: 'main.php?page=scegli_mestiere'          },
        ],
    },
    {
        key: 'servizi',
        label: 'Servizi',
        items: [
            { label: 'Albergo',          icon: 'fa-bed',      url: 'main.php?page=servizi_prenotazioni_prova' },
            { label: 'Patrocinio Volti', icon: 'fa-portrait', url: 'main.php?page=elenco_volti'               },
            { label: 'Elenco Staff',     icon: 'fa-users',    url: 'main.php?page=elenco_staff'               },
            { label: 'Gilde',            icon: 'fa-shield-halved', url: 'main.php?page=servizi_mestieri&solo_gilde=1' },
        ],
    },
    {
        key: 'account',
        label: 'Account',
        items: [
            { label: 'Cambio Password', icon: 'fa-key', url: 'main.php?page=user_cambio_pass' },
        ],
    },
]

// ---------------------------------------------------------------------------
// COMPONENTE
// ---------------------------------------------------------------------------

export default function Uffici() {
    return (
        <div className="uffici-page">

            <div className="uffici-page__title">
                Uffici
            </div>

            {SECTIONS.map(section => (
                <div key={section.key} className="uffici-page__section">
                    <div className="uffici-page__section-title">{section.label}</div>

                    {section.items.map(item => (
                        <a
                            key={item.url}
                            href={item.url}
                            className="uffici-page__item"
                            onClick={e => { e.preventDefault(); navigate(item.url) }}
                        >
                            <i className={`fas ${item.icon}`} />
                            <span>{item.label}</span>
                        </a>
                    ))}
                </div>
            ))}

        </div>
    )
}
