<?php session_start();

//Includio i parametri, la configurazione, la lingua e le funzioni
require ('includes/required.php');

	header('Content-Type:text/html; charset=UTF-8');    
    $last_message = $_SESSION['last_message'];
    $luogo = $_SESSION['luogo'];

	//Eseguo la connessione al database
	$handleDBConnection = gdrcd_connect();
	//Ricevo il tempo di reload
	$i_ref_time = gdrcd_filter_get($_GET['ref']);
    
    //Chiamo la funzione per il check degli oggetti temporanei ma va aggiunto un altro controllo per evitare di farlo quando non ce n'è bisogno
    check_scadenza();
	
   

/**********************************************************************************/
if((gdrcd_filter_get($_REQUEST['chat'])=='yes')&&(empty($_SESSION['login'])===FALSE))
{
	/*Aggiornamento chat*/
	/*Se ho inviato un'azione*/
	if ((gdrcd_filter('get',$_POST['op'])=='take_action')&&(($PARAMETERS['mode']['skillsystem']=='ON')||($PARAMETERS['mode']['dices']=='ON')))
	{
		$actual_healt = gdrcd_query("SELECT salute FROM personaggio WHERE nome = '".$_SESSION['login']."'");

		if (gdrcd_filter('get',$_POST['id_ab'])!='no_skill' && gdrcd_filter('get',$_POST['id_ab'])!='')
		{
        	
			if ($actual_healt['salute']>0)
			{
				$skill = gdrcd_query("SELECT nome, car FROM abilita WHERE id_abilita = ".gdrcd_filter('num',$_POST['id_ab'])." LIMIT 1");

				$car = gdrcd_query("SELECT car".gdrcd_filter('num',$skill['car'])." AS car_now FROM personaggio WHERE nome = '".$_SESSION['login']."' LIMIT 1");

				$bonus = gdrcd_query("SELECT SUM(oggetto.bonus_car".gdrcd_filter('num',$skill['car']).") as bonus FROM oggetto JOIN clgpersonaggiooggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome='".$_SESSION['login']."' AND clgpersonaggiooggetto.posizione > 1");
	
																																																																				

				$racial_bonus = gdrcd_query("SELECT bonus_car".gdrcd_filter('num',$skill['car'])." AS racial_bonus FROM razza WHERE id_razza IN (SELECT id_razza FROM personaggio WHERE nome='".$_SESSION['login']."')");

				$rank = gdrcd_query("SELECT grado FROM clgpersonaggioabilita WHERE id_abilita=".gdrcd_filter('num',$_POST['id_ab'])." AND nome='".$_SESSION['login']."' LIMIT 1");
															 
															

				if ($PARAMETERS['mode']['dices']=='ON')
				{
					mt_srand((double)microtime()*1000000);
					$die = mt_rand(1,(int)$_POST['dice']);
				 

					$chat_dice_msg =  gdrcd_filter('in', $MESSAGE['chat']['commands']['use_skills']['die']).' '.gdrcd_filter('num',$die).',';

				}
				else
				{
					$chat_dice_msg = '';
					$die = 0;
				}

				gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'C', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['uses']).' '.gdrcd_filter('in',$skill['nome']).': '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$skill['car'].'']).' '.gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus']).', '.$chat_dice_msg.' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['ramk']).' '.gdrcd_filter('num',$rank['grado']).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['items']).' '.gdrcd_filter('num',$bonus['bonus']).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['sum']).' '.(gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus'])+gdrcd_filter('num',$die)+gdrcd_filter('num',$rank['grado'])+gdrcd_filter('in',$bonus['bonus']))."')");
				gdrcd_query("INSERT INTO bak_chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'C', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['uses']).' '.gdrcd_filter('in',$skill['nome']).': '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$skill['car'].'']).' '.gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus']).', '.$chat_dice_msg.' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['ramk']).' '.gdrcd_filter('num',$rank['grado']).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['items']).' '.gdrcd_filter('num',$bonus['bonus']).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['sum']).' '.(gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus'])+gdrcd_filter('num',$die)+gdrcd_filter('num',$rank['grado'])+gdrcd_filter('in',$bonus['bonus']))."')");

	   		}
	   		else
			{
	      			gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '".gdrcd_capital_letter(gdrcd_filter('in', $_SESSION['login']))."', NOW(), 'S', '".
		gdrcd_filter('in',$MESSAGE['status_pg']['exausted'])."')");

	      			gdrcd_query("INSERT INTO bak_chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '".gdrcd_capital_letter(gdrcd_filter('in', $_SESSION['login']))."', NOW(), 'S', '".
		gdrcd_filter('in',$MESSAGE['status_pg']['exausted'])."')");
			}
	/** * Tiro su caratteristica
		* @author Blancks
	*/
		}
	/**	else if (gdrcd_filter('get', $_POST['id_stats']) != 'no_stats' && gdrcd_filter('get',$_POST['dice']) != 'no_dice')
		{
			mt_srand((double)microtime()*1000000);
			$die=mt_rand(1,gdrcd_filter('num', (int)$_POST['dice']));

			$id_stats = explode('_', $_POST['id_stats']);
													 
																   

			$car = gdrcd_query("SELECT car".gdrcd_filter('num',$id_stats[1])." AS car_now FROM personaggio WHERE nome = '".$_SESSION['login']."' LIMIT 1");
																	   
																		

			$racial_bonus = gdrcd_query("SELECT bonus_car".gdrcd_filter('num',$id_stats[1])." AS racial_bonus FROM razza WHERE id_razza IN (SELECT id_razza FROM personaggio WHERE nome='".$_SESSION['login']."')");

			gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'C', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['uses']).' '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$id_stats[1]]).': '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$id_stats[1].'']).' '.gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus']).', '.gdrcd_filter('in', $MESSAGE['chat']['commands']['use_skills']['die']).' '.gdrcd_filter('num',$die).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['sum']).' '.(gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus'])+gdrcd_filter('num',$die)+gdrcd_filter('num',$rank['grado'])+gdrcd_filter('in',$bonus['bonus']))."')");

		} */
        else if (gdrcd_filter('get', $_POST['id_stats']) != 'no_stats' && gdrcd_filter('get',$_POST['id_stats'])!='')
		{
        
			mt_srand((double)microtime()*1000000);
			$die=mt_rand(1,20);

			$id_stats = explode('_', $_POST['id_stats']);
													 
																   

			$car = gdrcd_query("SELECT car".gdrcd_filter('num',$id_stats[1])." AS car_now FROM personaggio WHERE nome = '".$_SESSION['login']."' LIMIT 1");
																	   
																		

			$racial_bonus = gdrcd_query("SELECT bonus_car".gdrcd_filter('num',$id_stats[1])." AS racial_bonus FROM razza WHERE id_razza IN (SELECT id_razza FROM personaggio WHERE nome='".$_SESSION['login']."')");

			gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'C', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['uses']).' '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$id_stats[1]]).': '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$id_stats[1].'']).' '.gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus']).', '.gdrcd_filter('in', $MESSAGE['chat']['commands']['use_skills']['die']).' '.gdrcd_filter('num',$die).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['sum']).' '.(gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus'])+gdrcd_filter('num',$die)+gdrcd_filter('num',$rank['grado'])+gdrcd_filter('in',$bonus['bonus']))."')");
			gdrcd_query("INSERT INTO bak_chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'C', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['uses']).' '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$id_stats[1]]).': '.gdrcd_filter('in',$PARAMETERS['names']['stats']['car'.$id_stats[1].'']).' '.gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus']).', '.gdrcd_filter('in', $MESSAGE['chat']['commands']['use_skills']['die']).' '.gdrcd_filter('num',$die).', '.gdrcd_filter('in',$MESSAGE['chat']['commands']['use_skills']['sum']).' '.(gdrcd_filter('num',$car['car_now']+$racial_bonus['racial_bonus'])+gdrcd_filter('num',$die)+gdrcd_filter('num',$rank['grado'])+gdrcd_filter('in',$bonus['bonus']))."')");

		}
		else if (gdrcd_filter('get',$_POST['dice'])!='no_dice' && gdrcd_filter('get',$_POST['dice'])!='')
		{
       
		       	mt_srand((double)microtime()*1000000);
	   		$die=mt_rand(1,gdrcd_filter('num',$_POST['dice']));

	   		gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'D', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['die']['cast']).gdrcd_filter('num',$_POST['dice']).': '.gdrcd_filter('in',$MESSAGE['chat']['commands']['die']['sum']).' '.gdrcd_filter('num',$die)."')");
	   		gdrcd_query("INSERT INTO bak_chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '', NOW(), 'D', '".$_SESSION['login'].' '.gdrcd_filter('in',$MESSAGE['chat']['commands']['die']['cast']).gdrcd_filter('num',$_POST['dice']).': '.gdrcd_filter('in',$MESSAGE['chat']['commands']['die']['sum']).' '.gdrcd_filter('num',$die)."')");

    		}
    		
        
	}











