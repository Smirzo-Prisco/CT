/**
 * SchedaModifica.jsx — Form di modifica scheda personaggio (Phase SPA)
 *
 * Fetch GET:  api_scheda.php?op=form_data&pg=X  → campi attuali + permessi
 * Fetch POST: api_scheda.php?op=save_modifica&pg=X → salva le modifiche
 *
 * Accesso: solo proprio pg o admin (403 dall'API altrimenti).
 *
 * Nickname Tokyobook e Gilda diventano readonly dopo il primo salvataggio
 * (logica enforcement anche server-side nella API).
 *
 * La funzione checkVolto chiama api_global.php?op=checkVolto direttamente,
 * senza dipendere dalla funzione globale in personaggio.js.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

/** Riga standard della tabella: label sinistra + contenuto destra. */
function FormRow({ label, children, valignTop = false }) {
    return (
        <tr>
            <td className="second_header"
                style={{ textTransform: 'uppercase', fontSize: 12, width: 200,
                         verticalAlign: valignTop ? 'top' : 'middle' }}>
                {label}
            </td>
            <td>{children}</td>
        </tr>
    )
}

/** Intestazione di sezione nella tabella. */
function SectionHeader({ children }) {
    return (
        <tr className="second_header">
            <td colSpan="2" style={{ textTransform: 'uppercase', fontSize: 15 }}>
                {children}
            </td>
        </tr>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaModifica() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [form,   setForm]   = useState(null)
    const [saving, setSaving] = useState(false)
    const [msg,    setMsg]    = useState(null)
    const [error,  setError]  = useState(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        fetch(`/pages/api_scheda.php?op=form_data&pg=${encodeURIComponent(pg)}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setForm(d)
                else           setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => setError('Errore di rete'))
    }, [pg])

    /** Aggiorna un campo del form (input/textarea → value, checkbox → checked). */
    const upd = (field) => (e) => setForm(prev => ({
        ...prev,
        [field]: e.target.type === 'checkbox' ? e.target.checked : e.target.value,
    }))

    /** Controlla la disponibilità del volto via api_global.php. */
    const checkVolto = useCallback(async () => {
        const v = (form?.volto ?? '').trim()
        if (v.length <= 2) return
        try {
            const r = await fetch(`/pages/api_global.php?op=checkVolto&volto=${encodeURIComponent(v)}`)
            const d = await r.json()
            if (d[0]) alert(d[0])
        } catch {}
    }, [form?.volto])

    /** Invia il form via fetch POST JSON. */
    const handleSubmit = async (e) => {
        e.preventDefault()
        setSaving(true)
        setMsg(null)
        try {
            const r = await fetch(
                `/pages/api_scheda.php?op=save_modifica&pg=${encodeURIComponent(pg)}`,
                {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(form),
                }
            )
            const d = await r.json()
            setMsg({ ok: d.success, text: d.message ?? (d.success ? 'Salvato' : 'Errore') })
        } catch {
            setMsg({ ok: false, text: 'Errore di rete' })
        } finally {
            setSaving(false)
        }
    }

    if (error)  return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!form)  return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master,
            nickname_tokyo_readonly, nickname_gilda_set, nickname_gilda_readonly,
            allow_audio } = form

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Modifica scheda</h2></div>

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
                        {msg && (
                            <div className={msg.ok ? 'notice' : 'error'} style={{ marginBottom: 10 }}>
                                {msg.text}
                            </div>
                        )}

                        <form onSubmit={handleSubmit}>
                            <table className="customTable">

                                {/* ── DATI PERSONALI ───────────────────── */}
                                <SectionHeader>Dati personali</SectionHeader>

                                <FormRow label="Cognome">
                                    <input type="text" className="form_input"
                                        value={form.cognome ?? ''} onChange={upd('cognome')}
                                        style={{ width: '100%' }} />
                                </FormRow>
                                <FormRow label="Età">
                                    <input type="number" className="form_input"
                                        value={form.eta ?? ''} onChange={upd('eta')}
                                        style={{ width: '100%' }} />
                                </FormRow>
                                <FormRow label="Nato a">
                                    <input type="text" className="form_input"
                                        value={form.natoa ?? ''} onChange={upd('natoa')}
                                        style={{ width: '100%' }} />
                                </FormRow>

                                {/* ── ASPETTO E IMMAGINI ───────────────── */}
                                <SectionHeader>Aspetto e immagini</SectionHeader>

                                <FormRow label="Volto">
                                    <div style={{ display: 'flex', gap: 8 }}>
                                        <input type="text" className="form_input"
                                            value={form.volto ?? ''} onChange={upd('volto')}
                                            style={{ flex: 1 }} />
                                        <input type="button" value="Controlla"
                                            className="form_button" onClick={checkVolto} />
                                    </div>
                                </FormRow>
                                <FormRow label="URL Immagine">
                                    <input type="url" className="form_input"
                                        value={form.url_img ?? ''} onChange={upd('url_img')}
                                        style={{ width: '100%' }} />
                                </FormRow>
                                <FormRow label="URL Immagine Chat">
                                    <input type="url" className="form_input"
                                        value={form.url_img_chat ?? ''} onChange={upd('url_img_chat')}
                                        placeholder="No gif animate" style={{ width: '100%' }} />
                                </FormRow>

                                {/* ── DESCRIZIONI ──────────────────────── */}
                                <SectionHeader>Descrizioni</SectionHeader>

                                <FormRow label="Background" valignTop>
                                    <textarea className="ares" rows="15"
                                        value={form.principale ?? ''} onChange={upd('principale')}
                                        style={{ width: '100%' }} />
                                </FormRow>
                                <FormRow label="Storia" valignTop>
                                    <textarea className="ares" rows="15"
                                        value={form.storia ?? ''} onChange={upd('storia')}
                                        style={{ width: '100%' }} />
                                </FormRow>
                                <FormRow label="Dice di sé" valignTop>
                                    <textarea className="ares" rows="15"
                                        value={form.descrizione ?? ''} onChange={upd('descrizione')}
                                        style={{ width: '100%' }} />
                                </FormRow>
                                <FormRow label="Off" valignTop>
                                    <textarea className="ares" rows="15"
                                        value={form.off ?? ''} onChange={upd('off')}
                                        style={{ width: '100%' }} />
                                </FormRow>

                                {/* ── IMPOSTAZIONI ─────────────────────── */}
                                <SectionHeader>Impostazioni</SectionHeader>

                                <FormRow label="Blocca media">
                                    <input type="checkbox"
                                        checked={!!form.blocca_media}
                                        onChange={upd('blocca_media')} />
                                </FormRow>
                                {allow_audio && (
                                    <FormRow label="Musica in scheda">
                                        <input type="url" className="form_input"
                                            value={form.url_media ?? ''} onChange={upd('url_media')}
                                            style={{ width: '100%' }} />
                                    </FormRow>
                                )}

                                {/* ── ALIAS E SOPRANNOMI ───────────────── */}
                                <SectionHeader>Alias e soprannomi</SectionHeader>

                                <FormRow label={
                                    <>Tokyobook<br />
                                    <em style={{ fontSize: 11, textTransform: 'none' }}>
                                        {form.nickname_tokyo == null
                                            ? '(Puoi impostarlo)'
                                            : '(Modificabile una sola volta)'}
                                    </em></>
                                }>
                                    <input type="text" className="form_input"
                                        value={form.nickname_tokyo ?? ''}
                                        onChange={upd('nickname_tokyo')}
                                        readOnly={nickname_tokyo_readonly}
                                        style={{ width: '100%' }} />
                                </FormRow>

                                {nickname_gilda_set && (
                                    <FormRow label={
                                        <>Famiglia<br />
                                        <em style={{ fontSize: 11, textTransform: 'none' }}>
                                            (Modificabile una sola volta)
                                        </em></>
                                    }>
                                        <input type="text" className="form_input"
                                            value={form.nickname_gilda ?? ''}
                                            onChange={upd('nickname_gilda')}
                                            readOnly={nickname_gilda_readonly}
                                            style={{ width: '100%' }} />
                                    </FormRow>
                                )}

                                {/* ── SUBMIT ───────────────────────────── */}
                                <tr>
                                    <td colSpan="2" style={{ textAlign: 'center', padding: 20 }}>
                                        <input type="submit" className="form_submit"
                                            value={saving ? 'Salvataggio…' : 'Salva scheda'}
                                            disabled={saving} />
                                    </td>
                                </tr>

                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    )
}
