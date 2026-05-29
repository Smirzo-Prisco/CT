# CHANGES — Crystal Tokyo

Modifiche apportate al progetto Crystal Tokyo a partire dalla versione base GDRCD 5.5.

---

## Infrastruttura e Deploy

- Aggiunto sistema di deploy automatico via GitHub Actions (FTP → SSH)
- Integrato **Vite** come build system per il frontend React
- Aggiunto cache busting su CSS e bundle JS via `filemtime()`
- Ignorati correttamente `node_modules`, `dist` e file di log nel `.gitignore`
- Rimosse 43+ file inutilizzate (zero riferimenti nel codice)
- Rimossi file legacy orfani (vecchie prove, script inutilizzati)

---

## Socket.io — Real-time

- Sostituito polling HTTP con **Socket.io WebSocket** per aggiornamenti in tempo reale
- Aggiornamento presenti online in tempo reale su login/logout e cambio stanza
- Aggiornamento chat e messaggi privati via socket al posto del polling
- Indicatore **"sta scrivendo"** real-time nella chattina off
- Notifica icona messaggi animata su nuovo DM in arrivo
- Socket emette `users:update` su connect, disconnect e cambio stanza
- Fix race condition `setImmediate` sulla registrazione alle room socket
- Gestione cambio stanza SPA: aggiorna room socket (`room:change`) in AppRouter

---

## Frontend React — SPA

- Integrato React come sistema UI principale tramite bundle Vite (`ct-app.js`)
- **AppRouter**: routing client-side per tutte le pagine migrate, senza reload PHP
- Intercettore click globale per navigazione React (`CT.navigate`)
- Navigazione SPA con `pushState` + gestione back/forward browser
- CSS per pagina iniettati dinamicamente alla prima navigazione
- Cambio stanza via `api_map.php?op=move` (POST) prima del render ChatShell

### Pagine migrate in React
- **Forum** (Araldo): sezioni, thread, lettura, nuova quest, pulsanti staff
- **MessagesInbox**: inbox messaggi privati ON/OFF, thread, compose, real-time
- **PresentiEstesi**: tabella presenti con aggiornamento socket
- **MapClick**: mappa interattiva con hotspot, popup zone, contatore utenti per stanza
- **ChatShell**: shell completa della chat (form, toolbar, pannello GDR, prompt difesa)
- **Scheda personaggio**: scheda, storia, dice, off, skills, transizioni, modifica, affetti, punti PX, equipaggiamento, oggetti
- **Gestione** (staff): pannello gestione personaggi
- **Uffici**: sezione uffici con voci commentate (spiriti, lavoro libero, talenti)

### Componenti laterali React
- **InfoLocation**: box luogo corrente con anno e link alla stanza
- **FrameMessaggi**: griglia icone navigazione + meteo + notifiche messaggi
- **AnteprimaScheda**: avatar + link scheda nella colonna destra
- **OnlineUsers**: box presenti online con icona famiglia

---

## Chat e Sistema GDR

- **ChatShell** completo: form invio, selezione bersagli, toolbar azioni, pannello GDR
- Pannello GDR aperto/chiuso via React state invece di manipolazione DOM diretta
- Descrizione stanza mostrata quando la chat è vuota e nessuna role è attiva
- Campo TAG spostato sopra la textarea a sinistra di "Invia"
- Pulsante **Cura PG** spostato dal pannello alla toolbar principale; scompare dopo l'uso

### Risposta immediata agli attacchi
- Prompt difesa in tempo reale via socket (`combat:attack_incoming`)
- Tre opzioni di risposta: **Dado** (difesa con tiro), **Scudo** (skill difensiva), **Subisci**
- Ripristino prompt dopo refresh pagina tramite `pending_attacks`
- Scudo scelto: svuota tutti i prompt (l'azione occupa l'intero turno)
- Dado scelto: rimuove il prompt corrente, toglie l'opzione scudo dagli altri
- Subisce: rimuove solo il prompt corrente

### Sistema turni
- Prompt chiusura turno real-time via socket (`combat:close_turn`)
- Auto-chiusura turno quando azione testuale + lancio sono entrambi completati
- Solo lo scudo chiude il turno automaticamente; dado e subisce no
- `checkTurnCanClose` non chiude il turno se ci sono attacchi in sospeso senza risposta
- `setCanSend`: `can_send=0` impostato solo dopo uno scudo, non dopo dado/attacco
- Omette il messaggio risultati turno se non c'è stato scontro

### Riepilogo turno (`elaboratePrint`)
- Riscritto come card-based dark-theme UI al posto delle righe di testo
- Card per ogni azione (difesa, devia, generica, attacco) con avatar, badge, barra HP
- Griglia riassuntiva finale con danni PS/INT per PG
- Stili in classi CSS `.ct-turn__*` (niente inline tranne HSL avatar e % barra HP)
- Riepilogo centrato nella colonna chat via `margin: 0 auto`
- Badge "Scudo fallito" rinominato in "Attacco fallito"

### Attacco fisico / Intervento fisico
- Optiongroup rinominato da "Attacco fisico" a "Intervento fisico"
- Aggiunta voce **Attacca fisicamente** (comportamento precedente)
- Aggiunta voce **Devia l'attacco di**: dado Destrezza vs attaccante; se supera, annulla l'attacco fisico/arma
- Fix `can_send` mancante in `usaAttaccoChat`
- Notifica real-time al bersaglio e totale corretto nel sussurro difesa

