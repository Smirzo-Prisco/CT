/**
 * GestioneMestieri.jsx — Pannello admin gestione mestieri e ruoli
 *
 * Sostituisce pages/gestione_mestieri.inc.php (391 righe di PHP monolitico).
 * Gestisce due entità: mestiere (professione) e ruolo_mestiere (gradi dentro
 * un mestiere), con upload immagine per entrambe tramite l'endpoint
 * pages/api_mestieri.php, che usa la funzione centralizzata
 * saveUploadedImage() in includes/custom_functions.inc.php.
 */

import { useState, useEffect, useCallback, useRef } from 'react'

const API = '/pages/api_mestieri.php'

function emptyMestiere() {
    return { id_mestiere: null, nome: '', tipo: '', url_sito: 'http://', visibile: 1, statuto: '', immagine: '' }
}

function emptyRuoloForm() {
    return { nome: '', livello_mestiere: 3, stipendio: 0, capo: false }
}

// ── RoleImagePreview ─────────────────────────────────────────────────────────

function ImageField({ id, currentUrl, onFileChange, label }) {
    const [preview, setPreview] = useState(null)

    const handleChange = (e) => {
        const file = e.target.files?.[0] ?? null
        onFileChange(file)
        if (file) {
            const reader = new FileReader()
            reader.onload = () => setPreview(reader.result)
            reader.readAsDataURL(file)
        } else {
            setPreview(null)
        }
    }

    return (
        <div className="form-group">
            <label htmlFor={id}>{label}</label>
            <div className="gm-image-field">
                <img
                    src={preview ?? (currentUrl ? `imgs/mestieri/${currentUrl}` : 'imgs/mestieri/standard_mestiere.png')}
                    alt=""
                    className="gm-image-thumb"
                    onError={e => { e.target.style.visibility = 'hidden' }}
                />
                <input id={id} type="file" accept="image/jpeg,image/png,image/gif,image/webp" onChange={handleChange} />
            </div>
        </div>
    )
}

// ── RuoloRow ─────────────────────────────────────────────────────────────────

function RuoloRow({ ruolo, onSaved, onDeleted }) {
    const [form, setForm] = useState({
        nome: ruolo.nome_ruolo,
        livello_mestiere: ruolo.livello_mestiere,
        stipendio: ruolo.stipendio,
        capo: ruolo.capo == 1,
    })
    const [file, setFile] = useState(null)
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)

    const save = async () => {
        setSaving(true)
        setError(null)
        const fd = new FormData()
        fd.append('id_ruolo', ruolo.id_ruolo)
        fd.append('mestiere', ruolo.mestiere)
        fd.append('nome', form.nome)
        fd.append('livello_mestiere', form.livello_mestiere)
        fd.append('stipendio', form.stipendio)
        fd.append('capo', form.capo ? '1' : '0')
        if (file) fd.append('immagine', file)

        const r = await fetch(`${API}?op=save_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved(d.ruoli)
        else setError(d.message ?? 'Errore nel salvataggio del ruolo')
    }

    const remove = async () => {
        if (!window.confirm(`Eliminare il ruolo «${ruolo.nome_ruolo}»?\nTutti i personaggi con questo ruolo ne verranno rimossi.`)) return
        const fd = new FormData()
        fd.append('id_ruolo', ruolo.id_ruolo)
        fd.append('mestiere', ruolo.mestiere)
        const r = await fetch(`${API}?op=delete_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        if (d.success) onDeleted(d.ruoli)
        else setError(d.message ?? 'Errore nell\'eliminazione del ruolo')
    }

    return (
        <div className="gm-role-row">
            <img
                src={`imgs/mestieri/${ruolo.immagine}`}
                alt=""
                className="gm-image-thumb gm-image-thumb--sm"
                onError={e => { e.target.style.visibility = 'hidden' }}
            />
            <div className="form-row gm-role-fields">
                <div className="form-group form-column">
                    <label>Nome ruolo</label>
                    <input value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} />
                </div>
                <div className="form-group" style={{ maxWidth: 90 }}>
                    <label>Livello (1-3)</label>
                    <input type="number" min="1" max="3" value={form.livello_mestiere}
                           onChange={e => setForm({ ...form, livello_mestiere: e.target.value })} />
                </div>
                <div className="form-group" style={{ maxWidth: 110 }}>
                    <label>Stipendio</label>
                    <input type="number" value={form.stipendio}
                           onChange={e => setForm({ ...form, stipendio: e.target.value })} />
                </div>
                <div className="form-group">
                    <label>Nuova immagine</label>
                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp"
                           onChange={e => setFile(e.target.files?.[0] ?? null)} />
                </div>
                <div className="form-group gp-checkbox-field">
                    <input id={`capo-${ruolo.id_ruolo}`} type="checkbox" checked={form.capo}
                           onChange={e => setForm({ ...form, capo: e.target.checked })} />
                    <label htmlFor={`capo-${ruolo.id_ruolo}`}>Controlli di mestiere</label>
                </div>
            </div>
            <div className="gp-actions">
                <button className="btn-action btn-action--edit btn-action--icon" title="Salva" onClick={save} disabled={saving}>
                    <i className="fa-solid fa-floppy-disk"></i>
                </button>
                <button className="btn-action btn-action--delete btn-action--icon" title="Elimina" onClick={remove} disabled={saving}>
                    <i className="fa-solid fa-trash"></i>
                </button>
            </div>
            {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
        </div>
    )
}

