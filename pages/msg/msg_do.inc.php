<link rel="stylesheet" href="../../themes/crystal/messaggi.css" type="text/css">
<link rel="stylesheet" href="../../themes/crystal/messages.css" type="text/css">
<link rel="stylesheet" href="/themes/crystal/msg_legacy.css" type="text/css">
<?php
include('../../header.inc.php');

$msgKO = ""; $pageToRedirect ="";
$flGESTCUSTOM = false; // true se si sta rispondendo come mittente custom
$OP = "";
$FROM = ""; $TO = ""; $DSGROUP = ""; $CTGROUP = ""; $TPGROUP =""; $MSG = ""; 
$IDGROUP = 0; $TPGROUP="";

//preparazione parametri
$OP = (isset($_POST['op'])===true && !Empty(gdrcd_filter('in',$_POST['op'])))? trim(gdrcd_filter('in',$_POST['op'])) : "";
$TPGROUP = (isset($_POST['tpgroup'])===true && gdrcd_filter('in',$_POST['tpgroup'])=='ON')? "ON" : "OFF";
$FROM = (isset($_POST['mittente'])===true)? trim(gdrcd_filter('in',$_POST['mittente'])) : gdrcd_filter('in',$_SESSION['login']);
$CTGROUP = (isset($_POST['ctgroup'])===true && !Empty(gdrcd_filter('in',$_POST['ctgroup'])))? trim(strip_tags(gdrcd_filter('in',$_POST['ctgroup']))) : "USERGROUP";
$TO = (isset($_POST['destinatari'])===true && !Empty(gdrcd_filter('in',$_POST['destinatari'])))? trim(strip_tags(gdrcd_filter('in',$_POST['destinatari']))) : "";
$DSGROUP = (isset($_POST['dsgruppo'])===true && !Empty(gdrcd_filter('in',$_POST['dsgruppo'])))? trim(htmlspecialchars(strip_tags(gdrcd_filter('in',$_POST['dsgruppo'])), ENT_QUOTES)) : "";
$MSG = (isset($_POST['messaggio'])===true && !Empty(gdrcd_filter('in',$_POST['messaggio'])))? trim(htmlspecialchars(gdrcd_filter('in',$_POST['messaggio']), ENT_QUOTES)) : "";
$IDGROUP = (isset($_POST['group'])===true && gdrcd_filter('num',$_POST['group']) > 0)? gdrcd_filter('num',$_POST['group']) : 0;
$TPGROUP = (isset($_POST['tpgroup'])===true && !Empty(gdrcd_filter('in',$_POST['tpgroup'])))? gdrcd_filter('in',$_POST['tpgroup']) : '';
$FLREADONLY = (isset($_POST['flreadonly']))=== true && $_SESSION['permessi'] >= gdrcd_filter('in',$PARAMETERS['setting']['msg']['group']['sender']['access_level'])? 'S': 'N';
$FLALLUSERS = (isset($_POST['fladdusers']))=== true && $_SESSION['permessi'] >= gdrcd_filter('in',$PARAMETERS['setting']['msg']['group']['sender']['access_level'])? 1: 0;
$MEMBER = (isset($_POST['membro'])===true && !Empty(gdrcd_filter('in',$_POST['membro'])))? trim(strip_tags(gdrcd_filter('in',$_POST['membro']))) : "";


