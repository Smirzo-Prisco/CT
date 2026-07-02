<?php
/**
 * api_mestieri.php — Endpoint JSON per il pannello React "Gestione mestieri"
 *
 * op = list | get | tipi | save | hide | save_ruolo | delete_ruolo
 * Letture (list/get/tipi) via GET, scritture via POST (multipart quando
 * è coinvolta un'immagine, altrimenti form-urlencoded — $_POST funziona
 * identico in entrambi i casi).
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

$op = $_GET['op'] ?? '';

/** Ricarica un mestiere con la descrizione del tipo, per le risposte */
function carica_mestiere($id) {
    return gdrcd_query(
        "SELECT m.*, c.descrizione AS tipo_descrizione
         FROM mestiere m LEFT JOIN codtipomestiere c ON m.tipo = c.cod_tipo
         WHERE m.id_mestiere = " . (int)$id
    );
}

/** Ricarica l'elenco ruoli di un mestiere */
function carica_ruoli($id_mestiere) {
    $ruoli  = [];
    $result = gdrcd_query(
        "SELECT * FROM ruolo_mestiere WHERE mestiere = " . (int)$id_mestiere . " ORDER BY capo DESC, stipendio DESC",
        'result'
    );
    while ($row = gdrcd_query($result, 'fetch')) {
        $ruoli[] = $row;
    }
    gdrcd_query($result, 'free');
    return $ruoli;
}

