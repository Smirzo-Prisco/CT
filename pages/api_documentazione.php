<?php
/**
 * api_documentazione.php — Dati menu per Documentazione.jsx
 *
 * op=getMenu — sezioni accordion dell'ambientazione/regolamento
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}
session_write_close();

$op = $_GET['op'] ?? '';

if ($op !== 'getMenu') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
    exit;
}

$SECTIONS = [
    ['tipo' => 'ambientazione', 'classe' => 'ambientazione'],
    ['tipo' => 'regolamento',   'classe' => 'regolamento'],
    ['tipo' => 'primipassi',    'classe' => 'primi_passi'],
    ['tipo' => 'manuali',       'classe' => 'manuale'],
    ['tipo' => 'combattimento', 'classe' => 'combattimento'],
    ['tipo' => 'staff',         'classe' => 'staff'],
];

$menu = [];
foreach ($SECTIONS as $sec) {
    $tipo = $sec['tipo'];
    $res  = gdrcd_query(
        "SELECT articolo, titolo FROM regolamento WHERE tipo='$tipo' ORDER BY articolo",
        'result'
    );
    $items = [];
    while ($row = gdrcd_query($res, 'fetch')) {
        $items[] = [
            'articolo' => (int)$row['articolo'],
            'titolo'   => $row['titolo'],
        ];
    }
    gdrcd_query($res, 'free');
    if ($items) {
        $menu[] = ['classe' => $sec['classe'], 'items' => $items];
    }
}

echo json_encode(
    ['success' => true, 'menu' => $menu],
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
