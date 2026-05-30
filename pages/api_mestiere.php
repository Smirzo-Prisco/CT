<?php
// DEBUG: scrive l'ultimo errore su file (funziona anche se Apache fa override della risposta 500)
register_shutdown_function(function () {
    $e = error_get_last();
    $log = __DIR__ . '/mestiere_debug.log';
    file_put_contents($log, date('H:i:s') . ' ' . json_encode($e) . "\n", FILE_APPEND | LOCK_EX);
});
ini_set('error_log',  __DIR__ . '/mestiere_debug.log');
ini_set('log_errors', '1');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
gdrcd_connect();

if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$login = gdrcd_filter('in', $_SESSION['login']);
$op    = $_GET['op'] ?? '';
session_write_close();

switch ($op) {
    case 'getState':
        echo json_encode(['ok' => true, 'op' => $op]);
        break;

    case 'change':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }

        $existing = gdrcd_query("SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'result');
        if (gdrcd_query($existing, 'num_rows') >= 1) {
            gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'");
        } else {
            gdrcd_query("INSERT INTO clgpersonaggiomestiere (id_ruolo, personaggio) VALUES ($idRuolo, '$login')");
        }
        gdrcd_query($existing, 'free');
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'");

        echo json_encode(['success' => true, 'message' => 'Mestiere aggiornato']);
        break;

    case 'pick':
        $pg = gdrcd_query("SELECT esperienza FROM personaggio WHERE nome = '$login'");
        echo json_encode(['debug' => 'pick-step1', 'pg_type' => gettype($pg)]);
        break;

    default:
        echo json_encode(['op' => $op]);
}
