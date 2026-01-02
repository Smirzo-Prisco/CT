<?php
$testo = "A seguito di un combattimento in $nome_zona, è stato fatto partire un fato automatico riguardo l\'arrivo della polizia. Ti invitiamo a monitorare.";
//inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '$nome' || destinatario = '$nome') 
                        AND  (nomesender = 'Segnalazione' || destinatario = 'Segnalazione') 
                        AND msggrp.ctgroup = 'SEGNALAZIONE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SEGNALAZIONE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '$nome');");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','Segnalazione', '$testo', '$nome');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '$nome' || destinatario = '$nome') 
                       AND  (nomesender ='Segnalazione' || destinatario ='Segnalazione') 
                       AND msggrp.ctgroup = 'SEGNALAZIONE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET letto = '0' WHERE nome ='$nome' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','Segnalazione','$testo');");
         }//fine if