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
        
        <link rel="icon" type="image/x-icon" href="imgs/favicon.ico">
        <?php
        $theme_path = 'themes/' . $PARAMETERS['themes']['current_theme'];
        $css_v = static function(string $file) use ($theme_path): string {
            $abs = __DIR__ . '/' . $theme_path . '/' . $file;
            return $theme_path . '/' . $file . '?v=' . (file_exists($abs) ? filemtime($abs) : 0);
        };
        ?>
        <link rel="stylesheet" href="<?=$css_v('main.css')?>" type="text/css">
        <!-- ct-styles.css: compilato da SCSS, source of truth — sovrascrive main.css e tutti i vecchi CSS -->
        <!-- homepage.css, chat.css, presenti.css, scheda.css, messaggi.css, forum.css, lettura_bacheca.css rimossi: tutte le regole sono ora in ct-styles.css -->
        <link rel="stylesheet" href="<?=$css_v('ct-styles.css')?>" type="text/css">

        <link rel="stylesheet" href="/themes/crystal/fontawesome/css/all.min.css">
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
        <meta property="og:description"  content="Scopri Crystal Tokyo GDR, un gioco di ruolo online play by chat gratuito, attivo da oltre vent'anni. Urban fantasy, famiglie magiche, combattimenti a dadi.">
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
            "description": "Gioco di ruolo online play by chat gratuito, ambientato in un mondo urban fantasy. Attivo da oltre vent'anni, con famiglie magiche, quest, sistema a dadi e crescita del personaggio.",
            "genre": ["Gioco di ruolo", "Play by chat", "Urban Fantasy"],
            "gamePlatform": "Browser web",
            "inLanguage": "it",
            "isAccessibleForFree": true,
            "operatingSystem": "Browser",
            "applicationCategory": "Game"
        }
        </script>
        <?php endif; ?>

        <!-- CT_USER + Socket.io: spostati nell'<head> per garantire disponibilità
             anche quando footer.inc.php non esegue (die() nel contenuto della pagina) -->
        <?php if (isset($_SESSION['login'])): ?>
        <?php
        $pg_avatar = '';
        $r_avatar = gdrcd_query("SELECT url_img_chat FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1");
        if ($r_avatar) $pg_avatar = trim($r_avatar['url_img_chat'] ?? '');
        ?>
        <script>
        window.CT_USER = {
            login:        <?=json_encode($_SESSION['login'])?>,
            luogo:        <?=(int)($_SESSION['luogo'] ?? 0)?>,
            mappa:        <?=(int)($_SESSION['mappa'] ?? 0)?>,
            url_img_chat: <?=json_encode($pg_avatar)?>,
            soundPrefs: {
                dm:     <?=(int)($_SESSION['suono_dm']     ?? 1)?>,
                chat:   <?=(int)($_SESSION['suono_chat']   ?? 1)?>,
                scheda: <?=(int)($_SESSION['suono_scheda'] ?? 1)?>
            }
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

        <!-- Bundle React — type="module" è sempre deferred: esegue dopo il DOM
             anche se caricato nell'<head>. Posizionato qui (anziché in footer)
             per garantire che sia in pagina anche se footer.inc.php non esegue
             a causa di un die() interno al contenuto della pagina. -->
        <?php if (file_exists(__DIR__ . '/themes/crystal/dist/ct-app.js')): ?>
        <?php if (file_exists(__DIR__ . '/themes/crystal/dist/ct-main.css')): ?>
        <?php $css_v = filemtime(__DIR__ . '/themes/crystal/dist/ct-main.css'); ?>
        <link rel="stylesheet" href="/themes/crystal/dist/ct-main.css?v=<?= $css_v ?>" type="text/css">
        <?php endif; ?>
        <?php $js_v = filemtime(__DIR__ . '/themes/crystal/dist/ct-app.js'); ?>
        <script type="module" src="/themes/crystal/dist/ct-app.js?v=<?= $js_v ?>"></script>
        <?php endif; ?>

        <!-- Listener ct:ready: monta i componenti React sidebar e AppRouter.
             Deve stare nell'<head> per la stessa ragione del bundle. -->
        <?php
        $isStaff = (($_SESSION['admin'] ?? 0) == 1 || ($_SESSION['moderatore'] ?? 0) == 1 || ($_SESSION['master'] ?? 0) == 1) ? 'true' : 'false';
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
            var el = document.getElementById('ct-app-content');
            if (el) CT.mount('AppRouter', 'ct-app-content', { isStaff: <?= $isStaff ?> });

            if (document.getElementById('info-location-container'))
                CT.mount('InfoLocation', 'info-location-container', {});

            if (document.getElementById('link-menu-container'))
                CT.mount('LinkMenu', 'link-menu-container', {
                    menuItems:   <?= json_encode($lm_items) ?>,
                    menuTitle:   <?= json_encode($lm_title) ?>,
                    theme:       <?= json_encode($lm_theme) ?>,
                    showGotomap: <?= $lm_showGotomap ?>
                });

            if (document.getElementById('frame-messaggi-container'))
                CT.mount('FrameMessaggi', 'frame-messaggi-container', {});

            if (document.getElementById('chattina-off-container'))
                CT.mount('ChattingOff', 'chattina-off-container', {});

            if (document.getElementById('anteprima-scheda-container'))
                CT.mount('AnteprimaScheda', 'anteprima-scheda-container', {});

            if (document.getElementById('online-users-container'))
                CT.mount('OnlineUsers', 'online-users-container', {});

            if (document.getElementById('meteo-container'))
                CT.mount('Meteo', 'meteo-container', {});

            if (document.getElementById('chatbot-widget-container'))
                CT.mount('ChatbotWidget', 'chatbot-widget-container', {});
        });
        </script>
    </head>
    <body class="main_body">
<?php if (!empty($_SESSION['login'])): ?>
<div id="chatbot-widget-container"></div>
<?php endif; ?>
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