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
        if ((int)($pg['esperienza'] ?? 0) < 10) {
            echo json_encode(['success' => false, 'message' => 'Servono almeno 10 punti esperienza per confermare il mestiere']);
            exit;
        }

        $data     = json_decode(file_get_contents('php://input'), true);
        $mestiere = (int)($data['mestiere'] ?? 0);
        $titolo   = gdrcd_filter('in', "Conferma nel mestiere - $login");
        $msg      = gdrcd_filter('in', "Il personaggio [b]$login[/b] ha appena confermato il ruolo nel mestiere");

        gdrcd_query("UPDATE clgpersonaggiomestiere SET conferma_mestiere = 1 WHERE personaggio = '$login'");

        $araldo = gdrcd_query("SELECT id_araldo FROM araldo WHERE tipo = 6 AND proprietari = $mestiere");
        if ($araldo && !empty($araldo['id_araldo'])) {
            $id  = (int)$araldo['id_araldo'];
            $sql = "INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista)"
                 . " VALUES ('-1', $id, '$titolo', '$msg', '$login', NOW(), NOW(), 'no', 'no')";
            gdrcd_query($sql);
        }

        echo json_encode(['success' => true, 'message' => 'Ruolo nel mestiere confermato. Esci e rientra per vedere le modifiche.']);
        break;

    default:
        echo json_encode(['op' => $op]);
}
