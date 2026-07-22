/**
 * Preferenze.jsx — Pagina Preferenze (SPA)
 *
 * Due sezioni:
 *   - Suoni: stessa logica gia' in AnteprimaScheda.jsx (ora rimosso — l'avatar
 *     e il link alla scheda sono gestiti da Hud.jsx), persistita lato server
 *     via api_global.php?op=saveSoundPrefs e specchiata su window.CT_USER
 *     cosi' che Scheda.jsx (che ascolta 'ct:soundprefs:update') resti coerente.
 *   - Colori land: le 8 palette esplorate durante il redesign HUD (vedi
 *     memoria di progetto "project_ui_redesign_crystaltokyo" e lo scratchpad
 *     ct-redesign-mockup/styles.css), applicate come data-palette su <body>
 *     e lette da _hud.scss (var --ink/--ink-light/--gold/--gold-deep scoped
 *     su .ct-hud). Solo lato client (localStorage): e' un tema visivo, non
 *     richiede una colonna DB dedicata. Hud.jsx la riapplica ad ogni pagina
 *     leggendo la stessa chiave.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

function navigate(url) {
    if (window.CT?.navigate) window.CT.navigate(url)
    else window.top.location.href = url
}

// ---------------------------------------------------------------------------
// DATI STATICI
// ---------------------------------------------------------------------------

const PALETTE_STORAGE_KEY = 'ct_hud_palette'

// I 3 eventi commento_* (fan-out Fase C) + nuovo_dm (Fase E, solo email: un
// DM non puo' notificare un altro DM, non applicabile via_dm — dmOnly:false
// nasconde la checkbox del messaggio per quella riga) + chat_off_non_letta
// (fan-out in api_chatoff.php, notifica solo alla transizione letto->non
// letto — default OFF su entrambi i canali, a differenza degli altri eventi,
// perche' la chattina off e' molto attiva). nuovo_post_sezione resta fuori:
// arrivera' con la Fase F, quando esistera' davvero un trigger che lo
// produce — mostrare il toggle ora sarebbe un interruttore senza alcun effetto.
const NOTIFICATION_EVENTS = [
    { key: 'commento_post_seguito',    label: 'Nuovo commento sui post che seguo' },
    { key: 'commento_post_commentato', label: 'Nuovo commento sui post che ho commentato' },
    { key: 'commento_post_proprio',    label: 'Nuovo commento sui post che ho creato' },
    { key: 'nuovo_dm',                 label: 'Nuovo messaggio privato ricevuto', emailOnly: true },
    { key: 'chat_off_non_letta',       label: 'Messaggi non letti nella chattina off' },
]

// swatch = [ink, gold] — stessi valori esatti delle varianti body[data-palette] in _hud.scss
const PALETTES = [
    { key: 'public',       label: 'Blu notte (attuale)', swatch: ['#10142a', '#daa832'] },
    { key: 'obsidian',     label: 'Ossidiana',           swatch: ['#111116', '#c9a45c'] },
    { key: 'petrol',       label: 'Petrolio',            swatch: ['#123339', '#d8b26a'] },
    { key: 'indigo',       label: 'Indaco',              swatch: ['#241a3f', '#e6b49c'] },
    { key: 'cyan',         label: 'Ciano',                swatch: ['#0c2a4a', '#2fe0d1'] },
    { key: 'emerald',      label: 'Smeraldo',            swatch: ['#0d3a2f', '#d8b26a'] },
    { key: 'forest',       label: 'Foresta',             swatch: ['#14301f', '#f2e9d8'] },
    { key: 'navy-mustard', label: 'Blu e senape',        swatch: ['#0d1f3a', '#c99a3d'] },
]

// ---------------------------------------------------------------------------
// COMPONENTE
// ---------------------------------------------------------------------------

export default function Preferenze() {

    // ── Suoni ────────────────────────────────────────────────────────────
    const [soundPrefs, setSoundPrefs] = useState(() => ({
        dm:     window.CT_USER?.soundPrefs?.dm     ?? 1,
        chat:   window.CT_USER?.soundPrefs?.chat   ?? 1,
        scheda: window.CT_USER?.soundPrefs?.scheda ?? 1,
    }))

    const handleSoundToggle = useCallback((key) => {
        setSoundPrefs(prev => {
            const next = { ...prev, [key]: prev[key] ? 0 : 1 }
            if (window.CT_USER) window.CT_USER.soundPrefs = next
            document.dispatchEvent(new CustomEvent('ct:soundprefs:update', { detail: next }))
            fetch('/pages/api_global.php?op=saveSoundPrefs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(next),
            }).catch(() => {})
            return next
        })
    }, [])

    // ── Notifiche ────────────────────────────────────────────────────────
    const [notifPrefs, setNotifPrefs] = useState(null)

    useEffect(() => {
        fetch('/pages/api_global.php?op=getNotificationPrefs')
            .then(r => r.json())
            .then(d => { if (d.success) setNotifPrefs(d.prefs) })
            .catch(() => {})
    }, [])

    const handleNotifToggle = useCallback((evento, canale) => {
        setNotifPrefs(prev => {
            const next = {
                ...prev,
                [evento]: { ...prev[evento], [canale]: prev[evento][canale] ? 0 : 1 },
            }
            fetch('/pages/api_global.php?op=saveNotificationPrefs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prefs: next }),
            }).catch(() => {})
            return next
        })
    }, [])

    // ── Colori land ──────────────────────────────────────────────────────
    const [palette, setPalette] = useState(() => localStorage.getItem(PALETTE_STORAGE_KEY) || 'public')

    const choosePalette = useCallback((key) => {
        setPalette(key)
        localStorage.setItem(PALETTE_STORAGE_KEY, key)
        document.body.dataset.palette = key
    }, [])

    return (
        <>
            {/* Fuori da .preferenze-page apposta: quella card ha un suo
                max-width centrato (margin:auto) dentro .output (gia' al 90%
                e centrato di suo, vedi _layout.scss) — un pulsante messo
                dentro la card risulterebbe "in alto a sinistra" solo
                rispetto alla card stessa, non alla pagina, apparendo di
                fatto verso il centro dello schermo. */}
            <div className="link_back" style={{ textAlign: 'left' }}>
                <button onClick={() => navigate('main.php?page=uffici')}>← Torna indietro</button>
            </div>

            <div className="preferenze-page">

            <div className="preferenze-page__title">Preferenze</div>

            <div className="preferenze-page__section">
                <div className="preferenze-page__section-title">Suoni</div>

                <label className="preferenze-page__row">
                    <span>Messaggi privati</span>
                    <input type="checkbox" checked={!!soundPrefs.dm} onChange={() => handleSoundToggle('dm')} />
                </label>
                <label className="preferenze-page__row">
                    <span>Chat di gioco</span>
                    <input type="checkbox" checked={!!soundPrefs.chat} onChange={() => handleSoundToggle('chat')} />
                </label>
                <label className="preferenze-page__row">
                    <span>Musica schede</span>
                    <input type="checkbox" checked={!!soundPrefs.scheda} onChange={() => handleSoundToggle('scheda')} />
                </label>
            </div>

            {notifPrefs && (
                <div className="preferenze-page__section">
                    <div className="preferenze-page__section-title">Notifiche</div>

                    <div className="preferenze-page__notif-grid">
                        <div className="preferenze-page__notif-head">
                            <span />
                            <span>Messaggio</span>
                            <span>Email</span>
                        </div>
                        {NOTIFICATION_EVENTS.map(({ key, label, emailOnly }) => (
                            <div key={key} className="preferenze-page__notif-row">
                                <span className="preferenze-page__notif-label">{label}</span>
                                {emailOnly
                                    ? <span className="preferenze-page__notif-na" title="Non applicabile">—</span>
                                    : (
                                        <input
                                            type="checkbox"
                                            checked={!!notifPrefs[key]?.dm}
                                            onChange={() => handleNotifToggle(key, 'dm')}
                                        />
                                    )}
                                <input
                                    type="checkbox"
                                    checked={!!notifPrefs[key]?.email}
                                    onChange={() => handleNotifToggle(key, 'email')}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="preferenze-page__section">
                <div className="preferenze-page__section-title">Colori land</div>

                <div className="preferenze-page__palettes">
                    {PALETTES.map(p => (
                        <button
                            key={p.key}
                            type="button"
                            className={`preferenze-page__palette${palette === p.key ? ' is-active' : ''}`}
                            onClick={() => choosePalette(p.key)}
                        >
                            <span
                                className="preferenze-page__swatch"
                                style={{ background: `linear-gradient(135deg, ${p.swatch[0]}, ${p.swatch[1]})` }}
                            />
                            {p.label}
                        </button>
                    ))}
                </div>
            </div>

            </div>
        </>
    )
}
