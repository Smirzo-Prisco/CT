/**
 * Documentazione.jsx — Ambientazione & Regolamento nella SPA
 *
 * Carica il menu accordion da pages/api_documentazione.php.
 * Testo articoli e risultati ricerca via fetch a documentazione_testo.php.
 * I link data-articolo nei risultati ricerca sono gestiti via event delegation.
 *
 * Stili: /themes/crystal/documentazione_menu.css + /themes/crystal/documentazione_spa.css
 * (iniettati da AppRouter).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect } from 'react'

function AccordionSection({ section, isOpen, onToggle, onLoad }) {
    return (
        <li className={isOpen ? 'open' : ''}>
            <div className={`dropdownlink ${section.classe}`} onClick={onToggle}>
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
                                        onClick={e => { e.preventDefault(); onLoad(item.articolo) }}
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

export default function Documentazione() {
    const [sections, setSections] = useState([])
    const [openIdx, setOpenIdx]   = useState(null)
    const [content, setContent]   = useState('')
    const [loading, setLoading]   = useState(true)
    const [error, setError]       = useState(null)
    const [searchQ, setSearchQ]   = useState('')

    useEffect(() => {
        fetch('pages/api_documentazione.php?op=getMenu')
            .then(r => r.json())
            .then(d => {
                if (d.success) setSections(d.menu)
                else setError(d.message ?? 'Errore caricamento menu')
                setLoading(false)
            })
            .catch(err => { setError(err.message); setLoading(false) })
    }, [])

    function loadArticolo(articolo) {
        fetch(`documentazione_testo.php?articolo=${articolo}`)
            .then(r => r.text())
            .then(html => setContent(html))
    }

    function handleSearch(e) {
        e.preventDefault()
        if (!searchQ.trim()) return
        fetch('documentazione_testo.php?op=search', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'ricerca=' + encodeURIComponent(searchQ),
        })
            .then(r => r.text())
            .then(html => setContent(html))
    }

    /** Event delegation: gestisce i link data-articolo iniettati dai risultati ricerca */
    function handleContentClick(e) {
        const link = e.target.closest('[data-articolo]')
        if (link) {
            e.preventDefault()
            loadArticolo(parseInt(link.dataset.articolo, 10))
        }
    }

    return (
        <div id="doc-wrap">
            <div className="doc-logo">
                <img src="themes/crystal/imgs/documentazione/titolo.png" alt="" />
            </div>

            <div className="pos-menu">
                {loading && <p style={{ color: '#8f8f8f', marginLeft: '8px' }}><i className="fas fa-spinner fa-spin" /></p>}
                {error   && <p style={{ color: '#ce846f', marginLeft: '8px' }}>{error}</p>}
                <ul className="accordion-menu">
                    {sections.map((sec, idx) => (
                        <AccordionSection
                            key={idx}
                            section={sec}
                            isOpen={openIdx === idx}
                            onToggle={() => setOpenIdx(prev => prev === idx ? null : idx)}
                            onLoad={loadArticolo}
                        />
                    ))}
                </ul>
                <form className="searchBar" onSubmit={handleSearch} style={{ marginTop: '8px' }}>
                    <div style={{ textAlign: 'center' }}>
                        <input
                            type="text"
                            value={searchQ}
                            onChange={e => setSearchQ(e.target.value)}
                            style={{ width: '200px' }}
                            placeholder="Inserire parola chiave"
                        />
                        <br />
                        <input type="submit" value="cerca" />
                    </div>
                </form>
            </div>

            <div id="doc-content" onClick={handleContentClick}>
                <button onClick={() => window.history.back()}>← Torna indietro</button>
                <div dangerouslySetInnerHTML={{ __html: content }} />
                <button style={{ marginTop: '10px' }} onClick={() => window.history.back()}>← Torna indietro</button>
            </div>
        </div>
    )
}
