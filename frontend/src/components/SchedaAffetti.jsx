/**
 * SchedaAffetti.jsx — Affetti del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile → dati pg + flag permessi per il menu
 *   api_scheda.php?op=affetti → lista affetti raggruppati per tipo
 *
 * Layout due pannelli:
 *   Sinistra: sidebar con tile cliccabili raggruppate per tipo
 *   Destra:   iframe (dettaglio_affetto.php, legacy — mostra il contenuto di
 *             un affetto esistente) oppure il form nativo AffettoForm quando
 *             si clicca "Aggiungi affetto" (mode 'add').
 *
 * Il vecchio form_affetto.php (tabelle HTML, CSS proprio, jQuery datato) è
 * stato sostituito da AffettoForm qui sotto per uniformarsi allo stile del
 * resto della SPA — solo la creazione, l'unico flusso raggiungibile dalla UI
 * (la modifica esisteva nel PHP legacy ma non era collegata a nessun bottone).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, Fragment } from 'react'
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
const DETAIL_BASE    = '/pages/dettaglio_affetto.php'
const INTRO_BASE     = '/pages/intro_affetto.php'

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

function AffettoTile({ entry, pg, selected, onClick }) {
    const displayName = entry.nome
        ? `${entry.nome} ${entry.cognome}`
        : entry.nomePg
    const detailUrl = `${DETAIL_BASE}?id=${entry.id}&username=${encodeURIComponent(pg)}&pg=${encodeURIComponent(entry.nomePg)}`

    return (
        <button
            type="button"
            className={`${styles.tile}${selected ? ` ${styles.tileSelected}` : ''}`}
            onClick={() => onClick(detailUrl)}
        >
            <img src={entry.avatar || AVATAR_DEFAULT} alt="" className={styles.tileAvatar} />
            <div className={styles.tileInfo}>
                <div className={styles.tileName}>{displayName}</div>
                {entry.titolo && <div className={styles.tileTitolo}>{entry.titolo}</div>}
            </div>
        </button>
    )
}

/** Form nativo di creazione affetto — sostituisce l'iframe form_affetto.php. */
function AffettoForm({ pg, onCreated, onCancel }) {
    const [form, setForm]       = useState({ nomePg: '', titolo: '', tipologia: 'legami', contenuto: '', avatar: '' })
    const [saving, setSaving]   = useState(false)
    const [error, setError]     = useState(null)

    const set = (key) => (e) => setForm(f => ({ ...f, [key]: e.target.value }))

    const handleSubmit = (e) => {
        e.preventDefault()
        if (!form.nomePg.trim() || !form.contenuto.trim()) {
            setError('Personaggio e dettaglio sono obbligatori')
            return
        }
        setSaving(true)
        setError(null)
        fetch(`/pages/api_scheda.php?op=affetto_create&pg=${encodeURIComponent(pg)}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form),
        })
            .then(r => r.json())
            .then(d => {
                setSaving(false)
                if (d.success) onCreated(d.id, form.nomePg)
                else setError(d.message ?? 'Errore durante il salvataggio')
            })
            .catch(() => { setSaving(false); setError('Errore di rete') })
    }

    return (
        <form className={styles.form} onSubmit={handleSubmit}>
            <h3 className={styles.formTitle}>Aggiungi affetto</h3>

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
                    {saving ? 'Salvataggio…' : 'Inserisci'}
                </button>
            </div>
        </form>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaAffetti() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,   setProfile]   = useState(null)
    const [affetti,   setAffetti]   = useState(null)
    const [canAdd,    setCanAdd]    = useState(false)
    const [iframeSrc, setIframeSrc] = useState('')
    const [selected,  setSelected]  = useState(null)
    const [mode,      setMode]      = useState('detail') // 'detail' | 'add'
    const [error,     setError]     = useState(null)
    const iframeRef = useRef(null)

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
            setIframeSrc(`${INTRO_BASE}?username=${enc}`)
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    const openDetail = (url, id) => {
        setMode('detail')
        setIframeSrc(url)
        setSelected(id)
    }

    const openAddForm = () => {
        setMode('add')
        setSelected(null)
    }

    const handleCreated = (newId, nomePg) => {
        loadAffetti().then(() => {
            const detailUrl = `${DETAIL_BASE}?id=${newId}&username=${encodeURIComponent(pg)}&pg=${encodeURIComponent(nomePg)}`
            openDetail(detailUrl, newId)
        })
    }

    if (error)              return <div className="pagina_scheda"><div className="error">{error}</div></div>
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
                                        pg={pg}
                                        selected={selected === entry.id}
                                        onClick={url => openDetail(url, entry.id)}
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
                        {mode === 'add' ? (
                            <AffettoForm
                                pg={pg}
                                onCreated={handleCreated}
                                onCancel={() => setMode('detail')}
                            />
                        ) : (
                            <iframe
                                ref={iframeRef}
                                src={iframeSrc}
                                allowTransparency="true"
                                frameBorder="0"
                                className={styles.iframe}
                                title="Dettaglio affetto"
                                onLoad={() => {
                                    try {
                                        const h = iframeRef.current?.contentWindow?.document?.body?.scrollHeight
                                        if (h > 420) iframeRef.current.style.height = h + 'px'
                                    } catch {}
                                }}
                            />
                        )}
                    </div>

                </div>
            </div>
        </div>
    )
}