switch ($op) {

    // -------------------------------------------------------------------------
    // LIST — elenco paginato mestieri
    // -------------------------------------------------------------------------
    case 'list':
        $per_page = (int)$PARAMETERS['settings']['records_per_page'];
        $offset   = max(0, (int)($_GET['offset'] ?? 0)) * $per_page;

        $totale = (int)gdrcd_query("SELECT COUNT(*) AS n FROM mestiere")['n'];
        $result = gdrcd_query(
            "SELECT m.id_mestiere, m.nome, m.visibile, c.descrizione AS tipo_descrizione
             FROM mestiere m LEFT JOIN codtipomestiere c ON m.tipo = c.cod_tipo
             ORDER BY m.nome LIMIT $offset, $per_page",
            'result'
        );
        $mestieri = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $mestieri[] = $row;
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'mestieri' => $mestieri, 'totale' => $totale, 'per_page' => $per_page]);
        break;

    // -------------------------------------------------------------------------
    // TIPI — elenco tipi mestiere (per il form)
    // -------------------------------------------------------------------------
    case 'tipi':
        $tipi   = [];
        $result = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipomestiere ORDER BY descrizione", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $tipi[] = $row;
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'tipi' => $tipi]);
        break;

    // -------------------------------------------------------------------------
    // GET — un mestiere + i suoi ruoli + i tipi (tutto per la vista di modifica)
    // -------------------------------------------------------------------------
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0 && $id !== -1) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non specificato']);
            exit;
        }

        // id = -1 è il mestiere "virtuale" usato per i ruoli/lavori indipendenti,
        // non ha una riga propria nella tabella mestiere
        if ($id === -1) {
            $mestiere = ['id_mestiere' => -1, 'nome' => 'Lavori indipendenti', 'indipendenti' => true];
        } else {
            $mestiere = carica_mestiere($id);
            if (!$mestiere) {
                echo json_encode(['success' => false, 'message' => 'Mestiere non trovato']);
                exit;
            }
        }

        $tipi   = [];
        $result = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipomestiere ORDER BY descrizione", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $tipi[] = $row;
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'mestiere' => $mestiere, 'ruoli' => carica_ruoli($id), 'tipi' => $tipi]);
        break;

    // -------------------------------------------------------------------------
    // SAVE — crea o aggiorna un mestiere (multipart, immagine opzionale)
    // -------------------------------------------------------------------------
    case 'save':
        try {
            $id     = (int)($_POST['id_mestiere'] ?? 0);
            $isEdit = ($id > 0);
            $nome   = trim($_POST['nome'] ?? '');
            $tipo   = (int)($_POST['tipo'] ?? 0);

            if ($nome === '' || $tipo <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nome e tipo sono obbligatori']);
                exit;
            }

            $visibile = (isset($_POST['visibile']) && $_POST['visibile'] == '1') ? 1 : 0;
            $url_sito = gdrcd_filter('in', $_POST['url_sito'] ?? '');
            $statuto  = gdrcd_filter('in', $_POST['statuto'] ?? '');
            $nome_esc = gdrcd_filter('in', $nome);

            $esistente = $isEdit ? carica_mestiere($id) : null;
            if ($isEdit && !$esistente) {
                echo json_encode(['success' => false, 'message' => 'Mestiere non trovato']);
                exit;
            }

            $immagine = saveUploadedImage($_FILES['immagine'] ?? [], 'imgs/mestieri', 'mestiere_', $esistente['immagine'] ?? null);
            if ($immagine === null) {
                $immagine = $esistente['immagine'] ?? 'standard_mestiere.png';
            }
            $immagine_esc = gdrcd_filter('in', $immagine);

            if ($isEdit) {
                gdrcd_query("UPDATE mestiere SET nome='$nome_esc', tipo=$tipo, visibile=$visibile, immagine='$immagine_esc', url_sito='$url_sito', statuto='$statuto' WHERE id_mestiere=$id LIMIT 1");
            } else {
                gdrcd_query("INSERT INTO mestiere (nome, tipo, immagine, url_sito, visibile, statuto) VALUES ('$nome_esc', $tipo, '$immagine_esc', '$url_sito', $visibile, '$statuto')");
                $id = (int)gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
            }

            echo json_encode(['success' => true, 'message' => 'Mestiere salvato.', 'mestiere' => carica_mestiere($id)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // -------------------------------------------------------------------------
    // HIDE — nasconde un mestiere (visibile=0), non lo cancella mai fisicamente
    // -------------------------------------------------------------------------
    case 'hide':
        $id = (int)($_POST['id_mestiere'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non specificato']);
            exit;
        }
        gdrcd_query("UPDATE mestiere SET visibile=0 WHERE id_mestiere=$id LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Mestiere nascosto.']);
        break;

    // -------------------------------------------------------------------------
    // SAVE_RUOLO — crea o aggiorna un ruolo di un mestiere (multipart)
    // -------------------------------------------------------------------------
    case 'save_ruolo':
        try {
            $id_ruolo   = (int)($_POST['id_ruolo'] ?? 0);
            $isEdit     = ($id_ruolo > 0);
            $mestiere   = (int)($_POST['mestiere'] ?? 0);
            $nome_ruolo = trim($_POST['nome'] ?? '');

            // mestiere = -1 è il contenitore "virtuale" dei ruoli/lavori indipendenti
            if (($mestiere <= 0 && $mestiere !== -1) || $nome_ruolo === '') {
                echo json_encode(['success' => false, 'message' => 'Nome ruolo e mestiere sono obbligatori']);
                exit;
            }

            $livello   = max(1, min(3, (int)($_POST['livello_mestiere'] ?? 3)));
            $stipendio = (int)($_POST['stipendio'] ?? 0);
            $capo      = (isset($_POST['capo']) && $_POST['capo'] == '1') ? 1 : 0;
            $nome_esc  = gdrcd_filter('in', $nome_ruolo);

            $esistente = $isEdit ? gdrcd_query("SELECT * FROM ruolo_mestiere WHERE id_ruolo=$id_ruolo") : null;
            if ($isEdit && !$esistente) {
                echo json_encode(['success' => false, 'message' => 'Ruolo non trovato']);
                exit;
            }

            $immagine = saveUploadedImage($_FILES['immagine'] ?? [], 'imgs/mestieri', 'ruolo_', $esistente['immagine'] ?? null);
            if ($immagine === null) {
                $immagine = $esistente['immagine'] ?? 'standard_gilda.png';
            }
            $immagine_esc = gdrcd_filter('in', $immagine);

            if ($isEdit) {
                gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo='$nome_esc', capo=$capo, immagine='$immagine_esc', mestiere=$mestiere, livello_mestiere=$livello, stipendio=$stipendio WHERE id_ruolo=$id_ruolo LIMIT 1");
            } else {
                gdrcd_query("INSERT INTO ruolo_mestiere (nome_ruolo, mestiere, immagine, stipendio, capo, livello_mestiere) VALUES ('$nome_esc', $mestiere, '$immagine_esc', $stipendio, $capo, $livello)");
            }

            echo json_encode(['success' => true, 'message' => 'Ruolo salvato.', 'ruoli' => carica_ruoli($mestiere)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // -------------------------------------------------------------------------
    // DELETE_RUOLO — elimina un ruolo e rimuove i personaggi che lo avevano
    // -------------------------------------------------------------------------
    case 'delete_ruolo':
        $id_ruolo = (int)($_POST['id_ruolo'] ?? 0);
        $mestiere = (int)($_POST['mestiere'] ?? 0);
        if ($id_ruolo <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ruolo non specificato']);
            exit;
        }
        gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo=$id_ruolo");
        gdrcd_query("DELETE FROM ruolo_mestiere WHERE id_ruolo=$id_ruolo LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Ruolo eliminato.', 'ruoli' => carica_ruoli($mestiere)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
