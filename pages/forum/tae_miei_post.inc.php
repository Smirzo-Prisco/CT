<?php
$login = $_SESSION['login'];

// Controllo permessi mestiere (sostituisci con ID reale del mestiere TAE)
$check_mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".gdrcd_filter('in', $login)."' AND id_mestiere = 2");

if (empty($check_mestiere) && $_SESSION['admin'] != 1) {
    echo "<div class='warning'>Non hai i permessi per visualizzare i tuoi post.</div>";
    return;
}

// CANCELLAZIONE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_post = (int)$_GET['delete'];
    gdrcd_query("DELETE FROM tae_post WHERE id = $id_post AND autore = '".gdrcd_filter('in', $login)."'");
    echo "<div class='success'>Post cancellato con successo.</div>";
}
?>

<link rel="stylesheet" href="../../themes/crystal/documentazione.css">
<style>
    .lista_post_tae { max-width: 800px; margin: 30px auto; color: #d6d6d6; font-family: Lato, sans-serif; }
    .post_box_tae { background-color: rgba(255,255,255,0.05); border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.3); }
    .post_box_tae h3 { color: #ce846f; margin: 0 0 10px 0; font-size: 18px; }
    .post_box_tae .desc { font-size: 14px; color: #bbb; margin-bottom: 10px; }
    .post_box_tae .azioni a { color: #ce846f; text-decoration: none; margin-right: 15px; font-size: 14px; }
    .success, .warning { background-color: rgba(255,255,255,0.1); padding: 10px; border-radius: 10px; margin-bottom: 20px; color: #ce846f; }
</style>

<div class="lista_post_tae">
    <h2 style="text-align:center; color:#ce846f;">I Miei Post</h2>

    <?php
    $result = gdrcd_query("SELECT * FROM tae_post WHERE autore = '".gdrcd_filter('in', $login)."' ORDER BY data_creazione DESC", 'result');
    while ($post = gdrcd_query($result, 'fetch')) {
        $descrizione = gdrcd_filter('out', $post['descrizione']);
        $data = date("d/m/Y", strtotime($post['data_creazione']));
        $id = (int)$post['id'];
        ?>
        <div class="post_box_tae">
            <h3>Post del <?php echo $data; ?></h3>
            <div class="desc"><?php echo substr($descrizione, 0, 150); ?>...</div>
            <div class="azioni">
                <a href="main.php?page=forum/tae_upload&id_post=<?php echo $id; ?>">âœŽ Modifica</a>
                <a href="main.php?page=forum/tae_miei_post&delete=<?php echo $id; ?>" onclick="return confirm('Sei sicuro di voler cancellare questo post?')">í ½í·‘ Cancella</a>
            </div>
        </div>
    <?php
    }
    gdrcd_query($result, 'free');
    ?>
</div>
