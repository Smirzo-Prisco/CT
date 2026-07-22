/**
 * ElencoVolti.jsx — Tabella dei personaggi con volto assegnato.
 *
 * Recupera i dati da api_volti.php?op=getVolti. I nomi sono cliccabili
 * e aprono la scheda personaggio. Gli admin vedono il pulsante Cancella
 * che chiama api_volti.php?op=deleteVolto via POST.
 *
 * Stili: /themes/crystal/volti.css (iniettato da AppRouter).
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

export default function ElencoVolti() {
    const [volti, setVolti]     = useState([])
    const [isAdmin, setIsAdmin] = useState(false)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)

    const fetchVolti = useCallback(() => {
        setLoading(true)
        setError(null)
        fetch('pages/api_volti.php?op=getVolti')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    setVolti(d.volti)
                    setIsAdmin(d.isAdmin)
                } else {
                    setError(d.message ?? 'Errore')
                }
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    useEffect(() => { fetchVolti() }, [fetchVolti])

    /** Cancella il volto di un personaggio (admin). */
    const handleDelete = async (nome) => {
        if (!window.confirm(`Rimuovere il volto di ${nome}?`)) return
        await fetch('pages/api_volti.php?op=deleteVolto', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ nome }),
        })
        fetchVolti()
    }

    return (
        <div className="page_body">

            <div className="link_back" style={{ textAlign: 'left' }}>
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>

            <table className="customTable">
                <tbody>
                    <tr className="second_header">
                        <td><div>PATROCINIO VOLTI</div></td>
                    </tr>
                </tbody>
            </table>
            <br /><br />

            {loading && <p><i className="fas fa-spinner fa-spin"></i> Caricamento…</p>}
            {error   && <p className="error">{error}</p>}

            {!loading && !error && (
                <table className="customTable">
                    <thead>
                        <tr><th colSpan={isAdmin ? 3 : 2}>Elenco Volti</th></tr>
                    </thead>
                    <tbody>
                        <tr className="second_header">
                            <td>NOME</td>
                            <td>VOLTO</td>
                            {isAdmin && <td></td>}
                        </tr>

                        {volti.length === 0
                            ? <tr><td colSpan={isAdmin ? 3 : 2}>Nessun volto assegnato</td></tr>
                            : volti.map(pg => (
                                <tr key={pg.nome} className="presente">
                                    <td>
                                        <span
                                            style={{ cursor: 'pointer', textDecoration: 'underline' }}
                                            onClick={() => navigate(`main.php?page=scheda&pg=${encodeURIComponent(pg.nome)}`)}
                                        >
                                            {pg.nome}
                                        </span>
                                    </td>
                                    <td style={{ fontSize: '12px' }}>{pg.volto}</td>
                                    {isAdmin && (
                                        <td style={{ textAlign: 'center', width: '7%' }}>
                                            <button onClick={() => handleDelete(pg.nome)}>
                                                Cancella
                                            </button>
                                        </td>
                                    )}
                                </tr>
                            ))
                        }
                    </tbody>
                </table>
            )}

        </div>
    )
}
