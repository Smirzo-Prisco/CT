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

        $conferma    = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'");
        $hasConferma = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
        $step        = $hasConferma ? 3 : 2;
        $mestieri    = [];

        if ($step === 2) {
            $mestieri[] = ['note' => 'step2 - non eseguito else'];
        } else {
            $res = gdrcd_query(
                "SELECT rm.id_ruolo, rm.nome_ruolo, rm.livello_mestiere
                 FROM personaggio p
                 LEFT JOIN ruolo_mestiere rm ON p.id_mestiere = rm.mestiere
                 WHERE p.nome = '$login'
                   AND rm.livello_mestiere > 0
                   AND rm.livello_mestiere < (
                       SELECT livello_mestiere FROM ruolo_mestiere
                       JOIN clgpersonaggiomestiere ON ruolo_mestiere.id_ruolo = clgpersonaggiomestiere.id_ruolo
                       WHERE clgpersonaggiomestiere.personaggio = '$login'
                       LIMIT 1
                   )
                 ORDER BY rm.livello_mestiere",
                'result'
            );
            while ($row = gdrcd_query($res, 'fetch')) {
                $mestieri[] = ['id' => (int)$row['id_ruolo'], 'nome' => $row['nome_ruolo']];
            }
            gdrcd_query($res, 'free');
        }

        echo json_encode(['step' => $step, 'mestieri' => $mestieri]);
        break;

    default:
        echo json_encode(['op' => $op]);
}
