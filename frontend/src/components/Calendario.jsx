/**
 * Calendario.jsx — Calendario impegni (SPA)
 *
 * Sostituisce il vecchio pages/agenda_center.inc.php + pages/agenda/* (PHP
 * legacy, tabella HTML disegnata a mano, overlib_mini.js per i tooltip).
 * Griglia mensile scritta a mano con Date nativo — nessuna libreria calendario
 * (coerente con frontend/package.json, che non ne ha nessuna).
 *
 * Vista mese → API op=month, payload leggero per giorno (colore/ora/luogo,
 * niente nota): basta sia per i pallini colorati (presenza impegni a colpo
 * d'occhio) sia per l'anteprima hover, senza una fetch aggiuntiva al passaggio
 * del mouse. Il dettaglio completo (nota, partecipanti, permessi di modifica)
 * arriva solo al click su un giorno, via op=day.
 *
 * Autocomplete "personaggi coinvolti": stesso pattern UX del menu @ menzione
 * nella chat di gioco (ChatShell.jsx, sussurri rapidi) — digitazione, dropdown
 * filtrato, click per selezionare — ma sorgente diversa: qui op=search_personaggi
 * cerca su tutti i personaggi, non solo i presenti in stanza.
 *
 * Filtro "calendario di un altro utente": di default si vedono solo i propri
 * impegni (autore/partecipante/pubblico). Cercando un nome (o scegliendo
 * "Tutti") si aggiungono gli impegni di quell'utente — o di chiunque abbia
 * condiviso il calendario — SE ha attivato l'opzione in Preferenze; altrimenti
 * l'API risponde con utente_condiviso=false e viene mostrato un avviso. Vedi
 * pages/api_calendario.php per la logica di visibilità lato server.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import { createPortal } from 'react-dom'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

/**
 * Coordinate (viewport) dell'elemento riferito da ref, aggiornate finche'
 * active e' true. Usata dai due autocomplete sotto per portare il loro
 * dropdown su document.body in position:fixed, invece che absolute nel
 * flusso: .calendario-page__modal scorre (overflow-y:auto) e lo taglierebbe
 * — stesso problema e stessa soluzione del menu @ menzione in ChatShell.jsx.
 */
function useAnchoredRect(ref, active) {
    const [rect, setRect] = useState(null)
    useEffect(() => {
        if (!active) { setRect(null); return }
        const update = () => {
            const el = ref.current
            if (!el) return
            const r = el.getBoundingClientRect()
            setRect({ left: r.left, width: r.width, top: r.bottom })
        }
        update()
        window.addEventListener('resize', update)
        window.addEventListener('scroll', update, true)
        return () => {
            window.removeEventListener('resize', update)
            window.removeEventListener('scroll', update, true)
        }
    }, [active, ref])
    return rect
}

// ---------------------------------------------------------------------------
// DATI STATICI
// ---------------------------------------------------------------------------

// Set predefinito — deve combaciare con CALENDARIO_COLORI in api_calendario.php
const COLORI = [
    { key: 'gold',    hex: '#daa832', label: 'Oro' },
    { key: 'azzurro', hex: '#4da6ff', label: 'Azzurro' },
    { key: 'verde',   hex: '#27ae60', label: 'Verde' },
    { key: 'rosso',   hex: '#e74c3c', label: 'Rosso' },
    { key: 'arancio', hex: '#f39c12', label: 'Arancio' },
    { key: 'viola',   hex: '#9b59b6', label: 'Viola' },
    { key: 'rosa',    hex: '#e685b5', label: 'Rosa' },
    { key: 'ciano',   hex: '#2fe0d1', label: 'Ciano' },
]
const COLORE_HEX = Object.fromEntries(COLORI.map(c => [c.key, c.hex]))

const MESI = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio',
    'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre']
const GIORNI_SETTIMANA = ['L', 'M', 'M', 'G', 'V', 'S', 'D']

