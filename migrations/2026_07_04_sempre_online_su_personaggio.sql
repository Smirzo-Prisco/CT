-- Sposta il flag "sempre online" dalla tabella privilegi (che si è rivelata
-- più fragile del previsto: pages/gestione_nomine.inc.php itera dinamicamente
-- ogni sua colonna trattandola come un ruolo/nomina) alla tabella personaggio,
-- attributo diretto del personaggio senza questo tipo di codice generico.
-- NB: se "ALTER TABLE privilegi DROP COLUMN sempre_online" è già stata eseguita
-- con successo in un tentativo precedente, non rilanciarla (la colonna non
-- esiste più, darebbe errore "unknown column").
ALTER TABLE privilegi DROP COLUMN sempre_online;

-- personaggio ha una colonna preesistente e scollegata (start_date, DATE/DATETIME
-- con un default non valido tipo '0000-00-00', mai usata nel codice attuale) che
-- con sql_mode strict blocca QUALSIASI ALTER TABLE su questa tabella finché non
-- si rilassa temporaneamente la validazione — non tocca i dati esistenti, vale
-- solo per questa sessione/connessione.
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''));
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', ''));
ALTER TABLE personaggio ADD COLUMN sempre_online TINYINT(1) NOT NULL DEFAULT 0;

-- is_invisible = 0: senza questo, presenti_estesi/presenti (op=presenti) nascondono
-- comunque la riga ai giocatori non-staff (if ($row['is_invisible'] == 1 && !$is_staff) continue;),
-- vanificando l'effetto "sempre online" proprio per il pubblico a cui è destinato
UPDATE personaggio SET sempre_online = 1, is_invisible = 0 WHERE nome IN ('Acamar', 'Christian', 'Kirari', 'Cecile', 'Latino', 'Neo');