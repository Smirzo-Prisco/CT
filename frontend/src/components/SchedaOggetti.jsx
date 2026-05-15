/**
 * SchedaOggetti.jsx — Inventario del personaggio (Phase SPA)
 *
 * Fetch: api_scheda.php?op=profile + api_scheda.php?op=oggetti
 *
 * Due viste gestite via stato interno (nessun cambio URL):
 *   Vista indice  — tabella con categorie di oggetti posseduti + conteggio
 *   Vista dettaglio — oggetti di una categoria specifica, con commenti
 *                     e pulsanti azione
 *
 * Operazioni (solo proprietario):
 *   abbandona   — rimuove un oggetto
 *   in_zaino    — sposta dall'inventario al backpack (posizione=1)
 *   commenta    — aggiunge/modifica nota personale
 *
 * Operazioni aggiuntive per admin/master:
 *   commenta_master — nota master
 *
 * Operazioni aggiuntive per shop keeper (mestiere 3=Magic, 4=Pandora):
 *   commenta_shop — nota negozio
 *
 * Accesso: solo proprietario, admin, o shop keeper per tipo specifico.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'

// ---------------------------------------------------------------------------
// ITEM DETAIL
// ---------------------------------------------------------------------------

/**
 * Riga singolo oggetto nella vista dettaglio categoria.
 * Mostra immagine, descrizione, commenti, form per aggiungere note, pulsanti.
 */
