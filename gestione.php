<?php
/**
 * gestione.php — Entry point per i tool di staff (pagine NON migrate a React)
 *
 * Gestisce tutte le sotto-pagine della sezione gestione:
 *   gestione_personaggio, gestione_gilde, gestione_razze, gestione_oggetti, ecc.
 *
 * Accesso limitato al solo staff (admin, master, moderatore, capogilda, ecc.).
 * Le pagine utente migrate a React restano su main.php.
 *
 * @author Crystal Tokyo Dev
 */

require('header.inc.php');
gdrcd_controllo_sessione();

$scripts = [];
function add_script(string $path): void { global $scripts; $scripts[] = $path; }

// Solo staff può accedere
$is_staff = ($_SESSION['admin'] ?? 0) || ($_SESSION['master'] ?? 0)
    || ($_SESSION['moderatore'] ?? 0) || ($_SESSION['capogilda'] ?? 0)
    || ($_SESSION['capomestiere'] ?? 0) || ($_SESSION['guida'] ?? 0);

if (!$is_staff) {
    header('Location: main.php');
    exit;
}

// Routing: carica la sotto-pagina richiesta (default: hub gestione)
$page         = isset($_REQUEST['page']) ? gdrcd_filter('include', $_REQUEST['page']) : 'gestione';
$strInnerPage = $page . '.inc.php';

if (gdrcd_controllo_esilio($_SESSION['login']) === true) session_destroy();
else require('layouts/' . $PARAMETERS['themes']['kind_of_layout'] . '_frames.php');

require('footer.inc.php');
