-- Notifica "messaggi non letti nella chattina off" (vedi Preferenze.jsx e
-- pages/api_chatoff.php). Stesso schema evento/canale degli altri eventi
-- (preferenze_notifiche, notifiche), ma via_dm e via_email di default
-- restano OFF per questo evento: e' gestito lato applicazione in
-- api_global.php (getNotificationPrefs), qui serve solo estendere l'ENUM.
ALTER TABLE preferenze_notifiche
    MODIFY COLUMN evento ENUM('nuovo_post_sezione','commento_post_seguito',
        'commento_post_commentato','commento_post_proprio','nuovo_dm','chat_off_non_letta') NOT NULL;

ALTER TABLE notifiche
    MODIFY COLUMN evento ENUM('nuovo_post_sezione','commento_post_seguito',
        'commento_post_commentato','commento_post_proprio','nuovo_dm','chat_off_non_letta') NOT NULL;
