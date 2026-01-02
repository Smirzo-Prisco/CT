<?php
$cond = '';
$join = '';
$fields = '';

//setto valori quest//
$Titolo=htmlspecialchars($_POST['titolo']);
$Partecipanti=htmlspecialchars($_POST['partecipanti']);
$Location=htmlspecialchars($_POST['location']);
$Riassunto=htmlspecialchars($_POST['riassunto']);
$Conseguenze=htmlspecialchars($_POST['conseguenze']);
$Note=htmlspecialchars($_POST['note']);
$Valutazioni=htmlspecialchars($_POST['valutazioni']);
$Tipologia=htmlspecialchars($_POST['tipologia']);

//testo_quest
//$testo_quest ="<center><div style=\"width: 600px; line-height: 10px; text-align: justify; padding-left: 15px;\"><font color=\"#7e7d7d\" style=\"font-family: tahoma; font-size:10px; line-height: 9px; letter-spacing: 1px;\"><center><font color=\"#70b1b5\" size=\"6\"><b>$Titolo</b></font><br><br><font color=\"#70b1b5\"><i>$Tipologia</i></font></center><br><br><font color=\"#70b1b5\">$Location</font><br><br>$Location<br><br><font color=\"#7e7d7d\" style=\"font-family: tahoma; font-size:10px; line-height: 9px; letter-spacing: 1px;\"><font color=\"#70b1b5\">Partecipanti</font><br><br>$Partecipanti<br><br><font color=\"#7e7d7d\" style=\"font-family: tahoma; font-size:10px; line-height: 9px; letter-spacing: 1px;\"><font color=\"#70b1b5\">Riassunto</font><br><br>$Riassunto<br><br><font color=\"#7e7d7d\" style=\"font-family: tahoma; font-size:10px; line-height: 9px; letter-spacing: 1px;\"><font color=\"#70b1b5\">Conseguenze</font><br><br>$Conseguenze<br><br><font color=\"#7e7d7d\" style=\"font-family: tahoma; font-size:10px; line-height: 9px; letter-spacing: 1px;\"><font color=\"#70b1b5\">Note</font><br><br>$Note<br><br><font color=\"#7e7d7d\" style=\"font-family: tahoma; font-size:10px; line-height: 9px; letter-spacing: 1px;\"><font color=\"#70b1b5\">Valutazioni</font><br><br>$Valutazioni<br><br></style></style>";

$testo_quest ="<center>
<font color=\"#9a6353\" style=\"font-size:20px; text-transform: uppercase;\"><b>$Titolo</b></font><br>
<font color=\"#9a6353\" style=\"font-size:12px;\">$Tipologia</font>
</center><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Luogo</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$Location</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Partecipanti</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$Partecipanti</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Riassunto</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$Riassunto</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Conseguenze</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$Conseguenze</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Note</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$Note</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Valutazioni</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$Valutazioni</font>";

