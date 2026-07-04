<div class="pagina_gestione_mercato">

    <?php /*HELP: Pagina di gestione del mercato */
    $login = $_SESSION['login'];
    $luogo = $_SESSION['luogo'];

    /*Controllo permessi*/
    $mestiere = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    
    if ($mestiere['id_mestiere'] != 3) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else if ($mestiere['id_mestiere'] == 3 && $_SESSION['luogo'] == 24) {
               
    //controllo che ci siano minimo 2 azioni
                            
    $check_negozio_azioni = gdrcd_query("SELECT * FROM chat WHERE stanza = '".$_SESSION['luogo']."' AND mittente = '".$_SESSION['login']."' AND tipo = 'P'", 'result');
    if (gdrcd_query($check_negozio_azioni, 'num_rows') > 1) {

    
     /*Diamo l'oggetto o a pg o al mercato*/
     
     /*Se e' stato richiesto di caricare un oggetto*/
            if($_POST['op'] == 'load') {
                $loaded_item = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto=".gdrcd_filter('num', $_POST['load_item'])."");
                
                $characters = gdrcd_query("SELECT nome FROM personaggio ORDER BY nome", 'result');
            }//fine load
            
            
          if(gdrcd_filter('get', $_POST['op']) == 'assign_market') {
          
          $result = gdrcd_query("SELECT id_oggetto FROM mercato WHERE id_oggetto = ".$_POST['id_oggetto']."", 'result');
          $price = gdrcd_query("UPDATE oggetto SET costo = ".gdrcd_filter('num', $_POST['costo_oggetto'])." WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])."", 'result');

                    if(gdrcd_query($result, 'num_rows') > 0) {
                        gdrcd_query($result, 'free');
                        $query = "UPDATE mercato SET numero = '9999' WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])."";
                    } else {
                        $query = "INSERT INTO mercato (id_oggetto, numero) VALUES (".gdrcd_filter('num', $_POST['id_oggetto']).", '9999')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Oggetto caricato nel mercato');</script>";
                    
                    
           } else if(gdrcd_filter('get', $_POST['op']) == 'assign') {
          
          $result = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_POST['give_item'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE clgpersonaggiooggetto SET numero = numero + ".gdrcd_filter('num', $_POST['num_oggetti'])." WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_POST['give_item'])."'";
                    } else {
          $query = "INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, cariche, numero, isTemp, temp_giorni) VALUES ('".gdrcd_filter('in', $_POST['give_item'])."', ".gdrcd_filter('num', $_POST['id_oggetto']).", ".gdrcd_filter('num', $_POST['cariche']).", ".gdrcd_filter('num', $_POST['num_oggetti']).", ".gdrcd_filter('num', $_POST['isTemp']).", ".gdrcd_filter('num', $_POST['temp_giorni']).")";
                    }
                    gdrcd_query($query);
           
          //assegno punto_mestiere
          
          $edit_px = gdrcd_query("UPDATE personaggio SET esperienza_mestiere = esperienza_mestiere + 1 WHERE nome = '". $_SESSION['login'] ."'");
          $id_nome = gdrcd_query ("SELECT * FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE oggetto.id_oggetto = ". $_POST['id_oggetto'] ."");          //inserisco in chat
          $messaggio = "$login ha venduto a ". $_POST['give_item'] ." il seguente oggetto: ". $id_nome['nome'] .".";
          
          gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$login', NOW(), 'C', '$messaggio')");
          
          echo "<script type='text/javascript'>alert('Oggetto assegnato a ".$_POST["give_item"]." e punto mestiere aggiunto');</script>";
          
          
          
          }
          
          
        $elenco_oggetti = gdrcd_query("SELECT id_oggetto, nome FROM oggetto ORDER BY nome", 'result');
        $elenco_magic = gdrcd_query("SELECT id_oggetto, nome, tipo FROM oggetto WHERE tipo = 8 ORDER BY nome", 'result');
        $elenco_gilda = gdrcd_query("SELECT id_oggetto, nome, tipo FROM oggetto WHERE tipo = 15 ORDER BY nome", 'result');
        $tipi_oggetto = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');
    
           ?>
           
           <!-- Iniziamo la costruzione della pagina -->
           
           <div class="panels_box">
            <!-- Elenco degli oggetti esistenti -->
            <div class="panels_box">
                <form class="form_gestione" action="main.php?page=oggetto_assegna_chat" method="post">
                    <div class='form_label'>
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['load_item']); ?>
                    </div>
                    <div class='form_field'>
                        <?php if(gdrcd_query($elenco_oggetti, 'num_rows') > 0) { ?>
                            <select name="load_item">
                             <?php                            
                            //facciamo visualizzare a magic solo la sua categoria//

                            if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1 && $mestiere['id_mestiere'] == 3) {


                                while($option = gdrcd_query($elenco_magic, 'fetch')) { ?>
                                    <option value="<?php echo $option['id_oggetto']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                    </option>
                                <?php }
                                } else if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1 && $_SESSION['capogilda'] == 1) {
                                
                                while($option = gdrcd_query($elenco_gilda, 'fetch')) { ?>
                                    <option value="<?php echo $option['id_oggetto']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                    </option>
                                <?php }
                                } else {
                            
                                //sei master/admin e puoi modificare tutto
                                while($option = gdrcd_query($elenco_oggetti, 'fetch')) { ?>
                                    <option value="<?php echo $option['id_oggetto']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                    </option>
                                <?php }
                                }//fine if
                                gdrcd_query($elenco_oggetti, 'free');
                                gdrcd_query($elenco_magic, 'free');
                                gdrcd_query($elenco_gilda, 'free');
                                ?>
                            </select>
                        <?php } ?>
                    </div>
                    <input type="hidden" name="op" value="load" />
                    <div class='form_submit'>
                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                    </div>
                </form>
            </div>
            
            <?php if ($_POST['load_item'] != "") { ?>
           
           
           <!-- ASSEGNA A PERSONAGGIO -->
           <br>
           <hr>
           <br>
           <form class="form_gestione" action="main.php?page=oggetto_assegna_chat" method="post">
           <div class='form_label'>
                    <?php echo "Assegna oggetto a personaggio"; ?>
                </div>
           <div class='form_field'>
                            <?php if(gdrcd_query($characters, 'num_rows') > 0) { ?>
                                <select name="give_item">
                                    <?php while($option = gdrcd_query($characters, 'fetch')) { ?>
                                        <option value="<?php echo $option['nome']; ?>">
                                            <?php echo gdrcd_filter('out', $option['nome']); ?>
                                        </option>
                                    <?php }
                                    gdrcd_query($characters, 'free');
                                    ?>
                                </select>
                            <?php } ?>
                        </div>
                        
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['number_item']); ?>
                        </div>
                        <div class='form_field'>
                            <input type="text" name="num_oggetti" value="0" />
                        </div>
                        
                        <div class='form_label'>
                            <?php echo "Cariche (-1 è infinito)" ?>
                        </div>
                        <div class='form_field'>
                            <input type="text" name="cariche" value="-1" />
                        </div>
                        
                        
                        <input type="hidden" name="id_oggetto" value="<?php echo $loaded_item['id_oggetto']; ?>" />
                        <input type="hidden" name="isTemp" value="<?php echo $loaded_item['isTemp']; ?>" />
                        <input type="hidden" name="temp_giorni" value="<?php echo $loaded_item['temp_giorni']; ?>" />
                        <input type="hidden" name="op" value="assign" />
                        <div class='form_submit'>
                            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                        </div>
                        </form>
           
           
            
            <?php
            }//fine load
            }//fine if permessi
            }//fine if azioni chat
            
