<?php
/**
 * main.php — Entry point Crystal Tokyo GDR (thin shell)
 *
 * Ruolo:
 *   1. Sessione, config e autenticazione (via header.inc.php)
 *   2. DB update per navigazioni PHP dirette verso mappa/stanze
 *      (le navigazioni SPA usano api_map.php — questo è il fallback)
 *   3. Routing: pagine migrate → $strInnerPage = null → React prende il controllo;
 *      pagine NON migrate (gestione sub-pagine, tool staff) → include .inc.php PHP
 *   4. Layout (left-right_frames.php) + footer React bundle
 *
 * @author Crystal Tokyo Dev
 */

require('header.inc.php');
gdrcd_controllo_sessione();

/** Raccoglie i path JS specifici di pagina da includere nel footer via add_script() */
$scripts = [];
function add_script(string $path): void { global $scripts; $scripts[] = $path; }

// ── Pagine migrate a React (AppRouter le gestisce client-side) ────────────────
// Quando $strInnerPage = null il layout emette solo <div id="ct-app-content"></div>.
// CSS e rendering sono a carico di AppRouter/injectCSS.
const MIGRATED_PAGES = [
    'forum', 'messages_center', 'presenti_estesi', 'mappaclick',
    'scheda', 'scheda_storia', 'scheda_dice', 'scheda_off',
    'scheda_skills', 'scheda_trans', 'scheda_modifica', 'scheda_affetti',
    'scheda_equip', 'scheda_oggetti',
    'scheda_px', 'scheda_px_shin', 'scheda_px_mestiere',
    'gestione', 'uffici',
];

// ── Aggiornamento DB per cambio mappa (navigazione PHP diretta) ───────────────
if (!empty($_GET['map_id']) && is_numeric($_GET['map_id']) && !empty($_SESSION['login'])) {
    $old_luogo = (int)(gdrcd_query(
        "SELECT ultimo_luogo FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
    )['ultimo_luogo'] ?? -1);
    $_SESSION['mappa'] = (int)$_GET['map_id'];
    gdrcd_query(
        "UPDATE personaggio SET ultima_mappa=" . gdrcd_filter('num', $_SESSION['mappa']) .
        ", ultimo_luogo=-1 WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
    );
    notifySocketServer('users:update', 'loc:' . $old_luogo);
    notifySocketServer('users:update', 'loc:-1');
}

// ── Routing ───────────────────────────────────────────────────────────────────
if (isset($_REQUEST['page'])) {
    $page = gdrcd_filter('include', $_REQUEST['page']);
    // Pagina migrata: React
    if (in_array($page, MIGRATED_PAGES)) {
        $strInnerPage = null;
    } else {
        // Pagina non migrata (es. gestione_personaggio): PHP classico
        $strInnerPage = $page . '.inc.php';
    }

} elseif (isset($_REQUEST['dir']) && is_numeric($_REQUEST['dir'])) {
    // Navigazione PHP diretta verso stanza o mappa — AppRouter gestisce ChatShell/MapClick
    $strInnerPage = null;

    if (!empty($_SESSION['login'])) {
        $old_luogo = (int)(gdrcd_query(
            "SELECT ultimo_luogo FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
        )['ultimo_luogo'] ?? -1);
        gdrcd_query(
            "UPDATE personaggio SET ultimo_luogo=" . gdrcd_filter('num', $_REQUEST['dir']) .
            " WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
        );
        notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:' . (int)$_REQUEST['dir']);
    }

} else {
    // Default: mappa — migrata
    $strInnerPage = null;
    $_REQUEST['id_map'] = $_SESSION['mappa'];
}

// ── Layout + footer ───────────────────────────────────────────────────────────
if (gdrcd_controllo_esilio($_SESSION['login']) === true) session_destroy();
else require('layouts/' . $PARAMETERS['themes']['kind_of_layout'] . '_frames.php');

require('footer.inc.php');
