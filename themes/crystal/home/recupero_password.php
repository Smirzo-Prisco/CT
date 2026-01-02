<?php
/* Includo i file necessari */
include('../../../config.inc.php');
include('../../../includes/functions.inc.php');
include('../../../includes/custom_functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

$msg = 'Errore invio dati';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $json_data = file_get_contents('php://input'); // Legge il corpo della richiesta
    $dati = json_decode($json_data, true); // Decodifica il JSON in un array associativo

    if ($dati !== null) {
        $email = (!isset($dati['email']) ? '' : htmlspecialchars($dati['email']));
        $exists = count(gdrcd_query("SELECT * FROM personaggio where email = '$email'"));

        if($exists > 0) {
            $newPass = gdrcd_genera_pass();

            gdrcd_query("UPDATE personaggio SET pass = '" . gdrcd_encript($newPass) . "' where email = '$email'");

            $subject = 'Crystal Tokyo - Recupero password';
            $message = "Ciao,<br>di seguito la nuova password che ti è stata assegnata:<br><br><b>".$newPass."</b>";
            $message .= "<br><br>Ti ricordiamo di non condividerla e di cambiarla al prossimo accesso.";
            $message .= "<br><br>Lo staff di <a href=\"https://".$_SERVER['SERVER_NAME']."\">Crystal Tokyo</a>";
            $message .= "<img src=\"https://".$_SERVER['SERVER_NAME']."/themes/crystal/imgs/maps/mappa_notte.png\">";
            $message .= "<br><br><br><br><br><br>";

            $send = send_mail($email, $subject, $message);

            if($send) $msg = "Hai ricevuto un'e-mail al tuo indirizzo di posta elettronica. Se non la trovi nella posta in arrivo, verifica nella cartella spam.";
            else $msg = "Errore nell'invio dell'e-mail";
        } else $msg = "Non esistono utenti registrati con questo indirizzo. Verifica di aver scritto correttamente l'e-mail e riprova.";
    } else $msg = "Errore nella decodifica del JSON.";
} else $msg = "Metodo non valido.";

echo json_encode(array("response" => $msg));