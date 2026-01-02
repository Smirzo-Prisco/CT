<?php
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

$handleDBConnection = gdrcd_connect();

$id = (int)$_POST['id'];
$left = (int)$_POST['left'];
$top = (int)$_POST['top'];
$role = gdrcd_filter('in', $_POST['role']);
$name = gdrcd_filter('in', $_POST['name']);

// Controllo se esiste già un pezzo con stesso ID stanza e nome
$check = gdrcd_query("SELECT id FROM chess WHERE id = $id AND nome = '$name'");

if ($check) {
    // Esiste già -> aggiorno solo posizione e ruolo
    gdrcd_query("UPDATE chess SET left_cd = $left, top_cd = $top, ruolo = '$role' WHERE id = $id AND nome = '$name'");
} else {
    // Non esiste -> inserisco nuovo pezzo
    gdrcd_query("INSERT INTO chess (id, left_cd, top_cd, ruolo, nome) VALUES ($id, $left, $top, '$role', '$name')");
}
?>
