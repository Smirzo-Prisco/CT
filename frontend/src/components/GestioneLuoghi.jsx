/**
 * GestioneLuoghi.jsx — Pannello admin gestione luoghi (stanze di gioco)
 *
 * Sostituisce pages/gestione_luoghi.inc.php (PHP monolitico, upload immagine
 * con validazione solo per estensione e nome file originale — due stanze che
 * caricano un file con lo stesso nome si sovrascrivevano a vicenda in
 * silenzio — nessuna conferma prima di eliminare, nessun controllo prima di
 * eliminare pur con mestieri/personaggi che possono dipendere dalla stanza,
 * e descrizione_immagine — usata dal popup descrizione luogo in Hud.jsx/
 * ChatViewer.jsx — esisteva in DB ma non era mai stata esposta nel form).
 */

import { useState, useEffect, useCallback } from 'react'
import { createPortal } from 'react-dom'

const API = '/pages/api_luoghi.php'

function emptyLuogo() {
    return {
        id: null, nome: '', descrizione: '', stato: '', chat: 1,
        immagine: '', descrizione_immagine: '', link_immagine: '', link_immagine_hover: '',
        pagina: '', stanza_apparente: '', id_mappa: -1, id_mappa_collegata: 0,
        x_cord: 0, y_cord: 0, privata: 0, proprietario: '', scadenza: '', costo: 0,
    }
}

// ── ImageField ────────────────────────────────────────────────────────────
// Stesso pattern di GestioneMestieri.jsx: thumbnail + file input, nessun
// upload finché non si salva il form.

function ImageField({ id, label, currentUrl, folder, defaultFile, onFileChange }) {
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
                    src={preview ?? `themes/crystal/imgs/${folder}/${currentUrl || defaultFile}`}
                    alt=""
                    className="gm-image-thumb"
                    onError={e => { e.target.style.display = 'none' }}
                />
                <input id={id} type="file" accept="image/jpeg,image/png,image/gif,image/webp" onChange={handleChange} />
            </div>
        </div>
    )
}

// ── LuogoModal ────────────────────────────────────────────────────────────

