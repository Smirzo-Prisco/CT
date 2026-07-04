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

    const [profile, setProfile]           = useState(null)
    const [error,   setError]             = useState(null)
    const [showModal, setShowModal]       = useState(false)
    const [inviando, setInviando]         = useState(false)
    const [richiestaMsg, setRichiestaMsg] = useState(null) // { ok, text } oppure null
    const scopedStyleEl                   = useRef(null)

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

    const { nome, cognome, is_own, is_admin, is_staff, is_master, narrazione_disponibile, narrazione_pendente } = profile
    const { html: fieldHtml } = extractAndScopeStyles(profile[field] ?? '')

    const mostraRichiestaNarrazione = page === 'scheda_dice' && is_own && narrazione_disponibile

    const richiediNarrazione = async () => {
        setInviando(true)
        try {
            const r = await fetch(`/pages/api_scheda.php?op=narrazione_richiedi&pg=${encodeURIComponent(pg)}`, { method: 'POST' })
            const d = await r.json()
            setRichiestaMsg({ ok: d.success, text: d.message ?? (d.success ? 'Richiesta inviata.' : 'Errore nell\'invio della richiesta.') })
            if (d.success) {
                setProfile({ ...profile, narrazione_pendente: true })
                setShowModal(false)
            }
        } catch {
            setRichiestaMsg({ ok: false, text: 'Errore di rete' })
        } finally {
            setInviando(false)
        }
    }

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

                {mostraRichiestaNarrazione && (
                    <div className="narrazione-ia-box">
                        {richiestaMsg && (
                            <p className={`gm-feedback gm-feedback--${richiestaMsg.ok ? 'ok' : 'error'}`}>{richiestaMsg.text}</p>
                        )}
                        <button
                            type="button"
                            className="btn btn--ghost btn-sm"
                            disabled={narrazione_pendente}
                            onClick={() => { alert('DEBUG: click ricevuto, narrazione_pendente=' + narrazione_pendente); setShowModal(true) }}
                        >
                            {narrazione_pendente ? 'Richiesta in attesa di approvazione…' : 'Richiedi narrazione generata dalle tue giocate'}
                        </button>
                    </div>
                )}

                {showModal && (
                    <div className="pg-edit-container" style={{ display: 'flex' }} role="dialog" aria-modal="true">
                        <div className="modal-content">
                            <div className="gp-modal-header">
                                <h2 className="gp-modal-title">Richiedi narrazione IA</h2>
                                <div className="gp-modal-header-actions">
                                    <button type="button" className="gp-modal-close" onClick={() => setShowModal(false)} aria-label="Chiudi">✕</button>
                                </div>
                            </div>
                            <div className="gm-modal-body">
                                <p>
                                    Un'intelligenza artificiale genererà una narrazione a partire da tutte le tue giocate concluse.
                                </p>
                                <p>
                                    <strong>Attenzione:</strong> questa azione, una volta approvata da uno staff, sovrascriverà
                                    interamente il testo attuale di "Dice di sé" con il nuovo testo generato.
                                </p>
                                <p>
                                    La richiesta dovrà essere approvata da un admin, che potrebbe contattarti per conferma prima di procedere.
                                </p>
                            </div>
                            <div className="gp-modal-footer">
                                <button type="button" className="btn btn--ghost" onClick={() => setShowModal(false)}>Annulla</button>
                                <button type="button" onClick={richiediNarrazione} disabled={inviando}>
                                    {inviando ? 'Invio…' : 'Conferma richiesta'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}
