/**
 * CancellaAccount.jsx — Autocancellazione account (SPA)
 *
 * Sostituisce pages/user_cancella_pg.inc.php (mai raggiungibile da nessun
 * menu — pagina orfana, vedi conversazione di progetto del 2026-07-31).
 * Solo autocancellazione: il ripristino di un account cancellato è
 * un'icona nella colonna Azioni di gestione_personaggio.inc.php (filtro
 * "Eliminati"), non un pannello separato — vedi conversazione di
 * progetto del 2026-07-31.
 *
 * API: pages/api_account.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

export default function CancellaAccount() {
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

    return (
        <>
            <div className="link_back link_back--left">
                <button onClick={() => navigate('main.php?page=preferenze')}>← Torna indietro</button>
            </div>

            <div className="account-page">

                <div className="account-page__title">Gestione Account</div>

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

            </div>
        </>
    )
}
