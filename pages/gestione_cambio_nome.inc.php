<div class="pagina_gestione_manutenzione">
    <?php
    /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
    /*Puoi entrare*/
    
    /*Selezioniamo liv*/
    $all_pg = gdrcd_query("SELECT * FROM personaggio WHERE (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    /*cambiamo la razza e i talenti al pg*/
    
    if(isset($_POST['op']) === true) {
    if(gdrcd_filter('get', $_POST['op']) == 'delete') {
    $nome = $_POST['pg'];
    $new_name = $_POST['causale'];
    //controllo se il nuovo nome non esiste già
    
    $check_name = gdrcd_query("SELECT nome FROM personaggio WHERE nome='" . gdrcd_capital_letter(gdrcd_filter('get',
                        $_POST['causale'])) . "' LIMIT 1", 'result');

                if (gdrcd_query($check_name, 'num_rows') > 0)
                {
                    gdrcd_query($check_name, 'free');
                    $ok = false;
                    echo '<div class="error">' . gdrcd_filter('out',
                            $MESSAGE['register']['error']['name_taken']) . '</div>';
                } else {
    
    gdrcd_query("UPDATE personaggio SET nome = '$new_name' WHERE nome = '$nome'");
    gdrcd_query("UPDATE appuntamenti SET autore = '$new_name' WHERE autore = '$nome'");
    gdrcd_query("UPDATE BakCalendario SET Destinatario = '$new_name' WHERE Destinatario = '$nome'");
    gdrcd_query("UPDATE clgpersonaggioabilita SET nome = '$new_name' WHERE nome = '$nome'");
    gdrcd_query("UPDATE clgpersonaggiooggetto SET nome = '$new_name' WHERE nome = '$nome'");
    gdrcd_query("UPDATE clgpersonaggioruolo SET personaggio = '$new_name' WHERE personaggio = '$nome'");
    gdrcd_query("UPDATE clgpersonaggiomestiere SET personaggio = '$new_name' WHERE personaggio = '$nome'");
    gdrcd_query("UPDATE clgpersonaggiolavoro SET personaggio = '$new_name' WHERE personaggio = '$nome'");
    gdrcd_query("UPDATE clgpersonaggioinclinazione SET personaggio = '$new_name' WHERE personaggio = '$nome'");
    gdrcd_query("UPDATE clgpersonaggiomostrine SET nome = '$new_name' WHERE nome = '$nome'");
    gdrcd_query("UPDATE log SET nome_interessato = '$new_name' WHERE nome_interessato = '$nome'");
    gdrcd_query("UPDATE log_entrate SET Nome = '$new_name' WHERE Nome = '$nome'");
    gdrcd_query("UPDATE log_doppi SET Nome = '$new_name' WHERE Nome = '$nome'");
    gdrcd_query("UPDATE messaggi SET destinatario = '$new_name' WHERE destinatario = '$nome'");
    gdrcd_query("UPDATE messaggi SET mittente = '$new_name' WHERE mittente = '$nome'");
    gdrcd_query("UPDATE privilegi SET nome = '$new_name' WHERE nome = '$nome'");
    gdrcd_query("UPDATE Punti SET nome = '$new_name' WHERE nome = '$nome'");
    gdrcd_query("UPDATE struttura_affetti SET username = '$new_name' WHERE username = '$nome'");
    gdrcd_query("UPDATE araldo_letto SET nome = '$new_name' WHERE nome = '$nome'");


echo "<script type='text/javascript'>alert('$nome');</script>";
    echo "<script type='text/javascript'>alert('$new_name');</script>";

   //echo "<font color='red'><b>Spirito a $nome cambiato + Talenti cambiati + Messaggio inoltrato</b></font><br>";
    }
    }
    }
    ?>
    
    <center>
<font size=4>Cambia nome a personaggio</font><br/><br/>
          <br><br> 
          <form action="main.php?page=gestione_cambio_nome" method="Post">
          
                        <select name="pg">
                            <?php      
                                while($option = gdrcd_query($all_pg, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }                         
                            gdrcd_query($all_pg, 'free');
                            ?>
                        </select>
                    
                        <input name="causale"/>
 
 <input type="hidden" name="op" value="delete" />
 <input type="submit" value="Cambia nome"> 
  
 </form>

    <?php
    
    
    }//fine