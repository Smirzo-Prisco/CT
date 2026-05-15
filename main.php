<?php
/**
 * main.php — Entry point Crystal Tokyo GDR (thin shell finale)
 *
 * Tutte le pagine utente sono migrate a React: AppRouter gestisce il routing
 * client-side in base alla URL. Questo file serve solo il contenitore HTML.
 *
 * Ruolo:
 *   1. Sessione e autenticazione (header.inc.php)
 *   2. DB update per navigazioni PHP dirette verso mappa/stanze (fallback)
 *   3. Layout con <div id="ct-app-content"></div> → React monta AppRouter
 *
 * Per i tool di staff (gestione_personaggio, gestione_gilde, ecc.) usare
 * gestione.php, che mantiene il routing PHP classico.
 *
 * @author Crystal Tokyo Dev
 */

require('header.inc.php');
gdrcd_controllo_sessione();

$scripts = [];
function add_script(string $path): void { global $scripts; $scripts[] = $path; }

// ── DB update per cambio mappa (navigazione PHP diretta) ──────────────────────
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

// ── DB update per navigazione diretta stanza/mappa ────────────────────────────
if (isset($_REQUEST['dir']) && is_numeric($_REQUEST['dir']) && !empty($_SESSION['login'])) {
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

// ── Tutte le pagine sono React: nessun routing PHP ────────────────────────────
$strInnerPage = null;

if (gdrcd_controllo_esilio($_SESSION['login']) === true) session_destroy();
else require('layouts/' . $PARAMETERS['themes']['kind_of_layout'] . '_frames.php');

require('footer.inc.php');
