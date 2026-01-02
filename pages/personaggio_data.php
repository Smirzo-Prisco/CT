<?php
session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

if(isset($_POST['nome_personaggio'])) {
    $nome_personaggio = gdrcd_filter('in', $_POST['nome_personaggio']);
    $query = "SELECT note_fato, particolari, salute, integrita, notorieta, soldi FROM personaggio WHERE nome = '$nome_personaggio'";
    $result = gdrcd_query($query);

    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Nessun risultato trovato']);
    }
} else {
    echo json_encode(['error' => 'Nome personaggio non inviato']);
}
?>
