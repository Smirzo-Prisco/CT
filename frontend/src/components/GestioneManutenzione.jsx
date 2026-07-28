/**
 * GestioneManutenzione.jsx — Pannello admin manutenzione database
 *
 * Sostituisce gli 8 file PHP legacy in pages/gestione/manutenzione/.
 * Ogni azione è distruttiva: prima di eseguirla si chiede sempre un'anteprima
 * (GET, sola lettura) con conteggi/campione di ciò che verrà toccato, poi una
 * conferma esplicita (POST) che esegue davvero la query.
 *
 * API: pages/api_manutenzione.php (GET = preview, POST = esegui)
 */

import { useState } from 'react'

const AZIONI = [
    {
        key: 'old_log',
        label: 'Log eventi amministrativi',
        description: 'Elimina i log di login, cambio permessi, bonifici, ecc. più vecchi della soglia scelta.',
        mesi: { min: 0, max: 12, default: 3 },
        danger: false,
    },
    {
        key: 'old_chat',
        label: 'Log di chat di gioco',
        description: 'Elimina le righe di chat di ruolo più vecchie della soglia scelta.',
        mesi: { min: 0, max: 12, default: 3 },
        danger: false,
    },
    {
        key: 'old_messages',
        label: 'Messaggi privati',
        description: 'Elimina i messaggi privati (e il relativo backup permanente) più vecchi della soglia scelta.',
        mesi: { min: 0, max: 12, default: 3 },
        danger: false,
    },
    {
        key: 'missing_soft',
        label: 'Personaggi assenti — cancellazione soft',
        description: 'Marca come cancellati (senza rimuoverli fisicamente) i personaggi inattivi da più della soglia scelta. Lo staff viene escluso automaticamente.',
        mesi: { min: 1, max: 12, default: 6 },
        danger: false,
    },
    {
        key: 'missing',
        label: 'Personaggi assenti — cancellazione definitiva',
        description: 'Elimina fisicamente i personaggi inattivi da più della soglia scelta. Operazione irreversibile.',
        mesi: { min: 1, max: 12, default: 6 },
        danger: true,
    },
    {
        key: 'deleted',
        label: 'Personaggi provvisoriamente cancellati',
        description: 'Elimina definitivamente i personaggi già marcati come cancellati (permessi = -1). Operazione irreversibile.',
        mesi: null,
        danger: true,
    },
    {
        key: 'blacklisted',
        label: 'Blacklist',
        description: 'Svuota completamente la blacklist.',
        mesi: null,
        danger: true,
    },
]

// ── ConfirmModal ─────────────────────────────────────────────────────────────

