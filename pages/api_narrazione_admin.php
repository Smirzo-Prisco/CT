<?php
/**
 * api_narrazione_admin.php — API JSON per il pannello admin "Narrazioni IA"
 *
 * op = list    (GET, admin)  — elenco richieste di rigenerazione completa
 * op = approva (POST, admin) — approva una richiesta (verrà elaborata dal
 *                               worker cron/narrazione_worker.php)
 * op = rifiuta (POST, admin) — rifiuta una richiesta
 *
 * Non gestisce narrazione_queue (riassunti automatici silenziosi): quella
 * coda non richiede approvazione, vedi includes/narrazione_functions.inc.php.
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

$is_admin = (($_SESSION['admin'] ?? 0) == 1);
if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$op = $_GET['op'] ?? '';

/**
 * Stima (grezza) del tempo di elaborazione per la rigenerazione completa di
 * un personaggio: una chiamata LLM per ogni giocata conclusa, con la
 * trascrizione troncata a 6000 caratteri (stesso limite del worker, vedi
 * narrazione_trascrizione_giocata()). Velocità basate sui test effettuati
 * sull'LLM locale (llama.cpp, CPUQuota 50%): ~3 token/sec in lettura del
 * prompt, ~1 token/sec in generazione (max_tokens=300 per riassunto).
 */
function narrazione_stima_durata(string $pg_name): array {
    // Nota: niente `const` qui dentro — in PHP un const dichiarato dentro una
    // funzione diventa globale al primo run e fa fallire le chiamate successive.
    $prompt_tokens_per_sec    = 3;
    $gen_tokens_per_sec       = 1;
    $gen_tokens_per_riassunto = 300;
    $chars_per_token          = 4;
    $overhead_prompt_chars    = 400; // istruzioni fisse del prompt, vedi narrazione_riassunto_giocata()
    $max_chars_trascrizione   = 6000;

    $pg_esc  = gdrcd_filter('in', $pg_name);
    $giocate = gdrcd_query("SELECT rs.id_role FROM role_sessions rs
        JOIN role_session_players rsp ON rsp.id_role = rs.id_role
        WHERE rsp.pg_name = '$pg_esc' AND rsp.png = 0 AND rs.end IS NOT NULL", 'result');

    $n_giocate      = 0;
    $secondi_totali = 0.0;
    while ($g = gdrcd_query($giocate, 'fetch')) {
        $n_giocate++;
        $len_row = gdrcd_query("SELECT SUM(CHAR_LENGTH(testo)) AS len FROM chat WHERE id_role = " . (int)$g['id_role'] . " AND testo IS NOT NULL");
        $chars         = min((int)($len_row['len'] ?? 0), $max_chars_trascrizione) + $overhead_prompt_chars;
        $prompt_tokens = $chars / $chars_per_token;
        $secondi_totali += ($prompt_tokens / $prompt_tokens_per_sec) + ($gen_tokens_per_riassunto / $gen_tokens_per_sec);
    }
    gdrcd_query($giocate, 'free');

    return ['n_giocate' => $n_giocate, 'secondi_stimati' => (int)round($secondi_totali)];
}

switch ($op) {

    // -------------------------------------------------------------------------
    // LIST — tutte le richieste, più recenti prima, con stima durata elaborazione
    // -------------------------------------------------------------------------
    case 'list':
        $result = gdrcd_query("SELECT id, pg_name, stato, creato_il, approvato_da, approvato_il, completato_il, errore
            FROM narrazione_richieste ORDER BY creato_il DESC", 'result');
        $richieste = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $stima = narrazione_stima_durata($row['pg_name']);
            $row['n_giocate']       = $stima['n_giocate'];
            $row['secondi_stimati'] = $stima['secondi_stimati'];
            $richieste[] = $row;
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'richieste' => $richieste]);
        break;

    // -------------------------------------------------------------------------
    // APPROVA — la richiesta passa a 'approvata', il worker la elaborerà
    // -------------------------------------------------------------------------
    case 'approva':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Richiesta non specificata']); exit; }
        $admin_esc = gdrcd_filter('in', $_SESSION['login']);
        gdrcd_query("UPDATE narrazione_richieste
            SET stato = 'approvata', approvato_da = '$admin_esc', approvato_il = NOW()
            WHERE id = $id AND stato = 'richiesta'");
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // RIFIUTA — la richiesta passa a 'rifiutata', non verrà elaborata
    // -------------------------------------------------------------------------
    case 'rifiuta':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Richiesta non specificata']); exit; }
        $admin_esc = gdrcd_filter('in', $_SESSION['login']);
        gdrcd_query("UPDATE narrazione_richieste
            SET stato = 'rifiutata', approvato_da = '$admin_esc', approvato_il = NOW()
            WHERE id = $id AND stato = 'richiesta'");
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
