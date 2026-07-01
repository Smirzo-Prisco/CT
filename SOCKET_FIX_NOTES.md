# Note intervento socket real-time

Data: 2026-07-01

## Contesto

Problemi segnalati:

- La lista "Presenti online" non si aggiornava correttamente, mentre la lista estesa dei presenti si.
- I nuovi messaggi in chat non comparivano subito; serviva refresh manuale.

## Modifiche applicate

### `frontend/src/components/OnlineUsers.jsx`

- Corretto il cleanup del listener socket `users:update`.
- Prima il componente chiamava `sock.off('users:update')` senza passare l'handler, rimuovendo potenzialmente anche listener registrati da altri componenti.
- Ora salva la callback locale `onUsersUpdate` e rimuove solo quella con `sock.off('users:update', onUsersUpdate)`.

### `frontend/src/components/MapClick.jsx`

- Quando il personaggio torna alla mappa, dopo `api_map.php?op=changemap`, aggiorna anche `window.CT_USER.luogo = -1`.
- Emette `window.ctSocket?.emit('room:change', { newLuogo: -1 })` per sganciare il socket dalla vecchia stanza chat.
- Questo evita che il client resti iscritto alla room `chat:*` / `loc:*` precedente dopo il ritorno alla mappa.

### `nodeserver/server.js`

- Aggiunto stato per-socket `currentLuogo`, inizializzato dalla handshake.
- Le room iniziali ora usano `currentLuogo` normalizzato.
- Su `room:change`, il server:
  - lascia le vecchie room `chat:*` e `loc:*`;
  - entra nelle nuove room;
  - aggiorna `currentLuogo`;
  - notifica sia la stanza lasciata sia quella nuova con `users:update`;
  - notifica la room `global` con `presenti:update`.
- Su `disconnect`, ora notifica `loc:${currentLuogo}` invece del luogo iniziale della handshake.

## Verifiche eseguite

- `node --check nodeserver/server.js`: passato.
- `npm run build` da `frontend/`: passato.
- Warning residuo non collegato ai socket: deprecazione Sass di `darken()` in `Forum.module.scss`.

## File rigenerati dalla build

La build frontend ha rigenerato anche file tracciati:

- `themes/crystal/dist/ct-app.js`
- `themes/crystal/dist/ct-main.css`
- `public/public.css`
- `public/public.js`
- `themes/crystal/ct-styles.css`

Nel `git status` locale risultavano inoltre modifiche non legate direttamente a questo intervento:

- `header.inc.php`
- `.claude/worktrees/...`

## Deploy

Per rendere attiva la modifica Node in produzione serve riavviare il processo socket:

```bash
pm2 restart ct-socket
```

Non e stato eseguito alcun push git da Codex durante questo intervento.
