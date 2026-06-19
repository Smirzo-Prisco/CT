/**
 * ElencoStaff.jsx — Elenco dei personaggi staff divisi per ruolo.
 *
 * Recupera i dati da api_staff.php?op=getStaff e mostra quattro sezioni:
 * Coordinatori, Master, Moderatori, Guide. Ogni nome è cliccabile e apre
 * la scheda del personaggio via SPA.
 *
 * Stili: /themes/crystal/famiglie.css (customTable, second_header).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function ElencoStaff() {
    const [staff, setStaff]     = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)

    useEffect(() => {
        fetch('pages/api_staff.php?op=getStaff')
            .then(r => r.json())
            .then(d => {
                if (d.success) setStaff(d.staff)
                else setError(d.message ?? 'Errore')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    if (loading) return <p><i className="fas fa-spinner fa-spin"></i> Caricamento…</p>
    if (error)   return <p className="error">{error}</p>

    const sections = [
        { key: 'coordinatori', label: 'COORDINATORI' },
        { key: 'master',       label: 'MASTER' },
        { key: 'moderatori',   label: 'MODERATORI' },
        { key: 'guide',        label: 'GUIDE' },
    ]

    return (
        <div className="elenco-staff-page">
            <table className="customTable">
                <tbody>
                    {sections.map(({ key, label }) => (
                        <>
                            <tr className="second_header" key={`hdr-${key}`}>
                                <td>{label}</td>
                            </tr>
                            <tr key={`row-${key}`}>
                                <td>
                                    {staff[key].length === 0
                                        ? <span>—</span>
                                        : staff[key].map((nome) => (
                                            <span key={nome}>
                                                <a
                                                    href={`main.php?page=scheda&pg=${encodeURIComponent(nome)}`}
                                                    onClick={e => { e.preventDefault(); navigate(`main.php?page=scheda&pg=${encodeURIComponent(nome)}`) }}
                                                >
                                                    {nome}
                                                </a>
                                                <br />
                                            </span>
                                        ))
                                    }
                                </td>
                            </tr>
                        </>
                    ))}
                </tbody>
            </table>
            <br /><br />
            <div className="panels_link">
                <a
                    href="main.php?page=uffici"
                    onClick={e => { e.preventDefault(); navigate('main.php?page=uffici') }}
                >
                    Torna indietro
                </a>
            </div>
        </div>
    )
}