function ConfirmModal({ preview, executing, onConfirm, onClose }) {
    return (
        <div className="gm-overlay" role="dialog" aria-modal="true">
            <div className="gm-modal card">
                <div className="gp-modal-header">
                    <h3 className="gp-modal-title">{preview.title}</h3>
                    <button className="gp-modal-close" onClick={onClose} aria-label="Chiudi">✕</button>
                </div>

                <div className="gm-modal-body">
                    <p className="gm-warning">⚠️ {preview.warning}</p>

                    <ul className="gm-items">
                        {preview.items.map((item, i) => (
                            <li key={i} className="gm-item">
                                <span className="gm-item-label">{item.label}</span>
                                <span className="gm-item-count">{item.count}</span>
                                {Array.isArray(item.sample) && item.sample.length > 0 && (
                                    <div className="gm-sample">
                                        {item.sample.join(', ')}
                                        {item.count > item.sample.length &&
                                            ` … (+${item.count - item.sample.length} altri)`}
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>

                    {preview.notes?.length > 0 && (
                        <ul className="gm-notes">
                            {preview.notes.map((nota, i) => <li key={i}>{nota}</li>)}
                        </ul>
                    )}
                </div>

                <div className="gp-modal-footer">
                    <button className="btn btn--ghost" onClick={onClose} disabled={executing}>
                        Annulla
                    </button>
                    <button className="btn btn--danger" onClick={onConfirm} disabled={executing}>
                        {executing ? 'Esecuzione…' : 'Conferma'}
                    </button>
                </div>
            </div>
        </div>
    )
}

// ── ActionCard ───────────────────────────────────────────────────────────────

function ActionCard({ azione, mesi, onMesiChange, onEsegui, loading, result }) {
    return (
        <div className="card gm-card">
            <h3>{azione.label}</h3>
            <p className="gm-description">{azione.description}</p>

            {azione.mesi && (
                <div className="gm-field">
                    <label htmlFor={`mesi-${azione.key}`}>Soglia (mesi)</label>
                    <select
                        id={`mesi-${azione.key}`}
                        value={mesi}
                        onChange={e => onMesiChange(azione.key, Number(e.target.value))}
                    >
                        {Array.from(
                            { length: azione.mesi.max - azione.mesi.min + 1 },
                            (_, i) => azione.mesi.min + i
                        ).map(m => <option key={m} value={m}>{m} mesi</option>)}
                    </select>
                </div>
            )}

            <button
                className={`btn ${azione.danger ? 'btn--danger' : 'btn--secondary'} gm-run-btn`}
                onClick={() => onEsegui(azione)}
                disabled={loading}
            >
                {loading ? 'Verifica…' : 'Verifica ed esegui'}
            </button>

            {result && (
                <p className={result.ok ? 'gm-feedback gm-feedback--ok' : 'gm-feedback gm-feedback--error'}>
                    {result.text}
                </p>
            )}
        </div>
    )
}

// ── GestioneManutenzione ─────────────────────────────────────────────────────

export default function GestioneManutenzione() {
    const [mesiByAction, setMesiByAction] = useState(() =>
        Object.fromEntries(AZIONI.filter(a => a.mesi).map(a => [a.key, a.mesi.default]))
    )
    const [loadingKey, setLoadingKey] = useState(null)
    const [executing, setExecuting]   = useState(false)
    const [preview, setPreview]       = useState(null) // { azione, ...datiPreview }
    const [results, setResults]       = useState({})   // key -> { ok, text }

    const goToMap = () => window.CT.navigate('main.php?page=mappaclick')

    const setMesi = (key, val) => setMesiByAction(prev => ({ ...prev, [key]: val }))

    const richiediPreview = async (azione) => {
        setLoadingKey(azione.key)
        const qs = azione.mesi ? `&mesi=${mesiByAction[azione.key]}` : ''
        try {
            const r = await fetch(`/pages/api_manutenzione.php?op=${azione.key}${qs}`)
            if (r.status === 403) { goToMap(); return }
            const d = await r.json()
            setLoadingKey(null)
            if (d.success) setPreview({ azione, ...d })
            else setResults(prev => ({ ...prev, [azione.key]: { ok: false, text: d.message ?? 'Errore' } }))
        } catch {
            setLoadingKey(null)
            setResults(prev => ({ ...prev, [azione.key]: { ok: false, text: 'Errore di rete' } }))
        }
    }

    const confermaEsecuzione = async () => {
        if (!preview) return
        const azione = preview.azione
        setExecuting(true)
        try {
            const r = await fetch(`/pages/api_manutenzione.php?op=${azione.key}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(azione.mesi ? { mesi: mesiByAction[azione.key] } : {}),
            })
            if (r.status === 403) { goToMap(); return }
            const d = await r.json()
            setResults(prev => ({
                ...prev,
                [azione.key]: { ok: d.success, text: d.message ?? (d.success ? 'Fatto.' : 'Errore') },
            }))
        } catch {
            setResults(prev => ({ ...prev, [azione.key]: { ok: false, text: 'Errore di rete' } }))
        } finally {
            setExecuting(false)
            setPreview(null)
        }
    }

    return (
        <div className="pagina_gestione">
            <header>🛠️ Manutenzione</header>
            <div className="dashboard gm-dashboard">
                {AZIONI.map(azione => (
                    <ActionCard
                        key={azione.key}
                        azione={azione}
                        mesi={mesiByAction[azione.key]}
                        onMesiChange={setMesi}
                        onEsegui={richiediPreview}
                        loading={loadingKey === azione.key}
                        result={results[azione.key]}
                    />
                ))}
            </div>

            {preview && (
                <ConfirmModal
                    preview={preview}
                    executing={executing}
                    onConfirm={confermaEsecuzione}
                    onClose={() => setPreview(null)}
                />
            )}
        </div>
    )
}
