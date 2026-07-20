<?php
/**
 * api_chatoff.php — API per la chattina off
 *
 * Operazioni:
 *   GET  ?op=messages → restituisce il log HTML + cancella chat_letta per il pg corrente
 *   POST ?op=send     → aggiunge un messaggio al log e notifica tutti via socket
 *   POST ?op=clear    → svuota il log (solo staff)
 *
 * Il log è persistito nel file log.html nella root del progetto,
 * compatibile con il vecchio chattina_off.php e post.php.
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
session_write_close();

$op  = $_GET['op'] ?? '';
$LOG = __DIR__ . '/../log.html';

switch ($op) {

    // -------------------------------------------------------------------------
    // MESSAGES — restituisce il contenuto corrente del log e marca come letto
    // -------------------------------------------------------------------------
    case 'messages':
        $html = (file_exists($LOG) && filesize($LOG) > 0) ? file_get_contents($LOG) : '';
        $login_f = gdrcd_filter('in', $_SESSION['login']);
        gdrcd_query("DELETE FROM chat_letta WHERE nome = '$login_f'");
        echo json_encode(['success' => true, 'html' => $html]);
        break;

    // -------------------------------------------------------------------------
    // SEND — aggiunge un messaggio al log e notifica gli altri pg online
    // -------------------------------------------------------------------------
    case 'send':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $text = trim($body['text'] ?? '');

        if ($text === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Testo vuoto']);
            exit;
        }

        $login    = $_SESSION['login'];
        $login_f  = gdrcd_filter('in', $login);
        $escaped  = htmlspecialchars(stripslashes($text));
        $line     = "<div class='msgln'>(" . date('d.m.y, H:i') . ") <b>" . $login . "</b>: " . $escaped . "<br></div>\n";

        $existing = (file_exists($LOG) && filesize($LOG) > 0) ? file_get_contents($LOG) : '';
        file_put_contents($LOG, $line . $existing);

        // Notifica tutti i pg online (escluso il mittente)
        $q = gdrcd_query(
            "SELECT nome FROM personaggio
             WHERE nome != '$login_f'
               AND DATE_ADD(ora_entrata, INTERVAL 14 DAY) >= NOW()",
            'result'
        );
        while ($row = gdrcd_query($q, 'fetch')) {
            $nome_f = gdrcd_filter('in', $row['nome']);

            // Notifica (DM/email) solo alla transizione letto -> non letto:
            // se il pg ha gia' una riga pendente in chat_letta, ha gia'
            // ricevuto la sua notifica per questo batch di non letti — niente
            // insert duplicato, niente nuova notifica per ogni riga scritta
            // da altri nel frattempo (altrimenti sarebbe una notifica per
            // ogni singolo messaggio, troppo invasivo).
            $gia_non_letto = gdrcd_query("SELECT COUNT(*) AS c FROM chat_letta WHERE nome = '$nome_f'")['c'] > 0;
            if (!$gia_non_letto) {
                gdrcd_query("INSERT INTO chat_letta (nome) VALUES ('$nome_f')");
                queueChatOffUnreadNotification($nome_f);
            }

            notifySocketServer('chatoff:update', 'chatoff:' . $row['nome']);
        }
        gdrcd_query($q, 'free');

        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // CLEAR — svuota il log (solo staff)
    // -------------------------------------------------------------------------
    case 'clear':
        $is_staff = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['master'] == 1);
        if (!$is_staff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
            exit;
        }
        file_put_contents($LOG, '');
        gdrcd_query('DELETE FROM chat_letta');
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
