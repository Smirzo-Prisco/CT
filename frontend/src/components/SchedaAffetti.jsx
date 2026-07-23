/**
 * SchedaAffetti.jsx — Affetti del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile → dati pg + flag permessi per il menu
 *   api_scheda.php?op=affetti → lista affetti raggruppati per tipo
 *
 * Layout due pannelli:
 *   Sinistra: sidebar con tile cliccabili raggruppate per tipo
 *   Destra:   AffettoDetail (view + modifica/elimina per il proprietario)
 *             oppure AffettoForm (creazione/modifica)
 *
 * Sostituisce interamente il vecchio iframe verso dettaglio_affetto.php /
 * form_affetto.php / intro_affetto.php (tabelle HTML, CSS proprio,
 * jQuery 1.11.2) con componenti nativi coerenti con lo stile del progetto.
 * Modifica ed eliminazione esistevano già nel PHP legacy (nella tabella di
 * gestione mostrata da intro_affetto.php quando non c'era nulla selezionato)
 * ma erano visivamente scollegate dal resto della SPA — qui sono azioni
 * dirette sul pannello di dettaglio.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, Fragment } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './SchedaAffetti.module.css'

// ---------------------------------------------------------------------------
// COSTANTI
// ---------------------------------------------------------------------------

const TIPO_LABELS = {
    legami:     'Legami',
    nemici:     'Nemici',
    famiglia:   'Razza',
    conoscenze: 'Conoscenze',
    memories:   'Memories',
}

const AVATAR_DEFAULT = '/themes/crystal/imgs/scheda/empy_affetti.png'
const EMPTY_FORM = { nomePg: '', titolo: '', tipologia: 'legami', contenuto: '', avatar: '' }

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

function AffettoTile({ entry, selected, onClick }) {
    const displayName = entry.nome
        ? `${entry.nome} ${entry.cognome}`
        : entry.nomePg

    return (
        <button
            type="button"
            className={`${styles.tile}${selected ? ` ${styles.tileSelected}` : ''}`}
            onClick={() => onClick(entry.id)}
        >
            <img src={entry.avatar || AVATAR_DEFAULT} alt="" className={styles.tileAvatar} />
            <div className={styles.tileInfo}>
                <div className={styles.tileName}>{displayName}</div>
                {entry.titolo && <div className={styles.tileTitolo}>{entry.titolo}</div>}
            </div>
        </button>
    )
}

/** Form nativo di creazione/modifica affetto — sostituisce l'iframe form_affetto.php. */
function AffettoForm({ pg, initial, onSaved, onCancel }) {
    const isEdit = !!initial
    const [form, setForm]     = useState(initial ? { ...EMPTY_FORM, ...initial } : EMPTY_FORM)
    const [saving, setSaving] = useState(false)
    const [error, setError]   = useState(null)

    const set = (key) => (e) => setForm(f => ({ ...f, [key]: e.target.value }))

    const handleSubmit = (e) => {
        e.preventDefault()
        if (!form.nomePg.trim() || !form.contenuto.trim()) {
            setError('Personaggio e dettaglio sono obbligatori')
            return
        }
        setSaving(true)
        setError(null)
        const op   = isEdit ? 'affetto_update' : 'affetto_create'
        const body = isEdit ? { ...form, id: initial.id } : form
        fetch(`/pages/api_scheda.php?op=${op}&pg=${encodeURIComponent(pg)}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        })
            .then(r => r.json())
            .then(d => {
                setSaving(false)
                if (d.success) onSaved(isEdit ? initial.id : d.id)
                else setError(d.message ?? 'Errore durante il salvataggio')
            })
            .catch(() => { setSaving(false); setError('Errore di rete') })
    }

    return (
        <form className={styles.form} onSubmit={handleSubmit}>
            <h3 className={styles.formTitle}>{isEdit ? 'Modifica affetto' : 'Aggiungi affetto'}</h3>

            <div className={styles.formGroup}>
                <label htmlFor="affetto-nomePg">Personaggio</label>
                <input
                    id="affetto-nomePg"
                    type="text"
                    value={form.nomePg}
                    onChange={set('nomePg')}
                    placeholder="Inserisci nome personaggio"
                    autoComplete="off"
                />
            </div>

            <div className={styles.formGroup}>
                <label htmlFor="affetto-titolo">Titolo</label>
                <input
                    id="affetto-titolo"
                    type="text"
                    value={form.titolo}
                    onChange={set('titolo')}
                    placeholder="Es: l'amato"
                    autoComplete="off"
                />
            </div>

            <div className={styles.formGroup}>
                <label htmlFor="affetto-tipologia">Tipologia</label>
                <select id="affetto-tipologia" value={form.tipologia} onChange={set('tipologia')}>
                    {Object.entries(TIPO_LABELS).map(([tipo, label]) => (
                        <option key={tipo} value={tipo}>{label}</option>
                    ))}
                </select>
            </div>

            <div className={styles.formGroup}>
                <label htmlFor="affetto-contenuto">Dettaglio</label>
                <textarea
                    id="affetto-contenuto"
                    rows={10}
                    value={form.contenuto}
                    onChange={set('contenuto')}
                    placeholder="Contenuto…"
                />
            </div>

            <div className={styles.formGroup}>
                <label htmlFor="affetto-avatar">Avatar</label>
                <input
                    id="affetto-avatar"
                    type="text"
                    value={form.avatar}
                    onChange={set('avatar')}
                    placeholder="Url completo dell'immagine 100x100"
                    autoComplete="off"
                />
            </div>

            {error && <div className={styles.formError}>{error}</div>}

            <div className={styles.formActions}>
                <button type="button" className="btn btn--ghost" onClick={onCancel} disabled={saving}>
                    Annulla
                </button>
                <button type="submit" className="btn btn--primary" disabled={saving}>
                    {saving ? 'Salvataggio…' : (isEdit ? 'Salva modifiche' : 'Inserisci')}
                </button>
            </div>
        </form>
    )
}

/** Vista di un affetto esistente — sostituisce l'iframe dettaglio_affetto.php. */
function AffettoDetail({ pg, id, canEdit, onEdit, onDeleted }) {
    const [data, setData]         = useState(null)
    const [error, setError]       = useState(null)
    const [deleting, setDeleting] = useState(false)

    useEffect(() => {
        setData(null)
        setError(null)
        fetch(`/pages/api_scheda.php?op=affetto_get&pg=${encodeURIComponent(pg)}&id=${id}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setData(d.affetto)
                else setError(d.message ?? 'Affetto non trovato')
            })
            .catch(() => setError('Errore di rete'))
    }, [pg, id])

    const handleDelete = () => {
        if (!window.confirm('Eliminare questo affetto?')) return
        setDeleting(true)
        fetch(`/pages/api_scheda.php?op=affetto_delete&pg=${encodeURIComponent(pg)}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) onDeleted()
                else { setDeleting(false); setError(d.message ?? 'Errore durante l\'eliminazione') }
            })
            .catch(() => { setDeleting(false); setError('Errore di rete') })
    }

    if (error) return <div className={styles.detailBody}><div className={styles.formError}>{error}</div></div>
    if (!data) return <div className={styles.detailBody}>Caricamento…</div>

    return (
        <div className={styles.detailBody}>
            <div className={styles.detailHeader}>
                <img src={data.avatar || AVATAR_DEFAULT} alt="" className={styles.detailAvatar} />
                <div className={styles.detailHeaderInfo}>
                    <div className={styles.detailNome}>{data.nomePg}</div>
                    {data.titolo && <div className={styles.detailTitolo}>{data.titolo}</div>}
                    <div className={styles.detailTipo}>{TIPO_LABELS[data.tipologia] ?? data.tipologia}</div>
                </div>
            </div>

            {/* white-space: pre-line (vedi CSS) preserva gli a-capo scritti nel
                testo — nella vecchia versione (span senza questa regola) il
                contenuto multi-riga collassava visivamente su una sola riga. */}
            <div className={styles.detailContenuto}>{data.contenuto}</div>

            {canEdit && (
                <div className={styles.formActions}>
                    <button type="button" className="btn btn--danger" onClick={handleDelete} disabled={deleting}>
                        {deleting ? 'Eliminazione…' : 'Elimina'}
                    </button>
                    <button type="button" className="btn btn--secondary" onClick={() => onEdit(data)}>
                        Modifica
                    </button>
                </div>
            )}
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaAffetti() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,     setProfile]     = useState(null)
    const [affetti,     setAffetti]     = useState(null)
    const [canAdd,      setCanAdd]      = useState(false)
    const [selected,    setSelected]    = useState(null)
    const [mode,        setMode]        = useState('intro') // 'intro' | 'detail' | 'add' | 'edit'
    const [editInitial, setEditInitial] = useState(null)
    const [error,       setError]       = useState(null)

    const loadAffetti = () => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=affetti&pg=${enc}`).then(r => r.json()).then(aff => {
            if (aff.success) { setAffetti(aff.affetti); setCanAdd(aff.can_add) }
            else setError(aff.message ?? 'Errore affetti')
            return aff
        })
    }

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            loadAffetti(),
        ]).then(([prof]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    const openDetail = (id) => {
        setMode('detail')
        setSelected(id)
    }

    const openAddForm = () => {
        setMode('add')
        setSelected(null)
    }

    const openEditForm = (data) => {
        setMode('edit')
        setEditInitial(data)
    }

    const handleSaved = (id) => {
        loadAffetti().then(() => openDetail(id))
    }

    const handleDeleted = () => {
        loadAffetti().then(() => { setMode('intro'); setSelected(null) })
    }

    if (error)                return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !affetti) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile

    const tipiConAffetti = Object.entries(TIPO_LABELS)
        .filter(([tipo]) => (affetti[tipo] ?? []).length > 0)

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Affetti</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                <div className={styles.layout}>

                    {/* ── Sidebar ─────────────────────────────────────── */}
                    <div className={styles.sidebar}>

                        {tipiConAffetti.length === 0 && !canAdd && (
                            <div className={styles.emptyList}>Nessun affetto registrato.</div>
                        )}

                        {tipiConAffetti.map(([tipo, label], idx) => (
                            <Fragment key={tipo}>
                                <div className={`${styles.groupHeader}${idx > 0 ? ` ${styles.groupHeaderDivider}` : ''}`}>
                                    {label}
                                </div>
                                {affetti[tipo].map(entry => (
                                    <AffettoTile
                                        key={entry.id}
                                        entry={entry}
                                        selected={mode === 'detail' && selected === entry.id}
                                        onClick={openDetail}
                                    />
                                ))}
                            </Fragment>
                        ))}

                        {canAdd && (
                            <button
                                type="button"
                                className={`${styles.addBtn}${mode === 'add' ? ` ${styles.tileSelected}` : ''}`}
                                onClick={openAddForm}
                            >
                                <i className="fa-solid fa-plus" />
                                Aggiungi affetto
                            </button>
                        )}
                    </div>

                    {/* ── Pannello dettaglio ───────────────────────────── */}
                    <div className={styles.detailPanel}>
                        {mode === 'add' && (
                            <AffettoForm
                                pg={pg}
                                onSaved={handleSaved}
                                onCancel={() => setMode(selected ? 'detail' : 'intro')}
                            />
                        )}
                        {mode === 'edit' && (
                            <AffettoForm
                                pg={pg}
                                initial={editInitial}
                                onSaved={handleSaved}
                                onCancel={() => setMode('detail')}
                            />
                        )}
                        {mode === 'detail' && selected && (
                            <AffettoDetail
                                pg={pg}
                                id={selected}
                                canEdit={is_own}
                                onEdit={openEditForm}
                                onDeleted={handleDeleted}
                            />
                        )}
                        {mode === 'intro' && (
                            <div className={styles.introMsg}>
                                {tipiConAffetti.length > 0
                                    ? 'Seleziona un affetto dalla lista per leggerlo.'
                                    : 'Nessun affetto da mostrare.'}
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </div>
    )
}
