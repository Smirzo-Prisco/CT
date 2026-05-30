<?php
/**
 * api_cambio_pass.php — Cambio password utente e admin
 *
 * Endpoint:
 *   GET  ?op=getInfo     — Restituisce email del pg corrente e permessi
 *                          (per mostrare il form admin se >= MODERATOR).
 *   POST ?op=changePass  — Cambia la propria password (verifica email).
 *   POST ?op=forcePass   — Impone la password di un account (solo >= MODERATOR).
 *   GET  ?op=getAccounts — Lista account a cui si può imporre la password.
 *
 * @author Crystal Tokyo Dev
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
require_once(__DIR__ . '/../includes/constant_values.inc.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    // -------------------------------------------------------------------------
    // getInfo — email corrente e livello permessi
    // -------------------------------------------------------------------------
    case 'getInfo':
        $row   = gdrcd_query("SELECT email FROM personaggio WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
        $perms = (int)($_SESSION['permessi'] ?? 0);
        echo json_encode([
            'success'   => true,
            'email'     => gdrcd_filter('out', $row['email']),
            'permessi'  => $perms,
            'isMod'     => $perms >= MODERATOR,
            'isSuperuser' => $perms >= SUPERUSER,
        ]);
        break;

    // -------------------------------------------------------------------------
    // getAccounts — lista account modificabili (admin use)
    // -------------------------------------------------------------------------
    case 'getAccounts':
        $perms = (int)($_SESSION['permessi'] ?? 0);
        if ($perms < MODERATOR) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $query = $perms >= SUPERUSER
            ? "SELECT nome FROM personaggio ORDER BY nome"
            : "SELECT nome FROM personaggio WHERE permessi < " . SUPERUSER . " ORDER BY nome";

        $res  = gdrcd_query($query, 'result');
        $list = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $list[] = gdrcd_filter('out', $row['nome']);
        }
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'accounts' => $list]);
        break;

    // -------------------------------------------------------------------------
    // changePass — cambia la propria password (verifica email)
    // -------------------------------------------------------------------------
    case 'changePass':
        $data     = json_decode(file_get_contents('php://input'), true);
        $emailIn  = gdrcd_filter_email($data['email'] ?? '');
        $newPass  = $data['new_pass'] ?? '';

        $row = gdrcd_query("SELECT email FROM personaggio WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");

        if ($row['email'] !== $emailIn || gdrcd_check_pass($newPass) !== true) {
            echo json_encode(['success' => false, 'message' => 'Email errata o password non valida']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET pass = '" . gdrcd_encript($newPass) . "', ultimo_cambiopass = NOW() WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
        gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento) VALUES ('" . gdrcd_filter('in', $_SESSION['login']) . "','" . gdrcd_filter('in', $_SESSION['login']) . "', NOW(), " . CHANGEDPASS . ", '" . $_SERVER['REMOTE_ADDR'] . "')");

        echo json_encode(['success' => true, 'message' => 'Password aggiornata correttamente']);
        break;

    // -------------------------------------------------------------------------
    // forcePass — impone la password di un account (solo >= MODERATOR)
    // -------------------------------------------------------------------------
    case 'forcePass':
        $perms = (int)($_SESSION['permessi'] ?? 0);
        if ($perms < MODERATOR) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $data    = json_decode(file_get_contents('php://input'), true);
        $account = gdrcd_filter('in', $data['account'] ?? '');
        $newPass = $data['new_pass'] ?? '';

        if (empty($account) || gdrcd_check_pass($newPass) !== true) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti o password non valida']);
            exit;
        }

        if ($perms >= SUPERUSER) {
            $query = "UPDATE personaggio SET pass = '" . gdrcd_encript($newPass) . "', ultimo_cambiopass = NOW() WHERE nome = '$account'";
        } else {
            $query = "UPDATE personaggio SET pass = '" . gdrcd_encript($newPass) . "', ultimo_cambiopass = NOW() WHERE nome = '$account' AND permessi < " . SUPERUSER;
        }
        gdrcd_query($query);
        gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento) VALUES ('$account', '" . gdrcd_filter('in', $_SESSION['login']) . "', NOW(), " . CHANGEDPASS . ", '" . $_SERVER['REMOTE_ADDR'] . "')");

        echo json_encode(['success' => true, 'message' => 'Password aggiornata correttamente']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
