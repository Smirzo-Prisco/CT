/**
 * GestioneEsilio.jsx — Esilia/Grazia un personaggio (Phase SPA)
 *
 * Porta a React la vecchia pagina gestione_esilio.inc.php, invariata nella
 * logica: dropdown con due gruppi (attivi/esiliati) → form data+causale se il
 * pg non è esiliato, pulsante Grazia se lo è. API: api_personaggio.php
 * (op=getPgPerEsilio, getEsilioStatus, exilePg, graziaPg).
 *
 * Supporta un pg preselezionato via ?pg=Nome nell'URL (usato dall'icona
 * "Esilia" di GestionePersonaggio.jsx), ma resta utilizzabile anche da sola
 * con selezione manuale, come la pagina originale.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

export default function GestioneEsilio() {
    const [elenco, setElenco]       = useState(null)
    const [error, setError]         = useState(null)
    const [selezionato, setSelezionato] = useState(() => new URLSearchParams(window.location.search).get('pg') ?? '')
    const [stato, setStato]         = useState(null)
    const [caricandoStato, setCaricandoStato] = useState(false)
    const [messaggio, setMessaggio] = useState(null)

    const oggi = new Date()
    const [giorno, setGiorno] = useState(oggi.getDate())
    const [mese, setMese]     = useState(oggi.getMonth() + 1)
    const [anno, setAnno]     = useState(oggi.getFullYear())
    const [causale, setCausale] = useState('')

    useEffect(() => {
        fetch('pages/api_personaggio.php?op=getPgPerEsilio')
            .then(r => r.json())
            .then(d => {
                if (d.success) setElenco(d)
                else setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => setError('Errore di rete'))
    }, [])

    const caricaStato = useCallback((nome) => {
        if (!nome) { setStato(null); return }
        setCaricandoStato(true)
        fetch('pages/api_personaggio.php?op=getEsilioStatus&name=' + encodeURIComponent(nome))
            .then(r => r.json())
            .then(d => {
                if (d.success) setStato(d)
                else setError(d.message ?? 'Errore')
                setCaricandoStato(false)
            })
            .catch(() => { setError('Errore di rete'); setCaricandoStato(false) })
    }, [])

    useEffect(() => {
        if (selezionato) caricaStato(selezionato)
    }, [selezionato, caricaStato])

    function handleEsilia(e) {
        e.preventDefault()
        fetch('pages/api_personaggio.php?op=exilePg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pg: selezionato, year: anno, month: mese, day: giorno, causale })
        })
            .then(r => r.json())
            .then(d => {
                setMessaggio(d.message)
                if (d.success) caricaStato(selezionato)
            })
            .catch(() => setMessaggio('Errore di rete'))
    }

    function handleGrazia() {
        if (!window.confirm(`Graziare ${selezionato}?`)) return
        fetch('pages/api_personaggio.php?op=graziaPg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pg: selezionato })
        })
            .then(r => r.json())
            .then(d => {
                setMessaggio(d.message)
                if (d.success) caricaStato(selezionato)
            })
            .catch(() => setMessaggio('Errore di rete'))
    }

    if (error)   return <div className="pagina_gestione_mercato"><div className="error">{error}</div></div>
    if (!elenco) return <div className="pagina_gestione_mercato"><div><i className="fas fa-spinner fa-spin" /> Caricamento…</div></div>

    return (
        <div className="pagina_gestione_mercato">
            <div className="gp-topbar">
                <div className="gp-topbar__left">
                    <button type="button" className="gp-back" title="Indietro" onClick={() => navigate('main.php?page=gestione_personaggio')}>
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
            </div>

            <div className="panels_box">
                <div className="panels_box">
                    <div className="form_label">Seleziona personaggio</div>
                    <div className="form_field">
                        <select value={selezionato} onChange={e => setSelezionato(e.target.value)}>
                            <option value="">—</option>
                            <optgroup label="Personaggi">
                                {elenco.attivi.map(nome => <option key={nome} value={nome}>{nome}</option>)}
                            </optgroup>
                            <optgroup label="Esiliati">
                                {elenco.esiliati.map(nome => <option key={nome} value={nome}>{nome}</option>)}
                            </optgroup>
                        </select>
                    </div>
                </div>

                {messaggio && <div className="warning">{messaggio}</div>}

                {selezionato && caricandoStato && <div>Caricamento…</div>}

                {selezionato && !caricandoStato && stato && stato.esilio == null && (
                    <form className="form_gestione" onSubmit={handleEsilia}>
                        <div className="form_label">Esilia/Imprigiona personaggio</div>
                        <div className="form_field">
                            <select className="day" value={giorno} onChange={e => setGiorno(e.target.value)}>
                                {Array.from({ length: 31 }, (_, i) => i + 1).map(d => <option key={d} value={d}>{d}</option>)}
                            </select>
                            <select className="month" value={mese} onChange={e => setMese(e.target.value)}>
                                {Array.from({ length: 12 }, (_, i) => i + 1).map(m => <option key={m} value={m}>{m}</option>)}
                            </select>
                            <select className="year" value={anno} onChange={e => setAnno(e.target.value)}>
                                {Array.from({ length: 21 }, (_, i) => oggi.getFullYear() + i).map(y => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </div>
                        <div className="form_label">Motivo</div>
                        <div className="form_field">
                            <input value={causale} onChange={e => setCausale(e.target.value)} />
                        </div>
                        <div className="form_submit">
                            <button type="submit">Conferma</button>
                        </div>
                    </form>
                )}

                {selezionato && !caricandoStato && stato && stato.esilio != null && (
                    <form className="form_gestione" onSubmit={e => { e.preventDefault(); handleGrazia() }}>
                        <div className="form_label">Grazia <b>{selezionato}</b></div>
                        <div className="form_submit">
                            <button type="submit">Grazia {selezionato}</button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    )
}
