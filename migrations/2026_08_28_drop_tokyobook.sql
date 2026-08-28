-- Rimozione completa della feature Tokyobook, su richiesta esplicita
-- dell'utente (conversazione di progetto del 2026-08-28): mini social
-- network per personaggi, irraggiungibile dall'interfaccia da tempo (solo
-- il campo "Alias Tokyobook" nella scheda personaggio restava collegato).
-- Codice PHP/React e asset rimossi in parallelo a questa migrazione.
DROP TABLE IF EXISTS tokyobook_likes;
DROP TABLE IF EXISTS tokyobook_lettura;
DROP TABLE IF EXISTS tokyobook_bacheca;
DROP TABLE IF EXISTS tokyobook;