function LuogoModal({ id, meta, onClose, onSaved }) {
    const [form, setForm] = useState(null)
    const [immagineFile, setImmagineFile] = useState(null)
    const [descrImgFile, setDescrImgFile] = useState(null)
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)

    useEffect(() => {
        if (!id) { setForm(emptyLuogo()); return }
        fetch(`${API}?op=get&id=${id}`)
            .then(r => r.json())
            .then(d => { if (d.success) setForm({ ...d.luogo, scadenza: (d.luogo.scadenza || '').slice(0, 10) }); else setError(d.message ?? 'Errore nel caricamento') })
            .catch(() => setError('Errore di rete'))
    }, [id])

    const isEdit = !!id

    const submit = async (e) => {
        e.preventDefault()
        setSaving(true)
        setError(null)

        const fd = new FormData()
        if (isEdit) fd.append('id', id)
        fd.append('nome', form.nome)
        fd.append('descrizione', form.descrizione)
        fd.append('stato', form.stato)
        if (form.chat) fd.append('chat', '1')
        fd.append('link_immagine', form.link_immagine)
        fd.append('link_immagine_hover', form.link_immagine_hover)
        fd.append('pagina', form.pagina)
        fd.append('stanza_apparente', form.stanza_apparente)
        fd.append('id_mappa', form.id_mappa)
        fd.append('id_mappa_collegata', form.id_mappa_collegata)
        fd.append('x_cord', form.x_cord)
        fd.append('y_cord', form.y_cord)
        if (meta.privaterooms) {
            if (form.privata) fd.append('privata', '1')
            fd.append('proprietario', form.proprietario)
            fd.append('scadenza', form.scadenza)
            fd.append('costo', form.costo)
        }
        if (immagineFile) fd.append('immagine', immagineFile)
        if (descrImgFile) fd.append('descrizione_immagine', descrImgFile)

        const r = await fetch(`${API}?op=save`, { method: 'POST', body: fd })
        if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved()
        else setError(d.message ?? 'Errore nel salvataggio')
    }

    // Portal su document.body: #maincontent (position:fixed) crea un proprio
    // stacking context — stesso motivo del portal in GestioneMappe.jsx.
    return createPortal(
        <div className="pg-edit-container" style={{ display: 'flex' }} role="dialog" aria-modal="true">
            <div className="modal-content">
                <div className="gp-modal-header">
                    <h2 className="gp-modal-title">
                        <i className="fa-solid fa-location-dot"></i>
                        {isEdit ? `Modifica — ${form?.nome ?? '…'}` : 'Nuovo luogo'}
                    </h2>
                    <div className="gp-modal-header-actions">
                        <button type="button" className="gp-modal-close" onClick={onClose} aria-label="Chiudi">✕</button>
                    </div>
                </div>

                {!form ? (
                    <div className="form-section" style={{ textAlign: 'center', padding: 20 }}>Caricamento…</div>
                ) : (
                <form id="formSaveLuogo" onSubmit={submit}>

                    <div className="form-section">
                        <h3 className="section-title">Informazioni base</h3>
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="l-nome">Nome</label>
                                <input id="l-nome" value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} required />
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="l-stanza-apparente">Nome visualizzato nei presenti</label>
                                <input id="l-stanza-apparente" value={form.stanza_apparente} onChange={e => setForm({ ...form, stanza_apparente: e.target.value })} />
                                <p className="gp-label-note">Se specificato, sostituisce il nome della stanza nell'elenco presenti.</p>
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="l-descrizione">Descrizione</label>
                                <textarea id="l-descrizione" rows={5} value={form.descrizione} onChange={e => setForm({ ...form, descrizione: e.target.value })} />
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="l-stato">Stato</label>
                                <textarea id="l-stato" rows={5} value={form.stato} onChange={e => setForm({ ...form, stato: e.target.value })} />
                            </div>
                        </div>
                    </div>

                    <div className="form-section">
                        <h3 className="section-title">Posizione e navigazione</h3>
                        <div className="form-row">
                            <div className="form-group gp-checkbox-field">
                                <input id="l-chat" type="checkbox" checked={!!form.chat}
                                       onChange={e => setForm({ ...form, chat: e.target.checked ? 1 : 0 })} />
                                <label htmlFor="l-chat">Chat attiva <span className="gp-label-note">— se disattivato il luogo è una pagina statica senza chat di gioco</span></label>
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="l-mappa">Mappa di appartenenza</label>
                                <select id="l-mappa" value={form.id_mappa} onChange={e => setForm({ ...form, id_mappa: e.target.value })}>
                                    <option value="-1">— nessuna —</option>
                                    {meta.mappe.map(m => <option key={m.id} value={m.id}>{m.nome}</option>)}
                                </select>
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="l-mappa-collegata">Bottone verso un'altra mappa</label>
                                <select id="l-mappa-collegata" value={form.id_mappa_collegata} onChange={e => setForm({ ...form, id_mappa_collegata: e.target.value })}>
                                    <option value="0">— nessuno —</option>
                                    {meta.mappe.map(m => <option key={m.id} value={m.id}>{m.nome}</option>)}
                                </select>
                                <p className="gp-label-note">Se impostato, mostra qui un link/pulsante per spostarsi su un'altra mappa grande.</p>
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="l-x">Coordinata X</label>
                                <input id="l-x" type="number" value={form.x_cord} onChange={e => setForm({ ...form, x_cord: e.target.value })} />
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="l-y">Coordinata Y</label>
                                <input id="l-y" type="number" value={form.y_cord} onChange={e => setForm({ ...form, y_cord: e.target.value })} />
                            </div>
                        </div>
                        <p className="gp-label-note">Posizione del punto cliccabile sull'immagine della mappa di appartenenza (in pixel, dall'angolo in alto a sinistra).</p>
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="l-pagina">Pagina</label>
                                <input id="l-pagina" value={form.pagina} onChange={e => setForm({ ...form, pagina: e.target.value })} />
                            </div>
                        </div>
                    </div>

                    <div className="form-section">
                        <h3 className="section-title">Immagini</h3>
                        <div className="form-row">
                            <ImageField id="l-immagine" label="Immagine luogo" currentUrl={form.immagine} folder="locations" defaultFile="standard_luogo.png" onFileChange={setImmagineFile} />
                            <ImageField id="l-descr-img" label="Immagine descrizione (popup)" currentUrl={form.descrizione_immagine} folder="descrizioni" defaultFile="standard_luogo.png" onFileChange={setDescrImgFile} />
                        </div>
                        <div className="form-row">
                            <div className="form-group form-column">
                                <label htmlFor="l-link-img">Immagine bottone (opzionale)</label>
                                <input id="l-link-img" value={form.link_immagine} onChange={e => setForm({ ...form, link_immagine: e.target.value })} />
                                <p className="gp-label-note">URL di un'immagine da usare al posto del link testuale.</p>
                            </div>
                            <div className="form-group form-column">
                                <label htmlFor="l-link-img-hover">Immagine bottone al passaggio del mouse</label>
                                <input id="l-link-img-hover" value={form.link_immagine_hover} onChange={e => setForm({ ...form, link_immagine_hover: e.target.value })} />
                            </div>
                        </div>
                    </div>

                    {meta.privaterooms && (
                        <div className="form-section">
                            <h3 className="section-title">Stanza privata</h3>
                            <div className="form-row">
                                <div className="form-group gp-checkbox-field">
                                    <input id="l-privata" type="checkbox" checked={!!form.privata}
                                           onChange={e => setForm({ ...form, privata: e.target.checked ? 1 : 0 })} />
                                    <label htmlFor="l-privata">Stanza privata</label>
                                </div>
                            </div>
                            {!!form.privata && (
                                <>
                                    <div className="form-row">
                                        <div className="form-group form-column">
                                            <label htmlFor="l-proprietario">Proprietario</label>
                                            <select id="l-proprietario" value={form.proprietario} onChange={e => setForm({ ...form, proprietario: e.target.value })}>
                                                <option value="">— nessuno —</option>
                                                <optgroup label="Gilde">
                                                    {meta.gilde.map(g => <option key={`g-${g.id}`} value={g.id}>{g.nome}</option>)}
                                                </optgroup>
                                                <optgroup label="Personaggi">
                                                    {meta.personaggi.map(p => <option key={`p-${p.nome}`} value={p.nome}>{p.nome} {p.cognome}</option>)}
                                                </optgroup>
                                            </select>
                                        </div>
                                        <div className="form-group form-column">
                                            <label htmlFor="l-costo">Costo affitto</label>
                                            <input id="l-costo" type="number" value={form.costo} onChange={e => setForm({ ...form, costo: e.target.value })} />
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group form-column">
                                            <label htmlFor="l-scadenza">Scadenza</label>
                                            <input id="l-scadenza" type="date" value={form.scadenza} onChange={e => setForm({ ...form, scadenza: e.target.value })} />
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {error && <p className="gm-feedback gm-feedback--error" style={{ margin: '0 20px 16px' }}>{error}</p>}

                    <div className="gp-modal-footer">
                        <button type="button" className="btn btn--ghost" onClick={onClose}>Annulla</button>
                        <button type="submit" className="btn btn--ghost" disabled={saving}>
                            <i className="fa-solid fa-floppy-disk"></i>&nbsp; {saving ? 'Salvataggio…' : (isEdit ? 'Salva modifiche' : 'Crea luogo')}
                        </button>
                    </div>
                </form>
                )}
            </div>
        </div>,
        document.body
    )
}

// ── GestioneLuoghi ────────────────────────────────────────────────────────

export default function GestioneLuoghi() {
    const [luoghi, setLuoghi]     = useState([])
    const [totale, setTotale]     = useState(0)
    const [perPage, setPerPage]   = useState(15)
    const [page, setPage]         = useState(0)
    const [meta, setMeta]         = useState(null)
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)
    const [editingId, setEditingId] = useState(undefined) // undefined = chiuso, null = nuovo, N = modifica id N
    const [msg, setMsg]           = useState(null)

    const loadList = useCallback(async (offset = 0) => {
        setLoading(true)
        try {
            const r = await fetch(`${API}?op=list&offset=${offset}`)
            if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            setLoading(false)
            if (d.success) {
                setLuoghi(d.luoghi)
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

    const apriModifica = async (id) => {
        if (!meta) {
            const r = await fetch(`${API}?op=meta`)
            if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const d = await r.json()
            if (d.success) setMeta(d)
        }
        setEditingId(id ?? null)
    }

    const elimina = async (luogo) => {
        if (!window.confirm(`Eliminare definitivamente il luogo «${luogo.nome}»?\n\nQuesta azione non è reversibile.`)) return
        const fd = new FormData()
        fd.append('id', luogo.id)
        const r = await fetch(`${API}?op=delete`, { method: 'POST', body: fd })
        if (r.status === 401 || r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setMsg({ ok: d.success, text: d.message ?? (d.success ? 'Luogo eliminato.' : 'Errore nell\'eliminazione') })
        setTimeout(() => setMsg(null), 5000)
        if (d.success) loadList(page)
    }

    const onSaved = () => {
        setEditingId(undefined)
        loadList(page)
    }

    const totalPages = Math.max(1, Math.ceil(totale / (perPage || 1)))

    return (
        <div className="pagina_gestione_luoghi">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" onClick={() => window.history.back()} className="gp-back" title="Indietro">
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
                <div className="gp-topbar__center">
                    <span className="gp-title">Gestione Luoghi</span>
                </div>
                <div className="gp-topbar__right">
                    <button className="btn btn--primary btn-sm" onClick={() => apriModifica(null)}>
                        <i className="fa-solid fa-plus"></i>&nbsp; Nuovo Luogo
                    </button>
                </div>
            </div>

            {error && <div className="gm-feedback gm-feedback--error" style={{ margin: '12px' }}>{error}</div>}
            {msg && <div className={`gm-feedback gm-feedback--${msg.ok ? 'ok' : 'error'}`} style={{ margin: '12px' }}>{msg.text}</div>}

            <div className="gp-list">
                <table className="gp-table--luoghi">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Mappa</th>
                            <th>Chat</th>
                            <th>Privata</th>
                            <th className="gp-th-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={5} style={{ textAlign: 'center', padding: 20 }}>Caricamento…</td></tr>
                        ) : luoghi.length === 0 ? (
                            <tr><td colSpan={5} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessun luogo trovato.</td></tr>
                        ) : luoghi.map(l => (
                            <tr key={l.id}>
                                <td className="gp-cell--name">{l.nome}</td>
                                <td>{l.mappa_nome}</td>
                                <td>{l.chat ? 'Sì' : 'No'}</td>
                                <td>{l.privata ? 'Sì' : 'No'}</td>
                                <td className="gp-cell--actions">
                                    <div className="gp-actions">
                                        <button className="btn-action btn-action--edit btn-action--icon" title="Modifica" onClick={() => apriModifica(l.id)}>
                                            <i className="fa-solid fa-pencil"></i>
                                        </button>
                                        <button className="btn-action btn-action--delete btn-action--icon" title="Elimina definitivamente" onClick={() => elimina(l)}>
                                            <i className="fa-solid fa-trash-can"></i>
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

            {editingId !== undefined && meta && (
                <LuogoModal id={editingId} meta={meta} onClose={() => setEditingId(undefined)} onSaved={onSaved} />
            )}
        </div>
    )
}
