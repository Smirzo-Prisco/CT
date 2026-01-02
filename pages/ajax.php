<?php
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();
$id = (int)$_POST['id'];
$result = gdrcd_query('SELECT nome_ruolo FROM ruolo WHERE gilda = "'.$id.'"', "result");
$html = '';
 //expecting just on row
 while($role = gdrcd_query($result, 'fetch')) {
 	$ruolo = $role['nome_ruolo'];
    $html .= '<input type="checkbox" id="'.$ruolo.'" name="ruolo[]" value="'.$ruolo.'"><label for="'.$ruolo.'">'.$ruolo.'</label><br>';  
}


header('Content-Type: application/json');
echo $html;
?>