// SICUREZZE: variabili di controllo per verifica che utente può effettuare le operazioni richieste (reply, segna come letto...
$flSecView=true; // true se l'utente è membro della conversazione - può quindi leggerne i messaggi inviati al suo interno
$flSecReply=true; // true se l'utente è membro della conversazione e il gruppo non è in only read
if($IDGROUP >0){	

    if(($MEMBER=='CUSTOM' || $FROM !=gdrcd_filter('in',$_SESSION['login'])) && $_SESSION['admin'] == 1){
		$whereFilter = "membri.tpuser ='CUSTOM' ";
		$flGESTCUSTOM = true;
	} else {
		$whereFilter = "membri.nome ='".gdrcd_filter('in',$_SESSION['login'])."' ";
		$flGESTCUSTOM = false;
	}

	//$query  = "SELECT *, idgrpuser FROM msggrpuser LEFT JOIN msggrp ON msggrp.idgroup = msggrpuser.idgroup 
	 //          WHERE msggrp.idgroup=".$IDGROUP." and ". $whereFilter." LIMIT 1";
	$query  = "SELECT *, (SELECT nomesender FROM msg 
				LEFT JOIN msggrpuser ON msggrpuser.idgroup= msg.idgroup and msggrpuser.nome= msg.nomesender
				WHERE msg.idgroup =".$IDGROUP." and msggrpuser.idgrpuser IS NOT NULL and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() 
				order by dtsend limit 1) as nomecreator 
				FROM msggrpuser as membri
				LEFT JOIN msggrp as gruppo ON gruppo.idgroup = membri.idgroup 
				WHERE ".$whereFilter." and gruppo.idgroup=".$IDGROUP." and membri.dtstart<= NOW() and membri.dtend >= NOW() limit 1; ";
	$result = gdrcd_query($query,'result');
	if (gdrcd_query($result, 'num_rows') <= 0) {

		$flSecReply = false;
		$flSecView = false;
	} else {
		$row = gdrcd_query($result, 'fetch');
		$tpgroup = gdrcd_filter('in',$row['tpgroup']);
		$ctgroup = gdrcd_filter('in',$row['ctgroup']);
		$idgrpuser = gdrcd_filter('num',$row['idgrpuser']);
		$nomecreator = gdrcd_filter('in', $row['nomecreator']); // membro che ha mandato il primo messaggio ed è ancora membro del gruppo 
		$flpin = gdrcd_filter('num',$row['flpin']);	
		$fladdusers = gdrcd_filter('num',$row['fladdusers'])==1? true:false;
        if($row['flreadonly']=='S' && $_SESSION['permessi'] < gdrcd_filter('in',$PARAMETERS['setting']['msg']['group']['sender']['access_level'])) $flSecReply = false;	 
		else $flSecReply = true;	
	}
	gdrcd_query($result, 'free');
	
}


