<?php	
/*******************************************************************************/
/**************      Lista gruppi - visualizzazione base       *****************/
/*******************************************************************************/
// gestione var. get
$OFFSET = isset($_REQUEST['offset'])===true? gdrcd_filter('num', $_REQUEST['offset']) :0;
$TPGROUP = (isset($_GET['what']) === true && strtolower(gdrcd_filter('in', $_GET['what'])) =='on')? 'ON':'OFF';
$flGESTCUSTOM = isset($_GET['sender']) === true && gdrcd_filter('in', $_GET['sender']) == 'custom' && $_SESSION['permessi'] >= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level'])? true:false; // true se si è richiesta la visualizzazione del tab "mittenti speciali"

$RECORDPERPAGE = gdrcd_filter('num', $PARAMETERS['settings']['records_per_page']);
?>

<link rel="stylesheet" href="pages/msg/new_sms.css" TYPE="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<?php

if(empty($_SESSION['last_istant_message']) === true) {
    $_SESSION['last_istant_message'] = 0;
}

?>
<!-- INIZIO PAGINA SMS -->

<div class="page_body">

<table class="principale_sms">

<tr>
<th scope="col" style="width: 25%;" class="second_header">
<?php

$non_letti_categoria = gdrcd_query("SELECT msggrpuser.*, msggrp.* FROM msggrpuser 
                                    JOIN msggrp on msggrpuser.idgroup=msggrp.idgroup 
                                    WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."' 
                                    and msggrpuser.letto < 1;", 'result');        
if(gdrcd_query($non_letti_categoria, 'num_rows') == 0) {
gdrcd_query($non_letti_categoria, 'free'); ?>
		
<a href="main.php?page=messages_center&what=off"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOff_Spento.png"></a>
<a href="main.php?page=messages_center&what=on"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOn_Spento.png"></a>

<?php

    } else {



$countOFF = 0;
$countON = 0;

while($rowc = gdrcd_query($non_letti_categoria, 'fetch')){
				if($rowc['tpgroup']=='OFF'){
					$countOFF=$countOFF + 1;
				}
				elseif($rowc['tpgroup']=='ON'){
					$countON=$countON + 1;
				}
				
			}

if($countOFF > 0){
?>
<a href="main.php?page=messages_center&what=off"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOff_Acceso.gif"></a>
<?php		} else { ?>
<a href="main.php?page=messages_center&what=off"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOff_Spento.png"></a>
<?php		} 

if($countON > 0){
?>
<a href="main.php?page=messages_center&what=on"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOn_Acceso.gif"></a>
<?php		} else {
?>
<a href="main.php?page=messages_center&what=on"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOn_Spento.png"></a>
<?php		}

}
?>
</th>
<th rowspan="2" scope="col">
 <!-- BOX CENTRALE -->
            
            
              
            <div class="content">  
  <div class="opendoc">
      <iframe name="opendocframe" src ="" class="iframedoc" allowtransparency="true" frameborder="0"></iframe>
  </div>
</div>
            
</th>
</tr>
<tr>
<?php
//Estraggo tutti i messaggi

if($flGESTCUSTOM) $whereFilter = "msggrpuser.tpuser ='CUSTOM'";
		else $whereFilter = "msggrpuser.nome ='".gdrcd_filter('in', $_SESSION['login'])."' AND msg.dtsend>msggrpuser.dtdel AND msggrp.tpgroup='".$TPGROUP."' ";
		//Query
		$query = "SELECT msggrpuser.dtlastread, msggrpuser.flpin, msggrpuser.letto, msggrp.dsgroup, msggrp.flreadonly, msggrp.idgroup as idgroup, max(msg.idmsg) as maxidmsg, max(msg.dtsend) as maxdtsend, msggrp.tpgroup, msggrp.ctgroup, (SELECT GROUP_CONCAT(DISTINCT nome ORDER BY FIELD(tpuser, 'CUSTOM','USER', 'SYSTEM', 'DELETED') SEPARATOR ', ')  as memberList FROM msggrpuser WHERE msggrpuser.idgroup=msggrp.idgroup   and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()
        and msggrpuser.nome != '".gdrcd_filter('in', $_SESSION['login'])."') as memberList 
				FROM msggrp msggrp 
				LEFT JOIN msggrpuser ON msggrp.idgroup = msggrpuser.idgroup and msggrpuser.dtstart<=NOW() and msggrpuser.dtend > NOW()
				LEFT JOIN msg msg on msggrp.idgroup = msg.idgroup 
				WHERE ".$whereFilter." 
				group by msggrp.idgroup,msggrp.dsgroup order by flpin desc, maxdtsend desc";
                
                //Conteggio gruppi
		$result = gdrcd_query($query, 'result');
		$totaleresults = gdrcd_query($result, 'num_rows'); //numero risultati
		gdrcd_query($result, 'free');

		//Gruppi (della pagina richiesta)
		$result = gdrcd_query($query,'result');	
        
                
