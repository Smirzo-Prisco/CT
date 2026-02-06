<script type="text/javascript"> 
  function showHideRow(row) { 
    $("#" + row).toggle(); 
  } 
</script> 
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
    $query = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5, gilda.nome as nome_gilda, ruolo.nome_ruolo, mestiere.nome as nome_mestiere, ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere, ruolo.immagine as immagine_famiglia, ruolo_mestiere.immagine as immagine_mestiere FROM personaggio LEFT JOIN razza ON personaggio.id_razza=razza.id_razza LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo WHERE personaggio.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
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
        <?php /*<a href="main.php?page=messages_center&op=create&reply_dest=<?php echo gdrcd_filter('url', $personaggio['nome']); ?>"
                       class="link_invia_messaggio">
                        <?php if(empty($PARAMETERS['names']['private_message']['image_file']) === false) { ?>
                            <img src="<?php echo $PARAMETERS['names']['private_message']['image_file']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['send']).' '.gdrcd_filter('out', $PARAMETERS['names']['private_message']['sing']).' '.gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['to']).' '.gdrcd_filter('out', $personaggio['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['send']).' '.gdrcd_filter('out', $PARAMETERS['names']['private_message']['sing']).' '.gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['to']).' '.gdrcd_filter('out', $personaggio['nome']); ?>"
                                 class="link_messaggio_forum">
                        <?php } else {
                            echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['send']).' '.gdrcd_filter('out', strtolower($PARAMETERS['names']['private_message']['sing'])).' '.gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['to']).' '.gdrcd_filter('out', $personaggio['nome']);
                        } ?>
                    </a>       */?>
    </div>
    <div class="scheda_page_body">
        <?php
        /** * Controllo e avviso che è ora di cambiare password
         * @author Blancks
         */
        if($PARAMETERS['mode']['alert_password_change'] == 'ON') {
            $six_months = 15552000;
            $ts_signup = strtotime($record['data_iscrizione']);
            $ts_lastpass = (int) strtotime($record['ultimo_cambiopass']);
            if($ts_lastpass + $six_months < time() && $record['nome'] == $_SESSION['login']) {
                $message = ($ts_signup + $six_months < time()) ? $MESSAGE['warning']['changepass'] : $MESSAGE['warning']['changepass_signup'];
                echo '<div class="warning">'.$message.'</div>';
            }
        }
        ?>
        <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
        <div class="scheda_page_body">
        <div class="title">                     
        <?php echo gdrcd_filter('out', $personaggio['nome']); ?> 
        <?php echo gdrcd_filter('out', $personaggio['cognome']); ?>
        </div><br>
            <div class="ritratto"><!-- nome, ritratto, ultimo ingresso -->
                <div class="titolo_box">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['portrait']); ?>
                </div>
                <div class="ritratto_nome">
                    <span class="ritratto_nome_nome">
                        <?php echo gdrcd_filter('out', $personaggio['nome']); ?>
                    </span>
                    <span class="ritratto_nome_cognome">
                        <?php echo gdrcd_filter('out', $personaggio['cognome']); ?>
                    </span>
                </div>
                <div class="ritratto_avatar">
                    <img src="<?php echo gdrcd_filter('fullurl', $personaggio['url_img']); ?>" class="ritratto_avatar_immagine" />
                </div>
                <?php /*
                <div class="iscritto_da">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['first_login']).' '.gdrcd_format_date($personaggio['data_iscrizione']); ?>
                </div>
                <?php if(gdrcd_format_date($record['ora_entrata']) != '00/00/0000') { ?>
                    <div class="ultimo_ingresso">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['last_login']).' '.gdrcd_format_date($personaggio['ora_entrata']); ?>
                    </div>
                <?php } ?>
                <div class="ritratto_invia_messaggio"><!-- Link invia messaggio -->
                    <a href="main.php?page=messages_center&op=create&reply_dest=<?php echo gdrcd_filter('url', $personaggio['nome']); ?>"
                       class="link_invia_messaggio">
                        <?php if(empty($PARAMETERS['names']['private_message']['image_file']) === false) { ?>
                            <img src="<?php echo $PARAMETERS['names']['private_message']['image_file']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['send']).' '.gdrcd_filter('out', $PARAMETERS['names']['private_message']['sing']).' '.gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['to']).' '.gdrcd_filter('out', $personaggio['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['send']).' '.gdrcd_filter('out', $PARAMETERS['names']['private_message']['sing']).' '.gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['to']).' '.gdrcd_filter('out', $personaggio['nome']); ?>"
                                 class="link_messaggio_forum">
                        <?php } else {
                            echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['send']).' '.gdrcd_filter('out', strtolower($PARAMETERS['names']['private_message']['sing'])).' '.gdrcd_filter('out', $MESSAGE['interface']['sheet']['send_message_to']['to']).' '.gdrcd_filter('out', $personaggio['nome']);
                        } ?>
                    </a>
                </div> */?>
            </div>
            <?php
            // Se l'URL del media esiste, aggiungi il player musicale invisibile
            if (!empty($personaggio['url_media'])) {
            $url_media = gdrcd_filter('out', $personaggio['url_media']);
            echo '<audio autoplay style="display:none;">
            <source src="' . $url_media . '" type="audio/mpeg">
            Your browser does not support the audio element.
            </audio>';
}
            ?>
            <!-- nome, ritratto, ultimo ingresso, abiti portati -->
            <div class="profilo"><!-- Punteggi, salute, status, classe, razza. -->
                <div class="titolo_box">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['profile']); ?>
                </div>
               
                <div class="primo_box">
                <div class="header_box">▪ PROFILO ▪</div><br>
                
                
                
                
                
                
                
                <span style="float: left; margin-left: 5px;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['age']); ?>:
                </span>    
                    <span style="float: right; margin-right: 5px; text-align: right;">
                    <?php echo gdrcd_filter('out', $personaggio['eta']); ?>
                </span><br>
                
                <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['place']); ?>:
                </span>    
                    <span style="float: right; margin-right: 5px; text-align: right;">
                    <?php echo gdrcd_filter('out', $personaggio['natoa']); ?>
                </span><br>
                
                    
                           
                
                
                
                    <?php
                    $job = gdrcd_query("SELECT * FROM clgpersonaggiolavoro WHERE personaggio = '".$_REQUEST['pg']."'", 'result');
                    if(gdrcd_query($job, 'num_rows') > 0) {
                    $lavoro = "SELECT clgpersonaggiolavoro.*, personaggio.nome, ruolo_mestiere.* FROM ruolo_mestiere JOIN clgpersonaggiolavoro ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggiolavoro.personaggio WHERE personaggio.nome ='".$_REQUEST['pg']."'";
                    $good_done = gdrcd_query($lavoro, 'result');
                    $work = gdrcd_query($good_done, 'fetch');
                    ?>

                    <span style="float: left; margin-left: 5px;">
                    <?php echo "Lavoro"; ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                    <?php echo $work['nome_ruolo']; ?>
                    </span><br>
                <?php }//fine Lavoro ?>
                
                
                
                                    <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['race']['sing']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php if((empty($personaggio['sing_f']) == false) || (empty($personaggio['sing_m']) == false)) {
                            echo ($personaggio['sesso'] == 'f') ? gdrcd_filter('out', $personaggio['sing_f']) : gdrcd_filter('out', $personaggio['sing_m']);
                        } else {
                            echo gdrcd_filter('out', $PARAMETERS['names']['race']['sing'].' '.$MESSAGE['interface']['sheet']['profile']['no_race']);
                        } ?>
                    </span>
                <br>
                <?php /* <div class="profilo_voce">
                    <div class="profilo_voce_label">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['guild']); ?>:
                    </div>
                    <div class="profilo_voce_valore">
                        <?php echo gdrcd_filter('out', $personaggio['nome_gilda']); ?>
                    </div>
                </div> */ ?>
                 <span style="float: left; margin-left: 5px;">
