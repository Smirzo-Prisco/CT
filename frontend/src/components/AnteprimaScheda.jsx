/**
 * AnteprimaScheda.jsx
 *
 * Box anteprima personaggio nella colonna destra — rimpiazza pages/anteprima_scheda.inc.php.
 *
 * Contenuto:
 *   - Nome del personaggio (dal titolo)
 *   - Avatar del personaggio (variante giorno/notte)
 *   - Link alla scheda personaggio (main.php?page=scheda)
 *   - Link alla lista presenti estesi (main.php?page=presenti_estesi)
 *
 * Sia il nome che l'URL dell'avatar vengono letti direttamente da window.CT_USER,
 * che viene iniettato in footer.inc.php con una query DB al load della pagina.
 * Questo evita una chiamata API separata (api_scheda.php aveva problemi di costanti).
 *
 * Nessun aggiornamento real-time necessario: l'avatar del proprio personaggio
 * cambia raramente e non richiede socket events.
 *
 * Montaggio: via ct:ready su #anteprima-scheda-container in anteprima_scheda.inc.php
 *
 * @author Crystal Tokyo Dev
 */

export default function AnteprimaScheda() {

    /** Nome del personaggio — letto da window.CT_USER (impostato da footer.inc.php) */
    const nome = window.CT_USER?.login ?? ''

    /** URL avatar — iniettato da footer.inc.php via query DB sul personaggio corrente */
    const avatar = window.CT_USER?.url_img_chat ?? ''

    /** true se è notte (orario 18-6) */
    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    return (
        <div className="pagina_info_location">

            {/* Nome personaggio */}
            <div className="page_title">
                <h2>{nome}</h2>
            </div>

            <div className="page_body">

                {/* Avatar con classe giorno/notte */}
                <div className={isNotte ? 'info_pg_night' : 'info_pg'}>
                    {avatar && (
                        <img
                            src={avatar}
                            className="immagine_pg"
                            alt={nome}
                        />
                    )}

                    {/* Link alla scheda personaggio */}
                    <a href={`main.php?page=scheda&pg=${encodeURIComponent(nome)}`}>
                        <img
                            className="scheda_pg"
                            src="themes/crystal/imgs/colonna_dx/scheda_personaggio.png"
                            alt="Scheda"
                        />
                    </a>
                </div>

                {/* Link alla lista presenti estesi */}
                <div className="presenti_button">
                    <a href="main.php?page=presenti_estesi">
                        <img src="../themes/crystal/imgs/menu/presenti.png" alt="Presenti" />
                    </a>
                </div>

            </div>
        </div>
    )
}
