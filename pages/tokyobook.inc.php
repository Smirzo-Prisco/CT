<?php
ob_start();

// Recuperare il login dell'utente dalla sessione
$login = $_SESSION['login'];

// Recuperare il parametro GET per il nome del personaggio, se esiste, altrimenti utilizzare il login
$nome_pg = isset($_GET['pg']) ? gdrcd_filter('in', $_GET['pg']) : $login;

// Recuperare il parametro GET per verificare se stiamo visualizzando i commenti di un post specifico
$post_id = isset($_GET['post_id']) ? gdrcd_filter('num', $_GET['post_id']) : null;

// Creazione della query per ottenere l'URL dell'avatar
$query = "SELECT * FROM personaggio WHERE nome = '" . gdrcd_filter('in', $login) . "'";
$result = gdrcd_query($query, 'result');

// Recuperare l'URL dell'immagine, se disponibile
$row = gdrcd_query($result, 'fetch');
$url_avatar = $row ? $row['url_img_chat'] : '../themes/crystal/imgs/avatars/empty.png';

// Recuperare il parametro GET per la categoria
$tipo = isset($_GET['tipo']) ? gdrcd_filter('in', $_GET['tipo']) : 'tokyobook'; // Default: 'tokyobook'

// INVIO NUOVO POST O COMMENTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post-textarea'])) {
    // Recupera il testo del post/commento
    $testo_post = gdrcd_filter('in', $_POST['post-textarea']);
    
    // Determina il valore di alias in base ai checkbox selezionati
    $alias = 0; // Valore predefinito (nessun flag)

    // Se il checkbox "alias" è selezionato
    if (isset($_POST['use-alias'])) {
        $alias = 1; // Imposta il valore a 1 se selezionato
    }

    // Se il checkbox "anonimo" è selezionato e l'utente è un master/admin
    if ($tipo == 'tokyobook' && ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1) && isset($_POST['use-anonimo'])) {
        $alias = 2; // Imposta il valore a 2 se selezionato
    }

    // Se c'è un post_id, stiamo inserendo un commento, altrimenti un post
    $id_messaggio_padre = $post_id ? $post_id : -1;
    
    // Recupera il titolo solo se stiamo creando un nuovo post, non per i commenti
    $titolo_post = '';
    if ($id_messaggio_padre == -1 && isset($_POST['post-titolo'])) {
        $titolo_post = gdrcd_filter('in', $_POST['post-titolo']);
    }

    // Inserisci il post/commento nel database
    $insert_post_query = "
        INSERT INTO tokyobook_bacheca (tipo, autore, titolo, testo, data, alias, id_messaggio_padre)
        VALUES ('" . gdrcd_filter('in', $tipo) . "', '" . gdrcd_filter('in', $login) . "', '" . gdrcd_filter('in', $titolo_post) . "', '" . gdrcd_filter('in', $testo_post) . "', NOW(), " . $alias . ", '" . gdrcd_filter('num', $id_messaggio_padre) . "')";
    
    $inserimento_risultato = gdrcd_query($insert_post_query);

    // Verifica se l'inserimento è andato a buon fine
    if ($inserimento_risultato) {
    
     $update_lettura_query_login = "
            UPDATE tokyobook_lettura 
            SET " . gdrcd_filter('in', $tipo) . " = NOW() 
            WHERE login = '" . gdrcd_filter('in', $login) . "'";
            gdrcd_query($update_lettura_query_login);
        
        
        // Determina se è un nuovo post o un commento in base al valore di $id_messaggio_padre
        $redirect_url = "main.php?page=tokyobook&tipo=" . $tipo;

        // Se è un commento, aggiungi il parametro post_id per tornare al messaggio principale
        if ($id_messaggio_padre != -1) {
            $redirect_url .= "&post_id=" . $id_messaggio_padre;
        }

        // Se è una pagina personale, aggiungi il parametro pg
        if ($tipo == 'pagina_personale') {
            $redirect_url .= "&pg=" . $nome_pg;
        }
        // Definisci un messaggio di successo per il redirect tramite JavaScript
         // Definisci il messaggio di successo e usa JavaScript per farlo apparire
    echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var successMessage = document.createElement("div");
                successMessage.textContent = "Post inserito con successo!";
                successMessage.style.position = "fixed";
                successMessage.style.top = "20px";
                successMessage.style.left = "50%";
                successMessage.style.transform = "translateX(-50%)";
                successMessage.style.padding = "15px 30px";
                successMessage.style.backgroundColor = "#28a745";
                successMessage.style.color = "white";
                successMessage.style.borderRadius = "5px";
                successMessage.style.boxShadow = "0 4px 8px rgba(0, 0, 0, 0.2)";
                successMessage.style.zIndex = "9999";
                successMessage.id = "success-message";

                document.body.appendChild(successMessage);

                // Rimuovi il messaggio dopo 3 secondi
                setTimeout(function() {
                    successMessage.style.opacity = "0";
                    setTimeout(function() {
                        successMessage.remove();
                        window.location.href = "' . $redirect_url . '"; // Reindirizza solo dopo la rimozione del messaggio
                    }, 500);
                }, 2000);
            });
          </script>';
        ob_end_flush(); // Chiudi il buffer di output
        exit;
    } else {
        // In caso di errore, mostra un messaggio di errore con JavaScript
        echo '<script>alert("Errore nell\'inserimento del post.");</script>';
    }
}

