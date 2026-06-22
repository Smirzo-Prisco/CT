/**
 * ScegliMestiere.jsx — Selezione e avanzamento del mestiere del personaggio.
 *
 * Step 2: elenco mestieri di base — cambia liberamente, conferma a 10 px.
 * Step 3: elenco livelli del mestiere corrente — avanza se hai esperienza sufficiente.
 *
 * API: pages/api_mestiere.php
 *   op=getState  GET              → { step, esperienza, expMestiere, mestieri, hasConferma }
 *   op=change    POST { id_record, mestiere } → cambia mestiere (step 2)
 *   op=pick      POST { mestiere }            → conferma mestiere (step 2)
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

function MesteireCard({ mestiere, step, esperienza, onAction }) {
    const isSelected = mestiere.selected

    const handlePick = () => {
        if (!window.confirm('Confermare il mestiere? Non sarà più possibile cambiarlo.')) return
        onAction('pick', { mestiere: mestiere.mestiere })
    }

    return (
        <div className={`sr-guild-card${isSelected ? ' sr-guild-card--selected' : ''}`}>
            <div className="sr-guild-img">
                <img src={`imgs/mestieri/${mestiere.immagine}`} alt={mestiere.nome} />
            </div>
            <div className="sr-guild-name">{mestiere.nome}</div>

            {/* Indicatore mestiere attuale (step 2) */}
            {step === 2 && isSelected && (
                <div className="sr-guild-role">
                    <i className="fas fa-check"></i> Mestiere attuale
                </div>
            )}

            {/* Azioni step 2 */}
            {step === 2 && isSelected && esperienza >= 10 && (
                <button className="sr-btn sr-btn--join" onClick={handlePick}>
                    <i className="fas fa-check-circle"></i> Conferma
                </button>
            )}
            {step === 2 && isSelected && esperienza < 10 && (
                <span className="sr-guild-locked">
                    <i className="fas fa-clock"></i> Conferma a 10 px
                </span>
            )}
            {step === 2 && !isSelected && (
                <button
                    className="sr-btn sr-btn--cancel"
                    onClick={() => onAction('change', { id_record: mestiere.id, mestiere: mestiere.mestiere })}
                >
                    <i className="fas fa-exchange-alt"></i> Cambia
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

    const { step, esperienza, expMestiere, mestieri } = state

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

            {/* Info esperienza */}
            {step === 2 && esperienza < 10 && (
                <div className="sr-msg sr-msg--warn">
                    <i className="fas fa-info-circle"></i>
                    Non hai ancora 10 px: non sei abilitato alle bacheche né alla lista membri. A 10 px potrai confermare il mestiere.
                </div>
            )}
            {step === 2 && esperienza >= 10 && (
                <div className="sr-msg sr-msg--ok">
                    <i className="fas fa-star"></i>
                    Hai {expMestiere} punti mestiere
                </div>
            )}
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
                            esperienza={esperienza}
                            onAction={doAction}
                        />
                    ))}
                </div>
            </section>

        </div>
    )
}
