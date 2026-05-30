<?php
/**
 * api_statuto.php — Dati statuto per Statuto.jsx
 *
 * op=getMenu&id=N   — sezioni accordion per gilda N
 * op=getMenu&id2=N  — sezioni accordion per mestiere N
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

$op  = $_GET['op'] ?? '';
$id  = (int)($_GET['id']  ?? 0);
$id2 = (int)($_GET['id2'] ?? 0);

if ($op !== 'getMenu') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
    exit;
}

/**
 * Carica gli articoli di un tipo/campo e restituisce un array sezione.
 * Ritorna null se non ci sono articoli.
 */
function buildSection($tipo, $campo, $valore, $classe) {
    $valore = (int)$valore;
    $res    = gdrcd_query(
        "SELECT articolo, titolo FROM statuti_new WHERE tipo='$tipo' AND $campo='$valore' ORDER BY articolo",
        'result'
    );
    $items = [];
    while ($row = gdrcd_query($res, 'fetch')) {
        $items[] = [
            'articolo' => (int)$row['articolo'],
            'titolo'   => gdrcd_filter('out', $row['titolo']),
        ];
    }
    gdrcd_query($res, 'free');
    if (!$items) return null;
    return ['classe' => $classe, 'items' => $items];
}

$sections = [];

if ($id > 0 && $id < 9) {
    $sections[] = buildSection('storia',    'id_gilda', $id, 'ambientazione');
    $sections[] = buildSection('statuto',   'id_gilda', $id, 'regolamento');
    $sections[] = buildSection('skill',     'id_gilda', $id, 'primi_passi');
    $sections[] = buildSection('requisiti', 'id_gilda', $id, 'manuale');
}

if ($id === 9) {
    $sections[] = buildSection('cittadini', 'id_gilda', $id, 'cittadini');
    $sections[] = buildSection('sit',       'id_gilda', $id, 'sit');
    $sections[] = buildSection('wiccan',    'id_gilda', $id, 'wikkan');
    $sections[] = buildSection('scorpion',  'id_gilda', $id, 'scorpion');
}

if ($id2 > 0) {
    $sections[] = buildSection('storia',    'id_mestiere', $id2, 'statutom');
    $sections[] = buildSection('statuto',   'id_mestiere', $id2, 'descrizione');
    $sections[] = buildSection('skill',     'id_mestiere', $id2, 'cariche');
    $sections[] = buildSection('requisiti', 'id_mestiere', $id2, 'specifiche');
}

$sections = array_values(array_filter($sections));

echo json_encode(
    ['success' => true, 'menu' => $sections],
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
