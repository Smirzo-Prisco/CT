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
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="/includes/forum.js"></script>


    <!-- Socket.io: variabili utente, libreria client e connessione unica condivisa -->
    <?php if (isset($_SESSION['login'])): ?>
    <script>
    window.CT_USER = {
        login: <?=json_encode($_SESSION['login'])?>,
        luogo: <?=(int)($_SESSION['luogo'] ?? 0)?>,
        mappa: <?=(int)($_SESSION['mappa'] ?? 0)?>
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

    <!-- Bundle React/Vite (caricato solo se il build è stato eseguito) -->
    <?php if (file_exists(__DIR__ . '/themes/crystal/dist/ct-app.js')): ?>
    <script type="module" src="/themes/crystal/dist/ct-app.js"></script>
    <?php endif; ?>

    <!-- Phase 3.1: monta AppRouter su #ct-app-content se presente.
         Le pagine migrate (forum, messages_center, presenti_estesi) usano
         questo div come punto di mount per il router React. -->
    <?php
    $isStaff = (($_SESSION['admin'] ?? 0) == 1 || ($_SESSION['moderatore'] ?? 0) == 1 || ($_SESSION['master'] ?? 0) == 1) ? 'true' : 'false';
    ?>
    <script>
    document.addEventListener('ct:ready', function() {
        var el = document.getElementById('ct-app-content');
        if (el) CT.mount('AppRouter', 'ct-app-content', { isStaff: <?= $isStaff ?> });

        // Phase 3.3 — frame laterali come componenti React
        if (document.getElementById('info-location-container'))
            CT.mount('InfoLocation', 'info-location-container', {});

        if (document.getElementById('frame-messaggi-container'))
            CT.mount('FrameMessaggi', 'frame-messaggi-container', {});

        if (document.getElementById('anteprima-scheda-container'))
            CT.mount('AnteprimaScheda', 'anteprima-scheda-container', {});

        if (document.getElementById('menu-icons-container'))
            CT.mount('MenuIcons', 'menu-icons-container', {});
    });
    </script>

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