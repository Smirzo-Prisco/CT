/**
 * IncrementoParametri.jsx — Pannello incremento parametri (wrapper per incremento_parametri.js).
 *
 * Renderizza lo scheletro HTML che incremento_parametri.js si aspetta, poi
 * carica lo script. Il nome del personaggio è iniettato da window.CT_USER.
 *
 * Stili: /themes/crystal/incremento_parametri.css (iniettato da AppRouter).
 *
 * @author Crystal Tokyo Dev
 */

import { useEffect } from 'react'

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

/** Carica uno script nel DOM; se già presente lo rimuove e ricarica per rieseguirlo. */
function loadScriptOnce(src) {
    return new Promise((resolve) => {
        const finalSrc = versionedSrc(src)
        const existing = document.querySelector(`script[src="${finalSrc}"]`)
        if (existing) existing.remove()
        const s  = document.createElement('script')
        s.src    = finalSrc
        s.onload = resolve
        s.onerror = resolve
        document.body.appendChild(s)
    })
}

export default function IncrementoParametri() {
    const pgName = window.CT_USER?.nome ?? ''

    useEffect(() => {
        loadScriptOnce('/includes/incremento_parametri.js')
    }, [])

    return (
        <div id="stats_panel" aria-live="polite" style={{ position: 'relative' }}>
            <h1>
                Pannello Assegnazione Punti
                <span className="help-container" title="Legenda livelli">
                    <span className="help-animated">?</span>
                    <div id="tooltip_soglie" className="tooltip-animated"></div>
                </span>
            </h1>
            <div className="sub">
                <p>Gestisci le caratteristiche usando Esperienza e Shin</p>
                <p>Ricorda che, una volta messi, i punti non possono essere recuperati.</p>
                <p>Fai salire il livello di {pgName} aumentando le sue caratteristiche</p>
            </div>

            {/* livello */}
            <div className="row">
                <div className="col total-card">
                    <div style={{ fontSize: '13px', color: 'var(--muted)' }}>Livello Personaggio</div>
                    <div style={{ fontSize: '20px', fontWeight: 700, marginTop: '6px' }} id="characterLevel"></div>
                </div>
                <div className="col total-card">
                    <div style={{ fontSize: '13px', color: 'var(--muted)' }}>Totale punti assegnati</div>
                    <div className="total-bar-container" aria-hidden="true">
                        <div className="total-bar-xp"    id="totalBarXP"></div>
                        <div className="total-bar-shin"  id="totalBarShin"></div>
                        <div className="total-bar-label" id="levelTotalLabel">0 punti</div>
                    </div>
                </div>
            </div>

            {/* available totals */}
            <div className="row">
                <div className="col total-card">
                    <div className="total-label" style={{ color: 'var(--xp)', fontWeight: 700 }}>Esperienza disponibile</div>
                    <div style={{ fontWeight: 700, fontSize: '20px', marginTop: '6px' }} id="xpDisponibili">0</div>
                </div>
                <div className="col total-card">
                    <div className="total-label" style={{ color: 'var(--shin)', fontWeight: 700 }}>Shin disponibili</div>
                    <div style={{ fontWeight: 700, fontSize: '20px', marginTop: '6px' }} id="shinDisponibili">0</div>
                </div>
            </div>

            {/* attributes container */}
            <div className="attributes" id="attributesContainer" aria-live="polite"></div>

            {/* save */}
            <div className="save-area">
                <button id="saveBtn" onClick={() => window.savePuntiPg?.(document.getElementById('saveBtn'))}>
                    💾 Salva modifiche
                </button>
            </div>
            <div className="link_back">
                <button onClick={() => {
                    if (window.CT?.navigate) window.CT.navigate('main.php?page=uffici')
                    else window.top.location.href = 'main.php?page=uffici'
                }}>← Torna indietro</button>
            </div>
        </div>
    )
}
