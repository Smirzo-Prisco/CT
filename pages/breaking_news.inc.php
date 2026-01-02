<?php
session_start();
/* Includi il file delle costanti e la connessione al database */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Esegui la connessione al database */
$handleDBConnection = gdrcd_connect();

$login = $_SESSION['login']; // Assumendo che tu abbia una sessione per il login dell'utente
$query = "SELECT last_date_news FROM personaggio WHERE nome = '" . gdrcd_filter('in', $login) . "'";
$result = gdrcd_query($query, 'result');
$row = gdrcd_query($result, 'fetch');
$last_date_news = !empty($row['last_date_news']) ? $row['last_date_news'] : '1970-01-01 00:00:00'; // Default ad una data molto vecchia

/* Lista di categorie */
$categories = array(
    'disordini',
    'cronaca nera',
    'gossip',
    'politica',
    'eventi',
    'segnalazioni'
);

/* Recupera la categoria selezionata dall'URL */
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

/* Recupera la pagina corrente (predefinito 1) */
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Numero massimo di notizie per pagina
$offset = ($page - 1) * $limit;

/* Query per recuperare il totale delle notizie per la paginazione */
$totalNewsQuery = "SELECT COUNT(*) AS total FROM ctnews WHERE tipologia = '" . gdrcd_filter('in', $selectedCategory) . "'";
$totalNewsResult = gdrcd_query($totalNewsQuery, 'result');
$totalNewsRow = gdrcd_query($totalNewsResult, 'fetch');
$totalNews = $totalNewsRow['total'];

/* Query per recuperare le notizie della categoria selezionata, limitando il numero di risultati */
$news = array();
if ($selectedCategory) {
    $query = "SELECT * FROM ctnews WHERE tipologia = '" . gdrcd_filter('in', $selectedCategory) . "' ORDER BY data DESC LIMIT $limit OFFSET $offset";
    $result = gdrcd_query($query, 'result');
    while ($row = gdrcd_query($result, 'fetch')) {
        $news[] = $row;
    }
    
    /* Se ci sono notizie, aggiorna il campo last_date_news nella tabella personaggio */
    if (!empty($news)) {
        $update_query = "UPDATE personaggio SET last_date_news = NOW() WHERE nome = '" . gdrcd_filter('in', $login) . "'";
        gdrcd_query($update_query);
    }
    
}

/* Calcola il numero totale di pagine */
$totalPages = ceil($totalNews / $limit);

/* Funzione per spostare una notizia nell'archivio */
if (isset($_GET['archive_news'])) {
    $news_id = gdrcd_filter('num', $_GET['archive_news']);
    $update_query = "UPDATE ctnews SET tipologia = 'archivio' WHERE id = $news_id";
    gdrcd_query($update_query);
    header("Location: breaking_news.inc.php?category=$selectedCategory"); // Ricarica la pagina
    exit;
}

function formatDateToFuture($date, $year_offset = 1053) {
    $formatted_date = date('d F Y', strtotime($date));
    $current_year = date('Y', strtotime($date));
    $future_year = $current_year + $year_offset;
    return $future_year . '年' . date('m月d日', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breaking News</title>
    <link href="../themes/crystal/breaking_news.css" rel="stylesheet" type="text/css">
    <style>
        body {
            background-color: #14172a;
            font-family: Lato, sans-serif;
        }
    </style>
</head>
<body>

<div class="container_breaking">

    <!-- Colonna sinistra con le categorie -->
    <div class="left_column_breaking">
        <?php
    foreach ($categories as $category) {
        /* Verifica se ci sono nuove notizie in questa categoria */
        $query = "SELECT COUNT(*) AS new_count FROM ctnews WHERE tipologia = '" . gdrcd_filter('in', $category) . "' AND data > '" . $last_date_news . "'";
        $result = gdrcd_query($query, 'result');
        $row = gdrcd_query($result, 'fetch');
        
        /* Aggiungi la classe 'new' se ci sono nuove notizie */
        $class = ($row['new_count'] > 0) ? 'new' : '';

        echo '<a href="breaking_news.inc.php?category=' . $category . '"><div class="category_breaking ' . $class . '">' . ucfirst($category) . '</div></a>';
    }
    ?>
    </div>

    <!-- Colonna centrale con le notizie -->
    <div class="content_breaking">
        <?php
        /* Se non è stata selezionata alcuna categoria, mostra l'immagine e il messaggio */
        if (empty($selectedCategory)) {
            echo '<img src="../themes/crystal/imgs/journalist_breaking.png" class="default_image" alt="Giornalista">';
            echo '<div class="default_message">Benvenuti nella sezione Breaking News. Seleziona una categoria per iniziare a leggere le ultime notizie!</div>';
        } elseif (empty($news)) {
            /* Nessuna notizia trovata per la categoria selezionata */
            echo '<div class="default_message">Non ci sono notizie per questa categoria.</div>';
        } else {
            /* Visualizza le notizie della categoria selezionata */
            foreach ($news as $item) {
                echo '<div class="news_content_breaking">';
                echo '<div class="header_breaking">';
                echo '<div class="header_title_breaking">' . $item['titolo'] . '</div>';
                echo '<div class="header_date_breaking">' . formatDateToFuture($item['data']) . '</div>';
                echo '</div>';
                echo '<div class="text_breaking">';
                echo '<span class="text_zone_breaking">' . strtoupper($item['zona']) . ' -</span> ';
                echo $item['contenuto'];
                echo '</div>';
                
                /* Mostra la X per archiviare solo se è admin o il master autore */
                if ($_SESSION['admin'] == 1 || ($_SESSION['master'] == 1 && $item['autore'] == $login)) {
                    echo '<a href="breaking_news.inc.php?archive_news=' . $item['id'] . '&category=' . $selectedCategory . '" onclick="confirmArchive(event)">';
                    echo '<img src="../themes/crystal/imgs/forum/cancella_topic.png" class="delete_icon" alt="Archivia">';
                    echo '</a>';
                }
                
                echo '</div>';
            }
        /* Visualizza la paginazione se ci sono più di 9 notizie */
            if ($totalPages > 1) {
                echo '<div class="pagination_breaking">';
                if ($page > 1) {
                    echo '<a href="breaking_news.inc.php?category=' . $selectedCategory . '&page=' . ($page - 1) . '">Precedente</a>';
                }
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == $page) {
                        echo '<strong>' . $i . '</strong>';
                    } else {
                        echo '<a href="breaking_news.inc.php?category=' . $selectedCategory . '&page=' . $i . '">' . $i . '</a>';
                    }
                }
                if ($page < $totalPages) {
                    echo '<a href="breaking_news.inc.php?category=' . $selectedCategory . '&page=' . ($page + 1) . '">Successiva</a>';
                }
                echo '</div>';
            }
        }
        ?>
    </div>

</div>

</body>

<script>
    function confirmArchive(event) {
        if (!confirm("Sei sicuro di voler archiviare questa news?")) {
            event.preventDefault(); // Blocca l'azione se l'utente annulla
        }
    }
</script>
</html>