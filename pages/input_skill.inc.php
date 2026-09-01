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

$leggi = "<font color=\"#b4b6bf\">(<a href=\"#\" onclick=\"CT.openSkillDesc($id);return false;\">Leggi</a>)</font>";

if (gdrcd_filter('get',$_POST['op'])=='take_action')
	{

      //valuto se il pg ha effettivamente quel livello
      $level = "SELECT * FROM clgpersonaggioabilita WHERE id_abilita = '$magia' && nome = '$login'";
      $check = gdrcd_query($level);   
      
      $skill1 = "SELECT * FROM abilita WHERE id_abilita = '$magia'";
      $nome_magia1 = gdrcd_query($skill1);      
      $nome1 = $nome_magia1['nome'];
      $tipo1 = $nome_magia1['tipo'];
      
      if (($tipo1 == 'Attacco' || $tipo1 == 'Mentale') && $check['grado'] < $liv) {
      gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('".gdrcd_filter('in', $luogo)."', 'System', '".gdrcd_filter('in', $login)."', NOW(), 'S', 'Hai scelto un livello superiore alla tua skill!')");
      } else {
      
      //puoi usarla
      $skill = "SELECT * FROM abilita WHERE id_abilita = '$magia'";
      $nome_magia = gdrcd_query($skill);      
      $nome = $nome_magia['nome'];
      $tipo = $nome_magia['tipo'];
    
    if ($tipo == 'Attacco base' || $tipo == 'Attacco medio' || $tipo == 'Attacco avanzato') {
    $messaggio = "$login usa la skill di attacco $nome di livello $liv";
    } else if ($tipo == 'Mentale base' || $tipo == 'Mentale media' || $tipo == 'Mentale avanzata' || $tipo == 'Mentale di attacco') {
    $messaggio = "$login usa la skill mentale $nome di livello $liv";
    } else if ($tipo == 'Generica base' || $tipo == 'Generica avanzata') {
    $messaggio = "$login usa la skill generica $nome di livello $liv";
    } else if ($tipo == 'Skill Temporanea') {
    $messaggio = "$login usa la skill $nome di livello $liv";
    } else if ($tipo == 'Difensiva') {
    $messaggio = "$login usa la skill difensiva $nome di livello $liv";
    } else if ($tipo == 'Default') {
    $messaggio = "$login usa la skill di default $nome di livello $liv";    
    } else if ($tipo == 'Potere speciale') {
    $messaggio = "$login usa la skill potere speciale $nome di livello $liv";
    
    } else if ($tipo == 'Talento') {
    
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
    } else if ($numtot >= 26) {
    $livello = "di livello 3";
    } else {
    $livello = "di livello 2";
    }
    $messaggio = "$login usa $nome $livello";
    $messaggio2 = "tot. tempra per la valutazione del livello: $num/20 + $numdesbon = $numtot)";

    //stampo messaggio
    
    }
    
    if ($avversario != "---") {
    
    $messaggio .= " verso <u>$avversario</u>";    
    
    }
    $compose = $messaggio . $leggi;
    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    if ($check_backing['back_chat'] == 1) {
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo, backing) VALUES ('".gdrcd_filter('in', $luogo)."', '".gdrcd_filter('in', $login)."', NOW(), 'C', '".gdrcd_filter('in', $compose)."', '1')");
    if ($tipo == 'Talento') {
    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('".gdrcd_filter('in', $luogo)."', 'System', '".gdrcd_filter('in', $login)."', NOW(), 'Q', '$messaggio2')");
    }
    } else {
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('".gdrcd_filter('in', $luogo)."', '".gdrcd_filter('in', $login)."', NOW(), 'C', '".gdrcd_filter('in', $compose)."')");
    if ($tipo == 'Talento') {
    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('".gdrcd_filter('in', $luogo)."', 'System', '".gdrcd_filter('in', $login)."', NOW(), 'Q', '$messaggio2')");
    }
    }
    gdrcd_query("UPDATE personaggio SET salute = salute-1 WHERE nome ='". $_SESSION['login'] ."'");
    
    if ($tipo == 'Skill Temporanea') {
    $num_usi = "SELECT id_abilita, usi FROM clgpersonaggioabilita WHERE id_abilita = '$magia'";
    $ris_usi = gdrcd_query($num_usi);      
    $usi = $ris_usi['usi'];
    
    if ($usi > 1) {
    gdrcd_query("UPDATE clgpersonaggioabilita SET usi = usi-1 WHERE id_abilita = '$magia' && nome ='". $_SESSION['login'] ."'");
    } else {
    gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita = '$magia' && nome ='". $_SESSION['login'] ."'");
    }//fine_usi
    
    }//fine skill temporanee
    
    
    //valuto punto shin
    
    //setto gli estremi per il px//
      $result_exp = gdrcd_query("SELECT count_exp, last_date_shin from personaggio WHERE nome = '".$_SESSION['login']."'");
      $count_exp = $result_exp['count_exp'];
      $last_date_exp = $result_exp['last_date_shin'];
      
          //setto gli estremi per il px//
          $last_power = 'yesterday 7:00:01';
          $current = new DateTime('now');  //('now') 
          $new_day = new DateTime('today 7:00');  // può essere passato o futuro
          $date_power = new DateTime($last_date_exp); // setto formato
          $date_power_mestiere = new DateTime($last_date_mestiere); // setto formato
          
          if($current < $new_day){ // prima delle 7
          $new_day->modify('-1day'); // setto il range
          }
          
          //shin automatica
           //richiamo le azioni dell'utente
         $check_actions = gdrcd_query("SELECT * FROM chat WHERE stanza = ". $_SESSION['luogo'] ." && mittente = '". $_SESSION['login'] ."' && (tipo = 'C') AND 
         (testo LIKE '%usa la skill generica%' OR 
         testo LIKE '%usa la skill di attacco%' OR
         testo LIKE '%usa la skill mentale%' OR
         testo LIKE '%usa la skill difensiva%' OR
         testo LIKE '%usa la skill potere speciale%' OR
         testo LIKE '%usa la skill di default%')
         && DATE_ADD(ora, INTERVAL 12 HOUR)  >= NOW()", 'result');
         
         if((gdrcd_query($check_actions, 'num_rows') > 2) && ($date_power < $new_day)) {
         $nome_luogo = gdrcd_query("SELECT nome FROM mappa WHERE id=".$_SESSION['luogo']."");            
         $resoconto = "Giocata libera - ". $nome_luogo['nome'] ."";
         
         gdrcd_query("INSERT INTO chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System', '".$_SESSION['login']."', NOW(), 'S', 'Punto shin assegnato')");            
         gdrcd_query("UPDATE personaggio SET shin = shin + 1, last_date_shin = NOW() WHERE nome = '".$_SESSION['login']."' LIMIT 1");

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
    
    
    
    }
    
      echo "<script type='text/javascript'> document.location = 'main.php?dir=$luogo'; </script>";
      /*Redirigo alla pagina del gioco*/
       
            ?>