if(isset($_POST['op']) === false || Empty($OP) ){
	$msgKO = gdrcd_filter('out', $MESSAGE['error']['unknown_operation']);
}
//------------------------------------------------------
// Nuovo messaggio 
else if($OP=="new_msg"){	

	// TIPO mittente (USER= nome pg, CUSTOM = mittente configurato)	
	if(strtolower($FROM) == strtolower(gdrcd_filter('in',$_SESSION['login'])) || $_SESSION['admin'] < 1 ){
		$tpuser = "USER";
		$nomesender = gdrcd_filter('in',$_SESSION['login']); 
		$flGESTCUSTOM = false;		
	} else {
		$flGESTCUSTOM = true;	
		$tpuser = "CUSTOM";
		$listGlobalSender = explode(",", htmlspecialchars($PARAMETERS['setting']['msg']['group']['sender']['dssender'], ENT_QUOTES));
		$nomesender = $listGlobalSender[(int)$FROM]; 	
	}
	
	// Oggetto/descrizione gruppo
	$dsgroup = $DSGROUP;
	if(Empty($dsgroup) && $tpuser == "USER" && $CTGROUP == "USERGROUP") $dsgroup = "Conversazione"." ". $TPGROUP ;
	else if (Empty($dsgroup) && $tpuser == "USER" && ($CTGROUP == "GLOBAL" || $CTGROUP == "ONLINE"))  $dsgroup = "Gruppo globale" ;
	else if(Empty($dsgroup) && $tpuser == "CUSTOM")  $dsgroup = "Mittente:"." ". $nomesender ;
	
	if($CTGROUP == "USERGROUP"){
		/*** messaggio standard a gruppo di utenti
		*/
		
		// Destinatari
		$arr_pgList = array();
		$query = "SELECT nome FROM personaggio where esilio is null or esilio < CURDATE() ;";
		$result = gdrcd_query($query, 'result');
		while($record = gdrcd_query($result , 'fetch')){
			$arr_pgList[] = strtolower(gdrcd_filter('in',$record['nome']));
		}
		gdrcd_query($result , 'free');	
		
		$arr_recipientsList = array();
		$recipients = array();	
		$recipients = explode(",", $TO);	
		foreach($recipients as $recipient) { 
		     $recipient=trim($recipient);
		     //destinatari consentiti: tutti gli altri e se stessi solo quando non si è già il mittente
		    $bRecipientAllowed = strtolower($recipient)!= strtolower($_SESSION['login']) || ($tpuser != "USER" && strtolower($recipient)==strtolower(gdrcd_filter('in',$_SESSION['login'])));
			if($recipient && $bRecipientAllowed && in_array(strtolower($recipient), $arr_pgList) && !(in_array(strtolower($recipient), $arr_recipientsList))){
				array_push($arr_recipientsList,trim(ucfirst($recipient)));
			}
		}	 
		
		// Controlli & inserimento
		if(Empty($MSG) || Empty($nomesender) || count($arr_recipientsList)<=1 ) $msgKO = "Uno o più dati obbligatori errati o mancanti (per fare un gruppo il minimo è 3 personaggi)";
		else if(count($arr_recipientsList) > gdrcd_filter('num',$PARAMETERS['setting']['msg']['group']['maxrecipients']))  $msgKO = "Impossibile procedere. Numero massimo destinatari superato (max: ".gdrcd_filter('num',$PARAMETERS['setting']['msg']['group']['maxrecipients']).")";
		else {
			// gruppo
			$retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('".$dsgroup."','". $TPGROUP."','". $CTGROUP."','". $FLREADONLY."', NOW());");  
			$lastId = gdrcd_query($retQuery, 'last_id'); // ultimo id inserito
			if($retQuery){
				// usergroup - inserimento membri del gruppo
				gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, letto) VALUES ('". $lastId."','".$tpuser."','".$nomesender."', '1');");
				foreach ($arr_recipientsList as $nome) {
					$recipient = trim($nome);
					if($recipient) gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER','".$recipient."');");
				}			
				// messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','".$nomesender."', '".$MSG."');";	
				$retQuery = gdrcd_query($query);
			}
			// AGGIORNAMENTO data ultima lettura pm 
			if($retQuery && $tpuser != "CUSTOM"){
				$query="UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE), letto = '1' WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup=".$lastId;
				gdrcd_query($query);
                $query2="UPDATE msggrpuser SET letto = '0' WHERE nome !='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup=".$lastId;
				gdrcd_query($query2);
			}
			
		}			
		
	} else {
		/*** messaggio globale o utenti online
		*/
		//setto caratteristiche
        
        if ($CTGROUP == 'GLOBAL') {
        $FLREADONLY_set = 'S';
        $FLALLUSERS_set = '1';
        $flpin_set = '1';
        } else if ($CTGROUP == 'ONLINE') {
        $FLREADONLY_set = 'S';
        $FLALLUSERS_set = '0';
        $flpin_set = '0';
        } else if ($CTGROUP == 'GILDA') {
        $FLREADONLY_set = 'S';
        $FLALLUSERS_set = '0';
        $flpin_set = '0';
        } else if ($CTGROUP == 'CAPOGILDA') {
        $FLREADONLY_set = 'S';
        $FLALLUSERS_set = '0';
        $flpin_set = '0';
        } else {
        $FLREADONLY_set = 'N';
        $FLALLUSERS_set = '0';
        $flpin_set = '0';
        }
		// Controlli & inserimento
		if(Empty($MSG) || Empty($nomesender)) $msgKO = "Uno o più dati obbligatori errati o mancanti";
		else if ($_SESSION['admin'] < 1) $msgKO = "Operazione non consentita";
		else {
			// gruppo
			$retQuery= gdrcd_query("INSERT INTO msggrp (dsgroup, tpgroup, ctgroup, flreadonly, fladdusers, dtlastreply) VALUES ('".$dsgroup."','". $TPGROUP ."','". $CTGROUP."','". $FLREADONLY_set."','". $FLALLUSERS_set."', NOW());"); 
			$lastId = gdrcd_query($retQuery, 'last_id'); // ultimo id inserito
			if($retQuery){
				// usergroup - inserimento membri del gruppo
				if($tpuser=='CUSTOM') gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, flpin, letto) VALUES ('". $lastId."','".$tpuser."','".$nomesender."','".$flpin_set."', '1');");
				$query = "SELECT nome FROM personaggio";
				if($CTGROUP == "ONLINE")  $query .= " " . "WHERE personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW()";
				$result = gdrcd_query($query, 'result');
				while($record = gdrcd_query($result , 'fetch')){
					$query="INSERT INTO msggrpuser (idgroup, tpuser, nome, flpin) VALUES ('".$lastId."','USER','".gdrcd_filter('in',$record['nome'])."', '".$flpin_set."');";
					gdrcd_query($query);
				}	
				gdrcd_query($result , 'free');	
				// messaggio
				$query="INSERT INTO msg (idgroup, nomesender, message) VALUES ('".$lastId."','".$nomesender."','".$MSG."');";
				gdrcd_query($query);
				
			}
			
		}
		
	} 	
	
	
	$pageToRedirect = "&op=read&group=".$lastId;	
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";
}
//------------------------------------------------------
// Rispondi al gruppo
else if($OP=="reply_msg"){

	if(Empty($MSG) || $IDGROUP<=0) $msgKO = "Uno o più dati obbligatori errati o mancanti";
	else if (!$flSecReply) $msgKO = "Operazione non consentita";
	else {
		
		// TIPO mittente (USER= nome pg, CUSTOM = mittente configurato)	
		if(!$flGESTCUSTOM) $nomesender = $_SESSION['login']; 	
		else {
			$listGlobalSender = explode(",", htmlspecialchars($PARAMETERS['setting']['msg']['group']['sender']['dssender'], ENT_QUOTES));
			$nomesender = $listGlobalSender[(int)$FROM]; 	
		}		
		
		// inserimento messaggio di risposta
		$query="INSERT INTO msg (idgroup, nomesender, message) VALUES ('".$IDGROUP."','".$nomesender."', '".$MSG."');";
		$retmsg =gdrcd_query($query);
		// aggiornamento data ultim lettura
		$query="UPDATE msggrpuser SET dtlastread = NOW(), letto = '1' WHERE nome ='".$nomesender."' and idgroup=".$IDGROUP;
		$retmsgLastRead =gdrcd_query($query);	
		$query2="UPDATE msggrpuser SET letto = '0' WHERE nome != '".$_SESSION['login']."' and idgroup=".$IDGROUP;
		$retmsgLastRead2 =gdrcd_query($query2);	
	}
	    // aggiunta dei membri mancanti
		if($fladdusers == 1){
			$query = "SELECT personaggio.nome FROM personaggio LEFT JOIN msggrpuser ON msggrpuser.nome = personaggio.nome and idgroup = ".$IDGROUP." and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() WHERE (esilio is null or esilio < CURDATE()) and msggrpuser.nome IS NULL ORDER BY personaggio.nome";
			$userMissList = gdrcd_query($query, 'result'); 	
			if(gdrcd_query($userMissList, 'num_rows') > 0 ){	
				while($userMiss = gdrcd_query($userMissList, 'fetch')) { 
					$query="INSERT INTO msggrpuser (idgroup, tpuser, nome, flpin) VALUES ('".$IDGROUP."','USER','".gdrcd_filter('in', $userMiss['nome'])."', '".$flpin_set."');";
					gdrcd_query($query);
				}
			}
			gdrcd_query($userMissList, 'free');				
		}
		
	
	$pageToRedirect = "&op=read&group=".$IDGROUP;	
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";
	
}
//------------------------------------------------------
// Modifica immagine 
else if( $OP=="img_modify" && $IDGROUP>0 ){
$immagine_gruppo = $_FILES['img_gruppo']['name'];
if($_FILES['img_gruppo']['name'] != '' || $_FILES['img_gruppo']['name'] == NULL) {
gdrcd_query("UPDATE msggrp SET img_gruppo = '".gdrcd_filter('in', $immagine_gruppo)."' WHERE idgroup = '".$IDGROUP."'");
upload_image();
}

// messaggio di sistema: log aggiunta membro
			$MSGsystem_img = "L\'immagine del gruppo è stata modificata";
			$query="INSERT INTO msg (idgroup, nomesender , message) VALUES "."(".$IDGROUP.",'SYSTEM', '".$MSGsystem_img."');";	
			$retQuery = gdrcd_query($query);
}
//------------------------------------------------------
// Eliminazione logica singolo gruppo 
else if( $OP=="group_del" && $IDGROUP>0 ){
	
	if($IDGROUP<=0) $msgKO = "Uno o più dati obbligatori errati o mancanti";
    else if (!$flSecView) $msgKO = "Operazione non consentita";
	else {		
		$query="UPDATE msggrpuser SET dtdel= NOW(), dtlastread= NOW() WHERE idgroup=".$IDGROUP." and nome='".gdrcd_filter('in',$_SESSION['login'])."' and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()";
		$retmsg =gdrcd_query($query);	
	}
	
	$pageToRedirect = "&what=".strtolower($tpgroup);
}

