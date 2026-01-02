<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');

// Debug: verifica se i dati sono stati inviati
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}


// Variabili comuni
$login = $_SESSION['login'];
$gruppo_id = intval($_POST['gruppo_id']);

// Recupera il valore di ongame per il gruppo corrente
$sql_ongame = "
    SELECT ongame 
    FROM sms 
    WHERE gruppo_id = $gruppo_id 
    LIMIT 1
";
$result_ongame = gdrcd_query($sql_ongame, 'result');
$row_ongame = gdrcd_query($result_ongame, 'fetch');
$ongame_value = $row_ongame['ongame'];

// Funzione per aggiornare il campo lettura per tutti i partecipanti eccetto chi ha effettuato l'operazione
function aggiorna_lettura($gruppo_id, $login) {
    $sql_aggiorna_lettura = "
        UPDATE partecipazione_gruppo 
        SET lettura = 0 
        WHERE gruppo_id = $gruppo_id 
        AND utente_nome != '$login'
    ";
    gdrcd_query($sql_aggiorna_lettura);
}

// Funzione per aggiornare il campo lettura per tutti i partecipanti
function aggiorna_lettura_per_tutti($gruppo_id) {
    $sql_aggiorna_lettura = "
        UPDATE partecipazione_gruppo 
        SET lettura = 0 
        WHERE gruppo_id = $gruppo_id
    ";
    gdrcd_query($sql_aggiorna_lettura);
}

// Funzione per reindirizzare alla pagina del gruppo
function redirect_to_group($gruppo_id) {
    header("Location: message_content.php?gruppo_id=$gruppo_id");
    exit();
}

// Funzione per reindirizzare alla schermata principale dei messaggi privati senza un frame aperto
function redirect_to_messaggi_iniziale() {
    echo '<script type="text/javascript">
        window.top.location.href = "../../main.php?page=messages_center&offset=0";
      </script>';
}

if (isset($_POST['modifica_titolo']) && isset($_POST['nuovo_titolo'])) {
    $nuovo_titolo = gdrcd_filter('in', $_POST['nuovo_titolo']);

    // Aggiorna il titolo del gruppo
    $sql_aggiorna_titolo = "
        UPDATE sms 
        SET titolo_gruppo = '$nuovo_titolo' 
        WHERE gruppo_id = $gruppo_id 
        AND mittente_nome = 'System'        
        LIMIT 1
    ";
    gdrcd_query($sql_aggiorna_titolo);

    // Inserisci un nuovo messaggio per notificare la modifica del titolo
    $testo_modifica = "Il gruppo è stato rinominato in <b><u>" . gdrcd_filter('in', $nuovo_titolo) . "</u></b>.";
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame)
        VALUES ('System', '$testo_modifica', $gruppo_id, $gruppo_id, 'gruppo', '$ongame_value')
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Aggiorna il campo lettura per tutti i partecipanti del gruppo eccetto chi ha effettuato la modifica
    aggiorna_lettura($gruppo_id, $login);

    // Ridireziona alla pagina del gruppo
    redirect_to_group($gruppo_id);
}

// Gestione aggiunta partecipante
if (isset($_POST['aggiungi_partecipante']) && !empty($_POST['aggiungi_partecipanti'])) {
    // Prendi il primo elemento dell'array
    $nuovo_partecipante = gdrcd_filter('in', $_POST['aggiungi_partecipanti'][0]); // Prende il primo partecipante

    // Verifica se il nome del partecipante è corretto
    if (!$nuovo_partecipante) {
        echo "Errore: il partecipante non è stato selezionato correttamente.";
        exit();
    }

    // Inserisci il nuovo partecipante nella tabella partecipazione_gruppo
    $sql_inserisci_partecipante = "
        INSERT INTO partecipazione_gruppo (gruppo_id, utente_nome, lettura)
        VALUES ($gruppo_id, '$nuovo_partecipante', 0)
    ";
    gdrcd_query($sql_inserisci_partecipante);

    // Inserisci un nuovo messaggio per notificare l'aggiunta del partecipante
    $testo_aggiunta = "<b><u>" . htmlspecialchars($nuovo_partecipante) . "</u></b> è stato aggiunto al gruppo.";
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame)
        VALUES ('System', '$testo_aggiunta', $gruppo_id, $gruppo_id, 'gruppo', '$ongame_value')
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Aggiorna il campo lettura per tutti i partecipanti del gruppo eccetto chi ha aggiunto il nuovo partecipante
    aggiorna_lettura($gruppo_id, $login);

    // Ridireziona alla pagina del gruppo dopo l'aggiunta
    redirect_to_group($gruppo_id);
}








// Gestione rimozione partecipante
if (isset($_POST['rimuovi_partecipante']) && !empty($_POST['rimuovi_partecipanti'])) {
    $partecipante_da_rimuovere = gdrcd_filter('in', $_POST['rimuovi_partecipanti']);

    // Rimuovi il partecipante dalla tabella partecipazione_gruppo
    $sql_rimuovi_partecipante = "
        DELETE FROM partecipazione_gruppo 
        WHERE gruppo_id = $gruppo_id 
        AND utente_nome = '$partecipante_da_rimuovere'
    ";
    gdrcd_query($sql_rimuovi_partecipante);

    // Inserisci un nuovo messaggio per notificare la rimozione del partecipante
    $testo_rimozione = "<b><u>" . htmlspecialchars($partecipante_da_rimuovere) . "</u></b> è stato rimosso dal gruppo.";
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame)
        VALUES ('System', '$testo_rimozione', $gruppo_id, $gruppo_id, 'gruppo', '$ongame_value')
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Aggiorna il campo lettura per tutti i partecipanti del gruppo eccetto chi ha rimosso il partecipante
    aggiorna_lettura($gruppo_id, $login);

    // Ridireziona alla pagina del gruppo dopo la rimozione
    redirect_to_group($gruppo_id);
}







// Gestione abbandono gruppo
if (isset($_POST['abbandona_gruppo'])) {
    // Rimuovi l'utente corrente dalla tabella partecipazione_gruppo
    $sql_rimuovi_utente = "
        DELETE FROM partecipazione_gruppo 
        WHERE gruppo_id = $gruppo_id 
        AND utente_nome = '$login'
    ";
    gdrcd_query($sql_rimuovi_utente);

    // Inserisci un nuovo messaggio per notificare l'abbandono del gruppo
    $testo_abbandono = "<b><u>" . htmlspecialchars($login) . "</u></b> ha abbandonato il gruppo.";
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame)
        VALUES ('System', '$testo_abbandono', $gruppo_id, $gruppo_id, 'gruppo', '$ongame_value')
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Aggiorna il campo lettura per tutti i partecipanti del gruppo
    aggiorna_lettura_per_tutti($gruppo_id);

    // Ridireziona alla pagina dei messaggi privati dopo l'abbandono
    redirect_to_messaggi_iniziale();
}
?>