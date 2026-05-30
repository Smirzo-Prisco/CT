<?php
/**
 * api_albergo.php — Prenotazione stanze private (albergo)
 *
 * Endpoint:
 *   GET  ?op=getRooms — Restituisce le stanze private con stato
 *                       (libera / occupata-da-me / occupata-da-altri).
 *   POST ?op=book     — Prenota una stanza libera per 4 ore.
 *
 * Nota: il sistema soldi è stato rimosso dal gioco. Il check su `soldi`
 * dell'implementazione PHP originale è stato eliminato.
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

$login = gdrcd_filter('in', $_SESSION['login']);
$op    = $_GET['op'] ?? '';

switch ($op) {

    // -------------------------------------------------------------------------
    // getRooms — lista stanze private con stato corrente
    // -------------------------------------------------------------------------
    case 'getRooms':
        $res = gdrcd_query(
            "SELECT mappa.id, mappa.nome AS luogo, mappa.proprietario,
                    mappa.invitati, mappa.scadenza, mappa_click.nome AS mappa_nome
             FROM mappa
             JOIN mappa_click ON mappa.id_mappa = mappa_click.id_click
             WHERE mappa.privata = 1
             ORDER BY mappa.nome, mappa.costo DESC",
            'result'
        );

        $rooms = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $now       = date('Y-m-d H:i:s');
            $occupied  = $row['scadenza'] > $now;
            $isMine    = $occupied && (
                $row['proprietario'] === gdrcd_capital_letter($_SESSION['login']) ||
                strpos($row['invitati'], gdrcd_capital_letter($_SESSION['login'])) !== false
            );

            if ($occupied && !$isMine) {
                $stato = 'occupata';
            } elseif ($isMine) {
                $stato = 'mia';
            } else {
                $stato = 'libera';
            }

            $rooms[] = [
                'id'    => (int)$row['id'],
                'nome'  => gdrcd_filter('out', $row['luogo']),
                'stato' => $stato,
            ];
        }
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'rooms' => $rooms]);
        break;

    // -------------------------------------------------------------------------
    // book — prenota una stanza libera (4 ore)
    // -------------------------------------------------------------------------
    case 'book':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Nessuna stanza selezionata']); exit;
        }

        // Verifica che la stanza sia effettivamente libera (scadenza passata)
        $room = gdrcd_query("SELECT id FROM mappa WHERE id = $id AND privata = 1 AND scadenza < NOW() LIMIT 1");
        if (!$room) {
            echo json_encode(['success' => false, 'message' => 'Stanza non disponibile']); exit;
        }

        gdrcd_query("UPDATE mappa SET proprietario = '$login', invitati='', ora_prenotazione=NOW(), scadenza=DATE_ADD(NOW(), INTERVAL 4 HOUR) WHERE id = $id AND scadenza < NOW() LIMIT 1");

        echo json_encode(['success' => true, 'message' => 'Stanza prenotata correttamente']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
