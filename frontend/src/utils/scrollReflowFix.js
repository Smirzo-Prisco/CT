/**
 * scrollReflowFix.js — Corregge due bug di Safari/WebKit (desktop e
 * iOS/iPadOS) che lasciano righe di tabella presenti nel DOM ma invisibili,
 * su pagine che caricano dati via fetch dopo il primo paint (Forum, lista
 * estesa presenti, ecc. — verificato: risposta API e DOM finale contengono
 * sempre tutte le righe, il dato non manca mai).
 *
 * 1. #maincontent (position:fixed + overflow:auto, l'unico contenitore di
 *    scroll condiviso da tutte le pagine, vedi _layout.scss) a volte non
 *    ricalcola l'altezza scrollabile quando il contenuto cresce dopo il
 *    primo paint: lo scroll resta agganciato all'altezza iniziale.
 *
 * 2. table.customTable (vedi _tables.scss) ha border-radius + overflow:hidden
 *    sulla TABELLA stessa per arrotondare gli angoli di thead/celle interne
 *    — usata così in ~40 pagine PHP. Su Safari, quando righe vengono
 *    aggiunte dinamicamente DOPO il primo paint, la maschera di ritaglio di
 *    overflow:hidden a volte non si ricalcola: le righe restano nel DOM ma
 *    vengono tagliate dalla maschera "vecchia" — indipendentemente dallo
 *    scroll della pagina (che infatti arriva regolarmente fino in fondo,
 *    il ritaglio è interno alla tabella, non legato al contenitore esterno).
 *    Sintomo riportato: "vedo fino a dove carica la pagina, ma scorrendo
 *    non c'è contenuto" — in realtà il contenuto c'è, è la tabella stessa
 *    a nasconderlo.
 *
 * Fix per entrambi: forzare un reflow dell'elemento interessato ad ogni
 * cambio d'altezza del suo contenuto — cambiare temporaneamente overflow a
 * un valore diverso da quello dichiarato in CSS, leggere offsetHeight,
 * ripristinare (rimuovendo lo stile inline, non riscrivendo lo stesso
 * valore: altrimenti per .customTable, già hidden via CSS, non cambierebbe
 * nulla e non forzerebbe alcun ricalcolo). Applicato su TUTTI i browser: su
 * Chromium/Firefox, che ricalcolano già correttamente, la nudge è un no-op
 * innocuo — più semplice e robusto di uno user-agent sniffing, inaffidabile
 * su iPadOS in particolare (si presenta come desktop Safari).
 *
 * @module scrollReflowFix
 */

/**
 * Crea una funzione di nudge per un elemento: al cambio di dimensione forza
 * un reflow impostando temporaneamente `overflow: tempValue` (diverso dal
 * valore CSS dichiarato) e poi rimuovendo lo stile inline.
 */
function makeNudger(el, tempValue) {
    let pending = false
    return () => {
        if (pending) return
        pending = true
        requestAnimationFrame(() => {
            el.style.overflow = tempValue
            void el.offsetHeight
            el.style.overflow = ''
            pending = false
        })
    }
}

/** Osserva una tabella .customTable e la nudge quando il contenuto cresce. */
function watchTable(table) {
    const nudge = makeNudger(table, 'visible')  // CSS dichiara 'hidden': 'visible' è un valore diverso, forza il ricalcolo
    new ResizeObserver(nudge).observe(table)
}

export function initScrollReflowFix() {
    const mainContent = document.getElementById('maincontent')
    const output = mainContent?.querySelector('.output')
    if (!mainContent || !output) return

    // #maincontent: CSS dichiara 'auto', 'hidden' è un valore diverso.
    new ResizeObserver(makeNudger(mainContent, 'hidden')).observe(output)

    // table.customTable: osserva quelle già presenti, e quelle aggiunte in
    // seguito (dati caricati via fetch dopo il mount, navigazione SPA).
    const watchedTables = new WeakSet()
    const scanForTables = (root) => {
        const tables = root.matches?.('table.customTable') ? [root] : []
        tables.push(...root.querySelectorAll?.('table.customTable') ?? [])
        for (const table of tables) {
            if (watchedTables.has(table)) continue
            watchedTables.add(table)
            watchTable(table)
        }
    }

    scanForTables(mainContent)

    new MutationObserver(mutations => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType === Node.ELEMENT_NODE) scanForTables(node)
            }
        }
    }).observe(mainContent, { childList: true, subtree: true })
}
