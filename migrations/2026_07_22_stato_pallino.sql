-- Stato manuale (occupato/libero) mostrato sul pallino della prima colonna
-- in presenti_estesi (vedi PresentiEstesi.jsx). Campo nuovo e indipendente
-- da personaggio.disponibile, che resta riservato al meccanismo automatico
-- di inattivita' (idle/attivo) gia' esistente — riusarlo per la scelta
-- manuale lo avrebbe esposto a essere sovrascritto da quel meccanismo.
-- Il rosso (in giocata) resta invece automatico, calcolato da role_sessions
-- come gia' avviene per il flag in_role — nessuna colonna per quello.

-- Stesso sblocco temporaneo di sql_mode gia' usato in
-- 2026_07_04_sempre_online_su_personaggio.sql e 2026_07_20_onboarding.sql:
-- personaggio ha una colonna preesistente (start_date) con un default non
-- valido ('0000-00-00') che con sql_mode strict blocca qualsiasi ALTER
-- TABLE su questa tabella finche' non si rilassa temporaneamente la
-- validazione — non tocca i dati esistenti, vale solo per questa sessione.
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''));
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', ''));

ALTER TABLE personaggio
    ADD COLUMN stato_pallino ENUM('libero','occupato') NOT NULL DEFAULT 'libero';

-- La nota breve riusa personaggio.online_status (gia' esistente, editabile
-- da scheda_modifica.inc.php ma finora mai mostrata a nessuno) — nessuna
-- nuova colonna per quella, solo esposta in api_map.php?op=presenti_estesi.
