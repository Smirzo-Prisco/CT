<?php
session_start();

include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

// Recupera i dati inviati tramite POST
$comment_id = $_POST['comment_id'];
$login = $_POST['login'];

// Controlla che tutti i dati necessari siano presenti
if (!empty($comment_id) && !empty($login)) {
    // Recupera il ruolo dell'utente dalla sessione
    $is_admin = $_SESSION['admin'] == 1;

    // Verifica se il commento esiste e qual è l'autore
    $check_comment_query = "
        SELECT autore, id_messaggio_padre 
        FROM tokyobook_bacheca 
        WHERE id_messaggio = '" . gdrcd_filter('num', $comment_id) . "'";
    $comment_result = gdrcd_query($check_comment_query, 'result');
    $comment_row = gdrcd_query($comment_result, 'fetch');

    // Verifica se l'utente è l'autore del commento o è un amministratore
    if ($comment_row && ($comment_row['autore'] == $login || $is_admin)) {
        // Verifica se si tratta di un messaggio padre (id_messaggio_padre == -1)
        if ($comment_row['id_messaggio_padre'] == -1) {
            // È un messaggio padre, cancella anche tutti i commenti associati
            $delete_comments_query = "
                DELETE FROM tokyobook_bacheca 
                WHERE id_messaggio_padre = '" . gdrcd_filter('num', $comment_id) . "' 
                OR id_messaggio = '" . gdrcd_filter('num', $comment_id) . "'";
            gdrcd_query($delete_comments_query);

            // Rimuove i like associati al messaggio padre e ai commenti
            $delete_likes_query = "
                DELETE FROM tokyobook_likes 
                WHERE id_messaggio IN (
                    SELECT id_messaggio 
                    FROM tokyobook_bacheca 
                    WHERE id_messaggio_padre = '" . gdrcd_filter('num', $comment_id) . "' 
                    OR id_messaggio = '" . gdrcd_filter('num', $comment_id) . "'
                )";
            gdrcd_query($delete_likes_query);

            // Invia una risposta di successo
            echo json_encode([
                'success' => true,
                'message' => 'Messaggio e commenti cancellati con successo.'
            ]);
        } else {
            // È un commento singolo, cancellalo
            $delete_comment_query = "
                DELETE FROM tokyobook_bacheca 
                WHERE id_messaggio = '" . gdrcd_filter('num', $comment_id) . "'";
            gdrcd_query($delete_comment_query);

            // Rimuove i like associati al commento
            $delete_likes_query = "
                DELETE FROM tokyobook_likes 
                WHERE id_messaggio = '" . gdrcd_filter('num', $comment_id) . "'";
            gdrcd_query($delete_likes_query);

            // Invia una risposta di successo
            echo json_encode([
                'success' => true,
                'message' => 'Commento cancellato con successo.'
            ]);
        }
    } else {
        // L'utente non è autorizzato a cancellare il commento
        echo json_encode([
            'success' => false,
            'message' => 'Non sei autorizzato a cancellare questo commento.'
        ]);
    }
} else {
    // Dati mancanti, invia una risposta di errore
    echo json_encode([
        'success' => false,
        'message' => 'Dati mancanti per eseguire l\'operazione.'
    ]);
}
?>
