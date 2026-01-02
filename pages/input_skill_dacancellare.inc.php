<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$parametri = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
$salute = $parametri["salute"];
$magia = $_POST['magie'];
$liv = $_POST['livello'];
$avversario = $_POST['avversario'];

/*link di descrizione*/

$id = gdrcd_filter('out', $magia);
$addr = "skill_desc.proc.php".$id;

$leggi = "<font color=\"#b4b6bf\">(<a href=\"javascript:;\" onClick=\'window.open(\"skill_desc.proc.php?id=$id\", \"titolo\", \"width=400, height=200, resizable, status, scrollbars=1, align=center, location\");\'><font color=\"#b4b6bf\">Leggi</font></a>)</font></font>";

if (gdrcd_filter('get',$_POST['op'])=='take_action')
	{


      $skill = "SELECT * FROM abilita WHERE id_abilita = '$magia'";
      $nome_magia = gdrcd_query($skill);      
      $nome = $nome_magia['nome'];
      $tipo = $nome_magia['tipo'];
    
    if ($tipo != 'Talento') {
    $messaggio = "$login usa $nome di livello $liv";
    } else {
    //tempra//
    $numdes = $parametri["car6"];
    $maxnum = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	if ($maxnum == 0) {$maxnum = 20;};
	$num = mt_rand(1, $maxnum);
    $numdesbon = (($numdes/10)-1);
    $numtot = $num + $numdesbon;
    
    //valuto livello talento
    if ($numtot < 17) {
    $livello = "di livello 1";
    } else if ($numtot > 25) {
    $livello = "di livello 3";
    } else {
    $livello = "di livello 2";
    }
    $messaggio = "$login usa $nome $livello (tot. tempra: $numtot ($num/20 + $numdesbon))";

    //stampo messaggio
    
    }
    
    if ($avversario != "---") {
    
    $messaggio .= " verso <u>$avversario</u>";    
    
    }
    $compose = $messaggio . $leggi;
    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    if ($check_backing['back_chat'] == 1) {
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo, backing) VALUES ('".gdrcd_filter('in', $luogo)."', '".gdrcd_filter('in', $login)."', NOW(), 'C', '".gdrcd_filter('in', $compose)."', '1')");
    } else {
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('".gdrcd_filter('in', $luogo)."', '".gdrcd_filter('in', $login)."', NOW(), 'C', '".gdrcd_filter('in', $compose)."')");
    }
    gdrcd_query("UPDATE personaggio SET salute = salute-1 WHERE nome ='". $_SESSION['login'] ."'");
    
    if ($tipo == 'Temporanea') {
    $num_usi = "SELECT id_abilita, usi FROM clgpersonaggioabilita WHERE id_abilita = '$magia'";
    $ris_usi = gdrcd_query($num_usi);      
    $usi = $ris_usi['usi'];
    
    if ($usi > 1) {
    gdrcd_query("UPDATE clgpersonaggioabilita SET usi = usi-1 WHERE id_abilita = '$magia' && nome ='". $_SESSION['login'] ."'");
    } else {
    gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita = '$magia' && nome ='". $_SESSION['login'] ."'");
    }//fine_usi
    
    }//fine skill temporanee
    }
    
      echo "<script type='text/javascript'> document.location = 'main.php?dir=$luogo'; </script>";
      /*Redirigo alla pagina del gioco*/
       
            ?>

