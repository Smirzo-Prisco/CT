/**
 * useHudBadges.js
 *
 * I 5 badge di notifica dell'HUD (messaggi privati, chat off, forum, giocate
 * aperte, eventi calendario): ciascuno segue lo stesso pattern — fetch al
 * mount + refetch su un evento socket/DOM dedicato — estratto qui per non
 * affollare Hud.jsx di 5 blocchi state+effect pressoché identici.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

export default function useHudBadges() {

    // ── Messaggi privati ─────────────────────────────────────────────────
    const [hasNewMessages, setHasNewMessages] = useState(false)

    const fetchMessages = useCallback(() => {
        fetch('/pages/api_global.php?op=getMessages')
            .then(r => r.json())
            .then(d => {
                if (!d.success) return
                setHasNewMessages(d.hasNew)

                // Suono sms.wav al nuovo messaggio: presente in FrameMessaggi.jsx
                // (il componente sostituito da Hud) ma mai riportato qui durante
                // il redesign — throttle 10 min via localStorage, rispetta la
                // preferenza utente soundPrefs.dm.
                if (d.hasNew && d.allowAudio && (window.CT_USER?.soundPrefs?.dm ?? 1)) {
                    const now = Date.now()
                    const last = parseInt(localStorage.getItem('last_audio_play') || '0', 10)
                    if (now - last > 600_000) {
                        new Audio('../sounds/sms.wav').play().catch(() => { })
                        localStorage.setItem('last_audio_play', String(now))
                    }
                }
            })
            .catch(err => console.error('[Hud] Errore messaggi:', err))
    }, [])

    useEffect(() => {
        fetchMessages()
        const sock = window.ctSocket
        if (sock) sock.on('dm:update', fetchMessages)
        return () => { if (sock) sock.off('dm:update', fetchMessages) }
    }, [fetchMessages])

    // ── Chat off non letta ───────────────────────────────────────────────
    const [hasNewChatOff, setHasNewChatOff] = useState(false)

    const fetchChatOff = useCallback(() => {
        fetch('/pages/api_global.php?op=getChatOff')
            .then(r => r.json())
            .then(d => { if (d.success) setHasNewChatOff(d.hasNew) })
            .catch(err => console.error('[Hud] Errore chat off:', err))
    }, [])

    useEffect(() => {
        fetchChatOff()
        const sock = window.ctSocket
        if (sock) sock.on('chatoff:update', fetchChatOff)
        return () => { if (sock) sock.off('chatoff:update', fetchChatOff) }
    }, [fetchChatOff])

    // ── Post forum non letti ─────────────────────────────────────────────
    const [hasNewForum, setHasNewForum] = useState(false)

    const fetchForumUnread = useCallback(() => {
        fetch('/pages/api_global.php?op=getForumUnread')
            .then(r => r.json())
            .then(d => { if (d.success) setHasNewForum(d.has_unread) })
            .catch(err => console.error('[Hud] Errore forum:', err))
    }, [])

    useEffect(() => {
        fetchForumUnread()
        const sock = window.ctSocket
        if (sock) sock.on('forum:update', fetchForumUnread)
        // 'ct:location-changed' scatta solo per i movimenti sulla mappa, MAI per
        // la navigazione fra pagine (forum incluso) — non basta a rilevare che
        // l'utente ha letto dei thread. Il vero segnale e' 'ct:forum-read',
        // emesso da Forum.jsx dopo ogni op=read/readall riuscito.
        window.addEventListener('ct:location-changed', fetchForumUnread)
        window.addEventListener('ct:forum-read', fetchForumUnread)
        return () => {
            if (sock) sock.off('forum:update', fetchForumUnread)
            window.removeEventListener('ct:location-changed', fetchForumUnread)
            window.removeEventListener('ct:forum-read', fetchForumUnread)
        }
    }, [fetchForumUnread])

    // ── Giocate aperte ───────────────────────────────────────────────────
    const [hasOpenRoles, setHasOpenRoles] = useState(false)

    const fetchOpenRoles = useCallback(() => {
        fetch('/pages/api_global.php?op=getOpenRoles')
            .then(r => r.json())
            .then(d => { if (d.success) setHasOpenRoles(d.has_open_roles) })
            .catch(err => console.error('[Hud] Errore giocate:', err))
    }, [])

    useEffect(() => {
        fetchOpenRoles()
        const sock = window.ctSocket
        if (sock) sock.on('role:update', fetchOpenRoles)
        return () => { if (sock) sock.off('role:update', fetchOpenRoles) }
    }, [fetchOpenRoles])

    // ── Eventi calendario di oggi ────────────────────────────────────────
    const [hasEvents, setHasEvents] = useState(false)

    const fetchEvents = useCallback(() => {
        fetch('/pages/api_global.php?op=events_today')
            .then(r => r.json())
            .then(d => { if (d.success) setHasEvents(d.has_events) })
            .catch(err => console.error('[Hud] Errore eventi:', err))
    }, [])

    useEffect(() => {
        fetchEvents()
        // 'ct:calendario-update' — emesso da Calendario.jsx dopo create/update/
        // delete riusciti: senza, il pallino resta quello del primo caricamento
        // finche' Hud.jsx non rimonta (mai, e' l'unico componente sempre montato).
        window.addEventListener('ct:calendario-update', fetchEvents)
        return () => window.removeEventListener('ct:calendario-update', fetchEvents)
    }, [fetchEvents])

    // Esposto per l'azzeramento ottimistico del badge quando si apre il
    // popover chat-off in Hud.jsx (op=messages lato server segna "letto"
    // subito, senza aspettare un giro di socket che qui non arriverebbe).
    const clearChatOff = useCallback(() => setHasNewChatOff(false), [])

    return { hasNewMessages, hasNewChatOff, hasNewForum, hasOpenRoles, hasEvents, clearChatOff }
}
