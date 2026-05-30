/**
 * Statuto.jsx — Statuto gilda o mestiere nella SPA
 *
 * Legge ?id=N (gilda) o ?id2=N (mestiere) dalla URL.
 * Carica il menu accordion da pages/api_statuto.php.
 * Il testo degli articoli viene caricato da statuto_testo.php via fetch.
 *
 * Stili: /themes/crystal/statuto_menu.css + /themes/crystal/statuto.css
 * (iniettati da AppRouter).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

/** Mappa id gilda → immagine di sfondo */
const BG_GILDE = {
    1: 'paladini.png', 2: 'demoni.png',    3: 'setta.png',
    4: 'custodi.png',  5: 'guardiani.png',  6: 'fiori.png',
    7: 'lancaster.png',8: 'muryu.png',      9: 'manuale_cittadini.png',
}

/** Mappa id mestiere → immagine di sfondo */
const BG_MESTIERI = {
    1: 'icc.png', 2: 'tae.png', 3: 'magic.png',
    4: 'pandora.png', 6: 'corte.png', 10: 'corte.png',
}

/** Sezione accordion del menu */
function AccordionSection({ section, isOpen, onToggle }) {
    return (
        <li className={isOpen ? 'open' : ''}>
            <div
                className={`dropdownlink ${section.classe}`}
                onClick={onToggle}
            >
                <i className="fa fa-chevron-down" />
            </div>
            <ul className="submenuItems" style={isOpen ? { display: 'block' } : {}}>
                <li>
                    <p>
                        <center>
                            {section.items.map(item => (
                                <span key={item.articolo}>
                                    <a
                                        href="#"
                                        onClick={e => { e.preventDefault(); section.onLoad(item.articolo) }}
                                    >
                                        {item.titolo}
                                    </a>
                                    <br />
                                </span>
                            ))}
                        </center>
                    </p>
                </li>
            </ul>
        </li>
    )
}

export default function Statuto() {
    const p   = new URLSearchParams(window.location.search)
    const id  = parseInt(p.get('id')  ?? '0', 10)
    const id2 = parseInt(p.get('id2') ?? '0', 10)

    const background = BG_GILDE[id] ?? BG_MESTIERI[id2] ?? null

    const [sections, setSections] = useState([])
    const [openIdx, setOpenIdx]   = useState(null)
    const [content, setContent]   = useState('')
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)

    useEffect(() => {
        setLoading(true)
        setContent('')
        setOpenIdx(null)
        const qs = id > 0 ? `id=${id}` : `id2=${id2}`
        fetch(`pages/api_statuto.php?op=getMenu&${qs}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setSections(d.menu)
                else setError(d.message ?? 'Errore caricamento menu')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [id, id2])

    function loadArticolo(articolo) {
        fetch(`statuto_testo.php?articolo=${articolo}`)
            .then(r => r.text())
            .then(html => setContent(html))
    }

    function toggleSection(idx) {
        setOpenIdx(prev => prev === idx ? null : idx)
    }

    if (!background) return <p>Pagina non trovata.</p>

    const sectionsWithHandler = sections.map(sec => ({ ...sec, onLoad: loadArticolo }))

    return (
        <div id="statuto-wrap">
            <div className="statuto-bg">
                <img src={`themes/crystal/imgs/statuti/${background}`} alt="" />
            </div>

            <div className="pos-menu">
                {loading && <p style={{ color: '#8f8f8f', marginLeft: '8px' }}><i className="fas fa-spinner fa-spin" /></p>}
                {error   && <p style={{ color: '#ce846f', marginLeft: '8px' }}>{error}</p>}
                <ul className="accordion-menu">
                    {sectionsWithHandler.map((sec, idx) => (
                        <AccordionSection
                            key={idx}
                            section={sec}
                            isOpen={openIdx === idx}
                            onToggle={() => toggleSection(idx)}
                        />
                    ))}
                </ul>
            </div>

            <div
                id="statuto-content"
                dangerouslySetInnerHTML={{ __html: content }}
            />
        </div>
    )
}
