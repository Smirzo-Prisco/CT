<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');  // Includi il file che contiene la funzione gdrcd_query

// Recuperiamo i dati inviati dal form
$mittente = $_SESSION['login']; // Il mittente è l'utente attualmente loggato
$destinatario = isset($_POST['destinatario']) ? $_POST['destinatario'] : ''; // Il destinatario selezionato
$ongame_offgame = isset($_POST['ongame_offgame']) ? $_POST['ongame_offgame'] : ''; // La categoria (ongame/offgame)
$messaggio = isset($_POST['messaggio']) ? gdrcd_filter('in', $_POST['messaggio']) : ''; // Il testo del messaggio, escapato
$id_conversazione = isset($_POST['id_conversazione']) ? $_POST['id_conversazione'] : ''; // L'id della conversazione se esistente
$gruppo_id = isset($_POST['gruppo_id']) ? $_POST['gruppo_id'] : ''; // L'id del gruppo se esistente
$is_globale = isset($_POST['is_globale']) ? $_POST['is_globale'] : ''; // L'id del gruppo se esistente
$is_archivio = ($destinatario === $mittente && $destinatario !== '');

if ($is_archivio) {
    // Caso 3: Archivio personale
    
    // Verifica se esiste già una conversazione nell'archivio
    $sql_verifica_archivio = "
        SELECT id_conversazione
        FROM conversazioni_individuali
        WHERE utente_nome = '$mittente' AND id_conversazione IN (
            SELECT id_conversazione 
            FROM sms 
            WHERE mittente_nome = '$mittente' AND destinatario_nome = '$mittente'
        )
    ";
    $result_verifica_archivio = gdrcd_query($sql_verifica_archivio, 'result');
    
    if (gdrcd_query($result_verifica_archivio, 'num_rows') > 0) {
        // Archivio esistente: Recupera l'ID della conversazione
        $row_archivio = gdrcd_query($result_verifica_archivio, 'fetch');
        $id_conversazione_archivio = $row_archivio['id_conversazione'];
        
        // Inserisci il nuovo messaggio nell'archivio esistente
        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('$mittente', '$mittente', '$messaggio', $id_conversazione_archivio, 'individuale', '0', NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Reindirizza alla pagina dell'archivio
        header("Location: message_content.php?conversazione_id=$id_conversazione_archivio");
        exit();
        
    } else {
        // Archivio non esistente: Crea una nuova conversazione
        // Trova l'ultimo id_conversazione e calcola il nuovo id_conversazione
        $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
        $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
        $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
        $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

        // Inserisci il nuovo messaggio nella tabella sms
        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('$mittente', '$mittente', '$messaggio', $nuovo_id_conversazione, 'individuale', '0', NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Inserisci la nuova conversazione nella tabella conversazioni_individuali
        $sql_inserisci_conversazione = "
            INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
            VALUES 
            ($nuovo_id_conversazione, '$mittente', 1)
        ";
        gdrcd_query($sql_inserisci_conversazione);

        // Reindirizza alla pagina della nuova conversazione
        header("Location: message_content.php?conversazione_id=$nuovo_id_conversazione");
        exit();
    }
} elseif ($gruppo_id > 0) {
        // Caso 2b: È un gruppo
        // Inserisci il messaggio nella tabella sms
        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame, is_globale, ora_spedizione)
            VALUES ('$mittente', '$messaggio', $gruppo_id, $gruppo_id, 'gruppo', $ongame_offgame, $is_globale, NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Aggiorna il campo lettura per i partecipanti del gruppo
        $sql_aggiorna_lettura = "
            UPDATE partecipazione_gruppo
            SET lettura = IF(utente_nome = '$mittente', 1, 0)
            WHERE gruppo_id = $gruppo_id
        ";
        gdrcd_query($sql_aggiorna_lettura);

        // Notifica i membri del gruppo (escluso il mittente)
        $members_result = gdrcd_query("SELECT utente_nome FROM partecipazione_gruppo WHERE gruppo_id = $gruppo_id AND utente_nome != '$mittente'", 'result');
        while ($m = gdrcd_query($members_result, 'fetch')) {
            notifySocketServer('dm:update', 'dm:' . $m['utente_nome']);
        }
        gdrcd_query($members_result, 'free');

        // **NUOVO BLOCCO DI CODICE**: Aggiunta dei nuovi utenti al gruppo globale solo se is_globale è 1
if ($is_globale == 1) {
    // Trova tutti gli utenti che non sono ancora nel gruppo globale
    $sql_nuovi_utenti = "
        SELECT nome 
        FROM personaggio 
        WHERE nome NOT IN (
            SELECT utente_nome 
            FROM partecipazione_gruppo 
            WHERE gruppo_id = $gruppo_id
        )
    ";
    $result_nuovi_utenti = gdrcd_query($sql_nuovi_utenti, 'result');

    // Inserisci i nuovi utenti in partecipazione_gruppo
    while ($row_nuovo_utente = gdrcd_query($result_nuovi_utenti, 'fetch')) {
        $nome_nuovo_utente = gdrcd_filter('in', $row_nuovo_utente['nome']);
        $sql_inserisci_nuovo_utente = "
            INSERT INTO partecipazione_gruppo (utente_nome, gruppo_id, lettura)
            VALUES ('$nome_nuovo_utente', $gruppo_id, 0)
        ";
        gdrcd_query($sql_inserisci_nuovo_utente);
    }
}

        // Reindirizza alla pagina del gruppo
        header("Location: message_content.php?gruppo_id=$gruppo_id");
        exit();

    } elseif ($id_conversazione > 0) {
        // Caso 2a: È una conversazione individuale
        // Inserisci il messaggio nella tabella sms
        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('$mittente', '$destinatario', '$messaggio', $id_conversazione, 'individuale', $ongame_offgame, NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Aggiorna il campo lettura nella tabella conversazione_individuale
        $sql_aggiorna_lettura = "UPDATE conversazioni_individuali 
        SET lettura = IF(utente_nome = '$destinatario', 0, 1)
        WHERE id_conversazione = $id_conversazione";
        gdrcd_query($sql_aggiorna_lettura);

        notifySocketServer('dm:update', 'dm:' . $destinatario);
        header("Location: message_content.php?conversazione_id=$id_conversazione");
        exit();
    } else {
    // Caso 1: La conversazione non esiste (nuova conversazione individuale)

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

    // Inserisci la nuova conversazione nella tabella conversazione_individuale
    $sql_inserisci_conversazione = "
        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
        VALUES 
        ($nuovo_id_conversazione, '$mittente', 1),
        ($nuovo_id_conversazione, '$destinatario', 0)
    ";
    gdrcd_query($sql_inserisci_conversazione);

    notifySocketServer('dm:update', 'dm:' . $destinatario);
    header("Location: message_content.php?conversazione_id=$nuovo_id_conversazione");
    exit();
}
?>