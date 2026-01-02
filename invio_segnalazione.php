<?php
// Verifica se il modulo è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Controlla se sono stati ricevuti tutti i campi necessari
    if (isset($_POST['email']) && isset($_POST['problem'])) {
        // Recupera i dati dal modulo
        $email = $_POST['email'];
        $problem = $_POST['problem'];

        // Email a cui inviare la segnalazione
        $to = "marko.marrone@gmail.com";
        
        // Soggetto dell'email
        $subject = "Segnalazione problema";

        // Messaggio dell'email
        $message = "Segnalazione di problema da: " . $email . "\n\n";
        $message .= "Problema segnalato:\n" . $problem;

        // Intestazioni dell'email
        mail($to, $subject, $message, 'From: ' . $_POST['email']);

        // Invia l'email
        if (mail($to, $subject, $message)) {
            // Messaggio di successo
            echo "La segnalazione è stata inviata con successo.";
        } else {
            // Messaggio di errore
            echo "Si è verificato un errore durante l'invio della segnalazione.";
        }
    } else {
        // Messaggio di errore se non sono stati ricevuti tutti i campi
        echo "Si è verificato un errore. Assicurati di compilare tutti i campi.";
    }
} else {
    // Messaggio di errore se il modulo non è stato inviato correttamente
    echo "Si è verificato un errore. Riprova più tardi.";
}
?>