Famiglia:                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['nome_ruolo']); ?>
                    </span>
                <br>
               <?php /* <div class="profilo_voce">
                    <div class="profilo_voce_label">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['job']); ?>:
                    </div>
                    <div class="profilo_voce_valore">
						<?php echo gdrcd_filter('out', $personaggio['nome_mestiere']); ?>                   
                     </div>
                </div> */ ?>
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['job_role']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                    <?php echo gdrcd_filter('out', $personaggio['nome_ruolo_mestiere']); ?>
                        
                    </span>
                <br><br>



                <?php 
                if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) {
                ?>
                <div class="header_box">▪ STATISTICHE ▪</div><br>




                 <!-- caratteristiche -->
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car0']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['car0'] + $personaggio['bonus_car0'] + $bonus_oggetti['BO0']); ?>
                    </span>
                <br>
                <!-- <div class="profilo_voce">
                    <div class="profilo_voce_label">
                    
                    </div>
                    <div class="profilo_voce_valore">
                    </div>
                </div> -->
                
                <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car8']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['car8'] + $personaggio['bonus_car8'] + $bonus_oggetti['BO8']); ?>
                    </span>
                <br>
                <!-- <div class="profilo_voce">
                    <div class="profilo_voce_label">
                    
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car9']); ?>:
                    </div>
                    <div class="profilo_voce_valore">
                        <?php echo gdrcd_filter('out', $personaggio['car9'] + $personaggio['bonus_car9'] + $bonus_oggetti['BO9']); ?>
                    </div>
                </div> -->
                
                
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car2']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['car2'] + $personaggio['bonus_car2'] + $bonus_oggetti['BO2']); ?>
                    </span>
                <br>
               <!-- <div class="profilo_voce">
                    <div class="profilo_voce_label">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car3']); ?>:
                    </div>
                    <div class="profilo_voce_valore">
                        <?php echo gdrcd_filter('out', $personaggio['car3'] + $personaggio['bonus_car3'] + $bonus_oggetti['BO3']); ?>
                    </div>
                </div> -->
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car4']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['car4'] + $personaggio['bonus_car4'] + $bonus_oggetti['BO4']); ?>
                    </span>
                <br>
               <!-- <div class="profilo_voce">
                    <div class="profilo_voce_label">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car5']); ?>:
                    </div>
                    <div class="profilo_voce_valore">
                        <?php echo gdrcd_filter('out', $personaggio['car5'] + $personaggio['bonus_car5'] + $bonus_oggetti['BO5']); ?>
                    </div>
                </div> -->
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car6']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['car6'] + $personaggio['bonus_car6'] + $bonus_oggetti['BO6']); ?>
                    </span>
                <br>
               <!-- <div class="profilo_voce">
                    <div class="profilo_voce_label">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car7']); ?>:
                    </div>
                    <div class="profilo_voce_valore">
                        <?php echo gdrcd_filter('out', $personaggio['car7'] + $personaggio['bonus_car7'] + $bonus_oggetti['BO7']); ?>
                    </div>
                </div> 
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['hitpoints']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['salute']).'/'.gdrcd_filter('out', $personaggio['salute_max']); ?>
                    </span>-->
                <br><br>
                <?php } ?>
                
                
                
                
                                <div class="header_box">▪ INFO ▪</div><br>

                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['hitpoints']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['salute']).'/'.gdrcd_filter('out', $personaggio['salute_max']); ?>
                    </span>
                    <br>
                                     <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['integrita']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', $personaggio['integrita']).'/'.gdrcd_filter('out', $personaggio['integrita_max']); ?>
                    </span>
                                    <?php 
                if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) {
                ?>
                    <br>
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['profile']['experience']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', floor($personaggio['esperienza'])); ?>
                    </span>
                    <br>
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['shin']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', floor($personaggio['shin'])); ?>
                    </span>
                    <? } ?>
                <br>
                 <span style="float: left; margin-left: 5px;">
                        <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['notorieta']); ?>:
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
                        <?php echo gdrcd_filter('out', floor($personaggio['notorieta'])); ?>
                    </span>
                <br>
                                 <span style="float: left; margin-left: 5px;">
                    <?php echo 'Cariche'; ?>
                    </span>
                    <span style="float: right; margin-right: 5px; text-align: right;">
 <?php 
                    $privilegi = "SELECT * FROM privilegi WHERE nome=".'"'.gdrcd_filter('out', $_REQUEST['pg']).'"';
                    $row_pri = gdrcd_query($privilegi);
                    
                    if($row_pri['admin']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Admin.png" width="20" height="20">';
                    }
                    if($row_pri['moderatore']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Moderatore.png" width="20" height="20">';
                    }
                    if($row_pri['master']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Master.png" width="20" height="20">';
                    }
                    if($row_pri['guida']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Guida.png" width="20" height="20">';
                    }
                    if($row_pri['grafico']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Grafico.png" width="20" height="20">';
                    }
                    ?>                                 </span>
                <br><br>
				</div>
                
                <div class="terzo_box">
                  <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['first_login']); ?>: <?php echo gdrcd_format_date($personaggio['data_iscrizione']); ?>
                    <br>
				
                               <?php
    echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['last_login']) . ': ';
    echo gdrcd_format_date($personaggio['ora_entrata']);
    
