<?php
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

// Recupera i dati inviati tramite POST
$post_id = $_POST['post_id'];
$action = $_POST['action'];
$login = $_POST['login'];

// Controlla che tutti i dati necessari siano presenti
if (!empty($post_id) && !empty($action) && !empty($login)) {
    // Verifica se l'utente ha già messo "Mi Piace" a questo post/commento
    $check_like_query = "
        SELECT * FROM tokyobook_likes 
        WHERE id_messaggio = '" . gdrcd_filter('num', $post_id) . "' 
        AND login = '" . gdrcd_filter('in', $login) . "'";
    $result_like_query = gdrcd_query($check_like_query, 'result');
    $like_exists = (gdrcd_query($result_like_query, 'num_rows') > 0);

    // Recupera i dettagli del post/commento e dell'autore
    $post_details_query = "
        SELECT autore, alias, tipo, id_messaggio_padre 
        FROM tokyobook_bacheca 
        WHERE id_messaggio = '" . gdrcd_filter('num', $post_id) . "'";
    $post_details_result = gdrcd_query($post_details_query, 'result');
    $post_details = gdrcd_query($post_details_result, 'fetch');

    // Conta il numero di like attuale prima della modifica
    $count_like_query = "
        SELECT COUNT(*) AS like_count 
        FROM tokyobook_likes 
        WHERE id_messaggio = '" . gdrcd_filter('num', $post_id) . "'";
    $like_count_result = gdrcd_query($count_like_query, 'result');
    $like_count_row = gdrcd_query($like_count_result, 'fetch');
    $current_like_count = $like_count_row['like_count'];

    // Gestione dell'incremento/decremento notorietà in base all'azione
    $notorieta_change = 0; // Valore predefinito

    if ($action === 'like' && !$like_exists) {
        // Inserisci il like se non esiste ancora
        $insert_like_query = "
            INSERT INTO tokyobook_likes (id_messaggio, login) 
            VALUES ('" . gdrcd_filter('num', $post_id) . "', '" . gdrcd_filter('in', $login) . "')";
        gdrcd_query($insert_like_query);
        $current_like_count++; // Incrementa il numero di like

        // Controlla se ha raggiunto esattamente una delle soglie per aumentare la notorietà
        if ($current_like_count == 5) {
            $notorieta_change = 1;
        } elseif ($current_like_count == 20) {
            $notorieta_change = 2;
        } elseif ($current_like_count == 50) {
            $notorieta_change = 3;
        } elseif ($current_like_count == 100) {
            $notorieta_change = 4;
        }

    } elseif ($action === 'unlike' && $like_exists) {
        // Rimuovi il like se esiste già
        $delete_like_query = "
            DELETE FROM tokyobook_likes 
            WHERE id_messaggio = '" . gdrcd_filter('num', $post_id) . "' 
            AND login = '" . gdrcd_filter('in', $login) . "'";
        gdrcd_query($delete_like_query);
        $current_like_count--; // Decrementa il numero di like

        // Controlla se ha perso esattamente una delle soglie per ridurre la notorietà
        if ($current_like_count == 4) {
            $notorieta_change = -1;
        } elseif ($current_like_count == 19) {
            $notorieta_change = -2;
        } elseif ($current_like_count == 49) {
            $notorieta_change = -3;
        } elseif ($current_like_count == 99) {
            $notorieta_change = -4;
        }
    }

    // Aggiorna la notorietà solo se c'è un cambiamento e il post/commento è pubblico e non ha alias
    // Includiamo la verifica su `id_messaggio_padre` per considerare anche i commenti
    if ($notorieta_change != 0 && $post_details['alias'] == 0 && $post_details['tipo'] == 'tokyobook') {
        $update_notorieta_query = "
            UPDATE personaggio 
            SET notorieta = notorieta + " . gdrcd_filter('num', $notorieta_change) . " 
            WHERE nome = '" . gdrcd_filter('in', $post_details['autore']) . "'";
        gdrcd_query($update_notorieta_query);
        
        $punti_master = gdrcd_query("INSERT INTO Punti (nome, notorieta, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_SESSION["login"])."', '1', NOW(), '".gdrcd_filter('num', $_POST['post_id'])."', 'Like Tokyobook')"); 

    }

    // Conta il numero aggiornato di like per questo post/commento
    $count_like_query = "
        SELECT COUNT(*) AS like_count 
        FROM tokyobook_likes 
        WHERE id_messaggio = '" . gdrcd_filter('num', $post_id) . "'";
    $like_count_result = gdrcd_query($count_like_query, 'result');
    $like_count_row = gdrcd_query($like_count_result, 'fetch');
    $new_like_count = $like_count_row['like_count'];

    // Invia la risposta al client con il nuovo numero di like
    echo json_encode([
        'success' => true,
        'new_like_count' => $new_like_count
    ]);
} else {
    // Dati mancanti, invia una risposta di errore
    echo json_encode([
        'success' => false,
        'message' => 'Dati mancanti per eseguire l\'operazione.'
    ]);
}
?>
