/**
 * SchedaSub.jsx — Sotto-sezioni testo della scheda personaggio (Phase SPA)
 *
 * Gestisce tre pagine con la stessa struttura (menu + titolo + testo HTML):
 *   scheda_storia → profile.storia       (background narrativo)
 *   scheda_dice   → profile.descrizione  (dice di sé)
 *   scheda_off    → profile.off          (sezione off-topic)
 *
 * Legge quale pagina mostrare da ?page= nella URL corrente.
 * Legge il personaggio da ?pg=.
 * Fetch: api_scheda.php?op=profile&pg=X
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef } from 'react'
import SchedaMenu from './SchedaMenu'
import { extractAndScopeStyles } from '../utils/schedaStyles'

// ---------------------------------------------------------------------------
// MAPPA PAGINA → CAMPO API + TITOLO
// ---------------------------------------------------------------------------

const PAGE_MAP = {
    scheda_storia: { field: 'storia',      title: 'Storia' },
    scheda_dice:   { field: 'descrizione', title: 'Dice di sé' },
    scheda_off:    { field: 'off',         title: 'Off' },
}

// ---------------------------------------------------------------------------
// COMPONENTE
// ---------------------------------------------------------------------------

export default function SchedaSub() {
    const params = new URLSearchParams(window.location.search)
    const pg     = params.get('pg')   ?? ''
    const page   = params.get('page') ?? ''

    const { field, title } = PAGE_MAP[page] ?? { field: 'storia', title: '' }

    const [profile, setProfile] = useState(null)
    const [error,   setError]   = useState(null)
    const scopedStyleEl         = useRef(null)

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        fetch(`/pages/api_scheda.php?op=profile&pg=${encodeURIComponent(pg)}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setProfile(d)
                else           setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => setError('Errore di rete'))
    }, [pg])

    // Inietta in <head> il CSS dal campo corrente, scopato a .background.
    // Rimosso automaticamente quando si lascia la pagina.
    useEffect(() => {
        if (!profile) return
        const { css } = extractAndScopeStyles(profile[field] ?? '')
        if (scopedStyleEl.current) scopedStyleEl.current.remove()
        if (css) {
            const el = document.createElement('style')
            el.textContent = css
            document.head.appendChild(el)
            scopedStyleEl.current = el
        }
        return () => { if (scopedStyleEl.current) scopedStyleEl.current.remove() }
    }, [profile, field])

    if (error)    return <div className="pagina_scheda"><div className="error">{error}</div></div>
    if (!profile) return <div className="pagina_scheda"><div>Caricamento…</div></div>

    const { nome, cognome, is_own, is_admin, is_staff, is_master } = profile
    const { html: fieldHtml } = extractAndScopeStyles(profile[field] ?? '')

    return (
        <div className="pagina_scheda">
            <div className="page_title"><h2>{title}</h2></div>

            <div className="scheda_page_body">
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                <div className="title">{nome} {cognome}</div>

                {/* Contenuto HTML dal DB — <style> estratti e scopati a .background */}
                <div className="background">
                    <br />
                    <div className="body_box"
                        dangerouslySetInnerHTML={{ __html: fieldHtml }} />
                </div>
            </div>
        </div>
    )
}
