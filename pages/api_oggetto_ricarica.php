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

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($_GET['op']) {
        case 'getOggettiScaduti':  // Oggetti scaduti (cariche=0, richiede_ricarica=1) di un personaggio
            $pg = gdrcd_filter('in', trim($_GET['pg'] ?? ''));
            if ($pg === '') {
                echo json_encode(['success' => false, 'message' => 'Personaggio mancante']);
                break;
            }

            $res = gdrcd_query(
                "SELECT c.id_oggetto, o.nome, o.ricarica_massima
                 FROM clgpersonaggiooggetto c
                 JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                 WHERE c.nome = '$pg' AND c.cariche = 0 AND o.richiede_ricarica = 1",
                'result'
            );
            $oggetti = [];
            while ($r = gdrcd_query($res, 'fetch')) $oggetti[] = $r;

            echo json_encode(['success' => true, 'oggetti' => $oggetti]);
            break;

        case 'ricaricaObj':  // Ricarica un oggetto scaduto (stesse regole di idoneità dell'originale)
            $login = gdrcd_filter('in', $_SESSION['login']);
            $pg_selezionato = gdrcd_filter('in', trim($data['pg'] ?? ''));
            $id_oggetto = (int)($data['id_oggetto'] ?? 0);

            if ($pg_selezionato === '' || $id_oggetto <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
                break;
            }

            $oggetto = gdrcd_query("SELECT nome, ricarica_massima FROM oggetto WHERE id_oggetto = $id_oggetto");
            if (!$oggetto) {
                echo json_encode(['success' => false, 'message' => 'Oggetto non trovato']);
                break;
            }

            $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '$login'");
            $permesso = false;

            if ($pg_selezionato === $login) {
                // Auto-ricarica: costa 100 monete
                gdrcd_query("UPDATE personaggio SET soldi = soldi - 100 WHERE nome = '$login'");
                $permesso = true;
            } elseif ($_SESSION['admin'] == 1) {
                $permesso = true;
            } elseif (($mestiere['id_mestiere'] ?? 0) == 3) {
                $check_azioni = gdrcd_query(
                    "SELECT COUNT(*) AS azioni FROM chat
                     WHERE mittente = '$login' AND stanza = 24 AND tipo = 'P'
                       AND ora >= DATE_SUB(NOW(), INTERVAL 3 HOUR)"
                );
                $permesso = ($check_azioni['azioni'] ?? 0) >= 2;
            } elseif ($_SESSION['master'] == 1) {
                $check_master = gdrcd_query(
                    "SELECT COUNT(*) AS masterate FROM chat
                     WHERE mittente = '$login' AND tipo = 'M'
                       AND ora >= DATE_SUB(NOW(), INTERVAL 3 HOUR)"
                );
                $permesso = ($check_master['masterate'] ?? 0) >= 2;
            }

            if (!$permesso) {
                echo json_encode(['success' => false, 'message' => 'Non hai i requisiti per ricaricare questo oggetto (azioni recenti insufficienti)']);
                break;
            }

            gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = " . (int)$oggetto['ricarica_massima'] . "
                         WHERE nome = '$pg_selezionato' AND id_oggetto = $id_oggetto");

            // Master Screen solo se non è admin e non è un'auto-ricarica (comportamento originale)
            if ($pg_selezionato !== $login && $_SESSION['admin'] != 1) {
                $zona_pg = gdrcd_query("SELECT ultimo_luogo FROM personaggio WHERE nome = '$login'");
                $messaggio = gdrcd_filter('in', "L'oggetto <b>" . $oggetto['nome'] . "</b> di <b>$pg_selezionato</b> è stato ricaricato.");
                gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('" . (int)($zona_pg['ultimo_luogo'] ?? 0) . "', 'Sistema', NOW(), 'M', '$messaggio')");
            }

            echo json_encode(['success' => true, 'message' => "Oggetto '" . gdrcd_filter('out', $oggetto['nome']) . "' ricaricato con successo per $pg_selezionato"]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
    }
}
