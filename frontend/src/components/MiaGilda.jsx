/**
 * MiaGilda.jsx — Auto-gestione della gilda del personaggio (SPA)
 *
 * Pagina per QUALSIASI giocatore loggato (non uno strumento di staff),
 * per questo vive su main.php/AppRouter e non sullo shim CT.mount usato
 * dagli strumenti admin (che richiedono lo staff gate di gestione.php).
 *
 * Tre stati:
 *  - nessuna gilda + può ancora affiliarsi (jobs_limit) → form di creazione
 *  - ha una gilda ed è il capo (ruolo_mestiere.capo=1) → editor completo
 *  - ha una gilda ma non è il capo → vista di sola lettura
 *
 * L'assunzione/espulsione dei membri resta per ora su
 * main.php?page=servizi_adm_mestieri (non ancora migrata).
 *
 * API: pages/api_mestieri.php (op=mia_gilda, crea_gilda, save, save_ruolo)
 */

import { useState, useEffect, useCallback } from 'react'

const API = '/pages/api_mestieri.php'

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
                    onError={e => { e.target.style.display = 'none' }}
                />
                <input id={id} type="file" accept="image/jpeg,image/png,image/gif,image/webp" onChange={handleChange} />
            </div>
        </div>
    )
}

// ── Riga grado (capo) — niente flag "controlli di mestiere": lato server è sempre ignorato ──

