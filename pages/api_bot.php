<?php
/**
 * api_bot.php — API gestione personaggi bot (sesso = 'b')
 *
 * Accesso riservato esclusivamente agli admin (SESSION admin = 1).
 *
 * Operazioni:
 *   list            GET  — lista bot con status e conteggio DM non letti
 *   set_password    POST — aggiorna la password comune a tutti i bot
 *   set_schedule    POST — imposta gli orari di presenza per i bot selezionati
 *   toggle_presence POST — porta online / offline i bot selezionati
 *   get_dms         GET  — conversazioni DM del bot (?bot=Nome)
 *                          oppure messaggi (?bot=Nome&id_conv=X)
 *   reply_dm        POST — invia un DM come il bot
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
$handleDBConnection = gdrcd_connect();

// Guard: solo admin
if (($_SESSION['admin'] ?? 0) != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non consentito']);
    exit;
}

$op   = $_GET['op'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($op) {

    // ── LIST ──────────────────────────────────────────────────────────────────
    case 'list':
        $result = gdrcd_query(
            "SELECT
                p.nome, p.cognome, p.ultimo_refresh,
                r.nome_razza,
                m.nome AS nome_mestiere,
                COALESCE(bs.paused, 0) AS paused,
                (SELECT COUNT(*)
                 FROM conversazioni_individuali
                 WHERE utente_nome = p.nome AND lettura = 0) AS unread_conv
             FROM personaggio p
             LEFT JOIN razza      r  ON p.id_razza    = r.id_razza
             LEFT JOIN mestiere   m  ON p.id_mestiere = m.id_mestiere
             LEFT JOIN bot_status bs ON bs.bot_nome   = p.nome
             WHERE p.sesso = 'b'
             ORDER BY p.nome ASC",
            'result'
        );
        $bots = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $bots[] = [
                'nome'          => $row['nome'],
                'cognome'       => $row['cognome'],
                'nome_razza'    => $row['nome_razza']    ?? '—',
                'nome_mestiere' => $row['nome_mestiere'] ?? '—',
                'online'        => !empty($row['ultimo_refresh']) && strtotime($row['ultimo_refresh']) + 240 > time(),
                'paused'        => (int)$row['paused'],
                'unread_conv'   => (int)$row['unread_conv'],
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'bots' => $bots]);
        break;

    // ── SET PASSWORD ──────────────────────────────────────────────────────────
    case 'set_password':
        $pass = trim($data['password'] ?? '');
        if (strlen($pass) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password troppo corta (minimo 6 caratteri)']);
            exit;
        }
        $hashed = gdrcd_encript($pass);
        gdrcd_query("UPDATE personaggio SET pass = '" . gdrcd_filter('in', $hashed) . "' WHERE sesso = 'b'");
        echo json_encode(['success' => true]);
        break;

    // ── SET SCHEDULE ──────────────────────────────────────────────────────────
    case 'set_schedule':
        $bots     = array_values(array_filter(
            array_map('trim', $data['bots'] ?? []),
            fn($n) => $n !== ''
        ));
        $schedule = $data['schedule'] ?? [];

        if (empty($bots)) {
            echo json_encode(['success' => false, 'message' => 'Nessun bot selezionato']);
            exit;
        }

        $bots_in = implode(',', array_map(fn($n) => "'" . gdrcd_filter('in', $n) . "'", $bots));
        gdrcd_query("DELETE FROM bot_schedule WHERE bot_nome IN ($bots_in)");

        foreach ($bots as $nome) {
            $nome_safe = gdrcd_filter('in', $nome);
            foreach ($schedule as $slot) {
                $giorno     = (int)($slot['giorno']    ?? -1);
                $ora_inizio = trim($slot['ora_inizio'] ?? '');
                $ora_fine   = trim($slot['ora_fine']   ?? '');
                if ($giorno < 0 || $giorno > 6) continue;
                if (!preg_match('/^\d{2}:\d{2}$/', $ora_inizio)) continue;
                if (!preg_match('/^\d{2}:\d{2}$/', $ora_fine))   continue;
                gdrcd_query("INSERT INTO bot_schedule (bot_nome, giorno, ora_inizio, ora_fine)
                             VALUES ('$nome_safe', $giorno, '$ora_inizio:00', '$ora_fine:00')");
            }
        }
        echo json_encode(['success' => true]);
        break;

    // ── TOGGLE PRESENCE ───────────────────────────────────────────────────────
    case 'toggle_presence':
        $bots   = array_values(array_filter(
            array_map('trim', $data['bots'] ?? []),
            fn($n) => $n !== ''
        ));
        $online = !empty($data['online']);

        if (empty($bots)) {
            echo json_encode(['success' => false, 'message' => 'Nessun bot selezionato']);
            exit;
        }

        $bots_in = implode(',', array_map(fn($n) => "'" . gdrcd_filter('in', $n) . "'", $bots));

        if ($online) {
            gdrcd_query("UPDATE personaggio SET ultimo_refresh = NOW(), ora_entrata = NOW() WHERE nome IN ($bots_in) AND sesso = 'b'");
            gdrcd_query("INSERT INTO bot_status (bot_nome, paused)
                         SELECT nome, 0 FROM personaggio WHERE nome IN ($bots_in) AND sesso = 'b'
                         ON DUPLICATE KEY UPDATE paused = 0");
        } else {
            gdrcd_query("UPDATE personaggio SET ultimo_refresh = '2000-01-01 00:00:00' WHERE nome IN ($bots_in) AND sesso = 'b'");
            gdrcd_query("INSERT INTO bot_status (bot_nome, paused)
                         SELECT nome, 1 FROM personaggio WHERE nome IN ($bots_in) AND sesso = 'b'
                         ON DUPLICATE KEY UPDATE paused = 1");
        }
        notifySocketServer('presenti:update', 'global');
        echo json_encode(['success' => true]);
        break;

    // ── GET DMs ───────────────────────────────────────────────────────────────
    case 'get_dms':
        $bot_raw = trim($_GET['bot'] ?? '');
        $bot     = gdrcd_filter('in', $bot_raw);
        $id_conv = isset($_GET['id_conv']) ? (int)$_GET['id_conv'] : null;

        if (empty($bot)) {
            echo json_encode(['success' => false, 'message' => 'Bot non specificato']);
            exit;
        }
        $check = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '$bot' AND sesso = 'b' LIMIT 1");
        if (empty($check)) {
            echo json_encode(['success' => false, 'message' => 'Bot non trovato']);
            exit;
        }

        if ($id_conv !== null) {
            // Messaggi di una conversazione specifica
            $msgs = gdrcd_query(
                "SELECT mittente_nome, destinatario_nome, testo, ora_spedizione
                 FROM sms
                 WHERE id_conversazione = $id_conv
                 ORDER BY ora_spedizione ASC",
                'result'
            );
            $messages = [];
            while ($row = gdrcd_query($msgs, 'fetch')) {
                $messages[] = [
                    'mittente'       => $row['mittente_nome'],
                    'destinatario'   => $row['destinatario_nome'],
                    'testo'          => $row['testo'],
                    'ora_spedizione' => $row['ora_spedizione'],
                ];
            }
            gdrcd_query($msgs, 'free');
            // Marca la conversazione come letta per il bot
            gdrcd_query("UPDATE conversazioni_individuali SET lettura = 1 WHERE utente_nome = '$bot' AND id_conversazione = $id_conv");
            echo json_encode(['success' => true, 'messages' => $messages]);
        } else {
            // Lista conversazioni del bot
            $convs = gdrcd_query(
                "SELECT
                    ci.id_conversazione,
                    ci.lettura,
                    (SELECT CASE WHEN s.mittente_nome = '$bot' THEN s.destinatario_nome ELSE s.mittente_nome END
                     FROM sms s WHERE s.id_conversazione = ci.id_conversazione LIMIT 1) AS altro_utente,
                    (SELECT s2.testo
                     FROM sms s2 WHERE s2.id_conversazione = ci.id_conversazione
                     ORDER BY s2.ora_spedizione DESC LIMIT 1) AS ultimo_testo,
                    (SELECT s3.ora_spedizione
                     FROM sms s3 WHERE s3.id_conversazione = ci.id_conversazione
                     ORDER BY s3.ora_spedizione DESC LIMIT 1) AS ultima_ora
                 FROM conversazioni_individuali ci
                 WHERE ci.utente_nome = '$bot'
                 ORDER BY ultima_ora DESC",
                'result'
            );
            $conversations = [];
            while ($row = gdrcd_query($convs, 'fetch')) {
                $conversations[] = [
                    'id_conversazione' => (int)$row['id_conversazione'],
                    'letto'            => (int)$row['lettura'],
                    'altro_utente'     => $row['altro_utente'],
                    'ultimo_testo'     => $row['ultimo_testo'],
                    'ultima_ora'       => $row['ultima_ora'],
                ];
            }
            gdrcd_query($convs, 'free');
            echo json_encode(['success' => true, 'conversations' => $conversations]);
        }
        break;

    // ── REPLY DM ─────────────────────────────────────────────────────────────
    case 'reply_dm':
        $bot          = trim($data['bot']          ?? '');
        $destinatario = trim($data['destinatario'] ?? '');
        $testo        = trim($data['testo']        ?? '');

        if (empty($bot) || empty($destinatario) || empty($testo)) {
            echo json_encode(['success' => false, 'message' => 'Dati incompleti']);
            exit;
        }
        $bot_safe = gdrcd_filter('in', $bot);
        $check = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '$bot_safe' AND sesso = 'b' LIMIT 1");
        if (empty($check)) {
            echo json_encode(['success' => false, 'message' => 'Bot non valido']);
            exit;
        }
        // send_sms usa $text direttamente nella query, quindi va passato già escaped
        send_sms($bot, $destinatario, '', gdrcd_filter('in', $testo), 0);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
