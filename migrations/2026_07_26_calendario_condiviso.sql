-- Flag "calendario condiviso": se attivo, tutti gli eventi dell'utente sono
-- visibili anche a chi non e' autore/partecipante (vedi pages/api_calendario.php,
-- case 'month' e 'day'). Versione semplice: tutto-o-niente, nessuna eccezione
-- per singolo evento — un'eventuale privacy per-evento e' un'estensione futura.

SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''));
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', ''));
ALTER TABLE personaggio ADD COLUMN calendario_condiviso TINYINT(1) NOT NULL DEFAULT 0;
