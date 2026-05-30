/**
 * ElencoStaff.jsx — Elenco dei personaggi staff divisi per ruolo.
 *
 * Recupera i dati da api_staff.php?op=getStaff e mostra quattro sezioni:
 * Coordinatori, Master, Moderatori, Guide. Ogni nome è cliccabile e apre
 * la scheda del personaggio via SPA.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Sotto-componenti ──────────────────────────────────────────────────────────

function StaffSection({ title, members }) {
    return (
        <div className="staff-section">
            <div className="staff-section-title">{title}</div>
            {members.length === 0
                ? <div className="staff-empty">—</div>
                : members.map(nome => (
                    <div
                        key={nome}
                        className="staff-member"
                        onClick={() => navigate(`main.php?page=scheda&pg=${encodeURIComponent(nome)}`)}
                    >
                        <i className="fas fa-user"></i>
                        <span>{nome}</span>
                    </div>
                ))
            }
        </div>
    )
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

    return (
        <div id="staff-app">
            <div className="container">

                <header>
                    <h1><i className="fas fa-shield-alt"></i> Staff</h1>
                    <p className="subtitle">Personaggi con ruoli speciali di gestione</p>
                </header>

                {loading && (
                    <div className="no-results">
                        <i className="fas fa-spinner fa-spin"></i>
                        <h3>Caricamento…</h3>
                    </div>
                )}

                {error && (
                    <div className="no-results">
                        <i className="fas fa-exclamation-triangle"></i>
                        <h3>Errore nel caricamento</h3>
                        <p>{error}</p>
                    </div>
                )}

                {staff && (
                    <div className="staff-grid">
                        <StaffSection title="Coordinatori" members={staff.coordinatori} />
                        <StaffSection title="Master"       members={staff.master} />
                        <StaffSection title="Moderatori"   members={staff.moderatori} />
                        <StaffSection title="Guide"        members={staff.guide} />
                    </div>
                )}

                <div className="panels_link" style={{ marginTop: '24px' }}>
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
