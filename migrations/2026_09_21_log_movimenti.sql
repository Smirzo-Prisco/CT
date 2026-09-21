-- Traccia ogni azione (non lettura) di ogni personaggio loggato, per poter
-- ricostruire lo storico completo di un account fin dal suo primo utilizzo —
-- utile per investigare comportamenti sospetti (vedi log_doppi) senza dover
-- aspettare di aver gia' identificato l'account come sospetto (vedi Tyler:
-- riconosciuto doppio di Patrick solo al login, non alla registrazione).
-- Popolata da includes/required.php (shutdown function), non da ogni singolo
-- pages/api_*.php: solo le richieste POST, che nel frontend segnano gia' le
-- azioni vere e proprie a differenza delle letture/polling in GET (op=ping,
-- op=presenti, op=profile, ecc.) — nessuna whitelist di endpoint da mantenere.
CREATE TABLE IF NOT EXISTS log_movimenti (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Nome CHAR(20) NOT NULL,
    Endpoint VARCHAR(60) NOT NULL,
    Operazione VARCHAR(60) NOT NULL,
    IP VARCHAR(60) NOT NULL,
    DataEvento DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_log_movimenti_nome (Nome, DataEvento),
    INDEX idx_log_movimenti_data (DataEvento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
