/**
 * CancellaAccount.jsx — Cancellazione/ripristino account (SPA)
 *
 * Sostituisce pages/user_cancella_pg.inc.php (mai raggiungibile da nessun
 * menu — pagina orfana, vedi conversazione di progetto del 2026-07-31).
 * Tre sezioni:
 *   - Autocancellazione: chiunque, verifica email + password.
 *   - Ripristino account: solo staff (permessi >= MODERATOR).
 *   - Cancellazione forzata di un account: solo staff.
 *
 * API: pages/api_account.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

export default function CancellaAccount() {
    const [info, setInfo] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        fetch('pages/api_account.php?op=getInfo')
            .then(r => r.json())
            .then(d => { if (d.success) setInfo(d); setLoading(false) })
            .catch(() => setLoading(false))
    }, [])

    // ── Autocancellazione ────────────────────────────────────────────────────
    const [delEmail, setDelEmail]     = useState('')
    const [delPass, setDelPass]       = useState('')
    const [delConfirm, setDelConfirm] = useState(false)
    const [msgDelete, setMsgDelete]   = useState(null)
    const [deleting, setDeleting]     = useState(false)

    const handleDelete = useCallback(async (e) => {
        e.preventDefault()
        setMsgDelete(null)
        setDeleting(true)
        try {
            const res  = await fetch('pages/api_account.php?op=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: delEmail, password: delPass }),
            })
            const data = await res.json()
            if (data.success) {
                navigate('index.php')
                return
            }
            setMsgDelete({ type: 'err', text: data.message })
        } catch {
            setMsgDelete({ type: 'err', text: 'Errore di rete — riprova' })
        } finally {
            setDeleting(false)
        }
    }, [delEmail, delPass])

    // ── Ripristino (staff) ───────────────────────────────────────────────────
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

    useEffect(() => {
        if (info?.isMod) loadDeletedAccounts()
    }, [info, loadDeletedAccounts])

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

    // ── Cancellazione forzata (staff) ────────────────────────────────────────
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

    useEffect(() => {
        if (info?.isMod) loadActiveAccounts()
    }, [info, loadActiveAccounts])

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
            <div className="account-page account-page--loading">
                <i className="fas fa-spinner fa-spin"></i> Caricamento…
            </div>
        )
    }

    return (
        <>
            <div className="link_back link_back--left">
                <button onClick={() => navigate('main.php?page=preferenze')}>← Torna indietro</button>
            </div>

            <div className="account-page">

                <div className="account-page__title">Gestione Account</div>

                {/* ── Autocancellazione ──────────────────────────────────── */}
                <div className="account-page__section account-page__section--danger">
                    <div className="account-page__section-title">
                        <i className="fa-solid fa-triangle-exclamation"></i> Elimina il mio account
                    </div>
                    <p className="account-page__hint">
                        Stai per eliminare il tuo account. Una volta eliminato, non avrai più accesso a Crystal Tokyo.
                        Procedi inserendo i tuoi dati per confermare l'eliminazione.
                    </p>
                    <form onSubmit={handleDelete}>
                        <label className="account-page__field">
                            <span>Email di registrazione</span>
                            <input
                                type="email"
                                value={delEmail}
                                onChange={e => setDelEmail(e.target.value)}
                                required
                            />
                        </label>
                        <label className="account-page__field">
                            <span>Password</span>
                            <input
                                type="password"
                                value={delPass}
                                onChange={e => setDelPass(e.target.value)}
                                required
                            />
                        </label>
                        <label className="account-page__confirm">
                            <input
                                type="checkbox"
                                checked={delConfirm}
                                onChange={e => setDelConfirm(e.target.checked)}
                            />
                            <span>Capisco che questa azione disconnette subito il mio account.</span>
                        </label>
                        <button type="submit" className="btn btn--danger" disabled={deleting || !delConfirm}>
                            {deleting ? 'Cancellazione…' : 'Elimina il mio account'}
                        </button>
                    </form>
                    {msgDelete && (
                        <p className={`account-page__msg account-page__msg--${msgDelete.type}`}>{msgDelete.text}</p>
                    )}
                </div>

                {/* ── Ripristino (staff) ─────────────────────────────────── */}
                {info?.isMod && (
                    <div className="account-page__section">
                        <div className="account-page__section-title">
                            <i className="fa-solid fa-rotate-left"></i> Ripristina un account cancellato
                        </div>
                        {deletedAccounts.length === 0 ? (
                            <p className="account-page__hint">Nessun account attualmente cancellato.</p>
                        ) : (
                            <form onSubmit={handleRestore}>
                                <label className="account-page__field">
                                    <span>Account</span>
                                    <select
                                        value={restoreAcct}
                                        onChange={e => setRestoreAcct(e.target.value)}
                                        required
                                    >
                                        <option value="" disabled>Seleziona…</option>
                                        {deletedAccounts.map(n => <option key={n} value={n}>{n}</option>)}
                                    </select>
                                </label>
                                <button type="submit" className="btn btn--secondary" disabled={restoring}>
                                    {restoring ? 'Ripristino…' : 'Ripristina'}
                                </button>
                            </form>
                        )}
                        {msgRestore && (
                            <p className={`account-page__msg account-page__msg--${msgRestore.type}`}>{msgRestore.text}</p>
                        )}
                    </div>
                )}

                {/* ── Cancellazione forzata (staff) ──────────────────────── */}
                {info?.isMod && (
                    <div className="account-page__section account-page__section--danger">
                        <div className="account-page__section-title">
                            <i className="fa-solid fa-user-slash"></i> Cancella un account
                        </div>
                        <p className="account-page__hint">
                            {info.isSuperuser
                                ? 'Puoi cancellare qualunque account.'
                                : 'Puoi cancellare qualunque account non appartenente allo staff superiore.'}
                        </p>
                        <form onSubmit={handleForceDelete}>
                            <label className="account-page__field">
                                <span>Account</span>
                                <select
                                    value={forceAcct}
                                    onChange={e => setForceAcct(e.target.value)}
                                    required
                                >
                                    <option value="" disabled>Seleziona…</option>
                                    {activeAccounts.map(n => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </label>
                            <label className="account-page__confirm">
                                <input
                                    type="checkbox"
                                    checked={forceConfirm}
                                    onChange={e => setForceConfirm(e.target.checked)}
                                />
                                <span>Confermo la cancellazione di questo account.</span>
                            </label>
                            <button type="submit" className="btn btn--danger" disabled={forceDeleting || !forceConfirm}>
                                {forceDeleting ? 'Cancellazione…' : 'Cancella account'}
                            </button>
                        </form>
                        {msgForce && (
                            <p className={`account-page__msg account-page__msg--${msgForce.type}`}>{msgForce.text}</p>
                        )}
                    </div>
                )}

            </div>
        </>
    )
}
