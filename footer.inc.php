<?php if (!empty($_SESSION['login'])): ?>
<?php
/** * Abilitazione tooltip
 * @author Blancks
 */
if($PARAMETERS['mode']['map_tooltip'] == 'ON' || $PARAMETERS['mode']['user_online_state'] == 'ON') { ?>
    <script type="text/javascript">
        var tooltip_offsetX = <?=$PARAMETERS['settings']['map_tooltip']['offset_x']?>;
        var tooltip_offsetY = <?=$PARAMETERS['settings']['map_tooltip']['offset_y']?>;
    </script>
    <script type="text/javascript" src="/includes/tooltip.js"></script>
    <?php
}
/** * Caricamento script per il titolo "lampeggiante" per i nuovi pm
 * @author Blancks
 */
if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON') echo '<script type="text/javascript" src="/includes/changetitle.js"></script>';

/** * Caricamento script per la scelta popup nel login
 * @author Blancks
 */
if($PARAMETERS['mode']['popup_choise'] == 'ON') echo '<script type="text/javascript" src="/includes/popupchoise.js"></script>';
?>
<?php endif; ?>

    </body>


    <!-- CT_USER, socket.io e bundle React spostati in header.inc.php per garantire
         disponibilità anche quando footer non esegue (die() nel contenuto) -->

    <?php if (!empty($_SESSION['login'])): ?>
    <!-- COREFUNCTIONS -->
    <script src="/includes/corefunctions.js?v=<?= filemtime(__DIR__ . '/includes/corefunctions.js') ?>"></script>

    <!-- Tutti i file js specifici da includere dopo -->
    <?php foreach ($scripts as $s): ?>
    <script src="<?= $s ?>?v=<?= file_exists(__DIR__ . $s) ? filemtime(__DIR__ . $s) : 0 ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>
</html>

<?php
/* Chiudo la connessione al database */
gdrcd_close_connection($handleDBConnection);

/**    * Per ottimizzare le risorse impiegate le liberiamo dopo che non ne abbiamo più bisogno
 * @author Blancks
 */
unset($MESSAGE);
unset($PARAMETERS);
?>