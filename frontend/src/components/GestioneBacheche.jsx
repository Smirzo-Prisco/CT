/**
 * GestioneBacheche.jsx — Pannello admin gestione bacheche (sezioni forum/araldo)
 *
 * Sostituisce pages/gestione_bacheche.inc.php (PHP monolitico, nessuna
 * conferma prima di eliminare, il campo Proprietario restava sempre visibile
 * indipendentemente dal Tipo scelto, Visibilità e "assegna punti quest" mai
 * esposti in UI pur esistendo in DB — vedi araldo.invisibile/araldo.punti).
 *
 * Il Tipo determina sia il raggruppamento visivo della bacheca nell'elenco
 * forum sia chi può accedervi (vedi can_access_section() in
 * custom_functions.inc.php) — per i tipi razza/gilda/mestiere, Proprietario
 * seleziona quale specifica razza/gilda/mestiere ne ha accesso esclusivo.
 */

import { useState, useEffect, useCallback } from 'react'
import { createPortal } from 'react-dom'

const API = '/pages/api_bacheche.php'

// Tipi per cui il campo Proprietario ha senso (indici di SOLORAZZA/SOLOGILDA/SOLOMESTIERE, vedi constant_values.inc.php)
const TIPI_CON_PROPRIETARIO = [4, 5, 6]

function emptyBacheca() {
    return { id_araldo: null, nome: '', descrizione: '', tipo: 0, proprietari: -1, invisibile: 0, punti: 0 }
}

// ── BachecaModal ─────────────────────────────────────────────────────────────

function BachecaModal({ bacheca, tipi, razze, gilde, mestieri, onClose, onSaved }) {
    const [form, setForm] = useState(bacheca)
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)

    const isEdit = !!form.id_araldo
    const showProprietario = TIPI_CON_PROPRIETARIO.includes(Number(form.tipo))

    const opzioniProprietario =
        Number(form.tipo) === 4 ? razze.map(r => ({ id: r.id, nome: r.nome })) :
        Number(form.tipo) === 5 ? gilde.map(g => ({ id: g.id, nome: g.nome })) :
        Number(form.tipo) === 6 ? mestieri.map(m => ({ id: m.id, nome: m.nome })) : []

    const submit = async (e) => {
        e.preventDefault()
        setSaving(true)
        setError(null)

        const fd = new FormData()
        if (isEdit) fd.append('id_araldo', form.id_araldo)
        fd.append('nome', form.nome)
        fd.append('descrizione', form.descrizione)
        fd.append('tipo', form.tipo)
        fd.append('proprietari', showProprietario ? form.proprietari : -1)
        if (form.invisibile) fd.append('invisibile', '1')
        if (form.punti) fd.append('punti', '1')

        const r = await fetch(`${API}?op=save`, { method: 'POST', body: fd })
        if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved()
        else setError(d.message ?? 'Errore nel salvataggio')
    }

    // Portal su document.body: #maincontent (position:fixed) crea un proprio
    // stacking context, un z-index qui dentro resterebbe intrappolato sotto
    // .ct-hud — stesso motivo del portal in GestioneMestieri.jsx.
    return createPortal(
        <div className="pg-edit-container" style={{ display: 'flex' }} role="dialog" aria-modal="true">
            <div className="modal-content">
                <div className="gp-modal-header">
                    <h2 className="gp-modal-title">
                        <i className="fa-solid fa-table-list"></i>
                        {isEdit ? `Modifica — ${bacheca.nome}` : 'Nuova bacheca'}
                    </h2>
                    <div className="gp-modal-header-actions">
                        <button type="button" className="gp-modal-close" onClick={onClose} aria-label="Chiudi">✕</button>
                    </div>
                </div>

                <form id="formSaveBacheca" onSubmit={submit}>
                    <div className="form-section">
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="b-nome">Nome</label>
                                <input id="b-nome" value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} required />
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="b-descrizione">Descrizione</label>
                                <input id="b-descrizione" value={form.descrizione} onChange={e => setForm({ ...form, descrizione: e.target.value })} />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="b-tipo">Tipo</label>
                                <select id="b-tipo" value={form.tipo} onChange={e => setForm({ ...form, tipo: e.target.value, proprietari: -1 })}>
                                    {tipi.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                                </select>
                                <p className="gp-label-note">Determina sia il raggruppamento della bacheca nell'elenco forum sia chi può accedervi.</p>
                            </div>

                            {showProprietario && (
                                <div className="form-group form-column">
                                    <label htmlFor="b-proprietario">Proprietario</label>
                                    <select id="b-proprietario" value={form.proprietari} onChange={e => setForm({ ...form, proprietari: e.target.value })}>
                                        <option value="-1">— nessuno —</option>
                                        {opzioniProprietario.map(o => <option key={o.id} value={o.id}>{o.nome}</option>)}
                                    </select>
                                    <p className="gp-label-note">Chi avrà accesso esclusivo a questa bacheca.</p>
                                </div>
                            )}
                        </div>

                        <div className="form-row">
                            <div className="form-group gp-checkbox-field">
                                <input id="b-invisibile" type="checkbox" checked={!form.invisibile}
                                       onChange={e => setForm({ ...form, invisibile: e.target.checked ? 0 : 1 })} />
                                <label htmlFor="b-invisibile">Visibile <span className="gp-label-note">— se disattivato la bacheca resta nel database ma sparisce dall'elenco forum</span></label>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group gp-checkbox-field">
                                <input id="b-punti" type="checkbox" checked={!!form.punti}
                                       onChange={e => setForm({ ...form, punti: e.target.checked ? 1 : 0 })} />
                                <label htmlFor="b-punti">Assegna punti quest <span className="gp-label-note">— pubblicare un resoconto quest in questa bacheca assegna automaticamente esperienza/shin/notorietà ai partecipanti</span></label>
                            </div>
                        </div>

                        {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
                    </div>

                    <div className="gp-modal-footer">
                        <button type="button" className="btn btn--ghost" onClick={onClose}>Annulla</button>
                        <button type="submit" className="btn btn--ghost" disabled={saving}>
                            <i className="fa-solid fa-floppy-disk"></i>&nbsp; {saving ? 'Salvataggio…' : (isEdit ? 'Salva modifiche' : 'Crea bacheca')}
                        </button>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    )
}