$filtro_autore = "";

// Se è stato passato un `post_id`, modifica la query per caricare i commenti del post specifico
if ($post_id) {
    // Modifica il filtro per autore in modo che carichi i commenti del post specifico
    $filtro_autore = "AND p.id_messaggio_padre = " . $post_id;
}

// Numero massimo di post per pagina
$limite_post_per_pagina = 10;

// Determina la pagina corrente
$pagina_corrente = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_corrente < 1) {
    $pagina_corrente = 1;
}

// Calcolo dell'offset per la query in base alla pagina corrente
$offset = ($pagina_corrente - 1) * $limite_post_per_pagina;

// Creazione della query per recuperare i post in base alla categoria e al filtro autore, se applicabile
$query_tokyobook = "
    SELECT 
        p.*, 
        p.titolo,
        (SELECT COUNT(*) FROM tokyobook_bacheca AS c WHERE c.id_messaggio_padre = p.id_messaggio) AS numero_commenti, 
        (SELECT COUNT(*) FROM tokyobook_likes AS l WHERE l.id_messaggio = p.id_messaggio AND l.login = '$login') AS like_status, 
        (SELECT COUNT(*) FROM tokyobook_likes WHERE id_messaggio = p.id_messaggio) AS numero_like,
        (SELECT nickname FROM tokyobook WHERE personaggio = p.autore) AS nickname,
        pers.url_img_chat,
        -- Sottoquery per ottenere la data più recente tra il post e i commenti
        GREATEST(
            p.data,
            COALESCE((SELECT MAX(c.data) FROM tokyobook_bacheca AS c WHERE c.id_messaggio_padre = p.id_messaggio), p.data)
        ) AS max_data
    FROM 
        tokyobook_bacheca AS p 
    JOIN 
        personaggio AS pers ON pers.nome = p.autore
    WHERE 
        p.tipo = '" . $tipo . "' 
        AND p.id_messaggio_padre = -1 
        " . $filtro_autore . "
    ORDER BY 
        max_data DESC
    LIMIT $offset, $limite_post_per_pagina
";


$posts = gdrcd_query($query_tokyobook, 'result');

// Carica i commenti solo se `post_id` è settato
$comments = [];
if ($post_id) {
    $query_comments = "
    SELECT 
        p.*, 
        (SELECT COUNT(*) FROM tokyobook_likes AS l WHERE l.id_messaggio = p.id_messaggio) AS like_count, 
        (SELECT COUNT(*) FROM tokyobook_likes AS l WHERE l.id_messaggio = p.id_messaggio AND l.login = '" . gdrcd_filter('in', $login) . "') AS like_status, 
        personaggio.url_img_chat, 
        IFNULL(tokyobook.nickname, '') AS nickname
    FROM tokyobook_bacheca AS p 
    JOIN personaggio ON p.autore = personaggio.nome
    LEFT JOIN tokyobook ON p.autore = tokyobook.personaggio
    WHERE p.id_messaggio_padre = " . $post_id . "
    ORDER BY p.data ASC";


    
    $comments = gdrcd_query($query_comments, 'result');
}


// Condizioni per mostrare la textarea in base alla tipologia (unica categoria rimasta: tokyobook)
$mostra_textarea = ($tipo == 'tokyobook');

// Verifica se il personaggio ha un alias nella tabella 'tokyobook'
$query_alias = "SELECT nickname FROM tokyobook WHERE personaggio = '" . gdrcd_filter('in', $nome_pg) . "'";
$result_alias = gdrcd_query($query_alias, 'result');
$has_alias = (gdrcd_query($result_alias, 'num_rows') > 0);



// Query per ottenere le ultime 10 azioni da `tokyobook_bacheca`
$query_ultime_azioni = "
    SELECT 
        p.id_messaggio, 
        p.autore, 
        p.alias, 
        p.id_messaggio_padre, 
        p.tipo, 
        p.data, 
        COALESCE((SELECT pers.nome FROM personaggio AS pers WHERE pers.nome = p.autore), 'Anonimo') AS autore_nome,
        (SELECT autore FROM tokyobook_bacheca AS padre WHERE padre.id_messaggio = p.id_messaggio_padre) AS autore_padre,
        (SELECT alias FROM tokyobook_bacheca AS padre WHERE padre.id_messaggio = p.id_messaggio_padre) AS alias_padre,
        (SELECT nickname FROM tokyobook WHERE personaggio = p.autore) AS autore_nickname,
        (SELECT nickname FROM tokyobook WHERE personaggio = (SELECT autore FROM tokyobook_bacheca AS padre WHERE padre.id_messaggio = p.id_messaggio_padre)) AS nickname_padre
    FROM 
        tokyobook_bacheca AS p
    WHERE 
        p.tipo = 'pagina_personale'
    ORDER BY 
        p.data DESC 
    LIMIT 10";

$ultime_azioni = gdrcd_query($query_ultime_azioni, 'result');

