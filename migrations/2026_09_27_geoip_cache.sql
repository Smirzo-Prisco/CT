-- Cache permanente delle risoluzioni IP -> paese/città/ISP (servizio terzo
-- ip-api.com, vedi gdrcd_geoip_lookup() in includes/custom_functions.inc.php),
-- usata dai tab Accessi/Doppi/Movimenti/Injection di main.php?page=log per
-- mostrare da dove si connette un IP. Un IP viene interrogato una sola volta:
-- niente scadenza, la posizione non cambia abbastanza spesso da giustificarla.
CREATE TABLE IF NOT EXISTS geoip_cache (
    IP VARCHAR(60) NOT NULL,
    Country VARCHAR(60) NULL,
    City VARCHAR(100) NULL,
    Isp VARCHAR(150) NULL,
    Proxy TINYINT(1) NOT NULL DEFAULT 0,
    DataRisoluzione DATETIME NOT NULL,
    PRIMARY KEY (IP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