?>


            <td bgcolor="#0f111d">
           <div class="lista_mittenti">
             <?php if($totaleresults  > 0){ 
             
             
             while ($row = gdrcd_query($result, 'fetch')){
					// dati del gruppo
					$idgroup = gdrcd_filter('num', $row['idgroup']); 
					$tpgroup = gdrcd_filter('in', $row['tpgroup']);					
					$ctgroup = gdrcd_filter('in', $row['ctgroup']); 
					list($data_spedito, $ora_spedito) = explode(' ', $row['maxdtsend']); // data-ora ultimo messaggio

					// partecipanti
					if($ctgroup == 'GLOBAL') $partecipanti = 'Tutti';
					else if($ctgroup == 'ONLINE') $partecipanti = 'Tutti (online)';
					else if($ctgroup == 'SYSTEM') $partecipanti = $_SESSION['login']; 
					else if($ctgroup == 'CALENDARIO') $partecipanti = 'Calendario'; 
					else if($ctgroup == 'MODERAZIONE') $partecipanti = 'Moderazione'; 
					else if($ctgroup == 'SEGNALAZIONE') $partecipanti = 'Segnalazione'; 
					else if($ctgroup == 'ARCHIVIO') $partecipanti = 'Archivio'; 
					else if($ctgroup == 'SISTEMA') $partecipanti = 'Sistema'; 
                    else  $partecipanti = gdrcd_filter('in', $row['memberList']); 
					$partecipanti = strlen($partecipanti)>33 ? substr($partecipanti,0,30).'&#8230' : $partecipanti;

					// descrizione gruppo
					$dsgroup = gdrcd_filter('out', $row['dsgroup']);
					$dsgroup = strlen($dsgroup)>28 ? substr($dsgroup,0,25).'&#8230' : $dsgroup;
                    
                    ?>
             
             
             
             
             <div class='single_mittente'>
             <?php if($row['letto'] < 1) { ?>  
             <div class='image_box_new mittenti_box'>
             <?php } else { ?>
             <div class='image_box mittenti_box'>
             <?php } ?>
             <!-- logo -->
             
             <?php
             $img_group = gdrcd_query("SELECT * FROM msggrp WHERE idgroup = ".gdrcd_filter('num', $row['idgroup'])."");
             $avvy = gdrcd_query("SELECT msg.*, personaggio.* FROM msg LEFT JOIN personaggio ON msg.nomesender = personaggio.nome WHERE msg.idgroup = ".gdrcd_filter('num', $row['idgroup'])." ORDER BY dtsend ASC LIMIT 1");
             $avvy_dest = gdrcd_query("SELECT msg.*, personaggio.* FROM msg LEFT JOIN personaggio ON msg.destinatario = personaggio.nome WHERE msg.idgroup = ".gdrcd_filter('num', $row['idgroup'])." ORDER BY dtsend ASC");
             
             if ($img_group['ctgroup'] == 'USERGROUP') { ?>
             <img src="pages/msg/img/<?php echo $img_group['img_gruppo'] ?>">
             <?php } else {
             
             //img singola
             if ($avvy['nomesender'] == $_SESSION['login']) {
             ?>
             <img src="<?php echo $avvy_dest['url_img_chat'] ?>">
             <?php } elseif ($avvy['nomesender'] == 'Calendario') {
             ?>
             <img src="../pages/msg/img/calendario.png">
             <?php } elseif ($avvy['nomesender'] == 'Segnalazione') {
             ?>
             <img src="../pages/msg/img/segnalazione.png">
             <?php } elseif ($avvy['nomesender'] == 'Archivio') {
             ?>
             <img src="../pages/msg/img/archivio.png">
             <?php } elseif ($avvy['nomesender'] == 'Moderazione') {
             ?>
             <img src="../pages/msg/img/mod.png">
             <?php } elseif ($avvy['nomesender'] == 'Sistema') {
             ?>
             <img src="../pages/msg/img/system.png">
             <?php } elseif ($avvy['nomesender'] != $_SESSION['login']) { ?>
             <img src="<?php echo $avvy['url_img_chat'] ?>">
             <?php } 
             }?>
            </div>
            
            
            
            
<div class='erase'>
<?php if ($ctgroup == 'SINGLE') { ?>
<form action="pages/msg/msg_do.inc.php?op=group_del&group=<?php echo $idgroup; ?>" method="post" onsubmit="return confirm('<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['comfirmdel']) ?>');" target="opendocframe" style="opacity:0.5;">
<input type="hidden" name="op" value="group_del" />
<input type="hidden" name="group" value="<?php echo $idgroup; ?>" />
<input type="image" src="pages/msg/img/X_cancella_messaggio.png" value="submit" />
</form>
<?php } else if ($ctgroup == 'USERGROUP') { ?>
<form action="pages/msg/msg_do.inc.php?op=remove_groupmember" method="post" onsubmit="return confirm('<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['comfirmleaving']) ?>');" target="opendocframe">
<input type="hidden" name="op" value="remove_groupmember" />
<input type="hidden" name="membro" value="<?php echo !$flGESTCUSTOM ? gdrcd_filter('in', $_SESSION['login']):'CUSTOM'?>" />
<input type="hidden" name="group" value="<?php echo $idgroup; ?>" />
<input type="image" src="pages/msg/img/X_cancella_messaggio.png" value="submit" />
</form>
<?php } ?>           
</div>
            
            
            
            
            
            
             <div class='data_box mittenti_box'>
             <div class='name'>
             <?php if($flGESTCUSTOM){
            echo $tpgroup;
            }
            ?>
            <a target="opendocframe" href="pages/msg/msg_read_conversation.inc.php?op=read&group=<?php echo $idgroup; if($flGESTCUSTOM) echo "&sender=custom"; ?>">
								<?php if ($img_group['ctgroup'] == 'USERGROUP') {
                                echo $img_group['dsgroup'];
                                } else {
                                echo $partecipanti; 
                                } ?><br>
            <?php echo gdrcd_format_date($data_spedito).' '.gdrcd_filter('out', $MESSAGE['interface']['messages']['time']).' '.gdrcd_format_time($ora_spedito); ?>
            </a>
             </div>
             </div>
             </div>
             
             
             
             
             
             <?php } //while
                        }//total_result?>
             </div>

            
            
            <div class="lista_comandi"><br>
<a href="../pages/msg/msg_new_single.inc.php" target="opendocframe"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/New_Off.png"></a>
<a href="../pages/msg/msg_new.inc.php" target="opendocframe"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/New_On.png"></a>
            </div>
            <br></td>
            
            
            
            
           
            
            
            
            
            
            
            
            
            
            </tr>
            </table>

</div>
<br>
<center>
<form action="pages/msg/msg_do.inc.php?op=allRead" method="post" target="opendocframe">
<input type="hidden" name="op" value="allRead" />
<input type="hidden" name="group" value="<?php echo $idgroup; ?>" />
<input type="submit" value="Segna tutto come letto" style="width: 150px;"/>
</form>
</center>
<script type="text/javascript">
$(function() {
      $( 'ul.ul-top li' ).on( 'click', function() {
            $( this ).parent().find( 'li.active' ).removeClass( 'active' );
            $( this ).addClass( 'active' );
      });
});
</script>
