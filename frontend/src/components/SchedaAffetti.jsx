/**
 * SchedaAffetti.jsx — Affetti del personaggio (Phase SPA)
 *
 * Fetch parallele:
 *   api_scheda.php?op=profile → dati pg + flag permessi per il menu
 *   api_scheda.php?op=affetti → lista affetti raggruppati per tipo
 *
 * Layout due pannelli:
 *   Sinistra: sidebar con tile cliccabili raggruppate per tipo
 *   Destra:   iframe che carica dettaglio_affetto.php / form_affetto.php
 *
 * Il click su una tile aggiorna il src dell'iframe (senza reload full-page).
 * Il bottone "Aggiungi affetto" carica form_affetto.php nello stesso iframe.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, Fragment } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './SchedaAffetti.module.css'

// ---------------------------------------------------------------------------
// COSTANTI
// ---------------------------------------------------------------------------

const TIPO_LABELS = {
    legami:     'Legami',
    nemici:     'Nemici',
    famiglia:   'Razza',
    conoscenze: 'Conoscenze',
    memories:   'Memories',
}

const AVATAR_DEFAULT = '/themes/crystal/imgs/scheda/empy_affetti.png'
const DETAIL_BASE    = '/pages/dettaglio_affetto.php'
const INTRO_BASE     = '/pages/intro_affetto.php'

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

function AffettoTile({ entry, pg, selected, onClick }) {
    const displayName = entry.nome
        ? `${entry.nome} ${entry.cognome}`
        : entry.nomePg
    const detailUrl = `${DETAIL_BASE}?id=${entry.id}&username=${encodeURIComponent(pg)}&pg=${encodeURIComponent(entry.nomePg)}`

    return (
        <button
            type="button"
            className={`${styles.tile}${selected ? ` ${styles.tileSelected}` : ''}`}
            onClick={() => onClick(detailUrl)}
        >
            <img src={entry.avatar || AVATAR_DEFAULT} alt="" className={styles.tileAvatar} />
            <div className={styles.tileInfo}>
                <div className={styles.tileName}>{displayName}</div>
                {entry.titolo && <div className={styles.tileTitolo}>{entry.titolo}</div>}
            </div>
        </button>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function SchedaAffetti() {
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile,   setProfile]   = useState(null)
    const [affetti,   setAffetti]   = useState(null)
    const [canAdd,    setCanAdd]    = useState(false)
    const [iframeSrc, setIframeSrc] = useState('')
    const [selected,  setSelected]  = useState(null)
    const [error,     setError]     = useState(null)
    const iframeRef = useRef(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        const enc = encodeURIComponent(pg)
        Promise.all([
            fetch(`/pages/api_scheda.php?op=profile&pg=${enc}`).then(r => r.json()),
            fetch(`/pages/api_scheda.php?op=affetti&pg=${enc}`).then(r => r.json()),
        ]).then(([prof, aff]) => {
            if (prof.success) setProfile(prof)
            else              setError(prof.message ?? 'Errore profilo')
            if (aff.success) {
                setAffetti(aff.affetti)
                setCanAdd(aff.can_add)
                setIframeSrc(`${INTRO_BASE}?username=${enc}`)
            } else {
                setError(aff.message ?? 'Errore affetti')
            }
        }).catch(e => setError(e.message ?? 'Errore di rete'))
    }, [pg])

    const openDetail = (url, id) => {
        setIframeSrc(url)
        setSelected(id)
    }

    if (error)              return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile || !affetti) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const enc = encodeURIComponent(pg)

    const tipiConAffetti = Object.entries(TIPO_LABELS)
        .filter(([tipo]) => (affetti[tipo] ?? []).length > 0)

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>Affetti</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                <div className={styles.layout}>

                    {/* ── Sidebar ─────────────────────────────────────── */}
                    <div className={styles.sidebar}>

                        {tipiConAffetti.length === 0 && !canAdd && (
                            <div className={styles.emptyList}>Nessun affetto registrato.</div>
                        )}

                        {tipiConAffetti.map(([tipo, label], idx) => (
                            <Fragment key={tipo}>
                                <div className={`${styles.groupHeader}${idx > 0 ? ` ${styles.groupHeaderDivider}` : ''}`}>
                                    {label}
                                </div>
                                {affetti[tipo].map(entry => (
                                    <AffettoTile
                                        key={entry.id}
                                        entry={entry}
                                        pg={pg}
                                        selected={selected === entry.id}
                                        onClick={url => openDetail(url, entry.id)}
                                    />
                                ))}
                            </Fragment>
                        ))}

                        {canAdd && (
                            <button
                                type="button"
                                className={styles.addBtn}
                                onClick={() => openDetail(`/pages/form_affetto.php?username=${enc}`, null)}
                            >
                                <i className="fa-solid fa-plus" />
                                Aggiungi affetto
                            </button>
                        )}
                    </div>

                    {/* ── Pannello dettaglio ───────────────────────────── */}
                    <div className={styles.detailPanel}>
                        <iframe
                            ref={iframeRef}
                            src={iframeSrc}
                            allowTransparency="true"
                            frameBorder="0"
                            className={styles.iframe}
                            title="Dettaglio affetto"
                            onLoad={() => {
                                try {
                                    const h = iframeRef.current?.contentWindow?.document?.body?.scrollHeight
                                    if (h > 420) iframeRef.current.style.height = h + 'px'
                                } catch {}
                            }}
                        />
                    </div>

                </div>
            </div>
        </div>
    )
}
