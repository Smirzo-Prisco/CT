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
<div class="pagina_scheda">
    <?php
    /* HELP: E' possibile modificare la scheda agendo su scheda.css nel tema scelto,
     * oppure sostituendo il codice che segue la voce "Scheda del personaggio"
     */
    /********* CARICAMENTO PERSONAGGIO ***********/
    //Se non e' stato specificato il nome del pg

    $login = $_SESSION['login'];
    $username = $_REQUEST['username'];
    $pg = $_REQUEST['pg'];
    $id = $_REQUEST['id'];
    
    $rs = gdrcd_query("SELECT * FROM struttura_affetti WHERE id = '$id' && username = '$username' && nomePg = '$pg'");
    ?>
        
        <!-- INIZIO SCHEDA -->
        
    <body style='background: #070a1b none;'>
    
    <span class="testo_contenuto">
    <? echo $rs['contenuto']; ?>
    </span>

</body>