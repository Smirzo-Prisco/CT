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
<style>
    span.look {
        font-size:13px;
        text-transform:uppercase;
        color: #ce846f;
    }
</style>
<body style="background-color:transparent;">
    <link rel="stylesheet" href="/themes/crystal/descrizione.css">
    <div id="container"><span class="luogo"><?=$result['nome']?></span><br></div>
    <div class="container2">
        <span class="testo"><?=$result['descrizione']?></span>
        <br><br>
        <center>
            <span class="testo">
<?php
    // Verifica del tipo di abilità e stampa del messaggio corrispondente
    $tipo_abilita = $result['tipo'];
    if ($tipo_abilita == 'Generica base' || $tipo_abilita == 'Generica avanzata' || $tipo_abilita == 'Difensiva') {
        echo '<span class="look">Attivazione</span><br><br><b>Tiro riuscita</b>: 10/20 (un turno)';
    } elseif ($tipo_abilita == 'Attacco base') {
        echo '<span class="look">Danni</span><br><br>
            <b>Liv 1</b>: -10 punti salute<br>
            <b>Liv 2</b>: -15 punti salute';
    } elseif ($tipo_abilita == 'Attacco medio') {
        echo '<span class="look">Danni Attacco singolo</span><br><br>
            <b>Liv 1</b>: -15 punti salute<br>
            <b>Liv 2</b>: -20 punti salute<br>
            <b>Liv 3</b>: -25 punti salute<br><br>
            <span class="look">Danni Attacco Doppio</span><br><br>
            <b>Liv 1</b>: -10 punti salute<br>
            <b>Liv 2</b>: -15 punti salute<br>
            <b>Liv 3</b>: -20 punti salute';
    } elseif ($tipo_abilita == 'Attacco avanzato') {
            echo '<span class="look">Danni Attacco singolo</span><br><br>
            <b>Liv 1</b>: -20 punti salute<br>
            <b>Liv 2</b>: -25 punti salute<br>
            <b>Liv 3</b>: -30 punti salute<br>
            <b>Liv 4</b>: -35 punti salute<br><br>
            <span class="look">Danni Attacco ad Area</span><br><br>
            <b>Liv 1</b>: -10 punti salute<br>
            <b>Liv 2</b>: -15 punti salute<br>
            <b>Liv 3</b>: -20 punti salute<br>
            <b>Liv 4</b>: -25 punti salute';
    } elseif ($tipo_abilita == 'Mentale base') {
        echo '<span class="look">Durata</span><br><br>
            <b>Liv 1</b>: 1 turno<br>
            <b>Liv 2</b>: 2 turni';
    } elseif ($tipo_abilita == 'Mentale media') {
        echo '<span class="look">Durata</span><br><br>
            <b>Liv 1</b>: 1 turno<br>
            <b>Liv 2</b>: 2 turni<br>
            <b>Liv 3</b>: 3 turni';
    } elseif ($tipo_abilita == 'Mentale avanzata') {
        echo '<span class="look">Durata</span><br><br>
            <b>Liv 1</b>: 1 turno<br>
            <b>Liv 2</b>: 2 turni<br>
            <b>Liv 3</b>: intera role<br>
            <b>Liv 4</b>: fino alla fine della giocata successiva';
    } elseif ($tipo_abilita == 'Mentale di attacco') {
        echo '<span class="look">Danni Attacco singolo</span><br><br>
            <b>Liv 1</b>: -1 punto integrità<br>
            <b>Liv 2</b>: -2 punti integrità<br>
            <b>Liv 3</b>: -3 punti integrità';
    } elseif ($tipo_abilita == 'Default') {
        echo '<span class="look">Durata</span><br><br>
            <b>Liv 1</b>: 13/20, un turno; 18/20, due turni<br>
            <b>Liv 2</b>: 11/20, un turno; 16/20, due turni<br>
            <b>Liv 3</b>: 9/20, un turno; 14/20, due turni<br>
            <b>Liv 4</b>: 8/20, un turno; 13/20, due turni';
    }
?>
            </span>
        </center>
    </div>
</body>