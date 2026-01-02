<link rel="stylesheet" href="../themes/crystal/famiglie.css">
<?php

/* STEP 1: controlla se è un pg nuovo o un pg vecchio */

     $check_exp = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
     $check_conferma = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio ='".$_SESSION['login']."'");
     
     
     
     //STEP 2: Se non hai ancora raggiunto 20 punti mestiere, sei libero di cambiare il mestiere
     
     $check_pg = "SELECT * FROM clgpersonaggiomestiere WHERE personaggio ='".$_SESSION['login']."'";
     $result = gdrcd_query($check_pg, 'result');
     if ((gdrcd_query($result, 'num_rows') < 1) || ($check_conferma['conferma_mestiere'] < 1)) {
     
     //elenco di tutti i 3 livelli dei mestieri + disocuppato
     
     if(isset($_POST['op']) === false) {
     
     if ($check_exp['esperienza'] < 10) {
     
     echo '<div class="error">Non avendo ancora acquisito un minimo di 10 punti esperienza:<br>
     <ul><li>Puoi cambiare mestiere quando lo riterrai opportuno, purché si segua la coerenza ON.</li>
     <li><b>Non</b> sei abilitato a leggere le bacheche ON e OFF del mestiere.</li>
     <li><b>Non</b> compari nella lista dei membri appartenenti al mestiere nella pagina "<b>MESTIERI</b>".</li>
     <li>Arrivato a 10 punti esperienza potrai confermare la tua appartenenza al mestiere e avere accesso a tutto.</li>
     </ul></div>';
     
     } else {
     
     echo '<div class="error">Hai ' . $check_exp['esperienza_mestiere'] /1 . ' punti mestiere</div>';
     
     }
     
     $elenco_mestieri = "SELECT * FROM ruolo_mestiere WHERE livello_mestiere = '3' ORDER BY mestiere";
     $result_mestieri = gdrcd_query($elenco_mestieri, 'result');
     ?>
     
     <table class="customTable">
  <thead>
    <tr>
      <th colspan="8" style="background-color: #181c31; font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?php echo 'ELENCO RUOLI MESTIERI' ?><br>
                      <?php echo 'Una volta confermato non sarà più possibile cambiarlo' ?></th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>
      		
		</td>	
        <td>
			
			NOME
		
		</td>
        <td>
			
			
		
		</td>
	</tr>
                        <?php
                    while($row = gdrcd_query($result_mestieri, 'fetch')) { ?>
                    
                    <!--table class="lavori_box" border="1"-->
                        <tr>
                            <td>
                                
                                    <img class="" src="imgs/mestieri/<?php echo $row['immagine']; ?>" />
                                
                            </td>
                            <td style="color: #8f8f8f;">
                                <?php echo $row['nome_ruolo']; ?>
                            </td>
                            <!-- Submit di scelta -->
                            <td>
                                
                                <?php
                                $check_id = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio = '". $_SESSION['login'] ."' && id_ruolo = ". $row['id_ruolo'] ."", 'result');
                                $numresults = gdrcd_query($check_id, 'num_rows');
                                if($numresults == 1) {
                                
                                if ($check_exp['esperienza'] < 10) {
                                echo "Puoi confermare il mestiere una volta raggiunti i 10 px";
                                } else {
                                ?>
                                <form onSubmit="return Conferma();" method="post" action="main.php?page=scegli_mestiere">

                                <input type="submit" value="Conferma Mestiere" />
                                <input type="hidden" name="op" value="pick" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $row['id_ruolo']; ?>" />
                                <input type="hidden" name="mestiere" value="<?php echo $row['mestiere']; ?>" />
                                </form>
                                <?php }
                                        } else { ?>
                                <form method="post" action="main.php?page=scegli_mestiere">
                                <input type="submit" value="Cambia" />
                                <input type="hidden" name="op" value="change" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $row['id_ruolo']; ?>" />
                                <input type="hidden" name="mestiere" value="<?php echo $row['mestiere']; ?>" />
                                </form>
                                <?php } ?>
                                
                                
                                </td>
                        </tr>
                     <?php }//while
                    gdrcd_query($result_mestiere, 'free');
                     
                    ?>
                    
                </table>
           
            <!--elenco_record_gioco-->
        <?php       
     }//fine op
     
     //cambio/inserimento
        
     if($_POST['op'] == 'change') {
     
     $check_cambio = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio = '". $_SESSION['login'] ."'", 'result');
     $num_cambio = gdrcd_query($check_cambio, 'num_rows');
     
     if($num_cambio == 1) {
     $change_mestiere = gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = ".gdrcd_filter('num', $_POST['id_record'])." WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
     $change_personaggio = gdrcd_query("UPDATE personaggio SET id_mestiere = ".gdrcd_filter('num', $_POST['mestiere']).", id_ruolo_mestiere = ".gdrcd_filter('num', $_POST['id_record'])." WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
     } else {
     $insert_mestiere = gdrcd_query("INSERT INTO clgpersonaggiomestiere (id_ruolo, personaggio) VALUES (".gdrcd_filter('num', $_POST['id_record']).", '".$_SESSION['login']."')");
     $change_personaggio = gdrcd_query("UPDATE personaggio SET id_mestiere = ".gdrcd_filter('num', $_POST['mestiere']).", id_ruolo_mestiere = ".gdrcd_filter('num', $_POST['id_record'])." WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
     }
     
     echo '<div class="error">Operazione completata.<br>
                              <a href="main.php?page=scegli_mestiere">Torna indietro</a>
           </div>';
     }//fine change
     
     if($_POST['op'] == 'pick') {
     
     $accept = gdrcd_query("UPDATE clgpersonaggiomestiere SET conferma_mestiere = 1 WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
         
      echo '<div class="warning">Ruolo nel mestiere confermato<br>
                                 Un messaggio è stato inserito nella bacheca specifica al tuo mestiere.<br>
                                 Da questo momento puoi guadagnare punti mestiere.<br>
                                 <u>Ti invitiamo a uscire e rientrare per valorizzare i cambiamenti</u>.
            </div>';
     
     //mex in bacheca
     
     $pg = $_SESSION['login'];
         $titolo = "Conferma nel mestiere - ". $_SESSION['login'] ."";
         $message = "Il personaggio [b]". $_SESSION['login'] ."[/b] ha appena confermato il ruolo nel mestiere";
         $araldo = gdrcd_query("SELECT * FROM araldo WHERE tipo = 6 && proprietari = ". $_POST['mestiere'] ."");
         $id = $araldo['id_araldo'];
         $notice = gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista) 
                   VALUES ('-1', '$id', '$titolo', '$message', '$pg', NOW(), NOW(), 'no', 'no')");
     
     echo '<div class="error">Operazione completata.<br>
                              <a href="main.php?page=scegli_mestiere">Torna indietro</a>
           </div>';
           
     }//fine pick
     
     }//fine step 2
     
     
     
     
     
     
     
     
     
     
     
     
     //STEP 3: scalo gerarchia
     
     else { 
     
     
     
     if(isset($_POST['op']) === false) {
     
     if ($check_exp['esperienza_mestiere'] > 55) {
     echo '<div class="error">Hai raggiunto il massimo dei punti mestiere.</div>';
     } else {
     echo '<div class="error">Hai ' . $check_exp['esperienza_mestiere'] /1 . ' punti mestiere</div>';
     }
     //$elenco_ruoli_mestieri = "SELECT personaggio.*, ruolo_mestiere.* FROM personaggio LEFT JOIN ruolo_mestiere ON personaggio.id_mestiere = ruolo_mestiere.mestiere WHERE personaggio.nome = '". $_SESSION['login']."' && ruolo_mestiere.livello_mestiere > 0 ORDER BY ruolo_mestiere.livello_mestiere";     
     //$result_ruoli_mestieri = gdrcd_query($elenco_ruoli_mestieri, 'result');
     $elenco_ruoli_mestieri = "SELECT personaggio.*, ruolo_mestiere.* FROM personaggio LEFT JOIN ruolo_mestiere ON personaggio.id_mestiere = ruolo_mestiere.mestiere WHERE personaggio.nome = '". $_SESSION['login']."' && ruolo_mestiere.livello_mestiere > 0 && ruolo_mestiere.livello_mestiere < 
                               (SELECT livello_mestiere FROM ruolo_mestiere JOIN clgpersonaggiomestiere ON ruolo_mestiere.id_ruolo = clgpersonaggiomestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio = '".$_SESSION['login']."')
     ORDER BY ruolo_mestiere.livello_mestiere";
     $result_ruoli_mestieri = gdrcd_query($elenco_ruoli_mestieri, 'result');
     ?>
     
     <table class="customTable">
  <thead>
    <tr>
      <th colspan="8"><?php echo 'ELENCO RUOLI MESTIERI' ?><br>
                      <?php echo 'Una volta confermato non sarà più possibile cambiarlo' ?></th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>
      		
		</td>	
        <td>
			
			NOME
		
		</td>
        <td>
			
			
		
		</td>
	</tr>
                        <?php
                    while($row = gdrcd_query($result_ruoli_mestieri, 'fetch')) { ?>
                    
                    <!--table class="lavori_box" border="1"-->
                        <tr>
                            <td>
                                
                                    <img class="" src="imgs/mestieri/<?php echo $row['immagine']; ?>" />
                                
                            </td>
                            <td style="color: #8f8f8f;">
                                <?php echo $row['nome_ruolo']; ?>
                            </td>
                            <!-- Submit di scelta -->
                            <td style="color: #8f8f8f;">
                                
                                <?php
                                $check_livello = gdrcd_query("SELECT * FROM ruolo_mestiere WHERE livello_mestiere NOT IN (SELECT livello_mestiere FROM ruolo_mestiere JOIN clgpersonaggiomestiere ON ruolo_mestiere.id_ruolo = clgpersonaggiomestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio = '".$_SESSION['login']."')", 'result');
                                ?>
                                
                                <?php                                
                                
                                if ($check_exp['esperienza_mestiere'] < '10' && $row['livello_mestiere'] < '3') {
                                echo 'Non hai ancora abbastanza esperienza';
                                
                                } else if (($check_exp['esperienza_mestiere'] >= '10' && $check_exp['esperienza_mestiere'] < '55') && $row['livello_mestiere'] < '2') {
                                echo 'Non hai ancora abbastanza esperienza';                                
                                
                                } else if (($check_exp['esperienza_mestiere'] >= '10' && $check_exp['esperienza_mestiere'] < '55') && $row['livello_mestiere'] > '2') {
                                echo '';                                
                                
                                } else if ($check_exp['esperienza_mestiere'] >= '55' && $row['livello_mestiere'] > '1') {
                                echo '';                                
                                
                                } else {
                                ?>
                                
                                
                                <form method="post" action="main.php?page=scegli_mestiere">                               
                                
                                <input type="submit" value="Cambia" />
                                <input type="hidden" name="op" value="level_up" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $row['id_ruolo']; ?>" />
                                <input type="hidden" name="mestiere" value="<?php echo $row['mestiere']; ?>" />
                                </form>
                                
                                <?php } ?>
                                
                                
                                </td>
                        </tr>
                     <?php }//while
                    gdrcd_query($result_mestiere, 'free');
                     
                    ?>
                    
                </table>
           
            <!--elenco_record_gioco-->
        <?php       
     
     }//fine op
     
     //cambio/inserimento
        
     if($_POST['op'] == 'level_up') {
     
     $change_mestiere = gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = ".gdrcd_filter('num', $_POST['id_record'])." WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
     $change_personaggio = gdrcd_query("UPDATE personaggio SET id_mestiere = ".gdrcd_filter('num', $_POST['mestiere']).", id_ruolo_mestiere = ".gdrcd_filter('num', $_POST['id_record'])." WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
     
     }//fine change
     }