//------------------------------------------------------
// Segna tutti come letti NUOVO
else if( $OP=="allRead" ){
$query="UPDATE msggrpuser SET dtlastread= NOW(), letto = '1' WHERE nome='".gdrcd_filter('in',$_SESSION['login'])."'";
$retmsg =gdrcd_query($query);
$pageToRedirect = "&what=".strtolower($TPGROUP);
}
// Segna tutti come letti VECCHIO
else if( $OP=="group_allRead" ){


	
	if(!Empty($TPGROUP)) $query="UPDATE msggrpuser LEFT JOIN msggrp on msggrpuser.idgroup= msggrp.idgroup SET dtlastread= NOW(), letto = '1' WHERE msggrp.tpgroup='".$TPGROUP."' and msggrpuser.nome='".gdrcd_filter('in',$_SESSION['login'])."' and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()"; 		
	else $query="UPDATE msggrpuser SET dtlastread= NOW(), letto = '1' WHERE nome='".gdrcd_filter('in',$_SESSION['login'])."' and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()";

	$retmsg =gdrcd_query($query);
	
	$pageToRedirect = "&what=".strtolower($TPGROUP);

}
//------------------------------------------------------
// Aggiungi membro al gruppo
else if( $OP=="add_groupmember" ){

	if($IDGROUP<=0 || Empty($MEMBER)) $msgKO = "Uno o più dati obbligatori errati o mancanti";
	else if($ctgroup == 'SYSTEM')  $msgKO = "Operazione non consentita";
	else if (!$flSecView) $msgKO = "Operazione non consentita";
	else {
		//verifica esistenza nome pg da aggiungere + verifica che non sia ancora membro
		$query = "SELECT personaggio.nome as nome, msggrpuser.nome as membro FROM personaggio LEFT JOIN msggrpuser ON msggrpuser.nome = personaggio.nome and msggrpuser.idgroup =  ".$IDGROUP."  and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() WHERE permessi > -1 and personaggio.nome= '".$MEMBER."' and msggrpuser.nome IS NULL";
		$record = gdrcd_query($query);
		$notMember = strtolower(gdrcd_filter('in', $record['nome'])) == strtolower($MEMBER)? true:false;
		if($notMember){
			//il pg selezionato non è ancora membro del gruppo -> viene aggiunto
			$query="INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('".$IDGROUP."','USER','".$MEMBER."');";
			$retmsg =gdrcd_query($query);
			
			// messaggio di sistema: log aggiunta membro
			$MSGsystem = $MEMBER." è stato aggiunto alla conversazione";
			$query="INSERT INTO msg (idgroup, nomesender , message) VALUES "."(".$IDGROUP.",'SYSTEM', '".$MSGsystem."');";	
			$retQuery = gdrcd_query($query);
			
		} else  $msgKO = "L'utente è già membro del gruppo";
		
	}

	$pageToRedirect = "&op=read&group=".$IDGROUP;	
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";

}
//------------------------------------------------------
// Abbandona gruppo
else if($OP=="group_leave" ){

	if($IDGROUP<=0 || Empty($MEMBER)) $msgKO = "Uno o più dati obbligatori errati o mancanti";
	else if($ctgroup == 'SYSTEM')  $msgKO = "Operazione non consentita";
	else if (!$flSecView) $msgKO = "Operazione non consentita";
	else {
		
		if($MEMBER=='CUSTOM'){
			$record = gdrcd_query("SELECT nome FROM msggrpuser WHERE tpuser='CUSTOM' and idgroup='".$IDGROUP."'");	
			$nomeMembro = $record['nome'];
		} else $nomeMembro = $MEMBER;			
		// messaggio di sistema: log abbandono
		$MSGsystem = $nomeMembro." ha abbandonato la conversazione";
		$query="INSERT INTO msg (idgroup, nomesender , message, dtsend) VALUES "."(".$IDGROUP.",'SYSTEM', '".$MSGsystem."', DATE_ADD(NOW(), INTERVAL -5 SECOND));";	
		$retQuery = gdrcd_query($query);
		
		// abbandono
		$query = "UPDATE msggrpuser set dtend = NOW(), dtlastread= NOW() WHERE idgrpuser=".$idgrpuser.";";
		$retmsg = gdrcd_query($query);
		

	}

	$pageToRedirect = "&what=".strtolower($tpgroup);
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";

}
//------------------------------------------------------
// Rimuovi da gruppo
// Per figure abilitare da configurazione + creatore = membro del gruppo, tra quelle ad ora appartenenti al gruppo, che per primo ha inviato un messaggio nel gruppo
else if( $OP=="remove_groupmember" ){

	if($IDGROUP<=0 || Empty($MEMBER)) $msgKO = "Uno o più dati obbligatori errati o mancanti";
	else if (!$flSecView) $msgKO = "Operazione non consentita";
	else if($ctgroup == 'SYSTEM')  $msgKO = "Operazione non consentita";
	else if($nomecreator!=gdrcd_filter('in',$_SESSION['login']) && $_SESSION['permessi'] < gdrcd_filter('in',$PARAMETERS['setting']['msg']['group']['sender']['access_level'])) $msgKO = "Operazione non consentita";
	else {

		// messaggio di sistema: log abbandono
		$MSGsystem = $MEMBER." è stato rimosso dalla conversazione";
		$query="INSERT INTO msg (idgroup, nomesender , message) VALUES "."(".$IDGROUP.",'SYSTEM', '".$MSGsystem."');";	
		$retQuery = gdrcd_query($query);
	
		$query = "DELETE FROM msggrpuser WHERE nome='".$MEMBER."' and idgroup=".$IDGROUP."";
		$retmsg = gdrcd_query($query);
			
	}

	$pageToRedirect = "&op=read&group=".$IDGROUP;
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";

}
//autorimozione gruppo

