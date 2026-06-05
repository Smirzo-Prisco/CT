<?php
require_once 'config.inc.php';
require_once 'includes/functions.inc.php';
require_once 'includes/custom_functions.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Si è verificato un errore. Riprova più tardi.';
    exit;
}

if (empty($_POST['email']) || empty($_POST['problem'])) {
    echo 'Si è verificato un errore. Assicurati di compilare tutti i campi.';
    exit;
}

$to      = $PARAMETERS['info']['webmaster_email'];
$subject = 'Segnalazione problema';
$from    = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
$problem = htmlspecialchars($_POST['problem'], ENT_QUOTES, 'UTF-8');
$message = "<p>Segnalazione da: <b>{$from}</b></p><p>Problema segnalato:</p><p>{$problem}</p>";

if (send_mail($to, $subject, $message)) {
    echo 'La segnalazione è stata inviata con successo.';
} else {
    echo 'Si è verificato un errore durante l\'invio della segnalazione.';
}
