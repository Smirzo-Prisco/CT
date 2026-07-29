-- Collega ogni riga di risposta (dado_risposta/subisce/difesa) all'attacco originale
-- a cui risponde. Elimina l'ambiguita' nella query di elaborateAttackTarget() che,
-- basandosi solo su striker/target, non distingueva la risposta a QUESTO attacco dalla
-- risposta di un attacco opposto fra le stesse due persone nello stesso turno.
ALTER TABLE role_fights ADD COLUMN id_fight INT UNSIGNED NULL AFTER id;
