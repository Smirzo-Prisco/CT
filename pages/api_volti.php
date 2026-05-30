<?php
/**
 * api_volti.php — Elenco volti assegnati ai personaggi
 *
 * Endpoint:
 *   GET  ?op=getVolti   — Restituisce la lista dei PG con volto assegnato,
 *                         ordinati per volto. Include flag isAdmin per la UI.
 *   POST ?op=deleteVolto — (solo admin) Rimuove il volto da un personaggio.
 *
 * @author Crystal Tokyo Dev
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

$op = $_GET['op'] ?? '';

switch ($op) {

    // -------------------------------------------------------------------------
    // getVolti — lista PG con volto assegnato
    // -------------------------------------------------------------------------
    case 'getVolti':
        $res = gdrcd_query(
            "SELECT nome, volto FROM personaggio
             WHERE volto != '' AND volto != 'niente'
             ORDER BY volto",
            'result'
        );
        $list = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $list[] = [
                'nome'  => gdrcd_filter('out', $row['nome']),
                'volto' => gdrcd_filter('out', $row['volto']),
            ];
        }
        gdrcd_query($res, 'free');

        echo json_encode([
            'success' => true,
            'volti'   => $list,
            'isAdmin' => (bool)($_SESSION['admin'] ?? 0),
        ]);
        break;

    // -------------------------------------------------------------------------
    // deleteVolto — rimuove il volto di un personaggio (solo admin)
    // -------------------------------------------------------------------------
    case 'deleteVolto':
        if (empty($_SESSION['admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $nome = gdrcd_filter('in', $data['nome'] ?? '');

        if (empty($nome)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome mancante']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET volto = NULL WHERE nome = '$nome'");
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
