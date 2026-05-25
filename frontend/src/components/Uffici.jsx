/**
 * Uffici.jsx — Pagina Uffici (Phase SPA)
 *
 * Componente statico: nessuna fetch, nessun DB — tutti i link sono noti
 * a build-time. Tre colonne con sfondo immagine (box_uffici.png):
 *   - Utilità  : servizi generali (anagrafe, banca, cambio password…)
 *   - Strumenti: azioni sul personaggio (lavoro, oggetti, corrente…)
 *   - Potenziamento: acquisto abilità, talenti, incrementi, level up
 *
 * I link puntano a pagine PHP non ancora migrate: causano un reload PHP.
 * Il CSS è caricato da AppRouter (injectCSS) per la navigazione SPA e
 * da uffici.inc.php (link tag) per il primo caricamento PHP.
 *
 * @author Crystal Tokyo Dev
 */

// ---------------------------------------------------------------------------
// DATI STATICI DELLE COLONNE
// ---------------------------------------------------------------------------

/**
 * Definizione delle tre colonne della pagina Uffici.
 * Ogni colonna ha un'immagine header e una lista di link.
 * Le immagini usano path assoluti (relativi al web root).
 */
const COLONNE = [
    {
        key: 'utilita',
        img: '/themes/crystal/imgs/uffici/uffici_utilita.png',
        alt: 'Utilità',
        class: 'utilita-box',
        links: [
            { label: 'ALBERGO', url: 'main.php?page=servizi_prenotazioni_prova' },
            { label: 'ANAGRAFE', url: 'main.php?page=anagrafe' },
            // { label: 'BANCA', url: 'main.php?page=servizi_banca' },
            // { label: 'MERCATO', url: 'main.php?page=servizi_mercato' },
            { label: 'CAMBIO PASSWORD', url: 'main.php?page=user_cambio_pass' },
            // { label: 'LISTA SPIRITI',       url: 'main.php?page=user_razze' },
            { label: 'PATROCINIO VOLTI', url: 'main.php?page=elenco_volti' },
            { label: 'STAFF', url: 'main.php?page=elenco_staff' },
        ],
    },
    {
        key: 'strumenti',
        img: '/themes/crystal/imgs/uffici/uffici_strumenti.png',
        alt: 'Strumenti',
        class: 'strumenti-box',
        links: [
            // { label: 'SCEGLI LAVORO',                      url: 'main.php?page=scegli_lavoro' },
            { label: 'CREA OGGETTO', url: 'main.php?page=oggetto_aggiungi_richiesta' },
            // { label: 'POTENZIA UMANO', url: 'main.php?page=scegli_umano' },
            // { label: 'SCEGLI CORRENTE E VIA',              url: 'main.php?page=scegli_inclinazione' },
            { label: 'SCEGLI MESTIERE', url: 'main.php?page=scegli_mestiere' },
        ],
    },
    {
        key: 'potenziamento',
        img: '/themes/crystal/imgs/uffici/uffici_potenziamento.png',
        alt: 'Potenziamento',
        class: 'potenziamento-box',
        links: [
            { label: 'ABILITÀ', url: 'main.php?page=mercato_abilita_atarashi' },
            // { label: 'TALENTI',              url: 'main.php?page=mercato_talento' },
            { label: 'PARAMETRI', url: 'main.php?page=incremento_parametri' },
            // { label: 'AUMENTO SPIRITO',      url: 'main.php?page=level_up' },
        ],
    },
]

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function Uffici() {
    return (
        <div className="uffici-container">
            {COLONNE.map(col => (
                <div key={col.key} className="ufficio-colonna">

                    {/* Immagine intestazione colonna */}
                    <div className="ufficio-header">
                        <img
                            src={col.img}
                            alt={col.alt}
                            className="header-image"
                        />
                    </div>

                    {/* Box con sfondo e lista link */}
                    <div className={`ufficio-content ${col.class}`}>
                        <div className="content-links">
                            {col.links.map(link => (
                                <a key={link.url} href={link.url}>
                                    {link.label}
                                </a>
                            ))}
                        </div>
                    </div>

                </div>
            ))}
        </div>
    )
}
