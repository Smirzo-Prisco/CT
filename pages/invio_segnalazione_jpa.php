<?php
$testo = "*chiamata* è stata inviata una pattuglia in $nome_zona per la presenza di un possibile scontro magico notificatoci da un cittadino. Se disponibile, recati subito sul posto. *fine chiamata*";
//inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '$nome' || destinatario = '$nome') 
                        AND  (nomesender = 'Segnalazione' || destinatario = 'Segnalazione') 
                        AND msggrp.ctgroup = 'SEGNALAZIONE' AND tpgroup = 'ON'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','ON','SEGNALAZIONE','N', NOW());");  
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
                       AND msggrp.ctgroup = 'SEGNALAZIONE' AND tpgroup = 'ON'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET letto = '0' WHERE nome ='$nome' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','Segnalazione','$testo');");
         }//fine if