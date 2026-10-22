/**
 * GestioneSkillPersonaggio.jsx — Gestione skill/talenti di un pg (Phase SPA, 3/3)
 *
 * Porta a React la vecchia pagina gst_personaggio_skill.inc.php: dropdown
 * personaggio → tabella delle sue skill raggruppate per tipo, con
 * depotenzia (-1 grado) e cancella (rimozione completa). API:
 * api_personaggio.php (op=getPgNomi, getPgSkillsAdmin, depotenziaSkillAdmin,
 * cancellaSkillAdmin — il rimborso punti skill viene ricalcolato lato
 * server invece di arrivare dal client, vedi commento in quel file).
 *
 * Supporta un pg preselezionato via ?pg=Nome nell'URL (usato dall'icona
 * "Gestione skill" di GestionePersonaggio.jsx).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

export default function GestioneSkillPersonaggio() {
    const [elenco, setElenco]       = useState(null)
    const [error, setError]         = useState(null)
    const [selezionato, setSelezionato] = useState(() => new URLSearchParams(window.location.search).get('pg') ?? '')
    const [skillPerTipo, setSkillPerTipo] = useState(null)
    const [caricandoSkill, setCaricandoSkill] = useState(false)

    useEffect(() => {
        fetch('pages/api_personaggio.php?op=getPgNomi')
            .then(r => r.json())
            .then(d => {
                if (d.success) setElenco(d.personaggi)
                else setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => setError('Errore di rete'))
    }, [])

    const caricaSkill = useCallback((nome) => {
        if (!nome) { setSkillPerTipo(null); return }
        setCaricandoSkill(true)
        fetch('pages/api_personaggio.php?op=getPgSkillsAdmin&pg=' + encodeURIComponent(nome))
            .then(r => r.json())
            .then(d => {
                if (d.success) setSkillPerTipo(d.skillPerTipo)
                else setError(d.message ?? 'Errore')
                setCaricandoSkill(false)
            })
            .catch(() => { setError('Errore di rete'); setCaricandoSkill(false) })
    }, [])

    useEffect(() => {
        if (selezionato) caricaSkill(selezionato)
    }, [selezionato, caricaSkill])

    function depotenzia(idAbilita) {
        fetch('pages/api_personaggio.php?op=depotenziaSkillAdmin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pg: selezionato, id_abilita: idAbilita })
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) caricaSkill(selezionato)
                else window.showNotification?.(d.message, 'error')
            })
            .catch(() => window.showNotification?.('Errore di rete', 'error'))
    }

    function cancella(idAbilita) {
        fetch('pages/api_personaggio.php?op=cancellaSkillAdmin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pg: selezionato, id_abilita: idAbilita })
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) caricaSkill(selezionato)
                else window.showNotification?.(d.message, 'error')
            })
            .catch(() => window.showNotification?.('Errore di rete', 'error'))
    }

    if (error)   return <div className="pagina_gestione_mercato"><div className="error">{error}</div></div>
    if (!elenco) return <div className="pagina_gestione_mercato"><div><i className="fas fa-spinner fa-spin" /> Caricamento…</div></div>

    return (
        <div className="pagina_gestione_mercato">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" className="gp-back" title="Indietro" onClick={() => navigate('main.php?page=gestione_personaggio')}>
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
            </div>

            <div className="panels_box">
                <div className="panels_box">
                    <div className="form_label">Seleziona personaggio</div>
                    <div className="form_field">
                        <select value={selezionato} onChange={e => setSelezionato(e.target.value)}>
                            <option value="">—</option>
                            {elenco.map(pg => (
                                <option key={pg.nome} value={pg.nome}>{pg.nome}{pg.cognome ? ' ' + pg.cognome : ''}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {selezionato && caricandoSkill && <div>Caricamento…</div>}

                {selezionato && !caricandoSkill && skillPerTipo && Object.keys(skillPerTipo).length === 0 && (
                    <p>Nessuna skill posseduta da {selezionato}.</p>
                )}

                {selezionato && !caricandoSkill && skillPerTipo && Object.entries(skillPerTipo).map(([tipo, skills]) => (
                    <div className="panels_box" key={tipo}>
                        <div className="form_label">Elenco abilità di tipo {tipo}</div>
                        <div className="elenco_record_gestione">
                            <table>
                                <tbody>
                                    <tr>
                                        <td className="casella_titolo"><div className="titoli_elenco">Nome</div></td>
                                        <td className="casella_titolo"><div className="titoli_elenco">Livello</div></td>
                                        <td className="casella_titolo"><div className="titoli_elenco">Operazioni</div></td>
                                    </tr>
                                    {skills.map(sk => (
                                        <tr className="risultati_elenco_record_gestione" key={sk.id_abilita}>
                                            <td className="casella_elemento"><div className="elementi_elenco">{sk.nome_abilita}</div></td>
                                            <td className="casella_elemento"><div className="elementi_elenco">{sk.grado}</div></td>
                                            <td className="controlli_elenco">
                                                <div className="controlli_elenco_wrapper">
                                                    {sk.grado > 1 && (
                                                        <div className="controllo_elenco">
                                                            <button type="button" className="opzioni_elenco_record_gestione" onClick={() => depotenzia(sk.id_abilita)}>−</button>
                                                        </div>
                                                    )}
                                                    <div className="controllo_elenco">
                                                        <button type="button" className="opzioni_elenco_record_gestione" onClick={() => cancella(sk.id_abilita)}>✕</button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
