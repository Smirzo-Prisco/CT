<?php
/**
 * main.php — Entry point Crystal Tokyo GDR
 *
 * Ruolo attuale (Phase SPA ~85% completata):
 *   1. Sessione, config e autenticazione (via header.inc.php)
 *   2. Aggiornamento posizione in DB per navigazioni PHP dirette
 *      (accesso diretto via URL o refresh; le navigazioni SPA usano api_map.php)
 *   3. Routing: determina quale .inc.php includere nel layout
 *      Le pagine migrate a React restituiscono solo <div id="ct-app-content"></div>;
 *      AppRouter fa il rendering effettivo lato client.
 *   4. Layout (left-right_frames.php) + footer React bundle
 *
 * ── Roadmap thin shell (passo finale, quando tutte le pagine sono migrate) ──
 *   - Eliminare il blocco mappa/dir (i DB update vanno solo via api_map.php)
 *   - Eliminare il blocco routing ($strInnerPage)
 *   - In left-right_frames.php: sostituire gdrcd_load_modules() con
 *     <div id="ct-app-content"></div> direttamente nel markup
 *   - Risultato: ~20 righe — solo sessione + HTML shell
 */

require('header.inc.php');
gdrcd_controllo_sessione();

/** Raccoglie i path JS specifici di pagina da includere nel footer via add_script() */
$scripts = [];
function add_script(string $path): void { global $scripts; $scripts[] = $path; }

// ── Aggiornamento DB per cambio mappa (navigazione PHP diretta) ───────────────
// In SPA questo non passa mai per qui: MapClick.jsx chiama api_map.php?op=changemap
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

// ── Routing: determina quale .inc.php mostrare nel layout ─────────────────────
// TODO thin shell: rimuovere questo blocco quando tutte le pagine sono migrate.
if (isset($_REQUEST['page'])) {
    $strInnerPage = gdrcd_filter('include', $_REQUEST['page']) . '.inc.php';

} elseif (isset($_REQUEST['dir']) && is_numeric($_REQUEST['dir'])) {
    // Navigazione PHP diretta verso una stanza o la mappa
    // In SPA questo non passa per qui: AppRouter chiama api_map.php?op=move
    $strInnerPage = (int)$_REQUEST['dir'] >= 0 ? 'frame_chat.inc.php' : 'mappaclick.inc.php';
    if ((int)$_REQUEST['dir'] < 0) $_REQUEST['id_map'] = $_SESSION['mappa'];

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
    // Default: mappa
    $strInnerPage = 'mappaclick.inc.php';
    $_REQUEST['id_map'] = $_SESSION['mappa'];
}

// ── Layout + footer ───────────────────────────────────────────────────────────
if (gdrcd_controllo_esilio($_SESSION['login']) === true) session_destroy();
else require('layouts/' . $PARAMETERS['themes']['kind_of_layout'] . '_frames.php');

require('footer.inc.php');
