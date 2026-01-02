<link rel="stylesheet" href="../../themes/crystal/documentazione.css">

<style>
/* Stili essenziali integrati */
.feed_container { padding: 30px; display: flex; flex-direction: column; align-items: center; }
.filter_bar { width: 100%; max-width: 800px; margin-bottom: 30px; }
.filter_bar input[type="text"] { width: 100%; padding: 10px; border-radius: 10px; border: none; font-size: 16px; }
.post_card { background-color: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; margin-bottom: 30px; width: 100%; max-width: 800px; box-shadow: 0 0 10px rgba(255,255,255,0.1); }
.post_header { display: flex; justify-content: space-between; color: #ce846f; font-size: 14px; margin-bottom: 10px; }
.post_images img { max-width: 100%; border-radius: 10px; max-height: 300px; margin-right: 10px; }
.post_description { color: #d6d6d6; font-size: 16px; line-height: 1.4; margin-bottom: 10px; }
.post_actions { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #999; }
.comment_section { margin-top: 15px; }
.comment { border-top: 1px solid rgba(255,255,255,0.1); padding: 8px 0; color: #ccc; font-size: 14px; }
.like_button { color: #ce846f; cursor: pointer; }
</style>

<div class="feed_container">
<?php
// Controllo se l'utente loggato è un artista TAE
$utente = $_SESSION['login'];
$check_mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".gdrcd_filter('in', $utente)."' AND id_mestiere = 2");
?>

<?php if (!empty($check_mestiere) || $_SESSION['login'] == 'Lii'): ?>
    <div style="width: 100%; max-width: 800px; text-align: right; margin-bottom: 20px;">
        <a href="main.php?page=forum/tae_upload" style="padding: 8px 15px; background-color: #ce846f; color: #14172a; border-radius: 8px; text-decoration: none; font-weight: bold;">+ Nuovo Post</a>
        <a href="main.php?page=forum/tae_miei_post" style="margin-left: 10px; color: #999; font-size: 14px;">Gestisci i tuoi post</a>
    </div>
<?php endif; ?>

    <div class="filter_bar">
    <form method="get" action="main.php">
        <input type="hidden" name="page" value="forum/tae_social">
        <input type="text" name="autore" placeholder="Cerca artista..." value="<?php echo isset($_GET['autore']) ? gdrcd_filter('out', $_GET['autore']) : ''; ?>">
    </form>
</div>


<?php
$where = "";
if (!empty($_GET['autore'])) {
    $autore = gdrcd_filter('in', $_GET['autore']);
    $where = "WHERE autore = '$autore'";
}

$query_post = "SELECT * FROM tae_post $where ORDER BY data_creazione DESC";

$result_post = gdrcd_query($query_post, 'result');

if (!empty($_GET['autore'])): ?>
    <div style="color:#ce846f; font-size:16px; margin-bottom:20px;">
        Stai visualizzando i post di <strong>@<?php echo gdrcd_filter('out', $_GET['autore']); ?></strong>
        [<a href="main.php?page=forum/tae_social" style="color:#999;">Torna al feed completo</a>]
    </div>
<?php endif;

while ($post = gdrcd_query($result_post, 'fetch')) {
    $id_post = (int)$post['id'];
    $immagini = explode(',', $post['immagini']); // oppure json_decode se decidi per JSON
    $descrizione = gdrcd_filter('out', $post['descrizione']);
    $autore = gdrcd_filter('out', $post['autore']);
    $data = date("d/m/Y", strtotime($post['data_creazione']));

    // Like
    $like_q = gdrcd_query("SELECT COUNT(*) as tot FROM tae_like WHERE post_id = $id_post");
    $tot_like = $like_q['tot'];

    // Commenti
    $comments_q = gdrcd_query("SELECT * FROM tae_commento WHERE post_id = $id_post ORDER BY data_commento ASC", 'result');
    $num_comments = mysqli_num_rows($comments_q);
    ?>
    <div class="post_card">
        <div class="post_header">
            <div><strong>@<?php echo $autore; ?></strong></div>
            <div><?php echo $data; ?></div>
        </div>
        <div class="post_images">
            <?php foreach ($immagini as $img): ?>
                <img src="<?php echo trim($img); ?>" alt="opera">
            <?php endforeach; ?>
        </div>
        <div class="post_description"><?php echo $descrizione; ?></div>
        <div class="post_actions">
            <div class="like_button"><form action="main.php?page=forum/tae_like" method="post" style="display:inline;">
    <input type="hidden" name="post_id" value="<?php echo $id_post; ?>">
    <input type="submit" class="like_button" value="❤️ <?php echo $tot_like; ?> Like">
</form>
</div>
            <div><?php echo $num_comments; ?> Commenti</div>
        </div>
        <div class="comment_section">
    <?php while ($comment = gdrcd_query($comments_q, 'fetch')): ?>
        <div class="comment"><strong>@<?php echo gdrcd_filter('out', $comment['autore']); ?>:</strong> <?php echo gdrcd_filter('out', $comment['commento']); ?></div>
    <?php endwhile; ?>

    <?php if (isset($_SESSION['login'])): ?>
        <form action="main.php?page=forum/tae_commenta" method="post" style="margin-top:10px;">
            <input type="hidden" name="post_id" value="<?php echo $id_post; ?>">
            <textarea name="commento" rows="2" placeholder="Scrivi un commento..." style="width:100%; padding:6px; border-radius:8px; font-size:14px;"></textarea>
            <input type="submit" value="Commenta" style="margin-top:5px; padding:5px 10px; border:none; border-radius:8px; background-color:#ce846f; color:#14172a; font-weight:bold; cursor:pointer;">
        </form>
    <?php endif; ?>
</div>

    </div>
<?php
}
gdrcd_query($result_post, 'free');
?>
</div>