// Funzione per ottenere il nome visualizzato in base al valore di alias
function getDisplayName($nome, $alias, $nickname) {
    if ($alias == 0) {
        return "<strong style='color: #ce846f;'>$nome</strong>"; // Nome del personaggio
    } elseif ($alias == 1) {
        return "<strong style='color: #ce846f;'>$nickname</strong>"; // Nome alias
    } else {
        return "<strong style='color: #ce846f;'>Anonimo</strong>"; // Anonimo
    }
}


/**
 * Filtra il testo per consentire solo determinati tag HTML.
 */
function filtra_testo($testo) {
    // Convertire i tag HTML in entità per farli vedere come testo
    $testo = htmlspecialchars($testo, ENT_QUOTES | ENT_HTML5);

    // Sostituzioni per il formato [b][/b], [i][/i], [u][/u]
    $testo = preg_replace('/\[b\](.*?)\[\/b\]/i', '<b>$1</b>', $testo);
    $testo = preg_replace('/\[i\](.*?)\[\/i\]/i', '<i>$1</i>', $testo);
    $testo = preg_replace('/\[u\](.*?)\[\/u\]/i', '<u>$1</u>', $testo);

    // Sostituzione per il formato [center][/center]
    $testo = preg_replace('/\[center\](.*?)\[\/center\]/i', '<div style="text-align: center;">$1</div>', $testo);

    // Sostituzione per il formato [color=COLOR][/color]
    $testo = preg_replace('/\[color=([#a-z0-9]+)\](.*?)\[\/color\]/i', '<span style="color: $1;">$2</span>', $testo);

    // Convertire il tag <img src=""> e [img] in <a href="">
    $testo = preg_replace('/&lt;img[^&gt;]+src=[\'"]([^\'"]+)[\'"][^&gt;]*&gt;/i', '<a href="$1" target="_blank">APRI IL LINK</a>', $testo);
    $testo = preg_replace('/\[img\](.*?)\[\/img\]/i', '<a href="$1" target="_blank">APRI IL LINK</a>', $testo);

    // Convertire il tag <a href=""> in un link con dicitura specifica e target "_blank"
    $testo = preg_replace('/&lt;a href=[\'"](.*?)[\'"][^&gt;]*&gt;(.*?)&lt;\/a&gt;/i', '<a href="$1" target="_blank">APRI IL LINK</a>', $testo);

    // Sostituzione per il formato [spoiler][/spoiler]
    $testo = preg_replace('/\[spoiler\](.*?)\[\/spoiler\]/is', 
        '<details style="border:1px dashed #9a6353; background-color: #03011f; padding:10px; border-radius:10px;">
            <summary style="border:0px dashed #9a6353; padding:2px; color: #9a6353; font-size:11px; text-transform:uppercase; font-weight:bold;">
                ESPANDI CONTENUTO
            </summary><br>$1</details>', $testo);

    // Sostituzione per il formato [spoiler=TESTO SCELTO DA UTENTE]TESTO MESSAGGIO[/spoiler]
    $testo = preg_replace('/\[spoiler=([^\]]+)\](.*?)\[\/spoiler\]/is',
        '<details style="border:1px dashed #9a6353; background-color: #03011f; padding:10px; border-radius:10px;">
            <summary style="border:0px dashed #9a6353; padding:2px; color: #9a6353; font-size:11px; text-transform:uppercase; font-weight:bold;">
                $1
            </summary><br>$2</details>', $testo);

    // Consentire solo determinati tag HTML
    $testo = strip_tags($testo, '<b><i><u><a><div><span><details><summary>');

    return $testo;
}




//NUOVI MESSAGGI
// Verifica se l'utente è presente nella tabella `tokyobook_lettura`
$query_check_user = "
    SELECT login 
    FROM tokyobook_lettura 
    WHERE login = '" . gdrcd_filter('in', $login) . "'";
$result_check_user = gdrcd_query($query_check_user, 'result');

// Se l'utente non è presente, inserisci un nuovo record con le date predefinite
if (gdrcd_query($result_check_user, 'num_rows') == 0) {
    $query_insert_user = "
        INSERT INTO tokyobook_lettura (login) 
        VALUES ('" . gdrcd_filter('in', $login) . "')";
    gdrcd_query($query_insert_user);
}

// Recupera le date di ultimo accesso dell'utente dalla tabella `tokyobook_lettura`
$query_get_last_dates = "
    SELECT tokyobook
    FROM tokyobook_lettura
    WHERE login = '" . gdrcd_filter('in', $login) . "'";
$result_last_dates = gdrcd_query($query_get_last_dates, 'result');
$last_dates = gdrcd_query($result_last_dates, 'fetch');

// Calcola i nuovi post/commenti rispetto all'ultima data di accesso
$query_new_posts = "
    SELECT tipo, COUNT(*) AS new_count
    FROM tokyobook_bacheca
    WHERE data > '" . $last_dates['tokyobook'] . "' AND tipo = 'tokyobook'
       AND autore != '" . gdrcd_filter('in', $login) . "'
    GROUP BY tipo";
$result_new_posts = gdrcd_query($query_new_posts, 'result');

// Inizializza un array per raccogliere le categorie con nuovi post/commenti
$new_posts = [];
while ($row = gdrcd_query($result_new_posts, 'fetch')) {
    $new_posts[$row['tipo']] = $row['new_count'];
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tokyo Book Layout</title>
    <link href="../themes/crystal/tokyobook.css" rel="stylesheet" type="text/css">

</head>

<body>


    <!-- Barra superiore -->
    <div class="top-bar">
        <div class="title">
            <img src="../themes/crystal/imgs/tokyobook/tokyobook_logo.png" alt="TokyoBook Logo">
        </div>
    </div>

    <!-- Contenitore principale -->
    <div class="main-container">
        <!-- Contenitore Sidebar -->
        <div class="sidebar">
            <div class="avatar">
                <img src="<?php echo gdrcd_filter('out', $url_avatar); ?>" alt="Avatar">
            </div>
            <div class="menu-container">
<?php
// Definisci l'elenco delle categorie principali
$categories = [
    'tokyobook' => 'Tokyobook',
];

// Loop attraverso le categorie e mostra il link con l'indicatore delle nuove notizie
foreach ($categories as $category_key => $category_name) {
    // Controlla se la categoria ha nuove notizie (se c'è almeno un nuovo post/commento)
    $new_indicator = isset($new_posts[$category_key]) ? '<span class="new-indicator">&#9679;</span>' : '';

    // Costruisci il link corretto in base alla categoria
    $category_link = "main.php?page=tokyobook&tipo=" . $category_key;

    // Stampa l'elemento di menu con l'indicatore delle nuove notizie, se presenti
    echo '<div class="menu-item"><a href="' . $category_link . '">' . $category_name . $new_indicator . '</a></div>';
}

// Quando l'utente accede a una categoria, aggiorna solo la data di quella categoria
if (isset($_GET['tipo'])) {
    $categoria_corrente = gdrcd_filter('in', $_GET['tipo']);
    if (in_array($categoria_corrente, ['tokyobook'])) {
        $update_last_access_query = "
            UPDATE tokyobook_lettura 
            SET " . $categoria_corrente . " = NOW() 
            WHERE login = '" . gdrcd_filter('in', $login) . "'";
        gdrcd_query($update_last_access_query);
    }
}


?>

</div>

            <div class="sidebar-footer">
                <div class="footer-box">
                    <div class="update-list">
            <?php 
            while($azione = gdrcd_query($ultime_azioni, 'fetch')) {
                // Recupera il nome visualizzato per l'autore del post/commento
                $autore_nome = getDisplayName($azione['autore_nome'], $azione['alias'], $azione['autore_nickname']);

                // Recupera il nome visualizzato per l'autore del messaggio padre (se è un commento)
                $autore_padre_nome = $azione['id_messaggio_padre'] != -1 ? getDisplayName($azione['autore_padre'], $azione['alias_padre'], $azione['nickname_padre']) : '';

                // Determina la tipologia di azione (nuovo post o commento)
                if ($azione['id_messaggio_padre'] == -1) { 
                    // Nuovo post
                    $sezione = ($azione['tipo'] == 'pagina_personale') ? "nella sua pagina" : "in " . ucfirst($azione['tipo']);
                    echo "<div class='update-item' style='color: #b4b6bf;'>" . $autore_nome . " ha pubblicato un nuovo post " . $sezione . "</div>";
                } else { 
                    // Commento
                    echo "<div class='update-item' style='color: #b4b6bf;'>" . $autore_nome . " ha commentato il post di " . $autore_padre_nome . "</div>";
                }
            }
            ?>
        </div>
                </div>
            </div>
        </div>



        <!-- Contenitore Centrale -->
<div class="content">
    <!-- Verifica se stiamo visualizzando i commenti di un post specifico -->
    <?php $post_originale = null;
          if ($post_id) { 
          $query_post_originale = "
    SELECT 
        p.*,
        p.titolo,
        (SELECT COUNT(*) FROM tokyobook_likes AS l WHERE l.id_messaggio = p.id_messaggio) AS numero_like,
        (SELECT COUNT(*) FROM tokyobook_bacheca AS c WHERE c.id_messaggio_padre = p.id_messaggio) AS numero_commenti,
        (SELECT nickname FROM tokyobook WHERE personaggio = p.autore) AS nickname,
        pers.url_img_chat
    FROM 
        tokyobook_bacheca AS p
    JOIN 
        personaggio AS pers ON pers.nome = p.autore
    WHERE 
        p.id_messaggio = " . gdrcd_filter('num', $post_id) . "
    LIMIT 1";


    $result_post_originale = gdrcd_query($query_post_originale, 'result');
    $post_originale = gdrcd_query($result_post_originale, 'fetch');
    
    // Se è stato trovato il post originale, visualizzalo
if ($post_originale) {

    ?>
        <!-- Sezione Commenti -->
        <!-- Pulsante Torna Indietro -->
        <div class="back-button-container">
             <button onclick="window.location.href='main.php?page=tokyobook&tipo=<?php echo $tipo; ?><?php echo ($tipo == 'pagina_personale') ? '&pg=' . $nome_pg : ''; ?>';">
                Torna alla lista dei post
            </button>
        </div>

        <!-- Mostra il post originale sopra i commenti -->
        <div class="post">
        <div class="post-header">
            <div class="post-avatar">
                <?php
                $avatar_url = ($post_originale['alias'] == 0) ? $post_originale['url_img_chat'] : '../themes/crystal/imgs/forum/anonimo.png';
                ?>
                <img src="<?php echo $avatar_url; ?>" alt="Avatar">
            </div>
            <div class="post-info">
                <div class="post-author">
                <?php
                // Determina il nome autore in base al valore di alias
                if ($post_originale['alias'] == 0) {
                    echo gdrcd_filter('out', $post_originale['autore']);
                } elseif ($post_originale['alias'] == 1) {
                    echo gdrcd_filter('out', $post_originale['nickname']);
                } elseif ($post_originale['alias'] == 2) {
                    echo 'Anonimo';
                }
                ?>
                </div>
                <div class="post-date"><?php echo gdrcd_format_date($post_originale['data']); ?></div>
            </div>
        </div>
        <div class="post-title" style="text-transform: uppercase; font-weight: bold; color: #ce846f; margin-bottom: 10px; font-size: 14px; text-align: left;">
            <?php echo gdrcd_filter('out', $post_originale['titolo']); ?>
        </div>
        <div class="post-content">
            <?php echo nl2br(filtra_testo(gdrcd_filter('out', $post_originale['testo']))); ?>
        </div>
        <div class="post-reactions">
            <div class="reaction">
                <?php
                    $like_class = ($post_originale['like_status'] > 0) ? '' : 'like-icon-off';
                ?>
                <!-- Aggiungi id e funzione per aggiornamento via AJAX -->
                <img id="like-icon-<?php echo $post_originale['id_messaggio']; ?>" src="../themes/crystal/imgs/tokyobook/like.png" alt="Like Icon" class="<?php echo $like_class; ?>" onclick="toggleLike(<?php echo $post_originale['id_messaggio']; ?>)"> 
                <span id="like-count-<?php echo $post_originale['id_messaggio']; ?>"><?php echo $post_originale['numero_like']; ?></span> Mi piace
            </div>
            <div class="reaction">
                <?php echo $post_originale['numero_commenti']; ?> Commenti
            </div>
        </div>
    </div>
<?php
}
?>

        <!-- Sezione Commenti -->
        <div class="comments-section">
            <?php while ($comment = gdrcd_query($comments, 'fetch')) { ?>
            <div class="post" id="comment-<?php echo $comment['id_messaggio']; ?>">
                <div class="post-header">
                    <div class="post-avatar">
                        <?php
                        $comment_avatar_url = ($comment['alias'] == 0) ? $comment['url_img_chat'] : '../themes/crystal/imgs/forum/anonimo.png';
                        ?>
                        <img src="<?php echo $comment_avatar_url; ?>" alt="Comment Avatar">
                    </div>
                    <div class="post-info">
                        <div class="post-author">
                        <?php
                        // Determina il nome del commentatore in base al valore di alias
                        if ($comment['alias'] == 0) {
                            echo gdrcd_filter('out', $comment['autore']);
                        } elseif ($comment['alias'] == 1) {
                            echo gdrcd_filter('out', $comment['nickname']);
                        } elseif ($comment['alias'] == 2) {
                            echo 'Anonimo';
                        }
                        ?>
                        </div>
                        <div class="post-date"><?php echo gdrcd_format_date($comment['data']); ?></div>
                    </div>
                    
                    <!-- Freccia verso il basso (visibile solo per autore o admin) -->
        <?php if ($comment['autore'] == $login || $_SESSION['admin'] == 1) { ?>
            <div class="post-menu">
                <span class="menu-icon" onclick="toggleMenu(<?php echo $comment['id_messaggio']; ?>)">&#9660;</span>
                <!-- Menu a discesa -->
                <div id="menu-dropdown-<?php echo $comment['id_messaggio']; ?>" class="menu-dropdown" style="display: none;">
                    <div class="menu-item" 
                         onclick="editMessage(<?php echo $comment['id_messaggio']; ?>, this.dataset.content, false);" 
                         data-content='<?php echo htmlspecialchars(json_encode($comment['testo']), ENT_QUOTES, 'UTF-8'); ?>'>
                         Modifica commento
                    </div>

                    <div class="menu-item" onclick="deleteComment(<?php echo $comment['id_messaggio']; ?>, false)">Cancella messaggio</div>
                </div>
            </div>
        <?php } ?>
        
                </div>
                <div class="post-content">
                    <?php echo nl2br(filtra_testo(gdrcd_filter('out', $comment['testo']))); ?>

                </div>
                <div class="post-reactions">
                    <div class="reaction">
                <?php
                    // Verifica lo stato del like per il commento
                    $comment_like_class = ($comment['like_status'] > 0) ? '' : 'like-icon-off';
                ?>
                <!-- Aggiungi id e funzione per aggiornamento via AJAX per i commenti -->
                <img id="like-icon-<?php echo $comment['id_messaggio']; ?>" src="../themes/crystal/imgs/tokyobook/like.png" alt="Like Icon" class="<?php echo $comment_like_class; ?>" onclick="toggleLike(<?php echo $comment['id_messaggio']; ?>)"> 
                <span id="like-count-<?php echo $comment['id_messaggio']; ?>"><?php echo $comment['like_count']; ?></span> Mi piace
            </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <!-- Textarea per rispondere con un nuovo commento -->
        <form method="POST" action="">
            <textarea class="post-textarea" name="post-textarea" placeholder="Scrivi qui il tuo commento..."></textarea>

            <!-- Contenitore per pulsante e checkbox -->
            <div class="button-container">
                <button type="submit" class="post-button">COMMENTA</button>
                
                <?php if ($tipo == 'tokyobook' && $has_alias) { ?>
                <div class="alias-container">
                    <input type="checkbox" id="use-alias" name="use-alias" class="alias-checkbox">
                    <label for="use-alias" class="alias-label">Usa alias</label>
                </div>
                <?php } ?>

                <?php if ($tipo == 'tokyobook' && ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1)) { ?>
                <div class="anonimo-container">
                    <input type="checkbox" id="use-anonimo" name="use-anonimo" class="anonimo-checkbox">
                    <label for="use-anonimo" class="anonimo-label">Usa anonimo (solo master)</label>
                </div>
                <?php } ?>
            </div>
        </form>








    <?php } else { ?>
    
    
    
    
    
    

    <!-- MESSAGGI PADRE -->
        <?php if ($mostra_textarea) { ?>
        <!-- Textarea per scrivere un post -->
        <form method="POST" action="">
        <input type="text" class="post-titolo" name="post-titolo" placeholder="Scrivi qui il tuo titolo..." required>
        <textarea class="post-textarea" name="post-textarea" placeholder="Scrivi qui il tuo post..."></textarea>

        <!-- Contenitore per pulsante e checkbox -->
        <div class="button-container">
            <button type="submit" class="post-button">POSTA</button>
            
            <?php if ($tipo == 'tokyobook' && $has_alias) { ?>
    <div class="alias-container">
        <input type="checkbox" id="use-alias" name="use-alias" class="alias-checkbox">
        <label for="use-alias" class="alias-label">Usa alias</label>
    </div>
<?php } ?>

            
            <?php if ($tipo == 'tokyobook' && ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1)) { ?>
            <div class="anonimo-container">
                <input type="checkbox" id="use-anonimo" name="use-anonimo" class="anonimo-checkbox">
                <label for="use-anonimo" class="anonimo-label">Usa anonimo (solo master)</label>
            </div>
            <?php } ?>            
        </div>
        </form>
        <?php } ?>
        <!-- Visualizzazione dei post filtrati per autore e categoria -->
        <?php while($post = gdrcd_query($posts, 'fetch')) { ?>
    <div class="post" id="post-<?php echo $post['id_messaggio']; ?>">
        <div class="post-header">
            <div class="post-avatar">
                <?php
                $avatar_url = ($post['alias'] == 0) ? $post['url_img_chat'] : '../themes/crystal/imgs/forum/anonimo.png';
                ?>
                <img src="<?php echo $avatar_url; ?>" alt="Avatar">
            </div>
            <div class="post-info">
                <div class="post-author">
                <?php
                // Determina il nome autore in base al valore di alias
                if ($post['alias'] == 0) {
                    echo gdrcd_filter('out', $post['autore']);
                } elseif ($post['alias'] == 1) {
                    echo gdrcd_filter('out', $post['nickname']);
                } elseif ($post['alias'] == 2) {
                    echo 'Anonimo';
                }
                ?>
                </div>
                <div class="post-date"><?php echo gdrcd_format_date($post['data']); ?></div>
            </div>
            
            <!-- Freccia menu visibile solo all'autore o all'admin -->
        <?php if ($post['autore'] == $login || $_SESSION['admin'] == 1) { ?>
        <div class="post-menu">
            <div class="menu-icon" onclick="togglePostMenu(<?php echo $post['id_messaggio']; ?>)">
                &#x25BC; <!-- Freccia Unicode verso il basso -->
            </div>
            <div class="menu-dropdown" id="menu-<?php echo $post['id_messaggio']; ?>" style="display: none;">
                <div class="menu-item" 
                     onclick='editMessage(<?php echo $post['id_messaggio']; ?>, this.dataset.content, true);' 
                     data-content='<?php echo htmlspecialchars(json_encode($post['testo']), ENT_QUOTES, 'UTF-8'); ?>'>
                     Modifica messaggio
                </div>

                <div class="menu-item" onclick="deleteComment(<?php echo $post['id_messaggio']; ?>, true);">Cancella messaggio</div>
            </div>
        </div>
        <?php } ?>
        
        
        </div>
        <div class="post-title" style="text-transform: uppercase; font-weight: bold; color: #ce846f; margin-bottom: 10px; font-size: 14px; text-align: left;">
            <?php echo gdrcd_filter('out', $post['titolo']); ?>
        </div>
        <div class="post-content">
            <?php echo nl2br(filtra_testo(gdrcd_filter('out', $post['testo']))); ?>
        </div>
        <div class="post-reactions">
            <div class="reaction">
                <?php
                    $like_class = ($post['like_status'] > 0) ? '' : 'like-icon-off';
                ?>
                <!-- Aggiungi id e funzione per aggiornamento via AJAX -->
                <img id="like-icon-<?php echo $post['id_messaggio']; ?>" src="../themes/crystal/imgs/tokyobook/like.png" alt="Like Icon" class="<?php echo $like_class; ?>" onclick="toggleLike(<?php echo $post['id_messaggio']; ?>)"> 
                <span id="like-count-<?php echo $post['id_messaggio']; ?>"><?php echo $post['numero_like']; ?></span> Mi piace
            </div>
            <div class="reaction">
                 <span onclick="window.location.href='main.php?page=tokyobook&tipo=<?php echo $tipo; ?><?php echo ($tipo == 'pagina_personale') ? '&pg=' . $nome_pg : ''; ?>&post_id=<?php echo $post['id_messaggio']; ?>';">
                        <?php echo $post['numero_commenti']; ?> Commenti
                    </span>
            </div>
        </div>
    </div>
<?php } ?>

<?php

$query_conta_post = "
    SELECT COUNT(*) AS totale_post 
    FROM tokyobook_bacheca AS p 
    WHERE p.tipo = '" . $tipo . "' 
    AND p.id_messaggio_padre = -1 
    " . $filtro_autore;

$totale_post_risultato = gdrcd_query($query_conta_post, 'result');
$totale_post = gdrcd_query($totale_post_risultato, 'fetch')['totale_post'];

// Calcolo del numero totale di pagine
$numero_pagine = ceil($totale_post / $limite_post_per_pagina);

if ($numero_pagine > 1) {
    // Generazione del menu a tendina per la paginazione
    echo '<div class="pagination-select">';
    echo '<label for="pagination-select">Vai a pagina:</label>';
    echo '<select id="pagination-select" class="pagination-dropdown" onchange="cambiaPagina()">';

    for ($i = 1; $i <= $numero_pagine; $i++) {
        $selected = ($i == $pagina_corrente) ? 'selected' : '';
        echo '<option value="' . $i . '" ' . $selected . '>Pagina ' . $i . '</option>';
    }

    echo '</select>';
    echo '</div>';
}
?>



</div>
<?php } ?>
</div>
    
    <script>
    // Funzione per gestire il like/unlike
    function toggleLike(postId) {
        // Ottieni lo stato attuale dell'icona del like
        let likeIcon = document.getElementById("like-icon-" + postId);
        let likeCount = document.getElementById("like-count-" + postId);
        let isLiked = !likeIcon.classList.contains("like-icon-off"); // Controlla se il like è attivo o no

        // Determina l'azione (like o unlike)
        let action = isLiked ? 'unlike' : 'like';

        // Ottieni il nome dell'utente dalla sessione PHP
        let login = "<?php echo $_SESSION['login']; ?>";

        // Crea la richiesta AJAX
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "../pages/like_post.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Imposta cosa fare quando ricevi una risposta dal server
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Risposta dal server
                let response = JSON.parse(xhr.responseText);

                if (response.success) {
                    // Aggiorna il numero di like
                    likeCount.innerText = response.new_like_count;

                    // Cambia lo stato del like in base all'azione
                    if (action === 'like') {
                        likeIcon.classList.remove("like-icon-off");
                    } else {
                        likeIcon.classList.add("like-icon-off");
                    }
                }
            }
        };

        // Invia la richiesta con i dati del post, dell'azione e del login dell'utente
        xhr.send("post_id=" + postId + "&action=" + action + "&login=" + login);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    //funzione menu per commenti padre
    function togglePostMenu(postId) {
    // Ottieni il menu specifico
    let menu = document.getElementById('menu-' + postId);
    
    // Verifica se il menu è visibile o nascosto
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}













//funzione menu per commenti
function toggleMenu(commentId) {
    let menu = document.getElementById("menu-dropdown-" + commentId);
    if (menu.style.display === "none" || menu.style.display === "") {
        menu.style.display = "block";
    } else {
        menu.style.display = "none";
    }
}

//cancellazione commento

// Funzione per cancellare un commento o un messaggio padre
function deleteComment(commentId, isParent = false) {
    // Mostra un messaggio di conferma prima di procedere con la cancellazione
    let confirmDelete = isParent
        ? confirm("Sei sicuro di voler cancellare questo messaggio e tutti i commenti associati? Questa azione non può essere annullata.")
        : confirm("Sei sicuro di voler cancellare questo commento? Questa azione non può essere annullata.");

    // Se l'utente conferma, procedi con la cancellazione
    if (confirmDelete) {
        let login = "<?php echo $_SESSION['login']; ?>"; // Ottieni il login dell'utente dalla sessione PHP

        // Crea la richiesta AJAX
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "../pages/tokyobook_delete_comment.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Gestione della risposta
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                let response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Rimuovi il commento o l'intero post dal DOM
                    if (isParent) {
                        // Se è un messaggio padre, rimuovi anche l'intera sezione commenti
                        let postElement = document.getElementById("post-" + commentId);
                        if (postElement) {
                            postElement.remove();
                        }
                    } else {
                        // Se è un commento singolo, rimuovi solo il commento specifico
                        let commentElement = document.getElementById("comment-" + commentId);
                        if (commentElement) {
                            commentElement.remove();
                        }
                    }
                    alert(response.message);
                } else {
                    alert("Errore nella cancellazione: " + response.message);
                }
            }
        };

        // Invia i dati con la richiesta
        xhr.send("comment_id=" + commentId + "&login=" + login);
    }
}









