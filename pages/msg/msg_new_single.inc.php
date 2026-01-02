<link rel="stylesheet" href="../../themes/crystal/messaggi.css" type="text/css">
<link rel="stylesheet" href="../../themes/crystal/messages.css" type="text/css">
<link rel="stylesheet" href="new_sms.css" type="text/css">
<body style="background-color:transparent;">

<?php
include('../../header.inc.php');

/*invio mex*/
 if(isset($_POST['op']) === true) {

//valorizzo
$tipo = $_POST['tipo'];
$CTGROUP = $_POST['ctgroup'];
$MSG = gdrcd_filter('in', $_POST['messaggio']);

/*Check destinatario*/

$fase_1 = gdrcd_query("SELECT nome FROM personaggio WHERE nome ='" . gdrcd_filter('in',
$_POST['destinatari']) . "' LIMIT 1", 'result');



if (gdrcd_query($fase_1, 'num_rows') < 1)
                {
                    gdrcd_query($fase_1, 'free');
                    $ok = false;
                    echo '<div class="error">Il destinatario non esiste</div>';
                } else {
                
                //Se esiste, faccio controllo conversazioni
                //la conversazione NON esiste
                
$fase_2 = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
(nomesender ='" . gdrcd_filter('in',
$_SESSION['login']) . "' || destinatario ='" . gdrcd_filter('in',
$_SESSION['login']) . "') AND  (nomesender ='" . gdrcd_filter('in',
$_POST['destinatari']) . "' || destinatario ='" . gdrcd_filter('in',
$_POST['destinatari']) . "') AND msggrp.ctgroup = 'SINGLE' AND tpgroup = '". $tipo."'", 'result');

if (gdrcd_query($fase_2, 'num_rows') < 1) {
//creo conversazione
$retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','". $tipo."','". $CTGROUP."','N', NOW());");  
$lastId = gdrcd_query($retQuery, 'last_id'); // ultimo id inserito

if($retQuery){
				// usergroup - inserimento membri del gruppo
				gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread, letto) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW(), '1');");
				gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_POST['destinatari']) . "');");
	
				// messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '".$MSG."', '" . gdrcd_filter('in', $_POST['destinatari']) . "');";	
				$retQuery = gdrcd_query($query);
                
                echo '<div class="error">Conversazione avviata</div>';
			}

} else {

$fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
(nomesender ='" . gdrcd_filter('in',
$_SESSION['login']) . "' || destinatario ='" . gdrcd_filter('in',
$_SESSION['login']) . "') AND  (nomesender ='" . gdrcd_filter('in',
$_POST['destinatari']) . "' || destinatario ='" . gdrcd_filter('in',
$_POST['destinatari']) . "') AND msggrp.ctgroup = 'SINGLE' AND tpgroup = '". $tipo."'");

$id_gruppo = $fase_find['idgroup'];

gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE), letto = '1' WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
gdrcd_query("UPDATE msggrpuser SET letto = '0' WHERE nome ='".gdrcd_filter('in',$_POST['destinatari'])."' and idgroup='".$id_gruppo."'");
gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='".$id_gruppo."'");

				// messaggio
gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('". $id_gruppo."','" . gdrcd_filter('in', $_SESSION['login']) . "', '".$MSG."');");	


echo '<div class="error">Messaggio inviato</div>';

}

}
                
                        
 } else { ?>
 
 
<?php 


/***************************************************************************/
/******           Form di composizione di un messaggio                ******/
/***************************************************************************/
// prevalorizzazione destinatario
$destinatari='';
if(isset($_REQUEST['pg'])===true) $destinatari = gdrcd_filter('get',$_REQUEST['pg']);	
if(isset($_REQUEST['destinatario'])===true) $destinatari = gdrcd_filter('get',$_REQUEST['destinatario']);
?> 
<center>
<div class="panels_box" style="margin: 0 auto; display: block;"><br><br><br><br><br><br>

        <form class="form_messaggi" action="msg_new_single.inc.php" method="post">

                <!-- Destinatario -->
               <?php if($_SESSION['admin'] == 2){ ?>
				<div class='form_field'>
					<select id="sel_ctgroup" name="ctgroup" required>
						<option selected value='SINGLE'>===</option>
						<option value='GLOBAL'>Tutti gli utenti</option>
						<option value='ONLINE'>Tutti i presenti</option>
					</select>
				</div>
			<?php } else {  ?>
            <input type="hidden" name="ctgroup" value="SINGLE" />
			<?php }   ?>
            <div class='form_field'>
            <input  id="destinatari" type="text" name="destinatari" placeholder="Inserire destinatario" value="<?php echo $destinatari ?>"/>
            </div>
<div class='form_field'>
<select name="tipo">
        <option value="OFF"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type'][0]);//parlato?></option>
        <option value="ON"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type'][1]);//parlato?></option>
        </select>
        <br>

<!-- Nome gruppo -->

<input type="hidden" name="dsgruppo" value="Messaggio singolo" />


</div>

			 <!-- Messaggio -->
			<div class='form_field'>
				<textarea class="textbox_new_msg" type="textbox" name="messaggio" id="messaggio" placeholder="Inserire testo" class="ed" required ></textarea>
			</div>
            
            <!-- Submit -->
			<div class='form_submit'>
				 <input type="hidden" name="op" value="send_single" />
				 <input type="submit" value="<?php echo gdrcd_filter('out',$MESSAGE['interface']['forms']['submit']); ?>" />
			</div>

</form>
</div>
<?php } ?>

<script type="application/javascript">
$(function() {
    $('#sel_ctgroup').change(function(){
		if($('#sel_ctgroup').val() == 'SINGLE') $('#destinatari').show(); 	
		else {
			$('#destinatari').hide();
			$('#destinatari').val("");
		}
	
    });
});
</script>