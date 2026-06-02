/**
 * MasterPanel.jsx — Pannello master separato dalla chat GDR.
 *
 * Overlay React con portal su document.body, distinto dal pannello comune.
 * Richiede isStaff=true (visibilità controllata da ChatShell).
 *
 * Sezioni:
 *   - Chiudi turno
 *   - Modifica Personaggio: carica/salva note_fato, particolari, salute, integrita, notorieta
 *   - Gestione PNG: creazione con tutti i parametri + attacco con sistema combattimento
 *   - Attacchi PNG in sospeso: risposta dado/subisce per conto dei PNG
 *
 * API usate (tutte su pages/api_chat.php):
 *   getPgList, getPgData, savePgData, newMasterPng, newMasterPngAttack,
 *   pending_attacks_png, risposta_immediata_png
 * API esterna: pages/api_roleSession.php?op=getPngRolePlaying (lista PNG attivi)
 *              pages/api_roleSession.php?op=getRolePgs (lista PG per target selector)
 *
 * CSS: eredita le classi .gdr-* da chat.css (già caricato con la chat).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import { createPortal } from 'react-dom'

// ── Modifica Personaggio ──────────────────────────────────────────────────────

function ModificaPG() {
    const [pgList, setPgList]     = useState([])
    const [selectedPg, setSelectedPg] = useState('')
    const [form, setForm]         = useState({ note_fato: '', particolari: '', salute: 0, integrita: 0, notorieta: 0 })
    const [loading, setLoading]   = useState(false)
    const [saving, setSaving]     = useState(false)
    const [msg, setMsg]           = useState('')

    // Carica la lista PG al mount
    useEffect(() => {
        fetch('pages/api_chat.php?op=getPgList')
            .then(r => r.json())
            .then(d => { if (d.success) setPgList(d.pgs) })
    }, [])

    function loadPg(name) {
        setSelectedPg(name)
        setMsg('')
        if (!name) return
        setLoading(true)
        fetch(`pages/api_chat.php?op=getPgData&pg=${encodeURIComponent(name)}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setForm(d.data)
                setLoading(false)
            })
    }

    function save() {
        setSaving(true)
        setMsg('')
        fetch('pages/api_chat.php?op=savePgData', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ pg: selectedPg, ...form }),
        })
            .then(r => r.json())
            .then(d => { setMsg(d.message ?? ''); setSaving(false) })
    }

    return (
        <div className="gdr-card">
            <div className="gdr-card-title">Modifica Personaggio</div>
            <div className="gdr-form-group">
                <label className="gdr-label">Personaggio</label>
                <select className="gdr-select" value={selectedPg}
                    onChange={e => loadPg(e.target.value)}>
                    <option value="">-- seleziona --</option>
                    {pgList.map(pg => <option key={pg} value={pg}>{pg}</option>)}
                </select>
            </div>
            {loading && <p style={{ fontSize: '12px', color: '#8f8f8f' }}>Caricamento…</p>}
            {selectedPg && !loading && (
                <>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Note del Fato</label>
                        <textarea className="gdr-textarea" rows={3} value={form.note_fato ?? ''}
                            onChange={e => setForm(f => ({ ...f, note_fato: e.target.value }))} />
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Particolari</label>
                        <textarea className="gdr-textarea" rows={3} value={form.particolari ?? ''}
                            onChange={e => setForm(f => ({ ...f, particolari: e.target.value }))} />
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Salute</label>
                        <input className="gdr-input" type="number" min={0} value={form.salute ?? 0}
                            onChange={e => setForm(f => ({ ...f, salute: e.target.value }))} />
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Integrità (0–100)</label>
                        <input className="gdr-input" type="number" min={0} max={100} value={form.integrita ?? 0}
                            onChange={e => setForm(f => ({ ...f, integrita: e.target.value }))} />
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Notorietà (0–100)</label>
                        <input className="gdr-input" type="number" min={0} max={100} value={form.notorieta ?? 0}
                            onChange={e => setForm(f => ({ ...f, notorieta: e.target.value }))} />
                    </div>
                    <button className="gdr-button gdr-btn-success" onClick={save} disabled={saving}>
                        {saving ? 'Salvataggio…' : 'Salva'}
                    </button>
                    {msg && <p style={{ color: '#8cba8c', marginTop: '6px', fontSize: '12px' }}>{msg}</p>}
                </>
            )}
        </div>
    )
}

// ── Crea PNG ──────────────────────────────────────────────────────────────────

function CreaPng() {
    const [newPng, setNewPng] = useState({ pngName: '', salute: 100, destrezza: 7, potere: 7, mente: 7, tempra: 7 })
    const [creating, setCreating]   = useState(false)
    const [createMsg, setCreateMsg] = useState('')

    function createPng() {
        if (!newPng.pngName.trim()) { setCreateMsg('Inserire il nome del PNG.'); return }
        setCreating(true)
        setCreateMsg('')
        fetch('pages/api_chat.php?op=newMasterPng', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(newPng),
        })
            .then(r => r.json())
            .then(d => {
                setCreateMsg(d.message ?? '')
                setCreating(false)
                if (d.success) {
                    setNewPng({ pngName: '', salute: 100, destrezza: 7, potere: 7, mente: 7, tempra: 7 })
                    window.dispatchEvent(new CustomEvent('png-created'))
                }
            })
    }

    return (
        <div className="gdr-card">
            <div className="gdr-card-title">Crea PNG</div>
            <div className="gdr-form-group">
                <label className="gdr-label">Nome PNG</label>
                <input className="gdr-input" value={newPng.pngName} placeholder="Nome PNG"
                    onChange={e => setNewPng(f => ({ ...f, pngName: e.target.value }))} />
            </div>
            <div className="gdr-form-group">
                <label className="gdr-label">Salute</label>
                <input className="gdr-input" type="number" min={1} max={200} value={newPng.salute}
                    onChange={e => setNewPng(f => ({ ...f, salute: +e.target.value }))} />
            </div>
            <div className="gdr-form-group">
                <label className="gdr-label">Destrezza</label>
                <input className="gdr-input" type="number" min={1} max={20} value={newPng.destrezza}
                    onChange={e => setNewPng(f => ({ ...f, destrezza: +e.target.value }))} />
            </div>
            <div className="gdr-form-group">
                <label className="gdr-label">Potere</label>
                <input className="gdr-input" type="number" min={1} max={20} value={newPng.potere}
                    onChange={e => setNewPng(f => ({ ...f, potere: +e.target.value }))} />
            </div>
            <div className="gdr-form-group">
                <label className="gdr-label">Mente</label>
                <input className="gdr-input" type="number" min={1} max={20} value={newPng.mente}
                    onChange={e => setNewPng(f => ({ ...f, mente: +e.target.value }))} />
            </div>
            <div className="gdr-form-group">
                <label className="gdr-label">Tempra</label>
                <input className="gdr-input" type="number" min={1} max={20} value={newPng.tempra}
                    onChange={e => setNewPng(f => ({ ...f, tempra: +e.target.value }))} />
            </div>
            <button className="gdr-button gdr-btn-success" onClick={createPng} disabled={creating}>
                {creating ? 'Aggiunta…' : 'Aggiungi PNG'}
            </button>
            {createMsg && <p style={{ color: '#8cba8c', marginTop: '6px', fontSize: '12px' }}>{createMsg}</p>}
        </div>
    )
}

// ── Gestione PNG ──────────────────────────────────────────────────────────────

function GestionePng() {
    const [pngList, setPngList]             = useState([])
    const [selectedPng, setSelectedPng]     = useState('')
    const [pgList, setPgList]               = useState([])
    const [message, setMessage]             = useState('')
    const [car, setCar]                     = useState('destrezza')
    const [selectedTargets, setTargets]     = useState([])
    const [damagePercent, setDamagePercent] = useState(100)
    const [sending, setSending]             = useState(false)
    const [sendMsg, setSendMsg]             = useState('')

    const loadPngList = useCallback(() => {
        fetch('pages/api_roleSession.php?op=getPngRolePlaying')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const list = d.png ?? []
                    setPngList(list)
                    setSelectedPng(prev => (list.includes(prev) ? prev : (list[0] ?? '')))
                }
            })
    }, [])

    useEffect(() => {
        loadPngList()
        fetch('pages/api_roleSession.php?op=getRolePgs', { method: 'POST' })
            .then(r => r.json())
            .then(d => { if (d.success) setPgList(d.users ?? []) })
    }, [loadPngList])

    // Aggiorna la lista quando CreaPng crea un nuovo PNG
    useEffect(() => {
        window.addEventListener('png-created', loadPngList)
        return () => window.removeEventListener('png-created', loadPngList)
    }, [loadPngList])

    function toggleTarget(name) {
        setTargets(prev => prev.includes(name) ? prev.filter(t => t !== name) : [...prev, name])
    }

    function sendAttack() {
        if (!selectedPng) { setSendMsg('Seleziona un PNG.'); return }
        if (selectedTargets.length === 0) { setSendMsg('Seleziona almeno un bersaglio.'); return }
        setSending(true)
        setSendMsg('')
        fetch('pages/api_chat.php?op=newMasterPngAttack', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                pngName:       selectedPng,
                pngMessage:    message,
                pngCar:        car,
                targets:       selectedTargets,
                damagePercent,
            }),
        })
            .then(r => r.json())
            .then(d => {
                setSendMsg(d.message ?? '')
                setSending(false)
                if (d.success) { setMessage(''); setTargets([]) }
            })
    }

    return (
        <div className="gdr-card">
            <div className="gdr-card-title">Gestione PNG</div>
            {pngList.length === 0 ? (
                <p style={{ fontSize: '12px', color: '#8f8f8f' }}>Nessun PNG attivo.</p>
            ) : (
                <>
                    <div className="gdr-form-group">
                        <label className="gdr-label">PNG</label>
                        <select className="gdr-select" value={selectedPng}
                            onChange={e => setSelectedPng(e.target.value)}>
                            {pngList.map(p => <option key={p} value={p}>{p}</option>)}
                        </select>
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Messaggio azione (opzionale)</label>
                        <textarea className="gdr-textarea" rows={2} value={message}
                            placeholder="Descrizione azione del PNG"
                            onChange={e => setMessage(e.target.value)} />
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Caratteristica</label>
                        <select className="gdr-select" value={car} onChange={e => setCar(e.target.value)}>
                            <option value="destrezza">Destrezza</option>
                            <option value="potere">Potere</option>
                            <option value="mente">Mente</option>
                            <option value="tempra">Tempra</option>
                        </select>
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">Bersagli</label>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px', marginTop: '6px' }}>
                            {pgList.map(name => (
                                <button
                                    key={name}
                                    type="button"
                                    onClick={() => toggleTarget(name)}
                                    style={{
                                        padding: '4px 10px',
                                        fontSize: '12px',
                                        borderRadius: '4px',
                                        border: '1px solid',
                                        cursor: 'pointer',
                                        background: selectedTargets.includes(name) ? '#4a7a4a' : '#1e2238',
                                        borderColor: selectedTargets.includes(name) ? '#6aaa6a' : '#3a3f5a',
                                        color: '#ddd',
                                    }}
                                >
                                    {name}
                                </button>
                            ))}
                            {pgList.length === 0 && (
                                <span style={{ fontSize: '12px', color: '#8f8f8f' }}>Nessun PG in role.</span>
                            )}
                        </div>
                    </div>
                    <div className="gdr-form-group">
                        <label className="gdr-label">% danno per bersaglio (1–100)</label>
                        <input className="gdr-input" type="number" min={1} max={100} value={damagePercent}
                            onChange={e => setDamagePercent(Math.max(1, Math.min(100, +e.target.value)))} />
                    </div>
                    <button className="gdr-button gdr-btn-success" onClick={sendAttack} disabled={sending}>
                        {sending ? 'Invio…' : 'Invia Attacco'}
                    </button>
                    {sendMsg && <p style={{ color: '#8cba8c', marginTop: '6px', fontSize: '12px' }}>{sendMsg}</p>}
                </>
            )}
        </div>
    )
}

// ── Attacchi PNG in sospeso ───────────────────────────────────────────────────

function AttacchiPngSospeso() {
    const [attacks, setAttacks] = useState([])
    const [loading, setLoading] = useState(false)

    const load = useCallback(() => {
        setLoading(true)
        fetch('pages/api_chat.php?op=pending_attacks_png')
            .then(r => r.json())
            .then(d => { if (d.success) setAttacks(d.attacks); setLoading(false) })
            .catch(() => setLoading(false))
    }, [])

    useEffect(() => { load() }, [load])

    function respond(id_fight, pngName, scelta) {
        fetch('pages/api_chat.php?op=risposta_immediata_png', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id_fight, pngName, scelta }),
        })
            .then(r => r.json())
            .then(() => load())
    }

    return (
        <div className="gdr-card">
            <div className="gdr-card-title">Attacchi PNG in sospeso</div>
            <button className="gdr-button" style={{ fontSize: '11px', marginBottom: '10px' }} onClick={load}>
                {loading ? 'Aggiornamento…' : 'Aggiorna'}
            </button>
            {!loading && attacks.length === 0 && (
                <p style={{ fontSize: '12px', color: '#8f8f8f' }}>Nessun attacco in sospeso.</p>
            )}
            {attacks.map(a => (
                <div key={`${a.id_fight}-${a.png}`} className="attack-prompt" style={{ marginBottom: '8px' }}>
                    <span className="attack-prompt-text">
                        <b>{a.attacker}</b> attacca <b>{a.png}</b> con <b>{a.car}</b> ({a.dice})
                    </span>
                    <div className="attack-prompt-buttons">
                        {a.choices.includes('dado') && (
                            <button type="button" className="attack-prompt-btn btn-dado"
                                onClick={() => respond(a.id_fight, a.png, 'dado')}>
                                🎲 Dado
                            </button>
                        )}
                        {a.choices.includes('subisce') && (
                            <button type="button" className="attack-prompt-btn btn-subisce"
                                onClick={() => respond(a.id_fight, a.png, 'subisce')}>
                                💔 Subisci
                            </button>
                        )}
                    </div>
                </div>
            ))}
        </div>
    )
}

// ── Componente principale ─────────────────────────────────────────────────────

/**
 * @param {boolean} props.isOpen  - Il pannello master è visibile
 * @param {Function} props.onClose - Callback per chiudere il pannello
 */
export default function MasterPanel({ isOpen, onClose }) {
    if (!isOpen) return null

    return createPortal(
        <div
            className="gdr-modal-overlay"
            style={{
                display:  'flex',
                position: 'fixed',
                top:      0,
                left:     '260px',
                right:    '260px',
                bottom:   0,
                zIndex:   1001,
            }}
        >
            <div className="gdr-panel-container">

                {/* Header */}
                <div className="gdr-header">
                    <div className="gdr-logo">Pannello Master</div>
                    <button className="gdr-close-btn" onClick={onClose}>&times;</button>
                </div>

                {/* Corpo scrollabile */}
                <div style={{ overflowY: 'auto', padding: '16px', flex: 1 }}>

                    {/* Chiudi turno */}
                    <button className="btn" id="endTurnMaster" onClick={() => window.closeTurn?.()}>
                        Chiudi turno
                    </button>

                    <br /><br />

                    {/* Modifica PG + Crea PNG + Gestione PNG + Attacchi PNG in sospeso */}
                    <div className="gdr-grid">
                        <ModificaPG />
                        <CreaPng />
                        <GestionePng />
                        <AttacchiPngSospeso />
                    </div>

                </div>
            </div>
        </div>,
        document.body
    )
}
