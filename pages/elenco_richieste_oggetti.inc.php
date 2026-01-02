<div class="pagina_gestione_manutenzione">
    <?php
    /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
    /*Puoi entrare*/
    
    /*Approvamo yeah*/
    if($_POST['Approva'] == 'Approva') {
    
    $insert=gdrcd_query("INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, numero, cariche) VALUES ('".gdrcd_filter('in', $_POST['creatore'])."', '".gdrcd_filter('in', $_POST['id_oggetto'])."', '1', '-1')"); 
    $edit_richiesto=gdrcd_query("UPDATE oggetto SET richiesto = '0' WHERE id_oggetto = '".gdrcd_filter('in', $_POST['id_oggetto'])."'");
    
    $testo_si = "La richiesta di oggetto personalizzato &egrave; stata accettata e il tuo oggetto &egrave; stato caricato nella tua scheda personaggio";
//inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_POST['creatore']) . "' || destinatario = '" . gdrcd_filter('in', $_POST['creatore']) . "') 
                        AND  (nomesender = 'Staff' || destinatario = 'Staff') 
                        AND msggrp.ctgroup = 'SISTEMA' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SISTEMA','S', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$_POST['creatore']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','Staff', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','Staff', '$testo_si', '".$_POST['creatore']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_POST['creatore']) . "' || destinatario = '" . gdrcd_filter('in', $_POST['creatore']) . "') 
                       AND  (nomesender ='Staff' || destinatario ='Staff') 
                       AND msggrp.ctgroup = 'SISTEMA' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='Staff' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','Staff','$testo_si');");
         }//fine if    
         }
         
         /* Negazione */
         
         elseif($_POST['Nega'] == 'Nega') {
    
         /*Avvio text per motivazione*/
         
         ?>
         <form action="main.php?page=elenco_richieste_oggetti" method=Post>
         <div class='form_label'>
         Motivo di negazione
         </div>
         <div class='form_field'>
         <textarea name="motivo"></textarea>
                </div>
    
    
    <input type="hidden" name="id_oggetto" value="<?php echo gdrcd_filter('out', $_POST['id_oggetto']); ?>" />
    <input type="hidden" name="creatore" value="<?php echo gdrcd_filter('out', $_POST['creatore']); ?>" />
    <input type="submit" name="Invia" value="Invia">
    </form>     
    
       <?php
           
           }
       /* Invio robe */
       
       if ($_POST['Invia'] == 'Invia') {
       
       $testo_no = "La richiesta di oggetto personalizzato &egrave; stata respinta e ti sono stati riaccreditati i 300 Yen. La motivazione &egrave; la seguente: <b>". gdrcd_filter('in', $_POST['motivo']) ."</b>";
//inizio iter messaggio
        
    $sql = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                        (nomesender = '" . gdrcd_filter('in', $_POST['creatore']) . "' || destinatario = '" . gdrcd_filter('in', $_POST['creatore']) . "') 
                        AND  (nomesender = 'Staff' || destinatario = 'Staff') 
                        AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'", 'result');
   if (gdrcd_query($sql, 'num_rows') < 1) {
   
   //creo conversazione
          $retQuery = gdrcd_query("INSERT INTO msggrp (dsgroup,tpgroup,ctgroup,flreadonly,dtlastreply) VALUES ('Titolo','OFF','SINGLE','S', NOW());");  
          $lastId = gdrcd_query($retQuery, 'last_id');
          if($retQuery){
          
          // usergroup - inserimento membri del gruppo
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome) VALUES ('". $lastId."','USER', '".$_POST['creatore']."');");
		  gdrcd_query("INSERT INTO msggrpuser (idgroup, tpuser, nome, dtlastread) VALUES ('". $lastId."','USER','Staff', NOW());");
	     
         // messaggio
				$query="INSERT INTO msg (idgroup, nomesender , message, destinatario) VALUES";
				$query.=" "."('".gdrcd_filter('in',$lastId)."','Staff', '$testo_no', '".$_POST['creatore']."');";	
				$retQuery = gdrcd_query($query);
                
          }
          } else {

          $fase_find = gdrcd_query("SELECT msg.*, msggrp.* FROM msg JOIN msggrp on msg.idgroup=msggrp.idgroup WHERE 
                       (nomesender = '" . gdrcd_filter('in', $_POST['creatore']) . "' || destinatario = '" . gdrcd_filter('in', $_POST['creatore']) . "') 
                       AND  (nomesender ='Staff' || destinatario ='Staff') 
                       AND msggrp.ctgroup = 'SINGLE' AND tpgroup = 'OFF'");

          $id_gruppo = $fase_find['idgroup'];

         gdrcd_query("UPDATE msggrp SET dtlastreply = NOW() WHERE idgroup='$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET letto = '0' WHERE nome = '" . gdrcd_filter('in', $_POST['creatore']) . "' and idgroup= '$id_gruppo'");
         gdrcd_query("UPDATE msggrpuser SET dtlastread = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE nome ='Staff' and idgroup='".$id_gruppo."'");
		 gdrcd_query("INSERT INTO msg (idgroup, nomesender, message) VALUES ('$id_gruppo','Staff','$testo_no');");
         }//fine if       
       $soldi_load = gdrcd_query("UPDATE personaggio SET soldi = soldi + 300 WHERE nome = '".$_POST['creatore']."'");
       $erase_oggetto=gdrcd_query("DELETE FROM oggetto WHERE id_oggetto = '".gdrcd_filter('in', $_POST['id_oggetto'])."'");

       }
          
       /* Chiusura nega */
       ?>
        
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['list_obj']); ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">
        

        <font size=1>Le immagini devono essere visibili<br>
             I bonus devono essere corretti.</font>
