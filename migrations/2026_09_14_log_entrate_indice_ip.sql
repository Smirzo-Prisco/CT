-- log_entrate non aveva alcun indice oltre alla chiave primaria: ogni ricerca
-- per IP (rilevamento doppi in pages/api_auth.php, calcolo probabilità in
-- pages/log.inc.php) faceva una scansione completa della tabella. Diventato
-- un collo di bottiglia serio da quando ogni login viene loggato senza
-- deduplica (la tabella cresce molto più in fretta di prima).
ALTER TABLE log_entrate ADD INDEX idx_log_entrate_ip (IP);
