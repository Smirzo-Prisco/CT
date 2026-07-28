<?php
/**
 * api_manutenzione.php — Endpoint JSON per il pannello React "Gestione manutenzione"
 *
 * op = old_log | old_chat | old_messages | missing | missing_soft | deleted | blacklisted
 *   GET  → preview (sola lettura): conteggi/campione di quello che verrebbe toccato
 *   POST → execute: esegue davvero le query distruttive
 *
 * Accesso riservato agli admin ($_SESSION['admin'] == 1).
 */
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if (($_SESSION['admin'] ?? 0) != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$op       = $_GET['op'] ?? '';
$isPreview = ($_SERVER['REQUEST_METHOD'] === 'GET');
$data     = $isPreview ? [] : (json_decode(file_get_contents('php://input'), true) ?? []);

/** Legge e valida il parametro mesi da GET (preview) o dal body JSON (execute) */
function leggi_mesi($isPreview, $data, $min, $max) {
    $raw = $isPreview ? ($_GET['mesi'] ?? null) : ($data['mesi'] ?? null);
    if ($raw === null || is_numeric($raw) === false) return null;
    $mesi = (int)$raw;
    if ($mesi < $min || $mesi > $max) return null;
    return $mesi;
}

/** Campiona fino a 20 nomi personaggio che soddisfano una WHERE, più il conteggio totale */
function campiona_personaggi($where) {
    $totale = (int)gdrcd_query("SELECT COUNT(*) AS n FROM personaggio $where")['n'];
    $sample = [];
    $result = gdrcd_query("SELECT nome FROM personaggio $where ORDER BY nome LIMIT 20", 'result');
    while ($row = gdrcd_query($result, 'fetch')) {
        $sample[] = $row['nome'];
    }
    gdrcd_query($result, 'free');
    return ['totale' => $totale, 'sample' => $sample];
}

switch ($op) {

    // -------------------------------------------------------------------------
    // OLD_LOG — log eventi amministrativi più vecchi di N mesi
    // -------------------------------------------------------------------------
    case 'old_log':
        $mesi = leggi_mesi($isPreview, $data, 0, 12);
        if ($mesi === null) {
            echo json_encode(['success' => false, 'message' => 'Numero di mesi non valido (0-12)']);
            exit;
        }
        $where = "WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > data_evento";

        if ($isPreview) {
            $count = (int)gdrcd_query("SELECT COUNT(*) AS n FROM log $where")['n'];
            echo json_encode(['success' => true,
                'title' => "Elimina log più vecchi di $mesi mesi",
                'warning' => 'Operazione irreversibile.',
                'items' => [
                    ['label' => 'Log eventi amministrativi (login, cambio permessi, bonifici, ecc.)', 'count' => $count],
                ],
            ]);
        } else {
            gdrcd_query("DELETE FROM log $where");
            gdrcd_query("OPTIMIZE TABLE log");
            echo json_encode(['success' => true, 'message' => 'Log eventi eliminati.']);
        }
        break;

    // -------------------------------------------------------------------------
    // OLD_CHAT — log di chat di ruolo più vecchi di N mesi
    // -------------------------------------------------------------------------
    case 'old_chat':
        $mesi = leggi_mesi($isPreview, $data, 0, 12);
        if ($mesi === null) {
            echo json_encode(['success' => false, 'message' => 'Numero di mesi non valido (0-12)']);
            exit;
        }
        $where = "WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > ora";

        if ($isPreview) {
            $count = (int)gdrcd_query("SELECT COUNT(*) AS n FROM chat $where")['n'];
            echo json_encode(['success' => true,
                'title' => 'Elimina le azioni della chat di gioco',
                'warning' => 'Operazione irreversibile.',
                'items' => [
                    ['label' => 'Azioni della chat recuperate', 'count' => $count],
                ],
            ]);
        } else {
            gdrcd_query("DELETE FROM chat $where");
            gdrcd_query("OPTIMIZE TABLE chat");
            echo json_encode(['success' => true, 'message' => 'Log di chat eliminati.']);
        }
        break;

    // -------------------------------------------------------------------------
    // OLD_MESSAGES — messaggi privati (e relativo backup) più vecchi di N mesi
    // -------------------------------------------------------------------------
    case 'old_messages':
        $mesi = leggi_mesi($isPreview, $data, 0, 12);
        if ($mesi === null) {
            echo json_encode(['success' => false, 'message' => 'Numero di mesi non valido (0-12)']);
            exit;
        }
        $where = "WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > spedito";

        if ($isPreview) {
            $count_messaggi     = (int)gdrcd_query("SELECT COUNT(*) AS n FROM messaggi $where")['n'];
            $count_backmessaggi = (int)gdrcd_query("SELECT COUNT(*) AS n FROM backmessaggi $where")['n'];
            echo json_encode(['success' => true,
                'title' => "Elimina messaggi privati più vecchi di $mesi mesi",
                'warning' => 'Operazione irreversibile.',
                'items' => [
                    ['label' => 'Messaggi privati (casella attiva)', 'count' => $count_messaggi],
                    ['label' => 'Backup permanente dei messaggi (usato dagli admin per lo storico)', 'count' => $count_backmessaggi],
                ],
                'notes' => ['Non vengono toccate le conversazioni di chat di ruolo: usare "Elimina log di chat" per quelle.'],
            ]);
        } else {
            gdrcd_query("DELETE FROM messaggi $where");
            gdrcd_query("OPTIMIZE TABLE messaggi");
            gdrcd_query("DELETE FROM backmessaggi $where");
            gdrcd_query("OPTIMIZE TABLE backmessaggi");
            echo json_encode(['success' => true, 'message' => 'Messaggi privati eliminati.']);
        }
        break;

    // -------------------------------------------------------------------------
    // MISSING — cancellazione DEFINITIVA dei personaggi inattivi da N mesi
    // -------------------------------------------------------------------------
    case 'missing':
        $mesi = leggi_mesi($isPreview, $data, 1, 12);
        if ($mesi === null) {
            echo json_encode(['success' => false, 'message' => 'Numero di mesi non valido (1-12)']);
            exit;
        }
        $where = "WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > ora_entrata";

        if ($isPreview) {
            $pg = campiona_personaggi($where);
            echo json_encode(['success' => true,
                'title' => "Elimina definitivamente i personaggi inattivi da più di $mesi mesi",
                'warning' => 'Operazione irreversibile: non sarà possibile ripristinare questi personaggi.',
                'items' => [
                    ['label' => 'Personaggi coinvolti', 'count' => $pg['totale'], 'sample' => $pg['sample']],
                ],
                'notes' => [
                    'Vengono rimossi oggetti, abilità, mostrine e ruoli/gilda del personaggio.',
                    'I messaggi privati e i riferimenti in chat/log NON vengono ripuliti da questa operazione (restano intestati al personaggio cancellato).',
                ],
            ]);
        } else {
            gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE nome IN (SELECT nome FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggiooggetto");

            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome IN (SELECT nome FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggioabilita");

            gdrcd_query("DELETE FROM clgpersonaggiomostrine WHERE nome IN (SELECT nome FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggiomostrine");

            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggioruolo");

            gdrcd_query("DELETE FROM personaggio $where");
            gdrcd_query("OPTIMIZE TABLE personaggio");
            echo json_encode(['success' => true, 'message' => 'Personaggi inattivi eliminati definitivamente.']);
        }
        break;

    // -------------------------------------------------------------------------
    // MISSING_SOFT — marca come cancellati (permessi=-1) i personaggi inattivi
    // da N mesi, escludendo lo staff (permessi != 0)
    // -------------------------------------------------------------------------
    case 'missing_soft':
        $mesi = leggi_mesi($isPreview, $data, 1, 12);
        if ($mesi === null) {
            echo json_encode(['success' => false, 'message' => 'Numero di mesi non valido (1-12)']);
            exit;
        }
        $where = "WHERE DATE_SUB(NOW(), INTERVAL $mesi MONTH) > ora_entrata AND permessi = 0";

        if ($isPreview) {
            $pg = campiona_personaggi($where);
            echo json_encode(['success' => true,
                'title' => "Marca come cancellati i personaggi inattivi da più di $mesi mesi",
                'warning' => 'Operazione reversibile solo manualmente (richiede di reimpostare i permessi sul personaggio).',
                'items' => [
                    ['label' => 'Personaggi coinvolti (staff escluso)', 'count' => $pg['totale'], 'sample' => $pg['sample']],
                ],
                'notes' => [
                    'Nessuna riga viene cancellata fisicamente: il personaggio viene solo marcato (permessi = -1).',
                    'Potrà essere ripulito definitivamente in seguito con "Elimina i personaggi provvisoriamente cancellati".',
                ],
            ]);
        } else {
            gdrcd_query("UPDATE personaggio SET permessi = -1 $where");
            echo json_encode(['success' => true, 'message' => 'Personaggi inattivi marcati come cancellati.']);
        }
        break;

    // -------------------------------------------------------------------------
    // DELETED — cancellazione DEFINITIVA dei personaggi già marcati (permessi=-1)
    // -------------------------------------------------------------------------
    case 'deleted':
        $where = "WHERE permessi = -1";

        if ($isPreview) {
            $pg = campiona_personaggi($where);
            echo json_encode(['success' => true,
                'title' => 'Elimina definitivamente i personaggi provvisoriamente cancellati',
                'warning' => 'Operazione irreversibile: non sarà possibile ripristinare questi personaggi.',
                'items' => [
                    ['label' => 'Personaggi coinvolti', 'count' => $pg['totale'], 'sample' => $pg['sample']],
                ],
                'notes' => [
                    'Vengono rimossi oggetti, abilità, mostrine, ruolo/gilda, messaggi privati (e relativo backup) e letture araldo.',
                    'I riferimenti in chat, log eventi e messaggi araldo vengono anonimizzati (sostituiti con "Cancellato") invece di essere eliminati.',
                ],
            ]);
        } else {
            gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE nome IN (SELECT nome FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggiooggetto");

            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome IN (SELECT nome FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggioabilita");

            gdrcd_query("DELETE FROM clgpersonaggiomostrine WHERE nome IN (SELECT nome FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggiomostrine");

            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE clgpersonaggioruolo");

            gdrcd_query("DELETE FROM messaggi WHERE mittente IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("DELETE FROM messaggi WHERE destinatario IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("DELETE FROM backmessaggi WHERE mittente IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("DELETE FROM backmessaggi WHERE destinatario IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE messaggi");

            gdrcd_query("DELETE FROM araldo_letto WHERE nome IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE araldo_letto");

            gdrcd_query("UPDATE chat SET mittente = 'Cancellato' WHERE nome IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("UPDATE chat SET destinatario = 'Cancellato' WHERE nome IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE chat");

            gdrcd_query("UPDATE log SET nome_interessato = 'Cancellato' WHERE nome IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE log");

            gdrcd_query("UPDATE messaggiaraldo SET autore = 'Cancellato' WHERE nome IN (SELECT nome AS personaggio FROM personaggio $where)");
            gdrcd_query("OPTIMIZE TABLE messaggiaraldo");

            gdrcd_query("DELETE FROM personaggio $where");
            gdrcd_query("OPTIMIZE TABLE personaggio");
            echo json_encode(['success' => true, 'message' => 'Personaggi cancellati eliminati definitivamente.']);
        }
        break;

    // -------------------------------------------------------------------------
    // BLACKLISTED — svuota la blacklist
    // -------------------------------------------------------------------------
    case 'blacklisted':
        if ($isPreview) {
            $count = (int)gdrcd_query("SELECT COUNT(*) AS n FROM blacklist")['n'];
            echo json_encode(['success' => true,
                'title' => 'Svuota la blacklist',
                'warning' => 'Operazione irreversibile.',
                'items' => [
                    ['label' => 'Voci in blacklist', 'count' => $count],
                ],
            ]);
        } else {
            gdrcd_query("DELETE FROM blacklist WHERE 1");
            gdrcd_query("OPTIMIZE TABLE blacklist");
            echo json_encode(['success' => true, 'message' => 'Blacklist svuotata.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
