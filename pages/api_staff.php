<?php
/**
 * api_staff.php — Elenco staff per ruolo
 *
 * Endpoint:
 *   ?op=getStaff — Restituisce i personaggi dello staff divisi per ruolo
 *                  (coordinatori, master, moderatori, guide).
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
    // getStaff — lista staff divisa per ruolo
    // -------------------------------------------------------------------------
    case 'getStaff':
        $groups = [
            'coordinatori' => 'admin = 1',
            'master'       => 'master = 1',
            'moderatori'   => 'moderatore = 1',
            'guide'        => 'guida = 1',
        ];

        $data = [];
        foreach ($groups as $key => $where) {
            $res = gdrcd_query(
                "SELECT personaggio.nome, personaggio.cognome, personaggio.url_img_chat FROM personaggio
                 LEFT JOIN privilegi ON personaggio.nome = privilegi.nome
                 WHERE $where
                 ORDER BY personaggio.nome",
                'result'
            );
            $list = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $list[] = [
                    'nome'         => $row['nome'],
                    'cognome'      => $row['cognome'],
                    'url_img_chat' => $row['url_img_chat'] ?? '',
                ];
            }
            gdrcd_query($res, 'free');
            $data[$key] = $list;
        }

        echo json_encode(['success' => true, 'staff' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
