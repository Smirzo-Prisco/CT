<?php
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
        $pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere FROM personaggio WHERE nome = '$login'");
        $exp         = (int)($pg['esperienza'] ?? 0);
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);
        echo json_encode(['switch' => 'getState reached', 'exp' => $exp]);
        break;
    default:
        echo json_encode(['switch' => 'default', 'op' => $op]);
}
