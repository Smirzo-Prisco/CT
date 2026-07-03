-- Tabella dedicata alle gilde giocatore, separata da clgpersonaggiomestiere:
-- un personaggio può ora avere sia un mestiere vero (tipo=1) che una gilda insieme.
CREATE TABLE IF NOT EXISTS clgpersonaggioaffiliazione LIKE clgpersonaggiomestiere;

-- Sposta le righe esistenti delle gilde giocatore (mestiere.tipo != 1) dalla vecchia tabella
INSERT INTO clgpersonaggioaffiliazione (personaggio, id_ruolo, conferma_mestiere, scadenza)
SELECT cpm.personaggio, cpm.id_ruolo, cpm.conferma_mestiere, cpm.scadenza
FROM clgpersonaggiomestiere cpm
JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
JOIN mestiere m ON rm.mestiere = m.id_mestiere
WHERE m.tipo != 1;

-- personaggio.id_mestiere/id_ruolo_mestiere sono da ora riservate al solo mestiere vero:
-- ripristina chi aveva lì la cache della gilda appena spostata sopra
UPDATE personaggio p
JOIN clgpersonaggioaffiliazione cga ON cga.personaggio = p.nome
SET p.id_mestiere = 0, p.id_ruolo_mestiere = 1
WHERE p.id_ruolo_mestiere = cga.id_ruolo;

DELETE cpm FROM clgpersonaggiomestiere cpm
JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
JOIN mestiere m ON rm.mestiere = m.id_mestiere
WHERE m.tipo != 1;
