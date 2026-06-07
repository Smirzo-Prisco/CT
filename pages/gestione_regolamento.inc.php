<div class="pagina_gestione_abilita">
    <?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1 AND $_SESSION['guida'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { ?>
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['page_name']); ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">

            <?php /*Inserimento di un nuovo record*/
            if($_POST['op'] == 'insert') {
                /*Eseguo l'inserimento*/
                if(is_numeric($_POST['articolo']) == true) {
                    gdrcd_query("INSERT INTO regolamento (articolo, titolo, testo, tipo) VALUES (".gdrcd_filter('num', $_POST['articolo']).", '".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $_POST['testo'])."', '".gdrcd_filter('in', $_POST['tipo'])."')");
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
                    <a href="main.php?page=gestione_regolamento">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un record */
            if($_POST['op'] == 'erase') {
                /*Eseguo la cancellatura*/
                gdrcd_query("DELETE FROM regolamento WHERE articolo=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_regolamento">
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
                gdrcd_query("UPDATE regolamento SET titolo ='".gdrcd_filter('in', $_POST['titolo'])."', testo ='".gdrcd_filter('in', $_POST['testo'])."', tipo ='".gdrcd_filter('in', $_POST['tipo'])."', articolo = ".gdrcd_filter('num', $_POST['articolo'])." WHERE articolo = ".gdrcd_filter('num', $_POST['art'])." LIMIT 1");                    
                
                include ('function_color.php');

	            $string_old = $_POST['old_testo'];
                $string_new = $_POST['testo'];
                $diff = get_decorated_diff($string_old, $string_new);
    
                $id_mex = '264815';   
                $diff_old = mb_substr($diff['old'], 0, 10000);
                $diff_new = mb_substr($diff['new'], 0, 10000);
                $testo_notifica ="Modifica al <b>manuale</b>.<br><br><b>Prima della modifica</b><br>[spoiler]<b>Titolo</b>:". $_POST['old_titolo'] ."<br><b>Testo</b>:". $diff_old ."[/spoiler]
                               <br><br><b>Modifica</b><br>[spoiler]<b>Titolo</b>:". $_POST['titolo'] ."<br><b>Testo</b>:". $diff_new ."[/spoiler]";
             
             //inserisco in bacheca                  
             gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio ) VALUES (".gdrcd_filter('num', $id_mex).", '142', '".gdrcd_filter('in', $testo_notifica)."', 'Coordinazione', 'no', 'no', NOW(), NOW())");
             gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = ".gdrcd_filter('num', $id_mex)." AND nome != '".$_SESSION['login']."'");
             
             //per le guide
             
             if ($_SESSION['guida'] == 1) {
             $id_mex_guide = '250843';
             $testo_guide = "Il personaggio <b>". $_SESSION['login'] ."</b> ha modificato la seguente sezione del manuale: <b>". $_POST['old_titolo'] ."</b>";
             
             //inserisco in bacheca                  
             gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio ) VALUES (".gdrcd_filter('num', $id_mex_guide).", '109', '".gdrcd_filter('in', $testo_guide)."', 'Sistema', 'no', 'no', NOW(), NOW())");
             gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = ".gdrcd_filter('num', $id_mex_guide)." AND nome != '".$_SESSION['login']."'");
             
             }
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
                    <a href="main.php?page=gestione_regolamento">
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
                    $loaded_record = gdrcd_query("SELECT * FROM regolamento WHERE articolo=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                } ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_regolamento" method="post" class="form_gestione">
                    
                        <div class='form_field'>
                            <input type = "hidden" name="articolo" value="<?php echo 0 + $loaded_record['articolo']; ?>" />
                        </div>    
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['title']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="titolo" value="<?php echo $loaded_record['titolo']; ?>" />
                            <input type="hidden" name="old_titolo" value="<?php echo $loaded_record['titolo']; ?>" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['infos']); ?>
                        </div>
                        <div class='form_field'>
                            <textarea name="testo"><?php echo $loaded_record['testo']; ?></textarea>
                            <textarea style="display:none;" name="old_testo"><?php echo $loaded_record['testo']; ?></textarea>
                        </div>
                        <div class="form_info">
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['help']['bbcode']); ?>
                        </div>
                        
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo_info']); ?>
                        </div>
                        <div class='form_field'>
                                <div class="titoli_elenco">
                                <select name="tipo">
                                <option value="ambientazione" <?php if($loaded_record['tipo'] == 'ambientazione') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][4]);//regolamento?></option>
                                <option value="primipassi" <?php if($loaded_record['tipo'] == 'primipassi') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][6]);//regolamento?></option>
                                <option value="regolamento" <?php if($loaded_record['tipo'] == 'regolamento') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][0]);//regolamento?></option>
                                <option value="regolegioco" <?php if($loaded_record['tipo'] == 'regolegioco') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][5]);//regole di gioco?></option>
                                <option value="manuali" <?php if($loaded_record['tipo'] == 'manuali') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][1]);//manuale?></option>
                                <option value="combattimento" <?php if($loaded_record['tipo'] == 'combattimento') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][2]);//combattimento?></option>
                                <option value="staff" <?php if($loaded_record['tipo'] == 'staff') {echo 'selected';} ?>><?php echo gdrcd_filter('out', $MESSAGE['manuale']['tipo'][3]);//staff?></option>
                                </select>
                                </div>
                                
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica*/
                            if($operation == "edit") {
                                ?>
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['modify']); ?>" />
                                <input type="hidden" name="art" value="<?php echo 0 + $loaded_record['articolo']; ?>">
                                <input type="hidden" name="op" value="doedit">
                            <?php } /* Altrimenti il tasto inserisci */ else { ?>
                                <input type="hidden" name="op" value="insert">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                            <?php } ?>
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
            }//if
            if(isset($_REQUEST['op']) === false) { /*Elenco record (Visualizzaione di base della pagina)*/
                //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM regolamento");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                if ($_SESSION['admin'] == 1) {
                $result = gdrcd_query("SELECT articolo, titolo, tipo, testo FROM regolamento ORDER BY articolo LIMIT ".$pagebegin.", ".$pageend."", 'result');
                } else if ($_SESSION['guida'] == 1) {
                $result = gdrcd_query("SELECT articolo, titolo, tipo, testo FROM regolamento WHERE tipo != 'ambientazione' ORDER BY articolo LIMIT ".$pagebegin.", ".$pageend."", 'result');
                }
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
                                    <div class="titoli_elenco">Tipo</div>
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
                                            <?php echo $row['articolo']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['titolo']); ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['tipo']); ?>
                                        </div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_regolamento" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['articolo'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_regolamento" method="post">
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
                            gdrcd_query($result, 'free');
                            ?>
                        </table>
                    </div>
                <?php }//if ?>
                <!-- Paginatore elenco -->
                <div class="pager">
                    <?php if($totaleresults > $PARAMETERS['settings']['records_per_page']) {
                        echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                        for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                            if($i != gdrcd_filter('num', $_REQUEST['offset'])) {
                                ?>
                                <a href="main.php?page=gestione_regolamento&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
                <!-- link crea nuovo -->
                <div class="link_back">
                    <a href="main.php?page=gestione_regolamento&op=new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['new']); ?>
                    </a>
                </div>
            <?php }//else ?>
        </div>
    <?php }//else (controllo permessi utente) ?>
</div><!--Pagina-->
