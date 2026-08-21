/**
 * scrollReflowFix.js — Corregge un bug di WebKit/Safari (desktop e
 * iOS/iPadOS) sullo scroll di #maincontent (position:fixed + overflow:auto,
 * l'unico contenitore di scroll condiviso da tutte le pagine, vedi
 * _layout.scss).
 *
 * Quando il contenuto di #maincontent cresce DOPO il primo paint — il caso
 * normale per qualunque pagina che carica dati via fetch (Forum, lista
 * estesa presenti, ecc. — verificato: sia il JSON di risposta sia il DOM
 * finale contengono già tutte le righe, il dato non manca mai) — Safari a
 * volte non ricalcola l'altezza scrollabile: lo scroll resta agganciato
 * all'altezza del primo paint, e il contenuto aggiunto dopo è presente nel
 * DOM ma irraggiungibile scorrendo. Sintomo riportato: "vedo fino a dove si
 * carica la pagina, ma scorrendo non c'è contenuto".
 *
 * Fix: un ResizeObserver su .output (il contenitore la cui altezza cresce
 * con i dati caricati) forza un reflow di #maincontent ad ogni cambio
 * d'altezza — overflow:hidden, lettura di offsetHeight, poi overflow
 * ripristinato al valore CSS originale — costringendo il motore di rendering
 * a ricalcolare l'area scrollabile. Applicato su TUTTI i browser (non solo
 * Safari/WebKit): su Chromium/Firefox, che ricalcolano già correttamente, la
 * nudge è un no-op innocuo (nessun bug da correggere, nessun effetto
 * visibile) — più semplice e robusto di uno sniffing dello user agent, che
 * su iPadOS in particolare è inaffidabile (si presenta come desktop Safari).
 *
 * @module scrollReflowFix
 */

export function initScrollReflowFix() {
    const mainContent = document.getElementById('maincontent')
    const output = mainContent?.querySelector('.output')
    if (!mainContent || !output) return

    let pending = false
    const nudge = () => {
        if (pending) return
        pending = true
        requestAnimationFrame(() => {
            mainContent.style.overflow = 'hidden'
            void mainContent.offsetHeight
            mainContent.style.overflow = ''
            pending = false
        })
    }

    new ResizeObserver(nudge).observe(output)
}
