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
session_write_close();

$pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere, id_mestiere, id_ruolo_mestiere FROM personaggio WHERE nome = '$login'");
$exp         = (int)($pg['esperienza']         ?? 0);
$expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);

$conferma       = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'");
$hasConferma    = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
$currentIdRuolo = $conferma ? (int)$conferma['id_ruolo'] : 0;
$step           = $hasConferma ? 3 : 2;

echo json_encode(['test' => 'queries ok', 'step' => $step, 'exp' => $exp]);