?>
<br>
<a target='_parent' href="#" onclick="changeFrame('pages/mex_privati/multi_message.php?destinatari=<?php echo urlencode($personaggio['nome']); ?>'); document.getElementById('id01').style.display='block';">                                 ▪ INVIA SMS ▪ 
        </a>



                </div>
                </div>
            </div>
         
            <?php //Punteggi, salute, status, classe, razza.
            /* Parte abilità disabilitata, c'è la sezione apposita Skills
            if($PARAMETERS['mode']['skillsystem'] == 'ON') { //solo se è attiva la modalità skillsystem
                include ('scheda/skillsystem.inc.php');
            } 
           */
            ?>
            <br>
            <div class="background"><!-- Background, affetti, robe varie -->
                <div class="titolo_box_scheda" onclick="showHideRow('NoteEFato');" style="cursor: pointer;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['notes&fato']); ?>
                </div><br>
                <div class="hidden_row body_box" id="NoteEFato">
                    <?php
                    /** * Html, bbcode o entrambi ?
                     * @author Blancks
                     
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
                    } */?>
                    <div class="particolari">
                        <div class="green">
                        <?php echo $personaggio['particolari']; ?>
                        </div>
                        <br>
                        <div class="blue">
                        <?php echo $personaggio['note_fato']; ?>                  
                        </div>
                  		</div>
                  
                </div>
            </div>
            <div class="background"><!-- Background, affetti, robe varie -->
                <div class="titolo_box_scheda" style="display:none";>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['background']); ?>
                </div>
                <br>
                <div class="body_box">
                    <?php
                    /** * Html, bbcode o entrambi ?
      
                    if($PARAMETERS['mode']['user_bbcode'] == 'ON') {
                        if($PARAMETERS['settings']['user_bbcode']['type'] == 'bbd' && $PARAMETERS['settings']['bbd']['free_html'] == 'ON') {
                            echo bbdecoder(gdrcd_html_filter($personaggio['principale']), true);
                        } elseif($PARAMETERS['settings']['user_bbcode']['type'] == 'bbd') {
                            echo bbdecoder(gdrcd_filter('out', $personaggio['principale']), true);
                        } else {
                            echo gdrcd_bbcoder(gdrcd_filter('out', $personaggio['principale']));
                        }
                    } else {
                        echo gdrcd_html_filter($personaggio['principale']);
                    }
                    */?>
                      <?php echo gdrcd_filter('out', $personaggio['principale']); ?> 
                    
                </div>
            </div>
            <!-- Background, affetti, robe varie -->
        </div>
       <?php /* <div class="link_back">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>">
                <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?>
            </a>
        </div> */ ?>
    </div><!-- Elenco abilità -->
    <?php
    /********* CHIUSURA SCHEDA **********/
    //Impedisci XSS nella musica
    $record['url_media'] = gdrcd_filter('fullurl', $record['url_media']);
    if($PARAMETERS['mode']['allow_audio'] == 'ON' && ! $_SESSION['blocca_media'] && ! empty($record['url_media'])) { ?>
        <audio autoplay>
            <source src="<?php echo $record['url_media']; ?>" type="audio/mpeg">
        </audio>
        <!--[if IE9]>
        <embed src="<?php echo $record['url_media']; ?>" autostart="true" hidden="true"/>
        <![endif]-->
    <?php } ?>
</div><!-- Pagina -->