//Qualora fosse una bacheca da punti esperienza//
$araldoPunti = gdrcd_query("SELECT * FROM araldo WHERE id_araldo = '".gdrcd_filter('num', $_POST['araldo'])."'");

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
        gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, giornalista, anonimo) VALUES (".gdrcd_filter('num', $_POST['padre']).", ".gdrcd_filter('num', $araldoData['id_araldo']).", '".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $testo_quest)."', '".gdrcd_filter('in', $_SESSION['login'])."', NOW(), NOW(), 'no', 'no')");
        
        if($_POST['padre'] == -1) {
            $_POST['padre'] = gdrcd_query('', 'last_id');
          
       } else {
            gdrcd_query("UPDATE messaggioaraldo SET data_ultimo_messaggio = NOW() WHERE id_messaggio = ".gdrcd_filter_num($_POST['padre']));
        }
        
        //caricamento post quest
        gdrcd_query("INSERT INTO messaggio_quest (id_messaggio, autore, titolo, location, partecipanti, riassunto, conseguenze, note, valutazioni, tipologia) VALUES (".gdrcd_filter('num', $_POST['padre']).", '".gdrcd_filter('in', $_SESSION['login'])."', '".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $_POST['location'])."', '".gdrcd_filter('in', $_POST['partecipanti'])."', '".gdrcd_filter('in', $_POST['riassunto'])."', '".gdrcd_filter('in', $_POST['conseguenze'])."', '".gdrcd_filter('in', $_POST['note'])."', '".gdrcd_filter('in', $_POST['valutazioni'])."', '".gdrcd_filter('in', $_POST['tipologia'])."')");

        //inizio inserimento punti//
         if ($Tipologia == 'Quest Singola') {
            $punti_master = gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_SESSION["login"])."', '2', NOW(), '".gdrcd_filter('num', $_POST['padre'])."', 'Master della quest')"); 
            $edit_px_master = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 2, esperienza_r = esperienza_r + 2 WHERE nome = '". $_SESSION["login"] ."'");
            
            } else if ($Tipologia == 'Evento') {
            $punti_master = gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_SESSION["login"])."', '2', NOW(), '".gdrcd_filter('num', $_POST['padre'])."', 'Master della quest')"); 
            $edit_px_master = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 2, esperienza_r = esperienza_r + 2 WHERE nome = '". $_SESSION["login"] ."'");
            
            } else if ($Tipologia == 'Quest di Gilda') {
            $punti_master = gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_SESSION["login"])."', '1', NOW(), '".gdrcd_filter('num', $_POST['padre'])."', 'Master della quest')"); 
            $edit_px_master = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 1, esperienza_r = esperienza_r + 1 WHERE nome = '". $_SESSION["login"] ."'");
            
            } else if ($Tipologia == 'Assegnazione Esperienza o Notorietà') {
            $edit_px_master = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 0, esperienza_r = esperienza_r + 0 WHERE nome = '". $_SESSION["login"] ."'");
            
            } else {
            $punti_master = gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_SESSION["login"])."', '3', NOW(), '".gdrcd_filter('num', $_POST['padre'])."', 'Master della quest')"); 
            $edit_px_master = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 3, esperienza_r = esperienza_r + 3 WHERE nome = '". $_SESSION["login"] ."'");
            
            }
            
         if($araldoPunti['punti'] > 0 && ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1)) {
             for ($i = 1; $i <= 20; $i++) {
                 if (($_POST["nome$i"] != '') && (($_POST["esperienza$i"] != 0) || ($_POST["shin$i"] != 0) || ($_POST["notorieta$i"] != 0) || ($_POST["mestiere$i"] != 0))) {
            $punti = gdrcd_query("INSERT INTO Punti (nome, esperienza, notorieta, mestiere, shin, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_POST["nome$i"])."', '". $_POST["esperienza$i"] ."', '". $_POST["notorieta$i"] ."', '". $_POST["mestiere$i"] ."', '". $_POST["shin$i"] ."', NOW(), '".gdrcd_filter('num', $_POST['padre'])."', '".gdrcd_filter('in', $_POST["commento$i"])."')"); 
            $edit_px = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + '". $_POST["esperienza$i"] ."', esperienza_r = esperienza_r + '". $_POST["esperienza$i"] ."', notorieta = notorieta + '". $_POST["notorieta$i"] ."', esperienza_mestiere = esperienza_mestiere + '". $_POST["mestiere$i"] ."', shin = shin + '". $_POST["shin$i"] ."' WHERE nome = '". $_POST["nome$i"] ."'");

            /*echo "<script type='text/javascript'>alert('".$_POST["padre"]."');</script>";
            echo "<script type='text/javascript'>alert('".$_POST["nome$i"]."');</script>";
            echo "<script type='text/javascript'>alert('".$_POST["commento$i"]."');</script>";
            echo "<script type='text/javascript'>alert('".$_POST["esperienza$i"]."');</script>";*/
      }//fine nome/exp vuota
     
      }//fine for
         echo "<script type='text/javascript'>alert('Punti caricati');</script>";
         }//fine araldoPunti
          
          
          //fine inserimento punti
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