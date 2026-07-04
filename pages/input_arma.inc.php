<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$parametri = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
$salute = $parametri["salute"];
$id_arma = $_POST['arma'];
$avversario = $_POST['avversario'];

$check_bianca = gdrcd_query("SELECT COUNT(*) AS n_bianca FROM clgpersonaggioabilita WHERE id_abilita = '41' && nome ='".$_SESSION['login']."'");
$check_lancio = gdrcd_query("SELECT COUNT(*) AS n_lancio FROM clgpersonaggioabilita WHERE id_abilita = '42' && nome ='".$_SESSION['login']."'");
$check_fuoco = gdrcd_query("SELECT COUNT(*) AS n_fuoco FROM clgpersonaggioabilita WHERE id_abilita = '40' && nome ='".$_SESSION['login']."'");
$check_fisico = gdrcd_query("SELECT COUNT(*) AS n_fisico FROM clgpersonaggioabilita WHERE id_abilita = '666' && nome ='".$_SESSION['login']."'");

if (gdrcd_filter('get',$_POST['op'])=='take_action')
	{
          
      $oggetto = "SELECT * FROM oggetto WHERE id_oggetto = '$id_arma'";
      $nome_oggetto = gdrcd_query($oggetto);      
      $nome = $nome_oggetto['nome'];
      $bonus_arma = $nome_oggetto['attacco'];
      $tipo_arma = $nome_oggetto['tipo_arma'];
      $attacco = $nome_oggetto['attacco'];
          
    
    //destrezza//
    $numdes = $parametri["car2"]/10;
    $maxnum = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	if ($maxnum == 0) {$maxnum = 20;};
	$num = mt_rand(1, $maxnum);
    $numdesbon = (($numdes/10)-1);
    
    $numtot = $num + $numdesbon;
    
    //tempra//
    $numtem = $parametri["car6"];
    $maxnum_tem = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	if ($maxnum_tem == 0) {$maxnum_tem = 20;};
	$num_tem = mt_rand(1, $maxnum_tem);
    $numtembon = (($numtem/10)-1);
    $numtot_tem = $num_tem + $numtembon;
    
    //valuto livello talento
    if ($numtot_tem < 17) {
    $bonus_talento = 0.5;
    $livello = "di livello 1";
    } else if ($numtot_tem > 25) {
    $bonus_talento = 1.5;
    $livello = "di livello 3";
    } else {
    $bonus_talento = 1;
    $livello = "di livello 2";
    }
    
    if ($id_arma == '999999999') {
      $tipo = 'attacca fisicamente';
      } else {
      $tipo = 'attacca con '. $nome;
      }
      
    $messaggio = "$login $tipo";
    
    if ($avversario != "---") {
    
    $messaggio .= " <u>$avversario</u>";    
    
    }
    
    $messaggio .= " con un tiro totale di destrezza di"; //mex generale
    
    
    //Talento + bonus corpo a corpo
    
    
    //Talento + bonus arma bianca
    if ($tipo_arma == 1 && $check_bianca['n_bianca'] > 0) {        
    $numtot1 = $num + $numdes + $attacco + $bonus_talento;
    $messaggio .= " $numtot1";
    $messaggio2 .= " $num/20 + $numdes + $attacco (bonus arma) + $bonus_talento (talento arma bianca) = $numtot1";
    }
    
    //Talento + bonus corpo a corpo
    else if ($id_arma == 999999999 && $check_fisico['n_fisico'] > 0) {        
    $numtot1 = $num + $numdes + $bonus_talento;
    $messaggio .= " $numtot1";
    $messaggio2 .= " $num/20 + $numdes + $bonus_talento (talento corpo a corpo) = $numtot1";
    }
    
    //Talento + bonus arma lancio
    else if ($tipo_arma == 2 && $check_lancio['n_lancio'] > 0) {
    $numtot1 = $num + $numdes + $attacco + $bonus_talento;
    $messaggio .= " $numtot1";
    $messaggio2 .= " $num/20 + $numdes + $attacco (bonus arma) + $bonus_talento (talento arma lancio) = $numtot1";
    }
    
    
    //Talento + bonus arma da fuoco
    else if ($tipo_arma == 3 && $check_fuoco['n_fuoco'] > 0) {
    $numtot1 = $num + $numdes + $attacco + $bonus_talento;
    $messaggio .= " $numtot1 ";
    $messaggio2 .= " $num/20 + $numdes + $attacco (bonus arma) + $bonus_talento (talento arma fuoco) = $numtot1";
    } else {
    $numtot1 = $num + $numdes + $attacco;
    $messaggio .= " $numtot1 ";
    $messaggio2 .= " $num/20 + $numdes + $attacco (bonus arma) = $numtot1";
    }
    
    
        

    
    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    if ($check_backing['back_chat'] == 1) {
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo, backing) VALUES ('$luogo', '$login', NOW(), 'C', '".gdrcd_filter('in', $messaggio)."', '1')");
    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('$luogo', 'System', '$login', NOW(), 'Q', '$messaggio2')");
    } else {
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$login', NOW(), 'C', '".gdrcd_filter('in', $messaggio)."')");
    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('$luogo', 'System', '$login', NOW(), 'Q', '$messaggio2')");
    }
    
    
    
    
    
    
    //INIZIO DELLA POLIZIA
    
    //0) Valorizzo dato 20 e messaggio
            
            $maxnum_police = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	        if ($maxnum_police == 0) {$maxnum_police = 20;};
	        $num_police = mt_rand(1, $maxnum_police);
            
            
            //1) Associo alla zona, il rischio

            $zona = gdrcd_query("SELECT * FROM mappa WHERE id = '$luogo'");
            
            $nome_zona = $zona['nome'];

            $sms_staff = "A seguito di un combattimento in $nome_zona, è stato fatto partire un fato automatico riguardo l\'arrivo della polizia. Ti invitiamo a monitorare."; 
            $sms_police = "*chiamata* è stata inviata una pattuglia in $nome_zona per possibile scontro magico. Se disponibile, recati sul posto. *fine chiamata*"; 

            if ($zona['id_mappa'] == 3 || $zona['id_mappa'] == 8 || $zona['id_mappa'] == 10) {
            //calcolo turnazione zona rossa
            if ($num_police >= 1 && $num_police <= 6) {
            $turni = 'non arriva';
            } else if ($num_police >= 7 && $num_police <= 13) {
            $turni = '3 turni';
            } else if ($num_police >= 14 && $num_police <= 20) {
            $turni = '2 turni';
            }
            
            } else if ($zona['id_mappa'] == 7 || $zona['id_mappa'] == 9 || $zona['id_mappa'] == 11) {
            //calcolo turnazione zona gialla
            if ($num_police >= 1 && $num_police <= 6) {
            $turni = '4 turni';
            } else if ($num_police >= 7 && $num_police <= 13) {
            $turni = '3 turni';
            } else if ($num_police >= 14 && $num_police <= 20) {
            $turni = '2 turni';
            }
            
            } if ($zona['id_mappa'] == 4 || $zona['id_mappa'] == 5 || $zona['id_mappa'] == 6) {
            //calcolo turnazione zona verde
            if ($num_police >= 1 && $num_police <= 6) {
            $turni = '3 turni';
            } else if ($num_police >= 7 && $num_police <= 13) {
            $turni = '2 turni';
            } else if ($num_police >= 14 && $num_police <= 20) {
            $turni = 'al prossimo turno';
            }
            
            }
            
            //2.1) Valorizzo master e permessi

            $avviso_utenti = "SELECT * FROM personaggio JOIN privilegi ON personaggio.nome = privilegi.nome WHERE personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW() AND (privilegi.master = 1 OR privilegi.admin = 1)";
            $result_avviso = gdrcd_query($avviso_utenti, 'result');
            
            //2)Verifichiamo prima che ci siano almeno 2 skill di attacco o due armi
            
            $check_skill_attacco = gdrcd_query("SELECT * FROM chat WHERE stanza = ".$_SESSION['luogo']." AND (testo LIKE '%usa la skill di attacco%' || testo LIKE '%attacca con%') AND DATE_ADD(ora, INTERVAL 4 HOUR)  >= NOW() AND tipo = 'C'", 'result');
            $check_master_nope = gdrcd_query("SELECT * FROM chat WHERE stanza = ".$_SESSION['luogo']." AND DATE_ADD(ora, INTERVAL 4 HOUR)  >= NOW() AND tipo = 'M'", 'result');
            
            if(($zona['id_mappa'] == 4 || $zona['id_mappa'] == 5 || $zona['id_mappa'] == 6) && (gdrcd_query($check_skill_attacco, 'num_rows') == 2) && (gdrcd_query($check_master_nope, 'num_rows') == 0)) {
            $responso = 'A seguito di ripetuti attacchi, alcuni cittadini hanno avvisato la polizia. <b>Tempo di arrivo della pattuglia:</b> ' . $turni . '.';
            $arrivo_polizia = gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES (".$_SESSION['luogo'].", 'Sistema automatico', NOW(), 'M', '$responso')");
            
            //contatto con sms master e strike
            
            while ($notifica_staff = gdrcd_query($result_avviso, 'fetch')) {
            $nome = $notifica_staff['nome']; 
            include ('invio_segnalazione_staff.php');
            }
            
            } else if(($zona['id_mappa'] == 7 || $zona['id_mappa'] == 9 || $zona['id_mappa'] == 11) && (gdrcd_query($check_skill_attacco, 'num_rows') == 3) && (gdrcd_query($check_master_nope, 'num_rows') == 0)) {
            $responso = 'A seguito di ripetuti attacchi, alcuni cittadini hanno avvisato la polizia. <b>Tempo di arrivo della pattuglia:</b> ' . $turni . '.';
            $arrivo_polizia = gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES (".$_SESSION['luogo'].", 'Sistema automatico', NOW(), 'M', '$responso')");
            
            //contatto con sms master e strike
            
            while ($notifica_staff = gdrcd_query($result_avviso, 'fetch')) {
            $nome = $notifica_staff['nome']; 
            include ('invio_segnalazione_staff.php');
            }
            
            } else if(($zona['id_mappa'] == 3 || $zona['id_mappa'] == 8 || $zona['id_mappa'] == 10) && (gdrcd_query($check_skill_attacco, 'num_rows') == 4) && (gdrcd_query($check_master_nope, 'num_rows') == 0)) {
            $responso = 'A seguito di ripetuti attacchi, alcuni cittadini hanno avvisato la polizia. <b>Tempo di arrivo della pattuglia:</b> ' . $turni . '.';
            $arrivo_polizia = gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES (".$_SESSION['luogo'].", 'Sistema automatico', NOW(), 'M', '$responso')");
            
            //contatto con sms master e strike
            
            while ($notifica_staff = gdrcd_query($result_avviso, 'fetch')) {
            $nome = $notifica_staff['nome']; 
            include ('invio_segnalazione_staff.php');
            }
            
            }
            
            
            
            
            
            
            
    } 
    
      echo "<script type='text/javascript'> document.location = 'main.php?dir=$luogo'; </script>";
      /*Redirigo alla pagina del gioco*/
       
            ?>
