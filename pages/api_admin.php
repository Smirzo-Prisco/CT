<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$op    = $_GET['op'] ?? '';
$data  = json_decode(file_get_contents('php://input'), true) ?? [];
$login = gdrcd_filter('in', $_SESSION['login']);

$is_admin = ($_SESSION['admin'] == 1);
$is_mod   = ($_SESSION['moderatore'] == 1);
$is_master = ($_SESSION['master'] == 1);
$is_custode = (gdrcd_query("SELECT id_gilda FROM personaggio WHERE nome = '$login'")['id_gilda'] ?? 0) == 4;

// Accesso minimo: almeno moderatore, master o custode
if (!$is_admin && !$is_mod && !$is_master && !$is_custode) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

switch ($op) {

    // -------------------------------------------------------------------------
    // CHARACTERS — lista / ricerca personaggi con filtri
    // -------------------------------------------------------------------------
    case 'characters':
        $filtro = $_GET['filtro'] ?? 'tutti';
        $cerca  = gdrcd_filter('in', $_GET['cerca'] ?? '');

        $where = 'WHERE 1=1';
        switch ($filtro) {
            case 'inattivi':
                $where .= " AND DATE_ADD(ora_entrata, INTERVAL 150 DAY) <= NOW() AND (esilio < NOW() OR esilio IS NULL)";
                break;
            case 'mai_entrati':
                $where .= " AND esperienza = '0.0000' AND DATE(ora_entrata) = DATE(data_iscrizione)";
                break;
            case 'esiliati':
                $where .= " AND esilio > NOW() AND esilio != '0000-00-00' AND esilio IS NOT NULL";
                break;
        }
        if (!empty($cerca)) {
            $where .= " AND (p.nome LIKE '%$cerca%' OR p.cognome LIKE '%$cerca%')";
        }

        $result = gdrcd_query("SELECT p.nome, p.cognome, p.permessi, p.sesso,
                p.salute, p.salute_max, p.integrita, p.esperienza,
                p.ora_entrata, p.ora_uscita, p.ultimo_refresh,
                p.esilio, p.motivo_esilio, p.autore_esilio,
                p.url_img_chat, p.id_gilda,
                r.nome_razza, g.nome AS nome_gilda, m.nome AS nome_mestiere
            FROM personaggio p
            LEFT JOIN razza    r ON p.id_razza    = r.id_razza
            LEFT JOIN gilda    g ON p.id_gilda    = g.id_gilda
            LEFT JOIN mestiere m ON p.id_mestiere = m.id_mestiere
            $where
            ORDER BY p.nome ASC", 'result');

        $characters = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $characters[] = [
                'nome'        => $row['nome'],
                'cognome'     => $row['cognome'],
                'permessi'    => (int)$row['permessi'],
                'sesso'       => $row['sesso'],
                'salute'      => (int)$row['salute'],
                'salute_max'  => (int)$row['salute_max'],
                'integrita'   => (int)$row['integrita'],
                'esperienza'  => (float)$row['esperienza'],
                'nome_razza'  => $row['nome_razza'],
                'nome_gilda'  => $row['nome_gilda'],
                'nome_mestiere' => $row['nome_mestiere'],
                'url_img_chat' => $row['url_img_chat'],
                'online'      => !empty($row['ultimo_refresh']) &&
                                 strtotime($row['ultimo_refresh']) + 240 > time() &&
                                 $row['ora_entrata'] > $row['ora_uscita'],
                'esiliato'    => !empty($row['esilio']) &&
                                 $row['esilio'] != '0000-00-00' &&
                                 $row['esilio'] > date('Y-m-d'),
                'esilio_data' => $row['esilio'],
                'esilio_motivo' => $row['motivo_esilio'],
                'esilio_autore' => $row['autore_esilio'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'characters' => $characters, 'totale' => count($characters)]);
        break;

    // -------------------------------------------------------------------------
    // EXILE — esilia o graczia un personaggio (admin o moderatore)
    // -------------------------------------------------------------------------
    case 'exile':
        if (!$is_admin && !$is_mod) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Solo admin e moderatori']);
            exit;
        }

        $pg      = gdrcd_filter('in', $data['pg'] ?? '');
        $azione  = $data['azione'] ?? ''; // 'esilia' o 'grazia'
        $data_fine = $data['data_fine'] ?? null;
        $motivo  = gdrcd_filter('in', $data['motivo'] ?? '');

        if (empty($pg)) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non specificato']);
            exit;
        }

        if ($azione === 'esilia') {
            if (empty($data_fine) || empty($motivo)) {
                echo json_encode(['success' => false, 'message' => 'Data fine e motivo obbligatori']);
                exit;
            }
            gdrcd_query("UPDATE personaggio SET
                esilio        = '" . gdrcd_filter('in', $data_fine) . "',
                data_esilio   = NOW(),
                autore_esilio = '$login',
                motivo_esilio = '$motivo'
                WHERE nome = '$pg'");
            echo json_encode(['success' => true, 'message' => "$pg esiliato fino al $data_fine"]);

        } elseif ($azione === 'grazia') {
            gdrcd_query("UPDATE personaggio SET
                esilio        = NULL,
                data_esilio   = '2009-07-01',
                autore_esilio = NULL,
                motivo_esilio = NULL
                WHERE nome = '$pg'");
            echo json_encode(['success' => true, 'message' => "$pg graziato"]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Azione non valida (esilia|grazia)']);
        }
        break;

    // -------------------------------------------------------------------------
    // SET_PERMESSI — cambia il livello permessi di un pg (solo admin)
    // -------------------------------------------------------------------------
    case 'set_permessi':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Solo admin']);
            exit;
        }

        $pg       = gdrcd_filter('in', $data['pg'] ?? '');
        $permessi = (int)($data['permessi'] ?? 0);

        if (empty($pg)) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non specificato']);
            exit;
        }

        $allowed = [USER, GUILDMODERATOR, GAMEMASTER, MODERATOR, SUPERUSER];
        if (!in_array($permessi, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Livello permessi non valido']);
            exit;
        }

        // Non si possono cambiare i propri permessi
        if ($pg === $login) {
            echo json_encode(['success' => false, 'message' => 'Non puoi modificare i tuoi permessi']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET permessi = $permessi WHERE nome = '$pg' LIMIT 1");
        echo json_encode(['success' => true, 'pg' => $pg, 'permessi' => $permessi]);
        break;

    // -------------------------------------------------------------------------
    // SET_PRIVILEGI — assegna ruoli staff (admin, master, mod, guida...)
    // -------------------------------------------------------------------------
    case 'set_privilegi':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Solo admin']);
            exit;
        }

        $pg = gdrcd_filter('in', $data['pg'] ?? '');
        if (empty($pg)) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non specificato']);
            exit;
        }

        $campi_ammessi = ['admin', 'moderatore', 'master', 'guida', 'grafico', 'capogilda', 'capomestiere'];
        $updates = [];
        foreach ($campi_ammessi as $campo) {
            if (isset($data[$campo])) {
                $updates[] = "$campo = " . ((int)(bool)$data[$campo]);
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'Nessun campo da aggiornare']);
            exit;
        }

        // Verifica che il record esista in privilegi, altrimenti inserisce
        $exists = gdrcd_query("SELECT COUNT(*) AS n FROM privilegi WHERE nome = '$pg'");
        if ($exists['n'] == 0) {
            gdrcd_query("INSERT INTO privilegi (nome) VALUES ('$pg')");
        }

        gdrcd_query("UPDATE privilegi SET " . implode(', ', $updates) . " WHERE nome = '$pg'");
        echo json_encode(['success' => true, 'pg' => $pg]);
        break;

    // -------------------------------------------------------------------------
    // EDIT_PG — modifica parametri vitali di un personaggio
    // -------------------------------------------------------------------------
    case 'edit_pg':
        $pg = gdrcd_filter('in', $data['pg'] ?? '');
        if (empty($pg)) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non specificato']);
            exit;
        }

        // Campi modificabili e chi può toccarli
        $fields_admin  = ['salute', 'salute_max', 'integrita', 'integrita_max',
                          'esperienza', 'shin', 'notorieta', 'soldi'];
        $fields_master = ['salute', 'integrita', 'notorieta'];
        $fields_mod    = ['note_fato', 'particolari'];

        $allowed_fields = $is_admin ? array_merge($fields_admin, $fields_mod)
                        : ($is_master ? $fields_master
                        : ($is_mod ? $fields_mod : []));

        if (empty($allowed_fields)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        $updates = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $val = in_array($field, ['note_fato', 'particolari'])
                    ? "'" . gdrcd_filter('in', $data[$field]) . "'"
                    : (float)$data[$field];
                $updates[] = "$field = $val";
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'Nessun campo da aggiornare']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET " . implode(', ', $updates) . " WHERE nome = '$pg'");
        echo json_encode(['success' => true, 'pg' => $pg, 'aggiornati' => array_keys($data)]);
        break;

    // -------------------------------------------------------------------------
    // ONLINE — lista utenti online (tutti i ruoli staff)
    // -------------------------------------------------------------------------
    case 'online':
        $result = gdrcd_query("SELECT p.nome, p.cognome, p.ultimo_refresh,
                p.ultima_mappa, p.ultimo_luogo, m.nome AS nome_luogo
            FROM personaggio p
            LEFT JOIN mappa m ON p.ultimo_luogo = m.id
            WHERE p.ora_entrata > p.ora_uscita
            AND DATE_ADD(p.ultimo_refresh, INTERVAL 4 MINUTE) > NOW()
            ORDER BY p.nome ASC", 'result');

        $online = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $online[] = [
                'nome'         => $row['nome'],
                'cognome'      => $row['cognome'],
                'ultimo_refresh' => $row['ultimo_refresh'],
                'luogo'        => (int)$row['ultimo_luogo'],
                'nome_luogo'   => $row['nome_luogo'] ?? 'Mappa',
                'mappa'        => (int)$row['ultima_mappa'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'online' => $online, 'totale' => count($online)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
