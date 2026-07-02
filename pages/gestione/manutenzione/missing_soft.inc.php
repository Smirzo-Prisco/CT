<?php
/*Controllo permessi utente: se non admin, redirect alla mappa*/
if($_SESSION['admin']!=1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}

if((is_numeric($_POST['mesi']) === true) && ($_POST['mesi'] >= 1) && ($_POST['mesi'] <= 12)) {
    $mesi = gdrcd_filter('num', $_POST['mesi']);

    if(gdrcd_filter_get($_POST['conferma']) !== '1') {
        /*Step 1: mostro il conteggio dei personaggi interessati (staff esclusi) e chiedo conferma*/
        $count = gdrcd_query("SELECT COUNT(*) AS n FROM personaggio WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > ora_entrata AND permessi = 0")['n'];
        ?>
        <div class="warning">
            <?php echo sprintf(gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['confirm_warning']), $count); ?>
        </div>
        <form action="main.php?page=gestione_manutenzione" method="post" class="form_gestione">
            <input type="hidden" name="op" value="missing_soft">
            <input type="hidden" name="mesi" value="<?php echo $mesi; ?>">
            <input type="hidden" name="conferma" value="1">
            <div class='form_submit'>
                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['confirm_button']); ?>" />
                <a href="main.php?page=gestione_manutenzione"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['maintenance']['cancel_button']); ?></a>
            </div>
        </form>
        <?php
    } else {
        /*Step 2: marco come cancellati (permessi = -1) i personaggi inattivi, staff esclusi*/
        gdrcd_query("UPDATE personaggio SET permessi = -1 WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > ora_entrata AND permessi = 0");
        ?>
        <!-- Conferma -->
        <div class="warning">
            <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
        </div>
        <?php
    }
} else {
    ?>
    <div class="error">
        <?php echo gdrcd_filter('out', $MESSAGE['warning']['cant_do']); ?>
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
