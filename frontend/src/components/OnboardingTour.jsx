/**
 * OnboardingTour.jsx — Tour guidato "Crystal Bot" per i nuovi iscritti
 *
 * Spotlight sulle icone REALI dell'HUD (mai coordinate hardcoded): ogni
 * tappa misura dal DOM la posizione del proprio target con
 * getBoundingClientRect() nel momento in cui viene mostrata, e si
 * ri-misura a resize/orientamento — l'HUD ha layout diversi desktop/mobile
 * e i suoi anelli si aprono/chiudono, quindi le posizioni cambiano di
 * continuo (vedi onboardingSteps.js per il perché di questa scelta).
 *
 * Renderizzato come figlio diretto di Hud.jsx (unico posto dove le icone
 * target esistono davvero nel DOM): riceve onOpenRing per aprire l'anello
 * giusto prima di evidenziare un'icona al suo interno.
 *
 * Stato:
 *   - Al mount controlla api_global.php?op=getOnboardingStatus. Se il tour
 *     non è mai stato completato E non è stato saltato in questa sessione
 *     (sessionStorage — evita che riparta ad ogni cambio pagina SPA se
 *     l'utente lo salta), parte automaticamente dalla prima tappa.
 *   - Il pulsante "?" fluttuante è sempre presente per i loggati e permette
 *     di riaprire il tour a comando in qualsiasi momento, indipendentemente
 *     dal flag salvato.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'
import { createPortal } from 'react-dom'
import onboardingSteps from '../data/onboardingSteps'

const SESSION_SKIP_KEY = 'ct_onboarding_skipped'

export default function OnboardingTour({ onOpenRing }) {
    const [active, setActive] = useState(false)
    const [stepIndex, setStepIndex] = useState(0)
    const [rect, setRect] = useState(null)
    const measureRef = useRef(null)

    const step = active ? onboardingSteps[stepIndex] : null

    // ── Avvio automatico al mount (una volta sola per personaggio) ─────────
    useEffect(() => {
        if (sessionStorage.getItem(SESSION_SKIP_KEY) === '1') return
        fetch('/pages/api_global.php?op=getOnboardingStatus')
            .then(r => r.json())
            .then(d => {
                if (d.success && !d.done) {
                    setStepIndex(0)
                    setActive(true)
                }
            })
            .catch(() => {})
    }, [])

    // ── Misurazione del target reale ────────────────────────────────────────
    // Ritenta per un paio di secondi (l'anello impiega una transizione CSS ad
    // aprirsi, e HudArcSheet su mobile monta l'arco altrove nel DOM): senza
    // ritentare, misurare subito dopo onOpenRing() troverebbe l'icona ancora
    // nella posizione "chiusa" o non ancora montata.
    const measure = useCallback(() => {
        if (!step?.target) { setRect(null); return }
        const el = document.querySelector(step.target)
        if (el) setRect(el.getBoundingClientRect())
    }, [step])

    useEffect(() => {
        if (!active || !step) return

        if (step.ring) onOpenRing(step.ring)

        let tries = 0
        const tryMeasure = () => {
            if (!step.target) { setRect(null); return }
            const el = document.querySelector(step.target)
            if (el) {
                setRect(el.getBoundingClientRect())
            } else if (tries++ < 20) {
                measureRef.current = setTimeout(tryMeasure, 100)
            }
        }
        tryMeasure()

        window.addEventListener('resize', measure)
        return () => {
            clearTimeout(measureRef.current)
            window.removeEventListener('resize', measure)
        }
    }, [active, step, onOpenRing, measure])

    // ── Controlli ────────────────────────────────────────────────────────────
    const finish = useCallback(() => {
        setActive(false)
        sessionStorage.setItem(SESSION_SKIP_KEY, '1')
        fetch('/pages/api_global.php?op=setOnboardingDone', { method: 'POST' }).catch(() => {})
    }, [])

    const next = useCallback(() => {
        if (stepIndex >= onboardingSteps.length - 1) { finish(); return }
        setStepIndex(i => i + 1)
    }, [stepIndex, finish])

    const back = useCallback(() => setStepIndex(i => Math.max(0, i - 1)), [])

    const restart = useCallback(() => {
        sessionStorage.removeItem(SESSION_SKIP_KEY)
        setStepIndex(0)
        setActive(true)
    }, [])

    // ── Rendering ────────────────────────────────────────────────────────────

    // Pulsante "?" sempre presente (anche a tour non attivo/gia' completato):
    // unico modo per riaprire il tour a comando, come da requisito. Portal su
    // document.body (vedi sotto per il perche'): senza, un anello HUD aperto
    // su mobile lo coprirebbe rendendolo intoccabile.
    if (!active) {
        return createPortal(
            <button type="button" className="ct-onboarding__replay" onClick={restart} title="Rifai il tour guidato">
                <i className="fa-solid fa-question" />
            </button>,
            document.body
        )
    }

    const isLast = stepIndex === onboardingSteps.length - 1

    // Card centrata (nessun target, es. la tappa finale) oppure ancorata
    // sotto/sopra il target a seconda dello spazio verticale disponibile.
    const cardStyle = rect
        ? {
            top: rect.bottom + 16 < window.innerHeight - 160 ? rect.bottom + 16 : rect.top - 16,
            left: Math.min(Math.max(rect.left + rect.width / 2, 160), window.innerWidth - 160),
            transform: rect.bottom + 16 < window.innerHeight - 160
                ? 'translate(-50%, 0)'
                : 'translate(-50%, -100%)',
        }
        : {}

    // Portal su document.body — non basta il suo z-index (900): .ct-hud, il
    // contenitore radice dell'HUD (di cui questo componente e' figlio
    // diretto), ha gia' position:fixed + z-index proprio (500), quindi crea
    // un suo stacking context che "intrappola" qualunque z-index dei
    // discendenti, .ct-onboarding incluso — vengono confrontati con l'esterno
    // solo come un blocco unico al livello 500. Su mobile l'anello sinistro/
    // destro aperto esce invece in portal su document.body (vedi HudArcSheet,
    // stesso motivo: containing block del suo position:fixed rotto dal
    // transform:scale() di .ct-hud__ring) con z-index 600: fuori da
    // .ct-hud, quindi confrontato al livello radice — batte il 500 di
    // .ct-hud e copre l'intero tour, pulsanti Avanti/Indietro/Salta compresi.
    // Stesso portal qui pareggia il confronto: 900 fuori da .ct-hud batte
    // sempre il 600 dell'anello, ovunque.
    return createPortal(
        <div className="ct-onboarding" role="dialog" aria-modal="true">
            {/* Overlay scuro con "buco" sul target: box-shadow enorme invece di
                un clip-path/mask, per restare semplice e senza calcoli di
                poligono — il buco e' semplicemente l'area SENZA quell'ombra. */}
            <div
                className="ct-onboarding__hole"
                style={rect
                    ? { top: rect.top - 8, left: rect.left - 8, width: rect.width + 16, height: rect.height + 16 }
                    : { top: '50%', left: '50%', width: 0, height: 0 }
                }
            />

            <div className={`ct-onboarding__card${rect ? '' : ' ct-onboarding__card--center'}`} style={cardStyle}>
                <div className="ct-onboarding__head">
                    <i className="fa-solid fa-robot" />
                    <strong>Crystal Bot</strong>
                    <span className="ct-onboarding__count">{stepIndex + 1}/{onboardingSteps.length}</span>
                </div>
                <p>{step.text}</p>
                <div className="ct-onboarding__actions">
                    <button type="button" className="ct-onboarding__skip" onClick={finish}>Salta</button>
                    <div className="ct-onboarding__nav">
                        {stepIndex > 0 && (
                            <button type="button" className="ct-onboarding__back" onClick={back}>Indietro</button>
                        )}
                        <button type="button" className="ct-onboarding__next" onClick={next}>
                            {isLast ? 'Fine' : 'Avanti'}
                        </button>
                    </div>
                </div>
            </div>
        </div>,
        document.body
    )
}
