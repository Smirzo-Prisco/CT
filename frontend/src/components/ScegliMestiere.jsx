/**
 * ScegliMestiere.jsx — Selezione e avanzamento del mestiere del personaggio.
 *
 * Step 2 (senza conferma): elenco dei mestieri di livello 3 con pulsante
 *   "Cambia" e, se il PG ha >= 10 esperienza e il mestiere è già selezionato,
 *   pulsante "Conferma Mestiere".
 *
 * Step 3 (con conferma): elenco dei livelli inferiori del mestiere corrente.
 *   Mostra "Cambia" solo se il PG ha abbastanza esperienza mestiere.
 *
 * API: api_mestiere.php
 * Stili: /themes/crystal/famiglie.css (iniettato da AppRouter).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function ScegliMestiere() {
    const [state, setState]       = useState(null)
    const [loading, setLoading]   = useState(true)
    const [fetchError, setFetchError] = useState(null)
    const [msg, setMsg]           = useState(null)   // { type: 'ok'|'err', text }

    const fetchState = useCallback(() => {
        setLoading(true)
        setMsg(null)
        setFetchError(null)
        fetch('pages/api_mestiere.php?op=getState')
            .then(r => r.json())
            .then(d => {
                if (d.success) setState(d)
                else setFetchError(d.message ?? 'Errore sconosciuto')
                setLoading(false)
            })
            .catch(err => { setFetchError(err.message); setLoading(false) })
    }, [])

    useEffect(() => { fetchState() }, [fetchState])

    /** Esegue un'operazione POST e ricarica lo stato. */
    const doAction = async (op, payload) => {
        setMsg(null)
        const res  = await fetch(`pages/api_mestiere.php?op=${op}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        })
        const data = await res.json()
        setMsg({ type: data.success ? 'ok' : 'err', text: data.message })
        if (data.success) fetchState()
    }

    if (loading) return <p><i className="fas fa-spinner fa-spin"></i> Caricamento…</p>
    if (fetchError) return <p className="error">Errore: {fetchError}</p>

    const { step, esperienza, expMestiere, mestieri, hasConferma } = state

    return (
        <div>
            {/* Intestazione esperienza */}
            {step === 2 && esperienza < 10 && (
                <div className="error">
                    Non avendo ancora acquisito un minimo di 10 punti esperienza:
                    <ul>
                        <li>Puoi cambiare mestiere quando lo riterrai opportuno, purché si segua la coerenza ON.</li>
                        <li><b>Non</b> sei abilitato a leggere le bacheche ON e OFF del mestiere.</li>
                        <li><b>Non</b> compari nella lista dei membri del mestiere.</li>
                        <li>A 10 px potrai confermare l'appartenenza.</li>
                    </ul>
                </div>
            )}
            {step === 2 && esperienza >= 10 && (
                <div className="error">Hai {expMestiere} punti mestiere</div>
            )}
            {step === 3 && expMestiere > 55 && (
                <div className="error">Hai raggiunto il massimo dei punti mestiere.</div>
            )}
            {step === 3 && expMestiere <= 55 && (
                <div className="error">Hai {expMestiere} punti mestiere</div>
            )}

            {msg && (
                <div className={msg.type === 'ok' ? 'warning' : 'error'}>{msg.text}</div>
            )}

            <table className="customTable">
                <thead>
                    <tr>
                        <th colSpan="3" style={{ backgroundColor: '#181c31', fontSize: '13px', color: '#9a6353', fontFamily: 'DejaVu Serif' }}>
                            ELENCO RUOLI MESTIERI<br />
                            {step === 2 ? 'Una volta confermato non sarà più possibile cambiarlo' : 'Una volta confermato non sarà più possibile cambiarlo'}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr className="second_header">
                        <td></td>
                        <td>NOME</td>
                        <td></td>
                    </tr>

                    {mestieri.map(m => (
                        <tr key={m.id}>
                            <td>
                                <img src={`imgs/mestieri/${m.immagine}`} width="25" height="25" alt={m.nome} />
                            </td>
                            <td style={{ color: '#8f8f8f' }}>{m.nome}</td>
                            <td style={{ color: '#8f8f8f' }}>
                                {step === 2 && (
                                    <>
                                        {/* Pulsante Conferma — solo se questo mestiere è selezionato e si ha >= 10 exp */}
                                        {m.selected && esperienza >= 10 && (
                                            <button
                                                onClick={() => {
                                                    if (!window.confirm('Confermare il mestiere? Non sarà più possibile cambiarlo.')) return
                                                    doAction('pick', { mestiere: m.mestiere })
                                                }}
                                            >
                                                Conferma Mestiere
                                            </button>
                                        )}
                                        {m.selected && esperienza < 10 && (
                                            <span>Puoi confermare a 10 px</span>
                                        )}
                                        {/* Pulsante Cambia — sempre disponibile se non è quello attuale */}
                                        {!m.selected && (
                                            <button onClick={() => doAction('change', { id_record: m.id, mestiere: m.mestiere })}>
                                                Cambia
                                            </button>
                                        )}
                                    </>
                                )}

                                {step === 3 && (
                                    m.unlocked
                                        ? <button onClick={() => doAction('levelUp', { id_record: m.id, mestiere: m.mestiere })}>Cambia</button>
                                        : <span>Non hai ancora abbastanza esperienza</span>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="panels_link" style={{ marginTop: '16px' }}>
                <span style={{ cursor: 'pointer' }} onClick={() => navigate('main.php?page=uffici')}>
                    Torna indietro
                </span>
            </div>
        </div>
    )
}
