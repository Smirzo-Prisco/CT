<link rel="stylesheet" href="../themes/crystal/volti.css">

<?php

       /* Controllamo se tiene l'esperienza */

       $check_exp = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

       if ($check_exp['esperienza'] < 10) {

     echo '<div class="error">Accesso negato.<br>
     Hai bisogno di almeno 10 px per scegliere un\'inclinazione magica.<br>
     <a href="/statuti/inclinazioni/inclinazioni.html" target="_blank">Puoi leggere le descrizioni qui</a>.</div>';

     } else {

     /* Ora controllamo che non sia in una famiglia */
     
     $check_ruolo = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
     if ($check_ruolo['id_gilda'] > 0) {

      echo '<div class="error">Accesso negato.<br>
      Sei già in una famiglia magica.</div>';

     } else {
     
     /* Infine controllamo che non abbia già un'inclinazione */

     $check_inclinazione = gdrcd_query("SELECT * FROM clgpersonaggioinclinazione WHERE personaggio ='".$_SESSION['login']."'");
     if ($check_inclinazione['id_ruolo'] > 0) {

      echo '<div class="error">Accesso negato.<br>
      Hai già scelto la tua inclinazione.</div>';

     } else {
     
     /* Ora puoi scegliere */
?>

    <div class="pagina_servizi_lavoro">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['inclinazioni']['page_name']); ?></h2>
    </div>
    
    <?php /*Elenco inclinazioni*/
        if(isset($_POST['op']) === false) {
            $query = "SELECT * FROM inclinazione ORDER BY id_inclinazione";
            $result = gdrcd_query($query, 'result'); ?>
            <table class="customTable">
  <thead>
    <tr>
      <th colspan="8"><?php echo 'ELENCO INCLINAZIONI' ?></th>
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
                    while($row = gdrcd_query($result, 'fetch')) { ?>
                    
                    <!--table class="lavori_box" border="1"-->
                        <tr>
                            <td>
                                
                                    <img class="" src="imgs/inclinazioni/<?php echo $row['immagine']; ?>" />
                                
                            </td>
                            <td>
                                <?php echo $row['nome']; ?>
                            </td>
                            <!-- Submit di scelta -->
                            <td>
                                
                                 <form method="post" action="main.php?page=scegli_inclinazione">
                                
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['job']['submit']['pick']); ?>" />
                                <input type="hidden" name="op" value="pick" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $row['id_inclinazione']; ?>" />
                                
                                </form>
                                
                                </td>
                        </tr>
                     <?php }//while
                    gdrcd_query($result, 'free');
                     
                    ?>
                    
                </table>
            </div>
            <!--elenco_record_gioco-->
        <?php
        }//if-op
        
        /* scelta inclinazione */
        
        if($_POST['op'] == 'pick') {
         
         $accept = gdrcd_query("INSERT INTO clgpersonaggioinclinazione (id_ruolo, personaggio) VALUES (".gdrcd_filter('num', $_POST['id_record']).", '".$_SESSION['login']."')");
         
           echo '<div class="warning">Inclinazione scelta correttamente!<br>
                                      Un messaggio è stato inoltrato ai membri della famiglia associata.
                 </div>';
         
         $pg = $_SESSION['login'];
         $titolo = "Scelta inclinazione - ". $_SESSION['login'] ."";
         $message = "Il personaggio [b]". $_SESSION['login'] ."[/b] ha appena scelto l\'inclinazione [u]". $_POST['nome_lavoro'] ."[/u]";
         $araldo = gdrcd_query("SELECT * FROM araldo WHERE tipo = 5 && proprietari = ". $_POST['id_record'] ."");
         $id = $araldo['id_araldo'];
         $notice = gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista) 
                   VALUES ('-1', '$id', '$titolo', '$message', '$pg', NOW(), NOW(), 'no', 'no')");
         
            ?>
            <div class="link_back">
                <a href="main.php?page=scegli_inclinazione"><?php echo gdrcd_filter('out', $MESSAGE['interface']['job']['back']); ?></a>
            </div>
            
            <?php
         
        }
                     
                     

?>


<?php
/* Chiudo questione inclinazione, gilda, esperienza e pagina */
   }
    }    
     }
?>
<br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>