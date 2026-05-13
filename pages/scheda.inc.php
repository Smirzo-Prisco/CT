<link rel="stylesheet" href="themes/<?=$PARAMETERS['themes']['current_theme']?>/scheda.css?<?=time()?>" type="text/css">

<div class="pagina_scheda">
    <?php
        require_once(__DIR__ . '/../includes/custom_functions.inc.php');

        /* HELP: E' possibile modificare la scheda agendo su scheda.css nel tema scelto,
        * oppure sostituendo il codice che segue la voce "Scheda del personaggio"
        */
        /********* CARICAMENTO PERSONAGGIO ***********/
        
        error_log('[SCHEDA] pg=' . ($_REQUEST['pg'] ?? 'NON_SET') . ' login=' . ($_SESSION['login'] ?? 'NO_SESSION'));

        // Se non e' stato specificato il nome del pg
        if(isset($_REQUEST['pg']) === false) {
            error_log('[SCHEDA] RETURN: pg non impostato');
            echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
            return;
        }
        $query = "SELECT
                    personaggio.*,
                    razza.sing_m,
                    razza.sing_f,
                    razza.id_razza,
                    razza.bonus_car0,
                    razza.bonus_car1,
                    razza.bonus_car2,
                    razza.bonus_car3,
                    razza.bonus_car4,
                    razza.bonus_car5,
                    gilda.nome as nome_gilda,
                    ruolo.nome_ruolo,
                    mestiere.nome as nome_mestiere,
                    ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere,
                    ruolo.immagine as immagine_famiglia,
                    ruolo_mestiere.immagine as immagine_mestiere
                FROM personaggio
                LEFT JOIN razza ON personaggio.id_razza = razza.id_razza
                LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda
                LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo
                LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere
                LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo
                WHERE personaggio.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
        $personaggi = gdrcd_query($query, 'result');

        // Se il personaggio non esiste
        if(gdrcd_query($personaggi, 'num_rows') == 0) {
            error_log('[SCHEDA] RETURN: personaggio non trovato per pg=' . $_REQUEST['pg']);
            echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
            return;
        }

        $pg = gdrcd_query($personaggi, 'fetch');
        gdrcd_query($personaggi, 'free');
        error_log('[SCHEDA] pg caricato: ' . $pg['nome']);

        $bonus_oggetti = gdrcd_query("SELECT SUM(oggetto.bonus_car0) AS BO0, SUM(oggetto.bonus_car1) AS BO1, SUM(oggetto.bonus_car2) AS BO2, SUM(oggetto.bonus_car3) AS BO3, SUM(oggetto.bonus_car4) AS BO4, SUM(oggetto.bonus_car5) AS BO5
                FROM oggetto JOIN clgpersonaggiooggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto
                WHERE clgpersonaggiooggetto.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' AND clgpersonaggiooggetto.posizione > ".ZAINO."");

        // Controllo esilio, se esiliato non visualizzo la scheda
        if($pg['esilio'] > strftime('%Y-%m-%d')) {
            echo '<div class="warning">'.gdrcd_filter('out', $pg['nome']).' '.gdrcd_filter('out', $pg['cognome']).' '.gdrcd_filter('out', $MESSAGE['warning']['character_exiled']).' '.gdrcd_format_date($pg['esilio']).' ('.$pg['motivo_esilio'].' - '.$pg['autore_esilio'].')</div>';
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
            return;
        }
    ?>
    <div class="page_title"><h2><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['page_name'])?></h2></div>
    
    <div class="scheda_page_body">
        <?php
            if($PARAMETERS['mode']['alert_password_change'] == 'ON') {
                $six_months = 15552000;
                $ts_signup = strtotime($pg['data_iscrizione']);
                $ts_lastpass = (int) strtotime($pg['ultimo_cambiopass']);
                if($ts_lastpass + $six_months < time() && $pg['nome'] == $_SESSION['login']) {
                    $message = ($ts_signup + $six_months < time()) ? $MESSAGE['warning']['changepass'] : $MESSAGE['warning']['changepass_signup'];
                    echo '<div class="warning">'.$message.'</div>';
                }
            }
        ?>
        <div class="menu_scheda"><?php include ('scheda/menu.inc.php'); ?></div> <!-- MENU SCHEDA -->
        <div class="title"><?=gdrcd_filter('out', $pg['nome']).' '.gdrcd_filter('out', $pg['cognome'])?></div> <!-- NOME COGNOME -->
        <div class="pg-infos">
            <!--
            <div class="ritratto">
                <div class="titolo_box"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['portrait'])?></div>
                <div class="ritratto_nome">
                    <span class="ritratto_nome_nome"><?=gdrcd_filter('out', $pg['nome'])?></span>
                    <span class="ritratto_nome_cognome"><?=gdrcd_filter('out', $pg['cognome'])?></span>
                </div>
            </div>
            -->
            <div class="ritratto_avatar"><img src="<?=gdrcd_filter('fullurl', $pg['url_img'])?>" class="ritratto_avatar_immagine" /></div>
            <!-- nome, ritratto, ultimo ingresso, abiti portati -->
            <div class="profilo"><!-- Punteggi, salute, status, classe, razza. -->
                <div class="titolo_box"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['profile'])?></div>
               
                <div class="primo_box">
                    <div class="header_box">▪ PROFILO ▪</div>
                    <br>
                    <span style="float: left; margin-left: 5px;">Età:</span>    
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['eta'])?></span>
                    <br>
                    
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['place'])?>:</span>    
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['natoa'])?></span>
                    <br>
                
                    <?php
                        $job = gdrcd_query("SELECT * FROM clgpersonaggiolavoro WHERE personaggio = '".$_REQUEST['pg']."'", 'result');
                        if(gdrcd_query($job, 'num_rows') > 0) {
                            $lavoro = "SELECT clgpersonaggiolavoro.*, personaggio.nome, ruolo_mestiere.*
                                        FROM ruolo_mestiere
                                        JOIN clgpersonaggiolavoro ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo
                                        JOIN personaggio ON personaggio.nome = clgpersonaggiolavoro.personaggio
                                        WHERE personaggio.nome ='".$_REQUEST['pg']."'";
                            $good_done = gdrcd_query($lavoro, 'result');
                            $work = gdrcd_query($good_done, 'fetch');
                            ?>

                            <span style="float: left; margin-left: 5px;">Lavoro:</span>
                            <span style="float: right; margin-right: 5px; text-align: right;"><?=$work['nome_ruolo']?></span>
                            <br>
                    <?php }//fine Lavoro ?>
                
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['race']['sing'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php if((empty($pg['sing_f']) == false) || (empty($pg['sing_m']) == false)) {
                            echo ($pg['sesso'] == 'f') ? gdrcd_filter('out', $pg['sing_f']) : gdrcd_filter('out', $pg['sing_m']);
                        } else {
                            echo gdrcd_filter('out', $PARAMETERS['names']['race']['sing'].' '.$MESSAGE['interface']['sheet']['profile']['no_race']);
                        } ?>
                    </span>
                    <br>

                    <span style="float: left; margin-left: 5px;">Famiglia:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['nome_ruolo'])?></span>
                    <br>

                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['job_role'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['nome_ruolo_mestiere'])?></span>
                    <br><br>

                    <?php 
                    if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) {
                    ?>
                        
                    <div class="header_box">▪ STATISTICHE ▪</div>
                    <br>
                    <!-- caratteristiche -->
                    <!-- EX FORZA
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car0'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['car0'] + $pg['bonus_car0'] + $bonus_oggetti['BO0'])?></span>
                    ADESSO LIVELLO -->
                    <span style="float:left; margin-left:5px; position:relative;">
                        Livello 
                        <span class="help-animated">?</span>
                        
                        <!-- TOOLTIP QUI DENTRO! -->
                        <div class="tooltip-animated">
                            <strong>Il livello si calcola in base alla somma di tutte le statistiche, se il personaggio appartiene ad una famiglia</strong><br>
                            <table style="width:100%; margin-top:5px; border-collapse: collapse; text-align:center;">
                                <tr><th class="form-group form-column">Livello</th><th class="form-group form-column">Fino a</th></tr>
                                <?php
                                try {
                                    $soglie = gdrcd_query("SELECT * FROM gilda_soglie ORDER BY livello", 'result', true);
                                    foreach ($soglie as $soglia): ?>
                                        <tr><td><?=$soglia['livello']?></td><td><?=$soglia['soglia']?></td></tr>
                                    <?php endforeach;
                                } catch (Exception $e) { /* tabella non trovata */ } ?>
                            </table>
                        </div>
                    </span>
                    <span style="float:right; margin-right:5px; text-align:right;color:yellow; font-weight:bold;"><?=$pg['id_gilda'] > 0 ? getLevelPg(getTotStatsPg($pg['nome'])) : 1?></span>
                    <br><br>
                
                    <span style="float:left; margin-left:5px; position:relative;">Tot. Caratteristiche</span>
                    <span style="float:right; margin-right:5px; text-align:right;font-weight:bold;"><?=getTotStatsPg($pg['nome'])?></span>
                    <br>

                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car8'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['car8'] + $pg['bonus_car8'] + $bonus_oggetti['BO8'])?></span>
                    <br>
                
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car2'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['car2'] + $pg['bonus_car2'] + $bonus_oggetti['BO2'])?></span>
                    <br>
                    
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car4'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['car4'] + $pg['bonus_car4'] + $bonus_oggetti['BO4'])?></span>
                    <br>
                    
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car6'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['car6'] + $pg['bonus_car6'] + $bonus_oggetti['BO6'])?></span>
                    <br>
                    
                    <?php } ?>
                        
                    <div class="header_box">▪ INFO ▪</div><br>
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['hitpoints'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['salute']).'/'.gdrcd_filter('out', $pg['salute_max'])?></span>
                    <br>
                    
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['integrita'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', $pg['integrita']).'/'.gdrcd_filter('out', $pg['integrita_max'])?></span>

                    <?php if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) : ?>
                        <br>
                        <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['experience'])?>:</span>
                        <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', floor($pg['esperienza']))?></span>
                        <br>

                        <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['shin'])?>:</span>
                        <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', floor($pg['shin']))?></span>
                    <? endif; ?>
                    <br>
                    <span style="float: left; margin-left: 5px;"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['notorieta'])?>:</span>
                    <span style="float: right; margin-right: 5px; text-align: right;"><?=gdrcd_filter('out', floor($pg['notorieta']))?></span>
                    <br>
                    
                    <span style="float: left; margin-left: 5px;">Cariche</span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                    <?php // ICONA RUOLO STAFF
                        $nomeUtente = gdrcd_filter('out', $_REQUEST['pg']);
                        $row_pri = gdrcd_query("SELECT * FROM privilegi WHERE nome = '{$nomeUtente}'");

                        // Mappa dei ruoli con le loro icone
                        $ruoli = [
                            'admin' => ['file' => 'Admin.png', 'alt' => 'Admin'],
                            'moderatore' => ['file' => 'Moderatore.png', 'alt' => 'Moderatore'],
                            'master' => ['file' => 'Master.png', 'alt' => 'Master'],
                            'guida' => ['file' => 'Guida.png', 'alt' => 'Guida'],
                            'grafico' => ['file' => 'Grafico.png', 'alt' => 'Grafico']
                        ];

                        // Ciclo unico per tutti i ruoli
                        foreach ($ruoli as $ruolo => $info) {
                            if (!empty($row_pri[$ruolo]) && $row_pri[$ruolo] == 1) {
                                echo '<img src="themes/crystal/imgs/staff/' . $info['file'] . 
                                    '" width="20" height="20" alt="' . $info['alt'] . '" title="' . $info['alt'] . '">';
                            }
                        }
                    ?>
                    </span>
                    <br>
                    <br>
                </div>
            </div>
        </div>

        <div class="pg-infos">
            <div class="secondo_box"><!-- Background, affetti, robe varie -->
                <div class="titolo_box_scheda" onclick="$('#NoteEFato').toggle();" style="cursor: pointer;">
                    <?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['notes&fato'])?>
                </div>
            </div>
            <div class="terzo_box">
                <?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['first_login']) . ': ' . gdrcd_format_date($pg['data_iscrizione'])?>
                <br>
                <?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['last_login']) . ': ' . gdrcd_format_date($pg['ora_entrata'])?>
                <br>
                <a target='_parent' href="#" onclick="changeFrame('pages/mex_privati/multi_message.php?destinatari=<?=urlencode($pg['nome'])?>'); document.getElementById('id01').style.display='block';"> ▪ INVIA SMS ▪ </a>
            </div>
        </div>

        <div class="hidden_row" id="NoteEFato">
            <div class="particolari">
                <div class="green"><?=$pg['particolari']?></div>
                <div class="blue"><?=$pg['note_fato']?></div>
            </div>
        </div>

        <div class="background"><!-- Background, affetti, robe varie -->
            <div class="titolo_box_scheda" style="display:none";>
                <?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['background'])?>
            </div>
            <br>
            <div class="body_box"><?=$pg['principale']?> </div>
        </div>
    </div> <!-- scheda_page_body -->
</div><!-- pagina_scheda -->
    
<?php
/********* CHIUSURA SCHEDA **********/

//Impedisci XSS nella musica
$pg['url_media'] = gdrcd_filter('fullurl', $pg['url_media']);
if($PARAMETERS['mode']['allow_audio'] == 'ON' && ! $_SESSION['blocca_media'] && ! empty($pg['url_media'])) { ?>
    <audio autoplay>
        <source src="<?=$pg['url_media']?>" type="audio/mpeg">
    </audio>
    <!--[if IE9]>
    <embed src="<?=$pg['url_media']?>" autostart="true" hidden="true"/>
    <![endif]-->
<?php } ?>