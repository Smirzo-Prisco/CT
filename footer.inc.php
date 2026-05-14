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

    <!-- Bundle React/Vite (caricato solo se il build è stato eseguito) -->
    <?php if (file_exists(__DIR__ . '/themes/crystal/dist/ct-app.js')): ?>
    <script type="module" src="/themes/crystal/dist/ct-app.js"></script>
    <?php endif; ?>

    <!-- Phase 3.1-3.4: montaggio centralizzato di tutti i componenti React.
         AppRouter gestisce le pagine SPA; i componenti sidebar non hanno più
         script ct:ready nei singoli .inc.php — tutto passa da qui.
         LinkMenu ha bisogno di props PHP, quindi viene montato qui
         invece che in link_menu.inc.php (rimosso in Phase 3.4). -->
    <?php
    $isStaff = (($_SESSION['admin'] ?? 0) == 1 || ($_SESSION['moderatore'] ?? 0) == 1 || ($_SESSION['master'] ?? 0) == 1) ? 'true' : 'false';

    // Props per LinkMenu: voci di menu dalla configurazione del gioco.
    // Costruisce l'array di voci filtrando quelle senza URL.
    $lm_items = [];
    if (!empty($PARAMETERS['menu'])) {
        foreach ($PARAMETERS['menu'] as $key => $item) {
            if (!empty($item['url'])) {
                $lm_items[] = [
                    'key'                => $key,
                    'url'                => $item['url'],
                    'text'               => $item['text']               ?? '',
                    'image_file'         => $item['image_file']         ?? '',
                    'image_file_onclick' => $item['image_file_onclick'] ?? '',
                ];
            }
        }
    }
    $lm_title       = $PARAMETERS['names']['gamemenu']['menu'] ?? '';
    $lm_theme       = $PARAMETERS['themes']['current_theme'];
    $lm_showGotomap = ($PARAMETERS['mode']['gotomap_list'] === 'ON') ? 'true' : 'false';
    ?>
    <script>
    document.addEventListener('ct:ready', function() {

        // SPA router — gestisce le pagine migrate (forum, messages_center, presenti_estesi)
        var el = document.getElementById('ct-app-content');
        if (el) CT.mount('AppRouter', 'ct-app-content', { isStaff: <?= $isStaff ?> });

        // ── Sidebar sinistra ────────────────────────────────────────────────
        if (document.getElementById('info-location-container'))
            CT.mount('InfoLocation', 'info-location-container', {});

        // LinkMenu riceve props PHP pre-elaborati (menu voci, titolo, tema, gotomap)
        if (document.getElementById('link-menu-container'))
            CT.mount('LinkMenu', 'link-menu-container', {
                menuItems:   <?= json_encode($lm_items) ?>,
                menuTitle:   <?= json_encode($lm_title) ?>,
                theme:       <?= json_encode($lm_theme) ?>,
                showGotomap: <?= $lm_showGotomap ?>
            });

        if (document.getElementById('frame-messaggi-container'))
            CT.mount('FrameMessaggi', 'frame-messaggi-container', {});

        // ── Sidebar destra ──────────────────────────────────────────────────
        if (document.getElementById('anteprima-scheda-container'))
            CT.mount('AnteprimaScheda', 'anteprima-scheda-container', {});

        if (document.getElementById('menu-icons-container'))
            CT.mount('MenuIcons', 'menu-icons-container', {});

        // OnlineUsers: era montato da frame_online.inc.php, ora centralizzato qui
        if (document.getElementById('online-users-container'))
            CT.mount('OnlineUsers', 'online-users-container', {});
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