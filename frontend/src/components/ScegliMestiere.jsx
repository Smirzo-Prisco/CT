/**
 * ScegliMestiere.jsx — Selezione e avanzamento del mestiere del personaggio.
 *
 * Step 2: elenco mestieri di base — un click conferma subito (irreversibile,
 * vedi window.confirm in MesteireCard). Non c'e' piu' una fase di scelta
 * provvisoria: prima richiedeva un secondo passaggio ("Conferma") sbloccato
 * a 10 px esperienza, che lasciava per un tempo indefinito un'affiliazione
 * non confermata — invisibile alle liste staff filtrate sulle sole
 * affiliazioni confermate ma comunque conteggiata nel limite, bloccando
 * nuove assunzioni senza modo di vederla per liberarla lato staff. Vedi
 * conversazione di progetto del 2026-08-08.
 * Step 3: elenco livelli del mestiere corrente — avanza se hai esperienza sufficiente.
 *
 * API: pages/api_mestiere.php
 *   op=getState  GET              → { step, esperienza, expMestiere, mestieri, hasConferma }
 *   op=change    POST { id_record, mestiere } → sceglie e conferma il mestiere (step 2 -> 3)
 *   op=levelUp   POST { id_record, mestiere } → avanza livello (step 3)
 *
 * Stili: _scegli_razza.scss (classi sr- condivise, scoped su #scegli-mestiere-app)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Card singolo mestiere ─────────────────────────────────────────────────────

function MesteireCard({ mestiere, step, onAction }) {
    // Un solo click sceglie E conferma (nessuna fase provvisoria, vedi
    // commento in cima al file): stesso avviso "irreversibile" di prima,
    // ora davanti alla scelta stessa invece che a un secondo passaggio
    // sbloccato a 10 px.
    const handleChoose = () => {
        if (!window.confirm('Confermare il mestiere? Non sarà più possibile cambiarlo.')) return
        onAction('change', { id_record: mestiere.id, mestiere: mestiere.mestiere })
    }

    return (
        <div className="sr-guild-card">
            <div className="sr-guild-img">
                <img src={`imgs/mestieri/${mestiere.immagine}`} alt={mestiere.nome} />
            </div>
            <div className="sr-guild-name">{mestiere.nome}</div>

            {/* Azioni step 2 */}
            {step === 2 && (
                <button className="sr-btn sr-btn--join" onClick={handleChoose}>
                    <i className="fas fa-check-circle"></i> Scegli questo mestiere
                </button>
            )}

            {/* Azioni step 3 */}
            {step === 3 && (
                mestiere.unlocked
                    ? <button
                        className="sr-btn sr-btn--join"
                        onClick={() => onAction('levelUp', { id_record: mestiere.id, mestiere: mestiere.mestiere })}
                      >
                        <i className="fas fa-arrow-up"></i> Avanza
                      </button>
                    : <span className="sr-guild-locked">
                        <i className="fas fa-lock"></i> Esperienza insufficiente
                      </span>
            )}

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
    const [state, setState]     = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)
    const [msg, setMsg]         = useState(null)

    const fetchState = useCallback(() => {
        setLoading(true)
        setMsg(null)
        setError(null)
        fetch('pages/api_mestiere.php?op=getState')
            .then(r => r.json())
            .then(d => {
                if (d.success) setState(d)
                else setError(d.message ?? 'Errore sconosciuto')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    useEffect(() => { fetchState() }, [fetchState])

    const doAction = async (op, payload) => {
        setMsg(null)
        try {
            const res  = await fetch(`pages/api_mestiere.php?op=${op}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            })
            const data = await res.json()
            setMsg({ type: data.success ? 'ok' : 'err', text: data.message })
            if (data.success) fetchState()
        } catch (e) {
            setMsg({ type: 'err', text: e.message })
        }
    }

    if (loading) return (
        <div id="scegli-mestiere-app">
            <div className="sr-state">
                <i className="fas fa-spinner fa-spin"></i>
                <p>Caricamento…</p>
            </div>
        </div>
    )

    if (error) return (
        <div id="scegli-mestiere-app">
            <div className="sr-state sr-state--error">
                <i className="fas fa-exclamation-triangle"></i>
                <p>{error}</p>
            </div>
        </div>
    )

    const { step, expMestiere, mestieri } = state

    return (
        <div id="scegli-mestiere-app">

            <header className="sr-header">
                <button className="sr-back" onClick={() => navigate('main.php?page=uffici')}>
                    <i className="fas fa-arrow-left"></i> Uffici
                </button>
                <div className="sr-title">
                    <h1><i className="fas fa-briefcase"></i> Scegli Mestiere</h1>
                    <p className="sr-subtitle">
                        {step === 2 ? 'Seleziona il tuo mestiere' : 'Avanza nel tuo mestiere'}
                    </p>
                </div>
            </header>

            {step === 3 && (
                <div className="sr-msg sr-msg--ok">
                    <i className="fas fa-star"></i>
                    {expMestiere > 55
                        ? 'Hai raggiunto il massimo dei punti mestiere.'
                        : `Hai ${expMestiere} punti mestiere`}
                </div>
            )}

            {msg && (
                <div className={`sr-msg sr-msg--${msg.type}`}>
                    <i className={`fas fa-${msg.type === 'ok' ? 'check-circle' : 'exclamation-circle'}`}></i>
                    {msg.text}
                </div>
            )}

            <section className="sr-section">
                <h2 className="sr-section-title">
                    <i className="fas fa-list"></i>
                    {step === 2 ? 'Mestieri disponibili' : 'Livelli disponibili'}
                </h2>
                <div className="sr-grid">
                    {mestieri.map(m => (
                        <MesteireCard
                            key={m.id}
                            mestiere={m}
                            step={step}
                            onAction={doAction}
                        />
                    ))}
                </div>
            </section>

        </div>
    )
}
