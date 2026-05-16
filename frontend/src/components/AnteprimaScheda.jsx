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
 * Strategia avatar (due livelli):
 *   1. Legge subito window.CT_USER.url_img_chat (iniettato da footer.inc.php),
 *      evitando un round-trip HTTP nella maggior parte dei casi.
 *   2. Se il valore è vuoto (campo non impostato in DB), fa una chiamata
 *      a api_scheda.php?op=profile come fallback.
 *
 * Nessun aggiornamento real-time: l'avatar cambia raramente.
 *
 * Montaggio: via ct:ready su #anteprima-scheda-container in left-right_frames.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

export default function AnteprimaScheda() {

    /** Nome del personaggio — letto da window.CT_USER (impostato da footer.inc.php) */
    const nome = window.CT_USER?.login ?? ''

    /**
     * Avatar: inizializzato dal valore iniettato da PHP (trimmed).
     * Il DB usa ' ' come default per i personaggi senza avatar, quindi il trim
     * è necessario per evitare broken images con src=" ".
     */
    const [avatar, setAvatar] = useState(
        () => (window.CT_USER?.url_img_chat ?? '').trim()
    )

    /** true se è notte (orario 18-6) */
    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    useEffect(() => {
        // Fallback API: solo se il footer non ha fornito il valore
        // (personaggio senza url_img_chat impostato in DB)
        if (avatar || !nome) return
        fetch(`/pages/api_scheda.php?op=profile&pg=${encodeURIComponent(nome)}`)
            .then(r => r.json())
            .then(d => { if (d.success && d.url_img_chat) setAvatar(d.url_img_chat.trim()) })
            .catch(() => {})
    }, [nome]) // eslint-disable-line react-hooks/exhaustive-deps

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

            </div>
        </div>
    )
}