### Fix sistema GDR
- Fix turno bloccato da `hasPendingUnrespondedAttacks` quando tutti hanno `close_turn=1`
- Fix risposta immediata che non chiudeva il turno quando il difensore era l'ultimo
- Fix scudo in `risposta_immediata` che chiude il turno come un attacco
- Fix PHP warning `false-array-access` in `elaborateTurn` che corrompeva il JSON
- Fix `foreach` su `mysqli_result` in `checkTurnEnd` che rompeva chiusura turno
- Fix check permessi su API `closeTurn`
- Fix logica mentale di comando (tre bug)
- Fix `registraDurata`: restituisce array, `durata_msg` nel riepilogo, tick solo su tipo P

---

## Scheda Personaggio

- Migrazione completa in React SPA con sotto-sezioni
- Fix encoding skills (`mb_convert_encoding`, `iconv//IGNORE`)
- Fix OOM skills con shutdown handler per E_ERROR silenzioso
- Rimossa colonna `a.costo` inesistente dalla query abilità
- Nuova paginazione scheda punti (px / shin / mestiere)
- Tooltip `?` sul campo Livello con tabella progressione
- Rimozione tag `<style>` dall'HTML del DB prima del rendering
- CSS del DB scopato a `.pagina_scheda`
- Etichetta "Ruolo" rinominata in "Mestiere"
- Skills: fusione tipi, "Livello attuale X/Y", layout `customTable` originale
- Colore arancione per "Livello attuale" nella lista abilità
- Affetti: iframe parte da `intro_affetto.php`
- Commenta voci Lavoro e Spirito

---

## Forum (Araldo)

- Migrazione completa in React
- Layout sezioni identico al vecchio PHP
- Badge numerico per sezioni non lette + luna icona
- Colonna STATO (aperto/chiuso), ricerca, BBCode toolbar
- Pulsanti staff, "Segna tutto come letto"
- Pulsante "Leggi tutto" globale in cima alle sezioni
- Pulsante "Nuova Quest" per staff nelle sezioni INFO
- Form creazione nuova quest integrato
- Tabella punti/valutazioni nella lettura quest

---

## Messaggi Privati

- Migrazione in React con inbox ON/OFF, thread, compose
- Navigazione DM da presenti ("Invia SMS") con auto-apertura conversazione
- Icona DM animata per nuovi messaggi; si spegne dopo la lettura
- Fix icona animata per conversazioni vuote/orfane con `lettura=0`
- Real-time via socket `dm:update`

---

## Mappa

- Migrazione in React SPA
- Hotspot trasparenti da 23px posizionati via `naturalWidth/Height`
- Popup zone come sibling del container (non figlio dell'hotspot)
- Pannello zone sotto la mappa invece di popup sovrapposto
- Contatore utenti online per stanza (badge rosso)
- `op=changemap` al mount per aggiornare la mappa corrente
- Navigazione stanze via `CT.navigate` (SPA) con chiamata `op=move` integrata

---

## Design System

- Implementati **SCSS + Design Tokens** (`_tokens.scss`) come palette canonica centralizzata
- Base SCSS: reset, typography, forms, buttons
- 8 componenti SCSS migrati
- Migrazione stili inline da JSX a SCSS (CSS Modules)
- Rimozione CSS inline da 16 file PHP (`<style>` + `echo style=`)
- Rimossi file orfani `tokens.css`, `uffici.css`
- Rimosso `a:link` globale da `forum.css` (rompeva layout uffici)
- Pulizia regole ridondanti e selettori legacy in `main.css`
- Bridge token SCSS → React via `ThemeSystem.jsx`

---

## Homepage e Iscrizione

- Pagina iscrizione riscritta come file standalone alla root
- Modale registrazione con form, Informazioni e Termini & Condizioni
- Hamburger menu mobile con animazione slide-down
- Modale login/registrazione si escludono a vicenda
- Spiriti sostituiti con gilde in fase di iscrizione
- Fix short tag PHP in `scegli_inclinazione`

---

## Chattina Off

- Integrata nella colonna sinistra del layout
- Styling completo (colori, bordi, sfondo)
- Indicatore "sta scrivendo" in tempo reale via socket
- Display `none` su mobile con breakpoint `768px`
- Fix overflow e larghezza su mobile e desktop

---

## Uffici e Servizi

- Migrazione Uffici in React SPA
- Rimosse voci Mercato e Banca
- Commentate voci: Lista spiriti, Scegli corrente, Aumento spirito, Scegli lavoro, Talenti
- `servizi_gilde`: rimossa tabella Lavoro Libero, aggiunta sezione mestieri
- Fix path CSS relativi e `topbar` z-index negli uffici

---

## Altro

- Rimossa funzionalità **Breaking News**
- Rimozione completa jQuery e script globali inutilizzati dall'header
- `CT_USER` e `socket.io` spostati nell'`<head>`
- Fix link nome luogo non soggetto alla regola globale `a:link`
- Fix ospedale cura PG: soglia 99, nessun requisito messaggi
- Pallino sessione role rosso quando inattiva (era grigio)
- `map_id` rimosso dalle URL delle stanze
