/**
 * Utility per l'isolamento del CSS inserito dagli utenti nei campi scheda.
 *
 * Tutti i selettori vengono prefissati con ".background" in modo che il CSS
 * utente si applichi esclusivamente al contenitore del testo del personaggio,
 * senza toccare menu, sidebar, o qualsiasi altro elemento della pagina.
 */

// Prefissa ogni selettore con SCOPE in modo che il CSS utente
// non possa influenzare elementi fuori da div.background.
const SCOPE = '.background'

function scopeSelectors(css) {
    return css.replace(/([^@{}]+)\{/g, (_, selector) => {
        const prefixed = selector
            .split(',')
            .map(s => {
                const t = s.trim()
                if (!t) return ''
                return t.replace(/^(body|html)\b/, SCOPE)
                        .replace(/^(?!\.background)/, `${SCOPE} `)
            })
            .filter(Boolean)
            .join(', ')
        return `${prefixed} {`
    })
}

// Suddivide il CSS in blocchi at-rule (@keyframes, @media…) e blocchi normali,
// rispettando le graffe annidate a un livello (sufficiente per i casi reali).
function splitAtRules(css) {
    const blocks = []
    let i = 0
    while (i < css.length) {
        const atIndex = css.indexOf('@', i)
        if (atIndex === -1) { blocks.push({ type: 'plain', content: css.slice(i) }); break }
        if (atIndex > i) blocks.push({ type: 'plain', content: css.slice(i, atIndex) })
        const braceStart = css.indexOf('{', atIndex)
        if (braceStart === -1) { blocks.push({ type: 'plain', content: css.slice(atIndex) }); break }
        let depth = 1
        let j = braceStart + 1
        while (j < css.length && depth > 0) {
            if (css[j] === '{') depth++
            else if (css[j] === '}') depth--
            j++
        }
        blocks.push({ type: 'at', content: css.slice(atIndex, j) })
        i = j
    }
    return blocks
}

/**
 * Estrae i blocchi <style> dall'HTML e li scopea a .background.
 * @param   {string} html
 * @returns {{ html: string, css: string }} html senza <style>, css scopato
 */
export function extractAndScopeStyles(html) {
    if (!html) return { html: '', css: '' }
    let css = ''
    const cleaned = html.replace(/<style[\s\S]*?>([\s\S]*?)<\/style>/gi, (_, block) => {
        css += block
        return ''
    })
    if (!css) return { html, css: '' }

    const scoped = splitAtRules(css).map(block => {
        if (block.type === 'plain') return scopeSelectors(block.content)
        const match = block.content.match(/^@([a-zA-Z-]+)\s*([^{]*)\{([\s\S]*)\}$/)
        if (!match) return block.content
        const [, name, prelude, inner] = match
        const innerCss = /^-?(webkit-|moz-|o-)?media$/i.test(name) ? scopeSelectors(inner) : inner
        return `@${name} ${prelude.trim()} {${innerCss}}`
    }).join('')

    return { html: cleaned, css: scoped }
}
