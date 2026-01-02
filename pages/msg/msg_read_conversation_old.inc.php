<link rel="stylesheet" href="new_sms.css" TYPE="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<?php 
/*****************************************************************/
/**  controlli preliminari
/** - controllo valorizzazione gruppo
/** - controlli permessi di lettura messaggi nel gruppo (utente loggato deve essere, ad oggi, membro del gruppo
*/
include('../../header.inc.php');

$errorMsg=''; 
if (isset($_REQUEST['group'])===false || (isset($_REQUEST['group'])===true && gdrcd_filter('num',$_REQUEST['group']) <= 0)) {	
	$errorMsg = gdrcd_filter('out', $MESSAGE['error']['can_t_load_frame']);	
	
} else {

	$IDGROUP = gdrcd_filter('num',$_REQUEST['group']);
	$flGESTCUSTOM = isset($_GET['sender']) === true && gdrcd_filter('in', $_GET['sender']) == 'custom' && $_SESSION['permessi'] >= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level'])? true:false;
	
	$whereFilter = "membri.nome ='".gdrcd_filter('in', $_SESSION['login'])."' ";
	if($flGESTCUSTOM) $whereFilter = "membri.tpuser ='CUSTOM' ";

	$query  = "SELECT *, (SELECT nomesender FROM msg 
				LEFT JOIN msggrpuser ON msggrpuser.idgroup= msg.idgroup and msggrpuser.nome= msg.nomesender
				WHERE msg.idgroup =".$IDGROUP." and msggrpuser.idgrpuser IS NOT NULL and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() 
				order by dtsend limit 1) as nomecreator 
				FROM msggrpuser as membri
				LEFT JOIN msggrp as gruppo ON gruppo.idgroup = membri.idgroup 
				WHERE ".$whereFilter." and gruppo.idgroup=".$IDGROUP." and membri.dtstart<= NOW() and membri.dtend >= NOW() limit 1; ";
	$result = gdrcd_query($query,'result');
	if (gdrcd_query($result, 'num_rows') == 0){
		$errorMsg = gdrcd_filter('out', $MESSAGE['error']['not_allowed']);	
		
	} else {
		$row = gdrcd_query($result, 'fetch');

		//dati del gruppo
		$idgrpuser = gdrcd_filter('num', $row['idgrpuser']);
		$dsgroup = gdrcd_filter('out', $row['dsgroup']);
		$tpgroup = gdrcd_filter('out', $row['tpgroup']);
		$ctgroup = gdrcd_filter('out', $row['ctgroup']);
		$nome = gdrcd_filter('out', $row['nome']);
		$dtlastread = $row['dtlastread'];
		$dtdel = $row['dtdel'];
		$nomecreator = gdrcd_filter('out', $row['nomecreator']); // membro che ha mandato il primo messaggio ed è ancora membro del gruppo 
		$flreadonly = $row['flreadonly'];
		if($flGESTCUSTOM){
			$listGlobalSender = explode(",", htmlspecialchars(stripslashes($PARAMETERS['setting']['msg']['group']['sender']['dssender']), ENT_QUOTES));
			$idMittente = array_search($nome , $listGlobalSender);
			$mittente = $nome; 			
		}		
		// partecipanti
		if($ctgroup == 'GLOBAL') $partecipanti= 'tutti';
		else if($ctgroup == 'ONLINE') $partecipanti= 'tutti (presenti online)';
		else if($ctgroup == 'SYSTEM') $partecipanti = ''; 
		else {
			$recordMsggrpuser = gdrcd_query("SELECT GROUP_CONCAT(nome ORDER BY FIELD(tpuser, 'CUSTOM','USER', 'SYSTEM', 'DELETED') SEPARATOR ' - ')as memberList FROM msggrpuser where idgroup=" . $IDGROUP . " and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()");	
			$partecipanti = gdrcd_filter('out', $recordMsggrpuser['memberList']);
		}
	}
	gdrcd_query($result, 'free');
}

if (!Empty($errorMsg)){
	echo '<div class="error">'.$errorMsg.'</div>';
	exit();
}




