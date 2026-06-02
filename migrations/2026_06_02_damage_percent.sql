-- Aggiunge la colonna damage_percent alla tabella role_fights.
-- Usata dal sistema PNG del pannello master: quando > 0, ogni bersaglio
-- subisce damage_percent% del danno calcolato invece della divisione per count(targets).
ALTER TABLE role_fights ADD COLUMN damage_percent INT NOT NULL DEFAULT 0;