// Funzione per abilitare la modifica del messaggio o del commento
function editMessage(idMessaggio, testoCorrente, isPost) {
    // Decodifica il contenuto del messaggio usando JSON.parse e assicurati che venga interpretato correttamente
    let contenuto = JSON.parse(testoCorrente);

    // Ottieni il div principale del messaggio/commento
    let messageDiv = isPost ? document.getElementById('post-' + idMessaggio) : document.getElementById('comment-' + idMessaggio);

    // Verifica se c'è già un form di modifica presente, ed eventualmente rimuovilo
    let existingForm = messageDiv.querySelector('.edit-form');
    if (existingForm) {
        existingForm.remove(); // Rimuove il form di modifica se esiste già
    }

    // Trova il div del contenuto e nascondilo
    let contentDiv = messageDiv.querySelector('.post-content');
    contentDiv.style.display = 'none';

    // Crea una nuova div per il form di modifica
    let editForm = document.createElement('div');
    editForm.className = 'edit-form';

    // Crea la textarea per modificare il messaggio/commento
    let textarea = document.createElement('textarea');
    textarea.id = 'edit-text-' + idMessaggio;
    textarea.className = 'edit-textarea';
    
    // Imposta il valore della textarea con il contenuto decodificato
    textarea.value = contenuto;

    // Crea il pulsante Salva
    let saveButton = document.createElement('button');
    saveButton.className = 'save-button';
    saveButton.textContent = 'Salva';
    saveButton.onclick = function () {
        saveMessage(idMessaggio, isPost);
    };

    // Crea il pulsante Annulla
    let cancelButton = document.createElement('button');
    cancelButton.className = 'cancel-button';
    cancelButton.textContent = 'Annulla';
    cancelButton.onclick = function () {
        cancelEdit(idMessaggio, isPost);
    };

    // Aggiungi gli elementi al form di modifica
    editForm.appendChild(textarea);
    editForm.appendChild(saveButton);
    editForm.appendChild(cancelButton);

    // Aggiungi il form di modifica al div principale del messaggio/commento
    messageDiv.appendChild(editForm);

    // Mostra il form di modifica
    editForm.style.display = 'block';
}



