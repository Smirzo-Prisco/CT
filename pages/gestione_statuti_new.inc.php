<div class="pagina_gestione_abilita">
    <?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1 && $_SESSION['capogilda'] != 1 && $_SESSION['capomestiere'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { ?>
    
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['page_name']); ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">
        
        <!-- Pagine gilda -->
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        <?php /*Visiono solo gli articoli della gilda*/
            if($_POST['op'] == 'view') {
        
               /*Carico il record da modificare*/
                $loaded_gilda = gdrcd_query("SELECT * FROM statuti WHERE id_gilda=".gdrcd_filter('num', $_POST['id_gilda'])."", 'result');
               //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM statuti");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $numresults = gdrcd_query($loaded_gilda, 'num_rows');             
        
        
                /* Se esistono record */
                if($numresults > 0) { ?>
                    <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['titolo']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                                
                            </tr>
                            <!-- Record -->
                            <?php while($row = gdrcd_query($loaded_gilda, 'fetch')) { ?>
                                <tr class="risultati_elenco_record_gestione">
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['articolo']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['titolo']); ?>
                                        </div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['articolo'] ?>" />
                                                    <input type="hidden" name="id_gilda" value="<?php echo $_POST['id_gilda'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            <div class="controllo_elenco"></div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['articolo'] ?>" />
                                                    <input type="hidden" name="op" value="erase" />
                                                    <input type="image" src="imgs/icons/erase.png"
                                                           alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" />
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } //while
                            gdrcd_query($loaded_gilda, 'free');
                            ?>
                        </table>
                    </div>
                <?php }//if
                ?>
                <!-- Paginatore elenco -->
                <div class="pager">
                    <?php if($totaleresults > $PARAMETERS['settings']['records_per_page']) {
                        echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                        for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                            if($i != gdrcd_filter('num', $_REQUEST['offset'])) {
                                ?>
                                <a href="main.php?page=gestione_statuti_new&id=<?php echo $_POST['id_gilda'] ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
                <!-- link crea nuovo -->
                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                <div class="link_back">
                <input type="hidden" name="op" value="new">
                <input type="hidden" name="id_gilda" value="<?php echo $_POST['id_gilda'] ?>" />
                <input type="submit" style="width: 200px;" value="NUOVO PARAGRAFO" />                            
                </div>
                </form>
                <?PHP
                }//op view?>
        
                 
        
        
        
        
        
        
        
        
        
        
        
        
            <?php /*Inserimento di un nuovo record*/
            if($_POST['op'] == 'insert') {
                /*Eseguo l'inserimento*/
                if(is_numeric($_POST['articolo']) == true) {
                    gdrcd_query("INSERT INTO statuti (articolo, titolo, testo, tipo, id_gilda) VALUES (".gdrcd_filter('num', $_POST['articolo']).", '".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $_POST['testo'])."', '".gdrcd_filter('in', $_POST['tipo'])."', '".gdrcd_filter('num', $_POST['id_gilda'])."')");
                    ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
                    </div>
                <?php } else { ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['cant_do']); ?>
                    </div>
                <?php } ?>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un record */
            if($_POST['op'] == 'erase') {
                /*Eseguo la cancellatura*/
                gdrcd_query("DELETE FROM statuti WHERE articolo=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Modifica di un record*/
            if($_POST['op'] == 'doedit') {
                /*Processo le informazioni ricevute dal form*/
                if((is_numeric($_POST['art']) == true) && (is_numeric($_POST['articolo']) == true)) {
                    /*Eseguo l'aggiornamento*/                
             gdrcd_query("UPDATE statuti SET titolo ='".gdrcd_filter('in', $_POST['titolo'])."', testo ='".gdrcd_filter('in', $_POST['testo'])."', tipo ='".gdrcd_filter('in', $_POST['tipo'])."', articolo = ".gdrcd_filter('num', $_POST['articolo'])." WHERE articolo = ".gdrcd_filter('num', $_POST['art'])." LIMIT 1");                    
             /*Inserisco l'aggiornamento*/
             if ($_POST['id_gilda'] == 1) {
             $id_mex = '243202';
             } elseif ($_POST['id_gilda'] == 2) {
             $id_mex = '243207';
             } elseif ($_POST['id_gilda'] == 3) {
             $id_mex = '243205';
             } elseif ($_POST['id_gilda'] == 4) {
             $id_mex = '243204';
             } elseif ($_POST['id_gilda'] == 5) {
             $id_mex = '243203';
             } elseif ($_POST['id_gilda'] == 6) {
             $id_mex = '243206';
             } elseif ($_POST['id_gilda'] == 7) {
             $id_mex = '243208';
             }
             
             $testo_notifica ="Modifica allo <b>statuto</b>.<br><br><b>Prima della modifica</b><br>[spoiler]<b>Titolo</b>:". $_POST['old_titolo'] ."<br><b>Testo</b>:". $_POST['old_testo'] ."[/spoiler]
                               <br><br><b>Modifica</b><br>[spoiler]<b>Titolo</b>:". $_POST['titolo'] ."<br><b>Testo</b>:". $_POST['testo'] ."[/spoiler]";
                               
             gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio ) VALUES (".gdrcd_filter('num', $id_mex).", '142', '".gdrcd_filter('in', $testo_notifica)."', '".gdrcd_filter('in', $_SESSION['login'])."', 'no', 'no', NOW(), NOW())");
             gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = ".gdrcd_filter('num', $id_mex)." AND nome != '".$_SESSION['login']."'");
             ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
                    </div>
                <?php } else { ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['cant_do']); ?>
                    </div>
                <?php } ?>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            
            
            
            
            
            
            
            
            /*Form di inserimento/modifica*/
            if((gdrcd_filter('get', $_POST['op'] == 'edit')) || (gdrcd_filter('get', $_REQUEST['op']) == 'new')) {
                /*Preseleziono l'operazione di inserimento*/
                $operation = 'insert';
                /*Se è stata richiesta una modifica*/
                if($_POST['op'] == 'edit') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM statuti WHERE articolo=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                } ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_statuti_new" method="post" class="form_gestione">
                    
                        <div class='form_field'>
                            <input type = "hidden" name="articolo" value="<?php echo 0 + $loaded_record['articolo']; ?>" />
                        </div>    
                        <div class='form_label'>
                          Titolo
                        </div>
                        <div class='form_field'>
                            <input name="titolo" value="<?php echo $loaded_record['titolo']; ?>" />
                            <input type="hidden" name="old_titolo" value="<?php echo $loaded_record['titolo']; ?>" />
                        </div>
                        <div class='form_label'>
                        Testo
                        </div>
                        <div class='form_field'>
                            <textarea name="testo"><?php echo $loaded_record['testo']; ?></textarea>
                            <textarea style="display:none;" name="old_testo"><?php echo $loaded_record['testo']; ?></textarea>
                        </div>

                        <div class='form_field'>
                                <div class="titoli_elenco">
                                <select name="tipo">
                                <option value="storia" <?php if($loaded_record['tipo'] == 'storia') {echo 'selected';} ?>>Storia</option>
                                <option value="statuto" <?php if($loaded_record['tipo'] == 'statuto') {echo 'selected';} ?>>Statuto</option>
                                <option value="skill" <?php if($loaded_record['tipo'] == 'skill') {echo 'selected';} ?>>Skill</option>
                                <option value="requisiti" <?php if($loaded_record['tipo'] == 'requisiti') {echo 'selected';} ?>>Requisiti</option>
                                </select>
                                </div>
                       
                              
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica*/
                            if($operation == "edit") {
                                ?>
                               <input type="hidden" name="id_gilda" value="<?php echo $loaded_record['id_gilda']; ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['modify']); ?>" />
                                <input type="hidden" name="art" value="<?php echo 0 + $loaded_record['articolo']; ?>">
                                <input type="hidden" name="op" value="doedit">
                            <?php } /* Altrimenti il tasto inserisci */ else { ?>
                                <input type="hidden" name="op" value="insert">
                               <input type="hidden" name="id_gilda" value="<?php echo $_POST['id_gilda'] ?>" />
                               <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                            <?php } ?>
                        </div>
                        </div>
                    </form>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_regolamento">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
                
                
                
                
       
       <?php
       }//edit/new
       if(isset($_REQUEST['op']) === false) { /*Elenco record (Visualizzaione di base della pagina)*/ 
        
        
        //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM gilda");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $result = gdrcd_query("SELECT * FROM gilda ORDER BY id_gilda LIMIT ".$pagebegin.", ".$pageend."", 'result');
                $numresults = gdrcd_query($result, 'num_rows');
        
                /* Se esistono record */
                if($numresults > 0) { ?>
        
                <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['titolo']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                                
                            </tr>
                            <!-- Record -->
                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                <tr class="risultati_elenco_record_gestione">
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['id_gilda']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['nome']); ?>
                                        </div>
                                    </td>
                                    
                                    <?php $checksoldi = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
                                    if ($checksoldi['id_gilda'] == $row['id_gilda']) {
                                    ?>

                                       <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_gilda" value="<?php echo $row['id_gilda'] ?>" />
                                                    <input type="hidden" name="op" value="view" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            
                                        </div>
                                    </td>
                                    <?php } else if ($_SESSION['admin'] == 1) { ?>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_gilda" value="<?php echo $row['id_gilda'] ?>" />
                                                    <input type="hidden" name="op" value="view" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            
                                        </div>
                                    </td>
                                    <?php } ?>
                                </tr>
                            <?php } //while
                            gdrcd_query($result, 'free');
                            ?>
                        </table>
                    </div>
        
        
        <?php
        }//numresults
        } /*IF ISSET FALSE*/
        ?>
    <?php }//else (controllo permessi utente) ?>












