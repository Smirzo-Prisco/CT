/**
 * SchedaEquip.jsx — Equipaggiamento e zaino del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile  → dati pg + flag permessi per il menu
 *   api_scheda.php?op=equip    → oggetti indossati (per omino) + zaino
 *
 * La figura "omino" mostra gli oggetti nelle posizioni 2-9 (worn).
 * La tabella zaino mostra tutti gli oggetti a posizione > 0.
 *
 * Operazioni disponibili a proprietario / admin / moderatore:
 *   abbandona  — rimuove l'oggetto (quantità -1 o DELETE)
 *   cedi       — trasferisce a un altro personaggio
 *   indossa    — mette l'oggetto in uno slot specifico (posizione 2-9)
 *   riponi     — sposta da zaino/indossato → inventario (posizione=0)
 *   togli      — rimuove dall'indossato → zaino (posizione=1)
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'
import shared from './shared.module.css'

// ---------------------------------------------------------------------------
// MANNEQUIN
// ---------------------------------------------------------------------------

/**
 * Figura omino con slot per gli oggetti indossati.
 * Colonna sinistra: neck(9), armdx(2), ring(8), feet(6)
 * Colonna centrale: immagine omino
 * Colonna destra:   head(7), chest(4), armsx(3), legs(5)
 */
function Mannequin({ worn }) {
    const slot = pos => worn[pos]
        ? <img src={`/imgs/items/${worn[pos].urlimg}`} alt={worn[pos].nome} title={worn[pos].nome} />
        : null

    return (
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
    )
}

// ---------------------------------------------------------------------------
// ITEM ROW
// ---------------------------------------------------------------------------

/**
 * Riga della tabella zaino.
 * Mostra immagine, descrizione, commenti e pulsanti azione.
 * I commenti sono visualizzati all'interno della cella descrizione
 * (semplificazione rispetto al rowspan dell'originale PHP).
 */
