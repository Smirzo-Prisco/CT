<?php
echo "<div>Debug: Il nome del destinatario è: $nome</div>";

// Verifica se esiste già una conversazione tra Segnalazione e il destinatario
$sql_verifica_conversazione = "
    SELECT id_conversazione 
    FROM sms 
    WHERE mittente_nome = 'Segnalazione' 
    AND destinatario_nome = '" . gdrcd_filter('in', $nome) . "' 
    LIMIT 1
";
$result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

// Messaggio di debug per verificare se la query è andata a buon fine
if (!$result_verifica_conversazione) {
    echo '<div class="error">Errore nella query di verifica conversazione: ' . gdrcd_query($result_verifica_conversazione, 'error') . '</div>';
    return;
}

if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
    // La conversazione esiste già, recupera l'id
    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
    $id_conversazione = $row_conversazione['id_conversazione'];

    // Inserisci il nuovo messaggio nella conversazione esistente
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Aggiorna il campo lettura nella tabella conversazioni_individuali
    $sql_aggiorna_lettura = "
        UPDATE conversazioni_individuali 
        SET lettura = 0 
        WHERE id_conversazione = $id_conversazione 
        AND utente_nome = '" . gdrcd_filter('in', $nome) . "'
    ";
    gdrcd_query($sql_aggiorna_lettura);
} else {
    // La conversazione non esiste, crea una nuova conversazione
    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

    // Inserisci il nuovo messaggio nella tabella sms
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Inserisci la nuova conversazione nella tabella conversazioni_individuali
    $sql_inserisci_conversazione = "
        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
        VALUES 
        ($nuovo_id_conversazione, '" . gdrcd_filter('in', $nome) . "', 0)
    ";
    gdrcd_query($sql_inserisci_conversazione);
}
