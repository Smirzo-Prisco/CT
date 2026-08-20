/**
 * MapChatSearchModal.jsx
 *
 * Modale di ricerca chat per la pagina mappa (MapClick.jsx): ricerca per
 * parola chiave con autocomplete tra tutte le chat di gioco, mostrando per
 * ciascun risultato la mappa/zona di appartenenza. Click su un risultato →
 * naviga direttamente alla chat (main.php?dir=X), stessa risoluzione URL
 * usata da MapClick per le stanze di tipo 'dir'.
 *
 * Dati: fetch unico al mount (api_map.php?op=search_rooms, tutte le chat
 * con la loro mappa), poi filtrato client-side ad ogni carattere digitato —
 * stesso pattern "carica una volta, filtra in memoria" di Anagrafe.jsx,
 * coerente col numero contenuto di chat totali nel gioco.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useMemo, useRef } from 'react'

export default function MapChatSearchModal({ onClose }) {
    const [rooms, setRooms] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState('')
    const [query, setQuery] = useState('')
    const [activeIndex, setActiveIndex] = useState(-1)
    const inputRef = useRef(null)

    useEffect(() => {
        fetch('/pages/api_map.php?op=search_rooms')
            .then(r => r.json())
            .then(d => {
                if (d.success) setRooms(d.rooms)
                else setError(d.message ?? 'Errore nel caricamento')
                setLoading(false)
            })
            .catch(() => { setError('Errore di rete'); setLoading(false) })
    }, [])

    // Autofocus sul campo di ricerca all'apertura
    useEffect(() => { inputRef.current?.focus() }, [])

    // Chiude con Esc, coerente con le altre modali del progetto (vedi QuestRecapModal)
    useEffect(() => {
        const onKey = e => { if (e.key === 'Escape') onClose() }
        document.addEventListener('keydown', onKey)
        return () => document.removeEventListener('keydown', onKey)
    }, [onClose])

    const results = useMemo(() => {
        const q = query.trim().toLowerCase()
        if (!q) return []
        return rooms.filter(r =>
            r.nome.toLowerCase().includes(q) || r.nome_mappa.toLowerCase().includes(q)
        )
    }, [rooms, query])

    // Reset dell'evidenziazione da tastiera ad ogni nuova ricerca
    useEffect(() => { setActiveIndex(-1) }, [query])

    function goToRoom(room) {
        const url = `main.php?dir=${room.id}`
        if (window.CT?.navigate) window.CT.navigate(url)
        else window.top.location.href = url
        onClose()
    }

    function onInputKeyDown(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault()
            setActiveIndex(i => Math.min(i + 1, results.length - 1))
        } else if (e.key === 'ArrowUp') {
            e.preventDefault()
            setActiveIndex(i => Math.max(i - 1, -1))
        } else if (e.key === 'Enter' && activeIndex >= 0 && results[activeIndex]) {
            e.preventDefault()
            goToRoom(results[activeIndex])
        }
    }

    return (
        <div className="map-search-overlay" onClick={() => onClose()}>
            <div className="map-search-modal" role="dialog" aria-modal="true" aria-label="Cerca chat" onClick={e => e.stopPropagation()}>
                <div className="map-search-header">
                    <h2><i className="fas fa-magnifying-glass"></i> Cerca chat</h2>
                    <button className="map-search-close" onClick={() => onClose()} aria-label="Chiudi">&times;</button>
                </div>

                <div className="map-search-body">
                    <input
                        ref={inputRef}
                        type="text"
                        className="map-search-input"
                        placeholder="Cerca per nome chat o zona…"
                        value={query}
                        onChange={e => setQuery(e.target.value)}
                        onKeyDown={onInputKeyDown}
                        autoComplete="off"
                    />

                    {loading && <p className="map-search-status">Caricamento…</p>}
                    {!loading && error && <p className="map-search-status map-search-status--error">{error}</p>}

                    {!loading && !error && (
                        <ul className="map-search-results">
                            {query.trim() === '' && (
                                <li className="map-search-status">Digita per cercare tra tutte le chat di gioco.</li>
                            )}
                            {query.trim() !== '' && results.length === 0 && (
                                <li className="map-search-status">Nessuna chat trovata.</li>
                            )}
                            {results.map((r, i) => (
                                <li
                                    key={r.id}
                                    className={`map-search-result${i === activeIndex ? ' map-search-result--active' : ''}`}
                                    onMouseEnter={() => setActiveIndex(i)}
                                    onClick={() => goToRoom(r)}
                                >
                                    <span className="map-search-result__nome">{r.nome}</span>
                                    <span className="map-search-result__mappa">{r.nome_mappa}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    )
}
