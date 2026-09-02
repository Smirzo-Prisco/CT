<?php
/**
 * api_mappe.php — Endpoint JSON per il pannello React "Gestione mappe"
 * (sostituisce pages/gestione_mappe.inc.php, PHP monolitico senza AJAX)
 *
 * Gestisce la tabella mappa_click: le "mappe grandi" cliccabili (es. "Mappa
 * principale", "Shinjuku"...), ciascuna delle quali raggruppa più stanze
 * (tabella mappa, colonna id_mappa → mappa_click.id_click).
 *
 * op = list | get | save | delete   (solo admin)
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
        $mappe = [];
        $result = gdrcd_query("
            SELECT mc.id_click, mc.nome, mc.posizione, mc.mobile,
                   (SELECT COUNT(*) FROM mappa m WHERE m.id_mappa = mc.id_click) AS n_stanze
            FROM mappa_click mc
            ORDER BY mc.nome
        ", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $mappe[] = [
                'id_click'  => (int)$row['id_click'],
                'nome'      => $row['nome'],
                'posizione' => (int)$row['posizione'],
                'mobile'    => (int)$row['mobile'],
                'n_stanze'  => (int)$row['n_stanze'],
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'mappe' => $mappe]);
        break;

    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = gdrcd_query("SELECT id_click, nome, posizione, mobile, immagine, larghezza, altezza, meteo, descrizione FROM mappa_click WHERE id_click = $id LIMIT 1");
        if ($row) {
            echo json_encode(['success' => true, 'mappaClick' => [
                'id_click'    => (int)$row['id_click'],
                'nome'        => $row['nome'],
                'posizione'   => (int)$row['posizione'],
                'mobile'      => (int)$row['mobile'],
                'immagine'    => $row['immagine'],
                'larghezza'   => (int)$row['larghezza'],
                'altezza'     => (int)$row['altezza'],
                'meteo'       => $row['meteo'],
                'descrizione' => $row['descrizione'],
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mappa non trovata']);
        }
        break;

    case 'save':
        $id          = (int)($_POST['id_click'] ?? 0);
        $nome        = trim($_POST['nome'] ?? '');
        $posizione   = (int)($_POST['posizione'] ?? 0);
        $mobile      = !empty($_POST['mobile']) ? 1 : 0;
        $immagine    = trim($_POST['immagine'] ?? '') !== '' ? gdrcd_filter('in', trim($_POST['immagine'])) : 'standard_mappa.png';
        $larghezza   = (int)($_POST['larghezza'] ?? 500);
        $altezza     = (int)($_POST['altezza'] ?? 330);
        $meteo       = gdrcd_filter('in', $_POST['meteo'] ?? '');
        $descrizione = gdrcd_filter('in', $_POST['descrizione'] ?? '');

        if ($nome === '') {
            echo json_encode(['success' => false, 'message' => 'Il nome è obbligatorio']);
            exit;
        }
        if ($larghezza <= 0 || $altezza <= 0) {
            echo json_encode(['success' => false, 'message' => 'Larghezza e altezza devono essere maggiori di zero']);
            exit;
        }

        $nome_esc = gdrcd_filter('in', $nome);

        if ($id > 0) {
            gdrcd_query("UPDATE mappa_click SET nome = '$nome_esc', posizione = $posizione, mobile = $mobile, immagine = '$immagine', larghezza = $larghezza, altezza = $altezza, meteo = '$meteo', descrizione = '$descrizione' WHERE id_click = $id LIMIT 1");
        } else {
            gdrcd_query("INSERT INTO mappa_click (nome, posizione, mobile, immagine, larghezza, altezza, meteo, descrizione) VALUES ('$nome_esc', $posizione, $mobile, '$immagine', $larghezza, $altezza, '$meteo', '$descrizione')");
        }

        echo json_encode(['success' => true, 'message' => $id > 0 ? 'Mappa aggiornata.' : 'Mappa creata.']);
        break;

    case 'delete':
        $id = (int)($_POST['id_click'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID non valido']);
            exit;
        }

        // Protezione assente nel vecchio codice: mappa.id_mappa referenzia
        // mappa_click.id_click senza vincolo di integrità nello schema —
        // cancellare qui senza controllo lascerebbe tutte le stanze
        // collegate orfane (mappa/luogo inesistente per chi ci si trova).
        $n_stanze = (int)(gdrcd_query("SELECT COUNT(*) AS n FROM mappa WHERE id_mappa = $id")['n'] ?? 0);
        if ($n_stanze > 0) {
            echo json_encode(['success' => false, 'message' => "Impossibile eliminare: $n_stanze stanze sono ancora collegate a questa mappa. Spostale o eliminale prima da Gestione luoghi."]);
            exit;
        }

        gdrcd_query("DELETE FROM mappa_click WHERE id_click = $id LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Mappa eliminata.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
