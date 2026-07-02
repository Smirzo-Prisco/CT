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
 * Stili: _servizi_mestieri.scss (classi .sm-*), stesso linguaggio visivo
 * di servizi_mestieri.inc.php / ServiziGilde.jsx — nessuno stile custom qui.
 *
 * API: pages/api_mestieri.php (op=mia_gilda, crea_gilda, save, save_ruolo, delete_ruolo)
 */

import { useState, useEffect, useCallback } from 'react'

const API = '/pages/api_mestieri.php'

// ── Immagine editabile: box cliccabile che apre il file picker ────────────

function EditableImage({ currentUrl, folder = 'mestieri', fallbackIcon, onFileChange, alt }) {
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
        <label className="sm-img-box sm-img-box--upload" title="Cambia immagine">
            {preview
                ? <img src={preview} alt={alt} />
                : currentUrl
                    ? <img src={`imgs/${folder}/${currentUrl}`} alt={alt} onError={e => { e.target.style.display = 'none' }} />
                    : <i className={`fas ${fallbackIcon}`} />
            }
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" hidden onChange={handleChange} />
        </label>
    )
}

// ── Riga grado (capo) — editing inline dentro .sm-rank-list ───────────────

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
        <div className="sm-rank-item sm-rank-item--edit">
            <EditableImage currentUrl={ruolo.immagine} fallbackIcon="fa-medal" onFileChange={setFile} alt={ruolo.nome_ruolo} />
            <input value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} placeholder="Nome grado" />
            <input type="number" min="1" max="3" style={{ maxWidth: 60 }} title="Livello (1 = più alto, 3 = più basso)"
                   value={form.livello_mestiere} onChange={e => setForm({ ...form, livello_mestiere: e.target.value })} />
            <input type="number" style={{ maxWidth: 90 }} title="Stipendio"
                   value={form.stipendio} onChange={e => setForm({ ...form, stipendio: e.target.value })} />
            <div className="sm-rank-actions">
                <button type="button" onClick={save} disabled={saving}>Salva</button>
                <button type="button" onClick={remove} disabled={saving}>Elimina</button>
            </div>
            {error && <p className="sm-error-text">{error}</p>}
        </div>
    )
}

// ── Riga del ruolo di comando — qui il capo può cambiare solo l'immagine
// (nome/livello/stipendio sono bloccati anche lato server, vedi api_mestieri.php) ──

