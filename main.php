<?php
/**
 * main.php — Entry point Crystal Tokyo GDR
 *
 * Ruolo:
 *   1. Sessione e autenticazione (header.inc.php)
 *   2. DB update per navigazioni PHP dirette verso mappa/stanze (fallback)
 *   3. Routing:
 *        - Pagine migrate a React → $strInnerPage = null → AppRouter
 *        - Pagine NON migrate (servizi_mercato, scegli_lavoro, ecc.) → PHP inc
 *        - Tool di staff → gestione.php (entry point separato)
 *
 * @author Crystal Tokyo Dev
 */

require('header.inc.php');
gdrcd_controllo_sessione();

$scripts = [];
function add_script(string $path): void { global $scripts; $scripts[] = $path; }

// Pagine migrate a React: AppRouter le gestisce client-side senza caricare .inc.php
const MIGRATED_PAGES = [
    'forum', 'messages_center', 'presenti_estesi', 'mappaclick',
    'scheda', 'scheda_storia', 'scheda_dice', 'scheda_off',
    'scheda_skills', 'scheda_trans', 'scheda_modifica', 'scheda_affetti',
    'scheda_equip', 'scheda_oggetti',
    'scheda_px', 'scheda_px_shin', 'scheda_px_mestiere',
    'gestione', 'uffici',
];

// ── DB update per cambio mappa (navigazione PHP diretta) ──────────────────────
if (!empty($_GET['map_id']) && is_numeric($_GET['map_id']) && !empty($_SESSION['login'])) {
    $old_luogo = (int)(gdrcd_query(
        "SELECT ultimo_luogo FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
    )['ultimo_luogo'] ?? -1);
    $_SESSION['mappa'] = (int)$_GET['map_id'];
    $_SESSION['luogo'] = -1;
    gdrcd_query(
        "UPDATE personaggio SET ultima_mappa=" . gdrcd_filter('num', $_SESSION['mappa']) .
        ", ultimo_luogo=-1 WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
    );
    session_write_close();
    notifySocketServer('users:update', 'loc:' . $old_luogo);
    notifySocketServer('users:update', 'loc:-1');
    notifySocketServer('presenti:update', 'global');
}

// ── Routing ───────────────────────────────────────────────────────────────────
if (isset($_REQUEST['page'])) {
    $page = gdrcd_filter('include', $_REQUEST['page']);
    // Pagina migrata: React gestisce tutto, nessun file .inc.php da caricare
    $strInnerPage = in_array($page, MIGRATED_PAGES) ? null : ($page . '.inc.php');

} elseif (isset($_REQUEST['dir']) && is_numeric($_REQUEST['dir'])) {
    // Navigazione diretta stanza/mappa: AppRouter gestisce ChatShell/MapClick
    $strInnerPage = null;

    if (!empty($_SESSION['login'])) {
        $old_luogo = (int)(gdrcd_query(
            "SELECT ultimo_luogo FROM personaggio WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
        )['ultimo_luogo'] ?? -1);
        gdrcd_query(
            "UPDATE personaggio SET ultimo_luogo=" . gdrcd_filter('num', $_REQUEST['dir']) .
            " WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'"
        );
        $_SESSION['luogo'] = (int)$_REQUEST['dir'];
        session_write_close();
        notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:' . (int)$_REQUEST['dir']);
        notifySocketServer('presenti:update', 'global');
    }

} else {
    // Default: mappa (migrata)
    $strInnerPage = null;
    $_REQUEST['id_map'] = $_SESSION['mappa'];
}

// ── Layout + footer ───────────────────────────────────────────────────────────
if (gdrcd_controllo_esilio($_SESSION['login']) === true) session_destroy();
else require('layouts/' . $PARAMETERS['themes']['kind_of_layout'] . '_frames.php');

require('footer.inc.php');
