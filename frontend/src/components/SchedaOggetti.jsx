/**
 * SchedaOggetti.jsx — Vista unificata oggetti del personaggio (Phase SPA)
 *
 * Fetch: api_scheda.php?op=oggetti_all
 *   Proprietario / admin / shop keeper → tutti gli oggetti (posizione >= 0)
 *   Chiunque altro                     → solo portati (posizione > 0)
 *
 * Layout tre sezioni:
 *   Omino    — figura con slot indossati (posizione 2-9)
 *   Zaino    — oggetti portati (posizione > 0), con azioni equip/cedi
 *   Inventario — oggetti depositati (posizione = 0), raggruppati per categoria,
 *                con commenti e azione "metti in zaino"
 *
 * Permessi azioni:
 *   abbandona       → is_own
 *   indossa/riponi  → is_own | is_admin
 *   cedi            → is_own | is_admin
 *   commenta        → is_own (qualsiasi posizione)
 *   commenta_master → is_admin | is_master (qualsiasi posizione)
 *   commenta_shop   → is_admin | mestiere 3/4, solo su pg altrui
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './SchedaOggetti.module.css'

// ---------------------------------------------------------------------------
// MANNEQUIN
// ---------------------------------------------------------------------------

function Mannequin({ worn }) {
    const slot = pos => worn[pos]
        ? <img src={`/imgs/items/${worn[pos].urlimg}`} alt={worn[pos].nome} title={worn[pos].nome} />
        : null

    return (
        <div className={styles.mannequinWrap}>
            <div className="omino_bianco_box">
                <table className="omino_bianco_table">
                    <tbody>
                        <tr>
                            <td>
                                <div className="omino_bianco_neck">{slot(9)}</div>
                                <div className="omino_bianco_armdx">{slot(2)}</div>
                                <div className="omino_bianco_ring">{slot(8)}</div>
                                <div className="omino_bianco_feet">{slot(6)}</div>
                            </td>
                            <td>
                                <img className="omino_bianco_img"
                                    src="/themes/crystal/imgs/scheda/omino.png"
                                    alt="" />
                            </td>
                            <td>
                                <div className="omino_bianco_head">{slot(7)}</div>
                                <div className="omino_bianco_chest">{slot(4)}</div>
                                <div className="omino_bianco_armsx">{slot(3)}</div>
                                <div className="omino_bianco_legs">{slot(5)}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    )
}

// ---------------------------------------------------------------------------
// ITEM CARD
// ---------------------------------------------------------------------------

function ItemCard({ item, worn, isOwn, isAdmin, isMaster, idMestiere, chars, onAction }) {
    const [commento,       setCommento]       = useState(item.commento)
    const [commentoMaster, setCommentoMaster] = useState(item.commento_master)
    const [commentoShop,   setCommentoShop]   = useState(item.commento_shop)
    const [giveTarget,     setGiveTarget]     = useState(chars[0] ?? '')

    const act = (sub, extra = {}) =>
        onAction({ sub, id_oggetto: item.id, numero: item.numero, cariche: item.cariche, ...extra })

    const isInventario = item.posizione === 0
    const isInZaino    = item.posizione === 1
    const isIndossato  = item.posizione > 1

    const canAct    = isOwn || isAdmin
    const canMaster = isAdmin || isMaster
    const canShop   = isAdmin || idMestiere === 3 || idMestiere === 4

    const canWear  = item.ubicabile > 1
    const slotFree = canWear && !worn[item.ubicabile]
    const dxFree   = !worn[2]
    const sxFree   = !worn[3]

    return (
        <div className={styles.card}>

            <div className={styles.cardImgWrap}>
                <img src={`/imgs/items/${item.urlimg}`} alt={item.nome} className={styles.cardImg} />
            </div>

            <div className={styles.cardBody}>

                <div className={styles.cardName}>{item.nome}</div>

                <div className={styles.cardMeta}>
                    <span>× {item.numero}</span>
                    {item.cariche > 1 && (
                        <span className={styles.cardCharges}>· {item.cariche} cariche</span>
                    )}
                    {isIndossato && (
                        <span className={`${styles.cardBadge} ${styles.cardBadgeWorn}`}>Indossato</span>
                    )}
                    {isInZaino && (
                        <span className={styles.cardBadge}>Zaino</span>
                    )}
                </div>

                {item.descrizione && (
                    <div
                        className={styles.cardDesc}
                        dangerouslySetInnerHTML={{ __html: item.descrizione }}
                    />
                )}

                {/* ── Commento personale ────────────────────────────── */}
                {isOwn ? (
                    <div className={styles.commentBlock}>
                        <div className={styles.commentLabel}>Commento personale</div>
                        <textarea
                            className={styles.commentTextarea}
                            value={commento}
                            onChange={e => setCommento(e.target.value)}
                        />
                        <button
                            type="button"
                            className={styles.saveBtn}
                            onClick={() => act('commenta', { commento })}
                        >
                            Salva
                        </button>
                    </div>
                ) : item.commento ? (
                    <div className={styles.commentBlock}>
                        <div className={styles.commentLabel}>Commento</div>
                        <span className={styles.commentText}>{item.commento}</span>
                    </div>
                ) : null}

                {/* ── Commento master ───────────────────────────────── */}
                {canMaster ? (
                    <div className={styles.commentBlock}>
                        <div className={`${styles.commentLabel} ${styles.commentLabelMaster}`}>Nota Master</div>
                        <textarea
                            className={styles.commentTextarea}
                            value={commentoMaster}
                            onChange={e => setCommentoMaster(e.target.value)}
                        />
                        <button
                            type="button"
                            className={styles.saveBtn}
                            onClick={() => act('commenta_master', { commento_master: commentoMaster })}
                        >
                            Salva
                        </button>
                    </div>
                ) : item.commento_master ? (
                    <div className={styles.commentBlock}>
                        <div className={`${styles.commentLabel} ${styles.commentLabelMaster}`}>Nota Master</div>
                        <span
                            className={styles.commentText}
                            dangerouslySetInnerHTML={{ __html: item.commento_master }}
                        />
                    </div>
                ) : null}

                {/* ── Commento shop ─────────────────────────────────── */}
                {canShop && !isOwn ? (
                    <div className={styles.commentBlock}>
                        <div className={`${styles.commentLabel} ${styles.commentLabelShop}`}>Nota Shop</div>
                        <textarea
                            className={styles.commentTextarea}
                            value={commentoShop}
                            onChange={e => setCommentoShop(e.target.value)}
                        />
                        <button
                            type="button"
                            className={styles.saveBtn}
                            onClick={() => act('commenta_shop', { commento_shop: commentoShop })}
                        >
                            Salva
                        </button>
                    </div>
                ) : !canShop && isOwn && item.commento_shop ? (
                    <div className={styles.commentBlock}>
                        <div className={`${styles.commentLabel} ${styles.commentLabelShop}`}>Nota Shop</div>
                        <span
                            className={styles.commentText}
                            dangerouslySetInnerHTML={{ __html: item.commento_shop }}
                        />
                    </div>
                ) : null}

                {/* ── Azioni ────────────────────────────────────────── */}
                {canAct && (
                    <div className={styles.actions}>

                        {/* Da inventario: metti in zaino */}
                        {isOwn && isInventario && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('indossa', { posizione: 1 })}
                            >
                                <i className="fa-solid fa-box-archive" /> Zaino
                            </button>
                        )}

                        {/* Da zaino/indossato: riponi in inventario */}
                        {(isInZaino || isIndossato) && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('riponi')}
                            >
                                <i className="fa-solid fa-warehouse" /> Inventario
                            </button>
                        )}

                        {/* Da zaino: opzioni indossa/impugna */}
                        {isInZaino && canWear && slotFree && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('indossa', { posizione: item.ubicabile })}
                            >
                                Indossa
                            </button>
                        )}
                        {isInZaino && dxFree && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('indossa', { posizione: 2 })}
                            >
                                Impugna DX
                            </button>
                        )}
                        {isInZaino && sxFree && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('indossa', { posizione: 3 })}
                            >
                                Impugna SX
                            </button>
                        )}

                        {/* Da indossato: togli (→ zaino) */}
                        {isIndossato && canWear && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('indossa', { posizione: 1 })}
                            >
                                Togli
                            </button>
                        )}

                        {/* Cedi: solo da zaino/indossato */}
                        {(isInZaino || isIndossato) && chars.length > 0 && (
                            <div className={styles.cediRow}>
                                <select
                                    className={styles.cediSelect}
                                    value={giveTarget}
                                    onChange={e => setGiveTarget(e.target.value)}
                                >
                                    {chars.map(c => <option key={c} value={c}>{c}</option>)}
                                </select>
                                <button
                                    type="button"
                                    className={styles.actionBtn}
                                    onClick={() => act('cedi', { give_item: giveTarget })}
                                >
                                    Cedi
                                </button>
                            </div>
                        )}

                        <button
                            type="button"
                            className={`${styles.actionBtn} ${styles.actionBtnDanger}`}
                            onClick={() => act('abbandona')}
                        >
                            <i className="fa-solid fa-trash" /> Abbandona
                        </button>

                    </div>
                )}

            </div>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaOggetti() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,  setProfile]  = useState(null)
    const [data,     setData]     = useState(null)
    const [feedback, setFeedback] = useState(null)
    const [error,    setError]    = useState(null)

    const loadData = useCallback(() => {
        const enc = encodeURIComponent(pg)
        return Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            fetch(`/pages/api_scheda.php?op=oggetti_all&pg=${enc}`).then(r => r.json()),
        ])
    }, [pg])

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        loadData().then(([prof, d]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (d.success)    setData(d)
            else              setError(d.message ?? 'Errore inventario')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg, loadData])

    const doAction = useCallback(async (params) => {
        const enc = encodeURIComponent(pg)
        try {
            const resp = await fetch(`/pages/api_scheda.php?op=oggetti_unif_action&pg=${enc}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(params),
            })
            const res = await resp.json()
            if (res.success) {
                setFeedback('Operazione eseguita.')
                loadData().then(([prof, d]) => {
                    if (prof.success) setProfile(prof)
                    if (d.success)    setData(d)
                })
            } else {
                setFeedback(res.message ?? 'Errore')
            }
        } catch {
            setFeedback('Errore di rete')
        }
    }, [pg, loadData])

    if (error)             return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !data) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const { worn, items, chars, id_mestiere } = data

    // Partiziona per posizione
    const zaino     = items.filter(i => i.posizione > 0)
    const inventario = items.filter(i => i.posizione === 0)

    // Raggruppa inventario per categoria
    const byCategory = {}
    inventario.forEach(item => {
        const cat = item.categoria || 'Altro'
        if (!byCategory[cat]) byCategory[cat] = []
        byCategory[cat].push(item)
    })
    const categories = Object.keys(byCategory).sort()

    const hasWorn = Object.keys(worn).length > 0

    const cardProps = { worn, isOwn: is_own, isAdmin: is_admin, isMaster: is_master, idMestiere: id_mestiere, chars, onAction: doAction }

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Oggetti</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                {feedback && <div className={styles.feedback}>{feedback}</div>}

                {/* ── Omino ─────────────────────────────────────────── */}
                {(hasWorn || is_own || is_admin) && (
                    <Mannequin worn={worn} />
                )}

                {/* ── Zaino e indossato ─────────────────────────────── */}
                {zaino.length > 0 && (
                    <div className={styles.section}>
                        <div className={styles.sectionHeader}>Zaino e indossato</div>
                        <div className={styles.grid}>
                            {zaino.map(item => (
                                <ItemCard key={item.id} item={item} {...cardProps} />
                            ))}
                        </div>
                    </div>
                )}

                {/* ── Inventario ────────────────────────────────────── */}
                {(is_own || is_admin) && (
                    <div className={styles.section}>
                        <div className={styles.sectionHeader}>Inventario</div>

                        {inventario.length === 0 ? (
                            <div className={styles.empty}>Nessun oggetto in inventario.</div>
                        ) : categories.map(cat => (
                            <div key={cat}>
                                {categories.length > 1 && (
                                    <div className={styles.catHeader}>{cat}</div>
                                )}
                                <div className={styles.grid}>
                                    {byCategory[cat].map(item => (
                                        <ItemCard key={item.id} item={item} {...cardProps} />
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

            </div>
        </div>
    )
}
