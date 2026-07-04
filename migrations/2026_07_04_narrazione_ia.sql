-- Narrazione IA: riassunti generati dall'LLM locale (llama.cpp + Qwen2.5-3B,
-- vedi memoria di progetto "project_local_llm") a partire dalle giocate dei
-- personaggi, mostrati/accodati in personaggio.descrizione (page=scheda_dice).
--
-- Due flussi distinti che condividono la stessa infrastruttura:
--  - narrazione_queue: riassunto automatico e silenzioso, accodato ad ogni
--    giocata conclusa (endRoleSession in includes/chat_functions.inc.php)
--  - narrazione_richieste: rigenerazione COMPLETA su richiesta esplicita del
--    giocatore (sovrascrive descrizione), soggetta ad approvazione admin

CREATE TABLE narrazione_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_role INT NOT NULL,
    pg_name VARCHAR(255) NOT NULL,
    stato ENUM('pending','processing','done','error') NOT NULL DEFAULT 'pending',
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    elaborato_il DATETIME NULL,
    errore TEXT NULL
);

CREATE TABLE narrazione_richieste (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pg_name VARCHAR(255) NOT NULL,
    stato ENUM('richiesta','approvata','in_elaborazione','completata','rifiutata') NOT NULL DEFAULT 'richiesta',
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approvato_da VARCHAR(255) NULL,
    approvato_il DATETIME NULL,
    completato_il DATETIME NULL,
    errore TEXT NULL
);
