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
		$fladdusers =  gdrcd_filter('num', $row['fladdusers']);
		if($flGESTCUSTOM){
			$listGlobalSender = explode(",", htmlspecialchars(stripslashes($PARAMETERS['setting']['msg']['group']['sender']['dssender']), ENT_QUOTES));
			$idMittente = array_search($nome , $listGlobalSender);
			$mittente = $nome; 			
		}		
		// partecipanti
		if($ctgroup == 'GLOBAL') $partecipanti= 'tutti';
		else if($ctgroup == 'ONLINE') $partecipanti= 'tutti (presenti online)';
        else if($ctgroup == 'CALENDARIO') $partecipanti= 'Calendario';
        else if($ctgroup == 'ARCHIVIO') $partecipanti= 'Archivio';
        else if($ctgroup == 'SEGNALAZIONE') $partecipanti= 'Segnalazione';
        else if($ctgroup == 'MODERAZIONE') $partecipanti= 'Moderazione';
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
$query="UPDATE msggrpuser SET dtlastread = NOW(), letto = '1' WHERE nome = '".$_SESSION['login']."' AND idgrpuser =".$idgrpuser;
gdrcd_query($query);

/*invio mex*/
 if(isset($_POST['op']) === false) {
 
?>

<!-- Messeggi del gruppo -->
<div class="content_sms">
<div class="opendoc_sms">

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
                   $avvy = gdrcd_query("SELECT * FROM personaggio WHERE nome = '".$_SESSION['login']."'");
                   $avvy_altro = gdrcd_query("SELECT * FROM personaggio WHERE nome = '".gdrcd_filter('out', $row['nomesender'])."'");

                    if($sender=="me"){
						// mittente  + data & testo
                    ?>
                    <table width="100%">
                    <tr>
                    <td width="20%">
                    <center><div class='image'><img src="<?php echo $avvy['url_img_chat'] ?>" style="width: 70px; height: 70px;"><br>
                    </div></center></td>
                    <?php
                    if(strpos(gdrcd_filter('out', $row['message']), 'Clicca qui')){
                    ?>
                    <td><?php echo '<div class="spedito">'.nl2br(gdrcd_bbcoder(gdrcd_filter('out', $row['message']))).'</div>'; ?>
                    <?php } else { ?>
                    <td><?php echo '<div class="spedito">'.nl2br(htmlspecialchars($row['message'])).'</div>'; ?>
                    <?php } ?>
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
                        ?>
                        <p align="center" style="font-family:lato; color: #b4b6bf;"><i>
                        <?php
							//notifica generica del sistema nel gruppo (aggiunta/rimozione/abbandono di un membro
							echo '-&ensp;'. gdrcd_filter('out',$row['message']) .'&ensp;-'; 
						}
						?>
                        </i></p>
                        <?php
					} // messaggi degli altri
					else { ?>
                    <table width="100%">
                    <tr>
                    <?php
                    if(strpos(gdrcd_filter('out', $row['message']), 'Clicca qui')){
                    ?>
                    <td><?php echo '<div class="ricevuto">'.nl2br(gdrcd_bbcoder(gdrcd_filter('out', $row['message']))).'</div>'; ?><br>
                    <?php } else { ?>
                    <td><?php echo '<div class="ricevuto">'.nl2br(htmlspecialchars($row['message'])).'</div>'; ?><br>
                    <?php } ?>
                    <div class='data'><?php echo $dtsend ?> <?php if($ctgroup == 'USERGROUP') { 
                    echo "(<i>".$row['nomesender']."</i>)";
                    }
                    ?></div>
                    </td>
                    
                    <td width="20%">
                    <center>
                    <div class='image_mitt'>
                    <?php
                    if($ctgroup == 'SEGNALAZIONE'){
                    echo '<img src="../../pages/msg/img/segnalazione.png" style="width: 70px; height: 70px;">';
                    } elseif($ctgroup == 'CALENDARIO'){
                    echo '<img src="../../pages/msg/img/calendario.png" style="width: 70px; height: 70px;">';
                    } elseif($ctgroup == 'MODERAZIONE'){
                    echo '<img src="../../pages/msg/img/mod.png" style="width: 70px; height: 70px;">';
                    } else { ?>
                    <img src="<?php echo $avvy_altro['url_img_chat'] ?>" style="width: 70px; height: 70px;">
                    <?php } ?>                    </div>
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
</div>

<div class="content_testo">
<div class="opendoc_testo">

<?php if (($flreadonly == 'N') || ($flreadonly != 'N' && $_SESSION['admin'] == 1)) { ?>

<form class="form_messaggi" action="msg_read_conversation.inc.php" method="post">
<input type="hidden" name="op" value="reply_msg" />
<input type="hidden" name="group" value="<?php echo $IDGROUP; ?>" />

<?php if ($ctgroup == 'SINGLE') { ?>
<input type="hidden" name="ctgroup" value="SINGLE" />
<?php } else if ($ctgroup == 'USERGROUP') { ?>
<input type="hidden" name="ctgroup" value="USERGROUP" />
<?php } else if ($ctgroup == 'GLOBAL') { ?>
<input type="hidden" name="fladdusers" value="fladdusers" />
<?php } ?>

<textarea type="textbox" name="messaggio" required style="font-family: Lato; color: #b4b6bf; height: 70px; width: 400px; border: 1px solid black; border-radius: 4px; background-color: #111423; margin-left: 10px; margin-top: 10px;"></textarea>
<div class='image_invio'>
<input type="image" src="../../themes/crystal/imgs/sms/Invio_messaggio.png" border="0" alt="Submit" />
</div>
</form>

<?php if ($ctgroup == 'USERGROUP') { ?>
<div class='image_modifica'>
<a href="setting.inc.php?group=<?= $IDGROUP ?>" target="opendocframe"><img src="../../themes/crystal/imgs/sms/modifica_gruppo.png" border="0" /></a>
</div>
<?php } ?>

<?php } ?>
</div>
</div>

<?php } else { 

$MSG = $_POST['messaggio']; 

if(!$flGESTCUSTOM) $nomesender = $_SESSION['login']; 	
		else {
			$listGlobalSender = explode(",", htmlspecialchars($PARAMETERS['setting']['msg']['group']['sender']['dssender'], ENT_QUOTES));
			$nomesender = $listGlobalSender[(int)$FROM]; 	
		}		
		
		// inserimento messaggio di risposta
		$query="INSERT INTO msg (idgroup, nomesender, message) VALUES ('".$IDGROUP."','".$nomesender."', '".gdrcd_filter('in', $MSG)."');";
		$retmsg =gdrcd_query($query);
		// aggiornamento data ultim lettura
		$query="UPDATE msggrpuser SET dtlastread = NOW(), letto = '1' WHERE nome ='".$nomesender."' and idgroup=".$IDGROUP;
		$retmsgLastRead =gdrcd_query($query);		
        
        $query2="UPDATE msggrpuser SET letto = '0' WHERE nome != '".$nomesender."' and idgroup=".$IDGROUP;
		$retmsgLastRead2 =gdrcd_query($query2);	
        // aggiornamento data ultim risp
        $query2="UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup =".$IDGROUP;
        gdrcd_query($query2);
        
        // aggiunta dei membri mancanti
		if($fladdusers == 1){
			$query = "SELECT personaggio.nome FROM personaggio LEFT JOIN msggrpuser ON msggrpuser.nome = personaggio.nome and idgroup = ".$IDGROUP." and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() WHERE (esilio is null or esilio < CURDATE()) and msggrpuser.nome IS NULL ORDER BY personaggio.nome";
			$userMissList = gdrcd_query($query, 'result'); 	
			if(gdrcd_query($userMissList, 'num_rows') > 0 ){	
				while($userMiss = gdrcd_query($userMissList, 'fetch')) { 
					$query="INSERT INTO msggrpuser (idgroup, tpuser, nome, flpin) VALUES ('".$IDGROUP."','USER','".gdrcd_filter('in', $userMiss['nome'])."', '1');";
					gdrcd_query($query);
				}
			}
			gdrcd_query($userMissList, 'free');				
		}
        ?>


<?php } ?>

