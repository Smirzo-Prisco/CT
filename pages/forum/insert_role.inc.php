<?php
//setto mesi//
date_default_timezone_set("Europe/Rome");
$mese = date('n');
$anno = date('Y');

if ($mese == 1) {
$titolo ="ROLE GENNAIO $anno";
} else if ($mese == 2) {
$titolo ="ROLE FEBBRAIO $anno";
} else if ($mese == 3) {
$titolo ="ROLE MARZO $anno";
} else if ($mese == 4) {
$titolo ="ROLE APRILE $anno";
} else if ($mese == 5) {
$titolo ="ROLE MAGGIO $anno";
} else if ($mese == 6) {
$titolo ="ROLE GIUGNO $anno";
} else if ($mese == 7) {
$titolo ="ROLE LUGLIO $anno";
} else if ($mese == 8) {
$titolo ="ROLE AGOSTO $anno";
} else if ($mese == 9) {
$titolo ="ROLE SETTEMBRE $anno";
} else if ($mese == 10) {
$titolo ="ROLE OTTOBRE $anno";
} else if ($mese == 11) {
$titolo ="ROLE NOVEMBRE $anno";
} else if ($mese == 12) {
$titolo ="ROLE DICEMBRE $anno";
}

$testo_role = "Per fini statistici (ricordandovi che più giocate più, con il nuovo sistema, potete aumentare le statistiche e diventare potenti), segnate qui le vostre role man mano che le eseguite, ricordando che:<br>
<br>
› Viene premiata <b>una sola</b> role al giorno per PG, dunque se ne effettuate più di una segnalatelo affianco;<br>
› Occorre la <b>data</b> della role (<u>NON</u> il giorno, la data completa). Per esempio: 12/07/2017;<br>
› Bisogna segnare anche l'<b>orario</b>. Per esempio: dalle 13:00 alle 18:00;<br>
› Le <b>note importanti</b> devono essere <u>in vista</u>. Per esempio: punti agli oggetti, punti ferita recuperati/da togliere e così via;<br>
› Le giocate in Chat Privata (Albergo) non vengono premiate;<br>
› I punti per i PNG vanno segnati <b>ANCHE</b> nell'altro post, sempre in questa bacheca;<br>
› Le role che vengono congelate e scongelate devono essere segnate;<br>
<br>
Per qualsiasi dubbio contattare una Guida o leggere il post in Bacheche Informazioni › Dubbi&Domande!
";

$cond = '';
$join = '';
$fields = '';

if($_POST['padre'] == -1) {
    $cond = ' araldo.id_araldo='.gdrcd_filter('num', $_POST['araldo']);
} else {
    $fields = ', MA.chiuso';
    $join = ' INNER JOIN messaggioaraldo AS MA ON MA.id_araldo=araldo.id_araldo ';
    $cond = " MA.id_messaggio=".gdrcd_filter('num', $_POST['padre'])." AND id_messaggio_padre=-1";
}

$thread = gdrcd_query("SELECT araldo.id_araldo, araldo.tipo, araldo.proprietari".$fields." FROM araldo ".$join.(! empty($cond) ? ' WHERE '.$cond : ''),'result');

if(gdrcd_query($thread, 'num_rows')) {
    $araldoData = gdrcd_query($thread, 'fetch');
    if(($araldoData['tipo'] == SOLORAZZA and ($_SESSION['id_razza'] == $araldoData['proprietari'] || $_SESSION['permessi'] >= MODERATOR)) || ($araldoData['tipo'] == SOLOGILDA and (strpos($_SESSION['gilda'],'*'.$araldoData['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR)) || ($araldoData['tipo'] >= SOLOMASTERS and $_SESSION['permessi'] >= GAMEMASTER) || ($araldoData['tipo'] >= SOLOMODERATORS and $_SESSION['permessi'] >= MODERATOR) || ($araldoData['tipo'] == PERTUTTI) || ($araldoData['tipo'] == INGIOCO) || $_POST['padre'] == -1 or ($araldoData['chiuso'] != 1 || $_SESSION['permessi'] >= MODERATOR)) {
        //Solo se il thread non è chiuso
        gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, anonimo, giornalista, importante, data_messaggio, data_ultimo_messaggio ) VALUES (".gdrcd_filter('num', $_POST['padre']).", '141', '".gdrcd_filter('in', $titolo)."', '".gdrcd_filter('in', $testo_role)."', '".gdrcd_filter('in', $_SESSION['login'])."', 'no', 'no', '1', NOW(), NOW())");

        
        if($_POST['padre'] == -1) {
            $_POST['padre'] = gdrcd_query('', 'last_id');
          
       } else {
            gdrcd_query("UPDATE messaggioaraldo SET data_ultimo_messaggio = NOW() WHERE id_messaggio = ".gdrcd_filter_num($_POST['padre']));
        }
        
        
        ?>
        <div class="warning">
            <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
        </div>
        <?php
        gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = ".gdrcd_filter('num', $_POST['padre'])." AND nome != '".$_SESSION['login']."'");

        gdrcd_redirect('main.php?page=forum&op=read&what='.gdrcd_filter('num', $_POST['padre']).'&where='.$araldoData['id_araldo']);
    } else {
        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    }
} else {
    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['administration']['forums']['not_exists']).'</div>';
}