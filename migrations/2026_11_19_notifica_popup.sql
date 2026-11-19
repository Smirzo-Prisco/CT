-- Canale "Popup": avviso live via socket sulla room personale dm:<nome> (stessa
-- room gia' usata per DM/combattimento in tempo reale) — fire-and-forget, nessuna
-- coda ne' persistenza come per dm/email: se il pg non e' online in quel momento
-- il popup semplicemente non arriva, esattamente come un avviso dal vivo. Per
-- questo la tabella "notifiche" (coda dm/email) non ha bisogno di un valore
-- 'popup' nell'ENUM canale — non viene mai scritta per questo canale.
ALTER TABLE preferenze_notifiche ADD COLUMN via_popup TINYINT(1) NOT NULL DEFAULT 0 AFTER via_dm;

-- Nuovo evento: "qualcuno entra nella stanza in cui mi trovo" (mai per chi esce,
-- mai per la mappa/home). Solo popup ha senso: via_dm/via_email restano sempre 0
-- per questo evento (mai scritti ne' esposti in UI, vedi api_global.php e
-- Preferenze.jsx) — un DM o una email per ogni ingresso in stanza sarebbe troppo
-- invasivo in una stanza affollata.
ALTER TABLE preferenze_notifiche MODIFY evento ENUM(
    'nuovo_post_sezione', 'commento_post_seguito', 'commento_post_commentato',
    'commento_post_proprio', 'nuovo_dm', 'chat_off_non_letta', 'calendario_nuovo_impegno',
    'ingresso_stanza'
) NOT NULL;