/*Se ho inviato un messaggio*/
	if (gdrcd_filter('get',$_POST['op'])=='new_chat_message')
	{

		$actual_healt = gdrcd_query("SELECT salute FROM personaggio WHERE nome = '".$_SESSION['login']."'");

		$chat_message=gdrcd_filter('in', gdrcd_angs($_POST['message']));
		$tag_n_beyond=gdrcd_filter('in',$_POST['tag']);
		$type=gdrcd_filter('in',$_POST['type']);
		$first_char=substr($chat_message,0,1);
		$second_char=substr($chat_message,0,4);
    if($PARAMETERS['mode']['exp_by_chat']=='ON')
    {
      $msg_length = strlen($chat_message);
      $result_exp = gdrcd_query("SELECT count_exp, last_date_exp, last_date_mestiere from personaggio WHERE nome = '".$_SESSION['login']."'");
      $count_exp = $result_exp['count_exp'];
      $last_date_exp = $result_exp['last_date_exp'];
      $last_date_mestiere = $result_exp['last_date_mestiere'];
      #$char_needed = gdrcd_filter('num', $PARAMETERS['settings']['exp_by_chat']['number']);
      #$exp_bonus = $msg_length/$char_needed;
	  $exp_bonus = 1;    
    }

 		if($type < "5" || $type > "7")
 		{
 		    if(!empty($_POST['message'])){
   		    //E' un messaggio.
  		    /*Verifico il tipo di messaggio*/
     			if (($type=="4")||($first_char=="@"))
     			{ /*Sussurro*/
  				$m_type='S';
  				if($type!='4')
  				{
   	  				$dest_end = strpos(substr($chat_message, 1), "@");
        					if ($dest_end === FALSE)
        					{
  	     				/*Se il destinatario e' mal formattato lo prendo come parlato*/
           					$m_type='P';
  	  				}
  	  				else
  	  				{
           					$tag_n_beyond=gdrcd_capital_letter(substr($chat_message, 1, $dest_end));
  	    					 $chat_message=substr($chat_message, $dest_end+2);
  	  				}
  				}//if
  				if ($m_type=='S')
  				{/*Se il sussurro e' inviato correttamente*/

  	     				$r_check_dest = gdrcd_query("SELECT nome FROM personaggio WHERE DATE_ADD(ultimo_refresh, INTERVAL 2 MINUTE) > NOW() AND ultimo_luogo = ".$_SESSION['luogo']." AND nome = '".$tag_n_beyond."' LIMIT 1", 'result');
														  
												   
												  

  	     				if (gdrcd_query($r_check_dest, 'num_rows') < 1)
  	     				{
              					$chat_message=$tag_n_beyond.' '.gdrcd_filter('in',$MESSAGE['chat']['whisper']['no']);
  		    				$tag_n_beyond=$_SESSION['login'];
  	    				}
  				}
  				else
  				{
  					$tag_n_beyond=$_SESSION['tag'];
  				}
     			}
                //sussurro sistema
                else if (($type=="11")||($second_char=="@|"))
     			{ /*Sussurro*/
  				$m_type='Q';
  				if($type!='11')
  				{
   	  				$dest_end = strpos(substr($chat_message, 1), "|@");
        					if ($dest_end === FALSE)
        					{
  	     				/*Se il destinatario e' mal formattato lo prendo come parlato*/
           					$m_type='P';
  	  				}
  	  				else
  	  				{
           					$tag_n_beyond=gdrcd_capital_letter(substr($chat_message, 1, $dest_end));
  	    					 $chat_message=substr($chat_message, $dest_end+2);
  	  				}
  				}//if
  				if ($m_type=='Q')
  				{/*Se il sussurro e' inviato correttamente*/

  	     				$r_check_dest = gdrcd_query("SELECT nome FROM personaggio WHERE DATE_ADD(ultimo_refresh, INTERVAL 2 MINUTE) > NOW() AND ultimo_luogo = ".$_SESSION['luogo']." AND nome = '".$tag_n_beyond."' LIMIT 1", 'result');
														  
												   
												  

  	     				if (gdrcd_query($r_check_dest, 'num_rows') < 1)
  	     				{
              					$chat_message=$tag_n_beyond.' '.gdrcd_filter('in',$MESSAGE['chat']['whisper']['no']);
  		    				$tag_n_beyond=$_SESSION['login'];
  	    				}
  				}
  				else
  				{
  					$tag_n_beyond=$_SESSION['tag'];
  				}
     			}
                

    			
     			elseif (($type=="1")||($first_char=="+"))
     			{ /*Azione*/
      				if ($actual_healt['salute']>0)
      				{
  	   				if ($first_char=="+")
  	   				{
  	   					$chat_message=substr($chat_message, 1);
  	   				}
  	  				$m_type='A';
  	   				$_SESSION['tag']=$tag_n_beyond;
  				}
  				else
  				{
  	       				$m_type='S';
  					$tag_n_beyond=$_SESSION['login'];
  					$chat_message=gdrcd_filter('in',$MESSAGE['status_pg']['exausted']);
  				}
     			}
     			elseif (($type == "10") || in_array($first_char, ["/", "^", "|"])) {
    // Controllo massimo 3 off
    $query = "SELECT tipo, COUNT(*) as count FROM chat 
              WHERE stanza = {$_SESSION['luogo']} AND mittente = '{$_SESSION['login']}'
              AND DATE_ADD(ora, INTERVAL 7 HOUR) >= NOW()
              AND tipo IN ('P', 'A', 'Z')
              GROUP BY tipo";

    $result = gdrcd_query($query, 'result');
    $count_off = 0;
    $count_stopoff = 0;

    while ($row = gdrcd_query($result, 'fetch')) {
        if (in_array($row['tipo'], ['P', 'A'])) {
            $count_off = $row['count'];
        } elseif ($row['tipo'] == 'Z') {
            $count_stopoff = $row['count'];
        }
    }

    if ($count_off == 0) {
        $m_type = 'S';
        $tag_n_beyond = $_SESSION['login'];
        $chat_message = gdrcd_filter('in', $MESSAGE['status_pg']['nooff']);
    } else {
        if (in_array($first_char, ["/", "^", "|"])) {
            $chat_message = substr($chat_message, 1);
        }
        $m_type = 'Z';
        $_SESSION['tag'] = $tag_n_beyond;
    }

    if ($count_stopoff > 4) {
        $m_type = 'S';
        $tag_n_beyond = $_SESSION['login'];
        $chat_message = gdrcd_filter('in', $MESSAGE['status_pg']['stopoff']);
    }
}
                
                
                
                
                
                
                
                elseif ((($type=="2")||($first_char=="=")||($first_char=="-|")||($first_char=="*"))&&($_SESSION['master']==1 || $_SESSION['admin']==1))
     			{ /*Master*/
  				$m_type='M';
  				if(($first_char=="=")||($first_char=="-|"))
  				{
  					$chat_message=substr($chat_message, 1);
  				}
  				if($first_char=="|")
  				{
  					$chat_message=substr($chat_message, 1);
  					$m_type='I';
  				}
                if($first_char=="*")
  				{
  					$chat_message=substr($chat_message, 1);
  					$m_type='Y';
  				}
     			}
     			elseif (($type=="3")&&($_SESSION['master']==1 || $_SESSION['admin']==1))
     			{ /*PNG*/
  				$m_type='N';
  				$_SESSION['tag']=$tag_n_beyond;
     			}
                elseif ((($type=="9")||$first_char=="$")&&($_SESSION['master']==1 || $_SESSION['admin']==1))
				{ /*Globale*/
				$m_type='G';
                if($first_char=="$"){
				$chat_message=substr($chat_message, 1);
                }
                $_SESSION['tag']=$tag_n_beyond;
				}
				elseif ((($type=="8")||$first_char=="%")&&($_SESSION['moderatore']==1 || $_SESSION['admin']==1))
				{ /*Moderatore*/
				$m_type='X';
                if($first_char=="%"){
				$chat_message=substr($chat_message, 1);
                }
                $_SESSION['tag']=$tag_n_beyond;
				}
     			
                
                
                
                
                
                
                
                
                
                
                else if (($type=="0") || (empty($type)===TRUE)) { /* Parlato */

    // Verifica se c'è una quest attiva nella tabella chat_master
    $check_quest3 = gdrcd_query("SELECT * FROM chat_master WHERE luogo = ".$_SESSION['luogo']."", 'result');
    $quest_active = gdrcd_query($check_quest3, 'num_rows') > 0;

    if ($quest_active) {
        // Controlla se il turno è scaduto
        $time = gdrcd_query("SELECT * FROM chat_master WHERE luogo = ".$_SESSION['luogo']." AND pg != '".$_SESSION['login']."' AND date_end < NOW()", 'result');
        $turno_scaduto = gdrcd_query($time, 'num_rows') > 0;

        if ($turno_scaduto) {
            $m_type = 'S';
            $tag_n_beyond = $_SESSION['login'];
            $chat_message = 'Tempo scaduto! Aspetta il prossimo master screen!';
        } else {
            // Verifica se l'utente ha già postato durante il turno corrente
            $info = gdrcd_query("
                SELECT * 
                FROM chat 
                LEFT JOIN chat_master ON chat_master.luogo = chat.stanza 
                WHERE chat.mittente = '".$_SESSION['login']."' 
                AND chat_master.pg != '".$_SESSION['login']."'
                AND chat.ora > chat_master.date_start 
                AND chat.ora < chat_master.date_end 
                AND chat.tipo = 'P'", 
                'result'
            );

            $ha_gia_postato = gdrcd_query($info, 'num_rows') > 0;

            if (!$ha_gia_postato) {
                // L'utente può inviare un messaggio
                $m_type = 'P';
                $_SESSION['tag'] = $tag_n_beyond;
            } else {
                // L'utente ha già inviato un messaggio durante il turno corrente
                $m_type = 'S';
                $tag_n_beyond = $_SESSION['login'];
                $chat_message = 'Hai già postato la tua azione! Aspetta il prossimo master screen!';
            }
        }
    } else {
        // Se non c'è nessuna quest attiva, l'utente può inviare un messaggio
        $m_type = 'P';
        $_SESSION['tag'] = $tag_n_beyond;
    }
} // elseif




        // Recupera l'ultima azione inviata dall'utente
$ultima_azione = gdrcd_query("SELECT ora, testo FROM chat WHERE mittente = '".$_SESSION['login']."' AND tipo = 'P' ORDER BY id DESC LIMIT 1");
$luogo_attuale = gdrcd_query("SELECT privata FROM mappa WHERE id = ".$_SESSION['luogo']);
if ($luogo_attuale['privata'] == 0) {
if ($ultima_azione) {
    $ultimo_time = strtotime($ultima_azione['ora']);
    $current_time = time();
    $time_diff = $current_time - $ultimo_time;
    $current_length = strlen($chat_message);

    if ($m_type === 'P' && $time_diff > 0 && $time_diff < 300 && $current_length >= 30) {

        // Decurta 1 punto esperienza
        gdrcd_query("UPDATE personaggio SET esperienza = IF(esperienza > 0, esperienza - 1, 0) WHERE nome = '".$_SESSION['login']."'");

        // Tenta di decurtare anche 1 punto shin (solo se presente)
        gdrcd_query("UPDATE personaggio SET shin = IF(shin > 0, shin - 1, shin) WHERE nome = '".$_SESSION['login']."'");

        // Sussurro di avviso
        gdrcd_query("INSERT INTO chat (
            stanza,
            mittente,
            destinatario,
            ora,
            tipo,
            testo
        ) VALUES (
            ".$_SESSION['luogo'].",
            'Moderazione',
            '".$_SESSION['login']."',
            NOW(),
            'S',
            'Ti è stato decurtato 1 punto esperienza e, se presente, 1 punto shin, per aver superato il limite caratteri consentito.'
        )");
    }
}
}


     			/*Inserisco il messaggio*/
            //vedo se l'utente vuole il back
            $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
            if ($check_backing['back_chat'] == 1) {
  			gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo, backing) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond))."', NOW(), '".$m_type."', '".$chat_message."', '1')");
            } else {
            gdrcd_query("INSERT INTO chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond))."', NOW(), '".$m_type."', '".$chat_message."')");
  			}  			gdrcd_query("INSERT INTO bak_chat ( stanza, imgs, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", '".$_SESSION['sesso'].";".$_SESSION['img_razza']."', '".$_SESSION['login']."', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond))."', NOW(), '".$m_type."', '".$chat_message."')");





        if($PARAMETERS['mode']['exp_by_chat']=='ON')
        {
        ##################################
        /*check_count_exp();*/
       #####check per azzerare###########
       
       // Recupera se la location è pubblica
    $info_privacy = gdrcd_query("SELECT privata, nome FROM mappa WHERE id = ".$_SESSION['luogo']."");
    
    if ($info_privacy['privata'] == 0) {  // Solo se la location NON è privata
             
          //setto gli estremi per il px//
          //setto gli estremi per il px//
          $last_power = 'yesterday 7:00:01';
          $current = new DateTime('now');  //('now') 
          $new_day = new DateTime('today 7:00');  // può essere passato o futuro
          $date_power = new DateTime($last_date_exp); // setto formato
          $date_power_mestiere = new DateTime($last_date_mestiere); // setto formato

          if($current < $new_day){ // prima delle 7
          $new_day->modify('-1day'); // setto il range
          }
            
            //exp automatica
           //richiamo le azioni dell'utente
            $check_actions = gdrcd_query("SELECT * FROM chat WHERE stanza = ". $_SESSION['luogo'] ." && mittente = '". $_SESSION['login'] ."' && (tipo = 'P' || tipo = 'M') && CHAR_LENGTH(testo) > 99 && DATE_ADD(ora, INTERVAL 12 HOUR)  >= NOW()", 'result');
            if((gdrcd_query($check_actions, 'num_rows') >= 4) && ($date_power < $new_day)) {
            
            $nome_luogo = gdrcd_query("SELECT nome FROM mappa WHERE id=".$_SESSION['luogo']."");            
            $resoconto = "Giocata libera - ". $nome_luogo['nome'] ."";
            
            
            gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 1, esperienza_r = esperienza_r + 1, last_date_exp = NOW() WHERE nome = '".$_SESSION['login']."' LIMIT 1");
            gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, commento) VALUES ('".$_SESSION['login']."', '1', NOW(), '".gdrcd_filter('in', $resoconto)."')");
            gdrcd_query("INSERT INTO chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System', '".$_SESSION['login']."', NOW(), 'S', 'Punto esperienza assegnato')");            
            
            } 
            
            //exp mestiere automatica
            if(
            ($check_backing['esperienza_mestiere'] < 55) &&
            (gdrcd_query($check_actions, 'num_rows') == 4) &&
            (($check_backing['id_mestiere'] == 1 && $_SESSION['luogo'] == 20) ||
            ($check_backing['id_mestiere'] == 2 && $_SESSION['luogo'] == 30) ||
            ($check_backing['id_mestiere'] == 3 && $_SESSION['luogo'] == 24) ||
            ($check_backing['id_mestiere'] == 10 && $_SESSION['luogo'] == 25) ||
            ($check_backing['id_mestiere'] == 4 && $_SESSION['luogo'] == 14)) &&
            ($date_power_mestiere < $new_day)
            )
            {
            
            $nome_luogo_mest = gdrcd_query("SELECT nome FROM mappa WHERE id=".$_SESSION['luogo']."");            
            $resoconto_mest = "Giocata di mestiere - ". $nome_luogo_mest['nome'] ."";
            
            
            gdrcd_query("UPDATE personaggio SET esperienza_mestiere = esperienza_mestiere + 1, last_date_mestiere = NOW() WHERE nome = '".$_SESSION['login']."' LIMIT 1");
            gdrcd_query("INSERT INTO PuntiMestiere (nome, mestiere, data_evento, commento) VALUES ('".$_SESSION['login']."', '1', NOW(), '".gdrcd_filter('in', $resoconto_mest)."')");
            gdrcd_query("INSERT INTO chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System', '".$_SESSION['login']."', NOW(), 'S', 'Punto mestiere assegnato')");
            
            } 
          }  
           
        }
      }//Not empty message
		}
		else
		{ //Altrimenti e' un comando di stanza privata.
			$info = gdrcd_query("SELECT invitati, nome, proprietario FROM mappa WHERE id=".$_SESSION['luogo']."");

   			$ok_command=FALSE;
   			if($info['proprietario']==$_SESSION['login'])
   			{
   				$ok_command=TRUE;
   			}
   			if(strpos($_SESSION['gilda'],$info['proprietario'])!=FALSE)
   			{
   				$ok_command=TRUE;
   			}
	   		if (($type=="5")&&($ok_command===TRUE))
	   		{ //invita
				gdrcd_query("UPDATE mappa SET invitati = '".$info['invitati'].','.gdrcd_capital_letter(strtolower(gdrcd_filter('in', $tag_n_beyond)))."' WHERE id=".$_SESSION['luogo']." LIMIT 1");
																		  
												 
																						   
										  
								
																									  
																				 
						 
																						 
																																																																								   

				gdrcd_query("INSERT INTO chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System message', '".$_SESSION['login']."', NOW(), 'S', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)).' '.$MESSAGE['chat']['warning']['invited']."')");
				gdrcd_query("INSERT INTO bak_chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System message', '".$_SESSION['login']."', NOW(), 'S', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)).' '.$MESSAGE['chat']['warning']['invited']."')");
																																  

				if(empty($_POST['tag'])===FALSE)
			   	{
					gdrcd_query("INSERT INTO messaggi ( mittente, destinatario, spedito, letto, testo ) VALUES ('System message', '".gdrcd_capital_letter(gdrcd_filter('in',$_POST['tag']))."', NOW(), 0,  '".$_SESSION['login'].' '.$MESSAGE['chat']['warning']['invited_message'].' '.$info['nome']."')");													   
																 
			   	}
																 
									  
   			}
   			else if (($type=="6")&&($ok_command===TRUE))
   			{ //caccia
       				$scaccia=str_replace(','.gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)), '',$info['invitati']);
	   			gdrcd_query("UPDATE mappa SET invitati = '".$scaccia."' WHERE id=".$_SESSION['luogo']." LIMIT 1");
																			   
																															   
	   			gdrcd_query("INSERT INTO chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System message', '".$_SESSION['login']."', NOW(), 'S', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)).' '.$MESSAGE['chat']['warning']['expelled']."')");
	   			gdrcd_query("INSERT INTO bak_chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System message', '".$_SESSION['login']."', NOW(), 'S', '".gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)).' '.$MESSAGE['chat']['warning']['expelled']."')");
						
																																																																																																													
				 

   			}
   			else if ($ok_command===TRUE)
   			{ //elenco
			       	$ospiti=str_replace(',', '', $info['invitati']);
       				gdrcd_query("INSERT INTO chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System message', '".$_SESSION['login']."', NOW(), 'S', '".$MESSAGE['chat']['warning']['list'].': '.$ospiti."')");
       				gdrcd_query("INSERT INTO bak_chat ( stanza, mittente, destinatario, ora, tipo, testo ) VALUES (".$_SESSION['luogo'].", 'System message', '".$_SESSION['login']."', NOW(), 'S', '".$MESSAGE['chat']['warning']['list'].': '.$ospiti."')");
   			}//else
 		}//else
																																																			
						 
					 
				 
								
	}
	else//if(op)
	{
		$_SESSION['tag'] = gdrcd_filter('in',$_POST['tag']);
	}

	/*Carico i nuovi messaggi*/
	if(empty($last_message)) $last_message = 0;
								   
																																														   