// ── GestioneBacheche ─────────────────────────────────────────────────────────

export default function GestioneBacheche() {
    const [bacheche, setBacheche] = useState([])
    const [tipi, setTipi]         = useState([])
    const [razze, setRazze]       = useState([])
    const [gilde, setGilde]       = useState([])
    const [mestieri, setMestieri] = useState([])
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)
    const [editing, setEditing]   = useState(null) // bacheca in modifica/creazione, oppure null
    const [msg, setMsg]           = useState(null) // { ok, text } oppure null

    const loadList = useCallback(async () => {
        setLoading(true)
        try {
            const r = await fetch(`${API}?op=list`)
            if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            setLoading(false)
            if (d.success) setBacheche(d.bacheche)
            else setError(d.message ?? 'Errore nel caricamento')
        } catch {
            setLoading(false)
            setError('Errore di rete')
        }
    }, [])

    useEffect(() => { loadList() }, [loadList])

    const apriModifica = async (bacheca) => {
        if (tipi.length === 0) {
            const r = await fetch(`${API}?op=tipi`)
            if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            if (d.success) { setTipi(d.tipi); setRazze(d.razze); setGilde(d.gilde); setMestieri(d.mestieri) }
        }
        setEditing(bacheca ?? emptyBacheca())
    }

    const elimina = async (bacheca) => {
        if (!window.confirm(`Eliminare definitivamente la bacheca «${bacheca.nome}»?\n\nVerranno cancellati anche tutti i thread e i messaggi al suo interno. Questa azione non è reversibile.`)) return
        const fd = new FormData()
        fd.append('id_araldo', bacheca.id_araldo)
        const r = await fetch(`${API}?op=delete`, { method: 'POST', body: fd })
        if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setMsg({ ok: d.success, text: d.message ?? (d.success ? 'Bacheca eliminata.' : 'Errore nell\'eliminazione') })
        setTimeout(() => setMsg(null), 3000)
        if (d.success) loadList()
    }

    const onSaved = () => {
        setEditing(null)
        loadList()
    }

    return (
        <div className="pagina_gestione_bacheche">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" onClick={() => window.history.back()} className="gp-back" title="Indietro">
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
                <div className="gp-topbar__center">
                    <span className="gp-title">Gestione Bacheche</span>
                </div>
                <div className="gp-topbar__right">
                    <button className="btn btn--primary btn-sm" onClick={() => apriModifica(null)}>
                        <i className="fa-solid fa-plus"></i>&nbsp; Nuova Bacheca
                    </button>
                </div>
            </div>

            {error && <div className="gm-feedback gm-feedback--error" style={{ margin: '12px' }}>{error}</div>}
            {msg && <div className={`gm-feedback gm-feedback--${msg.ok ? 'ok' : 'error'}`} style={{ margin: '12px' }}>{msg.text}</div>}

            <div className="gp-list">
                <table className="gp-table--bacheche">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Visibile</th>
                            <th className="gp-th-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={4} style={{ textAlign: 'center', padding: 20 }}>Caricamento…</td></tr>
                        ) : bacheche.length === 0 ? (
                            <tr><td colSpan={4} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessuna bacheca trovata.</td></tr>
                        ) : bacheche.map(b => (
                            <tr key={b.id_araldo}>
                                <td className="gp-cell--name">{b.nome}</td>
                                <td>{b.tipo_label}</td>
                                <td>{b.invisibile ? 'No' : 'Sì'}</td>
                                <td className="gp-cell--actions">
                                    <div className="gp-actions">
                                        <button className="btn-action btn-action--edit btn-action--icon" title="Modifica" onClick={() => apriModifica(b)}>
                                            <i className="fa-solid fa-pencil"></i>
                                        </button>
                                        <button className="btn-action btn-action--delete btn-action--icon" title="Elimina definitivamente" onClick={() => elimina(b)}>
                                            <i className="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {editing && (
                <BachecaModal
                    bacheca={editing}
                    tipi={tipi}
                    razze={razze}
                    gilde={gilde}
                    mestieri={mestieri}
                    onClose={() => setEditing(null)}
                    onSaved={onSaved}
                />
            )}
        </div>
    )
}
