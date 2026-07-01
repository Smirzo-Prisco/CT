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
    let currentLuogo = parseInt(luogo, 10);

    if (!login) {
        socket.disconnect(true);
        return;
    }

    if (isNaN(currentLuogo)) currentLuogo = -1;

    socket.join(`chat:${currentLuogo}`);
    socket.join(`loc:${currentLuogo}`);
    socket.join(`dm:${login}`);
    socket.join(`chatoff:${login}`);

    // Room globale: tutti i client la joinano per ricevere eventi
    // che riguardano l'intero gioco (login/logout di chiunque).
    // Usata da PresentiEstesi che mostra utenti di tutte le stanze.
    socket.join('global');

    // Notifica agli utenti nella stessa stanza (users:update) e a tutti
    // gli osservatori globali come PresentiEstesi (presenti:update su global).
    // Il setImmediate evita la race condition con InfoLocation: il listener
    // React si registra nel primo tick dopo il mount, ma l'evento socket
    // arriva prima. Rimandando al tick successivo il componente è già pronto.
    setImmediate(() => {
        io.to(`loc:${currentLuogo}`).emit('users:update');
        io.to('global').emit('presenti:update');
    });

    // Cambio stanza SPA: lascia le vecchie room chat/loc e joina le nuove
    socket.on('room:change', ({ newLuogo }) => {
        const nl = parseInt(newLuogo, 10);
        if (isNaN(nl)) return;
        const oldLuogo = currentLuogo;
        for (const room of [...socket.rooms]) {
            if (room.startsWith('chat:') || room.startsWith('loc:')) socket.leave(room);
        }
        socket.join(`chat:${nl}`);
        socket.join(`loc:${nl}`);
        currentLuogo = nl;
        // Notifica vecchia e nuova stanza (presenti aggiornati) e il pannello globale
        if (oldLuogo !== nl) io.to(`loc:${oldLuogo}`).emit('users:update');
        io.to(`loc:${nl}`).emit('users:update');
        io.to('global').emit('presenti:update');
    });

    // Typing indicator chattina off: broadcast a tutti tranne il mittente
    socket.on('chatoff:typing', (typing) => {
        socket.to('global').emit('chatoff:typing', { nome: login, typing: !!typing });
    });

    socket.on('disconnect', () => {
        io.to(`loc:${currentLuogo}`).emit('users:update');
        io.to('global').emit('presenti:update');
        // Assicura che il typing indicator venga rimosso se il socket cade
        socket.to('global').emit('chatoff:typing', { nome: login, typing: false });
    });
});

httpServer.listen(PORT, HOST, () => {
    console.log(`[CT Socket] in ascolto su ${HOST}:${PORT}`);
});
