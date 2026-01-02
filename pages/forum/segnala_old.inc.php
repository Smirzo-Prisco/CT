<?php 
/*Segnalazione Topic
Na cagata random pé provà! */
if ($_SESSION['login'] != 'Lii') {

echo 'Pagina momentaneamente soggetta a modifiche per qualche giorno';
} else {







if($_REQUEST['op']=='segnala'){
$gilda = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
$row = gdrcd_query("SELECT * FROM messaggioaraldo WHERE id_messaggio=".gdrcd_filter('num',$_REQUEST['what'])."");


echo '
<h3>'.gdrcd_filter('out','Segnala la Discussione').'</h3>
<form action="main.php?page=forum&op=segnala" method="post">
<input type="text" list="personaggi" name="interessato" placeholder="Nome del personaggio" />
';
?>
<br><select name="multipli">

                    <option value="---" selected>
                        -------
                    </option>

                    <?php 
                    if($_SESSION['admin'] == 1) { ?>
                        <option value="broadcast">
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['all']); ?>
                        </option>
                     <?php }//fine globale ?>
                     
                     <option value="moderatore">
                        <?php echo "Segnala a un moderatore"; ?>
                    </option>   
                    <?php 
                    $araldo = gdrcd_query("SELECT * FROM araldo WHERE id_araldo=".$row['id_araldo']."");
                    if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 1)) {
                    ?>
                    <option value="paladini">
                        <?php echo "Segnala ai Paladini"; ?>
                    </option>
<?php }
if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 2)) {
                    ?>
                    <option value="demoni">
                        <?php echo "Segnala al Regno di Caos"; ?>
                    </option>
<?php }
if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 3)) {
                    ?>
                    <option value="setta">
                        <?php echo "Segnala alla Setta dei Miracoli"; ?>
                    </option>
<?php }
if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 4)) {
                    ?>
                    <option value="custodi">
                        <?php echo "Segnala ai Custodi del Primordio"; ?>
                    </option>
<?php }
if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 5)) {
                    ?>
                    <option value="guardiani">
                        <?php echo "Segnala ai Guardiani di Cosmos"; ?>
                    </option>
<?php }
if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 6)) {
                    ?>
                    <option value="fiori">
                        <?php echo "Segnala ai Fiori del Male"; ?>
                    </option>
<?php }
if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == 7)) {
                    ?>
                    <option value="lancaster">
                        <?php echo "Segnala ai Lancaster"; ?>
                    </option>
                    
                    <?php }
                    if ($araldo['tipo'] == 6 && $_SESSION['admin'] != 1) {
                    ?>
                    <option value="tutto_mestiere">
                        <?php echo "Segnala al mestiere"; ?>
                    </option>
                    <?php } 
                    if ($araldo['tipo'] == 6 && $_SESSION['admin'] == 1) {
                    ?>
                    <option value="corte">
                        <?php echo "Segnala alla Corte"; ?>
                    </option>
                    <option value="strike">
                        <?php echo "Segnala alla I.C.C."; ?>
                    </option>
                    <option value="secret">
                        <?php echo "Segnala al Secret Pandora"; ?>
                    </option>
                    <option value="magic">
                        <?php echo "Segnala al Shirokuro Magic Shop"; ?>
                    </option>
                    <option value="tae">
                        <?php echo "Segnala alla TAE"; ?>
                    </option>
                    <?php }
                    if ($araldo['id_araldo'] == 7 || $araldo['id_araldo'] == 23) {
                    ?>
                    <option value="master">
                        <?php echo "Segnala ai master"; ?>
                    </option> 
                    <?php } 
                    if ($araldo['id_araldo'] == 9 || $araldo['id_araldo'] == 110) {
                    ?>
                    <option value="capo_gilda">
                        <?php echo "Segnala ai capogilda"; ?>
                    </option>   
                    <?php }
                    if ($araldo['id_araldo'] == 30) {
                    ?>
                    <option value="capo_mestiere">
                        <?php echo "Segnala ai capomestiere"; ?>
                    </option>   
                    <?php }?>
                </select>
