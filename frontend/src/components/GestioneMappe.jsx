/**
 * GestioneMappe.jsx — Pannello admin gestione mappe grandi (mappa_click)
 *
 * Sostituisce pages/gestione_mappe.inc.php (PHP monolitico, nessuna conferma
 * prima di eliminare, e soprattutto nessun controllo prima di eliminare: le
 * stanze collegate — mappa.id_mappa → mappa_click.id_click, senza vincolo di
 * integrità nello schema — restavano orfane in silenzio. Meteo e Descrizione
 * esistevano in DB ma non erano mai stati esposti nel form.
 */

import { useState, useEffect, useCallback } from 'react'
import { createPortal } from 'react-dom'

const API = '/pages/api_mappe.php'

function emptyMappa() {
    return { id_click: null, nome: '', posizione: 0, mobile: 0, immagine: 'standard_mappa.png', larghezza: 500, altezza: 330, meteo: '', descrizione: '' }
}

// ── MappaModal ────────────────────────────────────────────────────────────

function MappaModal({ id, onClose, onSaved }) {
    const [form, setForm] = useState(null) // null finché non è caricato
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)

    useEffect(() => {
        if (!id) { setForm(emptyMappa()); return }
        fetch(`${API}?op=get&id=${id}`)
            .then(r => r.json())
            .then(d => { if (d.success) setForm(d.mappaClick); else setError(d.message ?? 'Errore nel caricamento') })
            .catch(() => setError('Errore di rete'))
    }, [id])

    const isEdit = !!id

    const submit = async (e) => {
        e.preventDefault()
        setSaving(true)
        setError(null)

        const fd = new FormData()
        if (isEdit) fd.append('id_click', id)
        fd.append('nome', form.nome)
        fd.append('posizione', form.posizione)
        if (form.mobile) fd.append('mobile', '1')
        fd.append('immagine', form.immagine)
        fd.append('larghezza', form.larghezza)
        fd.append('altezza', form.altezza)
        fd.append('meteo', form.meteo)
        fd.append('descrizione', form.descrizione)

        const r = await fetch(`${API}?op=save`, { method: 'POST', body: fd })
        if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved()
        else setError(d.message ?? 'Errore nel salvataggio')
    }

    // Portal su document.body: #maincontent (position:fixed) crea un proprio
    // stacking context, un z-index qui dentro resterebbe intrappolato sotto
    // .ct-hud — stesso motivo del portal in GestioneBacheche.jsx.
    return createPortal(
        <div className="pg-edit-container" style={{ display: 'flex' }} role="dialog" aria-modal="true">
            <div className="modal-content">
                <div className="gp-modal-header">
                    <h2 className="gp-modal-title">
                        <i className="fa-solid fa-map"></i>
                        {isEdit ? `Modifica — ${form?.nome ?? '…'}` : 'Nuova mappa'}
                    </h2>
                    <div className="gp-modal-header-actions">
                        <button type="button" className="gp-modal-close" onClick={onClose} aria-label="Chiudi">✕</button>
                    </div>
                </div>

                {!form ? (
                    <div className="form-section" style={{ textAlign: 'center', padding: 20 }}>Caricamento…</div>
                ) : (
                <form id="formSaveMappa" onSubmit={submit}>
                    <div className="form-section">
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-nome">Nome</label>
                                <input id="m-nome" value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} required />
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="m-posizione">Posizione</label>
                                <input id="m-posizione" type="number" value={form.posizione} onChange={e => setForm({ ...form, posizione: e.target.value })} />
                                <p className="gp-label-note">Le mappe con lo stesso valore mostrano un link reciproco per spostarsi dall'una all'altra.</p>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-immagine">Immagine</label>
                                <input id="m-immagine" value={form.immagine} onChange={e => setForm({ ...form, immagine: e.target.value })} placeholder="standard_mappa.png" />
                            </div>
                            <div className="form-group gp-checkbox-field">
                                <input id="m-mobile" type="checkbox" checked={!!form.mobile}
                                       onChange={e => setForm({ ...form, mobile: e.target.checked ? 1 : 0 })} />
                                <label htmlFor="m-mobile">Mappa mobile <span className="gp-label-note">— pensata per ambienti che si spostano tra vicinati, come navi o carovane</span></label>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-larghezza">Larghezza (px)</label>
                                <input id="m-larghezza" type="number" min="1" value={form.larghezza} onChange={e => setForm({ ...form, larghezza: e.target.value })} required />
                                <p className="gp-label-note">Larghezza in pixel del file immagine della mappa.</p>
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="m-altezza">Altezza (px)</label>
                                <input id="m-altezza" type="number" min="1" value={form.altezza} onChange={e => setForm({ ...form, altezza: e.target.value })} required />
                                <p className="gp-label-note">Altezza in pixel del file immagine della mappa.</p>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-meteo">Meteo (testo di fallback)</label>
                                <input id="m-meteo" value={form.meteo} onChange={e => setForm({ ...form, meteo: e.target.value })} placeholder="20°c - sereno" />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-descrizione">Descrizione</label>
                                <textarea id="m-descrizione" rows={4} value={form.descrizione} onChange={e => setForm({ ...form, descrizione: e.target.value })} />
                            </div>
                        </div>

                        {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
                    </div>

                    <div className="gp-modal-footer">
                        <button type="button" className="btn btn--ghost" onClick={onClose}>Annulla</button>
                        <button type="submit" className="btn btn--ghost" disabled={saving}>
                            <i className="fa-solid fa-floppy-disk"></i>&nbsp; {saving ? 'Salvataggio…' : (isEdit ? 'Salva modifiche' : 'Crea mappa')}
                        </button>
                    </div>
                </form>
                )}
            </div>
        </div>,
        document.body
    )
}