// Funzione per annullare la modifica e ripristinare il testo originale
function cancelEdit(idMessaggio, isPost) {
    // Ottieni il div principale del messaggio/commento
    let messageDiv = isPost ? document.getElementById('post-' + idMessaggio) : document.getElementById('comment-' + idMessaggio);

    // Nascondi il form di modifica
    let editForm = messageDiv.querySelector('.edit-form');
    if (editForm) {
        editForm.style.display = 'none';
    }

    // Mostra nuovamente il contenuto originale
    let originalContentDiv = messageDiv.querySelector('.post-content');
    originalContentDiv.style.display = 'block';
}

// Funzione per salvare il messaggio modificato
function saveMessage(idMessaggio, isPost) {
    // Ottieni il nuovo testo dal textarea
    let nuovoTesto = document.getElementById('edit-text-' + idMessaggio).value;
    let tipo = isPost ? 'post' : 'comment';

    // Crea la richiesta AJAX
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "../pages/tokyobook_edit_comment.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Definisci cosa fare alla ricezione della risposta dal server
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // Risposta dal server
            let response = JSON.parse(xhr.responseText);

            if (response.success) {
                // Aggiorna il contenuto del messaggio/commento con il nuovo testo senza sostituire l'intero contenuto
                let messageDiv = isPost ? document.getElementById('post-' + idMessaggio) : document.getElementById('comment-' + idMessaggio);
                
                // Aggiorna solo il div del contenuto originale
                let contentDiv = messageDiv.querySelector('.post-content');
                contentDiv.innerHTML = nuovoTesto;

                // Rimuovi il form di modifica per garantire che venga ricreato correttamente
                let editForm = messageDiv.querySelector('.edit-form');
                if (editForm) {
                    editForm.remove();
                }

                // Mostra il contenuto aggiornato
                contentDiv.style.display = 'block';
            } else {
                alert("Errore nell'aggiornamento del messaggio.");
            }
        }
    };

    // Invia la richiesta con i dati del messaggio/commento
    xhr.send("id_messaggio=" + idMessaggio + "&testo=" + encodeURIComponent(nuovoTesto) + "&tipo=" + tipo);
}






function cambiaPagina() {
    var select = document.getElementById('pagination-select');
    var pagina = select.value;
    var tipo = '<?php echo $tipo; ?>';
    var pg = '<?php echo ($tipo == 'pagina_personale') ? $nome_pg : ''; ?>';
    
    // Costruisce l'URL in base al tipo e all'eventuale parametro pg
    var url = 'main.php?page=tokyobook&tipo=' + tipo + (pg ? '&pg=' + pg : '') + '&pagina=' + pagina;

    // Cambia pagina
    window.location.href = url;
}




//slect per cellulari
function navigateToCategory(selectElement) {
        // Recupera il valore della categoria selezionata
        var selectedCategory = selectElement.value;

        // Naviga alla pagina corrispondente
        if (selectedCategory) {
            window.location.href = "main.php?page=tokyobook&tipo=" + selectedCategory;
        }
    }
</script>

</body>

</html>