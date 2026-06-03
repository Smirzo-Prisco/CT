<?php
/**
 * api_chatHelp.php — Soglie danno per la pagina help chat
 */
session_start();
require_once(__DIR__ . '/../config.inc.php');
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/functions.inc.php');
header('Content-Type: application/json');

$soglie = [];
$res = gdrcd_query("SELECT livello, danno FROM gilda_soglie ORDER BY livello", 'result');
while ($row = gdrcd_query($res, 'fetch')) {
    $soglie[] = ['livello' => (int)$row['livello'], 'danno' => $row['danno']];
}
gdrcd_query($res, 'free');

echo json_encode(['soglie' => $soglie]);
