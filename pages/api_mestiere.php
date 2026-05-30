<?php
/**
 * api_mestiere.php — Selezione e avanzamento mestiere del personaggio
 *
 * Endpoint:
 *   GET  ?op=getState   — Stato del pg (esperienza, mestiere attuale, step).
 *                         step=2 → scelta libera; step=3 → avanzamento livello.
 *   POST ?op=change     — Imposta un mestiere di livello 3 (senza conferma).
 *   POST ?op=pick       — Conferma il mestiere corrente e lo blocca.
 *   POST ?op=levelUp    — Avanza a un livello inferiore del mestiere corrente.
 *
 * Tutte le query usano throwOnError=true: in caso di errore SQL viene lanciata
 * una Exception invece di chiamare die(), così il try-catch restituisce JSON
 * invece di HTML corrotto.
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

try {

switch ($op) {

    // -------------------------------------------------------------------------
    // getState — dati pg + mestieri disponibili
    // -------------------------------------------------------------------------
    case 'getState':
        $pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere, id_mestiere, id_ruolo_mestiere FROM personaggio WHERE nome = '$login'", 'query', true);
        $exp         = (int)($pg['esperienza'] ?? 0);
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);

        $conferma       = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'query', true);
        $hasConferma    = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
        $currentIdRuolo = $conferma ? (int)$conferma['id_ruolo'] : 0;

        // step 2 → scelta libera / step 3 → avanzamento
        $step = $hasConferma ? 3 : 2;

        if ($step === 2) {
            // Tutti i mestieri di livello 3 (grado base)
            $res      = gdrcd_query("SELECT id_ruolo, nome_ruolo, immagine, mestiere FROM ruolo_mestiere WHERE livello_mestiere = 3 ORDER BY nome_ruolo", 'result', true);
            $mestieri = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $mestieri[] = [
                    'id'       => (int)$row['id_ruolo'],
                    'nome'     => gdrcd_filter('out', $row['nome_ruolo']),
                    'immagine' => $row['immagine'],
                    'mestiere' => (int)$row['mestiere'],
                    'selected' => ((int)$row['id_ruolo'] === $currentIdRuolo),
                ];
            }
            gdrcd_query($res, 'free');
        } else {
            // step 3 → livelli inferiori del mestiere corrente (sotto il livello attuale)
            $res      = gdrcd_query(
                "SELECT rm.id_ruolo, rm.nome_ruolo, rm.immagine, rm.mestiere, rm.livello_mestiere
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
                'result',
                true
            );
            $mestieri = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                // Controlla se è sbloccabile in base all'esperienza mestiere
                $livello  = (int)$row['livello_mestiere'];
                $unlocked = false;
                if ($expMestiere >= 55 && $livello > 1) $unlocked = true;
                elseif ($expMestiere >= 10 && $expMestiere < 55 && $livello > 2) $unlocked = true;

                $mestieri[] = [
                    'id'       => (int)$row['id_ruolo'],
                    'nome'     => gdrcd_filter('out', $row['nome_ruolo']),
                    'immagine' => $row['immagine'],
                    'mestiere' => (int)$row['mestiere'],
                    'livello'  => $livello,
                    'unlocked' => $unlocked,
                ];
            }
            gdrcd_query($res, 'free');
        }

        echo json_encode([
            'success'        => true,
            'step'           => $step,
            'esperienza'     => $exp,
            'expMestiere'    => $expMestiere,
            'hasConferma'    => $hasConferma,
            'currentIdRuolo' => $currentIdRuolo,
            'mestieri'       => $mestieri,
        ]);
        break;

    // -------------------------------------------------------------------------
    // change — imposta/cambia il mestiere di livello 3
    // -------------------------------------------------------------------------
    case 'change':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere'] ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']); break;
        }

        $existing = gdrcd_query("SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'result', true);
        if (gdrcd_query($existing, 'num_rows') >= 1) {
            gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'", 'query', true);
        } else {
            gdrcd_query("INSERT INTO clgpersonaggiomestiere (id_ruolo, personaggio) VALUES ($idRuolo, '$login')", 'query', true);
        }
        gdrcd_query($existing, 'free');
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'", 'query', true);

        echo json_encode(['success' => true, 'message' => 'Mestiere aggiornato']);
        break;

    // -------------------------------------------------------------------------
    // pick — conferma il mestiere e lo blocca; pubblica messaggio in bacheca
    // -------------------------------------------------------------------------
    case 'pick':
        $pg = gdrcd_query("SELECT esperienza FROM personaggio WHERE nome = '$login'", 'query', true);
        if ((int)($pg['esperienza'] ?? 0) < 10) {
            echo json_encode(['success' => false, 'message' => 'Servono almeno 10 punti esperienza per confermare il mestiere']); break;
        }

        $data     = json_decode(file_get_contents('php://input'), true);
        $mestiere = (int)($data['mestiere'] ?? 0);
        $titolo   = "Conferma nel mestiere - $login";
        $msg      = "Il personaggio [b]$login[/b] ha appena confermato il ruolo nel mestiere";

        gdrcd_query("UPDATE clgpersonaggiomestiere SET conferma_mestiere = 1 WHERE personaggio = '$login'", 'query', true);

        $araldo = gdrcd_query("SELECT id_araldo FROM araldo WHERE tipo = 6 AND proprietari = $mestiere", 'query', true);
        if ($araldo && $araldo['id_araldo']) {
            $id = (int)$araldo['id_araldo'];
            gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista)
                         VALUES ('-1', $id, '$titolo', '$msg', '$login', NOW(), NOW(), 'no', 'no')", 'query', true);
        }

        echo json_encode(['success' => true, 'message' => 'Ruolo nel mestiere confermato. Ricarica la pagina per vedere le modifiche.']);
        break;

    // -------------------------------------------------------------------------
    // levelUp — avanza al livello inferiore del mestiere corrente
    // -------------------------------------------------------------------------
    case 'levelUp':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere'] ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']); break;
        }

        gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'", 'query', true);
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'", 'query', true);

        echo json_encode(['success' => true, 'message' => 'Livello mestiere aggiornato']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
