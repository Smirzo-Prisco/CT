/**
 * SchedaOggetti.jsx — Inventario del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile     → dati pg + flag permessi per il menu
 *   api_scheda.php?op=oggetti     → categorie con conteggio (vista indice)
 *   api_scheda.php?op=oggetti&what=X → oggetti di una categoria (vista dettaglio)
 *
 * Due viste gestite via stato interno (nessun cambio URL):
 *   Indice    — lista categorie con badge conteggio, cliccabili
 *   Dettaglio — card grid con immagine, descrizione, commenti, azioni
 *
 * Operazioni (solo proprietario):
 *   abbandona — rimuove un oggetto (quantità -1 o DELETE)
 *   in_zaino  — sposta in zaino (posizione=1)
 *   commenta  — nota personale
 *
 * Operazioni per admin/master:
 *   commenta_master — nota master
 *
 * Operazioni per shop keeper (mestiere 3=Magic, 4=Pandora):
 *   commenta_shop — nota negozio
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './SchedaOggetti.module.css'

// ---------------------------------------------------------------------------
// ITEM CARD
// ---------------------------------------------------------------------------

function OggettoCard({ item, isOwn, isAdmin, isMaster, idMestiere, onAction }) {
    const [commento,       setCommento]       = useState(item.commento)
    const [commentoMaster, setCommentoMaster] = useState(item.commento_master)
    const [commentoShop,   setCommentoShop]   = useState(item.commento_shop)

    const act = (sub, extra = {}) => onAction({ sub, id_oggetto: item.id, numero: item.numero, ...extra })

    const canMaster    = isAdmin || isMaster
    const canShop      = isAdmin || idMestiere === 3 || idMestiere === 4
    const isViewerOnly = !isOwn

    return (
        <div className={styles.card}>

            <div className={styles.cardImgWrap}>
                <img src={`/imgs/items/${item.urlimg}`} alt={item.nome} className={styles.cardImg} />
            </div>

            <div className={styles.cardBody}>
                <div className={styles.cardName}>{item.nome}</div>
                <div className={styles.cardCount}>
                    × {item.numero}
                    {item.cariche > 1 && (
                        <span className={styles.cardCharges}> · {item.cariche} cariche</span>
                    )}
                </div>

                {item.descrizione && (
                    <div
                        className={styles.cardDesc}
                        dangerouslySetInnerHTML={{ __html: item.descrizione }}
                    />
                )}

                {/* ── Commento personale ── */}
                {!isOwn && item.commento && (
                    <div className={styles.commentBlock}>
                        <div className={styles.commentLabel}>Commento</div>
                        <span className={styles.commentText}>{item.commento}</span>
                    </div>
                )}
                {isOwn && (
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
                            Salva commento
                        </button>
                    </div>
                )}

                {/* ── Commento master (modificabile solo se admin/master + proprietario) ── */}
                {canMaster && !isViewerOnly && (
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
                            Salva nota master
                        </button>
                    </div>
                )}
                {!canMaster && item.commento_master && (
                    <div className={styles.commentBlock}>
                        <div className={`${styles.commentLabel} ${styles.commentLabelMaster}`}>Nota Master</div>
                        <span dangerouslySetInnerHTML={{ __html: item.commento_master }} />
                    </div>
                )}

                {/* ── Commento shop (modificabile solo da shop keeper su pagine altrui) ── */}
                {canShop && !isOwn && (
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
                            Salva nota shop
                        </button>
                    </div>
                )}
                {!canShop && isOwn && item.commento_shop && (
                    <div className={styles.commentBlock}>
                        <div className={`${styles.commentLabel} ${styles.commentLabelShop}`}>Nota Shop</div>
                        <span dangerouslySetInnerHTML={{ __html: item.commento_shop }} />
                    </div>
                )}

                {/* ── Azioni ── */}
                {isOwn && (
                    <div className={styles.actions}>
                        {item.ubicabile > 0 && (
                            <button
                                type="button"
                                className={styles.actionBtn}
                                onClick={() => act('in_zaino')}
                            >
                                <i className="fa-solid fa-box-archive" /> Zaino
                            </button>
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

    const [profile,     setProfile]     = useState(null)
    const [indexData,   setIndexData]   = useState(null)
    const [detailData,  setDetailData]  = useState(null)
    const [selectedCat, setSelectedCat] = useState(null)
    const [feedback,    setFeedback]    = useState(null)
    const [error,       setError]       = useState(null)

    const loadIndex = useCallback(() => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=oggetti&pg=${enc}`).then(r => r.json())
    }, [pg])

    const loadDetail = useCallback((codTipo) => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=oggetti&pg=${enc}&what=${encodeURIComponent(codTipo)}`).then(r => r.json())
    }, [pg])

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            loadIndex(),
        ]).then(([prof, idx]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (idx.success)  setIndexData(idx)
            else              setError(idx.message  ?? 'Errore inventario')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg, loadIndex])

    const openCategory = useCallback((codTipo) => {
        setSelectedCat(codTipo)
        setFeedback(null)
        loadDetail(codTipo).then(d => {
            if (d.success) setDetailData(d)
            else           setFeedback(d.message ?? 'Errore')
        })
    }, [loadDetail])

    const doAction = useCallback(async (params) => {
        const enc = encodeURIComponent(pg)
        try {
            const resp = await fetch(`/pages/api_scheda.php?op=oggetti_action&pg=${enc}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(params),
            })
            const data = await resp.json()
            if (data.success) {
                setFeedback('Operazione eseguita.')
                if (selectedCat !== null) {
                    loadDetail(selectedCat).then(d => { if (d.success) setDetailData(d) })
                }
                loadIndex().then(d => { if (d.success) setIndexData(d) })
            } else {
                setFeedback(data.message ?? 'Errore')
            }
        } catch {
            setFeedback('Errore di rete')
        }
    }, [pg, selectedCat, loadDetail, loadIndex])

    if (error)                  return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !indexData) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const { categorie, id_mestiere } = indexData

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Inventario</h2></div>

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

                {/* ── Vista indice categorie ─────────────────────────── */}
                {selectedCat === null && (
                    <div className={styles.catList}>
                        {categorie.length === 0 ? (
                            <div className={styles.empty}>Nessun oggetto in inventario.</div>
                        ) : categorie.map(cat => (
                            <button
                                type="button"
                                key={cat.cod_tipo}
                                className={styles.catRow}
                                onClick={() => openCategory(cat.cod_tipo)}
                            >
                                <span className={styles.catName}>{cat.descrizione}</span>
                                <span className={styles.catCount}>{cat.numero}</span>
                                <i className="fa-solid fa-chevron-right" />
                            </button>
                        ))}
                    </div>
                )}

                {/* ── Vista dettaglio categoria ──────────────────────── */}
                {selectedCat !== null && (
                    <>
                        <div className={styles.detailHeader}>
                            <button
                                type="button"
                                className={styles.backBtn}
                                onClick={() => { setSelectedCat(null); setDetailData(null) }}
                            >
                                <i className="fa-solid fa-arrow-left" /> Torna all'indice
                            </button>
                            {detailData && (
                                <span className={styles.catTitle}>{detailData.categoria}</span>
                            )}
                        </div>

                        {detailData ? (
                            <div className={styles.grid}>
                                {detailData.items.length === 0 ? (
                                    <div className={styles.empty}>Nessun oggetto.</div>
                                ) : detailData.items.map(item => (
                                    <OggettoCard
                                        key={item.id}
                                        item={item}
                                        isOwn={is_own}
                                        isAdmin={is_admin}
                                        isMaster={is_master}
                                        idMestiere={id_mestiere}
                                        onAction={doAction}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div>Caricamento…</div>
                        )}
                    </>
                )}

            </div>
        </div>
    )
}