</center><br><br>


<table cellpadding=2 cellspacing=2 width="90%">
<tr>
<td width='8%' align=center class=table2>Immagine</td>
<td width='8%' align=center class=table2>Nome</td>
<td width='15%' align=center class=table2>Descrizione</td>
<td width='8%' align=center class=table2>Extra</td>
</tr>

<?php

    $query = gdrcd_query("SELECT * FROM oggetto WHERE richiesto = 1", 'result');
    while ($row = gdrcd_query($query, 'fetch')) {
    
    //diamo un nome alle parti del corpo
    $ubicabile = $row['ubicabile'];
    if ($ubicabile == 0) {
    $dove = "Non indossabile";
    } else if ($ubicabile == 2) {
    $dove = "Mano destra";
    } else if ($ubicabile == 3) {
    $dove = "Mano sinistra";
    } else if ($ubicabile == 4) {
    $dove = "Corpo";
    } else if ($ubicabile == 5) {
    $dove = "Gambe";
    } else if ($ubicabile == 6) {
    $dove = "Piedi";
    } else if ($ubicabile == 7) {
    $dove = "Testa";
    } else if ($ubicabile == 8) {
    $dove = "Anello";
    } else if ($ubicabile == 9) {
    $dove = "Collo";
    }
    
    //diamo un nome alle parti del corpo
    $tipo_arma = $row['tipo_arma'];
    if ($tipo_arma == 1) {
    $arma = "Arma bianca";
    } else if ($tipo_arma == 2) {
    $arma = "Arma da lancio";
    } else if ($tipo_arma == 3) {
    $arma = "Arma da fuoco";
    }
    
    //diamo un nome alle parti del corpo
    $cariche = $row['cariche'];
    if ($cariche == 1) {
    $testone = "Uso limitato";
    } else {
    $testone = "Uso illimitato";
    }
?>

<!-- Inizio impaginazione oggetti-->

<tr>
<td align=center><div align='center' ><img src="imgs/items/<?php echo gdrcd_filter('out',$row['urlimg']); ?>"/></div></td>
<td align=center><div align='center' ><?php echo gdrcd_filter('out',
                                        $row['nome']);

if ($row['tipo_arma'] > 0) {
        echo ("<br><br><font color=orange><b>".$arma."</b><br><br>");
	};
echo $testone;
?>
</div></td>
<td align=center><div align='justify' ><?php echo gdrcd_filter('out', $row['descrizione']); ?>
    <?php echo gdrcd_filter('out', $row['creatore']); ?>
    <br><br>
    <?php
    if ($row['ubicabile'] >= 0) {
        echo ("<br><br><font color=orange><b>Indossabile: ".$dove."</b>");
	};
    ?>
    </div></td>
    <td align=center><div align='center' >
    
    <form action="main.php?page=elenco_richieste_oggetti" method="Post">
    <input type="hidden" name="id_oggetto" value="<?php echo gdrcd_filter('out', $row['id_oggetto']); ?>" />
    <input type="hidden" name="creatore" value="<?php echo gdrcd_filter('out', $row['creatore']); ?>" />
    <input type="submit" name="Approva" value="Approva">
    <input type="submit" name="Nega" value="Nega">    
	

    </form>

   	
</div></td>
</tr>   
<?php
}
$row->close;
?>
</table>
   <?php
   /*CHIUSURA IF INIZIALE*/
             }
   ?>
