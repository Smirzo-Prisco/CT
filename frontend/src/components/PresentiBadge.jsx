/**
 * PresentiBadge.jsx
 *
 * Icona "Presenti" nel menu sinistro con badge numerico sovrapposto: numero
 * totale di personaggi online (api_map.php?op=presenti_totale — endpoint di
 * sola lettura, senza il side-effect di aggiornare ultimo_refresh/disponibile
 * che ha invece op=presenti, usato da OnlineUsers.jsx).
 * Aggiornato in tempo reale sull'evento socket 'users:update'.
 * Cliccabile: rimanda a main.php?page=presenti_estesi.
 *
 * Montaggio: via ct:ready su #presenti-button-container in header.inc.php
 * Sostituisce il markup statico precedente in layouts/left-right_frames.php.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

export default function PresentiBadge() {
    const [total, setTotal] = useState(0)

    const fetchTotal = useCallback(() => {
        fetch('/pages/api_map.php?op=presenti_totale')
            .then(r => r.json())
            .then(data => { if (data.success) setTotal(data.total_online) })
            .catch(() => {})
    }, [])

    useEffect(() => {
        fetchTotal()
        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchTotal)
        return () => { if (sock) sock.off('users:update', fetchTotal) }
    }, [fetchTotal])

    return (
        <div className="presenti_button">
            <a href="main.php?page=presenti_estesi">
                <img src="themes/crystal/imgs/menu/presenti.png" alt="Presenti" />
                <span className="presenti_badge">{total}</span>
            </a>
        </div>
    )
}
