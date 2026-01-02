<?php

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
#----- CASO IN CUI BISOGNA MODIFICARE I PUNTI ESPERIENZA/NOTORIETA -----
$px = gdrcd_query("SELECT * FROM araldo WHERE id_araldo = ".gdrcd_filter('num', $_REQUEST['prev'])."");
if($px['punti'] > 0) {

$exp_post = gdrcd_query("SELECT * FROM Punti WHERE id_messaggio = '". $_POST["id_messaggio"] ."'", 'result');
while ($rowe = mysqli_fetch_array($exp_post)){
# Passo 1 ---> Cancellare tutti i punti del post

$misu_px_pg = gdrcd_query("UPDATE personaggio SET esperienza = esperienza - '". $rowe["esperienza"] ."', esperienza_r = esperienza_r - '". $rowe["esperienza"] ."', shin = shin - '". $rowe["shin"] ."', notorieta = notorieta - '". $rowe["notorieta"] ."', esperienza_mestiere = esperienza_mestiere - '". $rowe["mestiere"] ."' WHERE nome = '". $rowe["nome"] ."'");
}

# Passo 2 ---> Cancellare tutti i punti registrati relativi al messaggio

$delete_post_pg = gdrcd_query("DELETE FROM Punti WHERE id_messaggio = '". $_POST["id_messaggio"] ."'");

# Passo 3 ---> Reinserisco tutti i punti registrati relativi al messaggio
for ($i = 1; $i <= 20; $i++) {
if (($_POST["nome$i"] != '') && (($_POST["esperienza$i"] != 0) || ($_POST["shin$i"] != 0) || ($_POST["notorieta$i"] != 0) || ($_POST["mestiere$i"] != 0))) {

#echo "<script type='text/javascript'>alert('".$_POST["nome$i"]."');</script>";
#echo "<script type='text/javascript'>alert('".$_POST["commento$i"]."');</script>";
#echo "<script type='text/javascript'>alert('".$_POST["esperienza$i"]."');</script>";

$re_punti = gdrcd_query("INSERT INTO Punti (nome, esperienza, notorieta, mestiere, shin, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_POST["nome$i"])."', '". $_POST["esperienza$i"] ."', '". $_POST["notorieta$i"] ."', '". $_POST["mestiere$i"] ."', '". $_POST["shin$i"] ."', NOW(), '".gdrcd_filter('num', $_POST['id_messaggio'])."', '".gdrcd_filter('in', $_POST["commento$i"])."')"); 
$edit_px_pg = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + '". $_POST["esperienza$i"] ."', esperienza_r = esperienza_r + '". $_POST["esperienza$i"] ."', shin = shin + '". $_POST["shin$i"] ."', notorieta = notorieta + '". $_POST["notorieta$i"] ."', esperienza_mestiere = esperienza_mestiere + '". $_POST["mestiere$i"] ."' WHERE nome = '". $_POST["nome$i"] ."'");
if ($_POST["nome_new$i"] != '') {
$re_punti_new = gdrcd_query("INSERT INTO Punti (nome, esperienza, notorieta, mestiere, shin, data_evento, id_messaggio, commento) VALUES ('".gdrcd_filter('in', $_POST["nome_new$i"])."', '". $_POST["esperienza_new$i"] ."', '". $_POST["notorieta_new$i"] ."', '". $_POST["mestiere_new$i"] ."', '". $_POST["shin_new$i"] ."', NOW(), '".gdrcd_filter('num', $_POST['id_messaggio'])."', '".gdrcd_filter('in', $_POST["commento_new$i"])."')"); 
$edit_px_pg_new = gdrcd_query("UPDATE personaggio SET esperienza = esperienza + '". $_POST["esperienza_new$i"] ."', esperienza_r = esperienza_r + '". $_POST["esperienza_new$i"] ."', notorieta = notorieta + '". $_POST["notorieta_new$i"] ."', esperienza_mestiere = esperienza_mestiere + '". $_POST["mestiere_new$i"] ."', shin = shin + '". $_POST["shin_new$i"] ."' WHERE nome = '". $_POST["nome_new$i"] ."'");
}
}//fine if
}//fine for
}//fine punti
$row = gdrcd_query("SELECT autore, titolo, messaggio, id_messaggio_padre, anonimo, giornalista FROM messaggioaraldo WHERE id_messaggio=".gdrcd_filter('num', $_POST['id_messaggio']));

if($row['autore'] == $_SESSION['login'] || ($row['autore'] != $_SESSION['login'] && $_SESSION['admin'] == 1)) {
    $time = strftime('%d/%m/%Y %H:%M');
    
    $text_edit = "Modificato da".$_SESSION['login'].": ".$time;

    
    gdrcd_query("UPDATE messaggioaraldo SET messaggio = '".gdrcd_filter('in', $testo_quest)."', titolo = '".gdrcd_filter('in', $_POST['titolo'])."', anonimo = 'no', giornalista = 'no' WHERE id_messaggio = ".gdrcd_filter('num', $_POST['id_messaggio'])." LIMIT 1");

    //modifico bacheca quest
    gdrcd_query("UPDATE messaggio_quest SET location = '".gdrcd_filter('in', $_POST['location'])."', titolo = '".gdrcd_filter('in', $_POST['titolo'])."', partecipanti = '".gdrcd_filter('in', $_POST['partecipanti'])."', riassunto = '".gdrcd_filter('in', $_POST['riassunto'])."', conseguenze = '".gdrcd_filter('in', $_POST['conseguenze'])."', note = '".gdrcd_filter('in', $_POST['note'])."', valutazioni = '".gdrcd_filter('in', $_POST['valutazioni'])."', tipologia = '".gdrcd_filter('in', $_POST['tipologia'])."' WHERE id_messaggio = ".gdrcd_filter('num', $_POST['id_messaggio'])." LIMIT 1");
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