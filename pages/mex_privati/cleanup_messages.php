<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');



   $date_limit = date('Y-m-d H:i:s', strtotime('-1 month'));


#### 2. **Pulizia dei messaggi di gruppo:**

// Seleziona i gruppi che hanno messaggi più vecchi di 3 mesi
$sql_gruppi = "
    SELECT gruppo_id, MIN(ora_spedizione) as primo_messaggio
    FROM sms
    WHERE tipo_messaggio = 'gruppo'
    GROUP BY gruppo_id
    HAVING primo_messaggio < '$date_limit'
";

$result_gruppi = gdrcd_query($sql_gruppi, 'result');

while ($row_gruppo = gdrcd_query($result_gruppi, 'fetch')) {
    $gruppo_id = $row_gruppo['gruppo_id'];
    $primo_messaggio = $row_gruppo['primo_messaggio'];

    // Cancella tutti i messaggi tranne il primo
    $sql_delete_group_messages = "
        DELETE FROM sms
        WHERE gruppo_id = $gruppo_id 
        AND ora_spedizione < '$date_limit'
        AND ora_spedizione != '$primo_messaggio'
    ";
    gdrcd_query($sql_delete_group_messages);

    // Controlla se rimane solo un messaggio, se sì, cancella anche il gruppo
    $sql_count_messages = "
        SELECT COUNT(*) as count
        FROM sms
        WHERE gruppo_id = $gruppo_id
    ";
    $count_result = gdrcd_query($sql_count_messages, 'fetch');

    if ($count_result['count'] == 1) {
        // Elimina l'ultimo messaggio e rimuovi il gruppo
        gdrcd_query("DELETE FROM sms WHERE gruppo_id = $gruppo_id");
        gdrcd_query("DELETE FROM partecipazione_gruppo WHERE gruppo_id = $gruppo_id");
    }
}


#### 3. **Pulizia delle conversazioni individuali:**

// Seleziona le conversazioni individuali che hanno messaggi più vecchi di 3 mesi
$sql_individuali = "
    SELECT id_conversazione, MIN(ora_spedizione) as primo_messaggio
    FROM sms
    WHERE tipo_messaggio = 'individuale'
    GROUP BY id_conversazione
    HAVING primo_messaggio < '$date_limit'
";

$result_individuali = gdrcd_query($sql_individuali, 'result');

while ($row_individuale = gdrcd_query($result_individuali, 'fetch')) {
    $id_conversazione = $row_individuale['id_conversazione'];
    $primo_messaggio = $row_individuale['primo_messaggio'];

    // Cancella tutti i messaggi tranne il primo
    $sql_delete_individual_messages = "
        DELETE FROM sms
        WHERE id_conversazione = $id_conversazione 
        AND ora_spedizione < '$date_limit'
        AND ora_spedizione != '$primo_messaggio'
    ";
    gdrcd_query($sql_delete_individual_messages);

    // Controlla se rimane solo un messaggio, se sì, cancella anche la conversazione
    $sql_count_messages = "
        SELECT COUNT(*) as count
        FROM sms
        WHERE id_conversazione = $id_conversazione
    ";
    $count_result = gdrcd_query($sql_count_messages, 'fetch');

    if ($count_result['count'] == 1) {
        // Elimina l'ultimo messaggio e rimuovi la conversazione
        gdrcd_query("DELETE FROM sms WHERE id_conversazione = $id_conversazione");
        gdrcd_query("DELETE FROM conversazioni_individuali WHERE id_conversazione = $id_conversazione");
    }
}     