else if( $OP=="remove_memember" ){

	if($IDGROUP<=0 || Empty($MEMBER)) $msgKO = "Uno o più dati obbligatori errati o mancanti";
	else if (!$flSecView) $msgKO = "Operazione non consentita";
	else if($ctgroup == 'SYSTEM')  $msgKO = "Operazione non consentita";
	else {

		// messaggio di sistema: log abbandono
		$MSGsystem = $MEMBER." ha abbandonato la conversazione";
		$query="INSERT INTO msg (idgroup, nomesender , message) VALUES "."(".$IDGROUP.",'SYSTEM', '".$MSGsystem."');";	
		$retQuery = gdrcd_query($query);
	
		$query = "DELETE FROM msggrpuser WHERE nome='".$MEMBER."' and idgroup=".$IDGROUP."";
		$retmsg = gdrcd_query($query);
			
	}

	$pageToRedirect = "&op=read&group=".$IDGROUP;
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";

}
//------------------------------------------------------
// Modifica nome gruppo
else if( $OP=="dsgroup_modify" ){

	if($IDGROUP<=0 || Empty($DSGROUP)) $msgKO = "Uno o più dati obbligatori errati o mancanti";
	else if (!$flSecView || !$flSecReply) $msgKO = "Operazione non consentita";
	else if($ctgroup == 'SYSTEM')  $msgKO = "Operazione non consentita";
	else {

		// messaggio di sistema: log abbandono
		$MSGsystem = "Il nome del gruppo è stato cambiato";
		$query="INSERT INTO msg (idgroup, nomesender , message) VALUES "."(".$IDGROUP.",'SYSTEM', '".$MSGsystem."');";	
		$retQuery = gdrcd_query($query);
		
		$query = "UPDATE msggrp set dsgroup = '".$DSGROUP."' WHERE idgroup=".$IDGROUP.";";
		$retmsg = gdrcd_query($query);
		
	}

	$pageToRedirect = "&op=read&group=".$IDGROUP;
	if($flGESTCUSTOM) $pageToRedirect .="&sender=custom";



} else {
	$msgKO = gdrcd_filter('out', $MESSAGE['error']['unknown_operation']);
}

$_POST = array();

// redirect 
if(Empty($msgKO)) {
	echo '<div class="warning success">Opersazione eseguita con successo<br>
                                       <a href="../../main.php?page=messages_center&offset=0"  target="_top"><b>Torna indietro</b></a>
                                       </div>';
   } else echo '<div class="warning">'.$msgKO.'</div>';

?>

<?php

//FILES

    function upload_image(){
$allow = array("jpg", "jpeg", "gif", "png");

$todir = "img/";
$test = $_FILES['img_gruppo']['name'];

if ( !!$_FILES['img_gruppo']['tmp_name'] ) // is the file uploaded yet?
{
    $info = explode('.', strtolower( $_FILES['img_gruppo']['name']) ); // whats the extension of the file

    if ( in_array( end($info), $allow) ) // is this file allowed
    {
        if ( move_uploaded_file( $_FILES['img_gruppo']['tmp_name'], $todir . basename($_FILES['img_gruppo']['name'] ) ) )
        {
            // the file has been moved correctly
        }
    }
    else
    {
        // error this file ext is not allowed
    }
}
}

?>