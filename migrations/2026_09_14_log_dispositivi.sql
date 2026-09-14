-- Traccia i dispositivi (cookie di lunga durata, 1 anno) usati per il login di
-- ogni personaggio, per il rilevamento doppi account (pages/api_auth.php) --
-- un segnale che sopravvive ai cambi di IP/rete, a differenza del solo IP.
CREATE TABLE IF NOT EXISTS log_dispositivi (
    device_id CHAR(32) NOT NULL,
    Nome CHAR(20) NOT NULL,
    DataEvento DATETIME NOT NULL,
    PRIMARY KEY (device_id, Nome),
    INDEX idx_log_dispositivi_nome (Nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registra quale segnale (ip / dispositivo) ha fatto scattare ciascuna riga
-- di log_doppi, cosi' chi la consulta sa perche' e' stata segnalata. Le righe
-- gia' esistenti restano NULL: non e' possibile risalire retroattivamente al
-- segnale che le avrebbe generate con la logica precedente.
ALTER TABLE log_doppi ADD COLUMN Metodo VARCHAR(20) NULL AFTER Browser;
