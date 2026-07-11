/**
 * ContattaModerazione.jsx — Pagina SPA per contattare la moderazione
 *
 * Sezioni:
 *  A — Form invio richiesta (stati: 'form' | 'preview' | 'success')
 *  B — Cronologia richieste dell'utente corrente con risposta espandibile
 *  C — [solo staff] Vista richieste di tutti gli utenti con modale risposta
 */

import { useState, useEffect, useCallback, Fragment } from 'react'
import { createPortal } from 'react-dom'
import styles from './ContattaModerazione.module.css'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── RispondiModal ─────────────────────────────────────────────────────────────

/**
 * Modale staff per rispondere a una richiesta di moderazione.
 * Carica il dettaglio dal backend e mostra titolo + testo + textarea risposta.
 */
function RispondiModal({ requestId, onClose, onDone }) {
    const [detail, setDetail]     = useState(null)
    const [risposta, setRisposta] = useState('')
    const [sending, setSending]   = useState(false)
    const [error, setError]       = useState(null)

    // Carica dettaglio richiesta al mount
    useEffect(() => {
        fetch(`/pages/api_moderazione.php?op=get_request&id=${requestId}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setDetail(d)
                else setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => setError('Errore di rete'))
    }, [requestId])

    const invia = async () => {
        if (!risposta.trim()) return
        setSending(true)
        setError(null)
        try {
            const r = await fetch('/pages/api_moderazione.php?op=rispondi', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: requestId, risposta: risposta.trim() }),
            })
            const d = await r.json()
            if (d.success) {
                onDone()
                onClose()
            } else {
                setError(d.message ?? 'Errore durante l\'invio')
                setSending(false)
            }
        } catch {
            setError('Errore di rete')
            setSending(false)
        }
    }

    // Portal su document.body: sfugge al transform della colonna laterale
    // (translateX(0) crea un containing block che intrappola position:fixed)
    return createPortal(
        <div className={styles.modalOverlay} onClick={e => e.target === e.currentTarget && onClose()}>
            <div className={styles.modal}>
                <h3>Rispondi alla richiesta</h3>

                {!detail && !error && <p className={styles.empty}>Caricamento…</p>}

                {error && <p className={styles.error}>{error}</p>}

                {detail && (
                    <div className={styles.modalDetail}>
                        <div className={styles.modalDetailTitle}>{detail.titolo}</div>
                        <div className={styles.modalDetailBody}>{detail.messaggio}</div>
                    </div>
                )}

                <div className={styles.formGroup}>
                    <label>Risposta della moderazione</label>
                    <textarea
                        className={styles.textarea}
                        value={risposta}
                        onChange={e => setRisposta(e.target.value)}
                        placeholder="Scrivi la risposta…"
                        rows={5}
                    />
                </div>

                {error && detail && <p className={styles.error}>{error}</p>}

                <div className={styles.modalActions}>
                    <button onClick={onClose} className={styles.btnSecondary}>Annulla</button>
                    <button
                        onClick={invia}
                        disabled={sending || !risposta.trim()}
                        className={styles.btnPrimary}
                    >
                        {sending ? 'Invio…' : 'Invia risposta'}
                    </button>
                </div>
            </div>
        </div>,
        document.body
    )
}

// ── StaffPanel ────────────────────────────────────────────────────────────────

/**
 * Sezione C: pannello richieste visibile solo allo staff.
 */
function StaffPanel() {
    const [requests, setRequests] = useState([])
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)
    const [modalId, setModalId]   = useState(null)

    const loadList = useCallback(() => {
        setLoading(true)
        fetch('/pages/api_moderazione.php?op=lista_staff')
            .then(r => r.json())
            .then(d => {
                setLoading(false)
                if (d.success) setRequests(d.requests ?? [])
                else setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => { setLoading(false); setError('Errore di rete') })
    }, [])

    useEffect(() => { loadList() }, [loadList])

    return (
        <section className={styles.section}>
            <h3>Vista Staff — Richieste di moderazione</h3>

            {loading && <p className={styles.empty}>Caricamento…</p>}
            {error   && <p className={styles.error}>{error}</p>}

            {!loading && !error && requests.length === 0 && (
                <p className={styles.empty}>Nessuna richiesta presente.</p>
            )}

            {!loading && !error && requests.length > 0 && (
                <table className={styles.table}>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Titolo</th>
                            <th>Anonimo</th>
                            <th>Stato</th>
                            <th>Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        {requests.map(req => (
                            <tr key={req.id}>
                                <td>{req.data_messaggio?.slice(0, 16)}</td>
                                <td>{req.titolo}</td>
                                <td>{req.anonimo === 'si' ? 'Sì' : 'No'}</td>
                                <td>
                                    <span className={
                                        req.stato === 0
                                            ? `${styles.badge} ${styles.badgeInCorso}`
                                            : `${styles.badge} ${styles.badgeChiuso}`
                                    }>
                                        {req.stato === 0 ? 'In corso' : 'Chiuso'}
                                    </span>
                                </td>
                                <td>
                                    {req.stato === 0 && (
                                        <button
                                            className={styles.btnSm}
                                            onClick={() => setModalId(req.id)}
                                        >
                                            Rispondi
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}

            {modalId !== null && (
                <RispondiModal
                    requestId={modalId}
                    onClose={() => setModalId(null)}
                    onDone={loadList}
                />
            )}
        </section>
    )
}

// ── ContattaModerazione ───────────────────────────────────────────────────────

export default function ContattaModerazione() {
    // Form state
    const [formStato, setFormStato] = useState('form')   // 'form' | 'preview' | 'success'
    const [titolo, setTitolo]       = useState('')
    const [testo, setTesto]         = useState('')
    const [anonimo, setAnonimo]     = useState(false)
    const [submitting, setSubmitting] = useState(false)
    const [submitError, setSubmitError] = useState(null)

    // History state
    const [history, setHistory]       = useState([])
    const [histLoading, setHistLoading] = useState(true)
    const [histError, setHistError]     = useState(null)
    const [openRespId, setOpenRespId]   = useState(null)  // id richiesta con risposta espansa
    const [isStaff, setIsStaff]         = useState(false)

    const loadHistory = useCallback(() => {
        setHistLoading(true)
        fetch('/pages/api_moderazione.php?op=list')
            .then(r => r.json())
            .then(d => {
                setHistLoading(false)
                if (d.success) {
                    setHistory(d.requests ?? [])
                    setIsStaff(!!d.is_staff)
                } else {
                    setHistError(d.message ?? 'Errore nel caricamento')
                }
            })
            .catch(() => { setHistLoading(false); setHistError('Errore di rete') })
    }, [])

    useEffect(() => { loadHistory() }, [loadHistory])

    // ── Form handlers ─────────────────────────────────────────────────────────

    const handleAnteprima = () => {
        if (!titolo.trim() || !testo.trim()) {
            setSubmitError('Titolo e testo sono obbligatori')
            return
        }
        setSubmitError(null)
        setFormStato('preview')
    }

    const handleInvia = async () => {
        setSubmitting(true)
        setSubmitError(null)
        try {
            const r = await fetch('/pages/api_moderazione.php?op=submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ titolo, testo, anonimo }),
            })
            const d = await r.json()
            if (d.success) {
                setFormStato('success')
                loadHistory()
            } else {
                setSubmitError(d.message ?? 'Errore durante l\'invio')
                setFormStato('preview')
            }
        } catch {
            setSubmitError('Errore di rete')
            setFormStato('preview')
        } finally {
            setSubmitting(false)
        }
    }

    const resetForm = () => {
        setTitolo('')
        setTesto('')
        setAnonimo(false)
        setSubmitError(null)
        setFormStato('form')
    }

    // ── Render form ───────────────────────────────────────────────────────────

    const renderForm = () => (
        <>
            <div className={styles.formGroup}>
                <label htmlFor="cm-titolo">Titolo</label>
                <input
                    id="cm-titolo"
                    type="text"
                    className={styles.input}
                    value={titolo}
                    onChange={e => setTitolo(e.target.value)}
                    placeholder="Oggetto della richiesta"
                />
            </div>
            <div className={styles.formGroup}>
                <label htmlFor="cm-testo">Testo</label>
                <textarea
                    id="cm-testo"
                    className={styles.textarea}
                    value={testo}
                    onChange={e => setTesto(e.target.value)}
                    placeholder="Descrivi la tua richiesta…"
                    rows={6}
                />
            </div>
            <div className={styles.formGroup}>
                <label className={styles.checkboxRow}>
                    <input
                        type="checkbox"
                        checked={anonimo}
                        onChange={e => setAnonimo(e.target.checked)}
                    />
                    Invia in modo anonimo
                </label>
            </div>
            <p className={styles.noteText}>
                (Qualora il richiedente scegliesse di restare anonimo, il suo nome viene criptato dal
                sistema. Gestione e moderazione non possono vedere il suo nome.)
            </p>
            {submitError && <p className={styles.error} style={{ marginTop: '10px' }}>{submitError}</p>}
            <div className={styles.formActions}>
                <button onClick={handleAnteprima} className={styles.btnPrimary}>Anteprima</button>
            </div>
        </>
    )

    // ── Render preview ────────────────────────────────────────────────────────

    const renderPreview = () => (
        <>
            <div className={styles.previewBox}>
                <div className={styles.previewLabel}>Titolo</div>
                <div className={styles.previewValue}>{titolo}</div>
                <div className={styles.previewLabel} style={{ marginTop: '12px' }}>Testo</div>
                <div className={styles.previewValue}>{testo}</div>
                <div className={styles.previewLabel} style={{ marginTop: '12px' }}>Anonimato</div>
                <div className={styles.previewValue}>{anonimo ? 'Anonimo' : 'Non anonimo'}</div>
            </div>
            {submitError && <p className={styles.error}>{submitError}</p>}
            <div className={styles.formActions}>
                <button onClick={() => setFormStato('form')} className={styles.btnSecondary}>
                    Modifica
                </button>
                <button onClick={handleInvia} disabled={submitting} className={styles.btnPrimary}>
                    {submitting ? 'Invio…' : 'Invia'}
                </button>
            </div>
        </>
    )

    // ── Render success ────────────────────────────────────────────────────────

    const renderSuccess = () => (
        <div className={styles.successBox}>
            <div className={styles.successIcon}>✓</div>
            <p className={styles.successText}>
                Messaggio inoltrato. Riceverai un messaggio privato con il responso della moderazione.
            </p>
            <button onClick={resetForm} className={styles.btnSecondary}>
                Nuova richiesta
            </button>
        </div>
    )

    // ── Render history ────────────────────────────────────────────────────────

    const renderHistory = () => (
        <section className={styles.section}>
            <h3>Le tue richieste</h3>

            {histLoading && <p className={styles.empty}>Caricamento…</p>}
            {histError   && <p className={styles.error}>{histError}</p>}

            {!histLoading && !histError && history.length === 0 && (
                <p className={styles.empty}>Nessuna richiesta ancora inviata.</p>
            )}

            {!histLoading && !histError && history.length > 0 && (
                <table className={styles.table}>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Titolo</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        {history.map(req => (
                            <Fragment key={req.id}>
                                <tr>
                                    <td>{req.data_messaggio?.slice(0, 16)}</td>
                                    <td>
                                        {req.risposta ? (
                                            <button
                                                className={styles.titleLink}
                                                onClick={() =>
                                                    setOpenRespId(prev => prev === req.id ? null : req.id)
                                                }
                                                title="Clicca per vedere la risposta"
                                            >
                                                {req.titolo} ▾
                                            </button>
                                        ) : (
                                            req.titolo
                                        )}
                                    </td>
                                    <td>
                                        <span className={
                                            req.stato === 0
                                                ? `${styles.badge} ${styles.badgeInCorso}`
                                                : `${styles.badge} ${styles.badgeChiuso}`
                                        }>
                                            {req.stato === 0 ? 'In corso' : 'Chiuso'}
                                        </span>
                                    </td>
                                </tr>
                                {openRespId === req.id && req.risposta && (
                                    <tr className={styles.rispostaRow}>
                                        <td colSpan={3}>
                                            <div className={styles.rispostaLabel}>
                                                Risposta della moderazione
                                            </div>
                                            <div className={styles.rispostaBox}>
                                                {req.risposta}
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </Fragment>
                        ))}
                    </tbody>
                </table>
            )}
        </section>
    )

    // ── Main render ───────────────────────────────────────────────────────────

    return (
        <div className={styles.wrap}>
            <div className="link_back">
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>

            <h2 className={styles.title}>Contatta la Moderazione</h2>

            {/* Sezione A: form */}
            <section className={styles.section}>
                <h3>
                    {formStato === 'form'    && 'Nuova richiesta'}
                    {formStato === 'preview' && 'Anteprima'}
                    {formStato === 'success' && 'Inviato'}
                </h3>
                {formStato === 'form'    && renderForm()}
                {formStato === 'preview' && renderPreview()}
                {formStato === 'success' && renderSuccess()}
            </section>

            {/* Sezione B: cronologia */}
            {renderHistory()}

            {/* Sezione C: pannello staff */}
            {isStaff && <StaffPanel />}
        </div>
    )
}
