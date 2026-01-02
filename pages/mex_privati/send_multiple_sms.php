<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');  // Includi il file che contiene la funzione gdrcd_query

// Recuperiamo i dati inviati dal form
$mittente = $_SESSION['login']; // Il mittente è l'utente attualmente loggato
$destinatari = isset($_POST['destinatari']) ? explode(',', $_POST['destinatari']) : []; // I destinatari separati da virgola
$ongame_offgame = isset($_POST['ongame_offgame']) ? $_POST['ongame_offgame'] : ''; // La categoria (ongame/offgame)
$messaggio = isset($_POST['messaggio']) ? gdrcd_filter('in', $_POST['messaggio']) : ''; // Il testo del messaggio, escapato

// Itera su ciascun destinatario
foreach ($destinatari as $destinatario) {
    $destinatario = trim($destinatario); // Rimuovi eventuali spazi bianchi

    // Controlla se esiste già una conversazione individuale ON/OFF con il destinatario
    $sql_verifica_conversazione = "
    SELECT ci.id_conversazione
    FROM conversazioni_individuali ci
    JOIN sms s ON ci.id_conversazione = s.id_conversazione
    WHERE 
        (
            (s.mittente_nome = '$mittente' AND s.destinatario_nome = '$destinatario')
            OR (s.mittente_nome = '$destinatario' AND s.destinatario_nome = '$mittente')
        )
    AND s.ongame = $ongame_offgame
";

    $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

    if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
        // Conversazione esistente: Recupera l'ID della conversazione
        $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
        $id_conversazione_esistente = $row_conversazione['id_conversazione'];

        // Inserisci il messaggio nella conversazione esistente
        $sql_inserisci_messaggio = "
    INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
    VALUES ('$mittente', '$destinatario', '$messaggio', $id_conversazione_esistente, 'individuale', $ongame_offgame, NOW())
";
        gdrcd_query($sql_inserisci_messaggio);

        // Aggiorna il campo lettura nella tabella conversazione_individuale
        $sql_aggiorna_lettura = "UPDATE conversazioni_individuali 
        SET lettura = IF(utente_nome = '$destinatario', 0, 1)
        WHERE id_conversazione = $id_conversazione_esistente";
        gdrcd_query($sql_aggiorna_lettura);
    } else {
        // Conversazione non esistente: Crea una nuova conversazione
        // Trova l'ultimo id_conversazione e calcola il nuovo id_conversazione
        $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
        $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
        $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
        $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

        // Inserisci il nuovo messaggio nella tabella sms
        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('$mittente', '$destinatario', '$messaggio', $nuovo_id_conversazione, 'individuale', $ongame_offgame, NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Inserisci la nuova conversazione nella tabella conversazioni_individuali
        $sql_inserisci_conversazione = "
            INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
            VALUES 
            ($nuovo_id_conversazione, '$mittente', 1),
            ($nuovo_id_conversazione, '$destinatario', 0)
        ";
        gdrcd_query($sql_inserisci_conversazione);
    }
}


// Reindirizza alla pagina principale o visualizza un messaggio di successo
echo '<script type="text/javascript">
        window.top.location.href = "../../main.php?page=messages_center&offset=0";
      </script>';
exit();

?>
