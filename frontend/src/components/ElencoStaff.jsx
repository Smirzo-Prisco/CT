/**
 * ElencoStaff.jsx — Elenco dei personaggi staff divisi per ruolo.
 *
 * Recupera i dati da api_staff.php?op=getStaff e mostra quattro sezioni
 * (Coordinatori, Master, Moderatori, Guide) in una card-grid con stile
 * coerente alla landing page: sfondo scuro, accenti oro, font Cinzel.
 *
 * Ogni card mostra un avatar circolare con le iniziali del nome e un link
 * alla scheda del personaggio via SPA.
 */

import { useState, useEffect } from 'react'

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti cambia href. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

/** Restituisce le iniziali (prima lettera di nome + cognome, oppure solo nome). */
function initials(nome) {
    const parts = nome.trim().split(/\s+/)
    if (parts.length === 1) return parts[0][0].toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

// ── Sezioni da renderizzare ──────────────────────────────────────────────────

const SECTIONS = [
    { key: 'coordinatori', label: 'Coordinatori' },
    { key: 'master',       label: 'Master'        },
    { key: 'moderatori',   label: 'Moderatori'    },
    { key: 'guide',        label: 'Guide'          },
]

// ── Componente principale ────────────────────────────────────────────────────

export default function ElencoStaff() {
    const [staff,   setStaff]   = useState(null)
    const [loading, setLoading] = useState(true)
    const [error,   setError]   = useState(null)

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

    if (loading) return <p><i className="fas fa-spinner fa-spin" /> Caricamento…</p>
    if (error)   return <p className="error">{error}</p>

    return (
        <div className="elenco-staff">

            {SECTIONS.map(({ key, label }) => (
                <div key={key}>
                    <div className="elenco-staff__section-title">{label}</div>

                    {staff[key].length === 0
                        ? <p className="elenco-staff__empty">Nessun membro</p>
                        : (
                            <div className="elenco-staff__grid">
                                {staff[key].map(nome => (
                                    <div key={nome} className="elenco-staff__card">
                                        <div className="elenco-staff__avatar">{initials(nome)}</div>
                                        <a
                                            href={`main.php?page=scheda&pg=${encodeURIComponent(nome)}`}
                                            className="elenco-staff__name"
                                            onClick={e => { e.preventDefault(); navigate(`main.php?page=scheda&pg=${encodeURIComponent(nome)}`) }}
                                        >
                                            {nome}
                                        </a>
                                    </div>
                                ))}
                            </div>
                        )
                    }
                </div>
            ))}

            <div className="link_back">
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>
        </div>
    )
}
