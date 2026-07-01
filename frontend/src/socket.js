import { io } from 'socket.io-client'

// Crea il socket e lo espone su window.ctSocket.
// Chiamato da main.jsx prima di ct:ready, così i componenti
// React trovano già window.ctSocket impostato al momento del mount.
export function initSocket() {
    const user = window.CT_USER
    if (!user) return

    const socket = io({ auth: user })

    // Dopo ogni (ri)connessione sincronizza la room con la stanza corrente.
    // Necessario quando il server socket viene riavviato (pm2 restart).
    socket.on('connect', () => {
        socket.emit('room:change', { newLuogo: window.CT_USER.luogo })
    })

    window.ctSocket = socket
}
