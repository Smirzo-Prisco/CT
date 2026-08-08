<?php
/**
 * api_mestiere.php — Selezione e avanzamento mestiere del personaggio
 *
 * op=getState  — stato corrente (step 2 = scelta, step 3 = avanzamento)
 * op=change    — sceglie il mestiere e lo conferma subito (step 2 -> step 3)
 * op=levelUp   — avanza di livello nel mestiere confermato (step 3)
 *
 * @author Crystal Tokyo Dev
 */

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

    // -------------------------------------------------------------------------
    // getState — stato personaggio + lista mestieri per lo step corrente
    // -------------------------------------------------------------------------
    case 'getState':
        $pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere, id_mestiere, id_ruolo_mestiere FROM personaggio WHERE nome = '$login'");
        $exp         = (int)($pg['esperienza']          ?? 0);
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);

        $conferma       = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'");
        $hasConferma    = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
        $currentIdRuolo = $conferma ? (int)$conferma['id_ruolo'] : 0;
        $step           = $hasConferma ? 3 : 2;

        if ($step === 2) {
            // Solo i mestieri veri (tipo=1): esclude le gilde giocatore, che condividono
            // la stessa tabella ruolo_mestiere ma non sono scelte in questa pagina
            $res      = gdrcd_query("SELECT rm.id_ruolo, rm.nome_ruolo, rm.immagine, rm.mestiere FROM ruolo_mestiere rm JOIN mestiere m ON rm.mestiere = m.id_mestiere WHERE rm.livello_mestiere = 3 AND m.tipo = 1 ORDER BY rm.nome_ruolo", 'result');
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
            $res = gdrcd_query(
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
                'result'
            );
            $mestieri = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $livello  = (int)$row['livello_mestiere'];
                // Livello 0 (capomestiere) non è accessibile in autonomia: assegnato solo da admin/capomestiere
                $unlocked = ($livello === 2 && $expMestiere >= 10)
                          || ($livello === 1 && $expMestiere >= 55);
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

        echo json_encode([
            'success'        => true,
            'step'           => $step,
            'esperienza'     => $exp,
            'expMestiere'    => $expMestiere,
            'hasConferma'    => $hasConferma,
            'currentIdRuolo' => $currentIdRuolo,
            'mestieri'       => $mestieri,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    // -------------------------------------------------------------------------
    // change — sceglie il mestiere e lo conferma subito (step 2 -> step 3)
    // -------------------------------------------------------------------------
    // Prima era un doppio passaggio (change poi pick, quest'ultimo bloccato
    // sotto i 10 px esperienza): lasciava per un tempo indefinito una riga in
    // clgpersonaggiomestiere con conferma_mestiere=0, invisibile nelle liste
    // staff che filtrano sulle sole affiliazioni confermate (es.
    // gestione_mestiere.inc.php) ma comunque conteggiata nel limite di
    // affiliazioni — bloccando una nuova assunzione senza modo di vederla per
    // liberarla. Ora la scelta è definitiva ed immediata (stesso avviso
    // "non sarà più possibile cambiarlo" lato client, vedi ScegliMestiere.jsx).
    case 'change':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }

        $existing = gdrcd_query("SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'result');
        if (gdrcd_query($existing, 'num_rows') >= 1) {
            gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo, conferma_mestiere = 1 WHERE personaggio = '$login'");
        } else {
            gdrcd_query("INSERT INTO clgpersonaggiomestiere (id_ruolo, personaggio, conferma_mestiere) VALUES ($idRuolo, '$login', 1)");
        }
        gdrcd_query($existing, 'free');
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'");

        $titolo = gdrcd_filter('in', "Conferma nel mestiere - $login");
        $msg    = gdrcd_filter('in', "Il personaggio [b]{$login}[/b] ha appena confermato il ruolo nel mestiere");

        $araldo = gdrcd_query("SELECT id_araldo FROM araldo WHERE tipo = 6 AND proprietari = $mestiere");
        if ($araldo && !empty($araldo['id_araldo'])) {
            $id  = (int)$araldo['id_araldo'];
            $sql = "INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista)"
                 . " VALUES ('-1', $id, '$titolo', '$msg', '$login', NOW(), NOW(), 'no', 'no')";
            gdrcd_query($sql);
        }

        echo json_encode(['success' => true, 'message' => 'Mestiere confermato. Esci e rientra per vedere le modifiche.']);
        break;

    // -------------------------------------------------------------------------
    // levelUp — avanza al livello inferiore del mestiere confermato (step 3)
    // -------------------------------------------------------------------------
    case 'levelUp':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }

        $ruolo = gdrcd_query("SELECT livello_mestiere FROM ruolo_mestiere WHERE id_ruolo = $idRuolo AND mestiere = $mestiere");
        if (!$ruolo) {
            echo json_encode(['success' => false, 'message' => 'Ruolo non valido']);
            exit;
        }
        $livelloRichiesto = (int)$ruolo['livello_mestiere'];

        // Livello 0 (capomestiere) non è accessibile in autonomia: solo admin/capomestiere
        if ($livelloRichiesto === 0) {
            echo json_encode(['success' => false, 'message' => 'Questo livello viene assegnato dal capomestiere']);
            exit;
        }

        $pg          = gdrcd_query("SELECT esperienza_mestiere FROM personaggio WHERE nome = '$login'");
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);
        if ($livelloRichiesto === 2 && $expMestiere < 10) {
            echo json_encode(['success' => false, 'message' => 'Servono almeno 10 punti mestiere per avanzare a questo livello']);
            exit;
        }
        if ($livelloRichiesto === 1 && $expMestiere < 55) {
            echo json_encode(['success' => false, 'message' => 'Servono almeno 55 punti mestiere per avanzare a questo livello']);
            exit;
        }

        gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'");
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'");

        echo json_encode(['success' => true, 'message' => 'Livello mestiere aggiornato']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