function OggettoRow({ item, isOwn, isAdmin, isMaster, idMestiere, pg, onAction, onReload }) {
    const [commento,       setCommento]       = useState(item.commento)
    const [commentoMaster, setCommentoMaster] = useState(item.commento_master)
    const [commentoShop,   setCommentoShop]   = useState(item.commento_shop)

    const act = (sub, extra = {}) => onAction({ sub, id_oggetto: item.id, numero: item.numero, ...extra })

    const canMaster    = isAdmin || isMaster
    const canShop      = isAdmin || idMestiere === 3 || idMestiere === 4
    const isMagic      = false   // tipo risolto server-side; lato client non distinguiamo qui
    const isViewerOnly = !isOwn  // può vedere commenti ma non modificarli

    return (
        <tr>
            {/* Immagine */}
            <td className="casella_elemento casella_img_col">
                <img src={`/imgs/items/${item.urlimg}`} align="center"
                    style={{ height: 120, width: 120 }} alt={item.nome} />
            </td>

            {/* Descrizione + commenti */}
            <td className="casella_elemento">
                <div className="inventario_riga_descrizione">
                    <span style={{ textTransform: 'uppercase' }}>
                        <center>
                            <b>{item.nome}</b> (<i>x {item.numero}
                                {item.cariche > 1 && ` Cariche: ${item.cariche}`}
                            </i>)
                        </center>
                    </span>
                    {item.descrizione && (
                        <div dangerouslySetInnerHTML={{ __html: item.descrizione }} />
                    )}
                </div>

                {/* Commento personale */}
                {!isOwn && item.commento && (
                    <div className="inventario_riga_commento">
                        <span style={{ textTransform: 'uppercase' }}><center><b>COMMENTO</b></center></span>
                        <i>{item.commento}</i>
                    </div>
                )}
                {isOwn && (
                    <div>
                        <textarea className="form_textarea" value={commento}
                            onChange={e => setCommento(e.target.value)} />
                        <br />
                        <center>
                            <button onClick={() => act('commenta', { commento })}>
                                Commenta
                            </button>
                        </center>
                    </div>
                )}

                {/* Commento master */}
                {canMaster && !isViewerOnly && (
                    <div>
                        <hr />
                        <textarea className="form_textarea" value={commentoMaster}
                            onChange={e => setCommentoMaster(e.target.value)} />
                        <button onClick={() => act('commenta_master', { commento_master: commentoMaster })}>
                            Commento Master
                        </button>
                    </div>
                )}
                {!canMaster && item.commento_master && (
                    <div>
                        <hr />
                        <span style={{ textTransform: 'uppercase' }}><center><b>COMMENTO MASTER</b></center></span>
                        <span dangerouslySetInnerHTML={{ __html: item.commento_master }} />
                    </div>
                )}

                {/* Commento shop */}
                {canShop && !isOwn && (
                    <div>
                        <hr />
                        <textarea className="form_textarea" value={commentoShop}
                            onChange={e => setCommentoShop(e.target.value)} />
                        <button onClick={() => act('commenta_shop', { commento_shop: commentoShop })}>
                            Commento Shop
                        </button>
                    </div>
                )}
                {!canShop && isOwn && item.commento_shop && (
                    <div>
                        <hr />
                        <span dangerouslySetInnerHTML={{ __html: item.commento_shop }} />
                    </div>
                )}
            </td>

            {/* Pulsanti */}
            <td className="casella_controlli casella_azioni_col">
                {isOwn && (
                    <div className="form_gioco">
                        <button onClick={() => act('abbandona')}>Abbandona</button>
                        {item.ubicabile > 0 && (
                            <button onClick={() => act('in_zaino')}>Equipaggia</button>
                        )}
                    </div>
                )}
            </td>
        </tr>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaOggetti() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,     setProfile]     = useState(null)
    const [indexData,   setIndexData]   = useState(null)   // categorie
    const [detailData,  setDetailData]  = useState(null)   // items per categoria
    const [selectedCat, setSelectedCat] = useState(null)   // cod_tipo selezionato
    const [feedback,    setFeedback]    = useState(null)
    const [error,       setError]       = useState(null)

    // Carica indice categorie
    const loadIndex = useCallback(() => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=oggetti&pg=${enc}`).then(r => r.json())
    }, [pg])

    // Carica items per categoria
    const loadDetail = useCallback((codTipo) => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=oggetti&pg=${enc}&what=${encodeURIComponent(codTipo)}`).then(r => r.json())
    }, [pg])

    // Caricamento iniziale
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

    // Quando l'utente clicca su una categoria
    const openCategory = useCallback((codTipo) => {
        setSelectedCat(codTipo)
        setFeedback(null)
        loadDetail(codTipo).then(d => {
            if (d.success) setDetailData(d)
            else           setFeedback(d.message ?? 'Errore')
        })
    }, [loadDetail])

    // Esegue un'azione e ricarica la vista corrente
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
                // Ricarica dettaglio corrente
                if (selectedCat !== null) {
                    loadDetail(selectedCat).then(d => { if (d.success) setDetailData(d) })
                }
                // Aggiorna anche i conteggi indice
                loadIndex().then(d => { if (d.success) setIndexData(d) })
            } else {
                setFeedback(data.message ?? 'Errore')
            }
        } catch {
            setFeedback('Errore di rete')
        }
    }, [pg, selectedCat, loadDetail, loadIndex])

    if (error)               return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !indexData) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const { categorie, id_mestiere } = indexData

    return (
        <div className="pagina_scheda_oggetti">
            <div className="page_title">
                <h2>Inventario</h2>
            </div>

            <div className="menu_scheda">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />
            </div>

            {feedback && <div className="warning">{feedback}</div>}

            <div className="page_body">

                {/* ── Vista indice ────────────────────────────────────── */}
                {selectedCat === null && (
                    <table className="customTable">
                        <tbody>
                            <tr className="second_header">
                                <td colSpan="3"><div>Tipo</div></td>
                                <td colSpan="3"><div>Numero oggetti</div></td>
                            </tr>
                            {categorie.length === 0
                                ? <tr><td colSpan="6" style={{ textAlign: 'center', padding: 20 }}>
                                    Nessun oggetto in inventario.
                                  </td></tr>
                                : categorie.map(cat => (
                                    <tr key={cat.cod_tipo}>
                                        <td className="casella_elemento" colSpan="3">
                                            <a href="#" onClick={e => { e.preventDefault(); openCategory(cat.cod_tipo) }}>
                                                {cat.descrizione}
                                            </a>
                                        </td>
                                        <td className="casella_elemento" colSpan="3">
                                            {cat.numero}
                                        </td>
                                    </tr>
                                ))
                            }
                        </tbody>
                    </table>
                )}

                {/* ── Vista dettaglio categoria ────────────────────────── */}
                {selectedCat !== null && detailData && (
                    <>
                        <div style={{ marginBottom: 8 }}>
                            <a href="#" onClick={e => { e.preventDefault(); setSelectedCat(null); setDetailData(null) }}>
                                ← Torna all'indice
                            </a>
                        </div>
                        <div className="panels_box">
                            <div className="elenco_record_gioco">
                                <table className="customTable inventory-table">
                                    <thead>
                                        <tr>
                                            <td className="casella_titolo casella_img_col">
                                                <div className="titoli_elenco">Immagine</div>
                                            </td>
                                            <td className="casella_titolo">
                                                <div className="titoli_elenco">Descrizioni e Commenti</div>
                                            </td>
                                            <td className="casella_titolo casella_azioni_col">
                                                <div className="titoli_elenco">&nbsp;</div>
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {detailData.items.length === 0
                                            ? <tr><td colSpan="3" style={{ textAlign: 'center', padding: 20 }}>
                                                Nessun oggetto.
                                              </td></tr>
                                            : detailData.items.map(item => (
                                                <OggettoRow
                                                    key={item.id}
                                                    item={item}
                                                    isOwn={is_own}
                                                    isAdmin={is_admin}
                                                    isMaster={is_master}
                                                    idMestiere={id_mestiere}
                                                    pg={pg}
                                                    onAction={doAction}
                                                />
                                            ))
                                        }
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </div>
    )
}
