/**
 * GestioneStatuti.jsx — Modale statuto mestiere (StatutoModal)
 *
 * Un mestiere ha N "articoli" statuto (titolo, testo, sezione), letti dalla
 * stessa tabella `statuti` usata dallo statuto gilda ma filtrati per
 * id_mestiere. Stessa struttura modale + righe editabili inline di
 * GestioneMestieri.jsx (ruoli -> articoli).
 *
 * Riusata direttamente da GestioneMestieri.jsx (icona "Statuto" nella tabella
 * Mestieri) — non ha più una pagina standalone propria: la vecchia
 * pages/gestione_statuti_new.inc.php e il default export di questo file
 * (elenco mestieri + apertura modale) sono stati rimossi perché ormai
 * ridondanti con quell'icona.
 *
 * API: pages/api_mestieri.php (op=statuti_list, statuti_save, statuti_delete)
 */

import { useState, useEffect } from 'react'
import { createPortal } from 'react-dom'

const API = '/pages/api_mestieri.php'

/** Stessi 4 valori letti da api_statuto.php (buildSection): l'etichetta qui
 *  è quella storica del form mestiere, diversa da quella usata per le gilde
 *  (stesso "tipo" storia/statuto/skill/requisiti, testo diverso in UI). */
const TIPI_STATUTO = [
    { value: 'storia',    label: 'Statuto' },
    { value: 'statuto',   label: 'Descrizione' },
    { value: 'skill',     label: 'Cariche' },
    { value: 'requisiti', label: 'Specifiche' },
]

function emptyArticoloForm() {
    return { titolo: '', testo: '', tipo: 'storia' }
}

// ── Riga articolo esistente (editabile inline) ────────────────────────────

