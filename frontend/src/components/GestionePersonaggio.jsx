/**
 * GestionePersonaggio.jsx — Gestione personaggi (Phase SPA, 2/3)
 *
 * Porta a React la vecchia pagina gestione_personaggio.inc.php: tabella
 * filtrabile + modale di modifica anagrafica/caratteristiche. Fetch:
 * api_personaggio.php (op=listPersonaggi, listRazze — nuovi; getUsr, savePg,
 * deletePg, resetPg, deleteEsiliati, checkVolto — gia' esistenti, riusati
 * senza modifiche tramite le funzioni globali di includes/personaggio.js).
 *
 * Il modale di modifica resta gestito imperativamente da personaggio.js
 * (popolaPgForm/savePgForm, invariate) invece di essere riscritto come form
 * React controllato — stesso pattern gia' usato da ChatShell.jsx con
 * chat.js per la modale "Modifica azione". Lo script viene iniettato dopo
 * il mount (DOMContentLoaded e' gia' scattato in navigazione SPA) e la sua
 * funzione di init viene richiamata esplicitamente, come
 * window.initChatListeners() in ChatShell.
 *
 * Il modale e' renderizzato con createPortal direttamente sotto <body> —
 * stesso scopo del vecchio teleport via appendChild in personaggio.js (che
 * ora non lo fa piu' in questo contesto), ma sicuro per React: un nodo
 * spostato a mano fuori dall'albero che React si aspetta puo' far fallire
 * removeChild allo smontaggio.
 *
 * Azioni Ripristina/Disattiva/Elimina/Elimina-esiliati: le funzioni in
 * personaggio.js ora chiamano window.refreshPgList() se definita (impostata
 * qui sotto) invece di window.location.reload() — nessun reload di pagina.
 *
 * Azione "Gestione skill": naviga via SPA verso gst_personaggio_skill
 * (migrata in fase 3) passando ?pg=Nome, letto da
 * GestioneSkillPersonaggio.jsx per la preselezione.
 *
 * Azione "Esilia": naviga via SPA verso gestione_esilio (migrata in fase 1)
 * passando ?pg=Nome, letto da GestioneEsilio.jsx per la preselezione.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import { createPortal } from 'react-dom'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

function versionedSrc(src) {
    const version = window.CT_ASSET_VERSIONS?.[src.split('/').pop()]
    return version ? `${src}?v=${version}` : src
}

function loadScriptOnce(src) {
    return new Promise((resolve) => {
        const finalSrc = versionedSrc(src)
        if (document.querySelector(`script[src="${finalSrc}"]`)) { resolve(); return }
        const s = document.createElement('script')
        s.src = finalSrc
        s.onload = resolve
        s.onerror = resolve
        document.body.appendChild(s)
    })
}

// Stessa semantica di hasPermesso() in custom_functions.inc.php: true se
// l'utente ha ALMENO uno dei ruoli richiesti.
function hasPermesso(perms, ruoliRichiesti) {
    return ruoliRichiesti.some(r => perms?.[r])
}

// Stessi due elenchi di gestione_personaggio.inc.php:6-20
const PERMESSI_AZIONI = {
    modifica:   ['admin', 'moderatore', 'master', 'custode'],
    skill:      ['admin'],
    cancella:   ['admin'],
    esilia:     ['admin', 'moderatore'],
    reset:      ['admin'],
    ripristina: ['admin', 'moderatore'],
}
const PERMESSI_SEZIONI = {
    anagrafica:   ['admin', 'moderatore'],
    area_admin:   ['admin'],
    area_master:  ['admin', 'master'],
    area_custodi: ['admin', 'master', 'custode'],
}

const FILTRI = [
    { value: 'tutti',       label: 'Tutti' },
    { value: 'inattivi',    label: 'Inattivi (5 mesi)' },
    { value: 'mai_entrati', label: 'Mai entrati' },
    { value: 'esiliati',    label: 'Esiliati' },
    { value: 'eliminati',   label: 'Eliminati' },
]

export default function GestionePersonaggio() {
    const [filtro, setFiltro]         = useState('tutti')
    const [cerca, setCerca]           = useState('')
    const [personaggi, setPersonaggi] = useState(null)
    const [perms, setPerms]           = useState(null)
    const [razze, setRazze]           = useState([])
    const [error, setError]           = useState(null)

    const login = window.CT_USER?.login ?? ''

    const fetchPersonaggi = useCallback((f) => {
        fetch('pages/api_personaggio.php?op=listPersonaggi&filtro=' + encodeURIComponent(f))
            .then(r => r.json())
            .then(d => {
                if (d.success) { setPersonaggi(d.personaggi); setPerms(d.perms) }
                else setError(d.message ?? 'Errore nel caricamento')
            })
            .catch(() => setError('Errore di rete'))
    }, [])

    useEffect(() => { fetchPersonaggi(filtro) }, [filtro, fetchPersonaggi])

    useEffect(() => {
        fetch('pages/api_personaggio.php?op=listRazze')
            .then(r => r.json())
            .then(d => { if (d.success) setRazze(d.razze) })
            .catch(() => {})
    }, [])

    // Refresh "soft" richiamato da ripristinaPg/disattivaPg/eliminaPg/eliminaEsiliati
    // in personaggio.js al posto di window.location.reload()
    useEffect(() => {
        window.refreshPgList = () => fetchPersonaggi(filtro)
        return () => { delete window.refreshPgList }
    }, [filtro, fetchPersonaggi])

    // Carica (se non gia' presente) personaggio.js e ri-attacca i suoi listener
    // (modale, submit form) ogni volta che la tabella/il modale vengono
    // ri-renderizzati con nuovi dati — idempotente, vedi initPersonaggioListeners.
    useEffect(() => {
        loadScriptOnce('/includes/personaggio.js').then(() => {
            window.initPersonaggioListeners?.()
        })
    }, [personaggi])

    if (error) {
        return <div className="pagina_gestione_mercato"><div className="error">{error}</div></div>
    }
    if (!personaggi) {
        return <div className="pagina_gestione_mercato"><div><i className="fas fa-spinner fa-spin" /> Caricamento…</div></div>
    }

    const personaggiFiltrati = cerca.trim() === ''
        ? personaggi
        : personaggi.filter(p => p.nome.toLowerCase().includes(cerca.trim().toLowerCase()))

    return (
        <div className="pagina_gestione_mercato">

            {/* ── Topbar ─────────────────────────────────────────────────── */}
            <div className="gp-topbar gp-topbar--personaggi">
                <div className="gp-topbar__left">
                    <button type="button" className="gp-back" title="Indietro" onClick={() => window.history.back()}>
                        <i className="fa-solid fa-chevron-left"></i>
                    </button>
                </div>

                <div className="gp-topbar__right">
                    <select value={filtro} onChange={e => setFiltro(e.target.value)}>
                        {FILTRI.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
                    </select>
                    <input
                        type="text"
                        className="gp-search"
                        placeholder="Cerca personaggio…"
                        value={cerca}
                        onChange={e => setCerca(e.target.value)}
                    />
                    {hasPermesso(perms, PERMESSI_AZIONI.reset) && (
                        <button className="btn-action btn-action--reset btn-action--icon" title="Reset punti di tutti i personaggi" onClick={() => window.resetPg([])}>
                            <i className="fa-solid fa-rotate"></i>
                        </button>
                    )}
                    {hasPermesso(perms, PERMESSI_AZIONI.cancella) && (
                        <button className="btn-action btn-action--delete btn-action--icon" title="Elimina definitivamente tutti gli esiliati" onClick={() => window.eliminaEsiliati()}>
                            <i className="fa-solid fa-trash"></i>
                        </button>
                    )}
                </div>
            </div>

            {/* ── Legenda icone azioni ──────────────────────────────────── */}
            <div className="gp-legend">
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--edit btn-action--icon"><i className="fa-solid fa-pen-to-square"></i></span>
                    <span className="gp-legend__text"><strong>Modifica</strong> — apre il pannello di modifica anagrafica/caratteristiche</span>
                </div>
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--skill btn-action--icon"><i className="fa-solid fa-wrench"></i></span>
                    <span className="gp-legend__text"><strong>Gestione skill</strong> — apre la pagina skill/talenti del personaggio</span>
                </div>
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--exile btn-action--icon"><i className="fa-solid fa-user-slash"></i></span>
                    <span className="gp-legend__text"><strong>Esilia</strong> — allontana il personaggio dal gioco <span className="gp-legend__confirm">richiede conferma</span></span>
                </div>
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--reset btn-action--icon"><i className="fa-solid fa-rotate"></i></span>
                    <span className="gp-legend__text"><strong>Reset punti</strong> — statistiche a 10, shin/skill/talenti/storico spese azzerati. Nella topbar agisce su tutti i personaggi <span className="gp-legend__confirm">richiede conferma</span></span>
                </div>
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--disable btn-action--icon"><i className="fa-solid fa-user-xmark"></i></span>
                    <span className="gp-legend__text"><strong>Disattiva</strong> — cancellazione logica (permessi=-1): azzera e scollega come il reset, ma è reversibile con Ripristina <span className="gp-legend__confirm">richiede conferma</span></span>
                </div>
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--delete btn-action--icon"><i className="fa-solid fa-trash"></i></span>
                    <span className="gp-legend__text"><strong>Elimina definitivamente</strong> — cancellazione fisica, irreversibile. Nella topbar elimina tutti gli esiliati <span className="gp-legend__confirm">richiede conferma</span></span>
                </div>
                <div className="gp-legend__item">
                    <span className="btn-action btn-action--restore btn-action--icon"><i className="fa-solid fa-rotate-left"></i></span>
                    <span className="gp-legend__text"><strong>Ripristina</strong> — riattiva un personaggio con cancellazione soft (permessi=0). Non annulla il reset/scollegamento da razza-gilda-mestiere fatto alla cancellazione <span className="gp-legend__confirm">richiede conferma</span></span>
                </div>
            </div>

            {/* ── Lista personaggi ──────────────────────────────────────── */}
            <div className="gp-list">
                <table id="pgTable" className="gp-table--personaggi">
                    <thead>
                        <tr>
                            <th className="gp-th-avatar"></th>
                            <th className="gp-th-name">Personaggio</th>
                            <th className="gp-th-meta">Razza</th>
                            <th className="gp-th-meta">Mestiere</th>
                            <th className="gp-th-esilio">Esilio</th>
                            <th className="gp-th-actions">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        {personaggiFiltrati.map(pg => (
                            <tr key={pg.nome} className={pg.isExiled ? 'gp-row--exiled' : ''}>

                                <td className="gp-cell--avatar">
                                    {pg.url_img_chat
                                        ? <img src={pg.url_img_chat} alt="" width="32" height="32" loading="lazy" />
                                        : <span className="gp-avatar-placeholder"><i className="fa-solid fa-user"></i></span>
                                    }
                                </td>

                                <td className="gp-cell--name">
                                    <a
                                        href={`main.php?page=scheda&pg=${encodeURIComponent(pg.nome)}`}
                                        onClick={e => { e.preventDefault(); navigate(`main.php?page=scheda&pg=${encodeURIComponent(pg.nome)}`) }}
                                    >
                                        {pg.nome}
                                    </a>
                                    {pg.cognome && <span className="gp-cognome">{pg.cognome}</span>}
                                </td>

                                <td className="gp-cell--meta">{pg.nome_gilda ?? '—'}</td>
                                <td className="gp-cell--meta">{pg.nome_mestiere ?? '—'}</td>

                                <td className="gp-cell--esilio">
                                    {pg.isExiled && (
                                        <span className="gp-badge gp-badge--exile">
                                            <i className="fa-solid fa-ban"></i> {pg.esilioFormattato}
                                        </span>
                                    )}
                                </td>

                                <td className="gp-cell--actions">
                                    <div className="gp-actions">

                                        {hasPermesso(perms, PERMESSI_AZIONI.modifica) && (
                                            <button className="btn-action btn-action--edit btn-action--icon" title="Modifica" onClick={() => window.popolaPgForm(pg.nome)}>
                                                <i className="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        )}

                                        {hasPermesso(perms, PERMESSI_AZIONI.skill) && (
                                            <button
                                                className="btn-action btn-action--skill btn-action--icon"
                                                title="Gestione skill"
                                                onClick={() => navigate(`main.php?page=gst_personaggio_skill&pg=${encodeURIComponent(pg.nome)}`)}
                                            >
                                                <i className="fa-solid fa-wrench"></i>
                                            </button>
                                        )}

                                        {hasPermesso(perms, PERMESSI_AZIONI.esilia) && pg.nome !== login && (
                                            <button
                                                className="btn-action btn-action--exile btn-action--icon"
                                                title="Esilia"
                                                onClick={() => {
                                                    if (window.confirm(`Esiliare ${pg.nome}?`)) {
                                                        navigate(`main.php?page=gestione_esilio&pg=${encodeURIComponent(pg.nome)}`)
                                                    }
                                                }}
                                            >
                                                <i className="fa-solid fa-user-slash"></i>
                                            </button>
                                        )}

                                        {hasPermesso(perms, PERMESSI_AZIONI.reset) && (
                                            <button className="btn-action btn-action--reset btn-action--icon" title="Reset punti" onClick={() => window.resetPg([{ nome: pg.nome }])}>
                                                <i className="fa-solid fa-rotate"></i>
                                            </button>
                                        )}

                                        {!pg.isDeleted && hasPermesso(perms, PERMESSI_AZIONI.cancella) && pg.nome !== login && (
                                            <button className="btn-action btn-action--disable btn-action--icon" title="Disattiva (cancellazione logica, reversibile con Ripristina)" onClick={() => window.disattivaPg(pg.nome)}>
                                                <i className="fa-solid fa-user-xmark"></i>
                                            </button>
                                        )}

                                        {hasPermesso(perms, PERMESSI_AZIONI.cancella) && pg.nome !== login && (
                                            <button className="btn-action btn-action--delete btn-action--icon" title="Elimina definitivamente (cancellazione fisica, irreversibile)" onClick={() => window.eliminaPg(pg.nome)}>
                                                <i className="fa-solid fa-trash"></i>
                                            </button>
                                        )}

                                        {pg.isDeleted && hasPermesso(perms, PERMESSI_AZIONI.ripristina) && (
                                            <button className="btn-action btn-action--restore btn-action--icon" title="Ripristina" onClick={() => window.ripristinaPg(pg.nome)}>
                                                <i className="fa-solid fa-rotate-left"></i>
                                            </button>
                                        )}

                                    </div>
                                </td>

                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* ── Modale modifica personaggio (createPortal: vedi commento in testa al file) ── */}
            {createPortal(
                <div className="pg-edit-container" id="pg_edit_container" role="dialog" aria-modal="true">
                    <div className="modal-content">

                        <div className="gp-modal-header">
                            <h2 className="gp-modal-title">
                                <i className="fa-solid fa-pen-to-square"></i>
                                Modifica — <span id="gp-modal-pg-name">…</span>
                            </h2>
                            <div className="gp-modal-header-actions">
                                <button type="button" className="gp-scroll-btn" title="Vai in cima" onClick={() => window.gpScrollForm?.('top')}>▲</button>
                                <button type="button" className="gp-scroll-btn" title="Vai in fondo" onClick={() => window.gpScrollForm?.('bottom')}>▼</button>
                                <button type="button" className="gp-modal-close" id="closePgModal" aria-label="Chiudi">✕</button>
                            </div>
                        </div>

                        <form id="formSavePg" method="post">

                            {hasPermesso(perms, PERMESSI_SEZIONI.anagrafica) && (
                                <div className="form-section">
                                    <h3 className="section-title">Anagrafica</h3>

                                    {hasPermesso(perms, PERMESSI_SEZIONI.area_admin) && (
                                        <>
                                            <div className="form-row">
                                                <div className="form-group form-column">
                                                    <label htmlFor="nome">Nome</label>
                                                    <input type="text" id="nome" name="nome" required />
                                                </div>
                                                <div className="form-group form-column">
                                                    <label htmlFor="razza">Razza</label>
                                                    <select id="razza" name="razza" defaultValue="0">
                                                        <option value="0">Nessuna</option>
                                                        {razze.map(r => <option key={r.id_razza} value={r.id_razza}>{r.nome_razza}</option>)}
                                                    </select>
                                                </div>
                                            </div>
                                            <div className="form-row">
                                                <div className="form-group form-column">
                                                    <label htmlFor="eta">Età</label>
                                                    <input type="number" id="eta" name="eta" />
                                                </div>
                                                <div className="form-group form-column">
                                                    <label htmlFor="email">E-mail</label>
                                                    <input type="email" id="email" name="email" />
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    <div className="form-row">
                                        <div className="form-group form-column">
                                            <label htmlFor="cognome">Cognome</label>
                                            <input type="text" id="cognome" name="cognome" />
                                        </div>
                                        <div className="form-group form-column">
                                            <label htmlFor="luogo">Luogo di nascita</label>
                                            <input type="text" id="luogo" name="luogo" />
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group form-column">
                                            <label htmlFor="volto">Volto personaggio</label>
                                            <div className="input-with-button">
                                                <input type="text" id="volto" name="volto" />
                                                <button type="button" onClick={() => window.checkVolto?.()}>Controlla</button>
                                            </div>
                                        </div>
                                        <div className="form-group form-column gp-checkbox-field">
                                            <label htmlFor="suoni">Disabilita suoni</label>
                                            <input type="checkbox" id="suoni" name="suoni" value="1" />
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group form-column">
                                            <label htmlFor="musica">Musica scheda (URL)</label>
                                            <input type="url" id="musica" name="musica" />
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group form-column">
                                            <label htmlFor="img">URL immagine profilo</label>
                                            <input type="url" id="img" name="img" />
                                        </div>
                                        <div className="form-group form-column">
                                            <label htmlFor="imgchat">URL immagine chat</label>
                                            <input type="url" id="imgchat" name="imgchat" />
                                        </div>
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="background">Background</label>
                                        <textarea id="background" name="background"></textarea>
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="storia">Storia</label>
                                        <textarea id="storia" name="storia"></textarea>
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="dice">Dice di sé</label>
                                        <textarea id="dice" name="dice"></textarea>
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="off">Off</label>
                                        <textarea id="off" name="off"></textarea>
                                    </div>
                                </div>
                            )}

                            {(hasPermesso(perms, PERMESSI_SEZIONI.area_custodi) || hasPermesso(perms, PERMESSI_SEZIONI.area_master)) && (
                                <div className="form-section">
                                    <h3 className="section-title">Caratteristiche</h3>

                                    {hasPermesso(perms, PERMESSI_SEZIONI.area_custodi) && (
                                        <div className="form-row">
                                            <div className="form-group form-column">
                                                <label htmlFor="salute">Salute <span className="gp-label-note" id="salute-max-note"></span></label>
                                                <input type="number" id="salute" name="salute" min="0" />
                                            </div>
                                            <div className="form-group form-column">
                                                <label htmlFor="integrita">Integrità <span className="gp-label-note" id="integrita-max-note"></span></label>
                                                <input type="number" id="integrita" name="integrita" min="0" />
                                            </div>
                                        </div>
                                    )}

                                    {hasPermesso(perms, PERMESSI_SEZIONI.area_master) && (
                                        <>
                                            <div className="form-row">
                                                <div className="form-group form-column">
                                                    <label htmlFor="shin">Punti shin</label>
                                                    <input type="number" id="shin" name="shin" disabled />
                                                </div>
                                                <div className="form-group form-column">
                                                    <label htmlFor="soldi">Soldi</label>
                                                    <input type="number" id="soldi" name="soldi" />
                                                </div>
                                            </div>
                                            <div className="form-row">
                                                <div className="form-group form-column">
                                                    <label htmlFor="notorieta">Notorietà</label>
                                                    <input type="number" id="notorieta" name="notorieta" />
                                                </div>
                                                <div className="form-group form-column">
                                                    <label htmlFor="mestiere_punti">Punti Mestiere</label>
                                                    <input type="number" id="mestiere_punti" name="mestiere_punti" min="0" max="100" />
                                                </div>
                                            </div>
                                            <div className="form-group">
                                                <label htmlFor="note_master">Note del Master</label>
                                                <textarea id="note_master" name="note_master"></textarea>
                                            </div>
                                            <div className="form-group">
                                                <label htmlFor="particolari">Particolari</label>
                                                <textarea id="particolari" name="particolari"></textarea>
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}

                            <input type="hidden" name="personaggio" id="personaggio" />

                        </form>

                        <div className="gp-modal-footer">
                            <button type="submit" id="gp-save-btn" form="formSavePg" className="btn-action btn-action--edit">
                                <i className="fa-solid fa-floppy-disk"></i> Salva modifiche
                            </button>
                        </div>

                    </div>
                </div>,
                document.body
            )}

        </div>
    )
}
