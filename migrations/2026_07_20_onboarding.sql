-- Flag "tour di onboarding completato" per personaggio: mostra il tour guidato
-- (Crystal Tour, vedi frontend/src/components/OnboardingTour.jsx) una sola
-- volta ai nuovi iscritti, riapribile a comando dall'icona dedicata nell'HUD.

-- Stesso sblocco temporaneo di sql_mode già usato in
-- 2026_07_04_sempre_online_su_personaggio.sql: personaggio ha una colonna
-- preesistente (start_date) con un default non valido ('0000-00-00') che con
-- sql_mode strict blocca qualsiasi ALTER TABLE su questa tabella finché non
-- si rilassa temporaneamente la validazione — non tocca i dati esistenti,
-- vale solo per questa sessione/connessione.
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''));
SET SESSION sql_mode = (SELECT REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', ''));
ALTER TABLE personaggio ADD COLUMN onboarding_visto TINYINT(1) NOT NULL DEFAULT 0;
