<div class="pagina_scheda">
    <?php
    /* HELP: E' possibile modificare la scheda agendo su scheda.css nel tema scelto,
     * oppure sostituendo il codice che segue la voce "Scheda del personaggio"
     */
    /********* CARICAMENTO PERSONAGGIO ***********/
    //Se non e' stato specificato il nome del pg
    if(isset($_REQUEST['pg']) === false) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
        exit();
    }
    $query = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5, gilda.nome as nome_gilda, ruolo.nome_ruolo, mestiere.nome as nome_mestiere, ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere FROM personaggio LEFT JOIN razza ON personaggio.id_razza=razza.id_razza LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo WHERE personaggio.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
    $personaggi = gdrcd_query($query, 'result');
    //Se il personaggio non esiste
    if(gdrcd_query($personaggi, 'num_rows') == 0) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
        exit();
    }
    $personaggio = gdrcd_query($personaggi, 'fetch');
    gdrcd_query($personaggio, 'free');
    $bonus_oggetti = gdrcd_query("SELECT SUM(oggetto.bonus_car0) AS BO0, SUM(oggetto.bonus_car1) AS BO1, SUM(oggetto.bonus_car2) AS BO2, SUM(oggetto.bonus_car3) AS BO3, SUM(oggetto.bonus_car4) AS BO4, SUM(oggetto.bonus_car5) AS BO5
            FROM oggetto JOIN clgpersonaggiooggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto
            WHERE clgpersonaggiooggetto.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' AND clgpersonaggiooggetto.posizione > ".ZAINO."");

    //Controllo esilio, se esiliato non visualizzo la scheda
    if($personaggio['esilio'] > strftime('%Y-%m-%d')) {
        echo '<div class="warning">'.gdrcd_filter('out', $personaggio['nome']).' '.gdrcd_filter('out', $personaggio['cognome']).' '.gdrcd_filter('out', $MESSAGE['warning']['character_exiled']).' '.gdrcd_format_date($personaggio['esilio']).' ('.$personaggio['motivo_esilio'].' - '.$personaggio['autore_esilio'].')</div>';
        if($_SESSION['admin']==1 || $_SESSION['moderatore']==1) { ?>
            <div class="panels_box">
                <div class="form_gioco">
                    <form action="main.php?page=scheda_modifica&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']) ?>" method="post">
                        <input type="hidden" value="<?php echo strftime('%Y'); ?>" name="year" />
                        <input type="hidden" value="<?php echo strftime('%m'); ?>" name="month" />
                        <input type="hidden" value="<?php echo strftime('%d'); ?>" name="day" />
                        <input type="hidden" value="<?php gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['unexile']); ?>" name="causale" />
                        <input type="hidden" value="exile" name="op" />
                        <div class="form_label">
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['unexile']); ?>
                        </div>
                        <div class="form_submit">
                            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                        </div>
                    </form>
                </div>
            </div>
        <?php
        }
        exit();
    }

    ?>
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['page_name']); ?></h2>
    </div>
    <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
    <div class="page_body">
        
            
            <div class="background"><!-- Background, affetti, robe varie -->
                <div class="titolo_box">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['background']); ?>
                </div>
                <div class="body_box">
                    <?php
                    /** * Html, bbcode o entrambi ?
                    
                    if($PARAMETERS['mode']['user_bbcode'] == 'ON') {
                        if($PARAMETERS['settings']['user_bbcode']['type'] == 'bbd' && $PARAMETERS['settings']['bbd']['free_html'] == 'ON') {
                            echo bbdecoder(gdrcd_html_filter($personaggio['descrizione']), true);
                        } elseif($PARAMETERS['settings']['user_bbcode']['type'] == 'bbd') {
                            echo bbdecoder(gdrcd_filter('out', $personaggio['descrizione']), true);
                        } else {
                            echo gdrcd_bbcoder(gdrcd_filter('out', $personaggio['descrizione']));
                        }
                    } else {
                        echo gdrcd_html_filter($personaggio['descrizione']);
                    } ?> */
                    
                 echo gdrcd_filter('out', $personaggio['descrizione']);?>
                </div>
            </div>
                   <div class="link_back" style="display: none;">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>">
                <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?>
            </a>
        </div>
</div><!-- Pagina -->
