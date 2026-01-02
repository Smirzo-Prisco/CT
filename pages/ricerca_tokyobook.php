<?php
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();


if (isset($_POST['termine'])) {
    $termine_ricerca = gdrcd_filter('in', $_POST['termine']);
    
    // Query per trovare i personaggi della TAE con livello < 3
    $query_tae_attuali = "
        SELECT p.nome, p.cognome, p.url_img_chat 
        FROM personaggio AS p 
        JOIN ruolo_mestiere AS r ON p.id_ruolo_mestiere = r.id_ruolo 
        WHERE r.mestiere = 2 
        AND r.livello_mestiere < 3
        AND (p.nome LIKE '%" . $termine_ricerca . "%' OR p.cognome LIKE '%" . $termine_ricerca . "%')";
    
    // Query per trovare i personaggi che **non** sono nella TAE ma hanno scritto almeno un post in `pagina_personale`
    $query_ex_tae = "
        SELECT p.nome, p.cognome, p.url_img_chat 
        FROM personaggio AS p
        WHERE p.nome IN (
            SELECT DISTINCT autore 
            FROM tokyobook_bacheca 
            WHERE tipo = 'pagina_personale'
        )
        AND (p.nome LIKE '%" . $termine_ricerca . "%' OR p.cognome LIKE '%" . $termine_ricerca . "%')";

    // Esegui le query
    $result_tae_attuali = gdrcd_query($query_tae_attuali, 'result');
    $result_ex_tae = gdrcd_query($query_ex_tae, 'result');

    // Creare un array per memorizzare i risultati
    $risultati = [];

    // Aggiungere i personaggi della TAE
    while ($row = gdrcd_query($result_tae_attuali, 'fetch')) {
        $risultati[] = $row;
    }

    // Aggiungere i personaggi ex-TAE con post
    while ($row = gdrcd_query($result_ex_tae, 'fetch')) {
        $risultati[] = $row;
    }

    // Restituire i risultati in formato JSON
    echo json_encode($risultati);
}
?>
