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
 * Il nome è letto direttamente da window.CT_USER.login (già disponibile dal footer).
 * L'avatar viene recuperato da api_scheda.php?op=profile&pg={login}.
 *
 * Nessun aggiornamento real-time necessario: l'avatar del proprio personaggio
 * cambia raramente e non richiede socket events.
 *
 * Montaggio: via ct:ready su #anteprima-scheda-container in anteprima_scheda.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

export default function AnteprimaScheda() {

    /** URL dell'avatar del personaggio corrente */
    const [avatar, setAvatar] = useState('')

    /** Nome del personaggio — letto da window.CT_USER (impostato da footer.inc.php) */
    const nome = window.CT_USER?.login ?? ''

    /** true se è notte (orario 18-6) */
    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    useEffect(() => {
        if (!nome) return

        // Recupera il profilo del personaggio per ottenere url_img_chat
        fetch(`/pages/api_scheda.php?op=profile&pg=${encodeURIComponent(nome)}`)
            .then(r => r.json())
            .then(d => { if (d.success && d.url_img_chat) setAvatar(d.url_img_chat) })
            .catch(err => console.error('[AnteprimaScheda] Errore:', err))
    }, [nome])

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