function ItemRow({ item, worn, canAct, chars, onAction }) {
    const [giveTarget, setGiveTarget] = useState(chars[0] ?? '')

    const act = (sub, extra = {}) =>
        onAction({ sub, id_oggetto: item.id, numero: item.numero, cariche: item.cariche, ...extra })

    const isWorn   = item.posizione > 1
    const canWear  = item.ubicabile > 1
    const slotFree = canWear && !worn[item.ubicabile]
    const dxFree   = !worn[2]
    const sxFree   = !worn[3]

    return (
        <tr>
            {/* Immagine */}
            <td className="casella_elemento casella_img_col">
                <div className="inventario_img">
                    <img src={`/imgs/items/${item.urlimg}`} alt={item.nome} style={{ maxWidth: 300 }} />
                </div>
            </td>

            {/* Descrizione + commenti */}
            <td className="casella_elemento">
                <div className="inventario_riga_descrizione">
                    <span className={shared.schedaUpper}>
                        <center><b>{item.nome}</b> x {item.numero}
                            {item.cariche > 1 && ` (Cariche: ${item.cariche})`}
                        </center>
                    </span>
                    {item.descrizione && (
                        <div dangerouslySetInnerHTML={{ __html: item.descrizione }} />
                    )}
                </div>

                {item.commento && (
                    <div className="inventario_riga_commento">
                        <span className={shared.schedaUpper}><center><b>COMMENTO</b></center></span>
                        {item.commento}
                    </div>
                )}
                {item.commento_master && (
                    <div className="inventario_riga_commento">
                        <span className={shared.schedaUpper}><center><b>NOTA MASTER</b></center></span>
                        <span dangerouslySetInnerHTML={{ __html: item.commento_master }} />
                    </div>
                )}
                {item.commento_shop && (
                    <div className="inventario_riga_commento">
                        <span className={shared.schedaUpper}><center><b>NOTA SHOP</b></center></span>
                        <span dangerouslySetInnerHTML={{ __html: item.commento_shop }} />
                    </div>
                )}
            </td>

            {/* Pulsanti azione */}
            <td className="casella_elemento casella_azioni_col">
                {canAct && (
                    <div className="form_gioco">
                        <button onClick={() => act('abbandona')}>Abbandona</button>
                        <button onClick={() => act('riponi')}>Inventario</button>

                        {canWear && !isWorn && (
                            <>
                                {slotFree && (
                                    <button onClick={() => act('indossa', { posizione: item.ubicabile })}>
                                        Indossa
                                    </button>
                                )}
                                {dxFree && (
                                    <button onClick={() => act('indossa', { posizione: 2 })}>
                                        Impugna (DX)
                                    </button>
                                )}
                                {sxFree && (
                                    <button onClick={() => act('indossa', { posizione: 3 })}>
                                        Impugna (SX)
                                    </button>
                                )}
                            </>
                        )}
                        {canWear && isWorn && (
                            <button onClick={() => act('indossa', { posizione: 1 })}>Togli</button>
                        )}

                        {chars.length > 0 && (
                            <div>
                                <button onClick={() => act('cedi', { give_item: giveTarget })}>
                                    Cedi a
                                </button>
                                <select value={giveTarget} onChange={e => setGiveTarget(e.target.value)}>
                                    {chars.map(c => <option key={c} value={c}>{c}</option>)}
                                </select>
                            </div>
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

export default function SchedaEquip() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,   setProfile]   = useState(null)
    const [equipData, setEquipData] = useState(null)
    const [feedback,  setFeedback]  = useState(null)
    const [error,     setError]     = useState(null)

    const loadEquip = useCallback(() => {
        const enc = encodeURIComponent(pg)
        return fetch(`/pages/api_scheda.php?op=equip&pg=${enc}`).then(r => r.json())
    }, [pg])

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            loadEquip(),
        ]).then(([prof, eq]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (eq.success)   setEquipData(eq)
            else              setError(eq.message  ?? 'Errore equipaggiamento')
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg, loadEquip])

    const doAction = useCallback(async (params) => {
        const enc = encodeURIComponent(pg)
        try {
            const resp = await fetch(`/pages/api_scheda.php?op=equip_action&pg=${enc}`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(params),
            })
            const data = await resp.json()
            if (data.success) {
                setFeedback('Operazione eseguita.')
                loadEquip().then(eq => { if (eq.success) setEquipData(eq) })
            } else {
                setFeedback(data.message ?? 'Errore')
            }
        } catch {
            setFeedback('Errore di rete')
        }
    }, [pg, loadEquip])

    if (error)                return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !equipData) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const { worn, items, chars } = equipData
    const canAct = is_own || equipData.is_admin

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Equipaggiamento</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />
                <div className="title">{nome} {cognome}</div>
            </div>

            <Mannequin worn={worn} />

            <div className="page_title"><h2>Zaino</h2></div>
            <div className="page_body box_equip">
                {feedback && <div className="warning">{feedback}</div>}
                <div className="panels_box">
                    <div className="elenco_record_gioco">
                        <table className="customTable inventory-table">
                            <thead>
                                <tr>
                                    <td className="casella_titolo casella_img_col">
                                        <div className="titoli_elenco">Immagine</div>
                                    </td>
                                    <td className="casella_titolo">
                                        <div className="titoli_elenco">Descrizione</div>
                                    </td>
                                    <td className="casella_titolo casella_azioni_col">
                                        <div className="titoli_elenco">&nbsp;</div>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0
                                    ? <tr><td colSpan="3" className={shared.centered}>
                                        Zaino vuoto.
                                      </td></tr>
                                    : items.map(item => (
                                        <ItemRow
                                            key={item.id}
                                            item={item}
                                            worn={worn}
                                            canAct={canAct}
                                            chars={chars}
                                            onAction={doAction}
                                        />
                                    ))
                                }
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    )
}
