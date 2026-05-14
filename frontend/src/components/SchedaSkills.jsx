/**
 * SchedaSkills.jsx — Abilità del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile  → dati pg + flag permessi per il menu
 *   api_scheda.php?op=skills   → abilità raggruppate per tipo
 *
 * Accesso ristretto: solo proprio pg, admin o master (403 dall'API).
 * Le skill sono mostrate raggruppate per tipo con nome, grado e descrizione.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'
import SchedaMenu from './SchedaMenu'

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

/**
 * Singola skill: nome, grado opzionale, descrizione collassabile.
 */
function SkillRow({ skill }) {
    const [open, setOpen] = useState(false)
    return (
        <div className="skill-row">
            <div className="skill-header" onClick={() => skill.descrizione && setOpen(o => !o)}
                style={{ cursor: skill.descrizione ? 'pointer' : 'default' }}>
                <span className="skill-nome">{skill.nome}</span>
                {skill.grado > 1 && <span className="skill-grado"> — Grado {skill.grado}</span>}
                {skill.usi    !== null && skill.usi !== undefined &&
                    <span className="skill-usi"> [{skill.usi}]</span>}
                {skill.descrizione && <span className="skill-toggle">{open ? ' ▲' : ' ▼'}</span>}
            </div>
            {open && skill.descrizione && (
                <div className="skill-desc"
                    dangerouslySetInnerHTML={{ __html: skill.descrizione }} />
            )}
        </div>
    )
}

/**
 * Gruppo di skill per tipo (es. "Difensiva", "Talento", ecc.)
 */
function SkillGroup({ tipo, lista }) {
    return (
        <div className="skill-group">
            <div className="titolo_box">{tipo}</div>
            {lista.map(skill => <SkillRow key={skill.id} skill={skill} />)}
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaSkills() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile, setProfile] = useState(null)
    const [skills,  setSkills]  = useState(null)
    const [error,   setError]   = useState(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            fetch(`/pages/api_scheda.php?op=skills&pg=${enc}`).then(r => {
                if (r.status === 403) throw new Error('Sezione riservata')
                return r.json()
            }),
        ]).then(([prof, sk]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (sk.success)   setSkills(sk.skills)
            else              setError(sk.message  ?? 'Errore abilità')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    if (error)            return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !skills) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const gruppi = Object.entries(skills) // [[tipo, [skill…]], …]

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Abilità</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                <div className="page_scheda_skills">
                    {gruppi.length === 0
                        ? <p>Nessuna abilità registrata.</p>
                        : gruppi.map(([tipo, lista]) => (
                            <SkillGroup key={tipo} tipo={tipo} lista={lista} />
                        ))
                    }
                </div>
            </div>
        </div>
    )
}
