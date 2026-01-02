<div class="pagina_gestione_gilde">
    <?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin']!=1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { ?>
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['page_name']); ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">
            <?php /*Inserimento di un nuovo ruolo nella mestiere corrente*/
            if(gdrcd_filter('get', $_POST['op']) == 'nuovo_ruolo') {
                /*Processo le informazioni ricevute dal form*/
                $is_capo = (isset($_POST['capo']) == true) && ($_POST['capo'] == 'is_capo') ? 1 : 0;

                #$immagine = ($_POST['immagine'] == '') ? "standard_mestiere.png" : gdrcd_filter('in', $_POST['immagine']);
                if($_FILES['img_oggetto']['name'] == '') {
                    $immagine_oggetto = 'standard_gilda.png';
                } else {
                    $immagine_oggetto = $_FILES['img_oggetto']['name'];
                }
                /*Eseguo l'inserimento*/
                gdrcd_query("INSERT INTO ruolo_mestiere (nome_ruolo, mestiere, immagine, stipendio, capo, livello_mestiere) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".gdrcd_filter('num', $_POST['mestiere']).", '".gdrcd_filter('in', $immagine_oggetto)."', '".gdrcd_filter('num', $_POST['stipendio'])."', '".$is_capo."', '".gdrcd_filter('num', $_POST['livello_mestiere'])."')");
                upload_image();
                ?>
                <!-- Conferma -->
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri&op=edit&id_record=<?php echo gdrcd_filter('num', $_POST['mestiere']); ?>">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Inserimento di un nuovo record*/
            if(gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['submit']['insert']) {
                /*Processo le informazioni ricevute dal form*/
                $is_visible = ((isset($_POST['visible']) == true) && ($_POST['visible'] == 'is_visible')) ? 1 : 0;

                $url_sito = ((isset($_POST['url_sito']) == true) && ($_POST['url_sito'] == 'http://')) ? '' :  $_POST['url_sito'];

                #$immagine = ($_POST['immagine'] == '') ? "standard_mestiere.png" : gdrcd_filter('in', $_POST['immagine']);
                if($_FILES['img_oggetto']['name'] == '') {
                    $immagine_oggetto = 'standard_gilda.png';
                } else {
                    $immagine_oggetto = $_FILES['img_oggetto']['name'];
                }
                /*Eseguo l'inserimento*/
                gdrcd_query("INSERT INTO mestiere (nome, tipo, immagine, url_sito, visibile, statuto) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".gdrcd_filter('in', $_POST['tipo']).", '".gdrcd_filter('in', $immagine_oggetto)."', '".gdrcd_filter('in', $_POST['url_sito'])."', '".$is_visible."', '".gdrcd_filter('in', $_POST['statuto'])."')");
                upload_image();
                ?>
                <!-- Conferma -->
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un record */
            if(gdrcd_filter('get', $_POST['op']) == 'erase') {
                /*Eseguo la cancellatura*/
                $result = gdrcd_query("SELECT id_ruolo FROM ruolo_mestiere WHERE mestiere = ".gdrcd_filter('num', $_POST['id_record'])."", 'result');

                while($row = gdrcd_query($result, 'fetch')) {
                    gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo=".gdrcd_filter('num', $row['id_ruolo'])."");
                }
                gdrcd_query($result, 'free');
                gdrcd_query("DELETE FROM ruolo_mestiere WHERE mestiere = ".gdrcd_filter('num', $_POST['id_record'])."");
                gdrcd_query("DELETE FROM mestiere WHERE id_mestiere=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                ?>
                <!-- Conferma -->
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un ruolo */
            if((gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['role']['submit']['delete']) && ($_POST['provenienza'] == 'ruolo')) { /*Eseguo la cancellatura*/
                gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo=".gdrcd_filter('num', $_POST['id_ruolo'])."");
                gdrcd_query("DELETE FROM ruolo_mestiere WHERE id_ruolo=".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");
                ?>
                <!-- Conferma -->
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri&op=edit&id_record=<?php echo gdrcd_filter('num', $_POST['mestiere']) ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?></a>
                </div>
            <?php
            }
            /*Modifica di un record*/
            if((gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['submit']['edit']) && (isset($_POST['provenienza']) == false)) {
                /*Processo le informazioni ricevute dal form*/
                $is_visible = ((isset($_POST['visible']) == true) && ($_POST['visible'] == 'is_visible')) ? 1 : 0;

                $url_sito = ((isset($_POST['url_sito']) == true) && ($_POST['url_sito'] == 'http://')) ? '' :  $_POST['url_sito'];

                $immagine = ($_POST['immagine'] == '') ? "standard_mestiere.png" : gdrcd_filter('in', $_POST['immagine']);

                /*Eseguo l'aggiornamento*/
                gdrcd_query("UPDATE mestiere SET nome ='".gdrcd_filter('in', $_POST['nome'])."', visibile = ".$is_visible.", immagine = '".gdrcd_filter('in', $immagine)."', tipo = ".gdrcd_filter('in', $_POST['tipo']).", url_sito = '".gdrcd_filter('in', $url_sito)."', statuto='".gdrcd_filter('in', $_POST['statuto'])."' WHERE id_mestiere = ".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                ?>
                <!-- Conferma -->
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Modifica di un ruolo*/
            if((gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['role']['submit']['edit']) && ($_POST['provenienza'] == 'ruolo')) {
                /*Processo le informazioni ricevute dal form*/
                $is_capo = (isset($_POST['capo']) == true) && ($_POST['capo'] == 'is_capo') ? 1 : 0;

                #$immagine = ($_POST['immagine'] == '') ? "standard_mestiere.png" : gdrcd_filter('in', $_POST['immagine']);
                /*Eseguo l'aggiornamento*/
                #gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo ='".gdrcd_filter('in', $_POST['nome'])."', capo = ".$is_capo.", immagine = '".gdrcd_filter('in', $immagine)."', mestiere = ".gdrcd_filter('num', $_POST['mestiere']).", stipendio = ".gdrcd_filter('num', $_POST['stipendio'])." WHERE id_ruolo = ".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");
                
                if($_FILES['img_oggetto']['name'] == '') {
                    /*Eseguo l'aggiornamento SENZA immagine*/
                gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo ='".gdrcd_filter('in', $_POST['nome'])."', capo = ".$is_capo.", mestiere = ".gdrcd_filter('num', $_POST['mestiere']).", livello_mestiere = ".gdrcd_filter('num', $_POST['livello_mestiere']).", stipendio = ".gdrcd_filter('num', $_POST['stipendio'])." WHERE id_ruolo = ".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");

                } else {
                    $immagine_oggetto = $_FILES['img_oggetto']['name'];
                    /*Eseguo l'aggiornamento CON immagine*/
                gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo ='".gdrcd_filter('in', $_POST['nome'])."', capo = ".$is_capo.", immagine = '".gdrcd_filter('in', $immagine_oggetto)."', mestiere = ".gdrcd_filter('num', $_POST['mestiere']).", livello_mestiere = ".gdrcd_filter('num', $_POST['livello_mestiere']).", stipendio = ".gdrcd_filter('num', $_POST['stipendio'])." WHERE id_ruolo = ".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");
                upload_image();
                }
                
                ?>
                <!-- Conferma -->
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri&op=edit&id_record=<?php echo $_POST['mestiere'] ?>">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Form di inserimento/modifica*/
            if((gdrcd_filter('get', $_REQUEST['op']) == 'edit') || (gdrcd_filter('get', $_REQUEST['op']) == 'new')) {
                /*Preseleziono l'operazione di inserimento*/
                $operation = 'insert';
                /*Se è stata richiesta una modifica*/
                if((gdrcd_filter('get', $_REQUEST['op']) == 'edit') && (gdrcd_filter('get', $_REQUEST['id_record'] > -1))) {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM mestiere WHERE id_mestiere=".gdrcd_filter('get', $_REQUEST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                }//if

                if((isset($_REQUEST['id_record']) === false) || (gdrcd_filter('get', $_REQUEST['id_record'] > -1))) { ?>
                    <!-- Form di inserimento/modifica -->
                    <div class="panels_box">
                        <form action="main.php?page=gestione_mestieri" method="post" class="form_gestione" enctype="multipart/form-data">
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['name']); ?>
                            </div>
                            <div class='form_field'>
                                <input name="nome" value="<?php echo gdrcd_filter('out', $loaded_record['nome']); ?>" />
                            </div>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['type']); ?>
                            </div>
                            <div class='form_field'>
                                <?php /* Carico l'elenco dei tipi di mestiere */
                                $tipi = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipomestiere", 'result');
                                /*Se sono presenti tipi sul database*/
                                if(gdrcd_query($tipi, 'num_rows') > 0) { ?>
                                    <!-- Elenco dei tipi -->
                                    <select name="tipo">
                                        <?php while($option = gdrcd_query($tipi, 'fetch')) { ?>
                                            <option value="<?php echo $option['cod_tipo']; ?>" <?php if($loaded_record['tipo'] == $option['cod_tipo']) {echo 'SELECTED';} ?>>
                                                <?php echo gdrcd_filter('out', $option['descrizione']); ?>
                                            </option>
                                        <?php }
                                        gdrcd_query($tipi, 'free');
                                        ?>
                                    </select>
                                <?php
                                } else { /*Altrimenti segnalo l'assenza di tipi*/
                                    echo gdrcd_filter('out', $MESSAGE['interface']['administration']['locations']['type_err']);
                                } ?>
                            </div>
                            <div class="link_back">
                                <a href="main.php?page=gestione_tipi&types=jobs">
                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['menage_types']); ?>
                                </a>
                            </div>

                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['image']); ?>
                            </div>
                            <div class='form_field'>
                    <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
                            </div>

                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['site']); ?>
                            </div>
                            <div class='form_field'>
                                <input name="url_sito" value="<?php if(isset($loaded_record['url_sito']) === true) {
                                           echo gdrcd_filter('out', $loaded_record['url_sito']);
                                       } else {
                                           echo "http://";
                                       } ?>" />
                            </div>
                            <div class='form_label'>
                                Statuto
                            </div>
                            <div class='form_field'><textarea name="statuto"><?php echo gdrcd_filter('out', $loaded_record['statuto']); ?></textarea>
                            </div>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['visible']); ?>
                            </div>
                            <div class='form_field'>
                                <input type="checkbox" name="visible"
                                    <?php if(gdrcd_filter('out', $loaded_record['visibile']) == 1) { ?>
                                        checked="checked"
                                    <?php } ?>
                                       value="is_visible" />
                            </div>
                            <div class='form_info'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['visible_info']); ?>
                            </div>
                            <!-- bottoni -->
                            <div class='form_submit'>
                                <?php /* Se l'operazione è una modifica stampo i tasti modifica e annulla */
                                if($operation == "edit") {  ?>
                                    <input type="hidden" name="id_record" value="<?php echo gdrcd_filter('out', $loaded_record['id_mestiere']); ?>">
                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['submit']['edit']); ?>" name="op" />
                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['submit']['undo']); ?>" name="cancel" />
                                <?php
                                } else { /* Altrimenti il tasto inserisci */ ?>
                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['submit']['insert']); ?>" name="op" />
                                <?php
                                } ?>
                            </div>
                        </form>
                    </div>
                <?php
                }//if
                if((gdrcd_filter('get', $_REQUEST['op']) == 'edit') && (isset($_REQUEST['id_record']) === true)) { ?>
                    <!-- Titolo della pagina -->
                    <div class="page_title">
                        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['page_name']); ?></h2>
                    </div>
                    <div class="page_body">
                        <?php $id_mestiere_padre = (0) ? -1 : gdrcd_filter('get', $_REQUEST['id_record']); ?>
                        <!-- Nuovo ruolo -->
                        <form action="main.php?page=gestione_mestieri" method="post" class="form_gestione" enctype="multipart/form-data">
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['name_new']); ?>
                            </div>
                            <div class='form_field'>
                                <input name="nome" value="" />
                            </div>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['image']); ?>
                            </div>
                            <div class='form_field'>
                    <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
                            </div>
                            <div class='form_label'>
                            Livello (3 il più basso, 1 il più alto):
                            </div>
                            <div class='form_field'>
                                <input name="livello_mestiere" value="3" />
                            </div>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['pay']); ?>
                            </div>
                            <div class='form_field'>
                                <input name="stipendio" value="0" />
                            </div>
                            <?php if(gdrcd_filter('get', $_REQUEST['id_record'] > -1)) { ?>
                                <div class='form_label'>
                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['head']); ?>
                                </div>
                                <div class='form_field'>
                                    <input type="checkbox" name="capo" value="is_capo" />
                                </div>
                            <?php } else { ?>
                                <div class='form_field'>
                                    <input type="hidden" name="capo" value="is_not_capo" />
                                </div>
                            <?php } ?>
                            <div class='form_info'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['head_info']); ?>
                            </div>
                            <div class='form_submit'>
                                <input type="hidden" name="mestiere" value="<?php echo gdrcd_filter('out', $id_mestiere_padre); ?>" />
                                <input type="hidden" name="op" value="nuovo_ruolo" />
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['submit']['insert']); ?>" name="submit" />
                            </div>
                        </form>
                        <?php /*Carico i ruoli della mestiere corrende*/
                        $result = gdrcd_query("SELECT * FROM ruolo_mestiere WHERE mestiere=".gdrcd_filter('num', $id_mestiere_padre)." ORDER BY capo DESC, stipendio DESC", 'result');
                        /*Elenco ruoli*/
                        while($row = gdrcd_query($result, 'fetch')) { ?>
                            <form action="main.php?page=gestione_mestieri" method="post" class="form_gestione" enctype="multipart/form-data">
                                <div class="elenco_record_gestione">
                                    <table>
                                        <tr>
                                            <td>
                                                <div class='titoli_elenco'>
                                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='titoli_elenco'>
                                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['image']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='titoli_elenco'>
                                                    Livello (3 più basso)
                                                </div>
                                            </td>
                                            <td>
                                                <div class='titoli_elenco'>
                                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['pay']); ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class='form_field'>
                                                    <input name="nome" value="<?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class='form_field'>
                                            <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
                                            </div>
                                            </td>
                                            <td>
                                                <div class='form_field'>
                                                    <input name="livello_mestiere" value="<?php echo 0 + gdrcd_filter('out', $row['livello_mestiere']); ?>" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class='form_field'>
                                                    <input name="stipendio" value="<?php echo 0 + gdrcd_filter('out', $row['stipendio']); ?>" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php if(gdrcd_filter('get', $_REQUEST['id_record'] > -1)) { ?>
                                                    <div class='form_label'>
                                                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['head']); ?>
                                                    </div>
                                                <?php } else { ?>
                                                    &nbsp;
                                                <?php } ?>
                                            </td>
                                            <td>
                                            </td>
                                            <td>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php if(gdrcd_filter('get', $_REQUEST['id_record'] > -1)) { ?>
                                                    <div class='form_field'>
                                                        <input type="checkbox" name="capo" <?php if($row['capo'] == 1) {echo 'checked';} ?> value="is_capo" />
                                                    </div>
                                                <?php } else { ?>
                                                    <div class='form_field'>
                                                        <input type="hidden" name="capo" value="is_not_capo" />
                                                    </div>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <div class='form_submit'>
                                                    <input type="hidden" name="provenienza" value="ruolo" />
                                                    <input type="hidden" name="id_ruolo" value="<?php echo gdrcd_filter('out', $row['id_ruolo']); ?>" />
                                                    <input type="hidden" name="mestiere" value="<?php echo gdrcd_filter('out', $id_mestiere_padre); ?>" />
                                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['submit']['edit']); ?>" name="op" />
                                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['submit']['delete']); ?>" name="op" />
                                                </div>
                                            </td>
                                            <td>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <!-- elenco_record_gestione -->
                            </form>
                        <?php
                        }//while
                        gdrcd_query($result, 'free');
                        ?>
                    </div>
                <?php
                }//if
                ?>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }//if
            if((isset($_POST['op']) === false) && (isset($_REQUEST['op']) === false)) { /*Elenco record (Visualizzaione di base della pagina)*/
                //Determinazione pagina (paginazione)
                $pagebegin = (int) gdrcd_filter('get', $_REQUEST['offset']) * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM mestiere");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $result = gdrcd_query("SELECT mestiere.id_mestiere, mestiere.nome, mestiere.visibile, codtipomestiere.descrizione FROM mestiere LEFT JOIN codtipomestiere ON mestiere.tipo = codtipomestiere.cod_tipo ORDER BY nome LIMIT ".$pagebegin.", ".$pageend."", 'result');
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
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['type']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['visible']); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                            </tr>
                            <!-- Record -->
                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                <tr>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco"><?php echo gdrcd_filter('out', $row['nome']); ?></div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco"><?php echo gdrcd_filter('out', $row['descrizione']); ?></div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco"><?php if($row['visibile'] == 1) {
                                                echo gdrcd_filter('out', $MESSAGE['interface']['administration']['yes']);
                                            } else {
                                                echo gdrcd_filter('out', $MESSAGE['interface']['administration']['no']);
                                            } ?></div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione"
                                                      action="main.php?page=gestione_mestieri" method="post">
                                                    <input type="hidden" name="id_record"
                                                           value="<?php echo gdrcd_filter('out', $row['id_mestiere']) ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image"
                                                           src="imgs/icons/edit.png"
                                                           alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione"
                                                      action="main.php?page=gestione_mestieri" method="post">
                                                    <input type="hidden" name="id_record"
                                                           value="<?php echo gdrcd_filter('out', $row['id_mestiere']) ?>" />
                                                    <input type="hidden" name="op" value="erase" />
                                                    <input type="image"
                                                           src="imgs/icons/erase.png"
                                                           alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']
                                                           ); ?>" />
                                                </form>
                                            </div>
                                            <div class="controlli_elenco">
                                    </td>
                                </tr>
                            <?php
                            } //while
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
                            if($i != gdrcd_filter('num', $_REQUEST['offset'])) { ?>
                                <a href="main.php?page=gestione_mestieri&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
                <!-- link crea nuovo -->
                <div class="link_back">
                    <a href="main.php?page=gestione_mestieri&op=new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['new']); ?>
                    </a><br />
                    <a href="main.php?page=gestione_mestieri&op=edit&id_record=-1">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['new_role']); ?>
                    </a><br />
                    <a href="main.php?page=gestione_tipi&types=jobs">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['link']['menage_types']); ?>
                    </a>
                </div>
            <?php
            }//else
            ?>
        </div><!-- page_body -->
    <?php
    }//else (controllo permessi utente)
    
    //FILES
    
    function upload_image(){
$allow = array("jpg", "jpeg", "gif", "png");

$todir = 'imgs/mestieri/';
$test = $_FILES['img_oggetto']['name'];

if ( !!$_FILES['img_oggetto']['tmp_name'] ) // is the file uploaded yet?
{
    $info = explode('.', strtolower( $_FILES['img_oggetto']['name']) ); // whats the extension of the file

    if ( in_array( end($info), $allow) ) // is this file allowed
    {
        if ( move_uploaded_file( $_FILES['img_oggetto']['tmp_name'], $todir . basename($_FILES['img_oggetto']['name'] ) ) )
        {
            // the file has been moved correctly
        }
    }
    else
    {
        // error this file ext is not allowed
    }
}
}

?>
</div><!-- pagina -->