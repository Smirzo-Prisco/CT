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
 * Protezioni errori:
 *  - ob_start() cattura qualsiasi output spurio (warning, die) prima del JSON
 *  - catch (\Throwable) cattura sia Exception che Error (PHP 7+)
 *  - jsonOut() verifica che json_encode non restituisca false (dati non-UTF8)
 *
 * @author Crystal Tokyo Dev
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
gdrcd_connect();

if (empty($_SESSION['login'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$login = gdrcd_filter('in', $_SESSION['login']);
$op    = $_GET['op'] ?? '';
// Rilascia il lock della sessione — la leggiamo solo, non la scriviamo.
// Senza questo, le richieste concorrenti (sidebar) si accodano e possono
// causare timeout silenzioso ("Unexpected end of JSON input").
session_write_close();

/**
 * Scarica il buffer, controlla che json_encode sia riuscito, poi invia.
 * Se json_encode fallisce (dati non-UTF8) restituisce un errore leggibile.
 */
function jsonOut(array $data) {
    ob_end_clean();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        echo json_encode(['success' => false, 'message' => 'Errore codifica JSON: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
    exit;
}

try {

switch ($op) {

    // -------------------------------------------------------------------------
    // getState — dati pg + mestieri disponibili
    // -------------------------------------------------------------------------
    case 'getState':
        $pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere, id_mestiere, id_ruolo_mestiere FROM personaggio WHERE nome = '$login'", 'query', true);
        $exp         = (int)($pg['esperienza']         ?? 0);
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);

        $conferma       = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'query', true);
        $hasConferma    = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
        $currentIdRuolo = $conferma ? (int)$conferma['id_ruolo'] : 0;

        $step = $hasConferma ? 3 : 2;

        if ($step === 2) {
            $res      = gdrcd_query("SELECT id_ruolo, nome_ruolo, immagine, mestiere FROM ruolo_mestiere WHERE livello_mestiere = 3 ORDER BY nome_ruolo", 'result', true);
            $mestieri = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $mestieri[] = [
                    'id'       => (int)$row['id_ruolo'],
                    'nome'     => gdrcd_filter('out', (string)$row['nome_ruolo']),
                    'immagine' => (string)$row['immagine'],
                    'mestiere' => (int)$row['mestiere'],
                    'selected' => ((int)$row['id_ruolo'] === $currentIdRuolo),
                ];
            }
            gdrcd_query($res, 'free');
        } else {
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
                $livello  = (int)$row['livello_mestiere'];
                $unlocked = ($expMestiere >= 55 && $livello > 1)
                          || ($expMestiere >= 10 && $expMestiere < 55 && $livello > 2);

                $mestieri[] = [
                    'id'       => (int)$row['id_ruolo'],
                    'nome'     => gdrcd_filter('out', (string)$row['nome_ruolo']),
                    'immagine' => (string)$row['immagine'],
                    'mestiere' => (int)$row['mestiere'],
                    'livello'  => $livello,
                    'unlocked' => $unlocked,
                ];
            }
            gdrcd_query($res, 'free');
        }

        jsonOut([
            'success'        => true,
            'step'           => $step,
            'esperienza'     => $exp,
            'expMestiere'    => $expMestiere,
            'hasConferma'    => $hasConferma,
            'currentIdRuolo' => $currentIdRuolo,
            'mestieri'       => $mestieri,
        ]);

    // -------------------------------------------------------------------------
    // change — imposta/cambia il mestiere di livello 3
    // -------------------------------------------------------------------------
    case 'change':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            jsonOut(['success' => false, 'message' => 'Dati mancanti']);
        }

        $existing = gdrcd_query("SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'result', true);
        if (gdrcd_query($existing, 'num_rows') >= 1) {
            gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'", 'query', true);
        } else {
            gdrcd_query("INSERT INTO clgpersonaggiomestiere (id_ruolo, personaggio) VALUES ($idRuolo, '$login')", 'query', true);
        }
        gdrcd_query($existing, 'free');
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'", 'query', true);

        jsonOut(['success' => true, 'message' => 'Mestiere aggiornato']);

    // -------------------------------------------------------------------------
    // pick — conferma il mestiere e lo blocca; pubblica messaggio in bacheca
    // -------------------------------------------------------------------------
    case 'pick':
        $pg = gdrcd_query("SELECT esperienza FROM personaggio WHERE nome = '$login'", 'query', true);
        if ((int)($pg['esperienza'] ?? 0) < 10) {
            jsonOut(['success' => false, 'message' => 'Servono almeno 10 punti esperienza per confermare il mestiere']);
        }

        $data     = json_decode(file_get_contents('php://input'), true);
        $mestiere = (int)($data['mestiere'] ?? 0);
        $titolo   = gdrcd_filter('in', "Conferma nel mestiere - $login");
        $msg      = gdrcd_filter('in', "Il personaggio [b]$login[/b] ha appena confermato il ruolo nel mestiere");

        gdrcd_query("UPDATE clgpersonaggiomestiere SET conferma_mestiere = 1 WHERE personaggio = '$login'", 'query', true);

        $araldo = gdrcd_query("SELECT id_araldo FROM araldo WHERE tipo = 6 AND proprietari = $mestiere", 'query', true);
        if ($araldo && !empty($araldo['id_araldo'])) {
            $id = (int)$araldo['id_araldo'];
            gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista)
                         VALUES ('-1', $id, '$titolo', '$msg', '$login', NOW(), NOW(), 'no', 'no')", 'query', true);
        }

        jsonOut(['success' => true, 'message' => 'Ruolo nel mestiere confermato. Esci e rientra per vedere le modifiche.']);

    // -------------------------------------------------------------------------
    // levelUp — avanza al livello inferiore del mestiere corrente
    // -------------------------------------------------------------------------
    case 'levelUp':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            jsonOut(['success' => false, 'message' => 'Dati mancanti']);
        }

        gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'", 'query', true);
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'", 'query', true);

        jsonOut(['success' => true, 'message' => 'Livello mestiere aggiornato']);

    default:
        jsonOut(['success' => false, 'message' => 'Operazione non valida']);
}

} catch (\Throwable $e) {
    // Cattura sia Exception che Error (PHP 7+)
    jsonOut(['success' => false, 'message' => $e->getMessage()]);
}
