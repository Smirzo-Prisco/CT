/**
 * SchedaMenu.jsx — Tab bar di navigazione per tutte le sotto-sezioni scheda
 *
 * Sostituisce il vecchio menu a dropdown con una tab bar piatta e responsiva
 * con scroll orizzontale nascosto su mobile.
 *
 * Props:
 *   pg       {string}  — nome del personaggio (raw, verrà urlencoded)
 *   isOwn    {boolean} — l'utente sta guardando la propria scheda
 *   isAdmin  {boolean}
 *   isStaff  {boolean}
 *   isMaster {boolean}
 *
 * @author Crystal Tokyo Dev
 */
import { useMemo } from 'react'
import styles from './SchedaMenu.module.css'

export default function SchedaMenu({ pg, isOwn, isAdmin, isStaff, isMaster }) {
    const enc = encodeURIComponent(pg)

    const activePage = useMemo(() => new URLSearchParams(window.location.search).get('page') ?? '', [])

    const tab = (page, label) => (
        <a
            key={page}
            href={`main.php?page=${page}&pg=${enc}`}
            className={`${styles.tab}${activePage === page ? ` ${styles.active}` : ''}`}
        >
            {label}
        </a>
    )

    return (
        <nav className={styles.nav}>
            <div className={styles.strip}>

                {/* ── Personaggio ────────────────────────────────────── */}
                <div className={styles.group}>
                    {tab('scheda',        'Scheda')}
                    {tab('scheda_storia', 'Storia')}
                    {tab('scheda_dice',   'Dice di sé')}
                    {tab('scheda_off',    'Off')}
                    {tab('scheda_affetti','Affetti')}
                </div>

                {/* ── Skill & Oggetti ─────────────────────────────────── */}
                <div className={styles.group}>
                    {(isOwn || isAdmin || isMaster) && tab('scheda_skills', 'Abilità')}
                    {tab('scheda_oggetti', 'Oggetti')}
                </div>

                {/* ── Punti (solo proprio pg o admin) ─────────────────── */}
                {(isOwn || isAdmin) && (
                    <div className={styles.group}>
                        {tab('scheda_px', 'Punti')}
                    </div>
                )}

                {/* ── Modifica (solo proprio pg o admin) — icona compatta ── */}
                {(isOwn || isAdmin) && (
                    <div className={styles.group}>
                        <a
                            href={`main.php?page=scheda_modifica&pg=${enc}`}
                            className={`${styles.tab}${activePage === 'scheda_modifica' ? ` ${styles.active}` : ''}`}
                            title="Modifica scheda"
                        >
                            <i className="fa-solid fa-pen-to-square" />
                        </a>
                    </div>
                )}

            </div>
        </nav>
    )
}
