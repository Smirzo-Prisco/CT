/**
 * GestioneLuoghiMestiere.jsx — Pannello admin associazione mestiere -> luogo
 *
 * Sostituisce gli array/controlli hardcoded sparsi nel codice che legano un
 * mestiere a una stanza specifica della chat:
 *   - $craft_locations in includes/chat_functions.inc.php (bonus punti mestiere)
 *   - $_SESSION['luogo'] == 24 in pages/oggetto_assegna_chat.inc.php (mercato)
 *   - $_SESSION['luogo'] == 25 in pages/api_chat.php (cura in ospedale)
 * Un mestiere ha al massimo un luogo associato (relazione 1:1, come in tutti
 * i casi hardcoded trovati) — persistito in mestiere.id_luogo.
 *
 * API: pages/api_mestieri.php (op=list, luoghi, mestiere_set_luogo)
 */

import { useState, useEffect, useCallback } from 'react'

const API = '/pages/api_mestieri.php'

function RigaMestiere({ mestiere, luoghi, onSaved }) {
    const [idLuogo, setIdLuogo] = useState(mestiere.id_luogo ?? '')
    const [saving, setSaving]   = useState(false)
    const [saved, setSaved]     = useState(false)
    const [error, setError]     = useState(null)

    const salva = async () => {
        setSaving(true)
        setError(null)
        setSaved(false)
        const fd = new FormData()
        fd.append('id_mestiere', mestiere.id_mestiere)
        fd.append('id_luogo', idLuogo)
        const r = await fetch(`${API}?op=mestiere_set_luogo`, { method: 'POST', body: fd })
        if (r.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
        const d = await r.json()
        setSaving(false)
        if (d.success) {
            onSaved(mestiere.id_mestiere, idLuogo === '' ? null : Number(idLuogo))
            setSaved(true)
            setTimeout(() => setSaved(false), 2500)
        } else {
            setError(d.message ?? 'Errore nel salvataggio')
        }
    }

    return (
        <tr>
            <td className="gp-cell--name">{mestiere.nome}</td>
            <td>
                <select value={idLuogo} onChange={e => setIdLuogo(e.target.value)}>
                    <option value="">— nessuno —</option>
                    {luoghi.map(l => <option key={l.id} value={l.id}>{l.nome}</option>)}
                </select>
            </td>
            <td className="gp-cell--actions">
                <div className="gp-actions">
                    <button className="btn-action btn-action--edit btn-action--icon" title="Salva" onClick={salva} disabled={saving}>
                        <i className={`fa-solid ${saved ? 'fa-check' : 'fa-floppy-disk'}`}></i>
                    </button>
                </div>
                {error && <div className="gm-feedback gm-feedback--error">{error}</div>}
            </td>
        </tr>
    )
}

export default function GestioneLuoghiMestiere() {
    const [mestieri, setMestieri] = useState([])
    const [luoghi, setLuoghi]     = useState([])
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)

    const carica = useCallback(async () => {
        setLoading(true)
        try {
            const [rList, rLuoghi] = await Promise.all([
                fetch(`${API}?op=list`),
                fetch(`${API}?op=luoghi`),
            ])
            if (rList.status === 403 || rLuoghi.status === 403) { window.CT.navigate('main.php?page=mappaclick'); return }
            const dList   = await rList.json()
            const dLuoghi = await rLuoghi.json()
            setLoading(false)
            if (dList.success && dLuoghi.success) {
                setMestieri(dList.mestieri)
                setLuoghi(dLuoghi.luoghi)
            } else {
                setError(dList.message ?? dLuoghi.message ?? 'Errore nel caricamento')
            }
        } catch {
            setLoading(false)
            setError('Errore di rete')
        }
    }, [])

    useEffect(() => { carica() }, [carica])

    const onSaved = (idMestiere, idLuogo) => {
        setMestieri(prev => prev.map(m => m.id_mestiere === idMestiere ? { ...m, id_luogo: idLuogo } : m))
    }

    return (
        <div className="pagina_gestione_gilde">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" onClick={() => window.history.back()} className="gp-back" title="Indietro">
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
                <div className="gp-topbar__center">
                    <span className="gp-title">Luoghi Mestiere</span>
                </div>
            </div>

            {error && <div className="gm-feedback gm-feedback--error" style={{ margin: '12px' }}>{error}</div>}

            <p className="gp-label-note" style={{ margin: '12px' }}>
                Il luogo associato determina dove le azioni di un personaggio con questo mestiere
                contano ai fini dei meccanismi automatici (bonus punti mestiere, mercato, cure in ospedale).
            </p>

            <div className="gp-list">
                <table>
                    <thead>
                        <tr>
                            <th>Mestiere</th>
                            <th>Luogo associato</th>
                            <th className="gp-th-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={3} style={{ textAlign: 'center', padding: 20 }}>Caricamento…</td></tr>
                        ) : mestieri.length === 0 ? (
                            <tr><td colSpan={3} style={{ textAlign: 'center', padding: 20, fontStyle: 'italic', color: 'var(--color-text-muted)' }}>Nessun mestiere trovato.</td></tr>
                        ) : mestieri.map(m => (
                            <RigaMestiere key={m.id_mestiere} mestiere={m} luoghi={luoghi} onSaved={onSaved} />
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    )
}
