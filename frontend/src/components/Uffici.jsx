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
            { label: 'Abilità',   icon: 'fa-bolt',          url: 'main.php?page=mercato_abilita_atarashi' },
            { label: 'Parametri', icon: 'fa-chart-bar',     url: 'main.php?page=incremento_parametri'     },
            { label: 'Razze',     icon: 'fa-dragon',        url: 'main.php?page=scegli_razza'             },
            { label: 'Gilde',     icon: 'fa-shield-halved', url: 'main.php?page=servizi_mestieri&solo_gilde=1' },
            { label: 'Mestieri',  icon: 'fa-hammer',        url: 'main.php?page=scegli_mestiere'          },
        ],
    },
    {
        key: 'servizi',
        label: 'Servizi',
        items: [
            { label: 'Anagrafe',         icon: 'fa-id-card',  url: 'main.php?page=anagrafe'                   },
            { label: 'Elenco Staff',     icon: 'fa-users',    url: 'main.php?page=elenco_staff'               },
            { label: 'Patrocinio Volti', icon: 'fa-portrait', url: 'main.php?page=elenco_volti'               },
            { label: 'Albergo',          icon: 'fa-bed',      url: 'main.php?page=servizi_prenotazioni_prova' },
        ],
    },
    {
        key: 'account',
        label: 'Account',
        items: [
            { label: 'Cambio Password',          icon: 'fa-key',                url: 'main.php?page=user_cambio_pass'      },
            { label: 'Contatta la moderazione',  icon: 'fa-envelope-open-text', url: 'main.php?page=contatta_moderazione'  },
            { label: 'Preferenze',               icon: 'fa-sliders',            url: 'main.php?page=preferenze'            },
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
