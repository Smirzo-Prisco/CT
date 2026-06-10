/**
 * AnteprimaScheda.jsx
 *
 * Box anteprima personaggio nella colonna destra — rimpiazza pages/anteprima_scheda.inc.php.
 *
 * Contenuto:
 *   - Avatar del personaggio (variante giorno/notte)
 *   - Link alla scheda personaggio (main.php?page=scheda)
 *   - Bottone preferenze suoni: angolo in basso a destra di info_pg
 *
 * Strategia avatar (due livelli):
 *   1. Legge subito window.CT_USER.url_img_chat (iniettato da footer.inc.php),
 *      evitando un round-trip HTTP nella maggior parte dei casi.
 *   2. Se il valore è vuoto (campo non impostato in DB), fa una chiamata
 *      a api_scheda.php?op=profile come fallback.
 *
 * Nessun aggiornamento real-time: l'avatar cambia raramente.
 *
 * Montaggio: via ct:ready su #anteprima-scheda-container in left-right_frames.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback, useRef } from 'react'

// ---------------------------------------------------------------------------
// PANNELLO PREFERENZE SUONI
// ---------------------------------------------------------------------------

function SoundPanel({ prefs, onToggle }) {
    return (
        <div className="sound-panel">
            <div className="sound-panel__title">Preferenze suoni</div>
            <label className="sound-panel__row">
                <span>Messaggi privati</span>
                <input type="checkbox" checked={!!prefs.dm}
                    onChange={() => onToggle('dm')} />
            </label>
            <label className="sound-panel__row">
                <span>Chat di gioco</span>
                <input type="checkbox" checked={!!prefs.chat}
                    onChange={() => onToggle('chat')} />
            </label>
            <label className="sound-panel__row">
                <span>Musica schede</span>
                <input type="checkbox" checked={!!prefs.scheda}
                    onChange={() => onToggle('scheda')} />
            </label>
        </div>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function AnteprimaScheda() {

    const nome = window.CT_USER?.login ?? ''

    const [avatar, setAvatar] = useState(
        () => (window.CT_USER?.url_img_chat ?? '').trim()
    )

    const isNotte = (() => { const h = new Date().getHours(); return h >= 18 || h <= 6 })()

    useEffect(() => {
        if (avatar || !nome) return
        fetch(`/pages/api_scheda.php?op=profile&pg=${encodeURIComponent(nome)}`)
            .then(r => r.json())
            .then(d => { if (d.success && d.url_img_chat) setAvatar(d.url_img_chat.trim()) })
            .catch(() => { })
    }, [nome]) // eslint-disable-line react-hooks/exhaustive-deps

    // ── Preferenze suoni ────────────────────────────────────────────────────

    const [showSoundPanel, setShowSoundPanel] = useState(false)
    const [soundPrefs, setSoundPrefs] = useState(() => ({
        dm:     window.CT_USER?.soundPrefs?.dm     ?? 1,
        chat:   window.CT_USER?.soundPrefs?.chat   ?? 1,
        scheda: window.CT_USER?.soundPrefs?.scheda ?? 1,
    }))
    const panelRef = useRef(null)

    useEffect(() => {
        if (!showSoundPanel) return
        function handleOutside(e) {
            if (panelRef.current && !panelRef.current.contains(e.target))
                setShowSoundPanel(false)
        }
        document.addEventListener('mousedown', handleOutside)
        return () => document.removeEventListener('mousedown', handleOutside)
    }, [showSoundPanel])

    const handleToggle = useCallback((key) => {
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

    const anySoundOn  = soundPrefs.dm || soundPrefs.chat || soundPrefs.scheda
    const speakerIcon = anySoundOn ? 'fa-volume-high' : 'fa-volume-xmark'

    // ────────────────────────────────────────────────────────────────────────

    return (
        <div className="pagina_info_location">
            <div className="page_body">
                <div className={isNotte ? 'info_pg_night' : 'info_pg'}>

                    {avatar && (
                        <a href={`main.php?page=scheda&pg=${encodeURIComponent(nome)}`}
                           className="avatar-link">
                            <img
                                src={avatar}
                                className="immagine_pg"
                                alt={nome}
                            />
                        </a>
                    )}

                    {/* Bottone suoni — angolo in basso a destra */}
                    <div className="sound-toggle-wrapper" ref={panelRef}>
                        <button
                            className={`sound-toggle-btn${!anySoundOn ? ' sound-toggle-btn--muted' : ''}`}
                            title="Preferenze suoni"
                            onClick={() => setShowSoundPanel(v => !v)}
                        >
                            <i className={`fa-solid ${speakerIcon}`} />
                        </button>
                        {showSoundPanel && (
                            <SoundPanel prefs={soundPrefs} onToggle={handleToggle} />
                        )}
                    </div>

                </div>
            </div>
        </div>
    )
}
