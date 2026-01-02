<?php
session_start();
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

if(isset($_POST['nome_personaggio'])) {
    $nome_personaggio = gdrcd_filter('in', $_POST['nome_personaggio']);
    
    // Esegui la query per ottenere i dati del personaggio
    $query = "SELECT note_fato, particolari, salute, integrita, notorieta, soldi FROM personaggio WHERE nome = '$nome_personaggio' LIMIT 1";
    $result = gdrcd_query($query, 'fetch');
    
    if ($result) {
        // Restituisci i dati in formato JSON
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Character not found']);
    }
} else {
    echo json_encode(['error' => 'No character name provided']);
}
?>
