<div class="pagina_gestione_razze">
<?php
// Controllo permessi utente
if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
} else { ?>

    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['page_name']); ?></h2>
    </div>

    <!-- Corpo della pagina -->
    <div class="page_body">

    <?php
    // Form di scelta del log (se non è stato ancora scelto nulla)
    if (!isset($_POST['op']) && !isset($_REQUEST['op'])) { ?>

        <div class="panels_box">
            <div class="form_gestione">
                <form action="main.php?page=log_eventi" method="post">
                    <div class="form_label">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['log_type']); ?>
                    </div>
                    <div class="form_field">
                        <select name="which_log">
                            <?php
                            foreach ($MESSAGE['event'] as $key => $event) { ?>
                                <option value="<?php echo $key; ?>"><?php echo $event; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form_submit">
                        <input type="hidden" name="op" value="view" />
                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                    </div>
                </form>
            </div>
        </div>

    <?php } // end if op non settato ?>


    <?php
    // Visualizzazione elenco log
    if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'view' && is_numeric($_REQUEST['which_log'])) {

        // Paginazione
        $offset = (isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0);
        $pagebegin = $offset * $PARAMETERS['settings']['records_per_page'];
        $pageend = $PARAMETERS['settings']['records_per_page'];

        // Conteggio totale
        $record_globale = gdrcd_query("SELECT COUNT(*) FROM log WHERE codice_evento = ".gdrcd_filter('num', $_REQUEST['which_log']));
        $totaleresults = $record_globale['COUNT(*)'];

        // Lettura record
        $result = gdrcd_query("SELECT autore, nome_interessato, data_evento, descrizione_evento 
                               FROM log 
                               WHERE codice_evento = ".gdrcd_filter('num', $_REQUEST['which_log'])." 
                               ORDER BY data_evento DESC 
                               LIMIT $pagebegin, $pageend", 'result');
        $numresults = gdrcd_query($result, 'num_rows');

        if ($numresults > 0) { ?>

            <div class="elenco_record_gestione">
                <table>
                    <tr>
                        <td class="casella_titolo"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['author']); ?></td>
                        <td class="casella_titolo"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['dest']); ?></td>
                        <td class="casella_titolo"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['date']); ?></td>
                        <td class="casella_titolo"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['descr']); ?></td>
                    </tr>

                    <?php while ($row = gdrcd_query($result, 'fetch')) { ?>
                        <tr class="risultati_elenco_record_gestione">
                            <td class="casella_elemento"><?php echo gdrcd_filter('out', $row['autore']); ?></td>
                            <td class="casella_elemento">
                                <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome_interessato']); ?>">
                                    <?php echo gdrcd_filter('out', $row['nome_interessato']); ?>
                                </a>
                            </td>
                            <td class="casella_elemento"><?php echo gdrcd_format_date($row['data_evento']) . ' ' . gdrcd_format_time($row['data_evento']); ?></td>
                            <td class="casella_elemento"><?php echo gdrcd_filter('out', $row['descrizione_evento']); ?></td>
                        </tr>
                    <?php } ?>

                    <?php gdrcd_query($result, 'free'); ?>
                </table>
            </div>

        <?php } else { ?>
            <div class="warning">Nessun record trovato.</div>
        <?php } ?>

        <!-- Paginatore -->
        <div class="pager">
        <?php
        if ($totaleresults > $PARAMETERS['settings']['records_per_page']) {
            echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
            for ($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                if ($i != $offset) {
                    echo ' <a href="main.php?page=log_eventi&op=view&which_log='.$_REQUEST['which_log'].'&offset='.$i.'">'.($i+1).'</a>';
                } else {
                    echo ' '.($i+1).' ';
                }
            }
        }
        ?>
        </div>

        <div class="link_back">
            <a href="main.php?page=log_eventi"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['link']['back']); ?></a>
        </div>

    <?php } // end if op=view
    ?>

    </div><!-- end page_body -->

<?php } // end permessi ?>
</div><!--Pagina-->
