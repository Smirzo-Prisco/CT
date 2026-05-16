/**
 * SchedaTrans.jsx — Riepilogo PX / Transizioni (Phase SPA)
 *
 * Mostra il log dei bonifici/stipendi PX del personaggio.
 * Fetch parallele:
 *   api_scheda.php?op=profile     → dati pg + flag permessi per il menu
 *   api_scheda.php?op=transizioni → log PX (tabella log, codice BONIFICO/STIPENDIO)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'
import SchedaMenu from './SchedaMenu'
import shared from './shared.module.css'

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return isNaN(d)
        ? dateStr
        : d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaTrans() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile, setProfile] = useState(null)
    const [trans,   setTrans]   = useState(null)
    const [error,   setError]   = useState(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            fetch(`/pages/api_scheda.php?op=transizioni&pg=${enc}`).then(r => r.json()),
        ]).then(([prof, tr]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (tr.success)   setTrans(tr.transizioni)
            else              setError(tr.message  ?? 'Errore transizioni')
        }).catch(() => setError('Errore di rete'))
    }, [pg])

    if (error)           return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !trans) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Riepilogo PX</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                <div className="page_body">
                    <div className="panels_box">
                        <div className="elenco_record_gioco">
                            <table className="customTable">
                                <thead>
                                    <tr>
                                        <td className="casella_titolo"><div className="titoli_elenco">Transizione</div></td>
                                        <td className="casella_titolo"><div className="titoli_elenco">A</div></td>
                                        <td className="casella_titolo"><div className="titoli_elenco">Data</div></td>
                                        <td className="casella_titolo"><div className="titoli_elenco">Autore</div></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    {trans.length === 0
                                        ? <tr><td colSpan="4" className={shared.centered}>
                                            Nessuna transizione registrata.
                                          </td></tr>
                                        : trans.map((t, i) => (
                                            <tr key={i}>
                                                <td className="casella_elemento"><div className="elementi_elenco">{t.descrizione}</div></td>
                                                <td className="casella_elemento"><div className="elementi_elenco">{t.nome_interessato}</div></td>
                                                <td className="casella_elemento"><div className="elementi_elenco">{formatDate(t.data)}</div></td>
                                                <td className="casella_elemento"><div className="elementi_elenco">{t.autore}</div></td>
                                            </tr>
                                        ))
                                    }
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
