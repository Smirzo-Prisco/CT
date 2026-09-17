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

    // Stesso gate della pagina gestione_abilita_master.inc.php: chi la vede
    // puo' anche creare/modificare/cancellare/assegnare, nessuna azione ha un
    // permesso piu' stretto delle altre.
    if (!hasPermesso($_SESSION, ['admin', 'master'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($_GET['op']) {
        case 'getAbilita':  // Dati di una skill temporanea per il form di modifica
            $id = (int)($_GET['id'] ?? 0);
            $abilita = gdrcd_query("SELECT id_abilita, nome, descrizione FROM abilita WHERE id_abilita = $id AND tipo = 'Skill temporanea'");

            if ($abilita) echo json_encode(['success' => true, 'abilita' => $abilita]);
            else echo json_encode(['success' => false, 'message' => 'Skill non trovata']);

            break;

        case 'saveAbilita':  // Crea o modifica una skill temporanea
            $id_abilita  = isset($_POST['id_abilita']) ? (int)$_POST['id_abilita'] : 0;
            $nome        = trim($_POST['nome'] ?? '');
            $descrizione = trim($_POST['descrizione'] ?? '');

            if ($nome === '' || $descrizione === '') {
                echo json_encode(['success' => false, 'message' => 'Nome e descrizione sono obbligatori']);
                break;
            }

            $nome_f        = gdrcd_filter('in', $nome);
            $descrizione_f = gdrcd_filter('in', $descrizione);

            // tipo/id_razza/max_lvl fissi: questa pagina gestisce solo skill temporanee,
            // sempre assegnate a grado 1 e mai livellate via mercato_abilita (vedi il
            // commento nel form della pagina) — max_lvl = 1 invece di 0 per non mostrare
            // "Livello attuale: 1/0" sulla scheda del personaggio.
            if ($id_abilita > 0) {
                $esistente = gdrcd_query("SELECT id_abilita FROM abilita WHERE id_abilita = $id_abilita AND tipo = 'Skill temporanea'");
                if (!$esistente) {
                    echo json_encode(['success' => false, 'message' => 'Skill non trovata']);
                    break;
                }
                gdrcd_query("UPDATE abilita SET nome = '$nome_f', descrizione = '$descrizione_f' WHERE id_abilita = $id_abilita");
                echo json_encode(['success' => true, 'message' => 'Skill modificata con successo']);
            } else {
                gdrcd_query("INSERT INTO abilita (nome, descrizione, max_lvl, id_razza, tipo) VALUES ('$nome_f', '$descrizione_f', 1, '0', 'Skill temporanea')");
                echo json_encode(['success' => true, 'message' => 'Skill creata con successo']);
            }

            break;

        case 'deleteAbilita':  // Elimina definitivamente una skill temporanea
            $id_abilita = (int)($data['id_abilita'] ?? 0);
            if ($id_abilita <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID mancante']);
                break;
            }

            $esistente = gdrcd_query("SELECT id_abilita FROM abilita WHERE id_abilita = $id_abilita AND tipo = 'Skill temporanea'");
            if (!$esistente) {
                echo json_encode(['success' => false, 'message' => 'Skill non trovata']);
                break;
            }

            gdrcd_query("DELETE FROM abilita WHERE id_abilita = $id_abilita LIMIT 1");
            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita = $id_abilita");

            echo json_encode(['success' => true, 'message' => 'Skill eliminata con successo']);
            break;

        case 'assegnaAbilita':  // Assegna la skill temporanea a un personaggio per N usi
            $id_abilita = (int)($_POST['id_abilita'] ?? 0);
            $personaggio = trim($_POST['personaggio'] ?? '');
            $usi = (int)($_POST['usi'] ?? 0);

            if ($id_abilita <= 0 || $personaggio === '' || $usi < 1 || $usi > 5) {
                echo json_encode(['success' => false, 'message' => 'Dati mancanti o non validi']);
                break;
            }

            $abilita = gdrcd_query("SELECT id_abilita FROM abilita WHERE id_abilita = $id_abilita AND tipo = 'Skill temporanea'");
            if (!$abilita) {
                echo json_encode(['success' => false, 'message' => 'Skill non trovata']);
                break;
            }

            $personaggio_f = gdrcd_filter('in', $personaggio);
            $pg = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '$personaggio_f'");
            if (!$pg) {
                echo json_encode(['success' => false, 'message' => 'Personaggio non trovato']);
                break;
            }

            gdrcd_query("INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado, usi) VALUES ('$personaggio_f', $id_abilita, '1', $usi)");

            echo json_encode(['success' => true, 'message' => 'Skill temporanea assegnata correttamente']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
    }
}
