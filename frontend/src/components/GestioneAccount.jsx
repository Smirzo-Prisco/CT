/**
 * GestioneAccount.jsx — Ripristino account cancellati (staff)
 *
 * Separato da CancellaAccount.jsx (autocancellazione, in Preferenze) — vedi
 * conversazione di progetto del 2026-07-31. Montato via CT.mount() su
 * gestione.php?page=gestione_account (stesso pattern di
 * GestioneManutenzione.jsx), non un'entrata di AppRouter — gli strumenti
 * staff vivono tutti sotto gestione.php, non main.php.
 *
 * Solo ripristino: la cancellazione forzata di un account da parte dello
 * staff non serve qui, esiste già in gestione_personaggio.inc.php
 * ("Elimina definitivamente" — cancellazione fisica via erasepg_scelta).
 *
 * API: pages/api_account.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

export default function GestioneAccount() {
    const [deletedAccounts, setDeletedAccounts] = useState([])
    const [restoreAcct, setRestoreAcct]          = useState('')
    const [msgRestore, setMsgRestore]            = useState(null)
    const [restoring, setRestoring]              = useState(false)

    const loadDeletedAccounts = useCallback(() => {
        fetch('pages/api_account.php?op=getDeletedAccounts')
            .then(r => r.json())
            .then(d => { if (d.success) setDeletedAccounts(d.accounts) })
            .catch(() => {})
    }, [])

    useEffect(() => { loadDeletedAccounts() }, [loadDeletedAccounts])

    const handleRestore = useCallback(async (e) => {
        e.preventDefault()
        if (!restoreAcct) return
        setMsgRestore(null)
        setRestoring(true)
        try {
            const res  = await fetch('pages/api_account.php?op=restore', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ account: restoreAcct }),
            })
            const data = await res.json()
            setMsgRestore({ type: data.success ? 'ok' : 'err', text: data.message })
            if (data.success) {
                setRestoreAcct('')
                loadDeletedAccounts()
            }
        } catch {
            setMsgRestore({ type: 'err', text: 'Errore di rete — riprova' })
        } finally {
            setRestoring(false)
        }
    }, [restoreAcct, loadDeletedAccounts])

    return (
        <div className="pagina_gestione">
            <header>👤 Ripristina account</header>
            <div className="dashboard gm-dashboard">

                <div className="card gm-card">
                    <h3><i className="fa-solid fa-rotate-left"></i> Ripristina un account cancellato</h3>
                    <p className="gm-description">
                        Elenco degli account attualmente marcati come cancellati (permessi = -1).
                    </p>
                    {deletedAccounts.length === 0 ? (
                        <p className="gm-description">Nessun account attualmente cancellato.</p>
                    ) : (
                        <form onSubmit={handleRestore}>
                            <div className="gm-field">
                                <label htmlFor="restore-select">Account</label>
                                <select
                                    id="restore-select"
                                    value={restoreAcct}
                                    onChange={e => setRestoreAcct(e.target.value)}
                                    required
                                >
                                    <option value="" disabled>Seleziona…</option>
                                    {deletedAccounts.map(n => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </div>
                            <button type="submit" className="btn btn--secondary gm-run-btn" disabled={restoring}>
                                {restoring ? 'Ripristino…' : 'Ripristina'}
                            </button>
                        </form>
                    )}
                    {msgRestore && (
                        <p className={msgRestore.type === 'ok' ? 'gm-feedback gm-feedback--ok' : 'gm-feedback gm-feedback--error'}>
                            {msgRestore.text}
                        </p>
                    )}
                </div>

            </div>
        </div>
    )
}
