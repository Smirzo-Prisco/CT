<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$parametri = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
$salute = $parametri["salute"];
$item=explode('-', gdrcd_filter('in',$_POST['id_item']));

if (gdrcd_filter('get',$_POST['op'])=='take_action')
	{
    
      $oggetto = "SELECT * FROM oggetto WHERE id_oggetto = '$item'";
      $nome_oggetto = gdrcd_query($oggetto);      
      $nome = $nome_oggetto['nome'];
      $heal = $nome_oggetto['heal'];
      
      //controlliamo se serve a curare
      if ($heal > 0) {
      //ora poniamo 100 come valore massimo
      $salute_now = $heal + $salute;
      
      if(($heal + $salute) >= 100){
                    	$heal = 100-$salute;
                    }
                                  
      //ora incrementiamo la salute
      $query = "UPDATE personaggio SET salute = salute + ".$heal." WHERE nome = '".$_SESSION['login']."'";
	  gdrcd_query($query);
      
      $salute_now = $heal + $salute;
      $messaggio .= "$login usa $nome e incrementa la sua salute di $heal (salute attuale: $salute_now/100)";
      }//chiusura controllo se serve a curare
    

    
    
    
    
    
    
    
    //oggetti potenziabili
    
            $used = gdrcd_query("SELECT used as used FROM clgpersonaggiooggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");

			$isTemp = gdrcd_query("SELECT isTemp as scadenza FROM clgpersonaggiooggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");

            $giorni = gdrcd_query("SELECT temp_giorni as giorni FROM clgpersonaggiooggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");
			
            $bonus_car1_extra = gdrcd_query("SELECT oggetto.bonus_car1_extra FROM oggetto JOIN clgpersonaggiooggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");											
			$bonus_car2_extra = gdrcd_query("SELECT oggetto.bonus_car2_extra FROM oggetto JOIN clgpersonaggiooggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");
			$bonus_car3_extra = gdrcd_query("SELECT oggetto.bonus_car3_extra FROM oggetto JOIN clgpersonaggiooggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");
			$bonus_car4_extra = gdrcd_query("SELECT oggetto.bonus_car4_extra FROM oggetto JOIN clgpersonaggiooggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND clgpersonaggiooggetto.id_oggetto = '".gdrcd_filter('num', $item[0])."' LIMIT 1");
			
			/*Per gli oggetti ad uso singolo */
			if($isTemp['scadenza'] > 0){
				if($used['used'] == 0){
               
                	#$scadenza_oggetto=date("Y-m-d H:i:s");
                    $giorni = (int) $giorni['giorni']; 
                   	
					$scadenza_oggetto = date('Y-m-d H:i:s', strtotime("+".$giorni." days"));
                    
					$query = "UPDATE clgpersonaggiooggetto SET used = 1 WHERE nome = '".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num', $item[0])."'";
					gdrcd_query($query);
                    
					$query = "UPDATE personaggio SET car0 = car0 + ".$bonus_car1_extra['bonus_car1_extra'].", car2 = car2 + ".$bonus_car2_extra['bonus_car2_extra']." , car4 = car4 + ".$bonus_car3_extra['bonus_car3_extra'].", car6 = car6 + ".$bonus_car4_extra['bonus_car4_extra']."  WHERE nome = '".$_SESSION['login']."'";
					gdrcd_query($query);
                    echo "<script type='text/javascript'>alert('2');</script>";
                    $query = "UPDATE clgpersonaggiooggetto SET data_scadenza='".$scadenza_oggetto."' WHERE nome = '".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num', $item[0])."'";
					gdrcd_query($query);
                    echo "<script type='text/javascript'>alert('3');</script>";
                }
                }
			if ($item[1]==1 && $isTemp['scadenza']==0)
			{
				$query="DELETE FROM clgpersonaggiooggetto WHERE nome ='".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num',$item[0])."' LIMIT 1";
	    		}
	    		elseif ($item[1]>1 && $isTemp['scadenza']==0)
	    		{
            			$query="UPDATE clgpersonaggiooggetto SET cariche = cariche -1 WHERE nome ='".$_SESSION['login']."' AND id_oggetto='".gdrcd_filter('num',$item[0])."' LIMIT 1";
						  

			}
   		 
		 	gdrcd_query($query);
			echo "<script type='text/javascript'>alert('arrivo qui');</script>";
            $messaggio .= "$login usa $nome e incrementa i suoi parametri";










    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$login', NOW(), 'C', '$messaggio')");
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
        //ora pensiamo alle cariche

      $totale = "SELECT * FROM clgpersonaggiooggetto WHERE id_oggetto = '$item' AND nome = '".$_SESSION['login']."'";
      $totale_res = gdrcd_query($totale);      
      $cariche = $totale_res['cariche'];
      $numero = $totale_res['numero'];
      
    if ($numero > 1 && $cariche > 1) {
    gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = cariche -1 WHERE nome ='".$_SESSION['login']."' AND id_oggetto= '$item'");
    } else if ($numero > 1 && $cariche == 1) {
    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero -1 WHERE nome ='".$_SESSION['login']."' AND id_oggetto= '$item'");
    } else if ($numero == 1 && $cariche > 1) {
    gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = cariche -1 WHERE nome ='".$_SESSION['login']."' AND id_oggetto= '$item'");
    } else if ($numero == 1 && $cariche == 1) {
    gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE nome ='".$_SESSION['login']."' AND id_oggetto='$item'");
    }
      
    
    } 
      echo "<script type='text/javascript'> document.location = 'main.php?dir=$luogo'; </script>";
      /*Redirigo alla pagina del gioco*/
       
            ?>