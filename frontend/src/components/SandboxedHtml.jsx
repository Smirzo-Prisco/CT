/**
 * SandboxedHtml.jsx — Rendering isolato di HTML/CSS/script personalizzato
 * (campi scheda: background, storia, dice di se, off, particolari, note_fato).
 *
 * Questi campi permettono ai giocatori di scrivere HTML/CSS/script liberi
 * per personalizzare la propria scheda (effetti, layout). Vengono mostrati
 * a chiunque visiti la scheda, quindi non possono essere renderizzati nella
 * pagina principale via dangerouslySetInnerHTML: uno script ostile agirebbe
 * con la sessione autenticata di chi guarda (es. un admin in visita).
 *
 * L'iframe con sandbox="allow-scripts" (senza allow-same-origin) isola il
 * contenuto in un'origine opaca: lo script del giocatore puo' ancora
 * eseguire (effetti voluti), ma non vede i cookie del sito e qualunque
 * fetch/XHR verso le API non porta con se' la sessione di chi visita,
 * essendo cross-origin dal punto di vista del browser.
 *
 * Contropartita: script che manipolano elementi FUORI dal proprio
 * documento (es. document.getElementsByClassName su id della pagina
 * principale) non trovano piu' nulla — e' un effetto collaterale accettato,
 * non un bug di questo componente.
 */

import { useState, useRef, useEffect, useMemo, useId } from 'react'

// Altezza minima mentre si attende la prima misurazione, per evitare un
// flash a 0px prima che il contenuto dell'iframe segnali la propria altezza.
const MIN_HEIGHT = 40

function buildSrcDoc(html) {
    return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    html, body { margin: 0; padding: 8px; box-sizing: border-box; font-family: inherit; color: inherit; background: transparent; overflow-x: hidden; }
    img { max-width: 100%; }
</style>
</head>
<body>
${html}
<script>
(function () {
    function invia() {
        var h = document.documentElement.scrollHeight;
        parent.postMessage({ source: 'ct-sandboxed-html', height: h }, '*');
    }
    window.addEventListener('load', invia);
    new ResizeObserver(invia).observe(document.documentElement);
    invia();
})();
</script>
</body>
</html>`
}

/**
 * @param {string} html - HTML grezzo dal campo scheda (gia' HTML, non bbcode)
 */
export default function SandboxedHtml({ html }) {
    const [height, setHeight] = useState(MIN_HEIGHT)
    const iframeRef = useRef(null)
    const uid = useId()

    const srcDoc = useMemo(() => buildSrcDoc(html || ''), [html])

    useEffect(() => {
        function onMessage(e) {
            if (e.data?.source === 'ct-sandboxed-html' && e.source === iframeRef.current?.contentWindow) {
                setHeight(Math.max(MIN_HEIGHT, e.data.height))
            }
        }
        window.addEventListener('message', onMessage)
        return () => window.removeEventListener('message', onMessage)
    }, [])

    if (!html) return null

    return (
        <iframe
            ref={iframeRef}
            key={uid}
            title="Contenuto personalizzato del personaggio"
            sandbox="allow-scripts"
            srcDoc={srcDoc}
            style={{ width: '100%', border: 'none', display: 'block', height }}
        />
    )
}