/*****************************************************************/
/******   VISUALIZZA:  msg di una conversazione/gruppo      *****/
/****************************************************************/
$RECORDPERPAGE = gdrcd_filter('num', $PARAMETERS['settings']['records_per_page']);
$OFFSET = isset($_REQUEST['offset'])===true? gdrcd_filter('num', $_REQUEST['offset']) :0;
$flshowconvstart = true;

// AGGIORNAMENTO data ultima lettura pm 
$query="UPDATE msggrpuser SET dtlastread = NOW() WHERE idgrpuser =".$idgrpuser;
gdrcd_query($query);

?>

<!-- Messeggi del gruppo -->
<div class="box_centrale">

	<!-- Reply -->   
	<?php 
	// se risposte abilitate o l'utente loggato è abilitato
	if($ctgroup!='SYSTEM' && ($flreadonly == 'N' || $flGESTCUSTOM || $_SESSION['permessi'] >= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level']))){ 
		
	} else echo '<br>'; ?>	

	<!-- Messaggi inviati nel gruppo  -->
		
     
		<?php
		
		    // per gestione spunta di lettura e tooltip (verifica solo membri pg (esclusi mittenti speciali))
			$listPgPartecipanti = array();
			$query = "SELECT msggrpuser.nome, personaggio.nome, dtlastread  FROM msggrpuser 
					LEFT JOIN personaggio on msggrpuser.nome=personaggio.nome 
					where idgroup =".$IDGROUP." and personaggio.nome IS NOT NULL and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() ;";
			$result = gdrcd_query($query, 'result');
			while($record = gdrcd_query($result , 'fetch')){
				$listPgPartecipanti[$record['nome']] = $record['dtlastread'];
			}
			gdrcd_query($result , 'free');
			

			//Determinazione pagina (paginazione)
			$pagebegin = (int) $OFFSET * $RECORDPERPAGE;
			$pageend = $RECORDPERPAGE;
			
			//Query: messaggi del gruppo
			$query = "SELECT msg.idgroup, nomesender, message, dtsend, msggrpuser.tpuser FROM msg LEFT JOIN msggrpuser ON msggrpuser.idgroup=msg.idgroup and msggrpuser.nome=msg.nomesender WHERE msg.idgroup =".$IDGROUP." ORDER BY dtsend desc";	
			//Conteggio messaggi
			$result = gdrcd_query($query, 'result');
			$totaleresults = gdrcd_query($result, 'num_rows'); //numero risultati
			gdrcd_query($result, 'free');

			//Messaggi (della pagina richiesta)
			$query .= " "." LIMIT ".$pagebegin.", ".$pageend;
			$result = gdrcd_query($query,'result');	


			$dtsend="";
			while ($row = gdrcd_query($result, 'fetch')){
				
				// per gestione stile mittente 
				if(gdrcd_filter('in',$row['nomesender']) == 'SYSTEM') $sender = "system";
				else if(!$flGESTCUSTOM && gdrcd_filter('in', $row['nomesender']) == gdrcd_filter('in', $_SESSION['login'])) $sender = "me";
				else if ($flGESTCUSTOM && gdrcd_filter('in', $row['tpuser']) == 'CUSTOM') $sender = "me";
				else $sender = "other";
				
				// data invio
				$dtsend = date('d/m/Y', strtotime($row['dtsend']))." ".date('H:i', strtotime($row['dtsend']));	

				// intramezzo presenza nuovi messaggi non letti
				if($flshowconvstart && $row['dtsend']<=$dtlastread){
					echo '<br><div class="msg_container alredyRead">già letti</div><br>';	
					$flshowconvstart=false;
				}
				?>	

				<!-- Messaggio	-->
				
					<?php 
					if($sender=="me"){
						// mittente  + data & testo
                    ?>
                    <table width="100%">
                    <tr>
                    <td width="20%">
                    <center><div class='image'><img src="https://i.ibb.co/h71gHz6/mini-lii.gif" style="width: 70px; height: 70px;"><br>
                    </div></center></td>
                    
                    <td><?php echo '<div class="spedito">'.gdrcd_bbcoder($row['message']).'</div>'; ?>
                    <br><div class='data'><?php echo $dtsend ?>

                    
                    
					<?php	
						// gestione spunte visualizzazione su singolo messaggio inviato da utente loggato
						$lblNONVisualizzatoDa = "";
						$lblVisualizzatoDa = "";
						$listaPgCheHannoLetto = array();
						$listaPgCheNONHannoLetto = array();
						foreach ($listPgPartecipanti as $nome => $dtlettura) {
							if(strtolower($nome) != strtolower($row['nomesender'])){
								if($dtlettura>= $row['dtsend']) $listaPgCheHannoLetto[] =  gdrcd_capital_letter($nome);
								else $listaPgCheNONHannoLetto[] =  gdrcd_capital_letter($nome);
							}										
						}
						
						$flAllReaded = false;
						if(count($listaPgCheHannoLetto) == 0 ) $lblVisualizzatoDa = "";
						else if( count($listaPgCheHannoLetto) == 1  && count($listPgPartecipanti) == 2) {
							$lblVisualizzatoDa = "Visualizzato";
							$flAllReaded = true;
						} else if(count($listaPgCheHannoLetto) == (count($listPgPartecipanti)-1)) {
							$lblVisualizzatoDa = "Visualizzato da tutti";
							$flAllReaded = true;
						} else {
							$lblVisualizzatoDa = "Visualizzato da";
							foreach ($listaPgCheHannoLetto as $nome) {
								$lblVisualizzatoDa .= " ".$nome . ",";
							}
							$lblVisualizzatoDa = substr($lblVisualizzatoDa, 0, -1);
							
							$lblNONVisualizzatoDa = "Non visualizzato da";
							foreach ($listaPgCheNONHannoLetto as $nome) {
								$lblNONVisualizzatoDa .= " ".$nome . ",";
							}
							$lblNONVisualizzatoDa = substr($lblNONVisualizzatoDa, 0, -1);
						}
						
						if(!empty($lblVisualizzatoDa)){
							$colorForAlReaded = $flAllReaded? 'color: #1E90FF;':'';  //spunta verde se hanno letto tutti
							echo '<span style="'. $colorForAlReaded .' font-size:10px; padding:10px; cursor: default;" title="'.$lblVisualizzatoDa .'&#10;'.$lblNONVisualizzatoDa.'">';
							echo '&check;';
							echo '</span>';
						}

						?> </div></td></tr></table> <?php
					} // messaggi del sistema
                    
					else if($sender=="system"){
						if($ctgroup=='SYSTEM'){
							// messaggio di sistema nel gruppo privato sistema-pg (questo gruppo raccoglie le notifiche del sistema al personaggio (es: cambio permessi, bonifico &....
							$msgGromSystem = "<b>SISTEMA</b><br><small>".$dtsend."</small><br><br>";
							$msgGromSystem .= gdrcd_bbcoder(gdrcd_filter('out',$row['message']));
							echo $msgGromSystem; 
						} else {
							//notifica generica del sistema nel gruppo (aggiunta/rimozione/abbandono di un membro
							echo '-&ensp;'. gdrcd_filter('out',$row['message']) .'&ensp;-'; 
						}
						
					} // messaggi degli altri
					else { ?>
                    <table width="100%">
                    <tr>                   
                    <td><?php echo '<div class="ricevuto">'.gdrcd_bbcoder($row['message']).'</div>'; ?><br>
                    <div class='data'><?php echo $dtsend ?></div>
                    </td>
                    
                    <td width="20%">
                    <center>
                    <div class='image_mitt'>
                    <img src="https://i.postimg.cc/tJxXh18h/Acamar-Mini-Avatar.png" style="width: 70px; height: 70px;">
                    </div>
                    </center>
                    </td>
                    </tr>
                    </table>
                    <?php
					}
					?>
                    
				<?php 
				
			}				
			gdrcd_query($result , 'free');
		?>
</div>
         <div class="box_scritto">
         </div>