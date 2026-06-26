/**
 * SchedaPunti.jsx — Storico punti unificato (PX, Shin, Mestiere)
 *
 * Tutte e tre le route (scheda_px, scheda_px_shin, scheda_px_mestiere) convergono
 * qui. Il parametro ?page= imposta il filtro iniziale; i pulsanti di filtro
 * permettono di cambiare tipo senza ricaricare la pagina.
 *
 * Fetch: api_scheda.php?op=punti_all&pg=X  → tutti i record senza paginazione.
 * Filtro e paginazione gestiti lato client (PER_PAGE = 25).
 *
 * Struttura record:
 *   { data, titolo, commento, tipi: ['px'|'shin'|'mestiere'], punti: {tipo: valore} }
 *
 * Un record Punti con shin > 0 ha tipi=['px','shin'] e appare in entrambi
 * i filtri separati, una sola volta nel filtro "Tutti".
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'
import SchedaMenu from './SchedaMenu'
import shared from './shared.module.css'
import styles from './SchedaPunti.module.css'

// ---------------------------------------------------------------------------
// COSTANTI
// ---------------------------------------------------------------------------

const PER_PAGE = 25

const TIPO_LABELS = { px: 'PX', shin: 'Shin', mestiere: 'Mestiere' }
const TIPO_COLORS = { px: '#4a9eff', shin: '#ffa726', mestiere: '#4caf50' }

const PAGE_TO_FILTER = {
    scheda_px:          'px',
    scheda_px_shin:     'shin',
    scheda_px_mestiere: 'mestiere',
}

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return isNaN(d) ? dateStr : d.toLocaleDateString('it-IT', { day: 'numeric', month: 'short', year: 'numeric' })
}

function buildPages(current, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i)
    const pages = new Set([0, total - 1])
    for (let i = Math.max(0, current - 2); i <= Math.min(total - 1, current + 2); i++) pages.add(i)
    const sorted = [...pages].sort((a, b) => a - b)
    const result = []
    sorted.forEach((p, idx) => {
        if (idx > 0 && p - sorted[idx - 1] > 1) result.push('…')
        result.push(p)
    })
    return result
}

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

function Pager({ current, total, onChange }) {
    return (
        <div className={styles.pager}>
            <button disabled={current === 0} onClick={() => onChange(current - 1)}>‹</button>
            {buildPages(current, total).map((p, i) =>
                p === '…'
                    ? <span key={`e${i}`} className={styles.ellipsis}>…</span>
                    : <button
                        key={p}
                        className={p === current ? styles.activePage : ''}
                        onClick={() => p !== current && onChange(p)}
                      >{p + 1}</button>
            )}
            <button disabled={current === total - 1} onClick={() => onChange(current + 1)}>›</button>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaPunti() {
    const params = new URLSearchParams(window.location.search)
    const pg   = params.get('pg')   ?? ''
    const page = params.get('page') ?? ''

    const [profile, setProfile] = useState(null)
    const [records, setRecords] = useState([])
    const [filter,  setFilter]  = useState(PAGE_TO_FILTER[page] ?? 'px')
    const [offset,  setOffset]  = useState(0)
    const [error,   setError]   = useState(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            fetch(`/pages/api_scheda.php?op=punti_all&pg=${enc}`).then(r => {
                if (r.status === 403) throw new Error('Sezione riservata')
                return r.json()
            }),
        ]).then(([prof, d]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (d.success)    setRecords(d.records)
            else              setError(d.message  ?? 'Errore punti')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    // Reset alla prima pagina ad ogni cambio filtro
    useEffect(() => { setOffset(0) }, [filter])

    if (error)   return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile

    const filtered = filter === 'tutti'
        ? records
        : records.filter(r => r.tipi.includes(filter))

    const pagine      = Math.ceil(filtered.length / PER_PAGE)
    const pageRecords = filtered.slice(offset * PER_PAGE, (offset + 1) * PER_PAGE)

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Storico Punti</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                {/* ── Filtri tipo ──────────────────────────────────────── */}
                <div className={styles.filterRow} style={{ marginTop: '16px' }}>
                    {['tutti', 'px', 'shin', 'mestiere'].map(t => {
                        const isActive = filter === t
                        const isTipo   = t !== 'tutti'
                        return (
                            <button
                                key={t}
                                className={[
                                    styles.filterBtn,
                                    isActive ? (isTipo ? styles.filterActiveTipo : styles.filterActive) : '',
                                ].join(' ')}
                                style={isTipo ? { '--tipo-color': TIPO_COLORS[t] } : undefined}
                                onClick={() => setFilter(t)}
                            >
                                {t === 'tutti' ? 'Tutti' : TIPO_LABELS[t]}
                            </button>
                        )
                    })}
                    <span style={{ marginLeft: 'auto', fontSize: '11px', color: '#6b6f8a', alignSelf: 'center' }}>
                        {filtered.length} record
                    </span>
                </div>

                {/* ── Tabella ──────────────────────────────────────────── */}
                <div className={styles.tableWrap}>
                    <table className={`customTable ${styles.puntiTable}`}>
                        <thead>
                            <tr className="second_header">
                                <td className={styles.dateCell}>Data</td>
                                <td>Tipo</td>
                                <td>Titolo</td>
                                <td className={styles.hideNarrow}>Commento</td>
                                <td className={styles.puntiCell}>Punti</td>
                            </tr>
                        </thead>
                        <tbody>
                            {pageRecords.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className={shared.centered}
                                        style={{ padding: '20px', fontStyle: 'italic', color: '#6b6f8a' }}>
                                        Nessun record trovato.
                                    </td>
                                </tr>
                            ) : pageRecords.map((r, i) => (
                                <tr key={i}>
                                    <td className={`casella_elemento ${styles.dateCell}`}>
                                        {formatDate(r.data)}
                                    </td>
                                    <td className="casella_elemento">
                                        <span className={styles.tipiBadges}>
                                            {r.tipi.map(t => (
                                                <span
                                                    key={t}
                                                    className={styles.tipoBadge}
                                                    style={{ '--tipo-color': TIPO_COLORS[t] }}
                                                >
                                                    {TIPO_LABELS[t]}
                                                </span>
                                            ))}
                                        </span>
                                    </td>
                                    <td className="casella_elemento">{r.titolo}</td>
                                    <td className={`casella_elemento ${styles.hideNarrow}`}>{r.commento}</td>
                                    <td className={`casella_elemento ${styles.puntiCell}`}>
                                        {Object.entries(r.punti).map(([t, v]) => (
                                            <span key={t} className={styles.puntiVal} style={{ '--tipo-color': TIPO_COLORS[t] }}>
                                                <span>{v > 0 ? '+' : ''}{v}</span> {TIPO_LABELS[t]}
                                            </span>
                                        ))}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* ── Paginazione ──────────────────────────────────────── */}
                {pagine > 1 && <Pager current={offset} total={pagine} onChange={setOffset} />}

            </div>
        </div>
    )
}
