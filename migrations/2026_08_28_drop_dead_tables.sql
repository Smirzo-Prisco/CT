-- Rimozione tabelle morte confermate da audit database del 2026-08-28.
-- role_sessions_end_backup_20260730: backup manuale una tantum, zero
--   riferimenti nel codice, 264 righe di dati storici -- l'admin ha
--   confermato che il backup ha gia' assolto il suo scopo.
-- ambientazione: lato scrittura (gestione_ambientazione.inc.php) e lato
--   lettura (pages/user_ambientazione.inc.php, themes/crystal/home/
--   user_ambientazione.php) erano entrambi orfani (nessuna route/link
--   attivo), rimossi dal codice lo stesso giorno.
DROP TABLE IF EXISTS role_sessions_end_backup_20260730;
DROP TABLE IF EXISTS ambientazione;
