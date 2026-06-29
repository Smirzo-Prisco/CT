<?php
    /* Includo i file necessari */
    include('includes/constant_values.inc.php');
    include('config.inc.php');
    include('vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
    include('includes/functions.inc.php');
    require 'header.inc.php'; /* Header comune */

    /* Eseguo la connessione al database */
    $handleDBConnection = gdrcd_connect();
    
    // Carico l'elenco delle abilità
    $result = gdrcd_query("SELECT * FROM abilita WHERE id_abilita = ".$_GET['id']);
?>

<body style="background-color:transparent;">
    <div id="container"><span class="luogo"><?=$result['nome']?></span><br></div>
    <div class="container2">
        <span class="testo"><?=$result['descrizione']?></span>
    </div>
</body>