function ArticoloRow({ articolo, idMestiere, onSaved, onDeleted }) {
    const [form, setForm]     = useState({ titolo: articolo.titolo, testo: articolo.testo, tipo: articolo.tipo })
    const [saving, setSaving] = useState(false)
    const [saved, setSaved]   = useState(false)
    const [error, setError]   = useState(null)

    const save = async () => {
        setSaving(true)
        setError(null)
        setSaved(false)
        const fd = new FormData()
        fd.append('articolo', articolo.articolo)
        fd.append('id_mestiere', idMestiere)
        fd.append('titolo', form.titolo)
        fd.append('testo', form.testo)
        fd.append('tipo', form.tipo)
        const r = await fetch(`${API}?op=statuti_save`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) {
            onSaved(d.articoli)
            setSaved(true)
            setTimeout(() => setSaved(false), 2500)
        } else {
            setError(d.message ?? 'Errore nel salvataggio')
        }
    }

    const remove = async () => {
        if (!window.confirm(`Eliminare l'articolo «${articolo.titolo}»?`)) return
        setSaving(true)
        const fd = new FormData()
        fd.append('articolo', articolo.articolo)
        const r = await fetch(`${API}?op=statuti_delete`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onDeleted(d.articoli)
        else setError(d.message ?? 'Errore nell\'eliminazione')
    }

    return (
        <div className="gm-role-row">
            <div className="form-row gm-role-fields">
                <div className="form-group form-column">
                    <label>Titolo</label>
                    <input value={form.titolo} onChange={e => setForm({ ...form, titolo: e.target.value })} />
                </div>
                <div className="form-group" style={{ maxWidth: 160 }}>
                    <label>Sezione</label>
                    <select value={form.tipo} onChange={e => setForm({ ...form, tipo: e.target.value })}>
                        {TIPI_STATUTO.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </select>
                </div>
                <div className="form-group" style={{ flex: '1 1 100%' }}>
                    <label>Testo</label>
                    <textarea rows={4} value={form.testo} onChange={e => setForm({ ...form, testo: e.target.value })} />
                </div>
            </div>
            <div className="gp-actions">
                <button className="btn-action btn-action--edit btn-action--icon" title="Salva" onClick={save} disabled={saving}>
                    <i className={`fa-solid ${saved ? 'fa-check' : 'fa-floppy-disk'}`}></i>
                </button>
                <button className="btn-action btn-action--delete btn-action--icon" title="Elimina" onClick={remove} disabled={saving}>
                    <i className="fa-solid fa-trash"></i>
                </button>
            </div>
            {saved && <p className="gm-feedback gm-feedback--ok">Articolo salvato.</p>}
            {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
        </div>
    )
}

// ── Modale statuto di un mestiere ──────────────────────────────────────────
// Esportata: riusata anche da GestioneMestieri.jsx come azione per riga
// (icona "Statuto" nella tabella Mestieri), non solo da questa pagina.

export function StatutoModal({ mestiere, onClose }) {
    const [articoli, setArticoli] = useState([])
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)
    const [nuovo, setNuovo]       = useState(emptyArticoloForm())
    const [nuovoSaving, setNuovoSaving] = useState(false)

    useEffect(() => {
        fetch(`${API}?op=statuti_list&id_mestiere=${mestiere.id_mestiere}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setArticoli(d.articoli)
                else setError(d.message ?? 'Errore nel caricamento')
                setLoading(false)
            })
            .catch(() => { setError('Errore di rete'); setLoading(false) })
    }, [mestiere.id_mestiere])

    const aggiungiArticolo = async () => {
        if (!nuovo.titolo.trim() || !nuovo.testo.trim()) return
        setNuovoSaving(true)
        const fd = new FormData()
        fd.append('id_mestiere', mestiere.id_mestiere)
        fd.append('titolo', nuovo.titolo)
        fd.append('testo', nuovo.testo)
        fd.append('tipo', nuovo.tipo)
        const r = await fetch(`${API}?op=statuti_save`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setNuovoSaving(false)
        if (d.success) {
            setArticoli(d.articoli)
            setNuovo(emptyArticoloForm())
        } else {
            setError(d.message ?? 'Errore nel salvataggio')
        }
    }

    // Portal su document.body: #maincontent (position:fixed) crea sempre un proprio
    // stacking context, quindi qualunque z-index qui dentro resterebbe intrappolato
    // sotto .ct-hud (z-index:500) — stesso motivo del portal in GestioneMestieri.jsx.
    return createPortal(
        <div className="pg-edit-container" style={{ display: 'flex' }} role="dialog" aria-modal="true">
            <div className="modal-content">
                <div className="gp-modal-header">
                    <h2 className="gp-modal-title">
                        <i className="fa-solid fa-scroll"></i> Statuto — {mestiere.nome}
                    </h2>
                    <div className="gp-modal-header-actions">
                        <button type="button" className="gp-modal-close" onClick={onClose} aria-label="Chiudi">✕</button>
                    </div>
                </div>

                <div className="gm-modal-body">
                    {loading && <p className="gp-label-note">Caricamento…</p>}
                    {error && <div className="gm-feedback gm-feedback--error">{error}</div>}

                    {!loading && (
                        <>
                            {articoli.length === 0 && (
                                <p className="gp-label-note">Nessun articolo definito per ora.</p>
                            )}
                            {articoli.map(a => (
                                <ArticoloRow
                                    key={a.articolo}
                                    articolo={a}
                                    idMestiere={mestiere.id_mestiere}
                                    onSaved={setArticoli}
                                    onDeleted={setArticoli}
                                />
                            ))}

                            <div className="gm-role-row gm-role-row--new">
                                <div className="form-row gm-role-fields">
                                    <div className="form-group form-column">
                                        <label>Titolo</label>
                                        <input placeholder="Nuovo articolo…" value={nuovo.titolo}
                                               onChange={e => setNuovo({ ...nuovo, titolo: e.target.value })} />
                                    </div>
                                    <div className="form-group" style={{ maxWidth: 160 }}>
                                        <label>Sezione</label>
                                        <select value={nuovo.tipo} onChange={e => setNuovo({ ...nuovo, tipo: e.target.value })}>
                                            {TIPI_STATUTO.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                                        </select>
                                    </div>
                                    <div className="form-group" style={{ flex: '1 1 100%' }}>
                                        <label>Testo</label>
                                        <textarea rows={4} value={nuovo.testo}
                                                  onChange={e => setNuovo({ ...nuovo, testo: e.target.value })} />
                                    </div>
                                </div>
                                <button type="button" className="btn btn--primary" onClick={aggiungiArticolo} disabled={nuovoSaving}>
                                    <i className="fa-solid fa-plus"></i>&nbsp; {nuovoSaving ? 'Salvataggio…' : 'Aggiungi articolo'}
                                </button>
                            </div>
                        </>
                    )}
                </div>

                <div className="gp-modal-footer">
                    <button type="button" className="btn btn--ghost" onClick={onClose}>Chiudi</button>
                </div>
            </div>
        </div>,
        document.body
    )
}
