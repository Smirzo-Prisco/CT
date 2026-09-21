-- Tentativi di injection (SQL/XSS/path traversal/command injection) rilevati
-- euristicamente su OGNI richiesta del sito (non solo per personaggi già noti
-- come doppi, non richiede nemmeno una sessione attiva) — vedi il secondo
-- register_shutdown_function in includes/required.php. Nome nullable: un
-- tentativo contro login/iscrizione può arrivare senza nessuna sessione.
CREATE TABLE IF NOT EXISTS log_injection (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Nome CHAR(20) NULL,
    Endpoint VARCHAR(60) NOT NULL,
    Operazione VARCHAR(60) NOT NULL,
    Campo VARCHAR(60) NOT NULL,
    Valore VARCHAR(500) NOT NULL,
    Pattern VARCHAR(30) NOT NULL,
    IP VARCHAR(60) NOT NULL,
    DataEvento DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_log_injection_data (DataEvento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
