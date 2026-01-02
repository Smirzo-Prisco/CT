<?php
session_start();

include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

// Recupera i dati inviati tramite POST
$id_messaggio = gdrcd_filter('num', $_POST['id_messaggio']);
$testo = gdrcd_filter('in', $_POST['testo']);
$tipo = gdrcd_filter('in', $_POST['tipo']);

// Verifica che tutti i dati necessari siano presenti
if (!empty($id_messaggio) && !empty($testo)) {
    // Esegui l'aggiornamento del messaggio/commento nel database
    $query_update = "
        UPDATE tokyobook_bacheca 
        SET testo = '$testo'
        WHERE id_messaggio = $id_messaggio";

    $update_result = gdrcd_query($query_update);

    // Verifica se l'aggiornamento è avvenuto con successo
    if ($update_result) {
        // Invia una risposta di successo
        echo json_encode(['success' => true]);
    } else {
        // Invia una risposta di errore
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento del messaggio.']);
    }
} else {
    // Dati mancanti, invia una risposta di errore
    echo json_encode(['success' => false, 'message' => 'Dati mancanti per eseguire l\'aggiornamento.']);
}
?>