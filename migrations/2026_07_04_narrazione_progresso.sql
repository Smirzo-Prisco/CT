-- Checkpoint per giocata delle rigenerazioni complete (narrazione_richieste):
-- permette al worker (cron/narrazione_worker.php) di elaborare una sola
-- giocata alla volta invece di un'intera richiesta in blocco, così che un
-- job automatico più urgente (narrazione_queue, giocata appena chiusa di
-- un altro personaggio) possa sempre intromettersi tra un passo e l'altro
-- invece di aspettare la fine di una rigenerazione lunga (anche ore).
-- Il progresso persiste su DB (non solo in memoria del worker): se il
-- servizio si riavvia a metà, riprende dall'ultima giocata già riassunta
-- invece di ripartire da zero.

CREATE TABLE narrazione_richieste_progresso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    richiesta_id INT NOT NULL,
    id_role INT NOT NULL,
    riassunto TEXT NOT NULL,
    elaborato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_richiesta_giocata (richiesta_id, id_role)
);
