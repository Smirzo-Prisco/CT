'use strict';

const http = require('http');
const { Server } = require('socket.io');

const PORT = 3000;
const HOST = '127.0.0.1';

const httpServer = http.createServer((req, res) => {
    if (req.method !== 'POST' || req.url !== '/notify') {
        res.writeHead(404);
        return res.end();
    }

    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
        try {
            const { event, room, data } = JSON.parse(body);
            if (event && room) {
                io.to(room).emit(event, data || {});
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end('{"ok":true}');
            } else {
                res.writeHead(400);
                res.end();
            }
        } catch {
            res.writeHead(400);
            res.end();
        }
    });
});

const io = new Server(httpServer, {
    cors: {
        origin: ['https://crystaltokyo.it', 'http://crystaltokyo.it'],
        methods: ['GET', 'POST'],
        credentials: true,
    },
    path: '/socket.io/',
});

io.on('connection', socket => {
    const { login, luogo } = socket.handshake.auth;

    if (!login) {
        socket.disconnect(true);
        return;
    }

    socket.join(`chat:${luogo}`);
    socket.join(`loc:${luogo}`);
    socket.join(`dm:${login}`);
    socket.join(`chatoff:${login}`);

    // Room globale: tutti i client la joinano per ricevere eventi
    // che riguardano l'intero gioco (login/logout di chiunque).
    // Usata da PresentiEstesi che mostra utenti di tutte le stanze.
    socket.join('global');

    socket.on('disconnect', () => {});
});

httpServer.listen(PORT, HOST, () => {
    console.log(`[CT Socket] in ascolto su ${HOST}:${PORT}`);
});
