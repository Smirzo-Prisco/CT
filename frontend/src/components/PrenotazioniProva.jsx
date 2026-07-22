/**
 * PrenotazioniProva.jsx — Prenotazione stanze private (albergo).
 *
 * Mostra la lista delle stanze private con il loro stato:
 *   - libera    → radio selezionabile per prenotare
 *   - mia       → link "Entra nella stanza"
 *   - occupata  → testo "Occupata"
 *
 * La prenotazione dura 4 ore. Il sistema soldi è stato rimosso dal gioco.
 *
 * API: api_albergo.php
 * Stili: /themes/crystal/albergo.css (iniettato da AppRouter).
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

export default function PrenotazioniProva() {
    const [rooms, setRooms]     = useState([])
    const [loading, setLoading] = useState(true)
    const [selected, setSelected] = useState(null)
    const [msg, setMsg]         = useState(null)   // { type: 'ok'|'err', text }

    const fetchRooms = useCallback(() => {
        setLoading(true)
        setMsg(null)
        fetch('pages/api_albergo.php?op=getRooms')
            .then(r => r.json())
            .then(d => { if (d.success) setRooms(d.rooms); setLoading(false) })
            .catch(() => setLoading(false))
    }, [])

    useEffect(() => { fetchRooms() }, [fetchRooms])

    /** Prenota la stanza selezionata. */
    const handleBook = async (e) => {
        e.preventDefault()
        if (!selected) { setMsg({ type: 'err', text: 'Seleziona una stanza' }); return }
        const res  = await fetch('pages/api_albergo.php?op=book', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: selected }),
        })
        const data = await res.json()
        setMsg({ type: data.success ? 'ok' : 'err', text: data.message })
        if (data.success) { setSelected(null); fetchRooms() }
    }

    return (
        <div className="pagina_servizi_prenotazioni">
            <div className="link_back" style={{ textAlign: 'left' }}>
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>

            <div className="page_title">
                <h2>Prenotazione Stanza</h2>
            </div>

            <div className="page_body">
                <table className="customTable">
                    <tbody>
                        <tr className="second_header" style={{ filter: 'drop-shadow(0 0 5px rgba(0,0,0,0.57))' }}>
                            <td><div>ALBERGO</div></td>
                        </tr>
                    </tbody>
                </table>
                <br /><br />

                <center>
                    Per prenotare una stanza, cliccala e poi clicca su Prenota.<br />
                    Per invitare qualcuno in una stanza, bisogna selezionare INVITA nella barra chat e scrivere il nome dell'invitato.
                </center>
                <br />

                {loading && <p><i className="fas fa-spinner fa-spin"></i> Caricamento…</p>}

                {!loading && (
                    <form onSubmit={handleBook}>
                        <table className="tg_no">
                            <tbody>
                                <tr>
                                    {rooms.map(room => (
                                        <td key={room.id}>
                                            {room.stato === 'mia' && (
                                                <span
                                                    style={{ cursor: 'pointer', textDecoration: 'underline', fontWeight: 'bold' }}
                                                    onClick={() => navigate(`main.php?dir=${room.id}`)}
                                                >
                                                    Entra nella stanza
                                                </span>
                                            )}
                                            {room.stato === 'occupata' && <span>Occupata</span>}
                                            {room.stato === 'libera' && (
                                                <label style={{ cursor: 'pointer' }}>
                                                    <input
                                                        type="radio"
                                                        name="id"
                                                        value={room.id}
                                                        checked={selected === room.id}
                                                        onChange={() => setSelected(room.id)}
                                                    />
                                                    {' '}{room.nome}
                                                </label>
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>

                        <br /><br />
                        <center>
                            Scegli una stanza privata da usare per massimo <b>4 ore</b>.<br />
                            Ricordiamo che le chat private <b>non garantiscono il punto esperienza</b><br />
                            <b>Non è possibile svolgere giocate di trama o di gilda o di scambio di informazioni</b>
                        </center>
                        <br /><br />

                        {msg && (
                            <div className={msg.type === 'ok' ? 'warning' : 'error'}>{msg.text}</div>
                        )}

                        <div className="form_submit">
                            <input type="submit" value="Prenota" style={{ width: '68px' }} />
                        </div>
                    </form>
                )}

            </div>
        </div>
    )
}
