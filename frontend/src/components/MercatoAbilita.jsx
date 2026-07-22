/**
 * MercatoAbilita.jsx — Mercato abilità (wrapper per mercato_abilita.js).
 *
 * Renderizza lo scheletro HTML che mercato_abilita.js si aspetta di trovare
 * nel DOM, poi carica lo script. Al mount, se lo script è già in pagina
 * lo script viene comunque rieseguito per popolare il pannello.
 *
 * Stili già presenti in ct-styles.css (compilati da _mercato_abilita.scss).
 *
 * @author Crystal Tokyo Dev
 */

import { useEffect } from 'react'

/** Naviga via CT.navigate (SPA) se disponibile, altrimenti reload. */
function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

/**
 * Aggiunge ?v=<mtime> (da window.CT_ASSET_VERSIONS, iniettato in header.inc.php)
 * per far capire al browser che il file è cambiato: nginx mette in cache questi
 * script per 1 anno assumendo URL versionati, altrimenti gli aggiornamenti non
 * arrivano mai ai client con la cache calda.
 */
function versionedSrc(src) {
    const version = window.CT_ASSET_VERSIONS?.[src.split('/').pop()]
    return version ? `${src}?v=${version}` : src
}

/** Carica uno script nel DOM; resolve subito se già presente. */
function loadScriptOnce(src) {
    return new Promise((resolve) => {
        const finalSrc = versionedSrc(src)
        const existing = document.querySelector(`script[src="${finalSrc}"]`)
        if (existing) {
            // Script già caricato: rimuoviamolo e ricarichiamo per rieseguirlo
            existing.remove()
        }
        const s     = document.createElement('script')
        s.src       = finalSrc
        s.onload    = resolve
        s.onerror   = resolve
        document.body.appendChild(s)
    })
}

export default function MercatoAbilita() {
    useEffect(() => {
        loadScriptOnce('/includes/mercato_abilita.js')
    }, [])

    return (
        <>
            <div className="link_back" style={{ textAlign: 'left' }}>
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>
            <div id="skillPanel">
                <div className="sticky-header">
                    <div className="skill-info-bar">
                        <div>Punti Shin: <span id="skillPoints">0</span></div>
                        <div>
                            Ogni livello di un'abilità richiede la spesa di un punto shin.
                            <br />
                            Il livello di un'abilità non può essere superiore al livello del personaggio
                        </div>
                        <div>Livello PG: <span id="playerLevel">0</span></div>
                    </div>
                    <div className="save-bar">
                        <button id="btnSave" className="btn-save" onClick={() => window.saveSkill?.()}>
                            💾 Salva Skill
                        </button>
                        <button id="btnReset" className="btn-save" style={{ display: 'none' }} onClick={() => window.resetSkills?.()}>
                            Reset
                        </button>
                    </div>
                </div>
                <div className="skill-accordion">
                    <div className="accordion-item">
                        <button className="accordion-header">🛡️ Difensiva</button>
                        <div className="accordion-body" data-cat="Default"></div>
                    </div>
                    <div className="accordion-item">
                        <button className="accordion-header">✨ Speciale</button>
                        <div className="accordion-body" data-cat="Speciale"></div>
                    </div>
                    <div className="accordion-item">
                        <button className="accordion-header">📘 Generica</button>
                        <div className="accordion-body" data-cat="Generica"></div>
                    </div>
                    <div className="accordion-item">
                        <button className="accordion-header">🗡️ Attacco</button>
                        <div className="accordion-body" data-cat="Attacco"></div>
                    </div>
                    <div className="accordion-item">
                        <button className="accordion-header">🧠 Mentale</button>
                        <div className="accordion-body" data-cat="Mentale"></div>
                    </div>
                </div>
            </div>
            <div id="skill-modal" className="skill-modal">
                <div className="skill-modal-content">
                    <h2 id="skill-modal-titolo"></h2>
                    <div id="skill-modal-descrizione"></div>
                    <button id="close-modal">Chiudi</button>
                </div>
            </div>
        </>
    )
}