function pad2(n) { return String(n).padStart(2, '0') }
function toYMD(y, m, d) { return `${y}-${pad2(m)}-${pad2(d)}` }

function formaEventoVuoto(dataDefault) {
    return {
        id: null,
        colore: 'gold',
        titolo: '',
        luogo: '',
        data: dataDefault,
        ora: '',
        nota: '',
        // Deselezionata di default: con la spunta pre-attivata, allo staff
        // bastava dimenticarsi di toglierla per rendere pubblico (visibile a
        // tutti) un evento privato con partecipanti specifici — vedi bug
        // riportato: "aggiungo un pg ma lo vedono tutti".
        pubblico: false,
        partecipanti: [],
    }
}

/**
 * Selettore luogo — non un <select> nativo: il popup delle opzioni e'
 * disegnato dal sistema operativo su mobile (Android/iOS), fuori dalla
 * portata di qualunque CSS della pagina (color-scheme non basta, verificato
 * su schermo). Lista in-flow (non un overlay posizionato) invece che un
 * dropdown assoluto: la modale del form ha gia' overflow-y:auto, quindi si
 * allunga o scorre senza rischio dei bug di clipping gia' visti altrove in
 * questa sessione (popup presenti_estesi tagliati dal contenitore).
 */
function LuogoPicker({ value, luoghi, onChange }) {
    const [open, setOpen] = useState(false)
    return (
        <div className="calendario-page__luogo-picker">
            <button type="button" className="calendario-page__luogo-picker-btn" onClick={() => setOpen(o => !o)}>
                <span>{value || '— Nessuno —'}</span>
                <i className={`fa-solid fa-chevron-${open ? 'up' : 'down'}`} />
            </button>
            {open && (
                <div className="calendario-page__luogo-picker-list">
                    <button
                        type="button"
                        className={!value ? 'is-active' : ''}
                        onClick={() => { onChange(''); setOpen(false) }}
                    >
                        — Nessuno —
                    </button>
                    {luoghi.map(l => (
                        <button
                            key={l}
                            type="button"
                            className={value === l ? 'is-active' : ''}
                            onClick={() => { onChange(l); setOpen(false) }}
                        >
                            {l}
                        </button>
                    ))}
                </div>
            )}
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE
// ---------------------------------------------------------------------------

export default function Calendario({ isStaff }) {

    const oggi = new Date()
    const [anno, setAnno] = useState(oggi.getFullYear())
    const [mese, setMese] = useState(oggi.getMonth() + 1) // 1-12

    // ── Vista mese ───────────────────────────────────────────────────────
    const [giorni, setGiorni] = useState({})
    const [loadingMonth, setLoadingMonth] = useState(true)
    const [luoghi, setLuoghi] = useState([])
    // Offset anno ambientazione (da config.inc.php via api_calendario.php): solo
    // l'etichetta mostrata cambia, la griglia dei giorni resta sull'anno reale.
    const [annoOffset, setAnnoOffset] = useState(0)

    // ── Filtro "calendario di un altro utente" ──────────────────────────
    // '' = solo i miei impegni (default), '__all__' = tutti quelli condivisi,
    // altrimenti il nome di un personaggio specifico. Si aggiunge alla vista
    // di base (i miei impegni restano sempre visibili), non la sostituisce.
    const [filtroUtente, setFiltroUtente] = useState('')
    const [filtroCondiviso, setFiltroCondiviso] = useState(null) // esito dell'ultima fetch: null=n/d, true/false
    const [filtroInput, setFiltroInput] = useState('')
    const [filtroSuggestions, setFiltroSuggestions] = useState([])
    const filtroDebounce = useRef(null)
    const filtroAcRef = useRef(null)
    const filtroRect = useAnchoredRect(filtroAcRef, filtroSuggestions.length > 0)

    const fetchMonth = useCallback(() => {
        setLoadingMonth(true)
        const utenteQs = filtroUtente ? `&utente=${encodeURIComponent(filtroUtente)}` : ''
        fetch(`/pages/api_calendario.php?op=month&y=${anno}&m=${mese}${utenteQs}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    setGiorni(d.giorni)
                    setFiltroCondiviso(d.utente_condiviso ?? null)
                    setAnnoOffset(d.anno_offset ?? 0)
                }
            })
            .catch(() => {})
            .finally(() => setLoadingMonth(false))
    }, [anno, mese, filtroUtente])

    const handleFiltroInput = (val) => {
        setFiltroInput(val)
        clearTimeout(filtroDebounce.current)
        const q = val.trim()
        if (q.length < 1) { setFiltroSuggestions([]); return }
        filtroDebounce.current = setTimeout(() => {
            fetch(`/pages/api_calendario.php?op=search_personaggi&q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(d => { if (d.success) setFiltroSuggestions(d.nomi) })
                .catch(() => {})
        }, 250)
    }

    const selezionaFiltroUtente = (nome) => {
        setFiltroUtente(nome)
        setFiltroInput('')
        setFiltroSuggestions([])
    }

    const selezionaFiltroTutti = () => {
        setFiltroUtente('__all__')
        setFiltroInput('')
        setFiltroSuggestions([])
    }

    const resetFiltroUtente = () => {
        setFiltroUtente('')
        setFiltroCondiviso(null)
        setFiltroInput('')
        setFiltroSuggestions([])
    }

    useEffect(() => { fetchMonth() }, [fetchMonth])

    useEffect(() => {
        fetch('/pages/api_calendario.php?op=luoghi')
            .then(r => r.json())
            .then(d => { if (d.success) setLuoghi(d.luoghi) })
            .catch(() => {})
    }, [])

    const cambiaMese = (delta) => {
        setHover(null)
        let m = mese + delta
        let y = anno
        if (m < 1) { m = 12; y-- }
        if (m > 12) { m = 1; y++ }
        setMese(m); setAnno(y)
    }

    // ── Anteprima hover (dati già in mano dal payload mese) ────────────────
    const [hover, setHover] = useState(null) // { data, rect }

    // ── Modale giorno ───────────────────────────────────────────────────
    const [selectedDay, setSelectedDay] = useState(null)
    const [dayEvents, setDayEvents] = useState([])
    const [loadingDay, setLoadingDay] = useState(false)

    const apriGiorno = (ymd) => {
        setSelectedDay(ymd)
        setHover(null)
        setLoadingDay(true)
        const utenteQs = filtroUtente ? `&utente=${encodeURIComponent(filtroUtente)}` : ''
        fetch(`/pages/api_calendario.php?op=day&data=${ymd}${utenteQs}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    setDayEvents(d.eventi)
                    setFiltroCondiviso(d.utente_condiviso ?? null)
                }
            })
            .catch(() => {})
            .finally(() => setLoadingDay(false))
    }

    // Cambio filtro utente: ricarica il mese e, se un giorno e' aperto, anche quello.
    useEffect(() => {
        if (selectedDay) apriGiorno(selectedDay)
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filtroUtente])

    const chiudiGiorno = () => { setSelectedDay(null); setDayEvents([]) }

    const ricaricaDopoSalvataggio = (ymd) => {
        fetchMonth()
        if (selectedDay) apriGiorno(selectedDay)
        else if (ymd) apriGiorno(ymd)
        // Aggiorna subito il pallino "Calendario" nell'HUD (useHudBadges.js):
        // senza, resterebbe quello del primo caricamento fino al prossimo
        // reload di pagina, dato che Hud.jsx non rimonta mai nella SPA.
        window.dispatchEvent(new CustomEvent('ct:calendario-update'))
    }

    // ── Form crea/modifica ──────────────────────────────────────────────
    const [form, setForm] = useState(null)
    const [formError, setFormError] = useState('')
    const [saving, setSaving] = useState(false)

    const apriCreazione = (ymd) => {
        setForm(formaEventoVuoto(ymd))
        setFormError('')
        setPartInput('')
        setPartSuggestions([])
    }

    const apriModifica = (evento) => {
        setForm({
            id: evento.id,
            colore: evento.colore,
            titolo: evento.titolo ?? '',
            luogo: evento.luogo ?? '',
            data: evento.data,
            ora: evento.ora ?? '',
            nota: evento.nota ?? '',
            pubblico: evento.pubblico,
            partecipanti: evento.partecipanti,
        })
        setFormError('')
        setPartInput('')
        setPartSuggestions([])
    }

    const chiudiForm = () => { setForm(null); setFormError('') }

    const salvaForm = () => {
        if (!form || saving) return
        setSaving(true)
        setFormError('')
        const op = form.id ? 'update' : 'create'
        fetch(`/pages/api_calendario.php?op=${op}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form),
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const dataEvento = form.data
                    chiudiForm()
                    ricaricaDopoSalvataggio(dataEvento)
                } else {
                    setFormError(d.message || 'Errore nel salvataggio')
                }
            })
            .catch(() => setFormError('Errore di rete'))
            .finally(() => setSaving(false))
    }

    const eliminaEvento = (id) => {
        if (!window.confirm('Eliminare questo impegno?')) return
        fetch('/pages/api_calendario.php?op=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        })
            .then(r => r.json())
            .then(d => { if (d.success) ricaricaDopoSalvataggio(null) })
            .catch(() => {})
    }

    // ── Autocomplete partecipanti ───────────────────────────────────────
    const [partInput, setPartInput] = useState('')
    const [partSuggestions, setPartSuggestions] = useState([])
    const partDebounce = useRef(null)
    const partAcRef = useRef(null)
    const partRect = useAnchoredRect(partAcRef, partSuggestions.length > 0)

    const handlePartInput = (val) => {
        setPartInput(val)
        clearTimeout(partDebounce.current)
        const q = val.trim()
        if (q.length < 1) { setPartSuggestions([]); return }
        partDebounce.current = setTimeout(() => {
            fetch(`/pages/api_calendario.php?op=search_personaggi&q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(d => {
                    if (d.success) setPartSuggestions(d.nomi.filter(n => !form?.partecipanti.includes(n)))
                })
                .catch(() => {})
        }, 250)
    }

    const aggiungiPartecipante = (nome) => {
        setForm(f => f ? { ...f, partecipanti: [...f.partecipanti, nome] } : f)
        setPartInput('')
        setPartSuggestions([])
    }

    const rimuoviPartecipante = (nome) => {
        setForm(f => f ? { ...f, partecipanti: f.partecipanti.filter(n => n !== nome) } : f)
    }

    // ── Griglia mensile ─────────────────────────────────────────────────
    const numGiorni = new Date(anno, mese, 0).getDate()
    let offset = new Date(anno, mese - 1, 1).getDay() // 0=domenica
    offset = offset === 0 ? 6 : offset - 1 // lunedì=0

    const celle = []
    for (let i = 0; i < offset; i++) celle.push(null)
    for (let d = 1; d <= numGiorni; d++) celle.push(d)

    const oggiYMD = toYMD(oggi.getFullYear(), oggi.getMonth() + 1, oggi.getDate())
    const login = window.CT_USER?.login ?? ''

    /** Etichetta di riepilogo quando l'evento non ha un titolo esplicito. */
    const etichettaEvento = (ev) => {
        if (ev.titolo) return ev.titolo
        if (ev.pubblico) return 'Evento pubblico'
        const altri = [ev.autore, ...ev.partecipanti].filter(n => n !== login)
        return altri.length > 0 ? `Con ${altri.join(', ')}` : 'Impegno personale'
    }

    return (
        <>
            {/* Fuori da .calendario-page apposta: ha un suo max-width
                centrato (margin:auto) dentro .output (gia' al 90% e
                centrato di suo, vedi _layout.scss) — dentro la card il
                pulsante sarebbe "in alto a sinistra" solo rispetto alla
                card, non alla pagina, apparendo verso il centro schermo
                (stesso bug gia' corretto per Preferenze/IncrementoParametri). */}
            <div className="link_back link_back--left">
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>

            <div className="calendario-page">

            <div className="calendario-page__title">Calendario</div>

            <div className="calendario-page__nav">
                <button type="button" onClick={() => cambiaMese(-1)} aria-label="Mese precedente">
                    <i className="fa-solid fa-chevron-left" />
                </button>
                <span className="calendario-page__mese">{MESI[mese - 1]} {anno + annoOffset}</span>
                <button type="button" onClick={() => cambiaMese(1)} aria-label="Mese successivo">
                    <i className="fa-solid fa-chevron-right" />
                </button>
            </div>

            <div className="calendario-page__filtro">
                <span className="calendario-page__filtro-label">
                    {filtroUtente === '' && 'Solo i miei impegni'}
                    {filtroUtente === '__all__' && 'I miei impegni + tutti quelli condivisi'}
                    {filtroUtente !== '' && filtroUtente !== '__all__' && `I miei impegni + quelli di ${filtroUtente}`}
                </span>

                <button type="button" className="calendario-page__filtro-tutti" onClick={selezionaFiltroTutti}>
                    Tutti
                </button>

                <div className="calendario-page__autocomplete calendario-page__filtro-autocomplete" ref={filtroAcRef}>
                    <input
                        type="text"
                        value={filtroInput}
                        placeholder="Cerca un utente…"
                        onChange={e => handleFiltroInput(e.target.value)}
                        autoComplete="off"
                    />
                    {filtroSuggestions.length > 0 && filtroRect && createPortal(
                        <div
                            className="calendario-page__autocomplete-list calendario-page__autocomplete-list--fixed"
                            style={{ left: filtroRect.left, width: filtroRect.width, top: filtroRect.top }}
                        >
                            {filtroSuggestions.map(nome => (
                                <button key={nome} type="button" onClick={() => selezionaFiltroUtente(nome)}>
                                    {nome}
                                </button>
                            ))}
                        </div>,
                        document.body
                    )}
                </div>

                {filtroUtente !== '' && (
                    <button type="button" className="calendario-page__filtro-reset" onClick={resetFiltroUtente}>
                        Solo i miei
                    </button>
                )}
            </div>

            {filtroCondiviso === false && (
                <p className="calendario-page__filtro-avviso">
                    {filtroUtente} non condivide il proprio calendario: non puoi visualizzare i suoi impegni.
                </p>
            )}

            <div className={`calendario-page__grid${loadingMonth ? ' is-loading' : ''}`}>
                <div className="calendario-page__weekdays">
                    {GIORNI_SETTIMANA.map((g, i) => <span key={i}>{g}</span>)}
                </div>
                <div className="calendario-page__days">
                    {celle.map((d, i) => {
                        if (d === null) return <div key={i} className="calendario-page__day calendario-page__day--empty" />
                        const ymd = toYMD(anno, mese, d)
                        const eventi = giorni[ymd] ?? []
                        return (
                            <div
                                key={i}
                                className={`calendario-page__day${ymd === oggiYMD ? ' is-today' : ''}${eventi.length ? ' has-events' : ''}`}
                                onClick={() => apriGiorno(ymd)}
                                onMouseEnter={(e) => setHover({ data: ymd, rect: e.currentTarget.getBoundingClientRect() })}
                                onMouseLeave={() => setHover(h => (h?.data === ymd ? null : h))}
                            >
                                <span className="calendario-page__day-num">{d}</span>
                                {eventi.length > 0 && (
                                    <div className="calendario-page__dots">
                                        {eventi.slice(0, 4).map(ev => (
                                            <span key={ev.id} className="calendario-page__dot" style={{ background: COLORE_HEX[ev.colore] }} />
                                        ))}
                                    </div>
                                )}
                            </div>
                        )
                    })}
                </div>
            </div>

            {hover && (giorni[hover.data]?.length > 0) && (
                <div
                    className="calendario-page__preview"
                    style={{
                        top: hover.rect.bottom + 6,
                        left: Math.min(hover.rect.left, window.innerWidth - 240),
                    }}
                >
                    {giorni[hover.data].map(ev => (
                        <div key={ev.id} className="calendario-page__preview-row">
                            <div>
                                <span className="calendario-page__preview-dot" style={{ background: COLORE_HEX[ev.colore] }} />
                                {ev.ora && <b>{ev.ora}</b>} {ev.luogo || 'Luogo non specificato'}
                            </div>
                            <div className="calendario-page__preview-pg">
                                {[ev.autore, ...ev.partecipanti].join(', ')}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* ============ MODALE GIORNO ============ */}
            {/* Portal su document.body: #maincontent (position:fixed) crea sempre un
                proprio stacking context, quindi qualunque z-index qui dentro resterebbe
                intrappolato sotto .ct-hud (z-index:500) — stesso motivo del portal in
                ChatShell.jsx per le sue modali. */}
            {selectedDay && createPortal(
                <div className="calendario-page__modal-overlay" onClick={chiudiGiorno}>
                    <div className="calendario-page__modal" onClick={e => e.stopPropagation()}>
                        <div className="calendario-page__modal-head">
                            <span>{selectedDay.split('-').reverse().join('/')}</span>
                            <button onClick={chiudiGiorno} aria-label="Chiudi">×</button>
                        </div>

                        {loadingDay ? (
                            <div className="calendario-page__modal-loading">Caricamento…</div>
                        ) : (
                            <div className="calendario-page__modal-body">
                                {dayEvents.length === 0 && <p className="calendario-page__modal-empty">Nessun impegno.</p>}
                                {dayEvents.map(ev => (
                                    <div key={ev.id} className="calendario-page__evento" style={{ borderColor: COLORE_HEX[ev.colore] }}>
                                        <div className="calendario-page__evento-head">
                                            <span className="calendario-page__evento-dot" style={{ background: COLORE_HEX[ev.colore] }} />
                                            <strong>{etichettaEvento(ev)}</strong>
                                            {ev.ora && <span className="calendario-page__evento-ora">{ev.ora}</span>}
                                        </div>
                                        {ev.luogo && (
                                            <div className="calendario-page__evento-luogo">
                                                <i className="fa-solid fa-location-dot" /> {ev.luogo}
                                            </div>
                                        )}
                                        {ev.nota && <div className="calendario-page__evento-nota">{ev.nota}</div>}
                                        <div className="calendario-page__evento-pg">
                                            <i className="fa-solid fa-users" /> {[ev.autore, ...ev.partecipanti].join(', ')}
                                        </div>
                                        {ev.editable && (
                                            <div className="calendario-page__evento-actions">
                                                <button type="button" onClick={() => apriModifica(ev)}>Modifica</button>
                                                <button type="button" onClick={() => eliminaEvento(ev.id)}>Elimina</button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                                <button type="button" className="calendario-page__nuovo-btn" onClick={() => apriCreazione(selectedDay)}>
                                    <i className="fa-solid fa-plus" /> Nuovo impegno
                                </button>
                            </div>
                        )}
                    </div>
                </div>,
                document.body
            )}

            {/* ============ FORM CREA/MODIFICA ============ */}
            {form && createPortal(
                <div className="calendario-page__modal-overlay" onClick={chiudiForm}>
                    <div className="calendario-page__modal" onClick={e => e.stopPropagation()}>
                        <div className="calendario-page__modal-head">
                            <span>{form.id ? 'Modifica impegno' : 'Nuovo impegno'}</span>
                            <button onClick={chiudiForm} aria-label="Chiudi">×</button>
                        </div>
                        <div className="calendario-page__modal-body">

                            <label className="calendario-page__field">
                                <span>Colore</span>
                                <div className="calendario-page__colori">
                                    {COLORI.map(c => (
                                        <button
                                            key={c.key}
                                            type="button"
                                            title={c.label}
                                            className={`calendario-page__colore-swatch${form.colore === c.key ? ' is-active' : ''}`}
                                            style={{ background: c.hex }}
                                            onClick={() => setForm(f => ({ ...f, colore: c.key }))}
                                        />
                                    ))}
                                </div>
                            </label>

                            {isStaff && (
                                <label className="calendario-page__field calendario-page__field--checkbox">
                                    <input
                                        type="checkbox"
                                        checked={form.pubblico}
                                        onChange={() => setForm(f => ({ ...f, pubblico: !f.pubblico }))}
                                    />
                                    <span>Evento pubblico (visibile a tutti)</span>
                                </label>
                            )}

                            <label className="calendario-page__field">
                                <span>Titolo (opzionale)</span>
                                <input
                                    type="text"
                                    value={form.titolo}
                                    onChange={e => setForm(f => ({ ...f, titolo: e.target.value }))}
                                    maxLength={100}
                                />
                            </label>

                            <label className="calendario-page__field">
                                <span>Personaggi coinvolti</span>
                                {form.partecipanti.length > 0 && (
                                    <div className="calendario-page__chips">
                                        {form.partecipanti.map(nome => (
                                            <span key={nome} className="calendario-page__chip">
                                                {nome}
                                                <button type="button" onClick={() => rimuoviPartecipante(nome)} aria-label={`Rimuovi ${nome}`}>×</button>
                                            </span>
                                        ))}
                                    </div>
                                )}
                                <div className="calendario-page__autocomplete" ref={partAcRef}>
                                    <input
                                        type="text"
                                        value={partInput}
                                        placeholder="Cerca personaggio…"
                                        onChange={e => handlePartInput(e.target.value)}
                                        autoComplete="off"
                                    />
                                    {partSuggestions.length > 0 && partRect && createPortal(
                                        <div
                                            className="calendario-page__autocomplete-list calendario-page__autocomplete-list--fixed"
                                            style={{ left: partRect.left, width: partRect.width, top: partRect.top }}
                                        >
                                            {partSuggestions.map(nome => (
                                                <button key={nome} type="button" onClick={() => aggiungiPartecipante(nome)}>
                                                    {nome}
                                                </button>
                                            ))}
                                        </div>,
                                        document.body
                                    )}
                                </div>
                            </label>

                            <label className="calendario-page__field">
                                <span>Luogo</span>
                                <LuogoPicker
                                    value={form.luogo}
                                    luoghi={luoghi}
                                    onChange={l => setForm(f => ({ ...f, luogo: l }))}
                                />
                            </label>

                            <div className="calendario-page__field-row">
                                <label className="calendario-page__field">
                                    <span>Data</span>
                                    <input
                                        type="date"
                                        value={form.data}
                                        min={oggiYMD}
                                        onChange={e => setForm(f => ({ ...f, data: e.target.value }))}
                                    />
                                </label>
                                <label className="calendario-page__field">
                                    <span>Ora (opzionale)</span>
                                    <input
                                        type="time"
                                        value={form.ora}
                                        onChange={e => setForm(f => ({ ...f, ora: e.target.value }))}
                                    />
                                </label>
                            </div>

                            <label className="calendario-page__field">
                                <span>Nota</span>
                                <textarea
                                    value={form.nota}
                                    onChange={e => setForm(f => ({ ...f, nota: e.target.value }))}
                                    rows={3}
                                    maxLength={2000}
                                />
                            </label>

                            {formError && <p className="calendario-page__form-error">{formError}</p>}

                            <button type="button" className="calendario-page__salva-btn" onClick={salvaForm} disabled={saving}>
                                {saving ? 'Salvataggio…' : 'Salva'}
                            </button>
                        </div>
                    </div>
                </div>,
                document.body
            )}

            </div>
        </>
    )
}
