-- Sposta il flag "sempre online" dalla tabella privilegi (che si è rivelata
-- più fragile del previsto: pages/gestione_nomine.inc.php itera dinamicamente
-- ogni sua colonna trattandola come un ruolo/nomina) alla tabella personaggio,
-- attributo diretto del personaggio senza questo tipo di codice generico.
ALTER TABLE privilegi DROP COLUMN sempre_online;
ALTER TABLE personaggio ADD COLUMN sempre_online TINYINT(1) NOT NULL DEFAULT 0;
