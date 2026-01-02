<script src="core/js/libs/jquery/jquery-1.11.2.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $(document).on("click", "a.tile-content", function (e) {
            e.preventDefault();
            var link = $(this).attr("href");
            var iframe = $("iframe#content");
            iframe.attr("src", link);
            iFrameResize();
        });
    });

    function iFrameResize() {
        try {
            var iframe = document.getElementById("content");
            iframe.height = (iframe.contentWindow.document.body.scrollHeight) + "px";
        } catch (e) {

        }
    }

    try {
        var inIframe = (window.self !== window.top);
        if (!inIframe) {
            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
            link.rel    = 'stylesheet';
            link.type   = 'text/css';
            link.href   = '../../css/iframe.css';
            link.media  = 'all';
            head.appendChild(link);
        }
    } catch (e) {
    }
</script>
<link rel="stylesheet" href="themes/crystal/scheda_affetto.css" TYPE="text/css">
<link rel="stylesheet" href="../themes/crystal/volti.css">
<div class="pagina_scheda">
    <?php
    /* HELP: E' possibile modificare la scheda agendo su scheda.css nel tema scelto,
     * oppure sostituendo il codice che segue la voce "Scheda del personaggio"
     */
    /********* CARICAMENTO PERSONAGGIO ***********/
    //Se non e' stato specificato il nome del pg
    $login = $_SESSION['login'];
    $pg = $_REQUEST['pg'];
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
   <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
        
        <!-- INIZIO SCHEDA -->
        
        <div class="page_body">

    <table class="scheda-main-dati">
        <tbody>
        <tr style="background: url('../themes/crystal/imgs/presenti/barra.png'); border: 1px solid #090a11; drop-shadow(0 0 5px rgba(0,0,0,0.57));">
            <td style="border: none;">
                <div class="faces" style="font-family: DejaVu Serif; color: #ce846f; font-size: 12px;">
                    <div class="faces_info">PERSONAGGI</div>
                </div>
            </td>

            <td style="border: none;">
                <div class="faces" style="font-family: DejaVu Serif; color: #ce846f; font-size: 12px;">
                    <div class="faces_info">DETTAGLIO</div>
                </div>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <ul class="list">
                <?php
                $lista = gdrcd_query("SELECT * FROM struttura_affetti WHERE username = '".$_REQUEST['pg']."' && tipologia = 'legami' ORDER BY nomePg", 'result');
                $numresults = gdrcd_query($lista, 'num_rows');
                if ($numresults > 0) { ?>
                <li class='tile no-height'>
                           <div class='tile-text header' align='center'>
                            <span class='header'>Legami</span>
                            </div>
                            </li>
                <?php
                
                while ($rs = gdrcd_query($lista, 'fetch')) {                
                ?>
                            
                            <li class="tile">
                            <a class="tile-content"
                               href="pages/dettaglio_affetto.php?id=<?php echo urlencode($rs['id']); ?>&username=<?php echo $pg; ?>&pg=<?php echo urlencode($rs['nomePg']); ?>">
                            
                            <div class="tile-icon">
                            <?php if ($rs['avatar'] != NULL || $rs['avatar'] != '') { ?>
                                        <img src="<?= $rs['avatar'] ?>" alt="">
                            <?php } else { ?>
                                        <img src="../themes/crystal/imgs/scheda/empy_affetti.png" alt="">
                            <?php } ?>
                            </div>

                                <div class="tile-text">
                                    <?php
                                    if (!empty($rs['Nome'])) {
                                        echo "<span>".strtoupper(sprintf("%s %s", $rs['Nome'], $rs['Cognome']))."</span>";
                                    } else {
                                        echo "<span>".strtoupper(sprintf("%s", $rs['nomePg']))."</span>";
                                    }
                                    ?>
                                    <br><span style="color: #616161; font-family: Dejavu Sans; font-size:11px; text-transform:none; letter-spacing: none;"><?php echo $rs['titolo']; ?></span>
                                </div>
                            </a>
                        </li>
                        
                        <? }
                                }
                            ?>
                            
                            
                            
                            
                            
                            
                          <?php
                $lista = gdrcd_query("SELECT * FROM struttura_affetti WHERE username = '".$_REQUEST['pg']."' && tipologia = 'nemici' ORDER BY nomePg", 'result');
                $numresults = gdrcd_query($lista, 'num_rows');
                if ($numresults > 0) { ?>  
                            
                            <li class='tile no-height'>
                           <div class='tile-text header' align='center'>
                            <span class='header'>Nemici</span>
                            </div>
                            </li>
                <?php
                while ($rs = gdrcd_query($lista, 'fetch')) {                
                ?>
                            
                            <li class="tile">
                            <a class="tile-content"
                               href="pages/dettaglio_affetto.php?id=<?php echo urlencode($rs['id']); ?>&username=<?php echo $pg; ?>&pg=<?php echo urlencode($rs['nomePg']); ?>">
                            
                            <div class="tile-icon">
                            <?php if ($rs['avatar'] != NULL || $rs['avatar'] != '') { ?>
                                        <img src="<?= $rs['avatar'] ?>" alt="">
                            <?php } else { ?>
                                        <img src="../themes/crystal/imgs/scheda/empy_affetti.png" alt="">
                            <?php } ?>
                            </div>

                                <div class="tile-text">
                                    <?php
                                    if (!empty($rs['Nome'])) {
                                        echo "<span>".strtoupper(sprintf("%s %s", $rs['Nome'], $rs['Cognome']))."</span>";
                                    } else {
                                        echo "<span>".strtoupper(sprintf("%s", $rs['nomePg']))."</span>";
                                    }
                                    ?>
                                    <br><span style="color: #616161; font-family: Dejavu Sans; font-size:11px; text-transform:none; letter-spacing: none;"><?php echo $rs['titolo']; ?></span>
                                </div>
                            </a>
                        </li>
                        
                        <? }
                                 }
                            ?>
                          
                          
                          
                          
                          
                          
                          
                          
                          
                          
                          <?php
                $lista = gdrcd_query("SELECT * FROM struttura_affetti WHERE username = '".$_REQUEST['pg']."' && tipologia = 'famiglia' ORDER BY nomePg", 'result');
                $numresults = gdrcd_query($lista, 'num_rows');
                if ($numresults > 0) { ?>
                          
                          <li class='tile no-height'>
                           <div class='tile-text header' align='center'>
                            <span class='header'>Famiglia</span>
                            </div>
                            </li>
                <?php                
                while ($rs = gdrcd_query($lista, 'fetch')) {                
                ?>
                            
                            <li class="tile">
                            <a class="tile-content"
                               href="pages/dettaglio_affetto.php?id=<?php echo urlencode($rs['id']); ?>&username=<?php echo $pg; ?>&pg=<?php echo urlencode($rs['nomePg']); ?>">
                            
                            <div class="tile-icon">
                            <?php if ($rs['avatar'] != NULL || $rs['avatar'] != '') { ?>
                                        <img src="<?= $rs['avatar'] ?>" alt="">
                            <?php } else { ?>
                                        <img src="../themes/crystal/imgs/scheda/empy_affetti.png" alt="">
                            <?php } ?>
                            </div>

                                <div class="tile-text">
                                    <?php
                                    if (!empty($rs['Nome'])) {
                                        echo "<span>".strtoupper(sprintf("%s %s", $rs['Nome'], $rs['Cognome']))."</span>";
                                    } else {
                                        echo "<span>".strtoupper(sprintf("%s", $rs['nomePg']))."</span>";
                                    }
                                    ?>
                                    <br><span style="color: #616161; font-family: Dejavu Sans; font-size:11px; text-transform:none; letter-spacing: none;"><?php echo $rs['titolo']; ?></span>
                                </div>
                            </a>
                        </li>
                        
                        <? }
                               }
                            ?>
                          
                          
                         
                         
                         
                         
                         
                          <?php
                $lista = gdrcd_query("SELECT * FROM struttura_affetti WHERE username = '".$_REQUEST['pg']."' && tipologia = 'conoscenze' ORDER BY nomePg", 'result');
                $numresults = gdrcd_query($lista, 'num_rows');
                if ($numresults > 0) { ?>
                          
                          <li class='tile no-height'>
                           <div class='tile-text header' align='center'>
                            <span class='header'>Conoscenze</span>
                            </div>
                            </li>
                <?php                
                while ($rs = gdrcd_query($lista, 'fetch')) {                
                ?>
                            
                            <li class="tile">
                            <a class="tile-content"
                               href="pages/dettaglio_affetto.php?id=<?php echo urlencode($rs['id']); ?>&username=<?php echo $pg; ?>&pg=<?php echo urlencode($rs['nomePg']); ?>">
                            
                            <div class="tile-icon">
                            <?php if ($rs['avatar'] != NULL || $rs['avatar'] != '') { ?>
                                        <img src="<?= $rs['avatar'] ?>" alt="">
                            <?php } else { ?>
                                        <img src="../themes/crystal/imgs/scheda/empy_affetti.png" alt="">
                            <?php } ?>
                            </div>

                                <div class="tile-text">
                                    <?php
                                    if (!empty($rs['Nome'])) {
                                        echo "<span>".strtoupper(sprintf("%s %s", $rs['Nome'], $rs['Cognome']))."</span>";
                                    } else {
                                        echo "<span>".strtoupper(sprintf("%s", $rs['nomePg']))."</span>";
                                    }
                                    ?>
                                    <br><span style="color: #616161; font-family: Dejavu Sans; font-size:11px; text-transform:none; letter-spacing: none;"><?php echo $rs['titolo']; ?></span>
                                </div>
                            </a>
                        </li>
                        
                        <? }
                                }
                            ?>
                            
                            
                            
                          
                          <?php
                $lista = gdrcd_query("SELECT * FROM struttura_affetti WHERE username = '".$_REQUEST['pg']."' && tipologia = 'memories' ORDER BY nomePg", 'result');
                $numresults = gdrcd_query($lista, 'num_rows');
                if ($numresults > 0) { ?>
                          
                          <li class='tile no-height'>
                           <div class='tile-text header' align='center'>
                            <span class='header'>Memories</span>
                            </div>
                            </li>
                <?php                
                while ($rs = gdrcd_query($lista, 'fetch')) {                
                ?>
                            
                            <li class="tile">
                            <a class="tile-content"
                               href="pages/dettaglio_affetto.php?id=<?php echo urlencode($rs['id']); ?>&username=<?php echo $pg; ?>&pg=<?php echo urlencode($rs['nomePg']); ?>">
                            
                            <div class="tile-icon">
                            <?php if ($rs['avatar'] != NULL || $rs['avatar'] != '') { ?>
                                        <img src="<?= $rs['avatar'] ?>" alt="">
                            <?php } else { ?>
                                        <img src="../themes/crystal/imgs/scheda/empy_affetti.png" alt="">
                            <?php } ?>
                            </div>

                                <div class="tile-text">
                                    <?php
                                    if (!empty($rs['Nome'])) {
                                        echo "<span>".strtoupper(sprintf("%s %s", $rs['Nome'], $rs['Cognome']))."</span>";
                                    } else {
                                        echo "<span>".strtoupper(sprintf("%s", $rs['nomePg']))."</span>";
                                    }
                                    ?>
                                    <br><span style="color: #616161; font-family: Dejavu Sans; font-size:11px; text-transform:none; letter-spacing: none;"><?php echo $rs['titolo']; ?></span>
                                </div>
                            </a>
                        </li>
                        
                        <? }
                                }
                            ?>
                          
                          
                          
                            
                            
                            
                            
   
                    <?php if ($login == $pg) { ?>
                        <li class="tile"><br>
                            <a class="tile-content" href="pages/form_affetto.php?username=<?php echo $pg; ?>">
                                <div class="tile-text" align="center">
                                    <span style="color:#353535; font-size: 11px; font-family: Dejavu Sans; text-transform:uppercase;">Aggiungi affetto</span>
                                </div>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </td>

            <td valign="top">
                <?php if (is_array($list) && count($list) > 0) { ?>
                    <iframe id="content" onload="iFrameResize();"
                            src="pages/dettaglio_affetto.php?username=<?php echo $pg; ?>&pg=<?php echo $list[0]['Nome']; ?>"
                            allowtransparency="true" frameborder="0" width="100%"></iframe>
                <?php } else { ?>
                    <iframe id="content" onload="iFrameResize();" src="pages/intro_affetto.php?username=<?php echo $pg; ?>" allowtransparency="true" frameborder="0"
                            width="100%"></iframe>
                <?php } ?>
            </td>
        </tr>
        </tbody>
    </table>
        <div class="link_back">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>">
                <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?>
            </a>
        </div>
</div>