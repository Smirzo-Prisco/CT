/**
 * GestioneAccount.jsx — Ripristino e cancellazione forzata account (staff)
 *
 * Separato da CancellaAccount.jsx (autocancellazione, in Preferenze): sono
 * due pubblici e due punti d'accesso diversi — vedi conversazione di
 * progetto del 2026-07-31. Montato via CT.mount() su gestione.php?page=
 * gestione_account (stesso pattern di GestioneManutenzione.jsx), non
 * un'entrata di AppRouter — gli strumenti staff vivono tutti sotto
 * gestione.php, non main.php.
 *
 * API: pages/api_account.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

export default function GestioneAccount() {
    const [isAdmin, setIsAdmin]   = useState(false)
    const [loading, setLoading]   = useState(true)

    useEffect(() => {
        fetch('pages/api_account.php?op=getStaffInfo')
            .then(r => r.json())
            .then(d => { if (d.success) setIsAdmin(d.isAdmin); setLoading(false) })
            .catch(() => setLoading(false))
    }, [])

    // ── Ripristino ───────────────────────────────────────────────────────────
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

    // ── Cancellazione forzata ────────────────────────────────────────────────
    const [activeAccounts, setActiveAccounts]   = useState([])
    const [forceAcct, setForceAcct]             = useState('')
    const [forceConfirm, setForceConfirm]       = useState(false)
    const [msgForce, setMsgForce]               = useState(null)
    const [forceDeleting, setForceDeleting]     = useState(false)

    const loadActiveAccounts = useCallback(() => {
        fetch('pages/api_account.php?op=getActiveAccounts')
            .then(r => r.json())
            .then(d => { if (d.success) setActiveAccounts(d.accounts) })
            .catch(() => {})
    }, [])

    useEffect(() => { loadActiveAccounts() }, [loadActiveAccounts])

    const handleForceDelete = useCallback(async (e) => {
        e.preventDefault()
        if (!forceAcct || !forceConfirm) return
        setMsgForce(null)
        setForceDeleting(true)
        try {
            const res  = await fetch('pages/api_account.php?op=forceDelete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ account: forceAcct }),
            })
            const data = await res.json()
            setMsgForce({ type: data.success ? 'ok' : 'err', text: data.message })
            if (data.success) {
                setForceAcct('')
                setForceConfirm(false)
                loadActiveAccounts()
                loadDeletedAccounts()
            }
        } catch {
            setMsgForce({ type: 'err', text: 'Errore di rete — riprova' })
        } finally {
            setForceDeleting(false)
        }
    }, [forceAcct, forceConfirm, loadActiveAccounts, loadDeletedAccounts])

    if (loading) {
        return (
            <div className="pagina_gestione">
                <div>Caricamento…</div>
            </div>
        )
    }

    return (
        <div className="pagina_gestione">
            <header>👤 Ripristina/cancella account</header>
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

                <div className="card gm-card">
                    <h3><i className="fa-solid fa-user-slash"></i> Cancella un account</h3>
                    <p className="gm-description">
                        {isAdmin
                            ? 'Puoi cancellare qualunque account.'
                            : 'Puoi cancellare qualunque account non appartenente allo staff superiore.'}
                    </p>
                    <form onSubmit={handleForceDelete}>
                        <div className="gm-field">
                            <label htmlFor="force-select">Account</label>
                            <select
                                id="force-select"
                                value={forceAcct}
                                onChange={e => setForceAcct(e.target.value)}
                                required
                            >
                                <option value="" disabled>Seleziona…</option>
                                {activeAccounts.map(n => <option key={n} value={n}>{n}</option>)}
                            </select>
                        </div>
                        <label className="gm-field" style={{ justifyContent: 'flex-start', gap: '8px' }}>
                            <input
                                type="checkbox"
                                checked={forceConfirm}
                                onChange={e => setForceConfirm(e.target.checked)}
                            />
                            <span>Confermo la cancellazione di questo account.</span>
                        </label>
                        <button type="submit" className="btn btn--danger gm-run-btn" disabled={forceDeleting || !forceConfirm}>
                            {forceDeleting ? 'Cancellazione…' : 'Cancella account'}
                        </button>
                    </form>
                    {msgForce && (
                        <p className={msgForce.type === 'ok' ? 'gm-feedback gm-feedback--ok' : 'gm-feedback gm-feedback--error'}>
                            {msgForce.text}
                        </p>
                    )}
                </div>

            </div>
        </div>
    )
}
