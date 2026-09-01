<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// header('Content-Type:text/html; charset=UTF-8');

/** * Se il personaggio è connesso avvio la gestione dei suoi spostamenti nella land
 * Il controllo va messo qui e non in main poichè in main risulterebbe trovarsi dopo l'inclusione del config
 * dando vita ad un bug sul tastino di aggiornamento della pagina corrente.
 * @author Blancks
 */
if( ! empty($_SESSION['login'])) {
    /** * Aggiornamento della posizione nella mappa del pg
     * @author Blancks
     */
    if(isset($_REQUEST['map_id']) && is_numeric($_REQUEST['map_id'])) {
        $_SESSION['luogo'] = -1;
        $_SESSION['mappa'] = $_REQUEST['map_id'];
    }

    if(isset($_REQUEST['dir']) && is_numeric($_REQUEST['dir'])) $_SESSION['luogo'] = $_REQUEST['dir'];
}

//Includo i parametri, la configurazione, la lingua e le funzioni
require_once('includes/required.php');

//Eseguo la connessione al database
$handleDBConnection = gdrcd_connect();

# Controllo del login
if(!empty($_SESSION['login'])){
    $me = gdrcd_filter('in',$_SESSION['login']);
    $check = gdrcd_query("SELECT count(nome) as TOT FROM personaggio WHERE ora_entrata > ora_uscita AND nome='{$me}' LIMIT 1");

    if($check['TOT'] == 0){
        session_destroy();
        die('Non sei collegato con nessun pg.');
    }

}

/** * CONTROLLO PER AGGIORNAMENTO DB
 * Il controllo viene lanciato solo in index e nelle pagine di installer/upgrade.
 * Dopo l'aggiornamento non dovrebbe dare noie.
 * Nel qual caso vogliate risparmiare risorse quando si visita la homepage però è possibile modificare la variabile $check_for_update in index.php e settarla a FALSE.
 * @author Blancks
 */
if(isset($check_for_update) && $check_for_update) {
    include('upgrade_details.php');
}
/** * Fine controllo di update */

/**    * Caricamento plugins.
 * I plugins non sono vitali all'esecuzione dell'engine, per cui si includono col comando include.
 * @author Blancks
 */

/* Caricamento bbdecoder */
if(($PARAMETERS['mode']['user_bbcode'] == 'ON' && $PARAMETERS['settings']['user_bbcode']['type'] == 'bbd') || $PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd') {
    include('plugins/bbdecoder/bbdecoder.php');
}
?>

<?php if (empty($GLOBALS['ct_is_guest_home'])): ?>
<!--Force IE6 into quirks mode with this comment tag-->
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" lang="it">
    <head>
        <base href="/">
        <meta charset="UTF-8">