<?php
echo '
<br>Commenta la Segnalazione:<br>
<textarea type="text" name="CommentoSegnalazione" cols="50" rows="6" placeholder="Eventuale commento" /></textarea>

<input type="hidden" name="what" value="'.$row["id_messaggio"].'" />
<input type="hidden" name="where" value="'.$row["id_araldo"].'" />
<input type="hidden" name="op" value="segnalaok" />
<p>'.gdrcd_filter('out','Invia la segnalazione').'</p>
<input type="submit" value="'.gdrcd_filter('out','Segnala').'" />
</form>
';
}

if($_REQUEST['op']=='segnalaok'){
    
	#$segnalazione=gdrcd_filter_in("[url]https://crystaltokyo.altervista.org/main.php?page=forum&op=read&what=".gdrcd_filter('num',$_REQUEST['what'])."&where=".gdrcd_filter('num',$_REQUEST['where'])."[/url]");
	$segnalazione="<a href='https://crystaltokyo.altervista.org/main.php?page=forum&op=read&what=".gdrcd_filter('num',$_REQUEST['what'])."&where=".gdrcd_filter('num',$_REQUEST['where'])."' target='_top'>Clicca qui</a>";	
    $CommentoSegnalazione = gdrcd_capital_letter(gdrcd_filter('in',$_POST['CommentoSegnalazione']));
    $messaggio=gdrcd_filter_in("<b>$segnalazione"."<br><br></b>"."$CommentoSegnalazione");
    
    
       //MODERAZIONE
        if ($_POST['multipli']=='moderatore')
        {
        $moderator= "SELECT * FROM privilegi WHERE moderatore = 1";
        $resultmoderator = gdrcd_query($moderator, 'result');
        while($record_moderator = gdrcd_query($resultmoderator , 'fetch'))
        {


//inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_moderator['nome']."' || destinatario = '".$record_moderator['nome']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_moderator['nome']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_moderator['nome']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender ='".$record_moderator['nome']."' || destinatario ='".$record_moderator['nome']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if


        }
        
        gdrcd_query($moderator , 'free');
        }
        
        
        
        
        
        
        
        
        //PALADINI
        elseif ($_POST['multipli']=='paladini')
        {
        $paladini= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 1)";
        $result_paladini = gdrcd_query($paladini, 'result');
        while($record_paladini = gdrcd_query($result_paladini , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_paladini['personaggio']."' || destinatario = '".$record_paladini['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_paladini['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_paladini['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_paladini['personaggio']."' || destinatario = '".$record_paladini['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($paladini , 'free');
        }
        
        
        
        
        
        
         //DEMONI
        elseif ($_POST['multipli']=='demoni')
        {
        $demoni= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 2)";
        $result_demoni = gdrcd_query($demoni, 'result');
        while($record_demoni = gdrcd_query($result_demoni , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_demoni['personaggio']."' || destinatario = '".$record_demoni['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_demoni['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_demoni['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_demoni['personaggio']."' || destinatario = '".$record_demoni['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($demoni , 'free');
        }
        
        
        
        
        
        
        
        
        
        //SETTA
        elseif ($_POST['multipli']=='setta')
        {
        $setta= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 3)";
        $result_setta = gdrcd_query($setta, 'result');
        while($record_setta = gdrcd_query($result_setta , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_setta['personaggio']."' || destinatario = '".$record_setta['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_setta['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_setta['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_setta['personaggio']."' || destinatario = '".$record_setta['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($setta , 'free');
        }
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        //BEAST
        elseif ($_POST['multipli']=='custodi')
        {
        $custodi= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 4)";
        $result_custodi = gdrcd_query($custodi, 'result');
        while($record_custodi = gdrcd_query($result_custodi , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_custodi['personaggio']."' || destinatario = '".$record_custodi['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_custodi['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_custodi['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_custodi['personaggio']."' || destinatario = '".$record_custodi['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($custodi , 'free');
        }
        
        
        
        
        
        
        
        //GUARDIANI
        elseif ($_POST['multipli']=='guardiani')
        {
        $guardiani= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 5)";
        $result_guardiani = gdrcd_query($guardiani, 'result');
        while($record_guardiani = gdrcd_query($result_guardiani , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_guardiani['personaggio']."' || destinatario = '".$record_guardiani['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_guardiani['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_guardiani['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_guardiani['personaggio']."' || destinatario = '".$record_guardiani['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($guardiani , 'free');
        }
        
        
        
        
        
        
        
        
        //FIORI
        elseif ($_POST['multipli']=='fiori')
        {
        $fiori= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 6)";
        $result_fiori = gdrcd_query($fiori, 'result');
        while($record_fiori = gdrcd_query($result_fiori , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_fiori['personaggio']."' || destinatario = '".$record_fiori['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_fiori['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_fiori['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_fiori['personaggio']."' || destinatario = '".$record_fiori['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($fiori , 'free');
        }
        
        
        
        
        
        
        
        
        
        //LANCASTER
        elseif ($_POST['multipli']=='lancaster')
        {
        $lancaster= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = 7)";
        $result_lancaster = gdrcd_query($lancaster, 'result');
        while($record_lancaster = gdrcd_query($result_lancaster , 'fetch'))
        {

    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_lancaster['personaggio']."' || destinatario = '".$record_lancaster['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_lancaster['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_lancaster['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_lancaster['personaggio']."' || destinatario = '".$record_lancaster['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($lancaster , 'free');
        }
        
        
        //TUTTO IL MESTIERE
        elseif ($_POST['multipli']=='tutto_mestiere')
        {
        $work= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1)";
        $result_work = gdrcd_query($work, 'result');
        while($record_work = gdrcd_query($result_work , 'fetch'))
        {


 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_work['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_work['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($work , 'free');
        }
        
        //CORTE
        elseif ($_POST['multipli']=='corte')
        {
        $work= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.conferma_mestiere = 1 AND ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = 6)";
        $result_work = gdrcd_query($work, 'result');
        while($record_work = gdrcd_query($result_work , 'fetch'))
        {
 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_work['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_work['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($work , 'free');
        }
        
        //STRIKE
        elseif ($_POST['multipli']=='strike')
        {
        $work= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.conferma_mestiere = 1 AND ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = 1)";
        $result_work = gdrcd_query($work, 'result');
        while($record_work = gdrcd_query($result_work , 'fetch'))
        {

 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_work['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_work['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if

        }
        
        gdrcd_query($work , 'free');
        }
        
        //Secret
        elseif ($_POST['multipli']=='secret')
        {
        $work= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.conferma_mestiere = 1 AND ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = 4)";
        $result_work = gdrcd_query($work, 'result');
        while($record_work = gdrcd_query($result_work , 'fetch'))
        {

 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_work['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_work['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if

        }
        
        gdrcd_query($work , 'free');
        }
        
        //MAGIC
        elseif ($_POST['multipli']=='magic')
        {
        $work= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.conferma_mestiere = 1 AND ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = 3)";
        $result_work = gdrcd_query($work, 'result');
        while($record_work = gdrcd_query($result_work , 'fetch'))
        {

 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_work['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_work['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if


        }
        
        gdrcd_query($work , 'free');
        }
        
        //TAE
        elseif ($_POST['multipli']=='tae')
        {
        $work= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.conferma_mestiere = 1 AND ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = 2)";
        $result_work = gdrcd_query($work, 'result');
        while($record_work = gdrcd_query($result_work , 'fetch'))
        {
 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_work['personaggio']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_work['personaggio']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_work['personaggio']."' || destinatario = '".$record_work['personaggio']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         
         }
        
        gdrcd_query($work , 'free');
        }
        //MASTER
        else if ($_POST['multipli']=='master')
        {
        $master= "SELECT * FROM privilegi WHERE master = 1";
        $resultmaster = gdrcd_query($master, 'result');
        while($record_master = gdrcd_query($resultmaster , 'fetch'))
        {
 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record_master['nome']."' || destinatario = '".$record_master['nome']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record_master['nome']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record_master['nome']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record_master['nome']."' || destinatario = '".$record_master['nome']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         }
        
        gdrcd_query($master , 'free');
        }
        
        //CAPOGILDA
        else if ($_POST['multipli']=='capo_gilda')
        {
        $capoguilds= "SELECT * FROM privilegi WHERE capogilda = 1";
        $resultguilds = gdrcd_query($capoguilds, 'result');
        while($record = gdrcd_query($resultguilds , 'fetch'))
        {
 //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender = '".$record['nome']."' || destinatario = '".$record['nome']."') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$record['nome']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '".$record['nome']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender = '".$record['nome']."' || destinatario = '".$record['nome']."') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
         }
        
        gdrcd_query($capoguilds , 'free');
        }
        //CAPOGILDA
        else if ($_POST['multipli']=='capo_mestiere')
        {
        $capoguilds= "SELECT * FROM privilegi WHERE capomestiere = 1";
        $resultguilds = gdrcd_query($capoguilds, 'result');
        while($record = gdrcd_query($resultguilds , 'fetch'))
        {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('".$_SESSION['login']."', '".$record['nome']."', NOW(), 'Segnalazione Capomestiere', '" . $messaggio . "', 'off')");
        }
        
        gdrcd_query($capoguilds , 'free');
        }
        //GLOBALE
        elseif ($_POST['multipli'] == "broadcast") {
        
        $sql_globale = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                                     msggrp.ctgroup = 'GLOBAL' AND tpgroup = 'OFF'", 'result');
         if (gdrcd_query($sql_globale, 'num_rows') < 1) {
            echo "<script type='text/javascript'>alert('No');</script>";
            } else {

          $fase_find_global = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                          msggrp.ctgroup = 'GLOBAL' AND tpgroup = 'OFF'");
          $id_gruppo = $fase_find_global['idgroup'];
          $fladdusers = $fase_find_global['fladdusers'];
          
          gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='".$id_gruppo."'");

				// messaggio
          gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('". $id_gruppo."','" . gdrcd_filter('in', $_SESSION['login']) . "', '".$messaggio."');");	

        // aggiunta dei membri mancanti
		if($fladdusers == 1){
			$query_globale = "SELECT personaggio.nome FROM personaggio LEFT JOIN msggrpuser ON msggrpuser.nome = personaggio.nome and idgroup = ".$id_gruppo." and msggrpuser.dtstart<=NOW() and msggrpuser.dtend >= NOW() WHERE (esilio is null or esilio < CURDATE()) and msggrpuser.nome IS NULL ORDER BY personaggio.nome";
			$userMissList = gdrcd_query($query_globale, 'result'); 	
			if(gdrcd_query($userMissList, 'num_rows') > 0 ){	
				while($userMiss = gdrcd_query($userMissList, 'fetch')) { 
					$query_global="INSERT INTO msggrpuser (idgroup, tpuser, nome, flpin) VALUES ('".$id_gruppo."','USER','".gdrcd_filter('in', $userMiss['nome'])."', '1');";
					gdrcd_query($query_global);
				}
			}
			gdrcd_query($userMissList, 'free');				
		}

}
        
        
        
    } else {
    //segnalazione multipla
    $Dest = explode(',', $_POST['interessato']);
    for ($i = 0; $i <= count($Dest)-1; $i++) {
        $destinat = trim($Dest[$i]);
        $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '" . $destinat . "'", 'result');
        
        //inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                        AND  (nomesender ='$destinat' || destinatario ='$destinat') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','N', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER','" . $destinat . "');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','" . gdrcd_filter('in', $_SESSION['login']) . "', '$messaggio', '" . $destinat . "');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_SESSION['login']) . "' || destinatario = '" . gdrcd_filter('in', $_SESSION['login']) . "') 
                       AND  (nomesender ='$destinat' || destinatario ='$destinat') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='".gdrcd_filter('in',$_SESSION['login'])."' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','" . gdrcd_filter('in', $_SESSION['login']) . "','$messaggio');");
         }//fine if
  }//fine for
      }//fine else
      $back = "forum&op=read&what=".$_REQUEST['what']."&where=".$_REQUEST['where'];
      echo '<div class="error">Messaggio segnalato!<br>';
      ?>
      <a href="main.php?page=<?php echo $back ?>" target="_top">Torna al post</a>
      <?php 
      echo '</div>';
    }//fine segnala_ok
    
    }