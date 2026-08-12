/**
 * ScegliMestiere.jsx — Selezione e avanzamento del mestiere del personaggio.
 *
 * Step 2 (nessun mestiere confermato): stesso linguaggio visivo/meccanismo di
 * servizi_mestieri.inc.php (classi .sm-*, mai duplicate qui — vedi
 * _servizi_mestieri.scss, gia' riusato anche da MiaGilda.jsx) — elenco
 * mestieri con contatore affiliati, click apre il dettaglio (gerarchia +
 * lavoratori + statuto). Unica differenza da servizi_mestieri: qui compare
 * anche il pulsante "Entra nel mestiere", assente li' perche' quella pagina
 * e' solo consultiva. Vedi conversazione di progetto del 2026-08-12.
 *
 * Step 3 (mestiere gia' confermato): invariato, avanzamento di livello.
 *
 * API: pages/api_mestiere.php
 *   op=getState  GET  → { step, esperienza, expMestiere, hasConferma, mestieri (solo step 3) }
 *   op=list      GET  → { sezione, mestieri: [{id, nome, n}] }               — step 2, elenco
 *   op=detail    GET  → { mestiere, ruoli, affiliati, statuto, entryIdRuolo } — step 2, dettaglio
 *   op=change    POST { id_record, mestiere } → sceglie e conferma il mestiere (step 2 -> 3)
 *   op=levelUp   POST { id_record, mestiere } → avanza livello (step 3)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

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

// ── STEP 2 — elenco mestieri ─────────────────────────────────────────────────

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
                            {m.url_sito && (
                                <a href={`${m.url_sito}?id2=${m.id}`} target="_blank" rel="noreferrer"
                                   className="sm-list-statute" title="Statuto">
                                    <i className="fas fa-scroll" />
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </section>
    )
}

// ── STEP 2 — dettaglio mestiere (gerarchia + lavoratori + statuto + entra) ──

function MestiereDetail({ id, onBack, onJoined }) {
    const [data, setData]     = useState(null)
    const [error, setError]   = useState(null)
    const [joining, setJoining] = useState(false)
    const [joinError, setJoinError] = useState(null)

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
            if (d.success) onJoined()
            else setJoinError(d.message ?? 'Errore nella conferma')
        } catch (e) {
            setJoining(false)
            setJoinError(e.message)
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
                        <button type="button" className="btn btn--primary" onClick={entra} disabled={joining || !data.entryIdRuolo}>
                            <i className="fas fa-check-circle" /> {joining ? 'Conferma in corso…' : 'Entra nel mestiere'}
                        </button>
                    </section>
                </>
            )}
        </>
    )
}

// ── STEP 3 — avanzamento livello (invariato rispetto a prima) ───────────────

function LivelloCard({ mestiere, onAction }) {
    return (
        <div className="sr-guild-card">
            <div className="sr-guild-img">
                <img src={`imgs/mestieri/${mestiere.immagine}`} alt={mestiere.nome} />
            </div>
            <div className="sr-guild-name">{mestiere.nome}</div>

            {mestiere.unlocked
                ? <button
                    className="sr-btn sr-btn--join"
                    onClick={() => onAction('levelUp', { id_record: mestiere.id, mestiere: mestiere.mestiere })}
                  >
                    <i className="fas fa-arrow-up"></i> Avanza
                  </button>
                : <span className="sr-guild-locked">
                    <i className="fas fa-lock"></i> Esperienza insufficiente
                  </span>
            }

            <button
                className="sr-btn sr-btn--statute"
                onClick={() => navigate(`main.php?page=statuto_main&id2=${mestiere.mestiere}`)}
            >
                <i className="fas fa-book-open"></i> Statuto
            </button>
        </div>
    )
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function ScegliMestiere() {
    const [step, setStep]           = useState(null) // null = non ancora caricato
    const [levelState, setLevelState] = useState(null) // payload getState per lo step 3
    const [error, setError]         = useState(null)
    const [msg, setMsg]             = useState(null)
    const [idMestiere, setIdMestiere] = useState(leggiIdMestiere)

    const fetchStep = useCallback(() => {
        setError(null)
        fetch(`${API}?op=getState`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) { setError(d.message ?? 'Errore sconosciuto'); return }
                setStep(d.step)
                if (d.step === 3) setLevelState(d)
            })
            .catch(e => setError(e.message))
    }, [])

    useEffect(() => { fetchStep() }, [fetchStep])

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
    const onJoined = () => {
        closeDetail()
        fetchStep()
    }

    const doLevelAction = async (op, payload) => {
        setMsg(null)
        try {
            const res  = await fetch(`${API}?op=${op}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            })
            const data = await res.json()
            setMsg({ type: data.success ? 'ok' : 'err', text: data.message })
            if (data.success) fetchStep()
        } catch (e) {
            setMsg({ type: 'err', text: e.message })
        }
    }

    if (error) return (
        <div className="sm-page">
            <div className="sm-state sm-state--error">
                <i className="fas fa-exclamation-triangle" />
                <p>{error}</p>
            </div>
        </div>
    )

    if (step === null) return (
        <div className="sm-page">
            <div className="sm-state">
                <i className="fas fa-spinner fa-spin" />
                <p>Caricamento…</p>
            </div>
        </div>
    )

    // ── STEP 2: scelta mestiere — stesso linguaggio visivo di servizi_mestieri.inc.php ──
    if (step === 2) {
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
                    <MestiereDetail id={idMestiere} onBack={closeDetail} onJoined={onJoined} />
                )}
            </div>
        )
    }

    // ── STEP 3: avanzamento livello (invariato) ──────────────────────────
    const { expMestiere, mestieri } = levelState
    return (
        <div id="scegli-mestiere-app">

            <header className="sr-header">
                <button className="sr-back" onClick={() => navigate('main.php?page=uffici')}>
                    <i className="fas fa-arrow-left"></i> Uffici
                </button>
                <div className="sr-title">
                    <h1><i className="fas fa-briefcase"></i> Scegli Mestiere</h1>
                    <p className="sr-subtitle">Avanza nel tuo mestiere</p>
                </div>
            </header>

            <div className="sr-msg sr-msg--ok">
                <i className="fas fa-star"></i>
                {expMestiere > 55
                    ? 'Hai raggiunto il massimo dei punti mestiere.'
                    : `Hai ${expMestiere} punti mestiere`}
            </div>

            {msg && (
                <div className={`sr-msg sr-msg--${msg.type}`}>
                    <i className={`fas fa-${msg.type === 'ok' ? 'check-circle' : 'exclamation-circle'}`}></i>
                    {msg.text}
                </div>
            )}

            <section className="sr-section">
                <h2 className="sr-section-title">
                    <i className="fas fa-list"></i> Livelli disponibili
                </h2>
                <div className="sr-grid">
                    {mestieri.map(m => (
                        <LivelloCard key={m.id} mestiere={m} onAction={doLevelAction} />
                    ))}
                </div>
            </section>

        </div>
    )
}
