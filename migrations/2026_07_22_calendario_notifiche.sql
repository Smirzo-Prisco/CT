-- Notifica "sei stato aggiunto a un impegno nel calendario" (vedi
-- Preferenze.jsx e includes/custom_functions.inc.php::
-- queueCalendarioEventoNotification). Stesso schema evento/canale degli
-- altri eventi, default OFF su entrambi i canali gestito lato applicazione
-- in api_global.php (getNotificationPrefs) — qui serve solo estendere l'ENUM.
ALTER TABLE preferenze_notifiche
    MODIFY COLUMN evento ENUM('nuovo_post_sezione','commento_post_seguito',
        'commento_post_commentato','commento_post_proprio','nuovo_dm',
        'chat_off_non_letta','calendario_nuovo_impegno') NOT NULL;

ALTER TABLE notifiche
    MODIFY COLUMN evento ENUM('nuovo_post_sezione','commento_post_seguito',
        'commento_post_commentato','commento_post_proprio','nuovo_dm',
        'chat_off_non_letta','calendario_nuovo_impegno') NOT NULL;
