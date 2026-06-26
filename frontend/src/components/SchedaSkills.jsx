/**
 * SchedaSkills.jsx — Abilità del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile  → dati pg + flag permessi per il menu
 *   api_scheda.php?op=skills   → abilità raggruppate per tipo DB
 *
 * Visualizzazione: griglia card responsive, raggruppata per categoria.
 * Ogni card mostra nome, barra livello e descrizione espandibile al click.
 *
 * Accesso ristretto: solo proprio pg, admin o master (403 dall'API).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, Fragment } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './SchedaSkills.module.css'

// ---------------------------------------------------------------------------
// MAPPA TIPI DB → CATEGORIE DISPLAY
// ---------------------------------------------------------------------------

const TYPE_MERGE = {
    'Default':          'Default/Difensiva',
    'Difensiva':        'Default/Difensiva',
    'Generica base':    'Generica',
    'Generica avanzata':'Generica',
    'Attacco base':     'Attacco',
    'Attacco medio':    'Attacco',
    'Attacco avanzato': 'Attacco',
    'Mentale base':     'Mentale',
    'Mentale media':    'Mentale',
    'Mentale avanzata': 'Mentale',
    'Mentale di attacco':'Mentale',
    'Potere speciale':  'Potere speciale',
    'Skill temporanea': 'Skill temporanee',
}

const CATEGORY_ORDER = [
    'Default/Difensiva',
    'Generica',
    'Attacco',
    'Mentale',
    'Potere speciale',
    'Skill temporanee',
]

const CAT_COLORS = {
    'Default/Difensiva': '#4a9eff',
    'Generica':          '#4caf50',
    'Attacco':           '#ef5350',
    'Mentale':           '#ab47bc',
    'Potere speciale':   '#ffa726',
    'Skill temporanee':  '#78909c',
}

const DEFAULT_COLOR = '#607d8b'

function mergeByCategory(skillsByTipo) {
    const merged = {}
    for (const [tipo, lista] of Object.entries(skillsByTipo)) {
        if (tipo === 'Talento') continue
        const cat = TYPE_MERGE[tipo] ?? tipo
        if (!merged[cat]) merged[cat] = []
        merged[cat].push(...lista)
    }
    const ordered = []
    for (const cat of CATEGORY_ORDER) {
        if (merged[cat]) ordered.push([cat, merged[cat]])
    }
    for (const [cat, lista] of Object.entries(merged)) {
        if (!CATEGORY_ORDER.includes(cat)) ordered.push([cat, lista])
    }
    return ordered
}

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

function SkillCard({ skill, showLevel, catColor }) {
    const [open, setOpen] = useState(false)
    const hasDesc = !!skill.descrizione
    const pct = showLevel && skill.max_lvl > 0
        ? Math.min(100, (skill.grado / skill.max_lvl) * 100)
        : 0

    return (
        <div
            className={`${styles.card}${hasDesc ? ` ${styles.clickable}` : ''}`}
            style={{ '--cat-color': catColor }}
            onClick={() => hasDesc && setOpen(o => !o)}
        >
            <div className={styles.skillName}>
                <span>{skill.nome}</span>
                {hasDesc && (
                    <span className={`${styles.expandIcon}${open ? ` ${styles.open}` : ''}`}>▾</span>
                )}
            </div>

            {showLevel && (
                <div className={styles.levelBar}>
                    <div className={styles.levelText}>Liv {skill.grado} / {skill.max_lvl}</div>
                    <div className={styles.barTrack}>
                        <div className={styles.barFill} style={{ width: `${pct}%` }} />
                    </div>
                </div>
            )}

            {open && hasDesc && (
                <div className={styles.desc} dangerouslySetInnerHTML={{ __html: skill.descrizione }} />
            )}
        </div>
    )
}

function SkillCategory({ categoria, lista }) {
    const catColor = CAT_COLORS[categoria] ?? DEFAULT_COLOR
    const showLevel = categoria !== 'Talento'

    return (
        <div className={styles.section}>
            <div className={styles.categoryHeader} style={{ '--cat-color': catColor }}>
                <span className={styles.categoryLabel}>{categoria}</span>
            </div>
            <div className={styles.grid}>
                {lista.map(skill => (
                    <SkillCard key={skill.id} skill={skill} showLevel={showLevel} catColor={catColor} />
                ))}
            </div>
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
            else setError(prof.message ?? 'Errore profilo')
            if (sk.success) setSkills(sk.skills)
            else setError(sk.message ?? 'Errore abilità')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    if (error)              return <div className="pagina_scheda"><div className="error">{error}</div></div>
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

                <div style={{ marginTop: '16px' }}>
                    {gruppi.length === 0
                        ? <p style={{ color: '#6b6f8a', fontStyle: 'italic' }}>Nessuna abilità registrata.</p>
                        : gruppi.map(([cat, lista]) => (
                            <SkillCategory key={cat} categoria={cat} lista={lista} />
                        ))
                    }
                </div>
            </div>
        </div>
    )
}
