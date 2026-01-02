<link rel="stylesheet" href="../themes/crystal/mestieri.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />


<script src="http://code.jquery.com/jquery-latest.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="../statuti/general_script.js"></script>
<link href="../statuti/general_css.css" rel="stylesheet" type="text/css" />


<div class="pagina_gestione_mercato">


<?php
//creo form di contatto
if (isset($_POST['fase']) === false)
{
?>

<font size="4">CONTATTA LA MODERAZIONE</font><br>
<font size="2">(<i>Qualora il richiedente scegliesse di restare anonimo, il suo nome viene criptato dal sistema. Gestione e moderazione <u>non</u> possono vedere il suo nome.</i>)</font>

<form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                          method="post">
                          
                          <div class='form_label'>
                            Titolo
                        </div>
                        
                          <div class='form_field'>
                            <input name="titolo" value="<?php echo gdrcd_filter('titolo', $_POST['titolo']); ?>"/>
                        </div>
                        
                        <div class='form_label'>
                            Testo
                        </div>
                        
                          <div class='form_field'>
                            <textarea name="testo"><?php echo gdrcd_filter('testo', $_POST['testo']); ?></textarea>
                        </div>
                        
                        <div class='form_label'>
                            Anonimo/Non Anonimo
                        </div>
                        
                        <div class="titoli_elenco">
                                <select name="anonimo" value="<?php echo gdrcd_filter('anonimo', $_POST['anonimo']); ?>">
                                <option value="no">Non Anonimo</option>
                                <option value="si">Anonimo</option>
                                </select>
                                </div>
                        
                        
<div class="form_submit">
                            <input type="hidden" name="fase" value="1"/>
                            <input type="submit"
                                   value="Anteprima"/>
</div>
</form>
<br><br>

<table class="customTable">
<tr>
<td colspan="3">
LISTA RICHIESTE DI <font style="text-transform: uppercase;"><?php echo $_SESSION['login']; ?></font>
</td>
</tr>
<tr class="second_header">
<td width=20%>
<b>Data e ora</b>
</td>
<td width=50%>
<b>Titolo</b>
</td>
<td width=30%>
<b>Stato</b>
</td>
</tr>
<?php
      $sql = "SELECT * FROM messaggio_moderazione WHERE autore = '". $_SESSION['login'] . "' ORDER BY id ASC";
      $result = gdrcd_query($sql, "result");
      if(gdrcd_query($result, "num_rows") > 0)
      {
        while($fetch = gdrcd_query($result, 'fetch'))
        {
          $responso = $fetch['risposta'];
          $titolo = $fetch['titolo'];
          $orario = $fetch['data_messaggio'];
          if ($fetch['stato'] == '0') {
          $risposta = 'In corso';
          } else {
          $risposta = 'Chiuso';
          }
    ?>
    <tr>
	<td><?= $orario ?></td>
    <td>
    <?php if ($responso != NULL) {
    ?>
    <a href="#dialog" name="modal"><?= $titolo ?></a>
    <?php } else { ?>
    <?= $titolo ?>
    <?php } ?>
    </td>
    <td><?= $risposta ?></td>
    </tr>
  
    
    <div id="box"> 
		<div id="dialog" class="window"> 
			<p class="titoletti">Risposta Moderatori</p><br /><br />
            <?php echo gdrcd_filter('out', $responso); ?>
            </div>
            <div id="mask">
		</div> 
	</div>
<?php
    }//fine while
    }//fine if
    
//fine form di contatto

?>

</table>

<?php
} 

/***** Fase 2 *****/
        if (gdrcd_filter('get', $_POST['fase']) == 1)
        {

?>
            
            <table style="width: 250px;" class="customTable">
                            <tr>
                                <td class='casella_titolo'>
                                    <div class='titoli_elenco'>ANTEPRIMA</div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $_POST['titolo']) ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $_POST['testo']) ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php 
                                        if ($_POST['anonimo'] == 'si')
                                        { $anonimo = 'Anonimo'; }
                                        else {$anonimo = 'Non anonimo'; }
                                        echo gdrcd_filter('out', $anonimo) ?>
                                    </div>
                                </td>
                            </tr>
                            </table>
                            
                            <div class="form_gioco">
                        <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                              method="post">
                            <div class="form_submit">
                                <input type="hidden" name="fase" value="2"/>
                                <input type="hidden" name="titolo"
                                       value="<?php echo gdrcd_filter('out', $_POST['titolo']) ?>"/>
                                <input type="hidden" name="testo"
                                       value="<?php echo gdrcd_filter('out', $_POST['testo']) ?>"/>
                                <input type="hidden" name="anonimo"
                                       value="<?php echo gdrcd_filter('out', $_POST['anonimo']) ?>"/>
                                
                               
                                <input type="submit"
                                       value="Invia"/>
                            </div>
                        </form>
                        <form action="javascript:history.go(-1)"
                              method="post">
                            <div class="form_submit">
                                <input type="hidden" name="fase" value="false"/>
                                <input type="hidden" name="titolo"
                                       value="<?php echo gdrcd_filter('out', $_POST['titolo']) ?>"/>
                                <input type="hidden" name="testo"
                                       value="<?php echo gdrcd_filter('out', $_POST['testo']) ?>"/>
                                <input type="hidden" name="anonimo"
                                       value="<?php echo gdrcd_filter('out', $_POST['anonimo']) ?>"/>
                                
                                
                                <input type="submit" value="Modifica"/>
                            </div>
                        </form>
                    </div>
<?php
/***** FINE Fase 2 *****/
        }
        
        /***** Fase 3 *****/
        if ($_POST['fase'] == 2)
        {
        
        $testo = $_POST['testo'];
        gdrcd_query("INSERT INTO messaggio_moderazione (titolo, messaggio, autore, data_messaggio, anonimo) VALUES ('" . gdrcd_filter('out', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . $_SESSION['login'] . "', NOW(), '" . gdrcd_filter('out', $_POST['anonimo']) . "')");
        gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, giornalista, anonimo) VALUES ('-1', '86', '" . gdrcd_filter('out', $_POST['titolo']) . "', '" . gdrcd_filter('in', $testo) . "', '" . $_SESSION['login'] . "', NOW(), NOW(), 'no', '" . gdrcd_filter('out', $_POST['anonimo']) . "')");
        echo '<div class="warning">Messaggio inoltrato. Riceverai un messaggio privato con il responso della moderazione.</div>';

/***** FINE Fase 3 *****/
        }
?>


</div>