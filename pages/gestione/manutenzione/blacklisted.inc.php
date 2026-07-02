<?php
/*Controllo permessi utente: se non admin, redirect alla mappa*/
if($_SESSION['admin']!=1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}

if(gdrcd_filter_get($_POST['conferma']) !== '1') {
    /*Step 1: mostro il conteggio delle righe interessate e chiedo conferma*/
    $count = gdrcd_query("SELECT COUNT(*) AS n FROM blacklist")['n'];
    ?>
    <div class="warning">
        <?php echo sprintf(gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['confirm_warning']), $count); ?>
    </div>
    <form action="main.php?page=gestione_manutenzione" method="post" class="form_gestione">
        <input type="hidden" name="op" value="blacklisted">
        <input type="hidden" name="conferma" value="1">
        <div class='form_submit'>
            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['confirm_button']); ?>" />
            <a href="main.php?page=gestione_manutenzione"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['cancel_button']); ?></a>
        </div>
    </form>
    <?php
} else {
    /*Step 2: eseguo l'aggiornamento*/
    gdrcd_query("DELETE FROM blacklist WHERE 1");
    gdrcd_query("OPTIMIZE TABLE blacklist");
    ?>
    <!-- Conferma -->
    <div class="warning">
        <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
    </div>
    <?php
}
?>
<!-- Link di ritorno alla visualizzazione di base -->
<div class="link_back">
    <a href="main.php?page=gestione_manutenzione">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['link']['back']); ?>
    </a>
</div>
