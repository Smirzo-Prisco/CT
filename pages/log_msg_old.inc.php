<div class="pagina_msg_log">
    <?php
    if($_SESSION['admin'] < 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
		
    } 
	//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
	// POST	
	else if (isset($_POST['op'])){
		
		$msgKO = "";
		$IDMSG = (isset($_POST['idmsg'])===true && gdrcd_filter('num',$_POST['idmsg']) > 0)? gdrcd_filter('num',$_POST['idmsg']) : 0;
		$MSG = (isset($_POST['msg'])===true && !Empty(gdrcd_filter('in',$_POST['msg'])))? trim(htmlspecialchars(gdrcd_filter('in',$_POST['msg']), ENT_QUOTES)) : "";
		
		if($IDMSG==0)  $msgKO = "Uno o più dati obbligatori errati o mancanti";
		else if(gdrcd_filter('get', $_POST['op']) == 'erase'){
			//Elimina messaggio
			gdrcd_query("DELETE FROM msg WHERE idmsg=".$IDMSG);			
		} else if(gdrcd_filter('get', $_POST['op']) == 'modify'){
			//Modifica messaggio
			if(Empty($MSG))  $msgKO = "Uno o più dati obbligatori errati o mancanti";
			else {
                $MSG .= "\n\n\n&bsol;&bsol; Modificato da " . gdrcd_filter('in',$_SESSION['login']);
				gdrcd_query("UPDATE msg set message='".$MSG."' WHERE idmsg=".$IDMSG); 
			}				
		}

		// redirect 
		if(Empty($msgKO)) {
			echo '<div class="warning success">Opersazione eseguita con successo</div>';
			gdrcd_redirect("main.php?page=log_msg" ,0);
		} else echo '<div class="warning">'.$msgKO.'</div>';
		
	} 
	//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
	// GET
	else {
		// var. request
		$WHAT = (isset($_REQUEST['what'])===true && !Empty(gdrcd_filter('in',$_REQUEST['what'])))? trim(gdrcd_filter('in',$_REQUEST['what'])) : "";
		$DTSTART = (isset($_REQUEST['dtstart'])===true && !Empty(gdrcd_filter('in',$_REQUEST['dtstart'])))? gdrcd_filter('in',$_REQUEST['dtstart']) : "";
		$DTEND = (isset($_REQUEST['dtend'])===true && !Empty(gdrcd_filter('in',$_REQUEST['dtend'])))? gdrcd_filter('in',$_REQUEST['dtend']) : "";
		$TPGROUP = (isset($_REQUEST['tpgroup'])===true && gdrcd_filter('in',$_REQUEST['tpgroup'])=='ON')? "ON" : "OFF";
		$PG = (isset($_REQUEST['pg'])===true && !Empty(gdrcd_filter('in',$_REQUEST['pg'])) && $WHAT=='viewpg')? gdrcd_filter('in',$_REQUEST['pg']) : "";
		$IDGROUP = (isset($_REQUEST['group'])===true && gdrcd_filter('num',$_REQUEST['group']) > 0)? gdrcd_filter('num',$_REQUEST['group']) : 0;
		$IDMSG = (isset($_REQUEST['idmsg'])===true && gdrcd_filter('num',$_REQUEST['idmsg']) > 0)? gdrcd_filter('num',$_REQUEST['idmsg']) : 0;
		$OFFSET = isset($_REQUEST['offset'])===true? gdrcd_filter('num', $_REQUEST['offset']) :0;
		//
		$RECORDPERPAGE = gdrcd_filter('num', $PARAMETERS['settings']['records_per_page']);
		//composizione path per link paginatore/gestione pagine
		$pathForpager = "page=log_msg&what=".$WHAT."&tpgroup=".$TPGROUP."&dtstart=".$DTSTART."&dtend=".$DTEND."&pg=".$PG;
		
		
        ?>
		
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2>Log gruppi e messaggi</h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">
			<?php
				//----------------------------------------------------------
				// MODIFICA/MODERA MESSAGGIO	
				//----------------------------------------------------------	
				if($WHAT == 'modify'){
					$record = gdrcd_query("SELECT message FROM msg WHERE idmsg=" . $IDMSG);	
					?>
					
					<!-- Form di modifica -->
					<div class="panels_box">
						<form action="main.php?page=log_msg" method="post" class="form_gestione">
							 <!-- Messaggio -->
							<div class='form_label'>
								<?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['body']); ?>
							</div>
							<div class='form_field'>
								<textarea class="textbox_new_msg" type="textbox" name="msg" id="msg" class="ed" required ><?php echo $record['message'];?></textarea>
							</div>
							<small><?php echo gdrcd_filter('out', $MESSAGE['interface']['help']['bbcode']); ?></small>	
							<br><br>
							<!-- Submit -->
							<div class='form_submit'>
								 <input type="hidden" name="op" value="modify" />
								 <input type="hidden" name="idmsg" value="<?php echo $IDMSG;?>" />
								 <input type="submit" value="<?php echo gdrcd_filter('out',$MESSAGE['interface']['forms']['submit']); ?>" />
							</div>
						</form>
					</div>
					<!-- Ritorno -->
					<div class="link_back">
						<a href="main.php?page=log_msg"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['go_back']); ?></a>
					</div>
					
					<?php	
				}
				//----------------------------------------------------------
				// MESSAGGI DEL GRUPPO
				//----------------------------------------------------------	
				else if($WHAT == 'read'){
					?>
				
					<div class="msggroup_container">
						<div class="group">	
							<?php

								//Determinazione pagina (paginazione)
								$pagebegin = (int) $OFFSET * $RECORDPERPAGE;
								$pageend = $RECORDPERPAGE;
								
								//Query: messaggi del gruppo che soddisfano i filtri applicati
								$query = "SELECT msg.idmsg, msg.idgroup, msggrp.dsgroup, msggrp.ctgroup, nomesender, message, dtsend, msggrp.tpgroup, msggrpuser.tpuser, (SELECT GROUP_CONCAT(DISTINCT nome ORDER BY FIELD(tpuser, 'CUSTOM','USER', 'SYSTEM', 'DELETED') SEPARATOR ', ')  as memberList FROM msggrpuser WHERE msggrpuser.idgroup=msg.idgroup and msggrpuser.dtstart<='".date( "Y-m-d H:i:s", strtotime($DTEND.' '.date('H:i:s')))."' and msggrpuser.dtend >='".date( "Y-m-d H:i:s", strtotime($DTSTART.' '.date('H:i:s')))."') as memberList 
								FROM msg LEFT JOIN msggrpuser ON msggrpuser.idgroup=msg.idgroup and msggrpuser.nome=msg.nomesender LEFT JOIN msggrp ON msggrp.idgroup = msg.idgroup 
								WHERE msg.idgroup =".$IDGROUP." and msg.dtsend <='".date( "Y-m-d H:i:s", strtotime($DTEND.' '.date('H:i:s')))."'  and msg.dtsend >='".date( "Y-m-d H:i:s", strtotime($DTSTART.' '.date('H:i:s')))."' 
								ORDER BY dtsend desc";									
								
								//Conteggio messaggi
								$result = gdrcd_query($query, 'result');
								$totaleresults = gdrcd_query($result, 'num_rows'); //numero risultati
								gdrcd_query($result, 'free');

								//Messaggi (della pagina richiesta)
								$query .= " "." LIMIT ".$pagebegin.", ".$pageend;
								$result = gdrcd_query($query,'result');	

								$flShowTitle = true;
								if($totaleresults > 0) { 

									while ($row = gdrcd_query($result, 'fetch')){
										// Testata (nome gruppo+partecipanti) solo 1^ volta
										$memberList = gdrcd_filter('in', $row['ctgroup'])=='GLOBAL' || $row['ctgroup']=='ONLINE'? "tutti": gdrcd_filter('out', $row['memberList']);
										if($flShowTitle) echo '<div class="dsgroup"><b>'.gdrcd_filter('out', $row['dsgroup'])."</b><br>Con: ".$memberList.'</div><br>';
										$flShowTitle=false;
																	
										// per gestione stile mittente 
										if(gdrcd_filter('in',$row['nomesender']) == 'SYSTEM') $sender = "system";
										else if(gdrcd_filter('in', $row['nomesender']) == $PG) $sender = "me";
										else $sender = "other";
										
										// data invio
										$dtsend = date('d/m/Y', strtotime($row['dtsend']))." ".date('H:i', strtotime($row['dtsend']));	

										?>	

										<!-- Blocco Messaggio	-->
										<div class="msg_container <?php echo $sender; ?>">
											<?php 
											if($sender=="system") echo '-&ensp;'. gdrcd_filter('out',$row['message']) .'&ensp;-'; 
											else {
												$formatSenderName ='';
												if(gdrcd_filter('in',$row['tpuser']) == 'DELETED') $formatSenderName = '<b><del>'.strtoupper(substr(gdrcd_filter('out', $row['nomesender']), 0, -4)).'</del></b> <small>(pg cancellato)</small>';
												else if(gdrcd_filter('in',$row['tpuser']) == 'USER') $formatSenderName = '<b><a href="main.php?page=scheda&pg='.gdrcd_filter('in', $row['nomesender']).'">'.strtoupper(gdrcd_filter('out', $row['nomesender'])).'</a></b>';
												else if(gdrcd_filter('in',$row['tpuser']) == 'CUSTOM')$formatSenderName = '<b>'. strtoupper(gdrcd_filter('out', $row['nomesender'])).'</b> (mittente speciale)';
												else $formatSenderName = '<b>'. strtoupper(gdrcd_filter('out', $row['nomesender'])).'</b>';
												echo $formatSenderName ."<br><small>".$dtsend.'</small><br><br>';
												echo '<div class="msg">'.gdrcd_bbcoder($row['message']).'</div>';
												//opzioni di moderazione - solo per gruppi on
												if(gdrcd_filter('in',$row['tpgroup'])=='ON'){
													echo '<div style="height: 28px; padding-top: 5px; border-top: 1px solid #a0a0a0;margin-top: 30px;">';
													?>
													<form style="display: inline-block;" action="main.php?" method="get">
													    <input type="hidden" name="page" value="log_msg" />
														<input type="hidden" name="what" value="modify" />
														<input type="hidden" name="idmsg" value="<?php echo gdrcd_filter('in',$row['idmsg']); ?>" />
														<input type="submit" name="" value="&#9998;" title="Modifica" />
													</form>
													<form style="display: inline-block;" action="main.php?page=log_msg" method="post" onsubmit="return confirm('Stai eliminando il messaggio. \nConfermi?');">
														<input type="hidden" name="op" value="erase" />
														<input type="hidden" name="idmsg" value="<?php echo gdrcd_filter('in',$row['idmsg']); ?>" />
														<input type="submit" name="dummy" value="&#10005;" title="Elimina" />
													</form>
													<?php
													echo '</div>';
												}
											}
											?>
										</div>				
										<?php 
									}
									gdrcd_query($result , 'free');
					
								} else echo '<p>Nessun messaggio in questo gruppo per le date selezionate</p>';

							?>
						</div>
						<!-- Paginatore elenco -->
						<div class="pager" style="clear:both; padding: 15px 0px;">
							<?php 
							if($totaleresults > $RECORDPERPAGE) {
								echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
								for($i = 0; $i <= ceil($totaleresults / $RECORDPERPAGE-1); $i++) {
									if($i != $OFFSET) {
										?>
										<a href="main.php?<?php echo $pathForpager; ?>&group=<?php echo $IDGROUP; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
										<?php 
									} else {
										echo ' '.($i + 1).' ';
									}
								}
							}
							?>
						</div>
					</div>
					<!-- Ritorno -->
					<div class="link_back">
						<a href="main.php?page=log_msg"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['go_back']); ?></a>
					</div>
					
					<?php
				} 
				//----------------------------------------------------------
				// LISTA GRUPPI
				//----------------------------------------------------------
				else if(substr($WHAT, 0, 4)=='view'){					

					if($WHAT != 'viewpg' || ($WHAT== 'viewpg' && !Empty($PG))) {
						$selectFlds = $WHAT != 'viewgrp'? " msggrpuser.nome, " : " " ;
										
						//Determinazione pagina (paginazione)
						$pagebegin = (int) $OFFSET * $RECORDPERPAGE;
						$pageend = $RECORDPERPAGE;
						
						//Query: gruppi che soddisfano i filtri applicati
						$query = "SELECT DISTINCT max(msg.idmsg), msggrp.idgroup, ".$selectFlds ." msggrp.dsgroup, msggrp.ctgroup, msggrp.dtcreate, (SELECT GROUP_CONCAT(DISTINCT nome ORDER BY FIELD(tpuser, 'CUSTOM','USER', 'SYSTEM', 'DELETED') SEPARATOR ', ')  as memberList FROM msggrpuser WHERE msggrpuser.idgroup=msggrp.idgroup and msggrpuser.dtstart<='".date( "Y-m-d H:i:s", strtotime($DTEND.' '.date('H:i:s')))."' and msggrpuser.dtend >='".date( "Y-m-d H:i:s", strtotime($DTSTART.' '.date('H:i:s')))."') as memberList 
						 FROM msggrpuser msggrpuser 
						 LEFT JOIN msggrp msggrp ON msggrp.idgroup = msggrpuser.idgroup 
						 LEFT JOIN msg msg ON msggrp.idgroup = msg.idgroup 
						 WHERE msggrp.flreadonly='N' and msggrp.dtcreate<='".date( "Y-m-d H:i:s", strtotime($DTEND.' '.date('H:i:s')))."' ";
						$query .= $WHAT == 'viewsenderc'? " and msggrpuser.tpuser='CUSTOM' " : " " ;
						$query .= $WHAT == 'viewpg' && !Empty($PG) ? " "."and msggrpuser.nome ='". $PG ."' and msggrpuser.dtstart<='".date( "Y-m-d H:i:s", strtotime($DTEND.' '.date('H:i:s')))."' and msggrpuser.dtend >='".date( "Y-m-d H:i:s", strtotime($DTSTART.' '.date('H:i:s')))."' " : " ";
						$query .= " "."and msg.dtsend<='".date( "Y-m-d H:i:s", strtotime($DTEND.' '.date('H:i:s')))."' and msg.dtsend>='".date( "Y-m-d H:i:s", strtotime($DTSTART.' '.date('H:i:s')))."'  and msggrp.tpgroup='".$TPGROUP."' group by msggrp.idgroup order by msg.dtsend desc";

                        
						//Conteggio gruppi
						$result = gdrcd_query($query, 'result');
						$totaleresults = gdrcd_query($result, 'num_rows'); //numero risultati
						gdrcd_query($result, 'free');

						//Gruppi (della pagina richiesta)
						$query .= " "." LIMIT ".$pagebegin.", ".$pageend;
						$result = gdrcd_query($query,'result');	

						if($totaleresults > 0) { 
							?>
							<div class="elenco_record_gestione">
								<table>
									<!-- Intestazione tabella -->
									<tr>
										<th class="casella_titolo">
											<div class="titoli_elenco"># gruppo</div>
										</th>
										<?php
										if($WHAT == 'viewsenderc') {
											?>
											<th class="casella_titolo">
												<div class="titoli_elenco">Mittente</div>
											</th>
											<?php								
										}
										?>
										<th class="casella_titolo">
											<div class="titoli_elenco">Nome gruppo</div>
										</th>
										<th class="casella_titolo">
											<div class="titoli_elenco">Partecipanti</div>
										</th>
										<th class="casella_titolo">
											<div class="titoli_elenco">Data creazione</div>
										</th>
									</tr>
									<!-- Record -->
									<?php while($row = gdrcd_query($result, 'fetch')) { ?>
										<tr class="risultati_elenco_record_gestione">
											<td class="casella_elemento">
												<div class="elementi_elenco">
													<?php echo gdrcd_filter('out', $row['idgroup']); ?>
												</div>
											</td>
											<?php
											if($WHAT == 'viewsenderc') {
												?>
												<td class="casella_elemento">
													<div class="elementi_elenco">
														<?php echo gdrcd_filter('out', $row['nome']); ?>
													</div>
												</td>
												<?php								
											}
											?>
											<td class="casella_elemento">
												<div class="elementi_elenco">
													<a href="main.php?page=log_msg&what=read&pg=<?php echo $PG; ?>&dtstart=<?php echo $DTSTART; ?>&dtend=<?php echo $DTEND; ?>&group=<?php echo gdrcd_filter('num', $row['idgroup']); ?>">
													<?php echo gdrcd_filter('out', $row['dsgroup']); ?>
													</a>
												</div>
											</td>
											<td class="casella_elemento">
												<div class="elementi_elenco">
													<?php echo gdrcd_filter('in', $row['ctgroup'])=='GLOBAL' || $row['ctgroup']=='ONLINE'? "tutti": gdrcd_filter('out', $row['memberList']); ?>
												</div>
											</td>
											<td class="casella_elemento">
												<div class="elementi_elenco">
													<?php echo gdrcd_filter('out', $row['dtcreate']); ?>
												</div>
											</td>
										</tr>
									<?php }
									gdrcd_query($result, 'free');
									?>
								</table>
							</div>
							<?php 
							
						} else echo "<p>Nessun gruppo con messaggi per i filtri impostati</p>";

						?>
						<!-- Paginatore elenco -->
						<div class="pager">
							<?php if($totaleresults > $RECORDPERPAGE) {
								echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);

								for($i = 0; $i <= ceil($totaleresults / $RECORDPERPAGE-1); $i++) {
									if($i != $OFFSET) {
										?>
										<a href="main.php?<?php echo $pathForpager; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
										<?php 
									} else {
										echo ' '.($i + 1).' ';
									}
								} //for
							}//if
							?>
						</div>
						<br>
						<!-- Ritorno -->
						<div class="link_back">
							<a href="main.php?page=log_msg"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['go_back']); ?></a>
						</div>
						<?php 
					}

				} 
				//----------------------------------------------------------
				// FILTRI INIZIALI
				//----------------------------------------------------------
				else {
					?>
					<p>Sono esclusi i gruppi in cui il pg non può rispondere</p>
					<br>
					<form name="form_gest_msggrp" action="main.php" method="get">
						<input type="hidden" name="page" value="log_msg"/>	
						<div>
							<input type="radio" id="ON" name="tpgroup" value="ON" style="width: auto; min-width: 0;" <?php if(Empty($TPGROUP) || $TPGROUP == 'ON') {echo 'checked';} ?>>
							<label for="ON">ON</label>
							<input type="radio" id="OFF" name="tpgroup" value="OFF"  style="width: auto; min-width: 0;" <?php if($TPGROUP == 'OFF') {echo 'checked';} ?>>
							<label for="OFF">OFF</label>
						</div>
						<br>
						<label for="start">Messaggi Dal</label> 
						<input type="date" id="dtstart" name="dtstart" required value="<?php echo (isset($_REQUEST['dtstart']) === false)? date('Y-m-d',(strtotime ( '-30 day' , strtotime (date("Y-m-d"))))):gdrcd_filter('in', $_REQUEST['dtstart']);?>" style="width: auto; min-width: 0;" >
						&emsp;  
						<label for="start">Al</label>
						<input type="date" id="dtend" name="dtend" required value="<?php echo (isset($_REQUEST['dtend']) === false)? date("Y-m-d"):gdrcd_filter('in', $_REQUEST['dtend']);?>" style="width: auto; min-width: 0;">
						<br><br>
						<select id="sel_what" name="what" class="form_gestione_selectbox" required >
							<option value="" disabled <?php if(isset($_REQUEST['what']) === false) {echo 'SELECTED';} ?>>- Cosa vuoi consultare? -</option>	
							<option value="viewgrp" <?php if(gdrcd_filter('in', $_REQUEST['what']) == 'viewgrp') {echo 'SELECTED';} ?>>Gruppi</option>
							<option value="viewpg" <?php if(gdrcd_filter('in', $_REQUEST['what']) == 'viewpg') {echo 'SELECTED';} ?>>Gruppi di conversazione di uno specifico pg</option>
							<option value="viewsenderc" <?php if(gdrcd_filter('in', $_REQUEST['what']) == 'viewsenderc') {echo 'SELECTED';} ?>>Gruppi di conversazione con mittenti speciali</option>
						</select>
						<br><br>
						<select id="sel_pg" name="pg" class="form_gestione_selectbox" >
							<option value="" disabled <?php if(isset($_REQUEST['pg']) === false) {echo 'SELECTED';} ?>>- Seleziona il pg -</option>	
							<?php
								$query = "SELECT nome, cognome FROM personaggio WHERE permessi > -1 ORDER BY nome";
								$nomi = gdrcd_query($query, 'result'); ?>
								<?php 
								while($option = gdrcd_query($nomi, 'fetch')) { 
									?>
									<option value="<?php echo gdrcd_filter('in', $option['nome']); ?>" <?php if(gdrcd_filter('in', $_REQUEST['pg']) == gdrcd_filter('in', $option['nome'])) {echo 'SELECTED';} ?>>
									<?php echo gdrcd_filter('out', $option['nome'])." ".gdrcd_filter('out', $option['cognome']); ?>
									</option>
									<?php }//while
								gdrcd_query($nomi, 'free');
							?>
						</select>			
						<div class="form_submit">
							<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
						</div>
					</form>
					
					<?php
				}
			?>
			
        </div>
        <?php
    }
    ?>
</div><!-- pagina -->
<script type="application/javascript">
$(function() {
    $('#sel_pg').hide(); 
    $('#sel_what').change(function(){
		if($('#sel_what').val() == 'viewpg'){
			$('#sel_pg').show();
			$('#sel_pg').prop('required',true);	
		} else {
			$('#sel_pg').hide();
			$('#sel_pg').prop('required',false);	
			$('#sel_pg').val("");
		}	
    });
});
</script>