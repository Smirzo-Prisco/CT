<?php
/**
 * api_narrazione_admin.php — API JSON per il pannello admin "Narrazioni IA"
 *
 * op = list    (GET, admin)  — elenco richieste di rigenerazione completa
 * op = approva (POST, admin) — approva una richiesta (verrà elaborata dal
 *                               worker cron/narrazione_worker.php)
 * op = rifiuta (POST, admin) — rifiuta una richiesta
 *
 * Non gestisce narrazione_queue (riassunti automatici silenziosi): quella
 * coda non richiede approvazione, vedi includes/narrazione_functions.inc.php.
 */
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$is_admin = (($_SESSION['admin'] ?? 0) == 1);
if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    // -------------------------------------------------------------------------
    // LIST — tutte le richieste, più recenti prima
    // -------------------------------------------------------------------------
    case 'list':
        $result = gdrcd_query("SELECT id, pg_name, stato, creato_il, approvato_da, approvato_il, completato_il, errore
            FROM narrazione_richieste ORDER BY creato_il DESC", 'result');
        $richieste = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $richieste[] = $row;
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'richieste' => $richieste]);
        break;

    // -------------------------------------------------------------------------
    // APPROVA — la richiesta passa a 'approvata', il worker la elaborerà
    // -------------------------------------------------------------------------
    case 'approva':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Richiesta non specificata']); exit; }
        $admin_esc = gdrcd_filter('in', $_SESSION['login']);
        gdrcd_query("UPDATE narrazione_richieste
            SET stato = 'approvata', approvato_da = '$admin_esc', approvato_il = NOW()
            WHERE id = $id AND stato = 'richiesta'");
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // RIFIUTA — la richiesta passa a 'rifiutata', non verrà elaborata
    // -------------------------------------------------------------------------
    case 'rifiuta':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Richiesta non specificata']); exit; }
        $admin_esc = gdrcd_filter('in', $_SESSION['login']);
        gdrcd_query("UPDATE narrazione_richieste
            SET stato = 'rifiutata', approvato_da = '$admin_esc', approvato_il = NOW()
            WHERE id = $id AND stato = 'richiesta'");
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
