<?php
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
        gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio ) VALUES (".gdrcd_filter('num', $_POST['padre']).", ".gdrcd_filter('num', $araldoData['id_araldo']).", '".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $_POST['messaggio'])."', '".gdrcd_filter('in', $_SESSION['login'])."', '".gdrcd_filter('in', $_POST['anonimo'])."', '".gdrcd_filter('in', $_POST['giornalista'])."', NOW(), NOW())");

        
        if($_POST['padre'] == -1) {
            $_POST['padre'] = gdrcd_query('', 'last_id');
          
       } else {
            gdrcd_query("UPDATE messaggioaraldo SET data_ultimo_messaggio = NOW() WHERE id_messaggio = ".gdrcd_filter_num($_POST['padre']));
        }
        
        //punti mestiere in alcune bacheche
        
        $check_mestiere = gdrcd_query("SELECT nome, esperienza_mestiere, id_mestiere FROM personaggio WHERE nome ='".$_SESSION['login']."'");
        
        if (
        
             ($check_mestiere['id_mestiere'] == 1 && $araldoData['id_araldo'] == 131) ||
             ($check_mestiere['id_mestiere'] == 2 && ($araldoData['id_araldo'] == 17 || $araldoData['id_araldo'] == 140))
             
           ) {
           
           $edit_px = gdrcd_query("UPDATE personaggio SET esperienza_mestiere = esperienza_mestiere + 0.5, last_date_mestiere = NOW() WHERE nome = '". $_SESSION['login'] ."'");
           $insert_pxm = gdrcd_query("INSERT INTO PuntiMestiere (nome, mestiere, data_evento, commento) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."', '0.5', NOW(), 'Punto mestiere tramite azione in bacheca')");
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