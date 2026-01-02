<?php

$query_globale = "SELECT nome FROM personaggio WHERE (esilio is null or esilio < CURDATE())";
$userMissList = gdrcd_query($query_globale, 'result'); 
while($userMiss = gdrcd_query($userMissList, 'fetch')) {
$testo_si = "Questo è il tuo archivio personale, dove potrai salvare tutti i messaggi/testi più importanti";

    $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','ARCHIVIO','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, flpin, letto) VALUES ('". $lastId."','USER','" . trim(gdrcd_capital_letter(gdrcd_filter('in', $userMiss['nome']))) . "', '1', '1');");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','Archivio', '$testo_si', '" . trim(gdrcd_capital_letter(gdrcd_filter('in', $userMiss['nome']))) . "');";	
				$retQuery = gdrcd_query($query);
                
          }
}