<?php
$postID = (int) $_POST['id_record'];

//estraggo dati eventuali px//

for ($i = 1; $i <= 20; $i++) {

$delete_punti = gdrcd_query("DELETE FROM Punti WHERE id_messaggio = ".$postID);
$edit_px = gdrcd_query("UPDATE personaggio SET esperienza = esperienza - '". $_POST["esperienza$i"] ."', esperienza_r = esperienza_r - '". $_POST["esperienza$i"] ."', shin = shin - '". $_POST["shin$i"] ."', notorieta = notorieta - '". $_POST["notorieta$i"] ."', esperienza_mestiere = esperienza_mestiere - '". $_POST["mestiere$i"] ."' WHERE nome = '". $_POST["nome$i"] ."'");
$delete_mestiere = gdrcd_query("DELETE FROM PuntiMestiere WHERE id_messaggio = ".$postID);

}//chiusura for
$delete_quest = gdrcd_query("DELETE FROM messaggio_quest WHERE id_messaggio = ".$postID);

$postData = gdrcd_query("SELECT id_messaggio_padre AS padre, autore FROM messaggioaraldo WHERE id_messaggio=".$postID);

if((int) $postData['padre'] == -1 && ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1 ||  $postData['autore'] == $_SESSION['login'])) {
    /*Cancello un topic da admin*/
    gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = ".$postID);
    $query = "DELETE FROM messaggioaraldo WHERE id_messaggio_padre= ".$postID." OR id_messaggio= ".$postID;
    $back = 'forum';
} elseif((int) $postData['padre'] != -1 && ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $postData['autore'] == $_SESSION['login'])) {
    /*Cancello un post da admin*/
    $query = "DELETE FROM messaggioaraldo WHERE id_messaggio = ".$postID;
    $back = 'forum&op=read&what='.(int) $postData['padre'];
}

if( ! empty($query)) {
    gdrcd_query($query);
    ?>
    <div class="warning">
        <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
    </div>
    <div class="link_back">
        <a href="main.php?page=<?php echo $back; ?>">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['topic']); ?>
        </a>
    </div>
    <?php
} else {
    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
}