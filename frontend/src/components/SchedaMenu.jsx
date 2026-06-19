/**
 * SchedaMenu.jsx — Menu di navigazione condiviso tra tutte le sotto-sezioni scheda
 *
 * Usato da: Scheda.jsx, SchedaSub.jsx, SchedaSkills.jsx, SchedaTrans.jsx
 *
 * Props:
 *   pg       {string}  — nome del personaggio (raw, verrà urlencoded)
 *   isOwn    {boolean} — l'utente sta guardando la propria scheda
 *   isAdmin  {boolean}
 *   isStaff  {boolean}
 *   isMaster {boolean}
 *
 * I link puntano a pagine PHP; per quelle migrate a React il click interceptor
 * di main.jsx intercetterà la navigazione e la gestirà via SPA.
 *
 * @author Crystal Tokyo Dev
 */
export default function SchedaMenu({ pg, isOwn, isAdmin, isStaff, isMaster }) {
    const enc = encodeURIComponent(pg)
    return (
        <div className="menu_scheda">
            <ul className="menu">

                {/* ── Personaggio ─────────────────────────────────────── */}
                <li className="menuItem">
                    <a href="#">PERSONAGGIO</a>
                    <ul className="subMenu">
                        <li className="subMenuItem"><a href={`main.php?page=scheda&pg=${enc}`}>Scheda</a></li>
                        <li className="subMenuItem"><a href={`main.php?page=scheda_storia&pg=${enc}`}>Storia</a></li>
                        <li className="subMenuItem"><a href={`main.php?page=scheda_dice&pg=${enc}`}>Dice di sé</a></li>
                        <li className="subMenuItem"><a href={`main.php?page=scheda_affetti&pg=${enc}`}>Affetti</a></li>
                        <li className="subMenuItem"><a href={`main.php?page=scheda_off&pg=${enc}`}>Off</a></li>
                        {/* <li className="subMenuItem"><a href={`main.php?page=scheda_trans&pg=${enc}`}>Transizioni</a></li> */}
                    </ul>
                </li>

                {/* ── Skill & Oggetti ──────────────────────────────────── */}
                <li className="menuItem">
                    <a href="#">SKILL&amp;OGGETTI</a>
                    <ul className="subMenu">
                        {(isOwn || isAdmin || isMaster) && (
                            <li className="subMenuItem"><a href={`main.php?page=scheda_skills&pg=${enc}`}>Abilità</a></li>
                        )}
                        <li className="subMenuItem"><a href={`main.php?page=scheda_equip&pg=${enc}`}>Equipaggiamento</a></li>
                        {(isOwn || isAdmin) && (
                            <li className="subMenuItem"><a href={`main.php?page=scheda_oggetti&pg=${enc}`}>Inventario</a></li>
                        )}
                    </ul>
                </li>

                {/* ── Punti + Modifica (solo proprio pg o admin) ───────── */}
                {(isOwn || isAdmin) && (
                    <>
                        <li className="menuItem">
                            <a href="#">PUNTI</a>
                            <ul className="subMenu">
                                <li className="subMenuItem"><a href={`main.php?page=scheda_px&pg=${enc}`}>Esperienza</a></li>
                                <li className="subMenuItem"><a href={`main.php?page=scheda_px_shin&pg=${enc}`}>Punti Shin</a></li>
                                <li className="subMenuItem"><a href={`main.php?page=scheda_px_mestiere&pg=${enc}`}>Punti Mestiere</a></li>
                            </ul>
                        </li>
                        <li className="menuItem">
                            <a href={`main.php?page=scheda_modifica&pg=${enc}`}>MODIFICA</a>
                        </li>
                    </>
                )}

            </ul>
        </div>
    )
}
