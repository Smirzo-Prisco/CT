<div class="pagina_scheda">
    <?php /* Permessi */
    if ($_SESSION['login'] == $_REQUEST['pg'] || $_SESSION['admin'] == 1) {
    ?>

    <?php

    //Se non e' stato specificato il nome del pg
    if (isset($_REQUEST['pg']) === false)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknonw_character_sheet']) . '</div>';
    } else
    {
    /*Visualizzo la pagina*/
    /*Verifico l'esistenza del PG*/
    $query = "SELECT nome FROM personaggio WHERE personaggio.nome = '" . gdrcd_filter('in', $_REQUEST['pg']) . "'";
    $result = gdrcd_query($query, 'result');
    //Se non esiste il pg
    if (gdrcd_query($result, 'num_rows') == 0)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']) . '</div>';
    }
    else
    {

    gdrcd_query($result, 'free');

    $num_logs = $PARAMETERS['settings']['view_logs'];
    ?>

    <!-- Riepilogo PX -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['px']['page_name']); ?></h2>
    </div>

    </div>
            <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
    <div class="pagina_scheda_px">
    
    <?php /* Assegnamento PX*/
    if ($_POST['op'] == "assegna")
    {
        if ((is_numeric($_POST['px']) === true) && ($_SESSION['admin']==1 || $_SESSION['moderatore']==1 || $_SESSION['master']==1))
        {
            gdrcd_query("UPDATE personaggio SET esperienza = esperienza + " . gdrcd_filter('num',
                    $_POST['px']) . " WHERE nome = '" . gdrcd_filter('in', $_REQUEST['pg']) . "' LIMIT 1 ");

            /*Registro l'operazione*/
            gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('" . gdrcd_filter('in',
                    $_REQUEST['pg']) . "', '" . $_SESSION['login'] . "', NOW(), " . PX . ", '(" . gdrcd_filter('num',
                    $_POST['px']) . ' px) ' . gdrcd_filter('in', $_POST['causale']) . "')");
            echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['done']) . '</div>';
        } else
        {
            echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['camt_do']) . '</div>';
        }
    } ?>


    <div class="page_body">
        <div class="panels_box">
            <?php 
            if(isset($_REQUEST['op']) === false)
            {
            //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['view_logs'];
                $pageend = $PARAMETERS['settings']['view_logs'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM PuntiMestiere WHERE nome = '" . gdrcd_filter('in',
                        $_REQUEST['pg']) . "'");
                $totaleresults = $record_globale['COUNT(*)'];
                
                /*Seleziono le ultime 20 assegnamzioni px*/

           
                       ?>
            <!-- Intestazione tabella elenco -->
            <div class="elenco_record_gioco">
                <table class="customTable">
                    <tr>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['px']['date']); ?>

                            </div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                Titolo
                            </div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                               Commento
                            </div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                Punti Shin
                            </div>
                        </td>
                    </tr>


                    <?php //Lettura record
                $result = gdrcd_query("SELECT * FROM Punti LEFT JOIN messaggioaraldo ON messaggioaraldo.id_messaggio = Punti.id_messaggio WHERE nome = '" . gdrcd_filter('in', 
                          $_REQUEST['pg']) . "' AND shin > 0 ORDER BY data_evento DESC LIMIT ".$pagebegin.", ".$pageend."", 'result');
                 $numresults = gdrcd_query($result, 'num_rows');

                /* Se esistono record */
                if($numresults > 0) {
                
                while ($record = gdrcd_query($result, 'fetch'))
                    { 
                    
                     if ($record['id_messaggio'] == 0) {
                     $record['titolo'] = "Assegnazione shin";
                     }
                     ?>

                        <tr>
                            <!-- Oggetto, immagine, quantità -->
                            
                            <td class="casella_elemento">
                                <div class="elementi_elenco"><?php echo gdrcd_filter('out',
                                        gdrcd_format_date($record['data_evento'])); ?></div>
                            </td>
                            
                            <td class="casella_elemento">
                                <div class="elementi_elenco"><?php echo gdrcd_filter('out',
                                        $record['titolo']); ?></div>
                            </td>
                            
                            <td class="casella_elemento">
                                <div class="elementi_elenco"><?php echo gdrcd_filter('out', 
                                        $record['commento']); ?></div>
                            </td>
                            
                            <td class="casella_elemento">
                                <div class="elementi_elenco"><?php echo gdrcd_filter('out', 
                                        $record['shin']); ?></div>
                            </td>                          
                            

                        </tr>
                    <?php }//while

                    gdrcd_query($result, 'free');
                    ?>
                </table>

            </div>

           <!-- <?php if ($_SESSION['admin']==1 || $_SESSION['moderatore']==1 || $_SESSION['master']==1)
            { ?>
                <div class="form_gioco">
                    <form action="main.php?page=scheda_px"
                          method="post">
                        <div class="form_label"><?php echo gdrcd_filter('out',
                                $MESSAGE['interface']['sheet']['px']['why']); ?></div>
                        <div class="form_field"><input name="causale"/></div>
                        <div class="form_label"><?php echo gdrcd_filter('out',
                                $MESSAGE['interface']['sheet']['px']['px']); ?></div>
                        <div class="form_field"><input name="px" value="0"/></div>
                        <div class="form_submit">
                            <input type="submit" value="<?php echo gdrcd_filter('out',
                                $MESSAGE['interface']['forms']['submit']); ?>"/>
                            <input type="hidden" value="assegna" name="op"/>
                            <input type="hidden"
                                   value="<?php echo gdrcd_filter('get', $_REQUEST['pg']); ?>"
                                   name="pg"/>
                        </div>
                    </form>
                </div>
            <?php } ?>
                              -->
        </div>
        
        <!-- Paginatore elenco -->
                <div class="pager">
                    <?php if ($totaleresults > $PARAMETERS['settings']['view_logs'])
                    {
                        echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                        for ($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['view_logs']); $i++)
                        {
                            if ($i != $_REQUEST['offset'])
                            {
                                ?>
                                <a href="main.php?page=scheda_px_shin&pg=<?php echo $_REQUEST['pg']; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else
                            {
                                echo ' ' . ($i + 1) . ' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
                
                
        <!-- Link a piè di pagina 
        <div class="link_back">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url',
                $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out',
                    $MESSAGE['interface']['sheet']['link']['back']); ?></a>
        </div> -->


        <?php
        /********* CHIUSURA SCHEDA **********/
        }//else

        }//else
        
        }//op
        
        }
        ?>
    </div>
    <?php } else { ?>
    Ce sta un motivo se 'sto link nun compare :)<br>
    Nun fa er furb*!<br><br>
    <?php } ?>
    <!-- Link a piè di pagina -->
        <div class="link_back">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?></a>
        </div>
        
            
</div><!-- Pagina -->