-- Sistema notifiche: forum (segui sezione/thread) + DM via email + coda invii.
-- Il calendario (evento_calendario) è escluso di proposito: appuntamenti/BakCalendario/
-- sms sono un'area da investigare a parte prima di aggiungere quell'evento all'ENUM.

-- Segue una sezione (araldo.id_araldo) o un thread (messaggioaraldo.id_messaggio radice,
-- id_messaggio_padre=-1): un'unica tabella con discriminatore invece di due tabelle
-- quasi identiche. tipo_segui e' sempre 'manuale' per le righe tipo_oggetto='sezione'
-- (non esiste auto-follow di un'intera sezione, solo il pulsante "Segui").
CREATE TABLE IF NOT EXISTS araldo_follow (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(20) NOT NULL,
    tipo_oggetto   ENUM('sezione','thread') NOT NULL,
    riferimento_id INT         NOT NULL,
    tipo_segui     ENUM('manuale','commentato','autore') NOT NULL DEFAULT 'manuale',
    data_creazione DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nome_oggetto (nome, tipo_oggetto, riferimento_id),
    KEY idx_oggetto (tipo_oggetto, riferimento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Preferenze per evento×canale. Sezione "Notifiche" in Uffici > Preferenze
-- (accanto a suono_dm/suono_chat/suono_scheda, che restano colonne separate
-- su personaggio: qui una tabella dedicata perché sono 5 eventi × 2 canali).
-- Riga assente per (nome, evento) => default via_dm=1, via_email=0.
CREATE TABLE IF NOT EXISTS preferenze_notifiche (
    nome      VARCHAR(20) NOT NULL,
    evento    ENUM('nuovo_post_sezione','commento_post_seguito',
                    'commento_post_commentato','commento_post_proprio','nuovo_dm') NOT NULL,
    via_dm    TINYINT(1)  NOT NULL DEFAULT 1,
    via_email TINYINT(1)  NOT NULL DEFAULT 0,
    PRIMARY KEY (nome, evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coda notifiche (creata dal fan-out, consumata da: consegna DM immediata,
-- worker email con raggruppamento anti-spam)
CREATE TABLE IF NOT EXISTS notifiche (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(20) NOT NULL,
    evento         ENUM('nuovo_post_sezione','commento_post_seguito',
                         'commento_post_commentato','commento_post_proprio','nuovo_dm') NOT NULL,
    riferimento_id INT UNSIGNED NOT NULL,
    canale         ENUM('dm','email') NOT NULL,
    stato          ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    data_creazione DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_invio     DATETIME NULL,
    KEY idx_stato (stato),
    KEY idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
