-- Stato manuale (occupato/libero) mostrato sul pallino della prima colonna
-- in presenti_estesi (vedi PresentiEstesi.jsx). Campo nuovo e indipendente
-- da personaggio.disponibile, che resta riservato al meccanismo automatico
-- di inattivita' (idle/attivo) gia' esistente — riusarlo per la scelta
-- manuale lo avrebbe esposto a essere sovrascritto da quel meccanismo.
-- Il rosso (in giocata) resta invece automatico, calcolato da role_sessions
-- come gia' avviene per il flag in_role — nessuna colonna per quello.
ALTER TABLE personaggio
    ADD COLUMN stato_pallino ENUM('libero','occupato') NOT NULL DEFAULT 'libero';

-- La nota breve riusa personaggio.online_status (gia' esistente, editabile
-- da scheda_modifica.inc.php ma finora mai mostrata a nessuno) — nessuna
-- nuova colonna per quella, solo esposta in api_map.php?op=presenti_estesi.
