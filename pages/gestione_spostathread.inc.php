<?php /*HELP: */

$result = gdrcd_query("SELECT messaggioaraldo.id_messaggio, messaggioaraldo.id_messaggio_padre, messaggioaraldo.autore, messaggioaraldo.id_araldo, messaggioaraldo.titolo FROM messaggioaraldo WHERE id_messaggio_padre = -1 ORDER BY titolo ASC", 'result');
$row = gdrcd_query($result, 'fetch');

$result2 = gdrcd_query("SELECT id_araldo, nome, tipo, proprietari FROM araldo ORDER BY nome ASC", 'result');
$row2 = gdrcd_query($result2, 'fetch');



if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1) { /* se almeno admin */

?>

<div class="pagina_servizi_banca">


<!-- Titolo della pagina -->
<div class="page_title">
<h2>Sposta thread</h2>
</div>

<!-- Operazioni bancarie -->
<div class="page_body">

<?php /*tutto da scrivere*/


        if(gdrcd_filter('get', $_POST['op']) == 'sposta') {
     
	  gdrcd_query("UPDATE messaggioaraldo SET id_araldo = '".$_POST['nuovabacheca']."' WHERE id_messaggio = '".$_POST['thread']."' LIMIT 1");
	  gdrcd_query("UPDATE messaggioaraldo SET id_araldo = '".$_POST['nuovabacheca']."' WHERE id_messaggio_padre = '".$_POST['thread']."' LIMIT 1");
	  gdrcd_query("UPDATE araldo_letto SET araldo_id = '".$_POST['nuovabacheca']."' WHERE thread_id = '".$_POST['thread']."'");
       

 echo '<div class="warning">Thread spostato correttamente</div>';

 ?>
 
 <div class="panels_link">
                <a href="main.php?page=forum"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['back']); ?></a>
            </div>
			
			
 <?php 
 
 }
		
		
 ?>


<?php /*Visualizzazione di base*/
if(isset($_POST['op'])===FALSE){?>
<div class="panels_box">
<div>
<!-- Istruzioni -->
qualche riga di istruzione<br />
</div>

<!-- Form -->

<div class="form_gioco">
<form action="main.php?page=gestione_spostathread" method="post">
                      
                        <div class="form_element">
						  <div class="form_label">
                          Seleziona il thread da spostare
                        </div>
                            <select name="thread">
                                <?php
															
                                while($row = gdrcd_query($result, 'fetch')) { ?>
                                    <option  value="<?php echo $row['id_messaggio']; ?>">
                                        <?php echo $row['titolo']; ?>  (<?php  echo $row['autore'] ?>) 
                                        
                                    </option>
                                <?php }
                                gdrcd_query($result, 'free');
                                ?>
                            </select>
							 <div class="form_label">
                          Seleziona la bacheca dove spostarlo
                        </div>
						
                            <select name="nuovabacheca">
                                <?php
                                while($row2 = gdrcd_query($result2, 'fetch')) { ?>
                                    <option value="<?php echo $row2['id_araldo']; ?>">
                                        <?php echo $row2['nome']; ?>
                                    </option>
                                <?php }
                                gdrcd_query($result2, 'free');
                                ?>
                            </select>
                        </div>
                        <div class="form_submit">
                            <input type="hidden" name="op" value="sposta" />
                            <input type="submit" name="submit" value="sposta" />
                        </div>
                    </form>
</div>

<?php } //fine base ?>



			
</div><!-- operazioni-->


</div><!-- box -->

<?php } else { echo '<div class="warning">Non sei abilitato a spostare questo thread</div>'; }//fine if se non si è admin ?>
