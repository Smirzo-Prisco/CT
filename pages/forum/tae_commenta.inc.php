<?php
require_once('../../includes/functions.inc.php');

$login = $_SESSION['login'];
$post_id = (int)$_POST['post_id'];
$commento = trim($_POST['commento']);

// Controllo presenza post
$controllo = gdrcd_query("SELECT id FROM tae_post WHERE id = $post_id");
if (empty($controllo)) {
    echo "<div class='warning'>Post non trovato.</div>";
    return;
}

// Validazione minima
if ($commento == '') {
    echo "<div class='warning'>Commento vuoto.</div>";
    return;
}

// Inserimento
gdrcd_query("INSERT INTO tae_commento (post_id, autore, commento) VALUES ($post_id, '".gdrcd_filter('in', $login)."', '".gdrcd_filter('in', $commento)."')");

// Redirect
header("Location: main.php?page=forum/tae_social");
exit;
?>
