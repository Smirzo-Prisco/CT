<?php
/**
 * api_moderazione.php — API per la sezione Contatta Moderazione
 *
 * Guard: sessione loggata (401 altrimenti).
 * $is_staff = admin=1 oppure moderatore=1.
 *
 * Operazioni:
 *   list           GET  — richieste proprie con stato e risposta
 *   submit         POST — nuova richiesta (messaggio_moderazione + messaggioaraldo)
 *   lista_staff    GET  — [staff] elenco completo richieste, ordine stato ASC, id DESC
 *   get_request    GET  — [staff] dettaglio singola richiesta (?id=X)
 *   rispondi       POST — [staff] risponde alla richiesta e invia DM all'autore
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
$handleDBConnection = gdrcd_connect();

// ── Guard: sessione attiva ────────────────────────────────────────────────────
if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$me       = $_SESSION['login'];
$is_staff = (($_SESSION['admin'] ?? 0) == 1) || (($_SESSION['moderatore'] ?? 0) == 1);
$op       = $_GET['op'] ?? '';
$data     = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($op) {

    // ── LIST — richieste proprie ──────────────────────────────────────────────
    case 'list':
        $result = gdrcd_query(
            "SELECT id, titolo, data_messaggio, stato, risposta
             FROM messaggio_moderazione
             WHERE autore = '" . gdrcd_filter('in', $me) . "'
             ORDER BY id ASC",
            'result'
        );
        $rows = [];
        while ($fetch = gdrcd_query($result, 'fetch')) {
            $rows[] = [
                'id'             => (int)$fetch['id'],
                'titolo'         => gdrcd_filter('out', $fetch['titolo']),
                'data_messaggio' => $fetch['data_messaggio'],
                'stato'          => (int)$fetch['stato'],
                'risposta'       => $fetch['risposta'] !== null
                                        ? gdrcd_filter('out', $fetch['risposta'])
                                        : null,
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'requests' => $rows, 'is_staff' => $is_staff]);
        break;

    // ── SUBMIT — nuova richiesta ──────────────────────────────────────────────
    case 'submit':
        $titolo  = trim($data['titolo'] ?? '');
        $testo   = trim($data['testo']  ?? '');
        $anonimo = !empty($data['anonimo']) ? 'si' : 'no';

        if ($titolo === '' || $testo === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Titolo e testo sono obbligatori']);
            exit;
        }

        $titolo_db  = gdrcd_filter('in', $titolo);
        $testo_db   = gdrcd_filter('in', $testo);
        $autore_db  = gdrcd_filter('in', $me);
        $anonimo_db = gdrcd_filter('in', $anonimo);

        gdrcd_query(
            "INSERT INTO messaggio_moderazione
             (titolo, messaggio, autore, data_messaggio, anonimo)
             VALUES
             ('$titolo_db', '$testo_db', '$autore_db', NOW(), '$anonimo_db')"
        );

        gdrcd_query(
            "INSERT INTO messaggioaraldo
             (id_messaggio_padre, id_araldo, titolo, messaggio, autore,
              data_messaggio, data_ultimo_messaggio, giornalista, anonimo)
             VALUES
             ('-1', '86', '$titolo_db', '$testo_db', '$autore_db',
              NOW(), NOW(), 'no', '$anonimo_db')"
        );

        echo json_encode(['success' => true]);
        break;

    // ── LISTA_STAFF — [staff] elenco completo ────────────────────────────────
    case 'lista_staff':
        if (!$is_staff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso non consentito']);
            exit;
        }
        $result = gdrcd_query(
            "SELECT id, titolo, data_messaggio, stato, anonimo
             FROM messaggio_moderazione
             ORDER BY stato ASC, id DESC",
            'result'
        );
        $rows = [];
        while ($fetch = gdrcd_query($result, 'fetch')) {
            $rows[] = [
                'id'             => (int)$fetch['id'],
                'titolo'         => gdrcd_filter('out', $fetch['titolo']),
                'data_messaggio' => $fetch['data_messaggio'],
                'stato'          => (int)$fetch['stato'],
                'anonimo'        => $fetch['anonimo'],
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'requests' => $rows]);
        break;

    // ── GET_REQUEST — [staff] dettaglio singola richiesta ────────────────────
    case 'get_request':
        if (!$is_staff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso non consentito']);
            exit;
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID non valido']);
            exit;
        }
        $fetch = gdrcd_query(
            "SELECT id, titolo, messaggio, data_messaggio, stato, anonimo
             FROM messaggio_moderazione
             WHERE id = $id"
        );
        if (!$fetch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Richiesta non trovata']);
            exit;
        }
        echo json_encode([
            'success'        => true,
            'id'             => (int)$fetch['id'],
            'titolo'         => gdrcd_filter('out', $fetch['titolo']),
            'messaggio'      => gdrcd_filter('out', $fetch['messaggio']),
            'data_messaggio' => $fetch['data_messaggio'],
            'stato'          => (int)$fetch['stato'],
            'anonimo'        => $fetch['anonimo'],
        ]);
        break;

    // ── RISPONDI — [staff] risponde e invia DM ───────────────────────────────
    case 'rispondi':
        if (!$is_staff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso non consentito']);
            exit;
        }
        $id       = (int)($data['id']       ?? 0);
        $risposta = trim($data['risposta']  ?? '');

        if ($id <= 0 || $risposta === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID e risposta sono obbligatori']);
            exit;
        }

        // Recupera autore server-side (non ci fidiamo del client)
        $row = gdrcd_query(
            "SELECT autore FROM messaggio_moderazione WHERE id = $id"
        );
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Richiesta non trovata']);
            exit;
        }
        $destinatario = $row['autore'];

        // Aggiorna la richiesta
        gdrcd_query(
            "UPDATE messaggio_moderazione
             SET risposta = '" . gdrcd_filter('in', $risposta) . "', stato = 1
             WHERE id = $id"
        );

        // Invia DM all'autore
        $msg_testo = 'La tua richiesta di moderazione è stata gestita. Trovi il responso nella sezione Gestione → Contatta Moderazione.';
        send_sms('Segnalazione', $destinatario, '', gdrcd_filter('in', $msg_testo), 0);

        echo json_encode(['success' => true]);
        break;

    // ── Default ───────────────────────────────────────────────────────────────
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
        break;
}
