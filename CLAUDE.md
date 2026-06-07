# Crystal Tokyo — Contesto progetto per Claude Code

## Cos'è il progetto
Crystal Tokyo è un GDR play-by-chat (gioco di ruolo testuale) costruito su GDRCD 5.5, un engine PHP open source. Il progetto è in fase di modernizzazione progressiva: le pagine legacy restano in PHP, le nuove funzionalità vengono migrate a React.

## Stack tecnologico
- **Backend**: PHP 8 + MySQL (LAMP), server Hetzner VPS
- **Frontend**: React 18 + Vite, compilato in `themes/crystal/dist/ct-app.js`
- **SCSS**: compilato da Vite, entry point `frontend/src/styles/main.scss`
- **Real-time**: Node.js + Socket.io (`nodeserver/server.js`), gestito da PM2
- **Deploy**: GitHub Actions auto-deploy su push a `main` (SSH → git reset --hard → npm build)

## File critici
| File | Note |
|---|---|
| `config.inc.php` | **Gitignored** — contiene DB credentials, SMTP Brevo, API key Anthropic. Va editato manualmente sul server via SSH/nano. Non modificarlo in git. |
| `header.inc.php` | Monta i componenti React via `CT.mount()` nell'evento `ct:ready` |
| `frontend/src/main.jsx` | Registra tutti i componenti React nel registry `window.CT` |
| `frontend/src/AppRouter.jsx` | Router client-side per le pagine migrate |
| `includes/required.php` | Include config.inc.php, functions, vocabulary — usato da tutti gli endpoint PHP |
| `includes/custom_functions.inc.php` | Contiene `send_mail()` via Brevo HTTP API |

## Architettura React
I componenti React vengono montati su `<div id="...">` già presenti nel DOM PHP tramite:
```js
CT.mount('NomeComponente', 'id-container', { props })
```
Il bundle è caricato nell'`<head>` come `type="module"` (sempre deferred). I montaggi avvengono nell'evento `ct:ready`.

Per aggiungere un nuovo componente:
1. Creare `frontend/src/components/NomeComponente.jsx`
2. Importarlo e registrarlo in `main.jsx`
3. Aggiungere il container div e il mount in `header.inc.php` (o nel file PHP che serve la pagina)
4. Se ha CSS dedicato: creare `frontend/src/styles/components/_nome.scss` con `@use '../tokens' as *;` in cima e importarlo in `main.scss`

## Deploy
GitHub Actions esegue su ogni push a `main`:
1. `git reset --hard origin/main` sul server
2. `npm install && npm run build` nel frontend
3. `pm2 restart ct-socket`

`config.inc.php` viene preservato tramite backup/restore nel workflow (non viene sovrascritto dal reset).

**Non fare `git pull` manuale sul server** — ci pensa Actions.

## Email
Hetzner blocca tutte le porte SMTP (25, 465, 587). La funzione `send_mail()` usa **Brevo HTTP API** (porta 443). La configurazione SMTP in `config.inc.php` è mantenuta per compatibilità ma non usata.

## Database
- Funzione query: `gdrcd_query($sql)` per singola riga, `gdrcd_query($sql, 'result')` + `gdrcd_query($result, 'fetch')` per più righe
- Sanitizzazione input DB: `gdrcd_filter('in', $valore)`
- Output sicuro: `gdrcd_filter('out', $valore)` o `gdrcd_bbcoder(gdrcd_filter('out', $testo))` per bbcode
- La chiave primaria di `personaggio` si chiama `nome` (non `id`)

## Crystal Bot (chatbot AI)
Chatbot basato su Claude Haiku (`claude-haiku-4-5-20251001`) integrato come widget floating React.
- **Endpoint**: `pages/api_chatbot.php` (`op=status`, `op=ask`)
- **RAG**: MySQL FULLTEXT su `regolamento (titolo, testo)` — max 5 articoli rilevanti, fallback su tutti se nessun match. Richiede: `ALTER TABLE regolamento ADD FULLTEXT INDEX ft_regolamento (titolo, testo);`
- **Rate limit**: C-token giornalieri per utente (`SUM(tokens_usati)` su `chatbot_log`). Limite configurabile: `$PARAMETERS['anthropic']['daily_token_limit']` in `config.inc.php`
- **Log**: tabella `chatbot_log (id, nome_personaggio, domanda, risposta, tokens_usati, created_at)`
- **Chiave API**: `$PARAMETERS['anthropic']['api_key']` in `config.inc.php` (mai in git)

## Convenzioni codice
- Gli SCSS dei componenti iniziano sempre con `@use '../tokens' as *;`
- PHP 8: **niente short tag** `<?` → usare sempre `<?php`
- Nei componenti React, i listener DOM (click, ecc.) vanno messi come `onClick` React, non tramite `addEventListener` in `corefunctions.js` (la SPA non garantisce DOMContentLoaded sul DOM React)
- `config.inc.php` non va mai committato — è in `.gitignore` ma era storicalmente tracciato; ora rimosso con `git rm --cached`

## Cosa non fare
- Non aggiungere `<?` short tag nei file PHP
- Non usare `mail()` nativa — usare sempre `send_mail()` da `custom_functions.inc.php`
- Non fare commit di `config.inc.php` (contiene credentials)
- Non eseguire `git pull` manuale sul server
- Non usare `addEventListener` per elementi renderizzati da React (usare `onClick` JSX)
