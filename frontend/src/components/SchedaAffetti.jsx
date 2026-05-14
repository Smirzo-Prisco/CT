/**
 * SchedaAffetti.jsx — Affetti del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile → dati pg + flag permessi per il menu
 *   api_scheda.php?op=affetti → lista affetti raggruppati per tipo
 *
 * Layout a due colonne:
 *   Sinistra: lista tile cliccabili (legami, nemici, famiglia, conoscenze, memories)
 *   Destra:   iframe che carica pages/dettaglio_affetto.php (pagina PHP esistente)
 *
 * Il click sulle tile aggiorna il src dell'iframe senza jQuery.
 * L'aggiunta di un nuovo affetto apre pages/form_affetto.php (link normale → reload PHP).
 *
 * scheda_affetti.css è iniettato da AppRouter via injectCSS alla navigazione SPA
 * e referenziato tramite <link> in scheda_affetti.inc.php per il caricamento PHP diretto.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, Fragment } from 'react'
import SchedaMenu from './SchedaMenu'

// ---------------------------------------------------------------------------
// COSTANTI
// ---------------------------------------------------------------------------

const TIPO_LABELS = {
    legami:     'Legami',
    nemici:     'Nemici',
    famiglia:   'Famiglia',
    conoscenze: 'Conoscenze',
    memories:   'Memories',
}

const AVATAR_DEFAULT = '/themes/crystal/imgs/scheda/empy_affetti.png'
const DETAIL_BASE    = '/pages/dettaglio_affetto.php'
const INTRO_BASE     = '/pages/intro_affetto.php'

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

/**
 * Singola tile affetto nella lista sinistra.
 * Cliccando aggiorna l'iframe nel pannello destro.
 */
function AffettoTile({ entry, pg, onClick, selected }) {
    const displayName = entry.nome
        ? `${entry.nome} ${entry.cognome}`.toUpperCase()
        : entry.nomePg.toUpperCase()
    const detailUrl = `${DETAIL_BASE}?id=${entry.id}&username=${encodeURIComponent(pg)}&pg=${encodeURIComponent(entry.nomePg)}`

    return (
        <li className={`tile${selected ? ' selected' : ''}`}>
            <a className="tile-content" href={detailUrl}
                onClick={e => { e.preventDefault(); onClick(detailUrl) }}>
                <div className="tile-icon">
                    <img src={entry.avatar || AVATAR_DEFAULT} alt="" />
                </div>
                <div className="tile-text">
                    <span>{displayName}</span>
                    {entry.titolo && (
                        <><br />
                        <span style={{ color: '#616161', fontFamily: 'Dejavu Sans', fontSize: 11,
                                       textTransform: 'none', letterSpacing: 'normal' }}>
                            {entry.titolo}
                        </span></>
                    )}
                </div>
            </a>
        </li>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaAffetti() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,  setProfile]  = useState(null)
    const [affetti,  setAffetti]  = useState(null)
    const [canAdd,   setCanAdd]   = useState(false)
    const [iframeSrc, setIframeSrc] = useState('')
    const [selected, setSelected] = useState(null)
    const [error,    setError]    = useState(null)
    const iframeRef = useRef(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`)
                .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); }),
            fetch(`/pages/api_scheda.php?op=affetti&pg=${enc}`)
                .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); }),
        ]).then(([prof, aff]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (aff.success) {
                setAffetti(aff.affetti)
                setCanAdd(aff.can_add)
                // Comportamento identico all'originale PHP: parte sempre da intro_affetto.php;
                // il dettaglio viene caricato solo al click su una tile (come faceva jQuery).
                setIframeSrc(`${INTRO_BASE}?username=${enc}`)
            } else {
                setError(aff.message ?? 'Errore affetti')
            }
        }).catch(e => setError(`Errore: ${e.message ?? 'risposta non valida dall\'API'}`))
    }, [pg])

    const openDetail = (url, id) => {
        setIframeSrc(url)
        setSelected(id)
    }

    if (error)            return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !affetti) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const enc = encodeURIComponent(pg)

    return (
        <div className="pagina_scheda">
            <div className="menu_scheda">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />
            </div>

            <div className="page_body">
                <table className="scheda-main-dati">
                    <tbody>
                        {/* Intestazione colonne */}
                        <tr style={{ background: "url('/themes/crystal/imgs/presenti/barra.png')", border: '1px solid #090a11' }}>
                            <td style={{ border: 'none' }}>
                                <div className="faces" style={{ fontFamily: 'DejaVu Serif', color: '#ce846f', fontSize: 12 }}>
                                    <div className="faces_info">PERSONAGGI</div>
                                </div>
                            </td>
                            <td style={{ border: 'none' }}>
                                <div className="faces" style={{ fontFamily: 'DejaVu Serif', color: '#ce846f', fontSize: 12 }}>
                                    <div className="faces_info">DETTAGLIO</div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            {/* Colonna sinistra: lista affetti */}
                            <td valign="top">
                                <ul className="list">
                                    {Object.entries(TIPO_LABELS).map(([tipo, label]) => {
                                        const lista = affetti[tipo] ?? []
                                        if (lista.length === 0) return null
                                        // Fragment con key per evitare <span>/<div> dentro <ul> (HTML invalido)
                                        return (
                                            <Fragment key={tipo}>
                                                <li className="tile no-height">
                                                    <div className="tile-text header" style={{ textAlign: 'center' }}>
                                                        <span className="header">{label}</span>
                                                    </div>
                                                </li>
                                                {lista.map(entry => (
                                                    <AffettoTile
                                                        key={entry.id}
                                                        entry={entry}
                                                        pg={pg}
                                                        selected={selected === entry.id}
                                                        onClick={url => openDetail(url, entry.id)}
                                                    />
                                                ))}
                                            </Fragment>
                                        )
                                    })}

                                    {/* Pulsante aggiungi (solo proprietario) */}
                                    {canAdd && (
                                        <li className="tile">
                                            <br />
                                            <a className="tile-content"
                                                href={`/pages/form_affetto.php?username=${enc}`}>
                                                <div className="tile-text" style={{ textAlign: 'center' }}>
                                                    <span style={{ color: '#353535', fontSize: 11,
                                                                   fontFamily: 'Dejavu Sans',
                                                                   textTransform: 'uppercase' }}>
                                                        Aggiungi affetto
                                                    </span>
                                                </div>
                                            </a>
                                        </li>
                                    )}
                                </ul>
                            </td>

                            {/* Colonna destra: iframe dettaglio (PHP esistente) */}
                            <td valign="top">
                                <iframe
                                    ref={iframeRef}
                                    id="content"
                                    src={iframeSrc}
                                    allowTransparency="true"
                                    frameBorder="0"
                                    width="100%"
                                    style={{ minHeight: 400 }}
                                    onLoad={() => {
                                        try {
                                            const h = iframeRef.current?.contentWindow?.document?.body?.scrollHeight
                                            if (h) iframeRef.current.style.height = h + 'px'
                                        } catch {}
                                    }}
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    )
}
