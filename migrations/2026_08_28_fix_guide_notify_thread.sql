-- Fix bug: pages/api_regolamento.php inseriva le notifiche di modifica
-- regolamento per le guide come risposta al thread id_messaggio=250843,
-- che non e' mai esistito in messaggioaraldo (nessun thread radice, nessuna
-- riga con quell'id, sezione "Bacheca Guide" id_araldo=109 senza nessun
-- thread radice) -- le notifiche finivano orfane e invisibili da sempre.
-- Crea qui il thread radice reale con id fisso 300000 (identico su
-- staging e produzione, ben oltre l'AUTO_INCREMENT corrente di entrambe
-- le tabelle) cosi' il codice puo' riferirlo con un letterale hardcoded
-- come gia' avviene per il thread 264815 nella sezione NOTIFICHE.
INSERT INTO messaggioaraldo
    (id_messaggio, id_messaggio_padre, id_araldo, titolo, messaggio, autore,
     giornalista, data_messaggio, data_ultimo_messaggio, anonimo, importante, chiuso)
VALUES
    (300000, -1, 109, 'Modifiche al regolamento',
     'Notifiche automatiche di modifica agli articoli del regolamento, per le guide.',
     'Sistema', 'no', NOW(), NOW(), 'no', 1, 0);

ALTER TABLE messaggioaraldo AUTO_INCREMENT = 300001;
