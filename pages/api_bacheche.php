<?php
/**
 * api_bacheche.php — Endpoint JSON per il pannello React "Gestione bacheche"
 * (sostituisce pages/gestione_bacheche.inc.php, PHP monolitico senza AJAX)
 *
 * Gestisce la tabella araldo: le sezioni del forum (bacheche), ciascuna con
 * un tipo che ne determina sia il raggruppamento visivo sia chi può
 * accedervi (vedi can_access_section() in custom_functions.inc.php).
 *
 * op = list | tipi | get | save | delete   (solo admin)
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

if (($_SESSION['admin'] ?? 0) != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'list':
        $bacheche = [];
        $result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari, invisibile, punti FROM araldo ORDER BY tipo, nome", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $bacheche[] = [
                'id_araldo'   => (int)$row['id_araldo'],
                'nome'        => $row['nome'],
                'descrizione' => $row['descrizione'],
                'tipo'        => (int)$row['tipo'],
                'tipo_label'  => $MESSAGE['interface']['forums']['type'][(int)$row['tipo']] ?? '—',
                'proprietari' => (int)$row['proprietari'],
                'invisibile'  => (int)$row['invisibile'],
                'punti'       => (int)$row['punti'],
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'bacheche' => $bacheche]);
        break;

    // Elenco tipi + elenco proprietari selezionabili (razze/gilde/mestieri),
    // in un'unica chiamata: evita 4 round-trip separati ad ogni apertura form.
    case 'tipi':
        $tipi = [];
        for ($i = 0; $i <= SOLOADMIN; $i++) {
            $tipi[] = ['value' => $i, 'label' => $MESSAGE['interface']['forums']['type'][$i]];
        }

        $razze = [];
        $result = gdrcd_query("SELECT id_razza, nome_razza FROM razza ORDER BY nome_razza", 'result');
        while ($row = gdrcd_query($result, 'fetch')) $razze[] = ['id' => (int)$row['id_razza'], 'nome' => $row['nome_razza']];
        gdrcd_query($result, 'free');

        $gilde = [];
        $result = gdrcd_query("SELECT id_gilda, nome FROM gilda ORDER BY nome", 'result');
        while ($row = gdrcd_query($result, 'fetch')) $gilde[] = ['id' => (int)$row['id_gilda'], 'nome' => $row['nome']];
        gdrcd_query($result, 'free');

        $mestieri = [];
        $result = gdrcd_query("SELECT id_mestiere, nome FROM mestiere ORDER BY nome", 'result');
        while ($row = gdrcd_query($result, 'fetch')) $mestieri[] = ['id' => (int)$row['id_mestiere'], 'nome' => $row['nome']];
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'tipi' => $tipi, 'razze' => $razze, 'gilde' => $gilde, 'mestieri' => $mestieri]);
        break;

    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari, invisibile, punti FROM araldo WHERE id_araldo = $id LIMIT 1");
        if ($row) {
            echo json_encode(['success' => true, 'bacheca' => [
                'id_araldo'   => (int)$row['id_araldo'],
                'nome'        => $row['nome'],
                'descrizione' => $row['descrizione'],
                'tipo'        => (int)$row['tipo'],
                'proprietari' => (int)$row['proprietari'],
                'invisibile'  => (int)$row['invisibile'],
                'punti'       => (int)$row['punti'],
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bacheca non trovata']);
        }
        break;

    case 'save':
        $id          = (int)($_POST['id_araldo'] ?? 0);
        $nome        = trim($_POST['nome'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $tipo        = (int)($_POST['tipo'] ?? -1);
        $proprietari = (int)($_POST['proprietari'] ?? -1);
        $invisibile  = !empty($_POST['invisibile']) ? 1 : 0;
        $punti       = !empty($_POST['punti']) ? 1 : 0;

        if ($nome === '') {
            echo json_encode(['success' => false, 'message' => 'Il nome è obbligatorio']);
            exit;
        }
        if ($tipo < ONGAME || $tipo > SOLOADMIN) {
            echo json_encode(['success' => false, 'message' => 'Tipo non valido']);
            exit;
        }
        // proprietari ha senso solo per i tipi razza/gilda/mestiere — per tutti
        // gli altri tipi lo forzo a -1 (nessuno), niente più valori residui
        // scelti per sbaglio da un menu che prima restava sempre visibile.
        if (!in_array($tipo, [SOLORAZZA, SOLOGILDA, SOLOMESTIERE], true)) {
            $proprietari = -1;
        }

        $nome_esc        = gdrcd_filter('in', $nome);
        $descrizione_esc = gdrcd_filter('in', $descrizione);

        if ($id > 0) {
            gdrcd_query("UPDATE araldo SET nome = '$nome_esc', descrizione = '$descrizione_esc', tipo = $tipo, proprietari = $proprietari, invisibile = $invisibile, punti = $punti WHERE id_araldo = $id LIMIT 1");
        } else {
            gdrcd_query("INSERT INTO araldo (nome, descrizione, tipo, proprietari, invisibile, punti) VALUES ('$nome_esc', '$descrizione_esc', $tipo, $proprietari, $invisibile, $punti)");
        }

        echo json_encode(['success' => true, 'message' => $id > 0 ? 'Bacheca aggiornata.' : 'Bacheca creata.']);
        break;

    case 'delete':
        $id = (int)($_POST['id_araldo'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID non valido']);
            exit;
        }

        // Cascata completa: il vecchio codice cancellava solo messaggioaraldo,
        // lasciando araldo_letto/araldo_follow orfani (nessun vincolo di
        // integrità referenziale in questo schema, nessun errore, ma righe
        // morte che restano per sempre).
        gdrcd_query("DELETE FROM araldo_letto WHERE araldo_id = $id");
        gdrcd_query("DELETE FROM araldo_follow WHERE tipo_oggetto = 'sezione' AND riferimento_id = $id");
        gdrcd_query("DELETE FROM messaggioaraldo WHERE id_araldo = $id");
        gdrcd_query("DELETE FROM araldo WHERE id_araldo = $id LIMIT 1");

        echo json_encode(['success' => true, 'message' => 'Bacheca eliminata.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
