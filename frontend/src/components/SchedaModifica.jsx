/**
 * SchedaModifica.jsx — Form di modifica scheda personaggio (Phase SPA)
 *
 * Fetch GET:  api_scheda.php?op=form_data&pg=X  → campi attuali + permessi
 * Fetch POST: api_scheda.php?op=save_modifica&pg=X → salva le modifiche
 *
 * Accesso: solo proprio pg o admin (403 dall'API altrimenti).
 *
 * Nickname Gilda diventa readonly dopo il primo salvataggio
 * (logica enforcement anche server-side nella API).
 *
 * La funzione checkVolto chiama api_global.php?op=checkVolto direttamente,
 * senza dipendere dalla funzione globale in personaggio.js.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './SchedaModifica.module.css'

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

/** Riga standard della tabella: label sinistra + contenuto destra. */
function FormRow({ label, children, valignTop = false }) {
    return (
        <tr>
            <td className={`second_header ${valignTop ? styles.labelCellTop : styles.labelCell}`}>
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
            <td colSpan="2" className={styles.sectionTitle}>
                {children}
            </td>
        </tr>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaModifica() {
    const pg      = new URLSearchParams(window.location.search).get('pg') ?? ''
    const formRef = useRef(null)

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
            if (typeof showNotification === 'function') {
                showNotification(d.message ?? (d.success ? 'Scheda salvata!' : 'Errore nel salvataggio'), d.success ? 'success' : 'error')
            }
        } catch {
            setMsg({ ok: false, text: 'Errore di rete' })
        } finally {
            setSaving(false)
        }
    }

    if (error)  return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!form)  return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master,
            nickname_gilda_set, nickname_gilda_readonly,
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
                            <div className={`${msg.ok ? 'notice' : 'error'} ${styles.fieldWrap}`}>
                                {msg.text}
                            </div>
                        )}

                        <form ref={formRef} onSubmit={handleSubmit}>
                            <table className="customTable">

                                {/* ── DATI PERSONALI ───────────────────── */}
                                <SectionHeader>Dati personali</SectionHeader>

                                <FormRow label="Cognome">
                                    <input type="text" className={`form_input ${styles.fullWidth}`}
                                        value={form.cognome ?? ''} onChange={upd('cognome')}
                                         />
                                </FormRow>
                                <FormRow label="Età">
                                    <input type="number" className={`form_input ${styles.fullWidth}`}
                                        value={form.eta ?? ''} onChange={upd('eta')}
                                         />
                                </FormRow>
                                <FormRow label="Nato a">
                                    <input type="text" className={`form_input ${styles.fullWidth}`}
                                        value={form.natoa ?? ''} onChange={upd('natoa')}
                                         />
                                </FormRow>

                                {/* ── ASPETTO E IMMAGINI ───────────────── */}
                                <SectionHeader>Aspetto e immagini</SectionHeader>

                                <FormRow label="Volto">
                                    <div className={styles.flexRow}>
                                        <input type="text" className={`form_input ${styles.flexItem}`}
                                            value={form.volto ?? ''} onChange={upd('volto')}
                                             />
                                        <input type="button" value="Controlla"
                                            className="form_button" onClick={checkVolto} />
                                    </div>
                                </FormRow>
                                <FormRow label="URL Immagine">
                                    <input type="url" className={`form_input ${styles.fullWidth}`}
                                        value={form.url_img ?? ''} onChange={upd('url_img')}
                                         />
                                </FormRow>
                                <FormRow label="URL Immagine Chat">
                                    <input type="url" className={`form_input ${styles.fullWidth}`}
                                        value={form.url_img_chat ?? ''} onChange={upd('url_img_chat')}
                                        placeholder="No gif animate"  />
                                </FormRow>

                                {/* ── DESCRIZIONI ──────────────────────── */}
                                <SectionHeader>Descrizioni</SectionHeader>

                                <FormRow label="Background" valignTop>
                                    <textarea className={`form_input ${styles.fullWidth}`} rows="15"
                                        value={form.principale ?? ''} onChange={upd('principale')} />
                                </FormRow>
                                <FormRow label="Storia" valignTop>
                                    <textarea className={`form_input ${styles.fullWidth}`} rows="15"
                                        value={form.storia ?? ''} onChange={upd('storia')} />
                                </FormRow>
                                <FormRow label="Dice di sé" valignTop>
                                    <textarea className={`form_input ${styles.fullWidth}`} rows="15"
                                        value={form.descrizione ?? ''} onChange={upd('descrizione')} />
                                </FormRow>
                                <FormRow label="Off" valignTop>
                                    <textarea className={`form_input ${styles.fullWidth}`} rows="15"
                                        value={form.off ?? ''} onChange={upd('off')} />
                                </FormRow>

                                {/* ── IMPOSTAZIONI ─────────────────────── */}
                                <SectionHeader>Impostazioni</SectionHeader>

                                {/* <FormRow label="Blocca media">
                                    <input type="checkbox"
                                        checked={!!form.blocca_media}
                                        onChange={upd('blocca_media')} />
                                </FormRow> */}
                                {allow_audio && (
                                    <FormRow label="Musica in scheda">
                                        <input type="text" className={`form_input ${styles.fullWidth}`}
                                            value={form.url_media ?? ''} onChange={upd('url_media')}
                                            placeholder="URL file MP3 o link YouTube" />
                                    </FormRow>
                                )}

                                {/* ── ALIAS E SOPRANNOMI ───────────────── */}
                                <SectionHeader>Alias e soprannomi</SectionHeader>

                                {nickname_gilda_set && (
                                    <FormRow label={
                                        <>Razza<br />
                                        <em className={styles.smallText}>
                                            (Modificabile una sola volta)
                                        </em></>
                                    }>
                                        <input type="text" className={`form_input ${styles.fullWidth}`}
                                            value={form.nickname_gilda ?? ''}
                                            onChange={upd('nickname_gilda')}
                                            readOnly={nickname_gilda_readonly} />
                                    </FormRow>
                                )}

                                {/* ── SUBMIT ───────────────────────────── */}
                                <tr>
                                    <td colSpan="2" className={styles.centered}>
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
            <div className={styles.stickyBar}>
                <button type="button" className={styles.stickyBtn}
                        onClick={() => document.getElementById('maincontent')?.scrollTo({ top: 0, behavior: 'smooth' })}>▲</button>
                <button type="button"
                        className={`${styles.stickyBtn} ${styles.stickyBtnSave}`}
                        onClick={() => formRef.current?.requestSubmit()}
                        disabled={saving}>
                    {saving ? '…' : '💾 Salva'}
                </button>
                <button type="button" className={styles.stickyBtn}
                        onClick={() => { const el = document.getElementById('maincontent'); el?.scrollTo({ top: el.scrollHeight, behavior: 'smooth' }) }}>▼</button>
            </div>
        </div>
    )
}
