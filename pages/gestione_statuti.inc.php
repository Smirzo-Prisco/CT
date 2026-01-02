<div class="pagina_gestione_abilita">
<?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { ?>

        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo "Modifica Statuti"; ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">

        <?php
        /* Cancellatura in un record */
            if($_POST['op'] == 'erase') {
                /*Eseguo la cancellatura*/
                gdrcd_query("DELETE FROM Statuti WHERE ID=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Modifica di un record*/
            if($_POST['op'] == 'doedit') {
                /*Processo le informazioni ricevute dal form*/
                if(is_numeric($_POST['art']) == true) {
                    /*Eseguo l'aggiornamento*/
                gdrcd_query("UPDATE Statuti SET Titolo ='".gdrcd_filter('in', $_POST['titolo'])."', 
                                                Testo ='".gdrcd_filter('in', $_POST['testo'])."', 
                                                Storia ='".gdrcd_filter('in', $_POST['storia'])."', 
                                                Skill ='".gdrcd_filter('in', $_POST['skill'])."', 
                                                Descrizione ='".gdrcd_filter('in', $_POST['descrizione'])."', 
                                                Incantesimi ='".gdrcd_filter('in', $_POST['incantesimi'])."',
                                                Occultisti ='".gdrcd_filter('in', $_POST['occultisti'])."',
                                                Alchimisti ='".gdrcd_filter('in', $_POST['alchimisti'])."',
                                                Divinatori ='".gdrcd_filter('in', $_POST['divinatori'])."',
                                                Incanti ='".gdrcd_filter('in', $_POST['incanti'])."'
                                                WHERE ID = ". $_POST['art'] ."");                    ?>
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
                    <a href="main.php?page=gestione_statuti">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Form di inserimento/modifica*/
            if(gdrcd_filter('get', $_POST['op'] == 'edit')) {
                /*Se è stata richiesta una modifica*/
                if($_POST['op'] == 'edit') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM Statuti WHERE ID=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                } ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_statuti" method="post" class="form_gestione">
                    
                        <div class='form_field'>
                            <input type = "hidden" name="articolo" value="<?php echo 0 + $loaded_record['ID']; ?>" />
                        </div>    
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['title']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="titolo" value="<?php echo $loaded_record['Titolo']; ?>" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['infos']); ?>
                        </div>
                        <div class='form_field'>
                            <textarea name="testo"><?php echo $loaded_record['Testo']; ?></textarea>
                        </div>
                        
                        <?php 
                        if ($loaded_record['ID'] < 9) {
                        ?>
                        <div class='form_label'>
                        <?php 
                        if ($loaded_record['ID'] < 8) {
                        echo "Storia";
                        } else {
                        echo "Cittadini";
                        }?>
                        </div>
                        <div class='form_field'>
                            <textarea name="storia"><?php echo $loaded_record['Storia']; ?></textarea>
                        </div>
                        
                        <div class='form_label'>
                        <?php 
                        if ($loaded_record['ID'] < 8) {
                        echo "Skill";
                        } else {
                        echo "Wikkan";
                        }?>
                        </div>
                        <div class='form_field'>
                            <textarea name="skill"><?php echo $loaded_record['Skill']; ?></textarea>
                        </div>
                        <?php } ?>    
                        
                        
                        <?php 
                        if ($loaded_record['ID'] == 11 || $loaded_record['ID'] == 12 || $loaded_record['ID'] == 13 || $loaded_record['ID'] == 14) {
                        ?>
                        <?php 
                        if ($loaded_record['ID'] == 14) {
                        ?>
                        <div class='form_label'>
                        Descrizione Secret Pandora
                        </div>
                        <?php } else if ($loaded_record['ID'] == 11) {
                        ?>
                        <div class='form_label'>
                        Descrizione Corte
                        </div>
                        <?php } else if ($loaded_record['ID'] == 12) {
                        ?>
                        <div class='form_label'>
                        Descrizione Centrale J.P.A.
                        </div>
                        <?php } else if ($loaded_record['ID'] == 13) {
                        ?>
                        <div class='form_label'>
                        Descrizione T.A.E.
                        </div>
                        <?php } ?>
                        <div class='form_field'>
                            <textarea name="descrizione"><?php echo $loaded_record['Descrizione']; ?></textarea>
                        </div>
                        <?php } ?>
                        
                        <?php 
                        if ($loaded_record['ID'] == 9 || $loaded_record['ID'] == 14) {
                        ?>
                        <div class='form_label'>
                        <?php
                        if ($loaded_record['ID'] == 9) {
                        echo "Apprendista";
                        } else {
                        echo "Incantesimi del Secret Pandora";
                        }
                        ?>
                        </div>
                        <div class='form_field'>
                            <textarea name="incantesimi"><?php echo $loaded_record['Incantesimi']; ?></textarea>
                        </div>
                        <?php } ?> 
                        
                        
                        
                        
                        
                        <?php 
                        if ($loaded_record['ID'] == 8 || $loaded_record['ID'] == 9 || $loaded_record['ID'] == 14) {
                        ?>
                        <div class='form_label'>
                        <?php 
                        if ($loaded_record['ID'] == 8) {
                        echo "Scorpion";
                        } else if ($loaded_record['ID'] == 9) {
                        echo "Occultisti";
                        } else {
                        echo "Sieri del Secret Pandora";
                        }?>
                        </div>
                        <div class='form_field'>
                            <textarea name="occultisti"><?php echo $loaded_record['Occultisti']; ?></textarea>
                        </div>                        
                        <?php 
                        }
                        ?>
                        
                        <?php 
                        if ($loaded_record['ID'] == 9 || $loaded_record['ID'] == 14 || $loaded_record['ID'] == 13 || $loaded_record['ID'] == 12 || $loaded_record['ID'] == 11) {
                        ?>
                        <div class='form_label'>
                        <?php 
                        if ($loaded_record['ID'] == 9) {
                        echo "Alchimisti";
                        } else if ($loaded_record['ID'] == 13 || $loaded_record['ID'] == 12 || $loaded_record['ID'] == 11) {
                        echo "Cariche";
                        } else {
                        echo "Droghe del Secret Pandora";
                        }?>
                        </div>
                        <div class='form_field'>
                            <textarea name="alchimisti"><?php echo $loaded_record['Alchimisti']; ?></textarea>
                        </div>                        
                        <?php 
                        }
                        ?>
                        
                        <?php 
                        if ($loaded_record['ID'] == 9 || $loaded_record['ID'] == 13 || $loaded_record['ID'] == 12 || $loaded_record['ID'] == 14) {
                        ?>
                        <div class='form_label'>
                        <?php 
                        if ($loaded_record['ID'] == 9) {
                        echo "Divinatori";
                        } else if ($loaded_record['ID'] == 13) {
                        echo "Notorietà";
                        } else if ($loaded_record['ID'] == 14) {
                        echo "Crafter";
                        } else {
                        echo "Equipaggiamento";
                        }?>
                        </div>
                        <div class='form_field'>
                            <textarea name="divinatori"><?php echo $loaded_record['Divinatori']; ?></textarea>
                        </div> 
                        <?php } ?>
                        
                        <?php 
                        if ($loaded_record['ID'] == 9) {
                        ?>
                        <div class='form_label'>
                        Forgiatore
                        </div>
                        <div class='form_field'>
                            <textarea name="incanti"><?php echo $loaded_record['Incanti']; ?></textarea>
                        </div>  
                        <?php 
                        }
                        ?>
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica*/
                            
                                ?>
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['modify']); ?>" />
                                <input type="hidden" name="art" value="<?php echo 0 + $loaded_record['ID']; ?>">
                                <input type="hidden" name="op" value="doedit">
                        </div>
                    </form>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_statuti">
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
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM Statuti");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $result = gdrcd_query("SELECT ID, Titolo FROM Statuti ORDER BY ID LIMIT ".$pagebegin.", ".$pageend."", 'result');
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
                                            <?php echo $row['ID']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo gdrcd_filter('out', $row['Titolo']); ?>
                                        </div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['ID'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                                </form>
                                            </div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_statuti" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['ID'] ?>" />
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
                                <a href="main.php?page=gestione_statuti&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
                
            <?php }//else ?>
        </div>





</div>
<?php }//fine permesso ?>
</div>