<!--MESTIERI-->


    <?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1 && $_SESSION['capomestiere'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { ?>


        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        <?php /*Visiono solo gli articoli della gilda*/
            if($_POST['op'] == 'view_mestiere') {
        
               /*Carico il record da modificare*/
                $loaded_gilda = gdrcd_query("SELECT * FROM statuti WHERE id_mestiere=".gdrcd_filter('num', $_POST['id_mestiere'])."", 'result');
               //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM statuti");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $numresults = gdrcd_query($loaded_gilda, 'num_rows');             
        
        
                /* Se esistono record */
                if($numresults > 0) { ?>
                    <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['titolo']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                                
                            </tr>
                            <!-- Record -->
                            <?php while($row = gdrcd_query($loaded_gilda, 'fetch')) { ?>
                                <tr class="risultati_elenco_record_gestione">
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['articolo']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['titolo']); ?>
                                        </div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['articolo'] ?>" />
                                                    <input type="hidden" name="id_mestiere" value="<?php echo $_POST['id_mestiere'] ?>" />
                                                    <input type="hidden" name="op" value="edit_mestiere" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            <div class="controllo_elenco"></div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['articolo'] ?>" />
                                                    <input type="hidden" name="op" value="erase_mestiere" />
                                                    <input type="image" src="imgs/icons/erase.png"
                                                           alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" />
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } //while
                            gdrcd_query($loaded_mestiere, 'free');
                            ?>
                        </table>
                    </div>
                <?php }//if
                ?>
                <!-- Paginatore elenco -->
                <div class="pager">
                    <?php if($totaleresults > $PARAMETERS['settings']['records_per_page']) {
                        echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                        for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                            if($i != gdrcd_filter('num', $_REQUEST['offset'])) {
                                ?>
                                <a href="main.php?page=gestione_statuti_new&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
                <!-- link crea nuovo -->
                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                <div class="link_back">
                <input type="hidden" name="op" value="new_mestiere">
                <input type="hidden" name="id_mestiere" value="<?php echo $_POST['id_mestiere'] ?>" />
                <input type="submit" style="width: 200px;" value="NUOVO PARAGRAFO" />                            
                </div>
                </form>
                <?PHP
                }//op view?>
        
                 
        
        
        
        
        
        
        
        
        
        
        
        
            <?php /*Inserimento di un nuovo record*/
            if($_POST['op'] == 'insert_mestiere') {
                /*Eseguo l'inserimento*/
                if(is_numeric($_POST['articolo']) == true) {
                    gdrcd_query("INSERT INTO statuti (articolo, titolo, testo, tipo, id_mestiere) VALUES (".gdrcd_filter('num', $_POST['articolo']).", '".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $_POST['testo'])."', '".gdrcd_filter('in', $_POST['tipo'])."', '".gdrcd_filter('num', $_POST['id_mestiere'])."')");
                    ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
                    </div>
                <?php } else { ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['cant_do']); ?>
                    </div>
                <?php } ?>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un record */
            if($_POST['op'] == 'erase_mestiere') {
                /*Eseguo la cancellatura*/
                gdrcd_query("DELETE FROM statuti WHERE articolo=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Modifica di un record*/
            if($_POST['op'] == 'doedit_mestiere') {
                /*Processo le informazioni ricevute dal form*/
                if((is_numeric($_POST['art']) == true) && (is_numeric($_POST['articolo']) == true)) {
                    /*Eseguo l'aggiornamento*/
             gdrcd_query("UPDATE statuti SET titolo ='".gdrcd_filter('in', $_POST['titolo'])."', testo ='".gdrcd_filter('in', $_POST['testo'])."', tipo ='".gdrcd_filter('in', $_POST['tipo'])."', articolo = ".gdrcd_filter('num', $_POST['articolo'])." WHERE articolo = ".gdrcd_filter('num', $_POST['art'])." LIMIT 1");                    ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
                    </div>
                <?php } else { ?>
                    <div class="warning">
                        <?php echo gdrcd_filter('out', $MESSAGE['warning']['cant_do']); ?>
                    </div>
                <?php } ?>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            
            
            
            
            
            
            
            
            /*Form di inserimento/modifica*/
            if((gdrcd_filter('get', $_POST['op'] == 'edit_mestiere')) || (gdrcd_filter('get', $_REQUEST['op']) == 'new_mestiere')) {
                /*Preseleziono l'operazione di inserimento*/
                $operation = 'insert_mestiere';
                /*Se è stata richiesta una modifica*/
                if($_POST['op'] == 'edit_mestiere') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM statuti WHERE articolo=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit_mestiere';
                } ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_statuti_new" method="post" class="form_gestione">
                    
                        <div class='form_field'>
                            <input type = "hidden" name="articolo" value="<?php echo 0 + $loaded_record['articolo']; ?>" />
                        </div>    
                        <div class='form_label'>
                          Titolo
                        </div>
                        <div class='form_field'>
                            <input name="titolo" value="<?php echo $loaded_record['titolo']; ?>" />
                        </div>
                        <div class='form_label'>
                        Testo
                        </div>
                        <div class='form_field'>
                            <textarea name="testo"><?php echo $loaded_record['testo']; ?></textarea>
                        </div>

                        <div class='form_field'>
                                <div class="titoli_elenco">
                                <select name="tipo">
                                <option value="storia" <?php if($loaded_record['tipo'] == 'storia') {echo 'selected';} ?>>Statuto</option>
                                <option value="statuto" <?php if($loaded_record['tipo'] == 'statuto') {echo 'selected';} ?>>Descrizione</option>
                                <option value="skill" <?php if($loaded_record['tipo'] == 'skill') {echo 'selected';} ?>>Cariche</option>
                                <option value="requisiti" <?php if($loaded_record['tipo'] == 'requisiti') {echo 'selected';} ?>>Specifiche</option>
                                </select>
                                </div>
                       
                              
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica*/
                            if($operation == "edit_mestiere") {
                                ?>
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['modify']); ?>" />
                                <input type="hidden" name="art" value="<?php echo 0 + $loaded_record['articolo']; ?>">
                                <input type="hidden" name="op" value="doedit_mestiere">
                            <?php } /* Altrimenti il tasto inserisci */ else { ?>
                                <input type="hidden" name="op" value="insert_mestiere">
                               <input type="hidden" name="id_mestiere" value="<?php echo $_POST['id_mestiere'] ?>" />
                               <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                            <?php } ?>
                        </div>
                        </div>
                    </form>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti_new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
                
                
                
                
       
       <?php
       }//edit/new
       if(isset($_REQUEST['op']) === false) { /*Elenco record (Visualizzaione di base della pagina)*/ 
        
        
        //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM mestiere");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $result = gdrcd_query("SELECT * FROM mestiere ORDER BY id_mestiere LIMIT ".$pagebegin.", ".$pageend."", 'result');
                $numresults = gdrcd_query($result, 'num_rows');
        
                /* Se esistono record */
                if($numresults > 0) { ?>
        
                <!-- Elenco dei record paginato -->
                 <hr><br>
                 <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['titolo']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                                
                            </tr>
                            <!-- Record -->
                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                <tr class="risultati_elenco_record_gestione">
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['id_mestiere']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['nome']); ?>
                                        </div>
                                    </td>
                                    
                                    
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti_new" method="post">
                                                    <input type="hidden" name="id_mestiere" value="<?php echo $row['id_mestiere'] ?>" />
                                                    <input type="hidden" name="op" value="view_mestiere" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            
                                        </div>
                                    </td>
                                </tr>
                            <?php } //while
                            gdrcd_query($result, 'free');
                            ?>
                        </table>
                    </div>
        
        
        <?php
        }//numresults
        } /*IF ISSET FALSE*/
        ?>
        </div>   
    <?php }//else (controllo permessi utente) ?>
</div><!--Pagina-->

