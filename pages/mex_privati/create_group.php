<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');

// Recupera il nome utente loggato
$login = $_SESSION['login'];

// Recupera il titolo del gruppo e i partecipanti dalla richiesta POST
$titolo_gruppo = isset($_POST['titolo_gruppo']) ? gdrcd_filter('in', $_POST['titolo_gruppo']) : '';
$partecipanti = isset($_POST['partecipanti']) ? $_POST['partecipanti'] : [];

if ($titolo_gruppo !== '' && !empty($partecipanti)) {
    // Inizia una transazione per garantire l'integrità dei dati
    gdrcd_query("START TRANSACTION");

    try {
        // Ottieni il massimo id_conversazione esistente
        $max_id = gdrcd_query("SELECT max(id_conversazione) as max FROM sms"); 
        $max_id_conversazione = $max_id['max'];

        

        // Se non ci sono risultati, imposta il massimo ID a 0
        if ($max_id_conversazione === null) {
            $max_id_conversazione = 0;
        }

        // Incrementa di 1 per ottenere il nuovo id_conversazione
        $new_id_conversazione = $max_id_conversazione + 1;

        

        // Inserisci il gruppo nella tabella sms con un messaggio predefinito
        $messaggio_iniziale = "È stato creato un nuovo gruppo!<br>I gruppi sono <u>solo ed esclusivamente</u> ON.";
        $sql_insert = "INSERT INTO sms (mittente_nome, titolo_gruppo, tipo_messaggio, ongame, testo, id_conversazione, gruppo_id) 
                       VALUES ('System', '$titolo_gruppo', 'gruppo', 1, '$messaggio_iniziale', $new_id_conversazione, $new_id_conversazione)";
        if (!gdrcd_query($sql_insert)) {
            echo "Errore nell'inserimento del nuovo gruppo.<br>";
            echo "Errore SQL: " . gdrcd_query($sql_insert, 'error') . "<br>";
        }

        // Il gruppo_id è uguale a new_id_conversazione
        $gruppo_id = $new_id_conversazione;

        // Aggiungi il creatore del gruppo (login) con lettura settata su 1
        $sql_partecipante = "INSERT INTO partecipazione_gruppo (gruppo_id, utente_nome, lettura) 
                             VALUES ($gruppo_id, '$login', 1)";
        gdrcd_query($sql_partecipante);

        // Aggiungi gli altri partecipanti con lettura settata su 0
        foreach ($partecipanti as $partecipante) {
            $sql_partecipante = "INSERT INTO partecipazione_gruppo (gruppo_id, utente_nome, lettura) 
                                 VALUES ($gruppo_id, '".gdrcd_filter('in', $partecipante)."', 0)";
            gdrcd_query($sql_partecipante);
        }

        // Conferma la transazione
        gdrcd_query("COMMIT");

        // Reindirizza alla pagina del contenuto del gruppo dopo qualche secondo
        echo "<p>Gruppo creato con successo! Verrai reindirizzato alla pagina del gruppo...</p>";
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'message_content.php?gruppo_id=$gruppo_id';
                }, 2000); // Reindirizza dopo 2 secondi
              </script>";

    } catch (Exception $e) {
        // Se c'è un errore, annulla la transazione
        gdrcd_query("ROLLBACK");
        echo "Errore nella creazione del gruppo: " . $e->getMessage();
    }
} else {
    echo "Errore: titolo del gruppo o partecipanti mancanti.";
}
?>