<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
        <meta http-equiv="Content-Type" content="text/html;">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="description" content="Scopri Crystal Tokyo GDR, un GDR play by chat gratuito con combattimenti a dadi, famiglie magiche, crescita del personaggio e gioco narrativo condiviso.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <?php $favicon_v = @filemtime(__DIR__ . '/imgs/favicon.ico') ?: 0; ?>
        <link rel="icon" type="image/x-icon" href="imgs/favicon.ico?v=<?= $favicon_v ?>">
        <?php
        $theme_path = 'themes/' . $PARAMETERS['themes']['current_theme'];
        $css_v = static function(string $file) use ($theme_path): string {
            $abs = __DIR__ . '/' . $theme_path . '/' . $file;
            return $theme_path . '/' . $file . '?v=' . (file_exists($abs) ? filemtime($abs) : 0);
        };
        ?>
        <?php if (!empty($_SESSION['login'])): ?>
        <link rel="stylesheet" href="<?=$css_v('main.css')?>" type="text/css">
        <!-- ct-styles.css: compilato da SCSS, source of truth — sovrascrive main.css e tutti i vecchi CSS -->
        <!-- homepage.css, chat.css, presenti.css, scheda.css, messaggi.css, forum.css, lettura_bacheca.css rimossi: tutte le regole sono ora in ct-styles.css -->
        <link rel="stylesheet" href="<?=$css_v('ct-styles.css')?>" type="text/css">
        <link rel="stylesheet" href="/themes/crystal/fontawesome/css/all.min.css">
        <?php else: ?>
        <!-- Per ospiti: critical CSS inline (above-fold, ~5 KiB) elimina render-blocking.
             public.css completo caricato async; public/_head.php salta le risorse già qui. -->
        <?php
        $critical_css = @file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/public/critical.min.css');
        if ($critical_css): ?>
        <style><?= $critical_css ?></style>
        <?php endif; ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap"></noscript>
        <link rel="preload" href="/themes/crystal/fontawesome/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="/themes/crystal/fontawesome/css/all.min.css"></noscript>
        <?php $pcss_v = @filemtime($_SERVER['DOCUMENT_ROOT'] . '/public/public.css') ?: 0; ?>
        <link rel="preload" as="style" href="/public/public.css?v=<?= $pcss_v ?>" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="/public/public.css?v=<?= $pcss_v ?>"></noscript>
        <?php $GLOBALS['ct_public_assets_in_head'] = true; ?>
        <?php endif; ?>
        <?php
        /** * Il controllo individua se l'header non è impiegato per il main */
        if(!isset($check_for_update)): ?>
            <link rel="stylesheet" href="layouts/<?=$PARAMETERS['themes']['kind_of_layout'], '_frames.php?css=true'; ?>" type="text/css">
        <?php endif; ?>

        <title><?= empty($_SESSION['login']) ? 'Crystal Tokyo – GDR play by chat' : $PARAMETERS['info']['site_name']; ?></title>

        <!-- SEO: Open Graph, Twitter Card e JSON-LD — solo per visitatori non autenticati -->
        <?php if (empty($_SESSION['login'])): ?>
        <link rel="canonical" href="https://crystaltokyo.it/">
        <meta property="og:type"         content="website">
        <meta property="og:url"          content="https://crystaltokyo.it/">
        <meta property="og:title"        content="Crystal Tokyo – GDR play by chat">
        <meta property="og:description"  content="Scopri Crystal Tokyo GDR, un gioco di ruolo online play by chat gratuito, attivo da oltre vent'anni. Urban fantasy, razze magiche, combattimenti a dadi.">
        <meta property="og:image"        content="https://crystaltokyo.it/themes/crystal/imgs/homepage.png">
        <meta property="og:locale"       content="it_IT">
        <meta name="twitter:card"        content="summary_large_image">
        <meta name="twitter:title"       content="Crystal Tokyo – GDR play by chat">
        <meta name="twitter:description" content="Gioco di ruolo online gratuito, urban fantasy. Vent'anni di gioco narrativo condiviso.">
        <meta name="twitter:image"       content="https://crystaltokyo.it/themes/crystal/imgs/homepage.png">
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "VideoGame",
            "name": "Crystal Tokyo GDR",
            "url": "https://crystaltokyo.it",
            "description": "Gioco di ruolo online play by chat gratuito, ambientato in un mondo urban fantasy. Attivo da oltre vent'anni, con razze magiche, quest, sistema a dadi e crescita del personaggio.",
            "genre": ["Gioco di ruolo", "Play by chat", "Urban Fantasy"],
            "gamePlatform": "Browser web",
            "inLanguage": "it",
            "isAccessibleForFree": true,
            "operatingSystem": "Browser",
            "applicationCategory": "Game"
        }
        </script>
        <?php endif; ?>

        <!-- CT_USER: dati utente per il bundle React e per socket.io-client (bundlato in ct-app.js).
             Solo per utenti autenticati. -->
        <?php if (!empty($_SESSION['login'])): ?>
        <?php
        $pg_avatar = '';
        $pg_sesso = 'm';
        $pg_disponibile = 1;
        $r_avatar = gdrcd_query("SELECT url_img_chat, sesso, disponibile FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1");
        if ($r_avatar) {
            $pg_avatar = trim($r_avatar['url_img_chat'] ?? '');
            $pg_sesso = $r_avatar['sesso'] ?? 'm';
            $pg_disponibile = (int)($r_avatar['disponibile'] ?? 1);
        }
        ?>
        <script>
        window.CT_USER = {
            login:        <?=json_encode($_SESSION['login'])?>,
            luogo:        <?=(int)($_SESSION['luogo'] ?? 0)?>,
            mappa:        <?=(int)($_SESSION['mappa'] ?? 0)?>,
            url_img_chat: <?=json_encode($pg_avatar)?>,
            sesso:        <?=json_encode($pg_sesso)?>,
            disponibile:  <?=$pg_disponibile?>,
            soundPrefs: {
                dm:     <?=(int)($_SESSION['suono_dm']     ?? 1)?>,
                chat:   <?=(int)($_SESSION['suono_chat']   ?? 1)?>,
                scheda: <?=(int)($_SESSION['suono_scheda'] ?? 1)?>
            }
        };
        window.ctSocket = null;
        </script>

        <!-- CT_ASSET_VERSIONS: mtime di file statici caricati dinamicamente da React
             (chat.js, role_session.js, ecc. + le immagini mappa giorno/notte) — nginx
             li mette in cache 1 anno assumendo URL versionati con ?v=; senza questo,
             il browser non vede mai gli aggiornamenti (serviva "svuota cache" a mano). -->
        <script>
        window.CT_ASSET_VERSIONS = {
            <?php
            $assetFiles = [
                'chat.js' => 'includes/chat.js',
                'role_session.js' => 'includes/role_session.js',
                'incremento_parametri.js' => 'includes/incremento_parametri.js',
                'mercato_abilita.js' => 'includes/mercato_abilita.js',
                'mappa_giorno.png' => 'themes/crystal/imgs/maps/mappa_giorno.png',
                'mappa_notte.png' => 'themes/crystal/imgs/maps/mappa_notte.png',
                'map-pin.jpg' => 'themes/crystal/imgs/maps/map-pin.jpg',
            ];
            $assetVersions = [];
            foreach ($assetFiles as $assetKey => $assetRelPath) {
                $assetPath = __DIR__ . '/' . $assetRelPath;
                $assetVersions[] = json_encode($assetKey) . ': ' . (file_exists($assetPath) ? filemtime($assetPath) : 0);
            }
            echo implode(",\n            ", $assetVersions);
            ?>
        };
        </script>
        <?php endif; ?>

        <!-- Bundle React — solo per utenti autenticati; type="module" è sempre deferred -->
        <?php if (!empty($_SESSION['login']) && file_exists(__DIR__ . '/themes/crystal/dist/ct-app.js')): ?>
        <?php if (file_exists(__DIR__ . '/themes/crystal/dist/ct-main.css')): ?>
        <?php $css_v = filemtime(__DIR__ . '/themes/crystal/dist/ct-main.css'); ?>
        <link rel="stylesheet" href="/themes/crystal/dist/ct-main.css?v=<?= $css_v ?>" type="text/css">
        <?php endif; ?>
        <?php $js_v = filemtime(__DIR__ . '/themes/crystal/dist/ct-app.js'); ?>
        <script type="module" src="/themes/crystal/dist/ct-app.js?v=<?= $js_v ?>"></script>
        <?php endif; ?>

        <!-- Listener ct:ready: monta i componenti React; solo per utenti autenticati -->
        <?php if (!empty($_SESSION['login'])): ?>
        <?php
        $isStaff = (($_SESSION['admin'] ?? 0) == 1 || ($_SESSION['moderatore'] ?? 0) == 1 || ($_SESSION['master'] ?? 0) == 1) ? 'true' : 'false';
        ?>
        <script>
        document.addEventListener('ct:ready', function() {
            var el = document.getElementById('ct-app-content');
            if (el) CT.mount('AppRouter', 'ct-app-content', { isStaff: <?= $isStaff ?> });

            // HUD immersivo: sostituisce InfoLocation/PresentiBadge/OnlineUsers/
            // ChattingOff/AnteprimaScheda/FrameMessaggi/Meteo (montati singolarmente
            // in precedenza) con i due anelli + topbar in un unico componente.
            if (document.getElementById('hud-container'))
                CT.mount('Hud', 'hud-container', { isStaff: <?= $isStaff ?> });

            if (document.getElementById('chatbot-widget-container'))
                CT.mount('ChatbotWidget', 'chatbot-widget-container', {});

            if (document.getElementById('skill-desc-modal-container'))
                CT.mount('SkillDescModal', 'skill-desc-modal-container', {});
        });
        </script>
        <?php endif; ?>
    </head>
    <body class="main_body">