function CapoRow({ ruolo, onSaved }) {
    const [saving, setSaving] = useState(false)
    const [error, setError] = useState(null)

    const salvaImmagine = async (file) => {
        if (!file) return
        setSaving(true)
        setError(null)
        const fd = new FormData()
        fd.append('id_ruolo', ruolo.id_ruolo)
        fd.append('mestiere', ruolo.mestiere)
        fd.append('nome', ruolo.nome_ruolo)
        fd.append('livello_mestiere', ruolo.livello_mestiere)
        fd.append('stipendio', ruolo.stipendio)
        fd.append('immagine', file)

        const r = await fetch(`${API}?op=save_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) onSaved(d.ruoli)
        else setError(d.message ?? 'Errore nel salvataggio dell\'immagine')
    }

    return (
        <div className="sm-rank-item sm-rank-item--top">
            <EditableImage currentUrl={ruolo.immagine} fallbackIcon="fa-crown" onFileChange={salvaImmagine} alt={ruolo.nome_ruolo} />
            <span className="sm-rank-name">{ruolo.nome_ruolo}{saving ? ' — salvataggio…' : ''}</span>
            <i className="fas fa-crown sm-rank-crown" />
            {error && <p className="sm-error-text">{error}</p>}
        </div>
    )
}

// ── Form di creazione ───────────────────────────────────────────────────────

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
        <section className="sm-section">
            <div className="sm-section-title">
                <i className="fas fa-shield-halved" /> Crea la tua gilda
            </div>
            <p className="sm-field-note" style={{ marginBottom: 16 }}>
                Diventerai automaticamente il capo. Potrai definire i gradi e, in seguito,
                assumere altri personaggi da <a href="main.php?page=servizi_adm_mestieri">Assumi/espelli</a>.
            </p>
            <form onSubmit={submit}>
                <div className="sm-field">
                    <label htmlFor="cg-nome">Nome della gilda</label>
                    <input id="cg-nome" value={nome} onChange={e => setNome(e.target.value)} required />
                </div>
                <div className="sm-field">
                    <label>Immagine</label>
                    <EditableImage fallbackIcon="fa-shield-halved" onFileChange={setFile} alt="" />
                </div>
                <div className="sm-field">
                    <label htmlFor="cg-statuto">Statuto</label>
                    <textarea id="cg-statuto" rows={6} value={statuto} onChange={e => setStatuto(e.target.value)} />
                </div>
                {error && <p className="sm-error-text">{error}</p>}
                <button type="submit" disabled={saving}>{saving ? 'Creazione…' : 'Crea gilda'}</button>
            </form>
        </section>
    )
}

// ── Componente principale ────────────────────────────────────────────────────

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

    if (loading) return (
        <div className="sm-page">
            <div className="sm-state">
                <i className="fas fa-spinner fa-spin" />
                <p>Caricamento…</p>
            </div>
        </div>
    )
    if (error) return (
        <div className="sm-page">
            <div className="sm-state sm-state--error">
                <i className="fas fa-exclamation-triangle" />
                <p>{error}</p>
            </div>
        </div>
    )

    return (
        <div className="sm-page">
            <div className="sm-header">
                <button className="sm-back" onClick={() => window.history.back()}>
                    <i className="fas fa-arrow-left" /> Indietro
                </button>
                <h2 className="sm-title">
                    <i className="fas fa-shield-halved" /> La mia gilda
                </h2>
            </div>

            {!stato.ha_gilda && stato.puo_creare && <CreaGilda onCreata={carica} />}

            {!stato.ha_gilda && !stato.puo_creare && (
                <section className="sm-section">
                    <p className="sm-field-note">
                        Hai già un'affiliazione lavorativa attiva: non puoi fondare una nuova gilda finché non la lasci.
                    </p>
                    <p><a href="main.php?page=servizi_adm_mestieri">Gestisci le tue affiliazioni</a></p>
                </section>
            )}

            {stato.ha_gilda && <GildaEsistente stato={stato} onChange={carica} />}
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
    const [nuovoGradoFile, setNuovoGradoFile] = useState(null)
    const [nuovoGradoSaving, setNuovoGradoSaving] = useState(false)
    const capoRole = ruoliLocali.find(r => r.capo == 1)

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
        if (nuovoGradoFile) fd.append('immagine', nuovoGradoFile)

        const r = await fetch(`${API}?op=save_ruolo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setNuovoGradoSaving(false)
        if (d.success) {
            setRuoliLocali(d.ruoli)
            setNuovoGrado({ nome: '', livello_mestiere: 3, stipendio: 0 })
            setNuovoGradoFile(null)
        }
    }

    if (!eCapo) {
        // Vista sola lettura per i membri semplici — stesso markup del dettaglio pubblico
        return (
            <>
                <section className="sm-section">
                    <div className="sm-img-box" style={{ marginBottom: 12 }}>
                        {mestiere.immagine
                            ? <img src={`imgs/mestieri/${mestiere.immagine}`} alt={mestiere.nome} onError={e => { e.target.style.display = 'none' }} />
                            : <i className="fas fa-shield-halved" />
                        }
                    </div>
                    {mestiere.statuto && (
                        <div className="sm-statute" style={{ whiteSpace: 'pre-wrap', textAlign: 'left' }}>{mestiere.statuto}</div>
                    )}
                </section>

                {ruoliLocali.length > 0 && (
                    <section className="sm-section">
                        <div className="sm-section-title"><i className="fas fa-layer-group" /> Gerarchia</div>
                        <div className="sm-rank-list">
                            {ruoliLocali.map((r, i) => (
                                <div key={r.id_ruolo} className={`sm-rank-item${r.capo == 1 ? ' sm-rank-item--top' : ''}`}>
                                    <span className="sm-rank-badge">{i + 1}</span>
                                    <div className="sm-img-box">
                                        {r.immagine
                                            ? <img src={`imgs/mestieri/${r.immagine}`} alt={r.nome_ruolo} />
                                            : <i className="fas fa-medal" />
                                        }
                                    </div>
                                    <span className="sm-rank-name">{r.nome_ruolo}</span>
                                    {r.capo == 1 && <i className="fas fa-crown sm-rank-crown" />}
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                <p><a href={`main.php?page=servizi_adm_mestieri&id_mestiere=${mestiere.id_mestiere}`}>Abbandona / gestisci affiliazione</a></p>
            </>
        )
    }

    // Vista capo: editing completo
    return (
        <>
            <section className="sm-section">
                <div className="sm-section-title"><i className="fas fa-pen" /> Gestisci la tua gilda</div>
                <form onSubmit={salvaGilda}>
                    <div className="sm-field">
                        <label htmlFor="mg-nome">Nome</label>
                        <input id="mg-nome" value={form.nome} onChange={e => setForm({ ...form, nome: e.target.value })} required />
                    </div>
                    <div className="sm-field">
                        <label>Immagine</label>
                        <EditableImage currentUrl={form.immagine} fallbackIcon="fa-shield-halved" onFileChange={setFile} alt={form.nome} />
                    </div>
                    <div className="sm-field sm-field--checkbox">
                        <input id="mg-visibile" type="checkbox" checked={form.visibile == 1}
                               onChange={e => setForm({ ...form, visibile: e.target.checked })} />
                        <label htmlFor="mg-visibile">Visibile agli altri giocatori</label>
                    </div>
                    <div className="sm-field">
                        <label htmlFor="mg-statuto">Statuto</label>
                        <textarea id="mg-statuto" rows={6} value={form.statuto ?? ''} onChange={e => setForm({ ...form, statuto: e.target.value })} />
                    </div>
                    {error && <p className="sm-error-text">{error}</p>}
                    <button type="submit" disabled={saving}>{saving ? 'Salvataggio…' : 'Salva modifiche'}</button>
                </form>
            </section>

            <section className="sm-section">
                <div className="sm-section-title"><i className="fas fa-layer-group" /> Gradi</div>
                <p className="sm-field-note" style={{ marginBottom: 8 }}>Livello 1 = più alto, 3 = più basso.</p>

                <div className="sm-rank-list">
                    {capoRole && <CapoRow ruolo={capoRole} onSaved={setRuoliLocali} />}

                    {ruoliLocali.filter(r => r.capo != 1).length === 0 && (
                        <p className="sm-field-note">Nessun grado oltre a "Capo" definito per ora.</p>
                    )}
                    {ruoliLocali.filter(r => r.capo != 1).map(r => (
                        <GradoRow key={r.id_ruolo} ruolo={r} onSaved={setRuoliLocali} onDeleted={setRuoliLocali} />
                    ))}

                    <div className="sm-rank-item sm-rank-item--edit">
                        <EditableImage key={ruoliLocali.length} fallbackIcon="fa-medal" onFileChange={setNuovoGradoFile} alt="" />
                        <input placeholder="Nuovo grado…" value={nuovoGrado.nome}
                               onChange={e => setNuovoGrado({ ...nuovoGrado, nome: e.target.value })} />
                        <input type="number" min="1" max="3" style={{ maxWidth: 60 }} title="Livello"
                               value={nuovoGrado.livello_mestiere}
                               onChange={e => setNuovoGrado({ ...nuovoGrado, livello_mestiere: e.target.value })} />
                        <input type="number" style={{ maxWidth: 90 }} title="Stipendio"
                               value={nuovoGrado.stipendio}
                               onChange={e => setNuovoGrado({ ...nuovoGrado, stipendio: e.target.value })} />
                        <div className="sm-rank-actions">
                            <button type="button" onClick={aggiungiGrado} disabled={nuovoGradoSaving}>Aggiungi</button>
                        </div>
                    </div>
                </div>
            </section>

            <p>
                <a href={`main.php?page=servizi_adm_mestieri&id_mestiere=${mestiere.id_mestiere}`} className="btn btn--secondary">
                    Assumi / espelli membri
                </a>
            </p>
        </>
    )
}
