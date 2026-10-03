-- Aggiunge un flag ON/OFF alle bacheche del forum (araldo), per unire in
-- un'unica card (lato React, vedi Forum.jsx) le coppie di bacheche che oggi
-- esistono solo come due righe distinte con lo stesso tipo/proprietari e il
-- nome che termina in " [ON]"/" [OFF]" (es. "Ospedale [ON]"/"Ospedale [OFF]",
-- e le 7 bacheche di fazione Adamanti/Beast/Celestiali/Demoni/Elementali/
-- Fiori/Lancaster, tutte con lo stesso pattern).
--
-- Convenzione del nome colonna/valori ripresa da sms.ongame (int 0/1),
-- l'unico precedente di flag ON/OFF nello schema — da non confondere con la
-- costante PHP ONGAME=0, che e' invece un valore del campo "tipo" (categoria
-- di accesso), concettualmente indipendente da questo flag.

ALTER TABLE araldo ADD COLUMN ongame TINYINT(1) NOT NULL DEFAULT 1 AFTER proprietari;

-- Backfill: normalizza le coppie esistenti rimuovendo il suffisso dal nome e
-- impostando il flag, cosi' le due righe condividono lo stesso nome (chiave
-- di raggruppamento lato api_forum.php) e si distinguono solo per ongame.
UPDATE araldo SET ongame = 0, nome = TRIM(REPLACE(nome, '[OFF]', '')) WHERE nome LIKE '% [OFF]';
UPDATE araldo SET ongame = 1, nome = TRIM(REPLACE(nome, '[ON]', ''))  WHERE nome LIKE '% [ON]';