<div class="content_header">
<div class="opendoc_header">
<center><b><?php
             $nome_pg = $avvy['nome'];
             $cognome_pg = $avvy['cognome'];
             $dest_pg = $avvy_altro['nome'];
             
             if ($ctgroup == 'SINGLE') { 
             echo 'Conversazione tra: ';
             $conversazione = "SELECT * FROM msggrpuser WHERE idgroup = ".$IDGROUP." ORDER BY nome LIMIT 2";
             $conversazioneList = gdrcd_query($conversazione, 'result'); 
             while($chat = gdrcd_query($conversazioneList, 'fetch')) {
             echo $chat['nome'];
             }
             } else if ($ctgroup == 'USERGROUP') {
             echo $dsgroup;
             }
             
             
?></b>

<?php
if ($_SESSION['admin'] == 1 && $ctgroup == 'GLOBAL') {
?>
<input id="fladdusers" type="checkbox" name="fladdusers" class="form_input" style="width: 50px;" <?php echo $fladdusers==1?'checked':''; ?>/>
<?php } ?>                  

		<br>
</center>

<!-- Paginatore elenco -->
<div class="pager" style="clear:both; padding: 15px 0px;">
	<?php 
	if($totaleresults > $RECORDPERPAGE) {
    echo 'Vai a pagina: ';
    echo '<select onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);" style="border-radius: 10px; background-color: #181c31; color: #8f8f8f; font-family: "DejaVu Serif"; border: 2px solid #07080e;">';
    echo '<option value=""></option>';
            
            
		echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
		// limita il numero di pagine da poter visualizzare a 10
		$maxPage = ceil($totaleresults / $RECORDPERPAGE-1)<=gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxpagemsgtoview'])-1? ceil($totaleresults / $RECORDPERPAGE-1):gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxpagemsgtoview'])-1;
		for($i = 0; $i <= $maxPage; $i++) {
			if($i != $OFFSET) {
				?>
      <option value="../../pages/msg/msg_read_conversation.inc.php?op=read&group=<?php echo $IDGROUP; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>                
      <?php } else { ?>
      <option selected disabled="disabled" value="../../pages/msg/msg_read_conversation.inc.php?op=read&group=<?php echo $IDGROUP; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>
	  <?php
			}
		}
        echo '</select>';
	}
	?>
</div>
</div>
</div>

<script>
var modal = document.getElementById('id01');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function changeFrame(input_text) {
  document.getElementById("myframe").src = input_text;
}
</script>