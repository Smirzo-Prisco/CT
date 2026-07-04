-- Flag opzionale per far risultare un account sempre online nelle liste
-- (Presenti Estesi, widget compatto, conteggio homepage pubblica), a
-- prescindere dall'attività reale. Pensato per gli account admin, per dare
-- un minimo di presenza percepita ai nuovi iscritti. Reversibile: basta
-- rimettere il flag a 0, nessun'altra colonna/tabella viene toccata.
ALTER TABLE privilegi ADD COLUMN sempre_online TINYINT(1) NOT NULL DEFAULT 0;
