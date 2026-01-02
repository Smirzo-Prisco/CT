<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$parametri = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
$salute = $parametri["salute"];
$id_arma = $_POST['arma'];
$avversario = $_POST['avversario'];
$talento = $_POST['talento'];

if ($talento == 40 || $talento == 41 || $talento == 42) {
$check_talento = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE id_abilita = $talento && nome ='".$_SESSION['login']."'");
}

if (gdrcd_filter('get',$_POST['op'])=='take_action')
	{
      $oggetto = "SELECT * FROM oggetto WHERE id_oggetto = '$id_arma'";
      $nome_oggetto = gdrcd_query($oggetto);      
      $nome = $nome_oggetto['nome'];
      $bonus_arma = $nome_oggetto['attacco'];
      $tipo_arma = $nome_oggetto['tipo_arma'];
          

    
    $numdes = $parametri["car2"]/10;
    $maxnum = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	if ($maxnum == 0) {$maxnum = 20;};
	$num = mt_rand(1, $maxnum);
    $numdesbon = (($numdes/10)-1);
    
    $numtot = $num + $numdesbon;
    

    $messaggio = "$login attacca";
    if ($avversario != "---") {
    
    $messaggio .= " <u>$avversario</u>";    
    
    }
    
    
    $messaggio .= " con $nome con un tiro totale di destrezza di"; //mex generale
    
    
    //Talento + bonus arma bianca
    if ($tipo_arma == 1 && ($talento == 41 && $check_talento['grado'] == 1)) {
        $numtot1 = $num + $numdes + 0.5;
        $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 0,5)";
    } else  if ($tipo_arma == 1 && ($talento == 41 && $check_talento['grado'] == 2)) {
    $numtot1 = $num + $numdes + 1;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 1)";
    } else  if ($tipo_arma == 1 && ($talento == 41 && $check_talento['grado'] == 3)) {
    $numtot1 = $num + $numdes + 1.5;
    $messaggio .= " $numtot1 ($num/20 + $numdes +  bonus talento di 1,5)";
    }
    
    //Talento + bonus arma lancio
    else if ($tipo_arma == 2 && ($talento == 42 && $check_talento['grado'] == 1)) {
    $numtot1 = $num + $numdes + 0.5;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 0,5)";
    } else  if ($tipo_arma == 2 && ($talento == 42 && $check_talento['grado'] == 2)) {
    $numtot1 = $num + $numdes + 1;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 1)";
    } else  if ($tipo_arma == 2 && ($talento == 42 && $check_talento['grado'] == 3)) {
    $numtot1 = $num + $numdes + 1.5;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 1,5)";
    }
    
    
    //Talento + bonus arma da fuoco
    else if ($tipo_arma == 3 && ($talento == 40 && $check_talento['grado'] == 1)) {
    $numtot1 = $num + $numdes + 0.5;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 0,5)";
    } else  if ($tipo_arma == 3 && ($talento == 40 && $check_talento['grado'] == 2)) {
    $numtot1 = $num + $numdes + 1;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 1)";
    } else  if ($tipo_arma == 3 && ($talento == 40 && $check_talento['grado'] == 3)) {
    $numtot1 = $num + $numdes + 1.5;
    $messaggio .= " $numtot1 ($num/20 + $numdes + bonus talento di 1,5)";
    } else {
    $numtot1 = $num + $numdes;
    $messaggio .= " $numtot1 ($num/20 + $numdes)";
    }
    
    
        

    
    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$login', NOW(), 'C', '$messaggio')");
    
    
    } 
    
      echo "<script type='text/javascript'> document.location = 'main.php?dir=$luogo'; </script>";
      /*Redirigo alla pagina del gioco*/
       
            ?>
