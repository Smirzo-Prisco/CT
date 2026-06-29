-- ============================================================
-- Bot System — Crystal Tokyo
-- I bot sono identificati da personaggio.sesso = 'b'
-- Non si tocca la struttura di personaggio.
-- ============================================================

CREATE TABLE IF NOT EXISTS bot_schedule (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    bot_nome    VARCHAR(100) NOT NULL,
    giorno      TINYINT      NOT NULL COMMENT '0=Lun, 1=Mar, 2=Mer, 3=Gio, 4=Ven, 5=Sab, 6=Dom',
    ora_inizio  TIME         NOT NULL,
    ora_fine    TIME         NOT NULL,
    INDEX idx_bot_nome (bot_nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bot_status (
    bot_nome    VARCHAR(100) NOT NULL PRIMARY KEY,
    paused      TINYINT      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
