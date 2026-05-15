/**
 * SchedaPunti.jsx — Storico punti (PX, Shin, Mestiere) della scheda personaggio
 *
 * Gestisce tre pagine con la stessa struttura (tabella paginata):
 *   scheda_px         → tipo=px        (tabella Punti, campi esperienza + notorieta)
 *   scheda_px_shin    → tipo=shin      (tabella Punti WHERE shin>0, campo shin)
 *   scheda_px_mestiere→ tipo=mestiere  (tabella PuntiMestiere, campo mestiere)
 *
 * Fetch: api_scheda.php?op=punti&pg=X&tipo=Y&offset=N
 * La paginazione usa lo stesso schema dell'originale PHP: offset = numero pagina.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'

// ---------------------------------------------------------------------------
// PAGINAZIONE
// ---------------------------------------------------------------------------

/**
 * Genera la sequenza di pagine da mostrare, con ellissi per range lunghi.
 * Mostra sempre prima, ultima, e una finestra di 2 pagine attorno alla corrente.
 * Esempio con 20 pagine, corrente=9: 1 … 7 8 [9] 10 11 … 20
 */
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

function Pager({ current, total, onChange }) {
    return (
        <div className="scheda-pager">
            <button className="nav" disabled={current === 0}
                onClick={() => onChange(current - 1)}>&#8249;</button>

            {buildPages(current, total).map((p, i) =>
                p === '…'
                    ? <span key={`e${i}`} className="ellipsis">…</span>
                    : <button key={p} className={p === current ? 'active' : ''}
                        onClick={() => p !== current && onChange(p)}>
                        {p + 1}
                      </button>
            )}

            <button className="nav" disabled={current === total - 1}
                onClick={() => onChange(current + 1)}>&#8250;</button>
        </div>
    )
}

// ---------------------------------------------------------------------------
// CONFIGURAZIONE PER PAGINA
// ---------------------------------------------------------------------------

const PAGE_CONFIG = {
    scheda_px: {
        tipo:    'px',
        title:   'Riepilogo PX',
        colonne: [
            { key: 'data',        label: 'Data' },
            { key: 'titolo',      label: 'Titolo' },
            { key: 'commento',    label: 'Commento' },
            { key: 'esperienza',  label: 'Punti Exp' },
            { key: 'notorieta',   label: 'Notorietà' },
        ],
    },
    scheda_px_shin: {
        tipo:    'shin',
        title:   'Riepilogo Punti Shin',
        colonne: [
            { key: 'data',     label: 'Data' },
            { key: 'titolo',   label: 'Titolo' },
            { key: 'commento', label: 'Commento' },
            { key: 'shin',     label: 'Punti Shin' },
        ],
    },
    scheda_px_mestiere: {
        tipo:    'mestiere',
        title:   'Riepilogo Punti Mestiere',
        colonne: [
            { key: 'data',      label: 'Data' },
            { key: 'titolo',    label: 'Titolo' },
            { key: 'commento',  label: 'Commento' },
            { key: 'mestiere',  label: 'Punti Mestiere' },
        ],
    },
}

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return isNaN(d) ? dateStr : d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaPunti() {
    const params = new URLSearchParams(window.location.search)
    const pg     = params.get('pg')   ?? ''
    const page   = params.get('page') ?? 'scheda_px'
    const config = PAGE_CONFIG[page]  ?? PAGE_CONFIG.scheda_px

    const [profile,  setProfile]  = useState(null)
    const [data,     setData]     = useState(null)
    const [offset,   setOffset]   = useState(parseInt(params.get('offset') ?? '0', 10))
    const [error,    setError]    = useState(null)

    // Carica i dati dell'API punti per la pagina e l'offset correnti
    const fetchData = useCallback((pg, tipo, off) => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=punti&pg=${enc}&tipo=${tipo}&offset=${off}`)
            .then(r => r.json())
    }, [])

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            fetchData(pg, config.tipo, offset),
        ]).then(([prof, d]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (d.success)    setData(d)
            else              setError(d.message  ?? 'Errore dati punti')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg, config.tipo, offset])

    // Cambio pagina: aggiorna offset e ricarica solo i dati punti
    const goToPage = (newOffset) => {
        setOffset(newOffset)
        fetchData(pg, config.tipo, newOffset)
            .then(d => { if (d.success) setData(d) })
            .catch(() => {})
    }

    if (error)             return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !data) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const { records, totale, per_page } = data
    const pagine = Math.ceil(totale / per_page)

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>{config.title}</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />
                <div className="title">{nome} {cognome}</div>
            </div>

            <div className="pagina_scheda_px">
                <div className="page_body">
                    <div className="panels_box">
                        <div className="elenco_record_gioco">
                            <table className="customTable">
                                <thead>
                                    <tr>
                                        {config.colonne.map(col => (
                                            <td key={col.key} className="casella_titolo">
                                                <div className="titoli_elenco">{col.label}</div>
                                            </td>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {records.length === 0
                                        ? <tr><td colSpan={config.colonne.length}
                                            style={{ textAlign: 'center', padding: 20 }}>
                                                Nessun record trovato.
                                          </td></tr>
                                        : records.map((r, i) => (
                                            <tr key={i}>
                                                {config.colonne.map(col => (
                                                    <td key={col.key} className="casella_elemento">
                                                        <div className="elementi_elenco">
                                                            {col.key === 'data' ? formatDate(r.data) : (r[col.key] ?? '')}
                                                        </div>
                                                    </td>
                                                ))}
                                            </tr>
                                        ))
                                    }
                                </tbody>
                            </table>
                        </div>

                        {/* Paginazione */}
                        {pagine > 1 && (
                            <Pager current={offset} total={pagine} onChange={goToPage} />
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
