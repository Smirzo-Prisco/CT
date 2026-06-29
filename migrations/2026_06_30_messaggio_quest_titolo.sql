-- Allarga messaggio_quest.titolo a VARCHAR(500) per evitare "Data too long" su titoli lunghi
ALTER TABLE messaggio_quest MODIFY COLUMN titolo VARCHAR(500) NOT NULL DEFAULT '';
