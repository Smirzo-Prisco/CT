/**
 * MapChatSearch.jsx
 *
 * Widget di ricerca chat per la pagina mappa (MapClick.jsx): non una modale,
 * ma un'icona che si affianca a un campo di testo quando aperta (in basso a
 * sinistra), con i risultati (autocomplete) che si aprono verso l'alto sopra
 * il campo — coerente con l'ancoraggio in basso del widget. Click su un
 * risultato → naviga direttamente alla chat (main.php?dir=X), stessa
 * risoluzione URL usata da MapClick per le stanze di tipo 'dir'.
 *
 * Dati: fetch unico al mount (api_map.php?op=search_rooms, tutte le chat
 * con la loro mappa), poi filtrato client-side ad ogni carattere digitato —
 * stesso pattern "carica una volta, filtra in memoria" di Anagrafe.jsx,
 * coerente col numero contenuto di chat totali nel gioco.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useMemo, useRef } from 'react'

export default function MapChatSearch() {
    const [open, setOpen] = useState(false)
    const [rooms, setRooms] = useState([])
    const [loaded, setLoaded] = useState(false)
    const [error, setError] = useState('')
    const [query, setQuery] = useState('')
    const [activeIndex, setActiveIndex] = useState(-1)
    const inputRef = useRef(null)
    const wrapRef = useRef(null)

    // Fetch pigro: solo alla prima apertura, non al mount del componente
    // (la pagina mappa lo monta sempre, anche se l'utente non cerca mai).
    useEffect(() => {
        if (!open || loaded) return
        fetch('/pages/api_map.php?op=search_rooms')
            .then(r => r.json())
            .then(d => {
                if (d.success) setRooms(d.rooms)
                else setError(d.message ?? 'Errore nel caricamento')
                setLoaded(true)
            })
            .catch(() => { setError('Errore di rete'); setLoaded(true) })
    }, [open, loaded])

    useEffect(() => { if (open) inputRef.current?.focus() }, [open])

    function close() {
        setOpen(false)
        setQuery('')
        setActiveIndex(-1)
    }

    // Chiude con Esc o cliccando fuori dal widget, coerente col resto della pagina
    // (vedi il popup zona in MapClick.jsx, stesso pattern document click).
    useEffect(() => {
        if (!open) return
        const onKey = e => { if (e.key === 'Escape') close() }
        const onClickOutside = e => { if (wrapRef.current && !wrapRef.current.contains(e.target)) close() }
        document.addEventListener('keydown', onKey)
        document.addEventListener('click', onClickOutside)
        return () => {
            document.removeEventListener('keydown', onKey)
            document.removeEventListener('click', onClickOutside)
        }
    }, [open])

    const results = useMemo(() => {
        const q = query.trim().toLowerCase()
        if (!q) return []
        return rooms.filter(r =>
            r.nome.toLowerCase().includes(q) || r.nome_mappa.toLowerCase().includes(q)
        )
    }, [rooms, query])

    useEffect(() => { setActiveIndex(-1) }, [query])

    function goToRoom(room) {
        const url = `main.php?dir=${room.id}`
        if (window.CT?.navigate) window.CT.navigate(url)
        else window.top.location.href = url
        close()
    }

    function onInputKeyDown(e) {
        if (e.key === 'ArrowUp') {
            // Frecce invertite rispetto a un dropdown verso il basso: i
            // risultati si aprono verso l'alto, quindi "su" = verso il
            // risultato successivo (più lontano dal campo), non precedente.
            e.preventDefault()
            setActiveIndex(i => Math.min(i + 1, results.length - 1))
        } else if (e.key === 'ArrowDown') {
            e.preventDefault()
            setActiveIndex(i => Math.max(i - 1, -1))
        } else if (e.key === 'Enter' && activeIndex >= 0 && results[activeIndex]) {
            e.preventDefault()
            goToRoom(results[activeIndex])
        }
    }

    return (
        <div className="map-search-widget" ref={wrapRef}>
            {open && (query.trim() !== '') && (
                <ul className="map-search-dropdown">
                    {!loaded && <li className="map-search-status">Caricamento…</li>}
                    {loaded && error && <li className="map-search-status map-search-status--error">{error}</li>}
                    {loaded && !error && results.length === 0 && (
                        <li className="map-search-status">Nessuna chat trovata.</li>
                    )}
                    {loaded && !error && results.map((r, i) => (
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

            <div className={`map-search-bar${open ? ' map-search-bar--open' : ''}`}>
                <button
                    type="button"
                    className="map-search-bar__icon"
                    onClick={() => (open ? close() : setOpen(true))}
                    title="Cerca chat"
                >
                    <i className="fas fa-magnifying-glass"></i>
                </button>
                {open && (
                    <input
                        ref={inputRef}
                        type="text"
                        className="map-search-bar__input"
                        placeholder="Cerca per nome chat o zona…"
                        value={query}
                        onChange={e => setQuery(e.target.value)}
                        onKeyDown={onInputKeyDown}
                        autoComplete="off"
                    />
                )}
            </div>
        </div>
    )
}
