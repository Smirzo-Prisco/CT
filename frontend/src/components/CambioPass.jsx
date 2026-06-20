/**
 * CambioPass.jsx — Cambio password utente e forzatura admin.
 *
 * Mostra il form utente (verifica email + nuova password).
 * Se il PG ha permessi >= MODERATOR mostra anche il form admin
 * per imporre la password a qualsiasi account.
 *
 * API: api_cambio_pass.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function CambioPass() {
    const [info, setInfo]           = useState(null)
    const [accounts, setAccounts]   = useState([])
    const [loading, setLoading]     = useState(true)

    // form utente
    const [email, setEmail]         = useState('')
    const [newPass, setNewPass]     = useState('')
    const [msgUser, setMsgUser]     = useState(null)   // { type: 'ok'|'err', text }

    // form admin
    const [forcePass, setForcePass] = useState('')
    const [forceAcct, setForceAcct] = useState('')
    const [msgAdmin, setMsgAdmin]   = useState(null)

    useEffect(() => {
        fetch('pages/api_cambio_pass.php?op=getInfo')
            .then(r => r.json())
            .then(d => {
                if (d.success) setInfo(d)
                setLoading(false)
            })
            .catch(() => setLoading(false))
    }, [])

    useEffect(() => {
        if (!info?.isMod) return
        fetch('pages/api_cambio_pass.php?op=getAccounts')
            .then(r => r.json())
            .then(d => { if (d.success) setAccounts(d.accounts) })
    }, [info])

    /** Cambio password utente. */
    const handleChangePass = async (e) => {
        e.preventDefault()
        setMsgUser(null)
        const res  = await fetch('pages/api_cambio_pass.php?op=changePass', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email, new_pass: newPass }),
        })
        const data = await res.json()
        setMsgUser({ type: data.success ? 'ok' : 'err', text: data.message })
        if (data.success) { setEmail(''); setNewPass('') }
    }

    /** Forzatura password admin. */
    const handleForcePass = async (e) => {
        e.preventDefault()
        setMsgAdmin(null)
        if (!forceAcct) { setMsgAdmin({ type: 'err', text: 'Seleziona un account' }); return }
        const res  = await fetch('pages/api_cambio_pass.php?op=forcePass', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ account: forceAcct, new_pass: forcePass }),
        })
        const data = await res.json()
        setMsgAdmin({ type: data.success ? 'ok' : 'err', text: data.message })
        if (data.success) { setForcePass(''); setForceAcct('') }
    }

    if (loading) return <p><i className="fas fa-spinner fa-spin"></i> Caricamento…</p>

    return (
        <div className="pagina_user_cambio_pass">
            <div className="page_title">
                <h2>Cambio Password</h2>
            </div>

            <div className="page_body" style={{ fontFamily: 'DejaVu Sans', fontSize: '12px', color: '#8f8f8f' }}>

                {/* Form utente */}
                <div className="panels_box">
                    <div className="form_gioco">
                        <form onSubmit={handleChangePass}>
                            <div className="form_label">Email</div>
                            <div className="form_field">
                                <input
                                    value={email}
                                    onChange={e => setEmail(e.target.value)}
                                    required
                                />
                            </div>
                            <div className="form_label">Nuova password</div>
                            <div className="form_field">
                                <input
                                    type="password"
                                    value={newPass}
                                    onChange={e => setNewPass(e.target.value)}
                                    required
                                />
                            </div>
                            <div className="form_submit">
                                <input type="submit" value="Cambia Password" />
                            </div>
                        </form>
                        {msgUser && (
                            <div className={msgUser.type === 'ok' ? 'warning' : 'error'}>
                                {msgUser.text}
                            </div>
                        )}
                    </div>
                </div>

                {/* Form admin (solo moderatori e superiori) */}
                {info?.isMod && (
                    <div className="panels_box" style={{ marginTop: '16px' }}>
                        <div className="form_gioco">
                            <form onSubmit={handleForcePass}>
                                <div className="form_label">Forza password account</div>
                                <div className="form_field">
                                    <input
                                        type="password"
                                        placeholder="Nuova password"
                                        value={forcePass}
                                        onChange={e => setForcePass(e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="form_field">
                                    <select
                                        value={forceAcct}
                                        onChange={e => setForceAcct(e.target.value)}
                                        required
                                    >
                                        <option value="" disabled>Seleziona account…</option>
                                        {accounts.map(n => (
                                            <option key={n} value={n}>{n}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="form_submit">
                                    <input type="submit" value="Forza Password" />
                                </div>
                            </form>
                            {msgAdmin && (
                                <div className={msgAdmin.type === 'ok' ? 'warning' : 'error'}>
                                    {msgAdmin.text}
                                </div>
                            )}
                        </div>
                    </div>
                )}

            </div>

            <div className="link_back">
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>
        </div>
    )
}
