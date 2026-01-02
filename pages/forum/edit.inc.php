<?php
$row = gdrcd_query("SELECT autore, titolo, messaggio, id_messaggio_padre, anonimo, giornalista FROM messaggioaraldo WHERE id_messaggio=".gdrcd_filter('num', $_POST['id_messaggio']));

if($row['autore'] == $_SESSION['login'] || ($row['autore'] != $_SESSION['login'] && $_SESSION['admin'] == 1)) {
    $time = strftime('%d/%m/%Y %H:%M');
    
    if ($row['anonimo'] == 'si') {
    $text_edit = "Modificato:".$time;
    } else {
    $text_edit = "Modificato da ".$_SESSION['login'].": ".$time;
    }
    
    gdrcd_query("UPDATE messaggioaraldo SET messaggio = '".gdrcd_filter('in', $_POST['messaggio']).'\n\n\n\n '.$text_edit."', titolo = '".gdrcd_filter('in', $_POST['titolo'])."', anonimo = '".gdrcd_filter('in', $_POST['anonimo'])."', giornalista = '".gdrcd_filter('in', $_POST['giornalista'])."' WHERE id_messaggio = ".gdrcd_filter('num', $_POST['id_messaggio'])." LIMIT 1");
    ?>
    <div class="warning">
        <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
    </div>
    <div class="link_back">
        <a href="main.php?page=forum">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['back']); ?>
        </a>
    </div>
    <?php
    if($row['id_messaggio_padre'] == -1) {
        gdrcd_redirect('main.php?page=forum&op=read&what='.gdrcd_filter('num', $_POST['id_messaggio']).'&where='.gdrcd_filter('num', $_POST['araldo']).'&prev='.gdrcd_filter('num', $_POST['prev']));
    } else {
        gdrcd_redirect('main.php?page=forum&op=read&what='.gdrcd_filter('num', $row['id_messaggio_padre']).'&where='.gdrcd_filter('num', $_POST['araldo']).'&prev='.gdrcd_filter('num', $_POST['prev']));
    }
} else {
    ?>
    <div class="warning">
        Furbacchione ;-)
    </div>
    <?php
}