// ── GestioneMappe ─────────────────────────────────────────────────────────

export default function GestioneMappe() {
    const [mappe, setMappe]     = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)
    const [editingId, setEditingId] = useState(undefined) // undefined = chiuso, null = nuova, N = modifica id N
    const [msg, setMsg]         = useState(null)

    const loadList = useCallback(async () => {
        setLoading(true)
        try {
            const r = await fetch(`${API}?op=list`)
            if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            setLoading(false)
            if (d.success) setMappe(d.mappe)
            else setError(d.message ?? 'Errore nel caricamento')
        } catch {
            setLoading(false)
            setError('Errore di rete')
        }
    }, [])

    useEffect(() => { loadList() }, [loadList])

    const elimina = async (mappa) => {
        if (!window.confirm(`Eliminare definitivamente la mappa «${mappa.nome}»?\n\nQuesta azione non è reversibile.`)) return
        const fd = new FormData()
        fd.append('id_click', mappa.id_click)
        const r = await fetch(`${API}?op=delete`, { method: 'POST', body: fd })
        if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setMsg({ ok: d.success, text: d.message ?? (d.success ? 'Mappa eliminata.' : 'Errore nell\'eliminazione') })
        setTimeout(() => setMsg(null), 4000)
        if (d.success) loadList()
    }

    const onSaved = () => {
        setEditingId(undefined)
        loadList()
    }

    return (
        <div className="pagina_gestione_mappe">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" onClick={() => window.history.back()} className="gp-back" title="Indietro">
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
                <div className="gp-topbar__center">
                    <span className="gp-title">Gestione Mappe</span>
                </div>
                <div className="gp-topbar__right">
                    <button className="btn btn--primary btn-sm" onClick={() => setEditingId(null)}>
                        <i className="fa-solid fa-plus"></i>&nbsp; Nuova Mappa
                    </button>
                </div>
            </div>

            {error && <div className="gm-feedback gm-feedback--error" style={{ margin: '12px' }}>{error}</div>}
            {msg && <div className={`gm-feedback gm-feedback--${msg.ok ? 'ok' : 'error'}`} style={{ margin: '12px' }}>{msg.text}</div>}

            <div className="gp-list">
                <table className="gp-table--mappe">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Posizione</th>
                            <th>Mobile</th>
                            <th>Stanze</th>
                            <th className="gp-th-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={5} style={{ textAlign: 'center', padding: 20 }}>Caricamento…</td></tr>
                        ) : mappe.length === 0 ? (
                            <tr><td colSpan={5} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessuna mappa trovata.</td></tr>
                        ) : mappe.map(m => (
                            <tr key={m.id_click}>
                                <td className="gp-cell--name">{m.nome}</td>
                                <td>{m.posizione}</td>
                                <td>{m.mobile ? 'Sì' : 'No'}</td>
                                <td>{m.n_stanze}</td>
                                <td className="gp-cell--actions">
                                    <div className="gp-actions">
                                        <button className="btn-action btn-action--edit btn-action--icon" title="Modifica" onClick={() => setEditingId(m.id_click)}>
                                            <i className="fa-solid fa-pencil"></i>
                                        </button>
                                        <button className="btn-action btn-action--delete btn-action--icon" title="Elimina definitivamente" onClick={() => elimina(m)}>
                                            <i className="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {editingId !== undefined && (
                <MappaModal id={editingId} onClose={() => setEditingId(undefined)} onSaved={onSaved} />
            )}
        </div>
    )
}
