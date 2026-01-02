<div class="pagina_gestione_abilita">
    <?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin']!=1 && $_SESSION['master']!=1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } elseif($PARAMETERS['mode']['skillsystem'] == 'OFF') {
        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['unactive']).'</div>';
    } else { ?>
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['page_name']); ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">
            <?php /*Inserimento di un nuovo record*/
            if($_POST['op'] == 'insert') {
                /*Eseguo l'inserimento*/
                gdrcd_query("INSERT INTO abilita (nome, descrizione, max_lvl, id_razza, tipo) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '".gdrcd_filter('in', $_POST['descrizione'])."', ".gdrcd_filter('num', $_POST['max_lvl']).", '0', 'Temporanea')");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un record */
            if(gdrcd_filter('get', $_POST['op']) == 'erase') {
                /*Eseguo la cancellatura*/
                gdrcd_query("DELETE FROM abilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");

                /*Aggiorno i personaggi*/
                gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])."");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Modifica di un record*/
            if(gdrcd_filter('get', $_POST['op']) == 'modify') {
                /*Eseguo l'aggiornamento*/
                gdrcd_query("UPDATE abilita SET nome ='".gdrcd_filter('in', $_POST['nome'])."', descrizione ='".gdrcd_filter('in', $_POST['descrizione'])."', max_lvl = ".gdrcd_filter('num', $_POST['max_lvl']).", id_razza = '0', tipo = 'Temporanea' WHERE id_abilita = ".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1"); ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Form di inserimento/modifica*/
            if((gdrcd_filter('get', $_POST['op']) == 'edit') || (gdrcd_filter('get', $_REQUEST['op']) == 'new')) {
                /*Preseleziono l'operazione di inserimento*/
                $operation = 'insert';
                /*Se è stata richiesta una modifica*/
                if(gdrcd_filter('get', $_POST['op']) == 'edit') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM abilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                } ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_abilita_master" method="post" class="form_gestione">
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['name']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="nome" value="<?php echo $loaded_record['nome']; ?>" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['infos']); ?>
                        </div>
                        <div class='form_field'>
                            <textarea name="descrizione"><?php echo $loaded_record['descrizione']; ?></textarea>
                        </div>
                        <div class='form_label'>
                            <?php echo 'Liv. max'; ?>
                        </div>
                        <div class='form_field'>
                            <select name='max_lvl'>
                                <option value="0" <?php if($loaded_record['max_lvl'] == 0) { echo 'SELECTED'; } ?>>
                                    0</option>
                                <option value="1" <?php if($loaded_record['max_lvl'] == 1) { echo 'SELECTED'; } ?>>
                                    1</option>
                                <option value="2" <?php if($loaded_record['max_lvl'] == 2) { echo 'SELECTED'; } ?>>
                                    2</option>
                                <option value="3" <?php if($loaded_record['max_lvl'] == 3) { echo 'SELECTED'; } ?>>
                                    3</option>
                                <option value="4" <?php if($loaded_record['max_lvl'] == 4) { echo 'SELECTED'; } ?>>
                                    4</option>
                                <option value="5" <?php if($loaded_record['max_lvl'] == 5) { echo 'SELECTED'; } ?>>
                                    5</option>
                            </select>
                        </div>

                        
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica e annulla */
                            if($operation == "edit") { ?>
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                                <input type="hidden" name="op" value="modify" />
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['submit']['edit']); ?>" />
                            <?php
                            }  else {  /* Altrimenti il tasto inserisci */ ?>
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['submit']['insert']); ?>" />
                                <input type="hidden" name="op" value="insert" />
                            <?php
                            } ?>
                        </div>
                    </form>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php }
            /*assegno skill*/
                if(gdrcd_filter('get', $_REQUEST['op']) == 'assegna') {
            $elenco_skill = gdrcd_query("SELECT id_abilita, nome FROM abilita WHERE tipo = 'Temporanea' ORDER BY nome", 'result');
            $characters = gdrcd_query("SELECT nome FROM personaggio WHERE esperienza > 0 ORDER BY nome", 'result');
            ?>
            <div class="panels_box">
            <form class="form_gestione" action="main.php?page=gestione_abilita_master" method="post">
            <div class='form_label'>
            <?php echo 'Skill temporanee'; ?>
            </div>
            <div class='form_field'>
            <?php if(gdrcd_query($elenco_skill, 'num_rows') > 0) { ?>
            <select name="load_item">
            <?php                            
            while($option = gdrcd_query($elenco_skill, 'fetch')) { ?>
                                    <option value="<?php echo $option['id_abilita']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                    </option>
            <?php }
            gdrcd_query($elenco_skill, 'free');
            ?>
                            </select>
                        <?php } ?>
                    </div>
            <div class='form_label'>
                    <?php echo "Assegna skill a personaggio"; ?>
                </div>
            <div class='form_field'>
                            <?php if(gdrcd_query($characters, 'num_rows') > 0) { ?>
                                <select name="give_skill">
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
                    <?php echo "Numero usi"; ?>
                </div>
            <div class='form_field'>
                            <select name='usi'>
                                <option value="1">
                                    1</option>
                                <option value="2">
                                    2</option>
                                <option value="3">
                                    3</option>
                                <option value="4">
                                    4</option>
                                <option value="5">
                                    5</option>
                            </select>
                        </div>
                        <input type="hidden" name="op" value="assign_pg" />
                        <div class='form_submit'>
                        <input type="submit" value="Assegna a pg" />
                        </div>
            </form>
            </div>
            <?php
            }
            if(gdrcd_filter('get', $_POST['op']) == 'assign_pg') {
          $query = "INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado, usi) VALUES ('".gdrcd_filter('in', $_POST['give_skill'])."', ".gdrcd_filter('num', $_POST['load_item']).", '1', ".gdrcd_filter('num', $_POST['usi']).")";
          gdrcd_query($query);
          ?>
          <div class="warning">
                    <?php echo "Skill temporanea correttamente inserita<br>"; ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
                <?php
                }
            //if
            if(isset($_REQUEST['op']) === false) { /*Elenco record (Visualizzaione di base della pagina)*/
                //Determinazione pagina (paginazione)
                $pagebegin = (int) gdrcd_filter('get', $_REQUEST['offset']) * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM abilita WHERE tipo = 'Temporanea'");
                $totaleresults = $record_globale['COUNT(*)'];

                //Lettura record
                $result = gdrcd_query("SELECT id_abilita, nome, max_lvl, id_razza FROM abilita WHERE tipo = 'Temporanea' ORDER BY id_abilita LIMIT ".$pagebegin.", ".$pageend."", 'result');
                $numresults = gdrcd_query($result, 'num_rows');

                /* Se esistono record */
                if($numresults > 0) { ?>
                    <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['name_col']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo 'Liv. Max'; ?></div>
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
                                            <?php echo $row['nome']; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['max_lvl']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_abilita_master" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" src="imgs/icons/edit.png" />
                                                </form>
                                            </div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_abilita_master" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="op" value="erase" />
                                                    <input type="image" src="imgs/icons/erase.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" />
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
                }//if
                ?>
                <!-- Paginatore elenco -->
                <div class="pager">
                    <?php if($totaleresults > $PARAMETERS['settings']['records_per_page']) {
                        echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                        for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                            if($i != gdrcd_filter('num', $_REQUEST['offset'])) {
                                ?>
                                <a href="main.php?page=gestione_abilita_master&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php
                            } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>

                <!-- link crea nuovo -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master&op=new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['new']); ?>
                    </a>
                </div>
                <!-- link crea nuovo -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_master&op=assegna">
                        <?php echo 'Assegna a personaggio'; ?>
                    </a>
                </div>
            <?php
            }//else
            ?>
        </div>
    <?php
    }//else (controllo permessi utente) ?>
</div><!--Pagina-->