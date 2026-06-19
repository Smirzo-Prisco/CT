<div class="page_body">  
<?php
    /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
        die();
    }

    // Valorizzo
    $Nome = $_POST['Nome'];
    $IP = $_POST['IP'];
    $IP = str_replace("%perc", "%", $IP);
?>

<table class="customTable">
  <thead><tr><th colspan="8">LOG ENTRATA</th></tr></thead>
  <tbody>
    <tr class="second_header">
        <td>PERSONAGGIO</td>	
        <td>DATA/ORA</td>
        <td>IP</td>
        <td>HOST</td>
	</tr>
    
<?php
    //Valorizzo ipotesi
    
    //Determinazione pagina (paginazione)
    $offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 1;
    $pagebegin = (int) gdrcd_filter('get', ($offset-1)) * $PARAMETERS['settings']['records_per_page'];
    $pageend = $PARAMETERS['settings']['records_per_page'];
    //Conteggio record totali
    $record_globale = gdrcd_query("SELECT COUNT(*) FROM log_entrate");
    $totaleresults = $record_globale['COUNT(*)'];

    // Lettura record  
    $where = '';

    if ($Nome != '') $where = "Nome = '$Nome'";
    else if ($IP != '') $where = "IP LIKE '%$IP%'";
    else if ($DataEvento != '') $where = "DataEvento LIKE '%$DataEvento%'";
    
    $result = gdrcd_query("SELECT * FROM log_entrate $where ORDER BY DataEvento DESC LIMIT ".$pagebegin.", ".$pageend."", 'result');
    $numresults = gdrcd_query($result, 'num_rows');
    
    //Conteggio record totali
    $totaleresults = gdrcd_query("SELECT COUNT(*) FROM log_entrate $where")['COUNT(*)'];

    /* Se esistono record */
    if($numresults > 0) {
        while($row = gdrcd_query($result, 'fetch')) {
    ?>
            <!-- Inizio impaginazione oggetti-->
            <tr>
                <td align=center><?=$row['Nome'];?></td>
                <td align=center><?=$row['DataEvento'];?></td>
                <td align=center><?=$row['IP'];?></td>
                <td align=center><?=$row['Host'];?></td>
            </tr>
<?php
        } // fine while
?>

        <!-- Paginazione -->
        <div class="pagination">
            <?php
            include __DIR__ . '/../includes/custom_functions.inc.php';

            // Genera i link di paginazione
            $total_pages = ceil($totaleresults / $PARAMETERS['settings']['records_per_page']);
            echo generateLinks($offset, $total_pages, preg_replace('/&.*/', '', $_SERVER["REQUEST_URI"]));
            echo "<p>Pagina $offset di $total_pages (Totale: $totaleresults record)</p>";
            ?>
        </div>
<?php
    } // if
?>    
</table>

<br><br>

<form action="main.php?page=log_entrate" method="post" class="form_gestione">
    <table class="customTable">
        <tr class="second_header">
            <td>PERSONAGGIO</td>
            <td>IP</td>
            <td>DATA</td>
            <td></td>
        </tr>    
        <tr>    
            <td><input Name=Nome maxLength=20 value='<?= htmlspecialchars(stripslashes($Nome)) ?>'></td>
            <td><input Name=IP maxLength=15 value='<?= htmlspecialchars(stripslashes($IP)) ?>'></td>
            <td><input Name=DataEvento maxLength=15 value='<?= htmlspecialchars(stripslashes($DataEvento)) ?>'></td>
            <td><input type=submit value='Cerca'></td>
        </tr>
    </table>
</form>