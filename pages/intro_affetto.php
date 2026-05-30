<?php

session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

?>


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
<link rel="stylesheet" href="../themes/crystal/scheda_affetto.css" TYPE="text/css">
<link rel="stylesheet" href="../themes/crystal/main.css" TYPE="text/css">

<div class="pagina_scheda">
    <?php
     /* Cancellatura in un record */
     if(gdrcd_filter('get', $_POST['op']) == 'erase') {
     /*Eseguo la cancellatura*/
    gdrcd_query("DELETE FROM struttura_affetti WHERE id=".gdrcd_filter('num', $_POST['id'])." LIMIT 1");

          }      
                
    /********* CARICAMENTO PERSONAGGIO ***********/
    //Se non e' stato specificato il nome del pg

    $login = $_SESSION['login'];
    $username = $_REQUEST['username'];
    $pg = $_REQUEST['pg'];
    $id = $_REQUEST['id'];
    
    $rs = gdrcd_query("SELECT * FROM struttura_affetti WHERE username = '".$_SESSION['login']."'", 'result');
    $numresults = gdrcd_query($rs, 'num_rows');
     ?>
        
        <!-- INIZIO SCHEDA -->
        
    <body style='background: #070a1b none;'>
    
    <?php 

    /* Se esistono record */
                if($numresults > 0 && $login == $username) { ?>
                    <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table class="scheda_main">
                            <!-- Intestazione tabella -->
                            <tr>
                                <td class="testa">
                                    <div style="font-family: Dejavu Sans; font-size: 11px; color: #779ac9; text-transform: uppercase; text-align: center;">Nome</div>
                                </td>
                                <td class="testa">
                                    <div style="font-family: Dejavu Sans; font-size: 11px; color: #779ac9; text-transform: uppercase; text-align: center;">Categoria</div>
                                </td>
                                <td class="testa">
                                    <div style="font-family: Dejavu Sans; font-size: 11px; color: #779ac9; text-transform: uppercase; text-align: center;"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                            </tr>
                            <!-- Record -->
                             <?php while($row = gdrcd_query($rs, 'fetch')) { ?>
                             <tr>
                                <td class="casella_elemento">
                                    <div class="testo_intro"><?php echo $row['nomePg']; ?></div>
                                </td>
                                <td class="casella_elemento">
                                    <div class="testo_intro" style="text-transform: capitalize;"><?php echo $row['tipologia']; ?></div>
                                </td>
                                <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                            <!-- Modifica -->
                                            <div style="display:inline-block; float:center; height: 26px; text-align: center;">
                                                <form action="form_affetto.php?id=<?php echo $row['id'] ?>" method="post">
                                                    <input type="hidden" name="id"
                                                           value="<?php echo $row['id'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image"
                                                           src="../themes/crystal/imgs/scheda/edit_affetti.png"
                                                           alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           style="width: 26px; height: 26px; text-align: center; display:inline-block; float:center;"/>
                                                </form>
                                            </div>
                                            <!-- Elimina -->
                                            <div style="display:inline-block; float: center; height: 26px; text-align: center;">
                                                <form action="intro_affetto.php" method="post">
                                                    <input type="hidden" name="id"
                                                           value="<?php echo $row['id'] ?>" />
                                                    <input type="hidden" name="op" value="erase" />
                                                    <input type="image"
                                                           src="../themes/crystal/imgs/scheda/x_affetti.png"
                                                           alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>"
                                                           title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']
                                                           ); ?>" 
                                                           style="width: 26px; height: 26px; text-align: center; display:inline-block; float:center;"/>
                                                </form>
                                            </div>
                                    </td>
                            </tr>
                            



<?php 
}//finewhile
}//fine num_row

?>

</body>