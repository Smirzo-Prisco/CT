/**
 * SchedaSkills.jsx — Abilità del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile  → dati pg + flag permessi per il menu
 *   api_scheda.php?op=skills   → abilità raggruppate per tipo DB
 *
 * I tipi DB vengono fusi nelle stesse categorie dell'originale PHP:
 *   Default + Difensiva            → Default/Difensiva
 *   Generica base + avanzata       → Generica
 *   Attacco base/medio/avanzato    → Attacco
 *   Mentale base/media/avanzata/di attacco → Mentale
 *   Potere speciale / Talento / Skill temporanea → label propria
 *
 * Ogni categoria usa <table class="customTable"> con header second_header,
 * identico al layout PHP originale (skillsystem.inc.php).
 * Per il tipo "Talento" il livello non viene mostrato (come nell'originale).
 *
 * Accesso ristretto: solo proprio pg, admin o master (403 dall'API).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, Fragment } from 'react'
import SchedaMenu from './SchedaMenu'
import shared from './shared.module.css'

// ---------------------------------------------------------------------------
// MAPPA TIPI DB → CATEGORIE DISPLAY
// ---------------------------------------------------------------------------

/** Fusione tipi DB → label categoria (come nell'originale PHP) */
const TYPE_MERGE = {
    'Default': 'Default/Difensiva',
    'Difensiva': 'Default/Difensiva',
    'Generica base': 'Generica',
    'Generica avanzata': 'Generica',
    'Attacco base': 'Attacco',
    'Attacco medio': 'Attacco',
    'Attacco avanzato': 'Attacco',
    'Mentale base': 'Mentale',
    'Mentale media': 'Mentale',
    'Mentale avanzata': 'Mentale',
    'Mentale di attacco': 'Mentale',
    'Potere speciale': 'Potere speciale',
    'Talento': 'Talento',
    'Skill temporanea': 'Skill temporanee',
}

/** Ordine canonico delle categorie display */
const CATEGORY_ORDER = [
    'Default/Difensiva',
    'Generica',
    'Attacco',
    'Mentale',
    'Potere speciale',
    'Talento',
    'Skill temporanee',
]

/**
 * Fonde i gruppi per-tipo restituiti dall'API nelle categorie display,
 * rispettando l'ordine canonico e appendendo eventuali tipi extra in fondo.
 *
 * @param {Object} skillsByTipo - { tipo: [skill, …], … }
 * @returns {Array} [[categoria, [skill, …]], …]
 */
function mergeByCategory(skillsByTipo) {
    const merged = {}
    for (const [tipo, lista] of Object.entries(skillsByTipo)) {
        const cat = TYPE_MERGE[tipo] ?? tipo
        if (!merged[cat]) merged[cat] = []
        merged[cat].push(...lista)
    }
    const ordered = []
    for (const cat of CATEGORY_ORDER) {
        if (merged[cat]) ordered.push([cat, merged[cat]])
    }
    // Tipi extra non mappati (fallback)
    for (const [cat, lista] of Object.entries(merged)) {
        if (!CATEGORY_ORDER.includes(cat)) ordered.push([cat, lista])
    }
    return ordered
}

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

/**
 * Singola riga skill nella tabella categoria.
 * Cliccando sul nome si espande/comprime la descrizione (inline, come popup originale).
 * Per le categorie "Talento" showLevel=false: il livello non viene mostrato.
 */
function SkillRow({ skill, showLevel }) {
    const [open, setOpen] = useState(false)
    return (
        <Fragment>
            <tr>
                <td width="40%">
                    {skill.descrizione
                        ? <a href="#" onClick={e => { e.preventDefault(); setOpen(o => !o) }}>{skill.nome}</a>
                        : skill.nome
                    }
                    {showLevel && (
                        <><br /><span className={shared.primaryColor}>Livello attuale: {skill.grado}/{skill.max_lvl}</span></>
                    )}
                </td>
            </tr>
            {open && skill.descrizione && (
                <tr>
                    <td>
                        <div className="skill-desc"
                            dangerouslySetInnerHTML={{ __html: skill.descrizione }} />
                    </td>
                </tr>
            )}
        </Fragment>
    )
}

/**
 * Tabella categoria con header second_header, identica alla struttura PHP originale.
 */
function SkillGroup({ categoria, lista }) {
    const showLevel = categoria !== 'Talento'
    return (
        <table className="customTable">
            <tbody>
                <tr className="second_header">
                    <td colSpan="1" style={{
                        textTransform: 'uppercase',
                        fontSize: 'var(--text-md)',
                        color: 'var(--palette-rust)',
                        fontFamily: 'var(--font-base)',
                        filter: 'drop-shadow(0 0 5px rgba(0,0,0,0.57))',
                    }}>
                        {categoria}
                    </td>
                </tr>
                {lista.map(skill => (
                    <SkillRow key={skill.id} skill={skill} showLevel={showLevel} />
                ))}
            </tbody>
        </table>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaSkills() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile, setProfile] = useState(null)
    const [skills, setSkills] = useState(null)
    const [error, setError] = useState(null)

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
            else setError(prof.message ?? 'Errore profilo')
            if (sk.success) setSkills(sk.skills)
            else setError(sk.message ?? 'Errore abilità')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    if (error) return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !skills) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const gruppi = mergeByCategory(skills)

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
                        : gruppi.map(([cat, lista]) => (
                            <SkillGroup key={cat} categoria={cat} lista={lista} />
                        ))
                    }
                </div>
            </div>
        </div>
    )
}
