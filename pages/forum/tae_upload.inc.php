<?php
// Controllo permessi: il personaggio ha un mestiere della TAE?
$login = $_SESSION['login'];
$check_mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".gdrcd_filter('in', $login)."' AND id_mestiere = 2");

if (empty($check_mestiere) && $_SESSION['admin'] != 1) {
    echo "<div class='warning'>Non hai i permessi per pubblicare su TAE Social.</div>";
    return;
}

// MODIFICA - carico il post se esiste
$post = [];
$modifica = false;

if (isset($_GET['id_post']) || isset($_POST['id_post'])) {
    $id_post = isset($_GET['id_post']) ? (int)$_GET['id_post'] : (int)$_POST['id_post'];
    $post = gdrcd_query("SELECT * FROM tae_post WHERE id = $id_post AND autore = '".gdrcd_filter('in', $login)."'");
    if (!empty($post)) {
        $modifica = true;
    }
}

// SALVATAGGIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['descrizione'])) {
    $descrizione = gdrcd_filter('in', $_POST['descrizione']);
    $immagini = gdrcd_filter('in', $_POST['immagini']); // nomi separati da virgola

    if ($modifica) {
        gdrcd_query("UPDATE tae_post SET immagini = '$immagini', descrizione = '$descrizione' WHERE id = $id_post AND autore = '".gdrcd_filter('in', $login)."'");
        echo "<div class='success'>Post aggiornato con successo.</div>";
    } else {
        gdrcd_query("INSERT INTO tae_post (autore, immagini, descrizione) VALUES ('".gdrcd_filter('in', $login)."', '$immagini', '$descrizione')");
        echo "<div class='success'>Post pubblicato con successo.</div>";
    }
}
?>

<link rel="stylesheet" href="../../themes/crystal/documentazione.css">
<style>
    .upload_tae_container { max-width: 700px; margin: 0 auto; padding: 30px; color: #d6d6d6; }
    .upload_tae_container textarea, .upload_tae_container input[type=text] {
        width: 100%; padding: 10px; border-radius: 10px; border: none; margin-bottom: 20px;
        font-family: Lato, sans-serif; font-size: 16px;
    }
    .upload_tae_container input[type=submit] {
        background-color: #ce846f; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; color: #14172a;
        font-weight: bold;
    }
    .upload_tae_container h2 { color: #ce846f; text-align: center; margin-bottom: 20px; }
    .success, .warning { background-color: rgba(255,255,255,0.1); padding: 10px; border-radius: 10px; margin-bottom: 20px; color: #ce846f; }
</style>

<div class="upload_tae_container">
    <h2><?php echo $modifica ? "Modifica Post" : "Nuovo Post"; ?></h2>
    <form method="post" action="main.php?page=forum/tae_upload">
        <?php if ($modifica): ?>
            <input type="hidden" name="id_post" value="<?php echo (int)$post['id']; ?>">
        <?php endif; ?>

        <label for="immagini">Nomi immagini (separati da virgola):</label>
        <input type="text" name="immagini" id="immagini" value="<?php echo gdrcd_filter('out', isset($post['immagini']) ? $post['immagini'] : ''); ?>" placeholder="es. opera1.jpg, opera2.jpg">

        <label for="descrizione">Descrizione:</label>
        <textarea name="descrizione" id="descrizione" rows="6"><?php echo gdrcd_filter('out', isset($post['descrizione']) ? $post['descrizione'] : ''); ?></textarea>

        <input type="submit" value="<?php echo $modifica ? 'Salva modifiche' : 'Pubblica post'; ?>">
    </form>
</div>
