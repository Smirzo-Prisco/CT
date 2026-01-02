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
<div class="elenco_record_gioco">

	<!-- link nuovo messaggio -->
	<div class="link_back">
		<a href="main.php?page=messages_center&op=create">
			<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['new']); ?>
		</a>
	</div>
	<br>
	
	<!-- tipo gruppi da visualizzare -->
    <div class="link_back">
        <?php
            echo $TPGROUP!='ON'  && !$flGESTCUSTOM? '[<b>OFF</b>]':'[<a href="main.php?page=messages_center">OFF</a>]';
			echo ' - ';
			echo $TPGROUP=='ON' && !$flGESTCUSTOM ? '[<b>ON</b>]':'[<a href="main.php?page=messages_center&what=on">ON</a>]';
			if($_SESSION['permessi'] >= $PARAMETERS['setting']['msg']['group']['sender']['access_level'] && !Empty($PARAMETERS['setting']['msg']['group']['sender']['dssender'])){
				echo ' - ';
				echo $flGESTCUSTOM ? '[<b>Mittenti speciali</b>]':'[<a href="main.php?page=messages_center&sender=custom">Mittenti speciali</a>]';	
			}
        ?>
        <br>&nbsp
    </div>
	
	<!-- lista gruppi -->
	<?php
			
		//Determinazione pagina (paginazione)
		$pagebegin = (int) $OFFSET * $RECORDPERPAGE;
		$pageend = $RECORDPERPAGE;
		
		// gestione where condition per etrarre gruppi on/off e gruppi con mittenti custom			
		if($flGESTCUSTOM) $whereFilter = "msggrpuser.tpuser ='CUSTOM'";
		else $whereFilter = "msggrpuser.nome ='".gdrcd_filter('in', $_SESSION['login'])."' AND msg.dtsend>msggrpuser.dtdel AND msggrp.tpgroup='".$TPGROUP."' ";
		//Query
		$query = "SELECT msggrpuser.dtlastread, msggrp.dsgroup, msggrp.flreadonly, msggrp.idgroup as idgroup, max(msg.idmsg) as maxidmsg, max(msg.dtsend) as maxdtsend, msggrp.tpgroup, msggrp.ctgroup, (SELECT GROUP_CONCAT(DISTINCT nome ORDER BY FIELD(tpuser, 'CUSTOM','USER', 'SYSTEM', 'DELETED') SEPARATOR ', ')  as memberList FROM msggrpuser WHERE msggrpuser.idgroup=msggrp.idgroup   and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW()) as memberList 
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
		$query .= " "." LIMIT ".$pagebegin.", ".$pageend;
		$result = gdrcd_query($query,'result');	

		if($totaleresults  >0){
			?>
			<table>
				<tr>
					<td>
						<!-- Icona -->
					</td>
					<?php if($flGESTCUSTOM){ ?>
					<td>
						<!-- tipo -->
					</td>
					<?php } ?>
					<td>
						<span class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['name']); ?></span>
					</td>
					<td>
						<span class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['with']); ?></span>
					</td>
					<td>
						<span class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['last']); ?></span>
					</td>
					<td>
						<!-- Azioni -->
					</td>
				</tr>
				<?php

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
					
					<tr>
						<td>
							<div class="elementi_elenco">
								<?php
								if($row['maxdtsend'] >  $row['dtlastread']) echo '<img src="imgs/icons/mail_new.png" class="colonna_elenco_messaggi_icon">';
								else echo '<img src="imgs/icons/mail_read.png" class="colonna_elenco_messaggi_icon">';
								?>
							</div>
						</td>
						<?php if($flGESTCUSTOM){ ?>
						<td>
							<div class="elementi_elenco" title="<?php echo $tpgroup; ?>">
								<br><?php echo $tpgroup; ?>
							</div> 
						</td>
						<?php } ?>
						<td>
							<div class="elementi_elenco" title="<?php echo $dsgroup; ?>">
								<br>
								<a href="main.php?page=messages_center&op=read&group=<?php echo $idgroup; ?><?php if($flGESTCUSTOM) echo "&sender=custom"; ?>">
								<?php echo  $dsgroup; ?>
								</a>
							</div>  
						</td>
						<td>
							<div class="elementi_elenco" title="<?php echo $partecipanti; ?>">
								<br><?php echo $partecipanti; ?>
							</div>                            
						</td>
						<td>
							<div class="elementi_elenco">
								<br>
								<?php echo gdrcd_format_date($data_spedito).' '.gdrcd_filter('out', $MESSAGE['interface']['messages']['time']).' '.gdrcd_format_time($ora_spedito); ?>
							</div>
						</td>
						<td>
							<div class="controlli_elenco">
								<div class="controllo_elenco" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['reply']); ?>">
									<!-- rispondi -->
									<?php if(gdrcd_filter('in', $row['flreadonly']) =='N' || $flGESTCUSTOM || $_SESSION['permessi'] >= $PARAMETERS['setting']['msg']['group']['sender']['access_level']){ 
										?>
										<a href="main.php?page=messages_center&op=reply&group=<?php echo $idgroup; ?><?php if($flGESTCUSTOM) echo "&sender=custom"; ?>">
										<img class='iconimg' src="imgs/icons/reply.png">
										</a>
										<?php 
									} else echo "&ensp;"; ?>
								</div>
								<div class="controllo_elenco" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['leave']); ?>">
									<!-- abbandona -->
									<?php if($ctgroup != 'SYSTEM' && $ctgroup != 'GLOBAL' && $ctgroup != 'ONLINE'){ 
										?>
									<form action="main.php?page=messages_center" method="post" onsubmit="return confirm('<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['comfirmleaving']) ?>');">
										<input type="hidden" name="op" value="group_leave" />
										<input type="hidden" name="membro" value="<?php echo !$flGESTCUSTOM ? gdrcd_filter('in', $_SESSION['login']):'CUSTOM'?>" />
										<input type="hidden" name="group" value="<?php echo $idgroup; ?>" />
										<input type="image" src="imgs/icons/leave.png" value="submit" />
									</form>
										<?php 
									} else echo "&ensp;"; ?>
								</div>
								<div name="formgroup_del_<?php echo $idgroup; ?>" class="controllo_elenco" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['erase']); ?>">
									<!-- cancella -->
									<?php if(!$flGESTCUSTOM){ 
										?>
										<form action="main.php?page=messages_center" method="post" onsubmit="return confirm('<?php echo gdrcd_filter('out', $MESSAGE['interface']['msggrp']['comfirmdel']) ?>');">
											<input type="hidden" name="op" value="group_del" />
											<input type="hidden" name="group" value="<?php echo $idgroup; ?>" />
											<input type="image" src="imgs/icons/erase.png" value="submit" />
										</form>
										<?php 
									} else echo "&ensp;"; ?>
								</div>
							</div>
						</td>
					</tr>						
					<?php	
				} 
				?>
			</table>
			<?php if(!$flGESTCUSTOM){ 
				?>
				<div class="pulsanti_elenco">
					<!-- segna come letto (tutti)-->
					<form name="formgroup_delAll"  action="main.php?page=messages_center" method="post">
						<div class="form_submit">
							<input type="hidden" name="op" value="group_allRead" />
							<input type="hidden" name="tpgroup" value="<?php echo $TPGROUP; ?>" />
							<input type="submit" value="<?php echo str_replace("%1", strtolower($TPGROUP), gdrcd_filter('out', $MESSAGE['interface']['msggrp']['markallasread'])); ?>" />
							
						</div>
					</form>
				</div>
				<?php 
			} 
				
		} else echo "<br>".gdrcd_filter('out', $MESSAGE['interface']['msggrp']['nogroup'])."<br>";
			
			
		gdrcd_query($result , 'free');
			
		?>		
<!-- Paginatore elenco -->
<div class="pager" style="clear:both; padding: 15px 0px;">
	<?php 
	if($totaleresults > $RECORDPERPAGE) {
		echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
		$maxPage = ceil($totaleresults / $RECORDPERPAGE-1)<=gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxgpagerouptoview'])-1? ceil($totaleresults / $RECORDPERPAGE-1):gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxgpagerouptoview'])-1;
		for($i = 0; $i <= $maxPage; $i++) {
			if($i != $OFFSET) {
				?>
				<a href="main.php?page=messages_center&what=<?php echo $TPGROUP; ?><?php if($flGESTCUSTOM) echo "&sender=custom"; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
				<?php 
			} else {
				echo ' '.($i + 1).' ';
			}
		}
	}
	?>
</div>
</div>


