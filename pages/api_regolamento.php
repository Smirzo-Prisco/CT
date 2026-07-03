<?php
/**
 * api_regolamento.php — Endpoint JSON per il pannello "Gestione regolamento"
 *
 * Stesso pattern di pages/api_personaggio.php (usato da gestione_personaggio.inc.php):
 * un modale unico nella pagina, popolato/salvato via fetch invece di un giro
 * completo di reload PHP per ogni inserimento/modifica/cancellazione.
 *
 * op = getArticolo (GET,  id)         — dati di un articolo per popolare il modale
 * op = save        (POST)             — inserimento o modifica (distingue in base a "art")
 * op = erase       (POST, id)         — cancellazione
 *
 * Permessi: admin o guida, verificati su ogni operazione (non solo alla vista,
 * a differenza di api_personaggio.php che si fida della sola sessione).
 */

session_start();

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
require_once(__DIR__ . '/function_color.php');
$handleDBConnection = gdrcd_connect();

header('Content-Type: application/json');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if (!hasPermesso($_SESSION, ['admin', 'guida'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => gdrcd_filter('out', $MESSAGE['error']['not_allowed'])]);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    // -------------------------------------------------------------------------
    // GETARTICOLO — dati di un articolo per popolare il modale di modifica
    // -------------------------------------------------------------------------
    case 'getArticolo':
        $id  = (int)($_GET['id'] ?? 0);
        $row = gdrcd_query("SELECT articolo, titolo, testo, tipo FROM regolamento WHERE articolo = $id LIMIT 1");
        if ($row) echo json_encode(['success' => true, 'articolo' => $row]);
        else      echo json_encode(['success' => false, 'message' => 'Articolo non trovato']);
        break;

    // -------------------------------------------------------------------------
    // SAVE — inserimento (art assente) o modifica (art = numero articolo originale)
    // -------------------------------------------------------------------------
    case 'save':
        $art_originale = (isset($_POST['art']) && $_POST['art'] !== '') ? (int)$_POST['art'] : null;
        $titolo        = trim($_POST['titolo'] ?? '');
        $testo         = $_POST['testo'] ?? '';
        $tipo          = $_POST['tipo'] ?? '';

        if ($titolo === '') {
            echo json_encode(['success' => false, 'message' => 'Il titolo è obbligatorio']);
            exit;
        }

        $titolo_esc = gdrcd_filter('in', $titolo);
        $testo_esc  = gdrcd_filter('in', $testo);
        $tipo_esc   = gdrcd_filter('in', $tipo);

        if ($art_originale === null) {
            // Inserimento — il numero articolo non è più scelto dall'utente:
            // il sistema assegna automaticamente il primo libero dopo l'ultimo esistente
            $max_articolo = (int)(gdrcd_query("SELECT MAX(articolo) AS n FROM regolamento")['n'] ?? 0);
            $articolo     = $max_articolo + 1;
            gdrcd_query("INSERT INTO regolamento (articolo, titolo, testo, tipo) VALUES ($articolo, '$titolo_esc', '$testo_esc', '$tipo_esc')");
            echo json_encode(['success' => true, 'message' => 'Articolo inserito.']);
            break;
        }

        // Modifica — il numero articolo resta invariato; logica di diff/notifica
        // in bacheca invariata rispetto alla versione precedente
        gdrcd_query("UPDATE regolamento SET titolo='$titolo_esc', testo='$testo_esc', tipo='$tipo_esc' WHERE articolo=$art_originale LIMIT 1");

        $old_titolo = $_POST['old_titolo'] ?? '';
        $old_testo  = $_POST['old_testo'] ?? '';
        $diff       = get_decorated_diff($old_testo, $testo);

        $id_mex   = '264815';
        $diff_old = mb_substr($diff['old'], 0, 10000);
        $diff_new = mb_substr($diff['new'], 0, 10000);
        $testo_notifica = "Modifica al <b>manuale</b>.<br><br><b>Prima della modifica</b><br>[spoiler]<b>Titolo</b>:" . $old_titolo . "<br><b>Testo</b>:" . $diff_old . "[/spoiler]
                       <br><br><b>Modifica</b><br>[spoiler]<b>Titolo</b>:" . $titolo . "<br><b>Testo</b>:" . $diff_new . "[/spoiler]";

        gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio) VALUES ($id_mex, '142', '" . gdrcd_filter('in', $testo_notifica) . "', 'Coordinazione', 'no', 'no', NOW(), NOW())");
        gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $id_mex AND nome != '" . $_SESSION['login'] . "'");

        // Notifica separata per le guide
        if ($_SESSION['guida'] == 1) {
            $id_mex_guide = '250843';
            $testo_guide  = "Il personaggio <b>" . $_SESSION['login'] . "</b> ha modificato la seguente sezione del manuale: <b>" . $old_titolo . "</b>";
            gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio) VALUES ($id_mex_guide, '109', '" . gdrcd_filter('in', $testo_guide) . "', 'Sistema', 'no', 'no', NOW(), NOW())");
            gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $id_mex_guide AND nome != '" . $_SESSION['login'] . "'");
        }

        echo json_encode(['success' => true, 'message' => 'Articolo modificato.']);
        break;

    // -------------------------------------------------------------------------
    // ERASE — cancellazione
    // -------------------------------------------------------------------------
    case 'erase':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Articolo non specificato']);
            exit;
        }
        gdrcd_query("DELETE FROM regolamento WHERE articolo=$id LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Articolo eliminato.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}

exit();
