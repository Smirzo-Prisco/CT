<?php

$id_messaggio = $_REQUEST['id_record'];
$newid = $_REQUEST['newid'];
$padre = $_REQUEST['padre'];

$result = gdrcd_query("SELECT * FROM messaggioaraldo WHERE id_messaggio = $id_messaggio", 'result');
$row = gdrcd_query($result, 'fetch');

$move = gdrcd_query("UPDATE messaggioaraldo SET id_araldo = '$newid' WHERE id_messaggio = $id_messaggio", 'result');
#$move_originale = gdrcd_query("UPDATE messaggioaraldo SET id_araldo = '$newid' WHERE id_messaggio_padre = $padre", 'result');
$change_letto = gdrcd_query("UPDATE araldo_letto SET araldo_id = '$newid' WHERE thread_id = $id_messaggio", 'result');
$back = 'forum';

echo 'Il messaggio <b>'. $row['titolo'] .' </b>&egrave; stato spostato<br><br><br>';

?>

<div class="link_back">
        <a href="main.php?page=<?php echo $back; ?>">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['topic']); ?>
        </a>
    </div>

