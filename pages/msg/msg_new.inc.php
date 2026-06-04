<link rel="stylesheet" href="../../themes/crystal/messaggi.css" type="text/css">
<link rel="stylesheet" href="../../themes/crystal/messages.css" type="text/css">
<link rel="stylesheet" href="/themes/crystal/msg_legacy.css" type="text/css">

<?php

include('../../header.inc.php');

/***************************************************************************/
/******           Form di composizione di un messaggio                ******/
/***************************************************************************/
// prevalorizzazione destinatario
$destinatari='';
if(isset($_REQUEST['pg'])===true) $destinatari = gdrcd_filter('get',$_REQUEST['pg']);	
if(isset($_REQUEST['destinatario'])===true) $destinatari = gdrcd_filter('get',$_REQUEST['destinatario']);
?> 
<div class="panels_box"><center><br><br><br><br><br><br>

        <form class="form_messaggi" action="msg_do.inc.php" method="post">

			<!-- Tipo --> <?php //Campo tabellato ENUM "ON','OFF'?>
			<div class='form_label'><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type']['title']); ?></div>
			<div class='form_field'>
				
                <input type="hidden" name="tpgroup" value="ON" />

			</div>
			<br>
			
			<!-- Mittente -->
			<?php
			// Utenze con ruolo da master o superiore ha la possibilità di inviare messaggi globali (con mittente diverso da se stesso)
			if($_SESSION['permessi']>= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level'])){
				$listSender=array();
				$listCustomSender = array();
				$listCustomSender = explode(",", htmlspecialchars($PARAMETERS['setting']['msg']['group']['sender']['dssender'], ENT_QUOTES));
					foreach($listCustomSender as $customSender) { 
						if(trim($customSender)) array_push($listSender,trim($customSender));
					}
				if(count($listSender)>0){
					?>
					<div class='form_label'>
						<?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['sender']); ?>
					</div>
					<div class="form_field">
						<select class="" name="mittente">
							<option selected value=<?php echo gdrcd_filter('in', $_SESSION['login']);?>>Te stesso (Uso personale)</option>
							<?php 
							foreach($listSender as $index => $customSender) { 
								?>
								<option value="<?php echo $index; ?>" >
									<?php echo trim(stripslashes($customSender)); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
					<?php 
				}
			}
			?>
			
			<!-- Destinatari -->
			<div class='form_label'>
				<?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['recipient']); ?>
			</div>
			<?php if($_SESSION['permessi']>= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level'])){ ?>
				<div class='form_field'>
					<select id="sel_ctgroup" name="ctgroup" required>
						<option selected value='USERGROUP'><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['options']['recipients']); ?></option>
						<option value='GLOBAL'><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['options']['all']); ?></option>
						<option value='ONLINE'><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['options']['online']); ?></option>
					</select>
				</div>
			<?php } else {  ?>
			    <input type="hidden" name="ctgroup" value="USERGROUP" />
			<?php }   ?>
			<div class='form_field'>
				<input  id="destinatari" type="text" name="destinatari" placeholder="<?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['info']) ." (max ".gdrcd_filter('num',$PARAMETERS['setting']['msg']['group']['maxrecipients'])." min ".gdrcd_filter('num',$PARAMETERS['setting']['msg']['group']['minrecipients']).")";?>" value="<?php echo $destinatari ?>" <?php if($_SESSION['permessi']< gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level'])) echo "required"; ?>/>
			</div>
			
			<!-- Disabilita possibilità di rispondere -->
			<?php if($_SESSION['permessi']>= gdrcd_filter('in', $PARAMETERS['setting']['msg']['group']['sender']['access_level'])){ ?>
				<div class='form_label'><?php echo 'Disabilita possibilità di replica <br><small>(messaggi solo in lettura)</small>'; ?></div>
				<div class='form_field'>
					<input type="checkbox" name="flreadonly" class="form_input" style="width: 50px;"/>
				</div>			 
			<?php }   ?>
			 
			 <!-- Nome gruppo -->
			
			<div class='form_field'>
				<input type="text" name="dsgruppo" placeholder="Nome del gruppo" />
			</div>	
			
			 <!-- Messaggio -->

			<div class='form_field'>
				<textarea placeholder="Inserire testo. I gruppi sono solo ON" class="textbox_new_msg" type="textbox" name="messaggio" id="messaggio" class="ed" required ></textarea>
			</div>
			
			
			<!-- Submit -->
			<div class='form_submit'>
				 <input type="hidden" name="op" value="new_msg" />
				 <input type="submit" value="<?php echo gdrcd_filter('out',$MESSAGE['interface']['forms']['submit']); ?>" />
			</div>
 
        </form><br><br>
        
        
<a href="../../main.php?page=messages_center&op=create" target="_top"><img src="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/imgs/sms/New_On.png"></a>
		
</div>

<script type="application/javascript">
$(function() {
    $('#sel_ctgroup').change(function(){
		if($('#sel_ctgroup').val() == 'USERGROUP') $('#destinatari').show(); 	
		else {
			$('#destinatari').hide();
			$('#destinatari').val("");
		}
	
    });
});
</script>