/** * Scorrimento dei messaggi in chat, verifico se non è stato invertito il flusso, in caso modifico l'ordinamento della query
	* @author Blancks
*/
	$typeOrder = 'ASC';

	if ($PARAMETERS['mode']['chat_from_bottom']=='ON')
																																																																																										 
				 
					
	{
		$typeOrder = 'DESC';
	}

/** * Controllo per impedire il print in chat delle azioni dei precedenti proprietari di una stanza privata
	* Per stanze non private ora_prenotazione equivarrà ad un tempo sempre inferiore all'orario dell'azione inviata
	* facendo risultare quindi sempre veritiero il controllo in questo caso.
																																																																													   
				 
			 
			   
							
																 

	* @author Blancks
*/
    $check_expp = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    $check_back_chat = gdrcd_query("SELECT * FROM chat WHERE stanza = ". $_SESSION['luogo'] ." && mittente = '". $_SESSION['login'] ."' && DATE_ADD(ora, INTERVAL 12 HOUR)  >= NOW() && (tipo = 'P' || tipo = 'M')", 'result');

    if ($_SESSION['admin'] == 1 || $check_expp['esperienza'] < 20) {
    $query= gdrcd_query("SELECT chat.id, chat.imgs, chat.mittente, chat.destinatario, chat.tipo, chat.ora, chat.testo, personaggio.url_img_chat, mappa.ora_prenotazione
						FROM chat
						INNER JOIN mappa ON mappa.id = chat.stanza
						LEFT JOIN personaggio ON personaggio.nome = chat.mittente
						WHERE chat.id > ".$last_message." AND (stanza = ".$_SESSION['luogo']." OR chat.tipo = 'G') AND chat.ora > IFNULL(mappa.ora_prenotazione, '0000-00-00 00:00:00') AND DATE_SUB(NOW(), INTERVAL 180 MINUTE) < ora ORDER BY id ". $typeOrder, 'result');
	} //blocco chi non sta giocando da vedere chi non vuole il back
    else if(gdrcd_query($check_back_chat, 'num_rows') < 1) {
    $query= gdrcd_query("SELECT chat.id, chat.imgs, chat.mittente, chat.destinatario, chat.tipo, chat.ora, chat.testo, chat.backing, personaggio.url_img_chat, mappa.ora_prenotazione
						FROM chat
						INNER JOIN mappa ON mappa.id = chat.stanza
						LEFT JOIN personaggio ON personaggio.nome = chat.mittente
						WHERE chat.id > ".$last_message." AND (stanza = ".$_SESSION['luogo']." OR chat.tipo = 'G') AND chat.ora > IFNULL(mappa.ora_prenotazione, '0000-00-00 00:00:00') AND DATE_SUB(NOW(), INTERVAL 180 MINUTE) < ora AND chat.backing = '0' ORDER BY id ". $typeOrder, 'result');
	} else {
	$query= gdrcd_query("SELECT chat.id, chat.imgs, chat.mittente, chat.destinatario, chat.tipo, chat.ora, chat.testo, personaggio.url_img_chat, mappa.ora_prenotazione
						FROM chat
						INNER JOIN mappa ON mappa.id = chat.stanza
						LEFT JOIN personaggio ON personaggio.nome = chat.mittente
						WHERE chat.id > ".$last_message." AND (stanza = ".$_SESSION['luogo']." OR chat.tipo = 'G') AND chat.ora > IFNULL(mappa.ora_prenotazione, '0000-00-00 00:00:00') AND DATE_SUB(NOW(), INTERVAL 180 MINUTE) < ora ORDER BY id ". $typeOrder, 'result');
	}
    
    
    while ($row = gdrcd_query($query, 'fetch'))
	{
      //immagini simbolo chat
      $mittente = $row['mittente'];
      $img_family = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5, gilda.nome as nome_gilda, ruolo.nome_ruolo, mestiere.nome as nome_mestiere, ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere, ruolo.immagine as immagine_famiglia, ruolo_mestiere.immagine as immagine_mestiere FROM personaggio LEFT JOIN razza ON personaggio.id_razza=razza.id_razza LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo WHERE personaggio.nome = '".$mittente."'";
      $personaggi = gdrcd_query($img_family, 'result');
      $personaggio = gdrcd_query($personaggi, 'fetch');
    
	  //Impedisci XSS nelle immagini
	  $row['url_img_chat']=gdrcd_filter('fullurl', $row['url_img_chat']);
																		

		if ($PARAMETERS['mode']['chaticons']=='ON')
		{
			$icone_chat=explode(";",gdrcd_filter('out', $row['imgs']));
			$add_icon = '<span class="chat_icons"> 
            <a href="#" onclick="Javascript: document.getElementById(\'message\').value=\'@'.$row['mittente'].'@\'; document.getElementById(\'message\').focus();">
            <img src="imgs/guilds/'.$personaggio['immagine_famiglia'].'">
            </a>
            </span>';
		}

        // identifico se l'ultimo messaggio è dell'utente o meno
        $isLastMessageFromUser = ($row['mittente'] == $_SESSION['login']);
        
		switch ($row['tipo'])
		{
			case 'P':
	 
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
                            <img src="imgs/icons/testamini'.$icone_chat[0].'.png">

					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				
                /** colori dialogo **/
                
                $row['testo'] = $row['testo'];
                
                // Controlliamo se ci sono più aperture « che chiusure »
                $aperture = substr_count($row['testo'], '«');
                $chiusure = substr_count($row['testo'], '»');

                // Se le aperture sono maggiori delle chiusure, aggiungiamo le chiusure mancanti
                if ($aperture > $chiusure) {
                // Troviamo la posizione dell'ultima apertura «
                $pos = strrpos($row['testo'], '«');
    
                // Cerchiamo il punto dopo la fine del testo successivo all'ultima apertura
                $fine_parlato = strpos($row['testo'], ' ', $pos); // Primo spazio dopo l'apertura
                if ($fine_parlato === false) {
                    // Se non ci sono spazi, il testo prosegue fino alla fine
                    $fine_parlato = strlen($row['testo']);
                }
                
                // Inseriamo la chiusura subito dopo la fine del testo successivo all'ultima apertura
               $row['testo'] = substr_replace($row['testo'], '</font>»', $fine_parlato, 0);
            }

                
                // Sostituzione per i simboli di apertura e chiusura
                $row['testo'] = str_replace('«', '«<font color=#ce846f>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                $row['testo'] = str_replace('[', '«<font color=#ce846f>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>»', $row['testo']);
				 
				 
				/** * Avatar di chat
					*@author Blancks
				*/
				if ($PARAMETERS['mode']['chat_avatar']=='ON' && !empty($row['url_img_chat']))
				{
					$add_chat .='<img src="'.$row['url_img_chat'].'" class="chat_avatar" style="width:'.$PARAMETERS['settings']['chat_avatar']['width'].'px; height:'.$PARAMETERS['settings']['chat_avatar']['height'].'px;" />';
				}
																							 

														  
									   
			 
																																																																																																			 

														
				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
			 
																			 
																																				   

																			
								
			  
				if ($PARAMETERS['mode']['chaticons']=='ON')
				{
					$add_chat.= $add_icon;
			 																				
				  
				}
						  

				$add_chat.= '<span class="chat_name"><a href="main.php?page=scheda&pg='.$row['mittente'].'">'.$row['mittente'].'</a>';

				if(empty ($row['destinatario']) === FALSE )
				{
					$add_chat.= '<span class="chat_tag"> [<font color=#d89d8c>'.gdrcd_filter('out',$row['destinatario']).'</font>]</span>';
				}

				$add_chat.=': </span> ';
				$add_chat.= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					if ($PARAMETERS['mode']['chat_avatar']=='ON')
						$add_chat .= '<br style="clear:both;" />';

					$add_chat.= '</div>';

			break;


			case 'A':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/

                $add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                
                

				/** * Avatar di chat
					*@author Blancks
				*/
				if ($PARAMETERS['mode']['chat_avatar'] == 'OFF' && !empty($row['url_img_chat']))
				{
					$add_chat .='<img src="'.$row['url_img_chat'].'" class="chat_avatar" style="width:'.$PARAMETERS['settings']['chat_avatar']['width'].'px; height:'.$PARAMETERS['settings']['chat_avatar']['height'].'px;" />';
				}


				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';

				if ($PARAMETERS['mode']['chaticons']=='ON')
				{
					$add_chat.= $add_icon;
				}

				$add_chat.= '<span class="chat_name"><a href="#" onclick="Javascript: document.getElementById(\'tag\').value=\''.$row['mittente'].'\';  document.getElementById(\'type\')[2].selected = \'1\'; document.getElementById(\'message\').focus();">'.$row['mittente'].'</a>';

				if(empty ($row['destinatario']) === FALSE )
				{
					$add_chat.= '<span class="chat_tag"> ['.gdrcd_filter('out',$row['destinatario']).']</span>';
				}
				$add_chat.='</span> ';
				$add_chat.= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					if ($PARAMETERS['mode']['chat_avatar']=='ON')
						$add_chat .= '<br style="clear:both;" />';

					$add_chat.= '</div>';

			break;


			case 'S':
				if ($_SESSION['login']==$row['destinatario'])
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                    $add_chat.= '<span class="chat_sussurro">'.gdrcd_format_time($row['ora']).'</span> &nbsp;';
					$add_chat.= '<span class="chat_sussurro">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['by'].': </span> ';
					$add_chat.= '<span class="chat_sussurro">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
					$add_chat.= '</div>';

				} else if ($_SESSION['login']==$row['mittente'])
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                    $add_chat.= '<span class="chat_sussurro">'.gdrcd_format_time($row['ora']).'</span>&nbsp;';
					$add_chat.= '<span class="chat_sussurro">'.$MESSAGE['chat']['whisper']['to'].' '.gdrcd_filter('out',$row['destinatario']).': </span>';
					$add_chat.= '<span class="chat_sussurro">'.gdrcd_filter('out',$row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '</div>';

				} else if ($_SESSION['admin']==1)
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                    $add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
					$add_chat.= '<span class="chat_name">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['from_to'].' '.gdrcd_filter('out',$row['destinatario']).' </span>';
					$add_chat.= '<span class="chat_name">'.gdrcd_filter('out',$row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '</div>';

				}
			break;

            case 'Q':
				if ($_SESSION['login']==$row['destinatario'])
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                    $add_chat.= '<span class="chat_sussurro">'.gdrcd_format_time($row['ora']).'</span> &nbsp;';
					$add_chat.= '<span class="chat_sussurro">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['skill'].': </span> ';
					$add_chat.= '<span class="chat_sussurro">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
					$add_chat.= '</div>';

				} else if ($_SESSION['login']==$row['mittente'])
				{

				} 
			break;
            
            
			case 'N':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                
                /** colori dialogo **/
                
                $row['testo'] = $row['testo'];
                $row['testo'] = str_replace('[', '[<font color=#7f99bc>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>]', $row['testo']);
                $row['testo'] = str_replace('«', '«<font color=#7f99bc>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                
				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<font color="white" style="font-size: 12px;"><b>'.$row['destinatario'].'</b></font> ';
				$add_chat.= '<span class="chat_msg">'.gdrcd_chatcolor(gdrcd_filter('out',$row['testo'])).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'M':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
                
                $row['testo'] = $row['testo'];
                $row['testo'] = str_replace('[', '[<font color=#d89d8c>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>]', $row['testo']);
                $row['testo'] = str_replace('«', '«<font color=#d89d8c>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                $row['testo'] = str_replace('{', '<font color=\'#ffd28a\' style=\'text-transform: uppercase;\'>', $row['testo']);
                $row['testo'] = str_replace('}', '</font>', $row['testo']);
                $row['testo'] = str_replace('(br)', '<br>', $row['testo']);
                $row['testo'] = str_replace('(center)', '<center>', $row['testo']);
                $row['testo'] = str_replace('(/center)', '</center>', $row['testo']);
                $row['testo'] = str_replace('(cor)', '<i>', $row['testo']);
                $row['testo'] = str_replace('(/cor)', '</i>', $row['testo']);
                $row['testo'] = str_replace('(link)', '<a href=', $row['testo']);
                $row['testo'] = str_replace('(/link)', ' target="_blank"><b>Clikka qui</b></a>', $row['testo']);
                
				$add_chat.= '<br><div class="chat_row_'.$row['tipo'].'">';
                
                $add_chat.= '<TABLE style="background-image: url(\'../themes/crystal/imgs/chat/sfondo_ms.png\'); background-repeat: repeat; border: 2px solid #07080e; width:95%;" align=center><TR><TD align=justify>';
                $add_chat.= '<span class="intestazione_master"><center>MASTER SCREEN ('.$row['mittente'].')<br>ORE '.gdrcd_format_time($row['ora']).'</center><br></span>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], $row['testo'], true).'</span>';
                $add_chat.= '</td></tr></table>';
                
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'I':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<img class="chat_img" src="'.gdrcd_filter('fullurl',$row['testo']).'" />';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'C':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				#$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_skill">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'D':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'O':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
			
			case 'X':
							/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<br><div class="chat_row_'.$row['tipo'].'">';

				#$add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
                
               $add_chat.= '<TABLE style="background-image: url(\'../themes/crystal/imgs/chat/sfondo_ms.png\'); background-repeat: repeat; border: 2px solid #07080e; width:95%;" align=center><TR><TD align=justify>';
                $add_chat.= '<span class="intestazione_moderazione"><center>MODERAZIONE ('.$row['mittente'].')<br>ORE '.gdrcd_format_time($row['ora']).'</center><br></span>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], $row['testo'], true).'</span>';
                $add_chat.= '</td></tr></table>';
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
            
            case 'G':
							/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<br><div class="chat_row_'.$row['tipo'].'">';

				#$add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
                
                 $add_chat.= '<TABLE style="background-image: url(\'../themes/crystal/imgs/chat/sfondo_ms.png\'); background-repeat: repeat; border: 2px solid #07080e; width:95%;" align=center><TR><TD align=justify>';
                $add_chat.= '<span class="intestazione_master"><center>FATO GLOBALE ('.$row['mittente'].')<br>ORE '.gdrcd_format_time($row['ora']).'</center><br></span>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], $row['testo'], true).'</span>';
                $add_chat.= '</td></tr></table>';
                
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
            
            case 'Z':
							/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
				$add_chat.= '<span class="chat_msg_off">'.$row['mittente'].' scrive in OFF: ';
				$add_chat.= '<span class="chat_msg_off">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
            
            case 'Y':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<center><iframe width="100" height="100" src="'.gdrcd_filter('fullurl',$row['testo']).'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
		}

		if ($row['id'] > (int)$last_message)
				$last_message=$row['id'];

	}
	gdrcd_query($query, 'free');
    


           // Prevedo la notifica in caso di nuovi messaggi
    if($_SESSION['last_message'] > 0 && (isset($isLastMessageFromUser) && !$isLastMessageFromUser) && (isset($add_chat) && $add_chat != '')){
        $playAudioController = AudioController::play('chat', TRUE);;
    }

        // Aggiorno ultimo messaggio visualizzato
    $_SESSION['last_message'] = $last_message;
}// Fine (gdrcd_filter_get($_REQUEST['chat']) == 'yes') && (empty($_SESSION['login']) === false)
/******************************************************************************************/
?>
<html>
																   
<head>


  <?php
if(gdrcd_filter('get',$_REQUEST['chat'])=='yes')
{
	echo '<script type="text/javascript"> function echoChat(){';

	/** * Gestione dell'ordinamento
		* @author Blancks
	*/
	if ($PARAMETERS['mode']['chat_from_bottom']=='OFF')
	{
		echo 'parent.document.getElementById(\'pagina_chat\').innerHTML+= '.json_encode((string)$add_chat).';';
		echo 'scrolling = parent.document.getElementById(\'pagina_chat\').scrollHeight;';

	}
	elseif ($PARAMETERS['mode']['chat_from_bottom']=='ON')
	{
		echo 'parent.document.getElementById(\'pagina_chat\').innerHTML= '.json_encode((string)$add_chat).'+parent.document.getElementById(\'pagina_chat\').innerHTML;';
		echo 'scrolling = 0;';
	}


	/** * Gestione intelligente della scrollbar
		* Forza lo scroll solo quando ci sono nuovi messaggi
		* @author Blancks
	*/
	if (!empty($add_chat))
			echo 'parent.document.getElementById(\'pagina_chat\').scrollTop = scrolling;';


	if ((gdrcd_filter('get',$_POST['op'])=='take_action')||(gdrcd_filter('get',$_POST['op'])=='new_chat_message'))
	{
      		if($PARAMETERS['mode']['skillsystem']=='ON')
      		{
         		echo 'parent.document.getElementById(\'chat_form_actions\').reset();';
     		}
     		echo 'parent.document.getElementById(\'chat_form_messages\').reset();
	         parent.document.getElementById(\'chat_form_messages\').elements["tag"].value=\''.$_SESSION["tag"].'\';';
	}//if
	echo '}</script>';
}

// Gestisco l'avviso
if (!empty($playAudioController)) {
    echo $playAudioController;
}
?>
   <!--meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"-->
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <meta http-equiv="refresh" content="<?php echo $i_ref_time; ?>">

   <link rel="stylesheet" href="../themes/<?php echo $PARAMETERS['themes']['current_theme'];?>/presenti.css" TYPE="text/css">
   <link rel="stylesheet" href="../themes/<?php echo $PARAMETERS['themes']['current_theme'];?>/main.css" TYPE="text/css">
   <link rel="stylesheet" href="../themes/<?php echo $PARAMETERS['themes']['current_theme'];?>/chat.css" TYPE="text/css">
</head>
<body class="transparent_body" <?php if(gdrcd_filter('get',$_REQUEST['chat'])=='yes'){ echo 'onLoad="echoChat();"';} ?> >
<?php
	/*function check_count_exp(){
               	$start_date = gdrcd_query("SELECT start_date FROM personaggio WHERE nome = '".$_SESSION['login']."'");
        if($start_date['start_date'] < $date_now){
        	gdrcd_query("UPDATE personaggio SET count_exp=0 WHERE nome = '".$_SESSION['login']."'");
        }
    }*/
    
    
	function check_scadenza(){
    $result = gdrcd_query("SELECT clgpersonaggiooggetto.id_oggetto, oggetto.nome, clgpersonaggiooggetto.isTemp, clgpersonaggiooggetto.temp_giorni, clgpersonaggiooggetto.data_scadenza, clgpersonaggiooggetto.used, oggetto.bonus_car1_extra, oggetto.bonus_car2_extra, oggetto.bonus_car3_extra, oggetto.bonus_car4_extra, clgpersonaggiooggetto.cariche FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND posizione > 0 ORDER BY oggetto.nome", 'result');
			while($row = gdrcd_query($result, 'fetch')){
            if($row['used']==1 && $row['data_scadenza'] < date("Y-m-d H:i:s")){
			$query = "UPDATE personaggio SET car0 = car0 - ".$row['bonus_car1_extra'].", car2 = car2 - ".$row['bonus_car2_extra']." , car4 = car4 - ".$row['bonus_car3_extra'].", car6 = car6 - ".$row['bonus_car4_extra']."  WHERE nome = '".$_SESSION['login']."'";
            gdrcd_query($query);
            $testo_mex = "Un oggetto potenziabile da te usato è scaduto. Le statistiche sono state decrementate. Puoi tornare a usare un altro oggetto potenziabile";
            $mex_si=gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."', '".gdrcd_filter('in', $_SESSION['login'])."', NOW(), 'Depotenziamento oggetto', '$testo_mex', 'off')"); 
            if($row['cariche']>1){
            $query="UPDATE clgpersonaggiooggetto SET cariche = cariche -1 WHERE nome ='".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num', $row['id_oggetto'])."' LIMIT 1";        
            $query2="UPDATE clgpersonaggiooggetto SET used = 0 WHERE nome = '".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num', $row['id_oggetto'])."'";
            $query3="UPDATE clgpersonaggiooggetto SET data_scadenza = '0000-00-00 00:00:00' WHERE nome = '".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num', $row['id_oggetto'])."'";
            gdrcd_query($query2);
            gdrcd_query($query3);
            }
            else{
            $query = "DELETE FROM clgpersonaggiooggetto WHERE nome = '".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num', $row['id_oggetto'])."'";
			}
            gdrcd_query($query);
            }
            }

}

    //controlla sessione
	//controlla esilio
?>
