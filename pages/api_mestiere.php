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
        $pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere, id_mestiere, id_ruolo_mestiere FROM personaggio WHERE nome = '$login'");
        $exp         = (int)($pg['esperienza']         ?? 0);
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);

        $conferma       = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'");
        $hasConferma    = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
        $currentIdRuolo = $conferma ? (int)$conferma['id_ruolo'] : 0;
        $step           = $hasConferma ? 3 : 2;

        $res      = gdrcd_query("SELECT id_ruolo, nome_ruolo, immagine, mestiere FROM ruolo_mestiere WHERE livello_mestiere = 3 ORDER BY nome_ruolo", 'result');
        $mestieri = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $mestieri[] = [
                'id'       => (int)$row['id_ruolo'],
                'nome'     => gdrcd_filter('out', (string)$row['nome_ruolo']),
                'immagine' => (string)$row['immagine'],
                'mestiere' => (int)$row['mestiere'],
                'selected' => ((int)$row['id_ruolo'] === $currentIdRuolo),
            ];
        }
        gdrcd_query($res, 'free');

        echo json_encode([
            'success'        => true,
            'step'           => $step,
            'esperienza'     => $exp,
            'expMestiere'    => $expMestiere,
            'hasConferma'    => $hasConferma,
            'currentIdRuolo' => $currentIdRuolo,
            'mestieri'       => $mestieri,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
