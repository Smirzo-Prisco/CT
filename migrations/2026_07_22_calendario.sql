-- Nuovo calendario (sostituisce appuntamenti/BakCalendario, vedi
-- migrations/2026_07_22_calendario_migra_dati.php per la migrazione dati).
-- destinatario non e' piu' una stringa comma-separated ma una relazione
-- vera in calendario_partecipanti, cosi' la visibilita' incrociata
-- (autore + coinvolti) e' una semplice query invece di un parsing manuale.

CREATE TABLE calendario_eventi (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    autore         VARCHAR(20)  NOT NULL,
    titolo         VARCHAR(100) NULL,        -- etichetta breve, opzionale (utile per eventi pubblici)
    colore         VARCHAR(20)  NOT NULL,    -- chiave della palette predefinita lato frontend
    luogo          VARCHAR(50)  NULL,        -- mappa.nome
    data           DATE         NOT NULL,
    ora            TIME         NULL,
    nota           TEXT         NULL,
    pubblico       TINYINT(1)   NOT NULL DEFAULT 0,  -- impostabile solo da staff (vedi api_calendario.php)
    data_creazione DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_data (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE calendario_partecipanti (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id  INT UNSIGNED NOT NULL,
    nome       VARCHAR(20)  NOT NULL,        -- personaggio.nome
    UNIQUE KEY uq_evento_nome (evento_id, nome),
    KEY idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
