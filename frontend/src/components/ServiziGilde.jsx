/**
 * ServiziGilde.jsx — Famiglie e mestieri del gioco (Phase 3: SPA)
 *
 * Vista lista:
 *   - Tabella famiglie: nome (→ dettaglio), icona statuto (→ statuto_main.php top-frame), iscritti
 *   - Sezione mestieri: tabella per tipo con nome (→ servizi_mestieri PHP) e icona statuto
 *
 * Vista dettaglio famiglia:
 *   - Tabella ruoli, tabella affiliati con link scheda, testo statuto inline
 *
 * Fix statuto: i link usano window.CT.navigate invece di target="_blank",
 * così statuto_main.php viene aperto nel frame principale (che include
 * header.inc.php e il bundle React) invece di una nuova scheda isolata.
 *
 * API: api_servizi_gilde.php (op=getList, op=getGuild)
 */

import { useState, useEffect, useMemo } from 'react'
import shared from './shared.module.css'

// ── Navigazione ───────────────────────────────────────────────────────────────

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ── Componente lista ──────────────────────────────────────────────────────────

function GildeTable({ gilde, onSelectGilda }) {
    return (
        <table className="customTable" style={{ width: '100%' }}>
            <tbody>
                <tr className="second_header">
                    <td style={{ width: '60%' }}><div>RAZZE</div></td>
                    <td><div>Statuto</div></td>
                    <td><div>Iscritti</div></td>
                </tr>
                {gilde.map(g => (
                    <tr key={g.id}>
                        <td style={{ width: '60%' }}>
                            <div>
                                <a
                                    href="#"
                                    onClick={e => { e.preventDefault(); onSelectGilda(g.id) }}
                                >
                                    {g.nome}
                                </a>
                            </div>
                        </td>
                        <td>
                            {/* Naviga nel top-frame invece di aprire una nuova scheda:
                                statuto_main.php include header.inc.php e funziona solo
                                nel contesto di navigazione principale, non in una scheda vuota */}
                            <a
                                href="#"
                                onClick={e => { e.preventDefault(); navigate(`main.php?page=statuto_main&id=${g.id}`) }}
                                title="Statuto"
                            >
                                <i className="fa-solid fa-scroll" />
                            </a>
                        </td>
                        <td>
                            <div className={shared.schedaSerial}>
                                {g.count}
                            </div>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    )
}

function MestieriSection({ mestieri }) {
    // Raggruppa per tipo preservando l'ordine
    const grouped = useMemo(() => {
        const map = []
        const seen = {}
        mestieri.forEach(m => {
            if (!seen[m.tipo]) {
                seen[m.tipo] = true
                map.push({ tipo: m.tipo, desc: m.tipo_desc, items: [] })
            }
            map[map.length - 1].items.push(m)
        })
        return map
    }, [mestieri])

    return (
        <>
            <div className="titolo" style={{ textAlign: 'center', margin: '16px 0 8px' }}>
                <img src="/themes/crystal/imgs/mestieri/titolo_mestieri.png" alt="Mestieri" />
            </div>
            <table className="customTable" style={{ width: '100%' }}>
                <tbody>
                    {grouped.map(group => (
                        <>
                            <tr key={`h-${group.tipo}`} className="second_header">
                                <td><div>Mestiere</div></td>
                                <td><div>Statuto</div></td>
                                <td><div>Iscritti</div></td>
                            </tr>
                            {group.items.map(m => (
                                <tr key={m.id}>
                                    <td style={{ width: '60%' }}>
                                        <div>
                                            <a
                                                href="#"
                                                onClick={e => {
                                                    e.preventDefault()
                                                    navigate(`main.php?page=servizi_mestieri&id_mestiere=${m.id}`)
                                                }}
                                            >
                                                {m.nome}
                                            </a>
                                        </div>
                                    </td>
                                    <td style={{ width: '20%' }}>
                                        {m.url_sito && (
                                            <a
                                                href="#"
                                                onClick={e => {
                                                    e.preventDefault()
                                                    navigate(`main.php?page=statuto_main&id2=${m.id}`)
                                                }}
                                                title="Statuto"
                                            >
                                                <i className="fa-solid fa-scroll" />
                                            </a>
                                        )}
                                    </td>
                                    <td>
                                        <div className={shared.schedaSerial}>
                                            {m.count}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </>
                    ))}
                </tbody>
            </table>
        </>
    )
}

// ── Componente dettaglio famiglia ─────────────────────────────────────────────

function GuildDetail({ gildaId, onBack }) {
    const [data, setData]       = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState(null)

    useEffect(() => {
        setLoading(true)
        fetch(`pages/api_servizi_gilde.php?op=getGuild&id_gilda=${gildaId}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setData(d)
                else setError(d.message ?? 'Errore caricamento')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [gildaId])

    if (loading) return <p><i className="fas fa-spinner fa-spin" /> Caricamento…</p>
    if (error)   return <p className="error">Errore: {error}</p>

    const { gilda, ruoli, affiliati } = data

    return (
        <div>
            {/* Ruoli */}
            <table className="customTable" style={{ width: '100%' }}>
                <tbody>
                    <tr className="second_header">
                        <td><div>&nbsp;</div></td>
                        <td><div>GRADO</div></td>
                    </tr>
                    {ruoli.map((r, i) => (
                        <tr key={i}>
                            <td><div><img src={`imgs/guilds/${r.immagine}`} alt="" /></div></td>
                            <td>
                                <div className={shared.schedaSerial}>
                                    {r.nome_ruolo}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <br /><br />

            {/* Affiliati */}
            <table className="customTable" style={{ width: '100%' }}>
                <tbody>
                    <tr className="second_header">
                        <td><div>&nbsp;</div></td>
                        <td><div>MEMBRI</div></td>
                        <td><div>GRADO</div></td>
                    </tr>
                    {affiliati.map((a, i) => (
                        <tr key={i}>
                            <td><div><img src={`imgs/guilds/${a.immagine}`} alt="" /></div></td>
                            <td>
                                <div>
                                    <a
                                        href="#"
                                        onClick={e => {
                                            e.preventDefault()
                                            navigate(`main.php?page=scheda&pg=${encodeURIComponent(a.nome)}`)
                                        }}
                                        style={a.special ? { color: '#9a6353' } : undefined}
                                    >
                                        {a.nome}{a.cognome ? ` ${a.cognome}` : ''}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div className={shared.schedaSerial}>
                                    {a.nickname ?? a.nome_ruolo}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Statuto inline (BBCode già processato lato server) */}
            {gilda.statuto && (
                <table style={{ marginTop: '16px', width: '100%' }}>
                    <tbody>
                        <tr className="second_header">
                            <td colSpan={4}><div>Statuto</div></td>
                        </tr>
                        <tr>
                            <td>
                                <div
                                    style={{ textAlign: 'justify' }}
                                    dangerouslySetInnerHTML={{ __html: gilda.statuto }}
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            )}

            {/* Torna alla lista */}
            <div className="panels_link" style={{ marginTop: '16px' }}>
                <a href="#" onClick={e => { e.preventDefault(); onBack() }}>
                    Torna indietro
                </a>
            </div>
        </div>
    )
}

// ── Componente principale ─────────────────────────────────────────────────────

export default function ServiziGilde() {
    const [loading, setLoading]         = useState(true)
    const [error, setError]             = useState(null)
    const [gilde, setGilde]             = useState([])
    const [mestieri, setMestieri]       = useState([])
    const [selectedGildaId, setSelected] = useState(null)

    useEffect(() => {
        fetch('pages/api_servizi_gilde.php?op=getList')
            .then(r => r.json())
            .then(d => {
                if (d.success) { setGilde(d.gilde); setMestieri(d.mestieri) }
                else setError(d.message ?? 'Errore caricamento')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    if (loading) return <p><i className="fas fa-spinner fa-spin" /> Caricamento…</p>
    if (error)   return <p className="error">Errore: {error}</p>

    if (selectedGildaId !== null) {
        return (
            <div className="pagina_servizi_gilde">
                <div className="page_body">
                    <GuildDetail
                        gildaId={selectedGildaId}
                        onBack={() => setSelected(null)}
                    />
                </div>
            </div>
        )
    }

    return (
        <div className="pagina_servizi_gilde">
            <div className="page_body">

                {/* Link ambientazione */}
                <table className="customTable" style={{ width: '100%', marginBottom: '16px' }}>
                    <tbody>
                        <tr>
                            <td>
                                <div>
                                    <a
                                        className="table-title"
                                        href="#"
                                        onClick={e => { e.preventDefault(); navigate('main.php?page=documentazione_main') }}
                                    >
                                        AMBIENTAZIONE &amp; REGOLAMENTO
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <GildeTable gilde={gilde} onSelectGilda={setSelected} />
                <br />
                <MestieriSection mestieri={mestieri} />

            </div>
        </div>
    )
}