function GradoRow({ ruolo, onSaved, onDeleted }) {
    const [form, setForm] = useState({
        nome: ruolo.nome_ruolo,
        livello_mestiere: ruolo.livello_mestiere,
        stipendio: ruolo.stipendio,
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
        if (file) fd.append('immagine', file)

        const r = await fetch(`${API}?op=save_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved(d.ruoli)
        else setError(d.message ?? 'Errore nel salvataggio del grado')
    }

    const remove = async () => {
        if (!window.confirm(`Eliminare il grado «${ruolo.nome_ruolo}»?`)) return
        const fd = new FormData()
        fd.append('id_ruolo', ruolo.id_ruolo)
        const r = await fetch(`${API}?op=delete_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        if (d.success) onDeleted(d.ruoli)
        else setError(d.message ?? 'Errore nell\'eliminazione del grado')
    }

    return (
        <div className="gm-role-row">
            <img
                src={`imgs/mestieri/${ruolo.immagine}`}
                alt=""
                className="gm-image-thumb gm-image-thumb--sm"
                onError={e => { e.target.style.display = 'none' }}
            />
            <div className="form-row gm-role-fields">
                <div className="form-group form-column">
                    <label>Nome grado</label>
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

// ── Form di creazione ─────────────────────────────────────────────────────────

function CreaGilda({ onCreata }) {
    const [nome, setNome] = useState('')
    const [statuto, setStatuto] = useState('')
    const [file, setFile] = useState(null)
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)

    const submit = async (e) => {
        e.preventDefault()
        if (!nome.trim()) return
        setSaving(true)
        setError(null)
        const fd = new FormData()
        fd.append('nome', nome)
        fd.append('statuto', statuto)
        if (file) fd.append('immagine', file)

        const r = await fetch(`${API}?op=crea_gilda`, { method: 'POST', body: fd })
        const d = await r.json()
        setSaving(false)
        if (d.success) onCreata()
        else setError(d.message ?? 'Errore nella creazione della gilda')
    }

    return (
        <div className="card gm-card">
            <h3>Crea la tua gilda</h3>
            <p className="gm-description">
                Diventerai automaticamente il capo. Potrai poi definire i gradi e, in seguito,
                assumere altri personaggi da <a href="main.php?page=servizi_adm_mestieri">Assumi/espelli</a>.
            </p>
            <form onSubmit={submit}>
                <div className="form-group">
                    <label htmlFor="cg-nome">Nome della gilda</label>
                    <input id="cg-nome" value={nome} onChange={e => setNome(e.target.value)} required />
                </div>
                <ImageField id="cg-immagine" currentUrl="" onFileChange={setFile} label="Immagine" />
                <div className="form-group">
                    <label htmlFor="cg-statuto">Statuto</label>
                    <textarea id="cg-statuto" rows={6} value={statuto} onChange={e => setStatuto(e.target.value)} />
                </div>
                {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
                <button type="submit" disabled={saving}>
                    <i className="fa-solid fa-plus"></i>&nbsp; {saving ? 'Creazione…' : 'Crea gilda'}
                </button>
            </form>
        </div>
    )
}

// ── MiaGilda ─────────────────────────────────────────────────────────────────

export default function MiaGilda() {
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)
    const [stato, setStato]     = useState(null) // risposta di op=mia_gilda

    const carica = useCallback(async () => {
        setLoading(true)
        try {
            const r = await fetch(`${API}?op=mia_gilda`)
            const d = await r.json()
            setLoading(false)
            if (d.success) setStato(d)
            else setError(d.message ?? 'Errore nel caricamento')
        } catch {
            setLoading(false)
            setError('Errore di rete')
        }
    }, [])

    useEffect(() => { carica() }, [carica])

    if (loading) return <div className="pagina_gestione"><p style={{ padding: 20 }}>Caricamento…</p></div>
    if (error)   return <div className="pagina_gestione"><p className="gm-feedback gm-feedback--error" style={{ margin: 20 }}>{error}</p></div>

    return (
        <div className="pagina_gestione">
            <header>🛡️ La mia gilda</header>
            <div style={{ padding: 20, maxWidth: 720, margin: '0 auto' }}>

                <div className="link_back" style={{ marginBottom: 16 }}>
                    <button onClick={() => window.history.back()}>← Torna indietro</button>
                </div>

                {!stato.ha_gilda && stato.puo_creare && <CreaGilda onCreata={carica} />}

                {!stato.ha_gilda && !stato.puo_creare && (
                    <div className="card gm-card">
                        <p>Hai già un'affiliazione lavorativa attiva: non puoi fondare una nuova gilda finché non la lasci.</p>
                        <a href="main.php?page=servizi_adm_mestieri" className="btn btn--secondary">Gestisci le tue affiliazioni</a>
                    </div>
                )}

                {stato.ha_gilda && (
                    <GildaEsistente stato={stato} onChange={carica} />
                )}
            </div>
        </div>
    )
}

// ── Vista gilda esistente (capo o membro) ────────────────────────────────────

function GildaEsistente({ stato, onChange }) {
    const { e_capo: eCapo, mestiere, ruoli } = stato
    const [form, setForm] = useState(mestiere)
    const [file, setFile] = useState(null)
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)
    const [ruoliLocali, setRuoliLocali] = useState(ruoli)
    const [nuovoGrado, setNuovoGrado] = useState({ nome: '', livello_mestiere: 3, stipendio: 0 })
    const [nuovoGradoSaving, setNuovoGradoSaving] = useState(false)

    const salvaGilda = async (e) => {
        e.preventDefault()
        setSaving(true)
        setError(null)
        const fd = new FormData()
        fd.append('id_mestiere', mestiere.id_mestiere)
        fd.append('nome', form.nome)
        fd.append('url_sito', form.url_sito ?? '')
        fd.append('statuto', form.statuto ?? '')
        fd.append('visibile', form.visibile ? '1' : '0')
        if (file) fd.append('immagine', file)

        const r = await fetch(`${API}?op=save`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onChange()
        else setError(d.message ?? 'Errore nel salvataggio')
    }

    const aggiungiGrado = async (e) => {
        e.preventDefault()
        if (!nuovoGrado.nome.trim()) return
        setNuovoGradoSaving(true)
        const fd = new FormData()
        fd.append('mestiere', mestiere.id_mestiere)
        fd.append('nome', nuovoGrado.nome)
        fd.append('livello_mestiere', nuovoGrado.livello_mestiere)
        fd.append('stipendio', nuovoGrado.stipendio)

        const r = await fetch(`${API}?op=save_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setNuovoGradoSaving(false)
        if (d.success) {
            setRuoliLocali(d.ruoli)
            setNuovoGrado({ nome: '', livello_mestiere: 3, stipendio: 0 })
        }
    }

    if (!eCapo) {
        // Vista sola lettura per i membri semplici
        return (
            <div className="card gm-card">
                <div className="gm-image-field" style={{ marginBottom: 16 }}>
                    <img src={`imgs/mestieri/${mestiere.immagine}`} alt="" className="gm-image-thumb"
                         onError={e => { e.target.style.display = 'none' }} />
                    <h3 style={{ margin: 0 }}>{mestiere.nome}</h3>
                </div>
                {mestiere.statuto && <p style={{ whiteSpace: 'pre-wrap' }}>{mestiere.statuto}</p>}
                <h4>Gerarchia</h4>
                <ul>
                    {ruoliLocali.map(r => <li key={r.id_ruolo}>{r.nome_ruolo}{r.capo == 1 ? ' (capo)' : ''}</li>)}
                </ul>
                <a href="main.php?page=servizi_adm_mestieri" className="btn btn--secondary">Abbandona / gestisci affiliazione</a>
            </div>
        )
    }

    // Vista capo: editing completo
    return (
        <div className="card gm-card">
            <h3>Gestisci la tua gilda</h3>
            <form onSubmit={salvaGilda}>
                <div className="form-group">
                    <label htmlFor="mg-nome">Nome</label>
                    <input id="mg-nome" value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} required />
                </div>
                <ImageField id="mg-immagine" currentUrl={form.immagine} onFileChange={setFile} label="Immagine" />
                <div className="form-group gp-checkbox-field">
                    <input id="mg-visibile" type="checkbox" checked={form.visibile == 1}
                           onChange={e => setForm({ ...form, visibile: e.target.checked })} />
                    <label htmlFor="mg-visibile">Visibile <span className="gp-label-note">— la gilda sarà visibile agli altri giocatori</span></label>
                </div>
                <div className="form-group">
                    <label htmlFor="mg-statuto">Statuto</label>
                    <textarea id="mg-statuto" rows={6} value={form.statuto ?? ''} onChange={e => setForm({ ...form, statuto: e.target.value })} />
                </div>
                {error && <p className="gm-feedback gm-feedback--error">{error}</p>}
                <button type="submit" disabled={saving}>
                    <i className="fa-solid fa-floppy-disk"></i>&nbsp; {saving ? 'Salvataggio…' : 'Salva modifiche'}
                </button>
            </form>

            <h4 style={{ marginTop: 24 }}>Gradi — livello 3 = più basso, 1 = più alto</h4>
            <div className="gm-role-row gm-role-row--new">
                <div className="form-row gm-role-fields">
                    <div className="form-group form-column">
                        <label>Nome grado</label>
                        <input placeholder="Nuovo grado…" value={nuovoGrado.nome}
                               onChange={e => setNuovoGrado({ ...nuovoGrado, nome: e.target.value })} />
                    </div>
                    <div className="form-group" style={{ maxWidth: 90 }}>
                        <label>Livello</label>
                        <input type="number" min="1" max="3" value={nuovoGrado.livello_mestiere}
                               onChange={e => setNuovoGrado({ ...nuovoGrado, livello_mestiere: e.target.value })} />
                    </div>
                    <div className="form-group" style={{ maxWidth: 110 }}>
                        <label>Stipendio</label>
                        <input type="number" value={nuovoGrado.stipendio}
                               onChange={e => setNuovoGrado({ ...nuovoGrado, stipendio: e.target.value })} />
                    </div>
                </div>
                <button type="button" className="btn btn--success" onClick={aggiungiGrado} disabled={nuovoGradoSaving}>
                    <i className="fa-solid fa-plus"></i>&nbsp; Aggiungi grado
                </button>
            </div>

            {ruoliLocali.filter(r => r.capo != 1).length === 0 ? (
                <p className="gp-label-note">Nessun grado oltre a "Capo" definito per ora.</p>
            ) : ruoliLocali.filter(r => r.capo != 1).map(r => (
                <GradoRow key={r.id_ruolo} ruolo={r} onSaved={setRuoliLocali} onDeleted={setRuoliLocali} />
            ))}

            <a href="main.php?page=servizi_adm_mestieri" className="btn btn--secondary" style={{ marginTop: 16, display: 'inline-block' }}>
                Assumi / espelli membri
            </a>
        </div>
    )
}
