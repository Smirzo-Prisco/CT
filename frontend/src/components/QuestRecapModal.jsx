/**
 * QuestRecapModal.jsx
 *
 * Modale staff-only per le card Quest in RoleRecap.jsx: replica la feature
 * "Assegna punti" del forum (ComposeQuest in Forum.jsx), ma con partecipanti/
 * location precompilati e non modificabili (presi dalla giocata), e senza il
 * campo "riassunto" — generato in automatico lato server da Claude a partire
 * dalle azioni master (tipo 'M') della giocata.
 *
 * Al salvataggio crea un post nella bacheca "Resoconti e Quest", esattamente
 * come farebbe il composer manuale del forum (stessa funzione condivisa
 * createQuestPost() lato server).
 *
 * API: GET  pages/api_roleSession.php?op=getQuestRecapData&id_role=...
 *      POST pages/api_roleSession.php?op=saveQuestRecap
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

/** Identiche al composer manuale del forum (ComposeQuest in Forum.jsx) */
const TIPOLOGIE_QUEST = [
    'Assegnazione Esperienza o Notorietà',
    'Quest Singola', 'Quest di Gilda', 'Evento',
    'Prima parte', 'Seconda parte', 'Terza parte',
    'Quarta parte', 'Quinta parte', 'Sesta parte',
    'Settima parte', 'Ottava parte', 'Nona parte',
    'Finale',
]

/** -5 … +10 step 0.5 (come il composer manuale del forum) */
const puntiOpts = Array.from({ length: 31 }, (_, i) => (i - 10) / 2)

