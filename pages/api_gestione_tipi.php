<?php
if (isset($_GET['op']) && $_GET['op'] != '') {
    session_start();

    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');

    header('Content-Type: application/json');

    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non autenticato']);
        exit;
    }

    // Stesso gate della pagina gestione_tipi.inc.php.
    if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
        exit;
    }

    // Allowlist tabella + tabella/colonna da azzerare alla cancellazione (stessa
    // logica di gestione_tipi.inc.php originale) — mai costruire il nome tabella
    // direttamente dall'input, solo da questa mappa fissa.
    $mappaTipi = [
        'items'  => ['tabella' => 'codtipooggetto',  'usato_da_tabella' => 'oggetto',  'usato_da_colonna' => 'tipo'],
        'guilds' => ['tabella' => 'codtipogilda',    'usato_da_tabella' => 'gilda',    'usato_da_colonna' => 'tipo'],
        'jobs'   => ['tabella' => 'codtipomestiere', 'usato_da_tabella' => 'mestiere', 'usato_da_colonna' => 'tipo'],
    ];

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $types = $data['types'] ?? $_POST['types'] ?? '';

    if (!isset($mappaTipi[$types])) {
        echo json_encode(['success' => false, 'message' => 'Tipo di lista non valido']);
        exit;
    }
    $tabella          = $mappaTipi[$types]['tabella'];
    $usatoDaTabella   = $mappaTipi[$types]['usato_da_tabella'];
    $usatoDaColonna   = $mappaTipi[$types]['usato_da_colonna'];

    switch ($_GET['op']) {
        case 'saveTipo':  // Crea o modifica un tipo
            $cod_tipo = isset($_POST['cod_tipo']) ? (int)$_POST['cod_tipo'] : 0;
            $nome     = trim($_POST['nome'] ?? '');

            if ($nome === '') {
                echo json_encode(['success' => false, 'message' => 'Il nome è obbligatorio']);
                break;
            }

            $nome_f = gdrcd_filter('in', $nome);

            if ($cod_tipo > 0) {
                gdrcd_query("UPDATE $tabella SET descrizione = '$nome_f' WHERE cod_tipo = $cod_tipo LIMIT 1");
                echo json_encode(['success' => true, 'message' => 'Tipo modificato con successo']);
            } else {
                gdrcd_query("INSERT INTO $tabella (descrizione) VALUES ('$nome_f')");
                echo json_encode(['success' => true, 'message' => 'Tipo creato con successo']);
            }

            break;

        case 'deleteTipo':  // Elimina un tipo e azzera i riferimenti esistenti
            $cod_tipo = (int)($data['cod_tipo'] ?? 0);
            if ($cod_tipo <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID mancante']);
                break;
            }

            gdrcd_query("DELETE FROM $tabella WHERE cod_tipo = $cod_tipo LIMIT 1");
            gdrcd_query("UPDATE $usatoDaTabella SET $usatoDaColonna = 0 WHERE $usatoDaColonna = $cod_tipo");

            echo json_encode(['success' => true, 'message' => 'Tipo eliminato con successo']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
    }
}
