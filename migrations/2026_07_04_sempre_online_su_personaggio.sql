-- Sposta il flag "sempre online" dalla tabella privilegi (che si è rivelata
-- più fragile del previsto: pages/gestione_nomine.inc.php itera dinamicamente
-- ogni sua colonna trattandola come un ruolo/nomina) alla tabella personaggio,
-- attributo diretto del personaggio senza questo tipo di codice generico.
ALTER TABLE privilegi DROP COLUMN sempre_online;
ALTER TABLE personaggio ADD COLUMN sempre_online TINYINT(1) NOT NULL DEFAULT 0;

-- is_invisible = 0: senza questo, presenti_estesi/presenti (op=presenti) nascondono
-- comunque la riga ai giocatori non-staff (if ($row['is_invisible'] == 1 && !$is_staff) continue;),
-- vanificando l'effetto "sempre online" proprio per il pubblico a cui è destinato
UPDATE personaggio SET sempre_online = 1, is_invisible = 0 WHERE nome IN ('Acamar', 'Christian', 'Kirari', 'Cecile', 'Latino', 'Neo');