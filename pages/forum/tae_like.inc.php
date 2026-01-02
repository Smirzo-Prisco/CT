<?php
require_once('../../includes/functions.inc.php');

$login = $_SESSION['login'];
$post_id = (int)$_POST['post_id'];

// Controlla se il like esiste già
$like_check = gdrcd_query("SELECT id FROM tae_like WHERE post_id = $post_id AND autore = '".gdrcd_filter('in', $login)."'");

// Ottieni autore del post
$post = gdrcd_query("SELECT autore FROM tae_post WHERE id = $post_id");

if (empty($post)) {
    echo "<div class='warning'>Post non trovato.</div>";
    return;
}

$autore_post = $post['autore'];

// Ottieni like prima della modifica
$totali_pre = gdrcd_query("SELECT COUNT(*) as tot FROM tae_like WHERE post_id = $post_id");
$like_pre = (int)$totali_pre['tot'];

// LIKE
if (empty($like_check)) {
    gdrcd_query("INSERT INTO tae_like (post_id, autore) VALUES ($post_id, '".gdrcd_filter('in', $login)."')");
    $like_post = $like_pre + 1;
} else {
    gdrcd_query("DELETE FROM tae_like WHERE post_id = $post_id AND autore = '".gdrcd_filter('in', $login)."'");
    $like_post = $like_pre - 1;
}

// Calcolo notorietà
$notorieta_pre = floor($like_pre / 5);
$notorieta_post = floor($like_post / 5);

if ($notorieta_post > $notorieta_pre) {
    gdrcd_query("UPDATE personaggio SET notorieta = notorieta + 1 WHERE nome = '".gdrcd_filter('in', $autore_post)."'");
} elseif ($notorieta_post < $notorieta_pre) {
    gdrcd_query("UPDATE personaggio SET notorieta = notorieta - 1 WHERE nome = '".gdrcd_filter('in', $autore_post)."' AND notorieta > 0");
}

// Redirect
header("Location: main.php?page=forum/tae_social");
exit;
?>
