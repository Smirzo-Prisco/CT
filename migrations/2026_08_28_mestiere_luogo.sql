-- Associazione mestiere -> luogo (mappa.id) per sostituire gli array hardcoded
-- sparsi nel codice (craft_locations in chat_functions.inc.php, i controlli
-- luogo==24/luogo==25 in oggetto_assegna_chat.inc.php e api_chat.php).
-- Un mestiere ha al massimo un luogo associato: tutti i casi trovati in codice
-- erano gia' 1:1, non serve una tabella di giunzione.
ALTER TABLE mestiere ADD COLUMN id_luogo INT NULL DEFAULT NULL AFTER visibile;

-- Backfill delle sole associazioni ancora valide (id_mestiere esistenti):
-- il vecchio array hardcoded includeva anche i mestieri 1, 2 e 4, cancellati
-- da tempo — non vengono ripristinati qui, l'admin li reimposta dal nuovo
-- pannello "Luoghi mestiere" se servono ancora.
UPDATE mestiere SET id_luogo = 24 WHERE id_mestiere = 3;  -- Shirokuro Magic Shop
UPDATE mestiere SET id_luogo = 25 WHERE id_mestiere = 10; -- Ospedale
