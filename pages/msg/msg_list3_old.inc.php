<script src="core/js/libs/jquery/jquery-1.11.2.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $(document).on("click", "a.tile-content", function (e) {
            e.preventDefault();
            var link = $(this).attr("href");
            var iframe = $("iframe#content");
            iframe.attr("src", link);
            iFrameResize();
        });
    });

    function iFrameResize() {
        try {
            var iframe = document.getElementById("content");
            iframe.height = (iframe.contentWindow.document.body.scrollHeight) + "px";
        } catch (e) {

        }
    }

    try {
        var inIframe = (window.self !== window.top);
        if (!inIframe) {
            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
            link.rel    = 'stylesheet';
            link.type   = 'text/css';
            link.href   = '../../css/iframe.css';
            link.media  = 'all';
            head.appendChild(link);
        }
    } catch (e) {
    }
</script>

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
<?php

if(empty($_SESSION['last_istant_message']) === true) {
    $_SESSION['last_istant_message'] = 0;
}

?>
<!-- INIZIO PAGINA SMS -->

<div class="page_body">

<table class="principale_sms">

<tr>
<td style="width: 25%;" class="second_header">
<?php

$non_letti_categoria = gdrcd_query("SELECT msggrpuser.*, msggrp.* FROM msggrpuser JOIN msggrp on msggrpuser.idgroup=msggrp.idgroup WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."' and msggrpuser.dtlastread = '1800-01-01 00:00:00';", 'result');        
if(gdrcd_query($non_letti_categoria, 'num_rows') == 0) {
gdrcd_query($non_letti_categoria, 'free'); ?>
		
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOff_Spento.png">
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOn_Spento.png">
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioStaff_Spento.png">

<?php

    } else {



$countOFF = 0;
$countON = 0;
$countSTAFF = 0;

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
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOff_Acceso.png">
<?php		} else { ?>
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOff_Spento.png">
<?php		} 

if($countON > 0){
?>
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOn_Acceso.png">
<?php		} else {
?>
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioOn_Spento.png">
<?php		}

if($countSTAFF > 0){
?>
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioStaff_Acceso.png">
<?php		} else { ?>
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/MessaggioStaff_Spento.png">
<?php		}
}
?>
</td>
<td>Cerca</td>
</tr>
<?php
//Estraggo tutti i messaggi

if($flGESTCUSTOM) $whereFilter = "msggrpuser.tpuser ='CUSTOM'";
		else $whereFilter = "msggrpuser.nome ='".gdrcd_filter('in', $_SESSION['login'])."' AND msg.dtsend>msggrpuser.dtdel AND msggrp.tpgroup='".$TPGROUP."' ";
		//Query
		$query = "SELECT msggrpuser.dtlastread, msggrp.dsgroup, msggrp.flreadonly, msggrp.idgroup as idgroup, max(msg.idmsg) as maxidmsg, max(msg.dtsend) as maxdtsend, msggrp.tpgroup, msggrp.ctgroup, (SELECT GROUP_CONCAT(DISTINCT nome ORDER BY FIELD(tpuser, 'CUSTOM','USER', 'SYSTEM', 'DELETED') SEPARATOR ', ')  as memberList FROM msggrpuser WHERE msggrpuser.idgroup=msggrp.idgroup   and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()
        and msggrpuser.nome != '".gdrcd_filter('in', $_SESSION['login'])."') as memberList 
				FROM msggrp msggrp 
				LEFT JOIN msggrpuser ON msggrp.idgroup = msggrpuser.idgroup and msggrpuser.dtstart<=NOW() and msggrpuser.dtend > NOW()
				LEFT JOIN msg msg on msggrp.idgroup = msg.idgroup 
				WHERE ".$whereFilter." 
				group by msggrp.idgroup,msggrp.dsgroup order by maxdtsend desc";
                
                //Conteggio gruppi
		$result = gdrcd_query($query, 'result');
		$totaleresults = gdrcd_query($result, 'num_rows'); //numero risultati
		gdrcd_query($result, 'free');

		//Gruppi (della pagina richiesta)
		$result = gdrcd_query($query,'result');	
        
                
?>
<tr>

            <td>
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
					else  $partecipanti = gdrcd_filter('in', $row['memberList']); 
					$partecipanti = strlen($partecipanti)>33 ? substr($partecipanti,0,30).'&#8230' : $partecipanti;

					// descrizione gruppo
					$dsgroup = gdrcd_filter('out', $row['dsgroup']);
					$dsgroup = strlen($dsgroup)>28 ? substr($dsgroup,0,25).'&#8230' : $dsgroup;
                    
                    ?>
             
             
             
             
             <div class='single_mittente'>
             <div class='image_box mittenti_box'>
             <img src="https://i.ibb.co/h71gHz6/mini-lii.gif">
             </div>
             <div class='data_box mittenti_box'>
             <div class='name'>
             <?php if($flGESTCUSTOM){
            echo $tpgroup;
            }
            ?>
            <a href="main.php?page=messages_center&op=read&group=<?php echo $idgroup; if($flGESTCUSTOM) echo "&sender=custom"; ?>">
								<?php echo $partecipanti; ?></a><br>
            <?php echo gdrcd_format_date($data_spedito).' '.gdrcd_filter('out', $MESSAGE['interface']['messages']['time']).' '.gdrcd_format_time($ora_spedito); ?>
            
             </div>
             </div>
             </div>
             
             
             
             
             
             <?php } //while
                        }//total_result?>
             </div>

            
            
            <div class="lista_comandi"><br>
<a href="main.php?page=messages_center&op=create_single"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/New_Off.png"></a>
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/New_On.png">
<img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/New_Global.png">
            </div>
            </td>
            
            
            
            
            
            
            
              <td valign="top">
              
            <div class="box_centrale">
            
            <div class='testo_box'>
            <div class='image'><img src="https://i.ibb.co/h71gHz6/mini-lii.gif" style="width: 70px; height: 70px;"><br>
            <div class='data'>20/12/2022 9:53</div></div>
            <div class='spedito'>
si            </div>
            </div>
            
            <div style='clear:both'></div>
            
            <div class='testo_box'>
            <div class='image_mitt'><img src="https://i.ibb.co/h71gHz6/mini-lii.gif" style="width: 70px; height: 70px;"><br>
            <div class='data'>20/12/2022 9:53</div></div>
            <div class='ricevuto'>
            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?
            </div>       
            </div>
            
            <div style='clear:both'></div>
            
            <div class='testo_box'>
            <div class='image_mitt'><img src="https://i.ibb.co/h71gHz6/mini-lii.gif" style="width: 70px; height: 70px;"><br>
            <div class='data'>20/12/2022 9:53</div></div>
            <div class='ricevuto'>
            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?
            </div>       
            </div>
            
            
            </div>


            <div class="box_scritto">
            <textarea type="textbox" name="testo" id="txt"></textarea>
            <div class='image_invio'><img src="../themes/crystal/imgs/sms/Invio_messaggio.png"></div>
            </div>
            </td>
            
            
            
            
            
            
            
            
            
            </tr>
            </table>

</div>