export default function QuestRecapModal({ game, onClose }) {
    const [loading, setLoading] = useState(true)
    const [loadError, setLoadError] = useState('')
    const [location, setLocation] = useState('')
    const [pgPunti, setPgPunti] = useState([])

    const [titolo, setTitolo]           = useState('')
    const [tipologia, setTipologia]     = useState(TIPOLOGIE_QUEST[0])
    const [conseguenze, setConseguenze] = useState('')
    const [note, setNote]               = useState('')
    const [valutazioni, setValutazioni] = useState('')

    const [saving, setSaving]   = useState(false)
    const [saveMsg, setSaveMsg] = useState('')

    useEffect(() => {
        fetch(`/pages/api_roleSession.php?op=getQuestRecapData&id_role=${game.id}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    setLocation(d.location ?? '')
                    setPgPunti((d.partecipanti ?? []).map(nome => ({ nome, exp: 0, shin: 0, mestiere: 0 })))
                } else {
                    setLoadError(d.message ?? 'Errore nel caricamento')
                }
                setLoading(false)
            })
            .catch(() => { setLoadError('Errore di rete'); setLoading(false) })
    }, [game.id])

    // Chiude con Esc, coerente con le altre modali del progetto
    useEffect(() => {
        const onKey = e => { if (e.key === 'Escape') onClose() }
        document.addEventListener('keydown', onKey)
        return () => document.removeEventListener('keydown', onKey)
    }, [onClose])

    const updatePg = useCallback((i, field, value) => {
        setPgPunti(prev => { const n = [...prev]; n[i] = { ...n[i], [field]: value }; return n })
    }, [])

    function handleSave() {
        if (!titolo.trim() || saving) return
        setSaving(true)
        setSaveMsg('')
        fetch('/pages/api_roleSession.php?op=saveQuestRecap', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_role: game.id,
                titolo, tipologia, conseguenze, note, valutazioni,
                partecipanti_punti: pgPunti,
            }),
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) onClose(true)
                else setSaveMsg(d.message ?? 'Errore nel salvataggio')
            })
            .catch(() => setSaveMsg('Errore di rete'))
            .finally(() => setSaving(false))
    }

    return (
        <div className="quest-modal-overlay" onClick={() => onClose()}>
            <div className="quest-modal" role="dialog" aria-modal="true" aria-label="Assegna punti Quest" onClick={e => e.stopPropagation()}>
                <div className="quest-modal-header">
                    <h2><i className="fas fa-scroll"></i> Assegna punti Quest</h2>
                    <button className="quest-modal-close" onClick={() => onClose()} aria-label="Chiudi">&times;</button>
                </div>

                <div className="quest-modal-body">
                    {loading && <p className="quest-modal-status">Caricamento…</p>}
                    {!loading && loadError && <p className="quest-modal-status quest-modal-status--error">{loadError}</p>}

                    {!loading && !loadError && (
                        <>
                            <div className="quest-field">
                                <label htmlFor="quest-titolo">Titolo quest</label>
                                <input id="quest-titolo" type="text" value={titolo}
                                    onChange={e => setTitolo(e.target.value)}
                                    placeholder="Inserire titolo quest" />
                            </div>

                            <div className="quest-field">
                                <label htmlFor="quest-tipologia">Tipologia quest</label>
                                <select id="quest-tipologia" value={tipologia} onChange={e => setTipologia(e.target.value)}>
                                    {TIPOLOGIE_QUEST.map(t => <option key={t} value={t}>{t}</option>)}
                                </select>
                            </div>

                            <div className="quest-field-row">
                                <div className="quest-field">
                                    <label>Partecipanti</label>
                                    <div className="quest-readonly">
                                        {pgPunti.length > 0 ? pgPunti.map(p => p.nome).join(', ') : '—'}
                                    </div>
                                </div>
                                <div className="quest-field">
                                    <label>Location</label>
                                    <div className="quest-readonly">{location || '—'}</div>
                                </div>
                            </div>

                            <div className="quest-field">
                                <label htmlFor="quest-conseguenze">Conseguenze quest</label>
                                <textarea id="quest-conseguenze" rows="3" value={conseguenze}
                                    onChange={e => setConseguenze(e.target.value)}
                                    placeholder="Eventi, conseguenze pg, alterazioni ambientazione..." />
                            </div>

                            <div className="quest-field">
                                <label htmlFor="quest-note">Note</label>
                                <textarea id="quest-note" rows="3" value={note}
                                    onChange={e => setNote(e.target.value)}
                                    placeholder="Punti salute, potenziamento oggetti di gilda, skill acquisite..." />
                            </div>

                            <div className="quest-field">
                                <label htmlFor="quest-valutazioni">Valutazioni</label>
                                <textarea id="quest-valutazioni" rows="3" value={valutazioni}
                                    onChange={e => setValutazioni(e.target.value)}
                                    placeholder="Valutazioni pg" />
                            </div>

                            <div className="quest-field">
                                <label>Punti partecipanti</label>
                                <div className="quest-table-wrap">
                                    <table className="quest-punti-table">
                                        <thead>
                                            <tr><th>Pg</th><th>Exp</th><th>Shin</th><th>Mestiere</th></tr>
                                        </thead>
                                        <tbody>
                                            {pgPunti.map((pg, i) => (
                                                <tr key={pg.nome}>
                                                    <td className="quest-punti-nome">{pg.nome}</td>
                                                    <td>
                                                        <select value={pg.exp} onChange={e => updatePg(i, 'exp', parseFloat(e.target.value))}>
                                                            {puntiOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select value={pg.shin} onChange={e => updatePg(i, 'shin', parseFloat(e.target.value))}>
                                                            {puntiOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select value={pg.mestiere} onChange={e => updatePg(i, 'mestiere', parseFloat(e.target.value))}>
                                                            {puntiOpts.map(v => <option key={v} value={v}>{v}</option>)}
                                                        </select>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {saveMsg && <p className="quest-modal-status quest-modal-status--error">{saveMsg}</p>}
                        </>
                    )}
                </div>

                <div className="quest-modal-footer">
                    <button className="quest-btn-secondary" onClick={() => onClose()}>Annulla</button>
                    <button className="quest-btn-primary" onClick={handleSave} disabled={saving || loading || !!loadError || !titolo.trim()}>
                        {saving ? 'Salvataggio…' : 'Salva'}
                    </button>
                </div>
            </div>
        </div>
    )
}
