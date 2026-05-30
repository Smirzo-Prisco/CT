<?php
/**
 * api_anagrafe.php — Lista personaggi per lettera iniziale
 *
 * Endpoint:
 *   GET ?op=getByLetter&letter=A — Restituisce i personaggi il cui nome
 *       inizia con la lettera indicata, con famiglia, mestiere, razza e
 *       ultimo accesso. La lettera deve essere A–Z.
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
    // getByLetter — personaggi filtrati per lettera iniziale
    // -------------------------------------------------------------------------
    case 'getByLetter':
        $letter = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $_GET['letter'] ?? ''), 0, 1));

        if (empty($letter)) {
            echo json_encode(['success' => true, 'personaggi' => []]);
            exit;
        }

        $res = gdrcd_query(
            "SELECT personaggio.nome, personaggio.cognome, personaggio.ultimo_refresh,
                    ruolo.nome AS nome_gilda, ruolo.immagine AS img_gilda,
                    ruolo_mestiere.nome_ruolo AS nome_mestiere, ruolo_mestiere.immagine AS img_mestiere,
                    razza.sing_m, razza.sing_f, razza.immagine AS img_razza
             FROM personaggio
             LEFT JOIN ruolo         ON personaggio.id_ruolo_gilda    = ruolo.id_ruolo
             LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo
             LEFT JOIN razza          ON personaggio.id_razza          = razza.id_razza
             WHERE personaggio.nome LIKE '" . gdrcd_filter('in', $letter) . "%'
               AND (personaggio.esilio < NOW() OR personaggio.esilio IS NULL)
             GROUP BY personaggio.nome
             ORDER BY personaggio.nome ASC",
            'result'
        );

        $list = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $list[] = [
                'nome'          => gdrcd_filter('out', $row['nome']),
                'cognome'       => gdrcd_filter('out', $row['cognome']),
                'ultimoRefresh' => $row['ultimo_refresh'],
                'gilda'         => ['nome' => gdrcd_filter('out', $row['nome_gilda'] ?? ''), 'img' => $row['img_gilda'] ?? ''],
                'mestiere'      => ['nome' => gdrcd_filter('out', $row['nome_mestiere'] ?? ''), 'img' => $row['img_mestiere'] ?? ''],
                'razza'         => ['nome' => gdrcd_filter('out', $row['sing_m'] ?? ''), 'img' => $row['img_razza'] ?? ''],
            ];
        }
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'personaggi' => $list]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
