/**
 * Anagrafe.jsx — Lista personaggi filtrata per lettera iniziale.
 *
 * Recupera i personaggi da api_anagrafe.php?op=getByLetter&letter=X.
 * L'alfabeto è mostrato come barra di navigazione; selezionando una lettera
 * si carica la lista corrispondente. I nomi aprono la scheda personaggio.
 * Le icone gilda/mestiere/razza vengono mostrate come immagini.
 *
 * Stili: /themes/crystal/anagrafe.css (iniettato da AppRouter).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

/** Formatta una data nel formato locale italiano. */
function formatDate(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    if (isNaN(d)) return dateStr
    return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function Anagrafe() {
    const [letter, setLetter]         = useState(null)
    const [personaggi, setPersonaggi] = useState([])
    const [loading, setLoading]       = useState(false)
    const [error, setError]           = useState(null)

    const fetchByLetter = useCallback((l) => {
        setLetter(l)
        setLoading(true)
        setError(null)
        fetch(`pages/api_anagrafe.php?op=getByLetter&letter=${encodeURIComponent(l)}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setPersonaggi(d.personaggi)
                else setError(d.message ?? 'Errore')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    return (
        <div className="pagina_servizi_prenotazioni">
            <div className="page_body">

                <table className="customTable">
                    <tbody>
                        <tr className="second_header" style={{ filter: 'drop-shadow(0 0 5px rgba(0,0,0,0.57))' }}>
                            <td><div>ANAGRAFE</div></td>
                        </tr>
                    </tbody>
                </table>
                <br /><br />

                {/* Barra alfabetica */}
                <div className="anagrafe-alphabet">
                    {ALPHABET.map(l => (
                        <span
                            key={l}
                            className={`anagrafe-letter${letter === l ? ' active' : ''}`}
                            onClick={() => fetchByLetter(l)}
                        >
                            [{l}]
                        </span>
                    ))}
                </div>

                {/* Lista risultati */}
                {loading && (
                    <p><i className="fas fa-spinner fa-spin"></i> Caricamento…</p>
                )}

                {error && <p className="error">{error}</p>}

                {!loading && !error && letter && personaggi.length === 0 && (
                    <p>Nessun personaggio trovato per la lettera "{letter}".</p>
                )}

                {!loading && !error && personaggi.length > 0 && (
                    <>
                        <br /><br />
                        <table className="customTable">
                            <thead>
                                <tr><th colSpan="5">LISTA UTENTI</th></tr>
                            </thead>
                            <tbody>
                                <tr className="second_header">
                                    <td>NOME E COGNOME</td>
                                    <td>FAMIGLIA</td>
                                    <td>LAVORO</td>
                                    <td>RAZZA</td>
                                    <td>ULTIMO ACCESSO</td>
                                </tr>
                                {personaggi.map(pg => (
                                    <tr key={pg.nome} className="presente">
                                        <td>
                                            <span
                                                style={{ cursor: 'pointer', textDecoration: 'underline' }}
                                                onClick={() => navigate(`main.php?page=scheda&pg=${encodeURIComponent(pg.nome)}`)}
                                            >
                                                {pg.nome} {pg.cognome}
                                            </span>
                                        </td>
                                        <td>
                                            {pg.gilda.img
                                                ? <img width="25" height="25" src={`imgs/guilds/${pg.gilda.img}`} alt={pg.gilda.nome} title={pg.gilda.nome} />
                                                : '—'
                                            }
                                        </td>
                                        <td>
                                            {pg.mestiere.img
                                                ? <img width="25" height="25" src={`imgs/mestieri/${pg.mestiere.img}`} alt={pg.mestiere.nome} title={pg.mestiere.nome} />
                                                : '—'
                                            }
                                        </td>
                                        <td>
                                            {pg.razza.img
                                                ? <img width="25" height="25" src={`imgs/races/${pg.razza.img}`} alt={pg.razza.nome} title={pg.razza.nome} />
                                                : '—'
                                            }
                                        </td>
                                        <td>{formatDate(pg.ultimoRefresh)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </>
                )}

                <div className="panels_link">
                    <br /><br />
                    <span
                        style={{ cursor: 'pointer' }}
                        onClick={() => navigate('main.php?page=uffici')}
                    >
                        Torna indietro
                    </span>
                </div>

            </div>
        </div>
    )
}
