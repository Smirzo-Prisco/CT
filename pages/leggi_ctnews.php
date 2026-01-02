<?php
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();
$pg = $_POST['pg'];
$query = gdrcd_query("UPDATE personaggio SET personaggio.ctnews_letto=1 WHERE nome = '".$pg."'");
?>