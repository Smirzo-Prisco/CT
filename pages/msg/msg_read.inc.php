
<?php 
/*****************************************************************/
/**  controlli preliminari
/** - controllo valorizzazione gruppo
/** - controlli permessi di lettura messaggi nel gruppo (utente loggato deve essere, ad oggi, membro del gruppo
*/
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
<div class="msggroup_container">

	<!-- Titolo -->
	<div class="dsgroup"><?php echo $ctgroup=='SYSTEM'? '<b>MESSAGGI DI SISTEMA</b>' : '<b>'.strtoupper($dsgroup).'</b><br>Con: '.$partecipanti.''; ?> </div>
		
	<!-- Gestione gruppo -->  
	<?php 
	if ($ctgroup=='SYSTEM') echo "";
	//else if($_SESSION['permessi'] < $PARAMETERS['setting']['msg']['group']['sender']['access_level'] && $ctgroup=='ONLINE')  echo "";
	else if($_SESSION['permessi'] < $PARAMETERS['setting']['msg']['group']['sender']['access_level'] && $flreadonly == 'S')  echo "";
	else {	
		?> 	
		<details>
			<summary style="text-transform: lowercase; text-align: left; cursor: pointer;"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['gestgrp']); ?></summary>

			<br>
			<!-- Modifica nome gruppo -->
			<fieldset style="border-color: #a0a0a0;border-style: solid;">
			<legend><b><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['dsgroupmod']); ?></b></legend>
				<br>
				<form class="form_messaggi" action="main.php?page=messages_center" method="post">
					<div class='form_field'>
						<input type="hidden" name="op" value="dsgroup_modify" />
						<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />
						<div class='form_field'>
							<input type="text" name="dsgruppo" placeholder="Nuovo nome del gruppo" required />
						</div>	
						<div class='form_submit'>
							<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
						</div>
					</div>
				</form>
			</fieldset>
				
			<?php
			$query = "SELECT personaggio.nome FROM personaggio LEFT JOIN msggrpuser ON msggrpuser.nome = personaggio.nome and idgroup = ".$IDGROUP." and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() WHERE permessi > -1 and msggrpuser.nome IS NULL ORDER BY personaggio.nome";
			$nomi = gdrcd_query($query, 'result'); 				
			if(gdrcd_query($nomi, 'num_rows') > 0 ){
				?>
				<br>
				<!-- aggiungi membro -->
				<fieldset style="border-color: #a0a0a0;border-style: solid;">
				<legend><b><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['addmember']); ?></b></legend>
				<br>
				<form class="form_messaggi" action="main.php?page=messages_center" method="post">
					<div class='form_field'>
						<input type="hidden" name="op" value="add_groupmember" />
						<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />
						<?php if($flGESTCUSTOM){ ?> <input type="hidden" name="mittente" value="<?php echo $idMittente; ?>" /><?php }?>
						<select name="membro" class="form_gestione_selectbox">
							<option value="" disabled selected>Seleziona il pg da aggiungere </option>
							<?php while($option = gdrcd_query($nomi, 'fetch')) { ?>
								<option value="<?php echo  gdrcd_filter('in', $option['nome']); ?>">
								<?php echo gdrcd_filter('out', $option['nome'])." ".gdrcd_filter('out', $option['cognome']); ?>
								</option>
								<?php }
							?>
						</select>
						<br>
						<div class='form_submit'>
							<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
						</div>
					</div>
				</form>
				</fieldset>
				<?php 
			} else echo "<p><b>Nessun nuovo membro da aggiungere</b></p>"; 
			gdrcd_query($nomi, 'free');
			
			
			if($_SESSION['permessi'] >= $PARAMETERS['setting']['msg']['group']['sender']['access_level'] || $nomecreator == gdrcd_filter('in', $_SESSION['login'])){
				// se creatore o membro abilitato dello staff -> funz. per rimozione membro
				$query = "SELECT personaggio.nome, msggrpuser.nome as membro FROM personaggio LEFT JOIN msggrpuser ON msggrpuser.nome = personaggio.nome and idgroup =  ".$IDGROUP." and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() WHERE permessi > -1 and msggrpuser.nome IS NOT NULL and personaggio.nome<>'".gdrcd_filter('in', $_SESSION['login'])."'ORDER BY personaggio.nome";
				$nomi = gdrcd_query($query, 'result'); 				
				if(gdrcd_query($nomi, 'num_rows') > 0 ){
					?>
					<br>
					<!-- rimuovi membro -->
					<fieldset style="border-color: #a0a0a0;border-style: solid;">
					<legend><b><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['removemember']); ?></b></legend>
					<br>
					<form class="form_messaggi" action="main.php?page=messages_center" method="post">
						<div class='form_field'>
							<input type="hidden" name="op" value="remove_groupmember" />
							<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />
							<?php if($flGESTCUSTOM){ ?> <input type="hidden" name="mittente" value="<?php echo $idMittente; ?>" /><?php }?>
							<select name="membro" class="form_gestione_selectbox">
								<option value="" disabled selected>Seleziona il pg da rimuovere </option>
								<?php while($option = gdrcd_query($nomi, 'fetch')) { ?>
									<option value="<?php echo gdrcd_filter('in', $option['nome']); ?>">
									<?php echo gdrcd_filter('out', $option['nome'])." ".gdrcd_filter('out', $option['cognome']); ?>
									</option>
									<?php }//while
								?>
							</select>
							<br>
							<div class='form_submit'>
								<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
							</div>
						</div>
					</form>
					</fieldset>
					<?php 
				}
				gdrcd_query($nomi, 'free');	
			}
				
			?>
			
		</details>
		<br>
		<?php 
		
	} 
	?>

	<!-- Reply -->   
	<?php 
	// se risposte abilitate o l'utente loggato è abilitato
	if($ctgroup!='SYSTEM' && ($flreadonly == 'N' || $flGESTCUSTOM || $_SESSION['permessi'] >= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level']))){ 
		?>
		<details style="margin-bottom: 10px;" <?php if(gdrcd_filter_get($_GET['op'])=='reply') echo 'open'; ?>>
			<summary style="text-transform: uppercase; text-align: right; font-weight: 900; padding-top: 5px; padding-bottom:15px; cursor: pointer;"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['reply']); ?> <?php if($flGESTCUSTOM) echo "come '". $mittente ."'"; ?></summary>
			
			<p><?php if($flreadonly == 'S') echo "<b>! risposte utenti disabilitate su questa conversazione !</b><br> Rispondi solo in qualità di membro dello staff</b>"; ?></p>
			<form class="form_messaggi" action="main.php?page=messages_center" method="post">
				<input type="hidden" name="op" value="reply_msg" />
				<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />
				<?php if($flGESTCUSTOM){ ?> <input type="hidden" name="mittente" value="<?php echo $idMittente; ?>" /><?php }?>
				<div class='form_field' id="ckeditor">
					<textarea type="textbox" name="messaggio" required></textarea>
					<br><small><?php echo gdrcd_filter('out', $MESSAGE['interface']['help']['bbcode']); ?></small>
				</div>
				<br>
				<div class='form_submit'>
					<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
				</div>
				<br>
			</form>
		</details>
		<?php 
	} else echo '<br>'; ?>	

	<!-- Messaggi inviati nel gruppo  -->
	<div class="group">	
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
				<div class="msg_container <?php echo $ctgroup=='SYSTEM'? 'fromsystem':$sender; ?>">
					<?php 
					if($sender=="me"){
						// mittente  + data & testo
						echo '<b>'.strtoupper(gdrcd_filter('out',$row['nomesender']))."</b><br><small>".$dtsend.'</small><br><br>';
						echo '<div class="msg">'.gdrcd_bbcoder($row['message']).'</div>';
						
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
							$colorForAlReaded = $flAllReaded? 'color: green;':'';  //spunta verde se hanno letto tutti
							echo '<br><span style="'. $colorForAlReaded .' font-size:10px; padding:10px; cursor: default;" title="'.$lblVisualizzatoDa .'&#10;'.$lblNONVisualizzatoDa.'">';
							echo '&check;';
							echo '</span>';
						}

						
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
					else {
						$formatSenderName ='';
						if($row['tpuser'] == 'DELETED') $formatSenderName = '<b><del>'.strtoupper(substr(gdrcd_filter('out', $row['nomesender']), 0, -4)).'</del></b> <small>(pg cancellato)</small>';
						else if($row['tpuser'] == 'USER') $formatSenderName = '<b><a href="main.php?page=scheda&pg='.gdrcd_filter('in', $row['nomesender']).'">'.strtoupper(gdrcd_filter('out', $row['nomesender'])).'</a></b>';
						else $formatSenderName = '<b>'. strtoupper(gdrcd_filter('out', $row['nomesender'])).'</b>';
						echo $formatSenderName ."<br><small>".$dtsend.'</small><br><br>';
						echo '<div class="msg">'.gdrcd_bbcoder($row['message']).'</div>';
					}
					?>
				</div>				
				<?php 
				
			}				
			gdrcd_query($result , 'free');
		?>
	</div>

</div>
<!-- Paginatore elenco -->
<div class="pager" style="clear:both; padding: 15px 0px;">
	<?php 
	if($totaleresults > $RECORDPERPAGE) {
		echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
		// limita il numero di pagine da poter visualizzare a 10
		$maxPage = ceil($totaleresults / $RECORDPERPAGE-1)<=gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxpagemsgtoview'])-1? ceil($totaleresults / $RECORDPERPAGE-1):gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxpagemsgtoview'])-1;
		for($i = 0; $i <= $maxPage; $i++) {
			if($i != $OFFSET) {
				?>
				<a href="main.php?page=messages_center&op=read&group=<?php echo $IDGROUP; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
				<?php 
			} else {
				echo ' '.($i + 1).' ';
			}
		}
	}
	?>
</div>
<!-- Ritorno -->
<div class="link_back">
    <a href="main.php?page=messages_center&what=<?php echo strtolower($tpgroup);?><?php if($flGESTCUSTOM)echo "&sender=custom"; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['go_back']); ?></a>
</div>