// ── MestiereModal ─────────────────────────────────────────────────────────────

function MestiereModal({ mestiere, ruoli, tipi, onClose, onSaved, onRuoliChange }) {
    const [form, setForm] = useState(mestiere)
    const [file, setFile] = useState(null)
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)
    const [nuovoRuolo, setNuovoRuolo] = useState(emptyRuoloForm())
    const [nuovoRuoloFile, setNuovoRuoloFile] = useState(null)
    const [nuovoRuoloSaving, setNuovoRuoloSaving] = useState(false)
    const formRef = useRef(null)

    const isEdit = !!form.id_mestiere
    const isIndipendenti = form.id_mestiere === -1

    const submit = async (e) => {
        e.preventDefault()
        setSaving(true)
        setError(null)
        const fd = new FormData()
        if (isEdit) fd.append('id_mestiere', form.id_mestiere)
        fd.append('nome', form.nome)
        fd.append('tipo', form.tipo)
        fd.append('url_sito', form.url_sito)
        fd.append('statuto', form.statuto)
        fd.append('visibile', form.visibile ? '1' : '0')
        if (file) fd.append('immagine', file)

        const r = await fetch(`${API}?op=save`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved(d.mestiere)
        else setError(d.message ?? 'Errore nel salvataggio')
    }

    const aggiungiRuolo = async (e) => {
        e.preventDefault()
        if (!nuovoRuolo.nome.trim()) return
        setNuovoRuoloSaving(true)
        const fd = new FormData()
        fd.append('mestiere', form.id_mestiere)
        fd.append('nome', nuovoRuolo.nome)
        fd.append('livello_mestiere', nuovoRuolo.livello_mestiere)
        fd.append('stipendio', nuovoRuolo.stipendio)
        fd.append('capo', nuovoRuolo.capo ? '1' : '0')
        if (nuovoRuoloFile) fd.append('immagine', nuovoRuoloFile)

        const r = await fetch(`${API}?op=save_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setNuovoRuoloSaving(false)
        if (d.success) {
            onRuoliChange(d.ruoli)
            setNuovoRuolo(emptyRuoloForm())
            setNuovoRuoloFile(null)
        }
    }

    return (
        <div className="pg-edit-container" style={{ display: 'flex' }} role="dialog" aria-modal="true">
            <div className="modal-content">
                <div className="gp-modal-header">
                    <h2 className="gp-modal-title">
                        <i className="fa-solid fa-briefcase"></i>
                        {isIndipendenti ? 'Ruoli — Lavori indipendenti' : isEdit ? `Modifica — ${mestiere.nome}` : 'Nuovo mestiere'}
                    </h2>
                    <div className="gp-modal-header-actions">
                        <button type="button" className="gp-modal-close" onClick={onClose} aria-label="Chiudi">✕</button>
                    </div>
                </div>

                {!isIndipendenti && (
                <form id="formSaveMestiere" ref={formRef} onSubmit={submit}>
                    <div className="form-section">
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-nome">Nome</label>
                                <input id="m-nome" value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} required />
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="m-tipo">Tipo</label>
                                {tipi.length > 0 ? (
                                    <select id="m-tipo" value={form.tipo} onChange={e => setForm({ ...form, tipo: e.target.value })} required>
                                        <option value="">— seleziona —</option>
                                        {tipi.map(t => <option key={t.cod_tipo} value={t.cod_tipo}>{t.descrizione}</option>)}
                                    </select>
                                ) : (
                                    <p className="gp-label-note">Nessun tipo disponibile.</p>
                                )}
                                <a href="main.php?page=gestione_tipi&types=jobs" className="gp-label-note">Gestisci tipi</a>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-sito">URL del sito</label>
                                <input id="m-sito" value={form.url_sito} onChange={e => setForm({ ...form, url_sito: e.target.value })} />
                            </div>
                            <ImageField id="m-immagine" currentUrl={form.immagine} onFileChange={setFile} label="Immagine" />
                        </div>

                        <div className="form-row">
                            <div className="form-group gp-checkbox-field">
                                <input id="m-visibile" type="checkbox" checked={!!form.visibile}
                                       onChange={e => setForm({ ...form, visibile: e.target.checked })} />
                                <label htmlFor="m-visibile">Visibile <span className="gp-label-note">— il mestiere sarà visibile ai giocatori</span></label>
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="m-statuto">Statuto</label>
                                <textarea id="m-statuto" rows={6} value={form.statuto} onChange={e => setForm({ ...form, statuto: e.target.value })} />
                            </div>
                        </div>

                        {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
                    </div>
                </form>
                )}

                {isEdit && (
                        <div className="form-section">
                            <h3 className="section-title">Ruoli — livello 3 = più basso, 1 = più alto</h3>

                            <div className="gm-role-row gm-role-row--new">
                                <div className="form-row gm-role-fields">
                                    <div className="form-group form-column">
                                        <label>Nome ruolo</label>
                                        <input placeholder="Nuovo ruolo…" value={nuovoRuolo.nome}
                                               onChange={e => setNuovoRuolo({ ...nuovoRuolo, nome: e.target.value })} />
                                    </div>
                                    <div className="form-group" style={{ maxWidth: 90 }}>
                                        <label>Livello</label>
                                        <input type="number" min="1" max="3" value={nuovoRuolo.livello_mestiere}
                                               onChange={e => setNuovoRuolo({ ...nuovoRuolo, livello_mestiere: e.target.value })} />
                                    </div>
                                    <div className="form-group" style={{ maxWidth: 110 }}>
                                        <label>Stipendio</label>
                                        <input type="number" value={nuovoRuolo.stipendio}
                                               onChange={e => setNuovoRuolo({ ...nuovoRuolo, stipendio: e.target.value })} />
                                    </div>
                                    <div className="form-group">
                                        <label>Immagine</label>
                                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp"
                                               onChange={e => setNuovoRuoloFile(e.target.files?.[0] ?? null)} />
                                    </div>
                                    <div className="form-group gp-checkbox-field">
                                        <input id="nuovo-ruolo-capo" type="checkbox" checked={nuovoRuolo.capo}
                                               onChange={e => setNuovoRuolo({ ...nuovoRuolo, capo: e.target.checked })} />
                                        <label htmlFor="nuovo-ruolo-capo">Controlli di mestiere</label>
                                    </div>
                                </div>
                                <button type="button" className="btn btn--success" onClick={aggiungiRuolo} disabled={nuovoRuoloSaving}>
                                    <i className="fa-solid fa-plus"></i>&nbsp; Aggiungi ruolo
                                </button>
                            </div>

                            {ruoli.length === 0 ? (
                                <p className="gp-label-note">Nessun ruolo definito per questo mestiere.</p>
                            ) : ruoli.map(r => (
                                <RuoloRow key={r.id_ruolo} ruolo={r} onSaved={onRuoliChange} onDeleted={onRuoliChange} />
                            ))}
                        </div>
                )}

                <div className="gp-modal-footer">
                    <button type="button" className="btn btn--ghost" onClick={onClose}>Annulla</button>
                    {!isIndipendenti && (
                        <button type="submit" form="formSaveMestiere" className="btn btn--primary" disabled={saving}>
                            <i className="fa-solid fa-floppy-disk"></i>&nbsp; {saving ? 'Salvataggio…' : (isEdit ? 'Salva modifiche' : 'Crea mestiere')}
                        </button>
                    )}
                </div>
            </div>
        </div>
    )
}

// ── GestioneMestieri ─────────────────────────────────────────────────────────

export default function GestioneMestieri() {
    const [mestieri, setMestieri] = useState([])
    const [totale, setTotale]     = useState(0)
    const [perPage, setPerPage]   = useState(20)
    const [page, setPage]         = useState(0)
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)
    const [editing, setEditing]   = useState(null) // { mestiere, ruoli, tipi } oppure null
    const [hideMsg, setHideMsg]   = useState(null)

    const loadList = useCallback(async (offset = 0) => {
        setLoading(true)
        try {
            const r = await fetch(`${API}?op=list&offset=${offset}`)
            if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            setLoading(false)
            if (d.success) {
                setMestieri(d.mestieri)
                setTotale(d.totale)
                setPerPage(d.per_page)
                setPage(offset)
            } else {
                setError(d.message ?? 'Errore nel caricamento')
            }
        } catch {
            setLoading(false)
            setError('Errore di rete')
        }
    }, [])

    useEffect(() => { loadList(0) }, [loadList])

    const apriNuovo = async () => {
        const r = await fetch(`${API}?op=tipi`)
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setEditing({ mestiere: emptyMestiere(), ruoli: [], tipi: d.success ? d.tipi : [] })
    }

    const apriModifica = useCallback(async (id) => {
        const r = await fetch(`${API}?op=get&id=${id}`)
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        if (d.success) {
            setEditing({
                mestiere: { ...d.mestiere, id_mestiere: d.mestiere.id_mestiere },
                ruoli: d.ruoli,
                tipi: d.tipi,
            })
        } else {
            setError(d.message ?? 'Errore nel caricamento del mestiere')
        }
    }, [])

    // Deep-link da menu esterni: main.php?page=gestione_mestieri&op=edit&id_record=X
    // (usato es. dal menu "Lavori indipendenti" con id_record=-1)
    useEffect(() => {
        const params = new URLSearchParams(window.location.search)
        if (params.get('op') === 'edit' && params.has('id_record')) {
            apriModifica(parseInt(params.get('id_record'), 10))
        }
    }, [apriModifica])

    const nascondi = async (id, nome) => {
        if (!window.confirm(`Nascondere il mestiere «${nome}»?\nIl mestiere non sarà più visibile ma i dati verranno conservati.`)) return
        const fd = new FormData()
        fd.append('id_mestiere', id)
        const r = await fetch(`${API}?op=hide`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setHideMsg(d.message ?? null)
        setTimeout(() => setHideMsg(null), 3000)
        loadList(page)
    }

    const onMestiereSaved = () => {
        setEditing(null)
        loadList(page)
    }

    const totalPages = Math.max(1, Math.ceil(totale / (perPage || 1)))

    return (
        <div className="pagina_gestione_gilde">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <a href="javascript:history.back()" className="gp-back" title="Indietro">
                        <i className="fa-solid fa-chevron-left"></i>
                    </a>
                </div>
                <div className="gp-topbar__center">
                    <span className="gp-title">Gestione Mestieri</span>
                </div>
                <div className="gp-topbar__right">
                    <a href="main.php?page=gestione_tipi&types=jobs" className="btn btn--ghost btn-sm">Tipi</a>
                    <button className="btn btn--ghost btn-sm" onClick={() => apriModifica(-1)}>
                        Lavori indipendenti
                    </button>
                    <button className="btn btn--primary btn-sm" onClick={apriNuovo}>
                        <i className="fa-solid fa-plus"></i>&nbsp; Nuovo Mestiere
                    </button>
                </div>
            </div>

            {error && <div className="gm-feedback gm-feedback--error" style={{ margin: '12px' }}>{error}</div>}
            {hideMsg && <div className="gm-feedback gm-feedback--ok" style={{ margin: '12px' }}>{hideMsg}</div>}

            <div className="gp-list">
                <table>
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
                        ) : mestieri.length === 0 ? (
                            <tr><td colSpan={4} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessun mestiere trovato.</td></tr>
                        ) : mestieri.map(m => (
                            <tr key={m.id_mestiere}>
                                <td className="gp-cell--name">{m.nome}</td>
                                <td className="gp-cell--meta">{m.tipo_descrizione ?? '—'}</td>
                                <td>{m.visibile == 1 ? 'Sì' : 'No'}</td>
                                <td className="gp-cell--actions">
                                    <div className="gp-actions">
                                        <button className="btn-action btn-action--edit btn-action--icon" title="Modifica" onClick={() => apriModifica(m.id_mestiere)}>
                                            <i className="fa-solid fa-pencil"></i>
                                        </button>
                                        <button className="btn-action btn-action--delete btn-action--icon" title="Nascondi" onClick={() => nascondi(m.id_mestiere, m.nome)}>
                                            <i className="fa-solid fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {totalPages > 1 && (
                <div className="pager" style={{ padding: 12, textAlign: 'center' }}>
                    {Array.from({ length: totalPages }, (_, i) => i).map(i => (
                        i === page
                            ? <strong key={i}> {i + 1} </strong>
                            : <a key={i} href="#" onClick={e => { e.preventDefault(); loadList(i) }}> {i + 1} </a>
                    ))}
                </div>
            )}

            {editing && (
                <MestiereModal
                    mestiere={editing.mestiere}
                    ruoli={editing.ruoli}
                    tipi={editing.tipi}
                    onClose={() => setEditing(null)}
                    onSaved={onMestiereSaved}
                    onRuoliChange={ruoli => setEditing({ ...editing, ruoli })}
                />
            )}
        </div>
    )
}
