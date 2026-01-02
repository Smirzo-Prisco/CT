<link rel="stylesheet" href="new_sms.css" TYPE="text/css">
<link rel="stylesheet" href="../../themes/crystal/messaggi.css" TYPE="text/css">
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
			<!-- Modifica nome gruppo -->
			<fieldset style="border-color: #a0a0a0;border-style: solid;">
			<legend><b><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['dsgroupmod']); ?></b></legend>
				<br>
				<form class="form_messaggi" action="msg_do.inc.php" method="post">
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
            
            <!-- Modifica immagine gruppo -->
			<fieldset style="border-color: #a0a0a0;border-style: solid;">
			<legend><b>Modifica immagine gruppo</b></legend>
				<br>
				<form class="form_messaggi" action="msg_do.inc.php" method="post" enctype="multipart/form-data">
					<div class='form_field'>
						<input type="hidden" name="op" value="img_modify" />
						<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />
						<div class='form_field'>
							 <input type="file" name="img_gruppo" />
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
				<form class="form_messaggi" action="msg_do.inc.php" method="post">
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
					<form class="form_messaggi" action="msg_do.inc.php" method="post">
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
			} else {
            //abbandona il gruppo da solo
            ?>
            <br>
            <fieldset style="border-color: #a0a0a0;border-style: solid;">
					<legend><b><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['removemember']); ?></b></legend>
					<br>
					<form class="form_messaggi" action="msg_do.inc.php" method="post">
						<div class='form_field'>
							<input type="hidden" name="op" value="remove_memember" />
							<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />
							<input type="hidden" name="membro" value="<?php echo $_SESSION['login']; ?>" />
							
							<br>
							<div class='form_submit'>
								<input type="submit" value="Abbandona il gruppo" />
							</div>
						</div>
					</form>
					</fieldset>
            <?php
            
            }
				
			?>

		<br>