<?php if (!empty($_SESSION['login'])): ?>
<div id="chatbot-widget-container"></div>
<div id="skill-desc-modal-container"></div>
<?php endif; ?>
<?php endif; // ct_is_guest_home ?>
<?php
    /** * CONTROLLO PER AGGIORNAMENTO DB
     * Il controllo viene lanciato solo in index e nelle pagine di installer/upgrade.
     * Dopo l'aggiornamento non dovrebbe dare noie.
     * Nel qual caso vogliate risparmiare risorse quando si visita la homepage però è possibile modificare la variabile $check_for_update in index.php e settarla a FALSE.
     * @author Blancks
     */
    if((($table == 0) && isset($dont_check) && ! $dont_check) && isset($check_for_update) && $check_for_update) {
        echo '<div class="error">', $MESSAGE['error']['db_empty'], '</div>', '<div class="link_back"><a href="installer.php">', gdrcd_filter_out($MESSAGE['installer']['instal']), '</a></div>', '</body></html>';
        exit();
    } elseif((isset($updating_queryes[0]) && ! empty($updating_queryes[0]) && ! $dont_check) && isset($check_for_update) && $check_for_update) {
        echo '<div class="error">', $MESSAGE['error']['db_not_updated'], '</div>';

        if($updating_password) echo '<div class="error">', $MESSAGE['warning']['pass_not_encripted'], '</div>';

        echo '<div class="link_back"><a href="upgrade.php">', gdrcd_filter_out($MESSAGE['homepage']['updater']['update']), '</a></div>', '</body></html>';

        exit();
    }
?>