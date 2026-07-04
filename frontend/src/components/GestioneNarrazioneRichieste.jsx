/**
 * GestioneNarrazioneRichieste.jsx — Pannello admin richieste narrazione IA
 *
 * Elenca le richieste dei giocatori di rigenerazione completa di
 * "Dice di sé" (personaggio.descrizione) tramite l'LLM locale, con
 * approvazione/rifiuto uno alla volta. Logica in pages/api_narrazione_admin.php.
 */

import { useState, useEffect, useCallback } from 'react'

const API = '/pages/api_narrazione_admin.php'

const STATO_LABEL = {
    richiesta:        'In attesa di approvazione',
    approvata:        'Approvata — in coda al worker',
    in_elaborazione:  'In elaborazione',
    completata:       'Completata',
    rifiutata:        'Rifiutata',
}

// Stima grezza calcolata lato server (pages/api_narrazione_admin.php) in base
// al numero di giocate concluse e alla lunghezza dei messaggi da riassumere.
function formattaDurata(secondi, nGiocate) {
    if (!nGiocate) return 'Nessuna giocata'
    if (secondi < 60) return '< 1 min'
    const ore = Math.floor(secondi / 3600)
    const min = Math.round((secondi % 3600) / 60)
    const testo = ore > 0 ? `~${ore}h ${min}min` : `~${min} min`
    return `${testo} (${nGiocate} giocat${nGiocate === 1 ? 'a' : 'e'})`
}

export default function GestioneNarrazioneRichieste() {
    const [richieste, setRichieste] = useState([])
    const [loading, setLoading]     = useState(true)
    const [error, setError]         = useState(null)
    const [busyId, setBusyId]       = useState(null)

    const load = useCallback(async () => {
        setLoading(true)
        try {
            const r = await fetch(`${API}?op=list`)
            if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            setLoading(false)
            if (d.success) setRichieste(d.richieste)
            else setError(d.message ?? 'Errore nel caricamento')
        } catch {
            setLoading(false)
            setError('Errore di rete')
        }
    }, [])

    useEffect(() => { load() }, [load])

    const agisci = async (id, azione, pgName) => {
        if (azione === 'approva' && !window.confirm(
            `Approvare la rigenerazione della narrazione per «${pgName}»?\n` +
            `Il testo attuale di "Dice di sé" verrà sovrascritto non appena il worker la elabora.`
        )) return
        setBusyId(id)
        const fd = new FormData()
        fd.append('id', id)
        const r = await fetch(`${API}?op=${azione}`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        setBusyId(null)
        load()
    }

    const pendenti = richieste.filter(r => r.stato === 'richiesta')
    const altre     = richieste.filter(r => r.stato !== 'richiesta')

    return (
        <div className="pagina_gestione_gilde">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" onClick={() => window.history.back()} className="gp-back" title="Indietro">
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
                <div className="gp-topbar__center">
                    <span className="gp-title">Narrazioni IA — Richieste giocatori</span>
                </div>
                <div className="gp-topbar__right"></div>
            </div>

            {error && <div className="gm-feedback gm-feedback--error" style={{ margin: '12px' }}>{error}</div>}

            <h3 className="section-title" style={{ margin: '12px' }}>In attesa di approvazione</h3>
            <div className="gp-list">
                <table>
                    <thead>
                        <tr>
                            <th>Personaggio</th>
                            <th>Richiesta il</th>
                            <th>Durata stimata elaborazione</th>
                            <th className="gp-th-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={4} style={{ textAlign: 'center', padding: 20 }}>Caricamento…</td></tr>
                        ) : pendenti.length === 0 ? (
                            <tr><td colSpan={4} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessuna richiesta in attesa.</td></tr>
                        ) : pendenti.map(r => (
                            <tr key={r.id}>
                                <td className="gp-cell--name">{r.pg_name}</td>
                                <td className="gp-cell--meta">{r.creato_il}</td>
                                <td className="gp-cell--meta">{formattaDurata(r.secondi_stimati, r.n_giocate)}</td>
                                <td className="gp-cell--actions">
                                    <div className="gp-actions">
                                        <button className="btn-action btn-action--edit btn-action--icon" title="Approva"
                                            disabled={busyId === r.id} onClick={() => agisci(r.id, 'approva', r.pg_name)}>
                                            <i className="fa-solid fa-check"></i>
                                        </button>
                                        <button className="btn-action btn-action--delete btn-action--icon" title="Rifiuta"
                                            disabled={busyId === r.id} onClick={() => agisci(r.id, 'rifiuta', r.pg_name)}>
                                            <i className="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <h3 className="section-title" style={{ margin: '24px 12px 12px' }}>Storico</h3>
            <div className="gp-list">
                <table>
                    <thead>
                        <tr>
                            <th>Personaggio</th>
                            <th>Stato</th>
                            <th>Richiesta il</th>
                            <th>Durata stimata elaborazione</th>
                            <th>Gestita da</th>
                        </tr>
                    </thead>
                    <tbody>
                        {altre.length === 0 ? (
                            <tr><td colSpan={5} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessuna richiesta ancora gestita.</td></tr>
                        ) : altre.map(r => (
                            <tr key={r.id}>
                                <td className="gp-cell--name">{r.pg_name}</td>
                                <td className="gp-cell--meta">{STATO_LABEL[r.stato] ?? r.stato}</td>
                                <td className="gp-cell--meta">{r.creato_il}</td>
                                <td className="gp-cell--meta">{formattaDurata(r.secondi_stimati, r.n_giocate)}</td>
                                <td className="gp-cell--meta">{r.approvato_da ?? '—'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    )
}
