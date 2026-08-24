/**
 * ScegliMestiere.jsx — Selezione, dettaglio e avanzamento del mestiere.
 *
 * Nessun concetto di "step" (non più due viste separate scelta/avanzamento):
 * il dettaglio di un mestiere è sempre raggiungibile da chiunque, confermato
 * lì, confermato altrove, o senza mestiere — stesso principio già in vigore
 * per ScegliRazza.jsx (razza) e servizi_mestieri.inc.php (gilde giocatore).
 * Stesso linguaggio visivo/meccanismo di servizi_mestieri.inc.php (classi
 * .sm-*, mai duplicate qui — vedi _servizi_mestieri.scss, già riusato anche
 * da MiaGilda.jsx): elenco mestieri con contatore affiliati, click apre il
 * dettaglio (gerarchia + lavoratori + statuto). Se il personaggio è già
 * confermato in QUEL mestiere, il dettaglio mostra anche, in cima,
 * "Avanzamento" (i ranghi raggiungibili), — per i mestieri con un negozio
 * dedicato, oggi solo Magic Shop — "Gestisci oggetti del mestiere", e infine
 * "Abbandona Mestiere" (stesso stile/componente di "Abbandona Razza" in
 * ScegliRazza.jsx — vedi ConfirmDanger.jsx). Vedi conversazione di progetto
 * del 2026-08-24.
 *
 * API: pages/api_mestiere.php
 *   op=getState  GET  → { hasConferma, idMestiere, esperienza, expMestiere,
 *                          currentIdRuolo, mestieri (ranghi raggiungibili, solo se confermato) }
 *   op=list      GET  → { sezione, mestieri: [{id, nome, n}] }
 *   op=detail    GET  → { mestiere, ruoli, affiliati, statuto, entryIdRuolo,
 *                          vieneConfermatoQui, giaConfermatoAltrove, gestioneOggetti }
 *   op=change    POST { id_record, mestiere } → sceglie e conferma il mestiere
 *                (rifiutato lato server se già impiegato altrove)
 *   op=levelUp   POST { id_record, mestiere } → avanza livello nel proprio mestiere
 *   op=leave     POST {} → abbandona il mestiere confermato (reset completo)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import ConfirmDanger from './ConfirmDanger'

const API = 'pages/api_mestiere.php'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

function leggiIdMestiere() {
    const p = new URLSearchParams(window.location.search)
    const v = parseInt(p.get('id_mestiere') ?? '', 10)
    return Number.isFinite(v) && v > 0 ? v : null
}

// ── Elenco mestieri ───────────────────────────────────────────────────────

function MestiereList({ onOpen }) {
    const [data, setData]   = useState(null)
    const [error, setError] = useState(null)

    useEffect(() => {
        fetch(`${API}?op=list`)
            .then(r => r.json())
            .then(d => { if (d.success) setData(d); else setError(d.message ?? 'Errore sconosciuto') })
            .catch(e => setError(e.message))
    }, [])

    if (error) return (
        <div className="sm-state sm-state--error">
            <i className="fas fa-exclamation-triangle" />
            <p>{error}</p>
        </div>
    )
    if (!data) return (
        <div className="sm-state">
            <i className="fas fa-spinner fa-spin" />
            <p>Caricamento…</p>
        </div>
    )

    return (
        <section className="sm-section">
            <div className="sm-section-title">{data.sezione}</div>
            {data.mestieri.length === 0 ? (
                <p className="sm-field-note" style={{ textAlign: 'center', padding: 20 }}>
                    Nessun mestiere disponibile al momento.
                </p>
            ) : (
                <div className="sm-list">
                    {data.mestieri.map(m => (
                        <div className="sm-list-item" key={m.id}>
                            <button type="button" className="sm-list-link" onClick={() => onOpen(m.id)}>
                                <i className="fas fa-briefcase sm-list-icon" />
                                <span className="sm-list-name">{m.nome}</span>
                            </button>
                            <span className="sm-list-count">{m.n}</span>
                            <button type="button" className="sm-list-statute" title="Statuto"
                                    onClick={() => navigate(`main.php?page=statuto_main&id2=${m.id}`)}>
                                <i className="fas fa-scroll" />
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </section>
    )
}

// ── Avanzamento — solo se il personaggio è confermato in questo mestiere ──
// Auto-contenuta (fetch/stato/errore propri), stesso principio di "entra"
// dentro MestiereDetail: ogni azione gestisce la propria richiesta/feedback,
// nessuno stato di livello va tenuto/passato dal componente principale.

function AvanzamentoSection({ expMestiere, ranghi, onChanged }) {
    const [busyId, setBusyId] = useState(null)
    const [err, setErr]       = useState(null)

    const avanza = async (r) => {
        setBusyId(r.id)
        setErr(null)
        try {
            const res = await fetch(`${API}?op=levelUp`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id_record: r.id, mestiere: r.mestiere }),
            })
            const d = await res.json()
            setBusyId(null)
            if (d.success) onChanged()
            else setErr(d.message ?? 'Errore nell\'avanzamento')
        } catch (e) {
            setBusyId(null)
            setErr(e.message)
        }
    }

    return (
        <section className="sm-section">
            <div className="sm-section-title">
                <i className="fas fa-star" /> Avanzamento
            </div>
            <p className="sm-field-note" style={{ marginBottom: 12 }}>
                {expMestiere > 55 ? 'Hai raggiunto il massimo dei punti mestiere.' : `Hai ${expMestiere} punti mestiere.`}
            </p>
            {err && <p className="sm-error-text">{err}</p>}
            {ranghi.length > 0 && (
                <div className="sm-rank-list">
                    {ranghi.map(r => (
                        <div className="sm-rank-item" key={r.id}>
                            <div className="sm-img-box">
                                {r.immagine
                                    ? <img src={`imgs/mestieri/${r.immagine}`} alt={r.nome} />
                                    : <i className="fas fa-medal" />}
                            </div>
                            <span className="sm-rank-name">{r.nome}</span>
                            {r.unlocked
                                ? <button type="button" className="btn btn--primary" disabled={busyId === r.id}
                                          onClick={() => avanza(r)}>
                                      <i className="fas fa-arrow-up" /> {busyId === r.id ? 'Avanzo…' : 'Avanza'}
                                  </button>
                                : <span className="sm-field-note">Esperienza insufficiente</span>}
                        </div>
                    ))}
                </div>
            )}
        </section>
    )
}

// ── Dettaglio mestiere (gerarchia + lavoratori + statuto + avanzamento +
// gestione oggetti + entra) ────────────────────────────────────────────────

function MestiereDetail({ id, avanzamento, onBack, onAffiliationChanged, onChanged }) {
    const [data, setData]     = useState(null)
    const [error, setError]   = useState(null)
    const [joining, setJoining] = useState(false)
    const [joinError, setJoinError] = useState(null)
    const [confirmLeaveOpen, setConfirmLeaveOpen] = useState(false)
    const [leaving, setLeaving] = useState(false)
    const [leaveError, setLeaveError] = useState(null)

    useEffect(() => {
        setData(null)
        setError(null)
        fetch(`${API}?op=detail&id_mestiere=${id}`)
            .then(r => r.json())
            .then(d => { if (d.success) setData(d); else setError(d.message ?? 'Errore sconosciuto') })
            .catch(e => setError(e.message))
    }, [id])

    // Un solo click sceglie E conferma, nessuna fase provvisoria: stesso
    // avviso "irreversibile" di prima del redesign, ora davanti a questo
    // pulsante invece che a un secondo passaggio sbloccato a punti.
    const entra = async () => {
        if (!data?.entryIdRuolo) return
        if (!window.confirm('Confermare il mestiere? Non sarà più possibile cambiarlo.')) return
        setJoining(true)
        setJoinError(null)
        try {
            const r = await fetch(`${API}?op=change`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id_record: data.entryIdRuolo, mestiere: data.mestiere.id }),
            })
            const d = await r.json()
            setJoining(false)
            if (d.success) onAffiliationChanged()
            else setJoinError(d.message ?? 'Errore nella conferma')
        } catch (e) {
            setJoining(false)
            setJoinError(e.message)
        }
    }

    const abbandona = async () => {
        setLeaving(true)
        setLeaveError(null)
        try {
            const r = await fetch(`${API}?op=leave`, { method: 'POST' })
            const d = await r.json()
            setLeaving(false)
            if (d.success) onAffiliationChanged()
            else setLeaveError(d.message ?? 'Errore nell\'abbandono')
        } catch (e) {
            setLeaving(false)
            setLeaveError(e.message)
        }
    }

    return (
        <>
            <div className="sm-header">
                <button type="button" className="sm-back" onClick={onBack}>
                    <i className="fas fa-arrow-left" /> Indietro
                </button>
                {data && <h2 className="sm-title">{data.mestiere.nome}</h2>}
            </div>

            {error && (
                <div className="sm-state sm-state--error">
                    <i className="fas fa-exclamation-triangle" />
                    <p>{error}</p>
                </div>
            )}

            {!error && !data && (
                <div className="sm-state">
                    <i className="fas fa-spinner fa-spin" />
                    <p>Caricamento…</p>
                </div>
            )}

            {data && (
                <>
                    {data.vieneConfermatoQui && avanzamento && (
                        <AvanzamentoSection
                            expMestiere={avanzamento.expMestiere}
                            ranghi={avanzamento.ranghi}
                            onChanged={onChanged}
                        />
                    )}

                    {data.gestioneOggetti && (
                        <section className="sm-section">
                            <a href="main.php?page=gestione_oggetti" className="btn btn--secondary">
                                <i className="fas fa-boxes-stacked" /> Gestisci oggetti del mestiere
                            </a>
                        </section>
                    )}

                    {data.ruoli.length > 0 && (
                        <section className="sm-section">
                            <div className="sm-section-title">
                                <i className="fas fa-layer-group" /> Gerarchia
                            </div>
                            <div className="sm-rank-list">
                                {data.ruoli.map((r, i) => (
                                    <div className={`sm-rank-item${r.capo ? ' sm-rank-item--top' : ''}`} key={r.id_ruolo}>
                                        <span className="sm-rank-badge">{i + 1}</span>
                                        <div className="sm-img-box">
                                            {r.immagine
                                                ? <img src={`imgs/mestieri/${r.immagine}`} alt={r.nome_ruolo} />
                                                : <i className="fas fa-medal" />}
                                        </div>
                                        <span className="sm-rank-name">{r.nome_ruolo}</span>
                                        {r.capo ? <i className="fas fa-crown sm-rank-crown" /> : null}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {data.affiliati.length > 0 && (
                        <section className="sm-section">
                            <div className="sm-section-title">
                                <i className="fas fa-users" /> Lavoratori
                            </div>
                            <div className="sm-member-list">
                                {data.affiliati.map(a => (
                                    <div className={`sm-member-item${a.capo ? ' sm-member-item--leader' : ''}`} key={a.personaggio}>
                                        <div className="sm-img-box">
                                            {a.immagine
                                                ? <img src={`imgs/mestieri/${a.immagine}`} alt={a.nome_ruolo} />
                                                : <i className="fas fa-user" />}
                                        </div>
                                        <div className="sm-member-info">
                                            <a href={`main.php?page=scheda&pg=${encodeURIComponent(a.personaggio)}`} className="sm-member-name">
                                                {a.personaggio} {a.cognome}
                                            </a>
                                            <span className="sm-member-role">{a.nome_ruolo}</span>
                                        </div>
                                        {a.capo ? <i className="fas fa-crown sm-member-crown" /> : null}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {data.statuto && (
                        <section className="sm-section">
                            <div className="sm-section-title">
                                <i className="fas fa-scroll" /> Statuto
                            </div>
                            <div className="sm-statute" dangerouslySetInnerHTML={{ __html: data.statuto }} />
                        </section>
                    )}

                    <section className="sm-section">
                        {joinError && <p className="sm-error-text">{joinError}</p>}
                        {data.vieneConfermatoQui ? (
                            <>
                                <p className="sm-field-note" style={{ marginBottom: 12 }}>Fai già parte di questo mestiere.</p>
                                {leaveError && <p className="sm-error-text">{leaveError}</p>}
                                {!confirmLeaveOpen ? (
                                    <button type="button" className="btn btn--danger-ghost" onClick={() => setConfirmLeaveOpen(true)}>
                                        <i className="fas fa-door-open" /> Abbandona Mestiere
                                    </button>
                                ) : (
                                    <ConfirmDanger
                                        titolo="Sei sicuro di voler abbandonare questo mestiere?"
                                        confermaLabel="Confermo, abbandono"
                                        busy={leaving}
                                        onConfirm={abbandona}
                                        onCancel={() => setConfirmLeaveOpen(false)}
                                    >
                                        <li>Tornerai ad essere <strong>senza mestiere</strong></li>
                                        <li>Perderai tutti i <strong>punti mestiere</strong> accumulati</li>
                                        <li>Il tuo posto nella <strong>gerarchia</strong> verrà liberato</li>
                                    </ConfirmDanger>
                                )}
                            </>
                        ) : data.giaConfermatoAltrove ? (
                            <p className="sm-field-note">Sei già impiegato in un altro mestiere: abbandonalo per poterne scegliere uno nuovo.</p>
                        ) : (
                            <button type="button" className="btn btn--primary" onClick={entra} disabled={joining || !data.entryIdRuolo}>
                                <i className="fas fa-check-circle" /> {joining ? 'Conferma in corso…' : 'Entra nel mestiere'}
                            </button>
                        )}
                    </section>
                </>
            )}
        </>
    )
}

// ── Componente principale ─────────────────────────────────────────────────

export default function ScegliMestiere() {
    const [state, setState] = useState(null) // risposta di op=getState
    const [error, setError] = useState(null)
    const [idMestiere, setIdMestiere] = useState(leggiIdMestiere)

    const fetchState = useCallback(() => {
        setError(null)
        fetch(`${API}?op=getState`)
            .then(r => r.json())
            .then(d => { if (d.success) setState(d); else setError(d.message ?? 'Errore sconosciuto') })
            .catch(e => setError(e.message))
    }, [])

    useEffect(() => { fetchState() }, [fetchState])

    // Torna/avanti del browser sull'URL con ?id_mestiere=X (impostato da
    // openDetail sotto) — la pagina rimane la stessa (page=scegli_mestiere),
    // AppRouter non tiene traccia di questo parametro quindi lo si legge
    // direttamente qui, stesso pattern di MapClick.jsx per ?map_id=X.
    useEffect(() => {
        const onPop = () => setIdMestiere(leggiIdMestiere())
        window.addEventListener('popstate', onPop)
        return () => window.removeEventListener('popstate', onPop)
    }, [])

    const openDetail = (id) => {
        window.history.pushState({}, '', `main.php?page=scegli_mestiere&id_mestiere=${id}`)
        setIdMestiere(id)
    }
    const closeDetail = () => {
        window.history.pushState({}, '', 'main.php?page=scegli_mestiere')
        setIdMestiere(null)
    }
    // Nome neutro: chiude il dettaglio e ricarica lo stato sia dopo un
    // "Entra" sia dopo un "Abbandona" (op=change / op=leave in MestiereDetail).
    const onAffiliationChanged = () => {
        closeDetail()
        fetchState()
    }

    if (error) return (
        <div className="sm-page">
            <div className="sm-state sm-state--error">
                <i className="fas fa-exclamation-triangle" />
                <p>{error}</p>
            </div>
        </div>
    )

    if (state === null) return (
        <div className="sm-page">
            <div className="sm-state">
                <i className="fas fa-spinner fa-spin" />
                <p>Caricamento…</p>
            </div>
        </div>
    )

    return (
        <div className="sm-page">
            {idMestiere === null ? (
                <>
                    <div className="sm-header sm-header--list">
                        <button type="button" className="sm-back" onClick={() => navigate('main.php?page=uffici')}>
                            <i className="fas fa-arrow-left" /> Uffici
                        </button>
                        <h2 className="sm-title">
                            <i className="fas fa-briefcase" /> Scegli mestiere
                        </h2>
                    </div>
                    <MestiereList onOpen={openDetail} />
                </>
            ) : (
                <MestiereDetail
                    id={idMestiere}
                    avanzamento={state.idMestiere === idMestiere ? { expMestiere: state.expMestiere, ranghi: state.mestieri } : null}
                    onBack={closeDetail}
                    onAffiliationChanged={onAffiliationChanged}
                    onChanged={fetchState}
                />
            )}
        </div>
    )
}
