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

    </body>
    <footer></footer>


    <!-- Socket.io: variabili utente, libreria client e connessione unica condivisa -->
    <?php if (isset($_SESSION['login'])): ?>
    <?php
    // Recupera l'avatar del personaggio corrente per esporlo in CT_USER
    // ed evitare una chiamata API separata da AnteprimaScheda.jsx
    $pg_avatar = '';
    $r = gdrcd_query("SELECT url_img_chat FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1");
    if ($r) $pg_avatar = trim($r['url_img_chat'] ?? '');
    ?>
    <script>
    window.CT_USER = {
        login:        <?=json_encode($_SESSION['login'])?>,
        luogo:        <?=(int)($_SESSION['luogo'] ?? 0)?>,
        mappa:        <?=(int)($_SESSION['mappa'] ?? 0)?>,
        url_img_chat: <?=json_encode($pg_avatar)?>
    };
    window.ctSocket = null;
    </script>
    <?php endif; ?>
    <script src="/socket.io/socket.io.js"></script>
    <?php if (isset($_SESSION['login'])): ?>
    <script>
    if (typeof io !== 'undefined' && window.CT_USER) {
        window.ctSocket = io({ auth: window.CT_USER });
    }
    </script>
    <?php endif; ?>

    <!-- Bundle React e listener ct:ready spostati in header.inc.php per garantire
         che siano sempre presenti anche se footer non esegue (die() nel contenuto) -->

    <!-- COREFUNCTIONS -->
    <script src="/includes/corefunctions.js"></script>
    
    <!-- Tutti i file js specifici da includere dopo -->
    <?php foreach ($scripts as $s): ?><script src="<?= $s ?>"></script><?php endforeach; ?>
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