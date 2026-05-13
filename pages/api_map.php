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

$op   = $_GET['op'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($op) {

    // -------------------------------------------------------------------------
    // CURRENT — info sulla posizione corrente del personaggio
    // -------------------------------------------------------------------------
    case 'current':
        $luogo = (int)$_SESSION['luogo'];
        $mappa = (int)$_SESSION['mappa'];
        $isNotte = (date("G") >= 18 || date("G") <= 6);

        if ($luogo >= 0) {
            // Il personaggio è in una stanza specifica
            $row = gdrcd_query("SELECT mappa.nome, mappa.descrizione, mappa.stato,
                    mappa.immagine, mappa.stanza_apparente, mappa.privata,
                    mappa_click.meteo, mappa_click.nome AS nome_zona
                FROM mappa
                LEFT JOIN mappa_click ON mappa_click.id_click = mappa.id_mappa
                WHERE mappa.id = $luogo LIMIT 1");

            echo json_encode([
                'success'        => true,
                'tipo'           => 'stanza',
                'luogo'          => $luogo,
                'mappa'          => $mappa,
                'nome'           => $row['nome'] ?? '',
                'nome_zona'      => $row['nome_zona'] ?? '',
                'descrizione'    => $row['descrizione'] ?? '',
                'stato'          => $row['stato'] ?? '',
                'immagine'       => $row['immagine'] ?? 'ingresso.png',
                'privata'        => (bool)($row['privata'] ?? false),
                'meteo'          => $row['meteo'] ?? '',
                'is_notte'       => $isNotte,
                'anno'           => date('Y', strtotime('+1053 years')),
            ]);
        } else {
            // Il personaggio è sulla mappa (luogo = -1)
            $zona = gdrcd_query("SELECT nome FROM mappa_click WHERE id_click = $mappa LIMIT 1");
            echo json_encode([
                'success'   => true,
                'tipo'      => 'mappa',
                'luogo'     => -1,
                'mappa'     => $mappa,
                'nome'      => $zona['nome'] ?? $PARAMETERS['names']['maps_location'],
                'is_notte'  => $isNotte,
                'anno'      => date('Y', strtotime('+1053 years')),
            ]);
        }
        break;

    // -------------------------------------------------------------------------
    // ROOMS — lista stanze di una mappa (per costruire la UI React)
    // -------------------------------------------------------------------------
    case 'rooms':
        $map_id = (int)($_GET['map_id'] ?? $_SESSION['mappa']);

        $result = gdrcd_query("SELECT mappa.id, mappa.nome, mappa.immagine,
                mappa.descrizione, mappa.stanza_apparente, mappa.privata,
                mappa.id_mappa
            FROM mappa
            WHERE mappa.id_mappa = $map_id
            ORDER BY mappa.nome ASC", 'result');

        $rooms = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            // Conta gli utenti online nella stanza
            $online = gdrcd_query("SELECT COUNT(*) AS n FROM personaggio
                WHERE ultimo_luogo = {$row['id']}
                AND DATE_ADD(ultimo_refresh, INTERVAL 4 MINUTE) > NOW()
                AND is_invisible = 0
                AND ora_entrata > ora_uscita");

            $rooms[] = [
                'id'               => (int)$row['id'],
                'nome'             => $row['nome'],
                'immagine'         => $row['immagine'] ?? 'ingresso.png',
                'descrizione'      => $row['descrizione'] ?? '',
                'stanza_apparente' => $row['stanza_apparente'],
                'privata'          => (bool)$row['privata'],
                'utenti_online'    => (int)$online['n'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'map_id' => $map_id, 'rooms' => $rooms]);
        break;

    // -------------------------------------------------------------------------
    // MAPS — lista mappe disponibili (per la navigazione inter-mappa)
    // -------------------------------------------------------------------------
    case 'maps':
        $result = gdrcd_query("SELECT id_click, nome, posizione FROM mappa_click
            WHERE posizione <> -1 ORDER BY nome ASC", 'result');
        $maps = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $maps[] = [
                'id'        => (int)$row['id_click'],
                'nome'      => $row['nome'],
                'posizione' => $row['posizione'],
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'maps' => $maps]);
        break;

    // -------------------------------------------------------------------------
    // MOVE — spostamento in una stanza (dir=X)
    // -------------------------------------------------------------------------
    case 'move':
        $dir = isset($data['dir']) ? (int)$data['dir'] : -1;

        if ($dir < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Stanza non valida']);
            exit;
        }

        // Verifica che la stanza esista
        $stanza = gdrcd_query("SELECT id, nome, privata, invitati, proprietario, scadenza
            FROM mappa WHERE id = $dir LIMIT 1");
        if (empty($stanza)) {
            echo json_encode(['success' => false, 'message' => 'Stanza non trovata']);
            exit;
        }

        // Controlla accesso stanza privata
        if ($stanza['privata'] == 1) {
            $login = $_SESSION['login'];
            $invitati = $stanza['invitati'] ?? '';
            $proprietario = $stanza['proprietario'] ?? '';
            $is_staff = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1);
            $is_invitato = strpos($invitati, gdrcd_capital_letter($login)) !== false;
            $is_proprietario = $proprietario == gdrcd_capital_letter($login);
            $scadenza_valida = $stanza['scadenza'] > date('Y-m-d H:i:s');

            if (!$is_staff && !($scadenza_valida && ($is_proprietario || $is_invitato))) {
                echo json_encode(['success' => false, 'message' => 'Accesso negato alla stanza privata']);
                exit;
            }
        }

        $login_f = gdrcd_filter('in', $_SESSION['login']);

        // Leggo vecchio luogo dal DB (la sessione potrebbe essere già aggiornata)
        $old = gdrcd_query("SELECT ultimo_luogo FROM personaggio WHERE nome = '$login_f' LIMIT 1");
        $old_luogo = (int)($old['ultimo_luogo'] ?? -1);

        // Aggiorno DB e sessione
        gdrcd_query("UPDATE personaggio SET ultimo_luogo = $dir WHERE nome = '$login_f'");
        $_SESSION['luogo'] = $dir;

        // Socket: notifica vecchio luogo e nuovo luogo
        notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:' . $dir);

        echo json_encode([
            'success'      => true,
            'luogo'        => $dir,
            'nome_stanza'  => $stanza['nome'],
            'old_luogo'    => $old_luogo,
        ]);
        break;

    // -------------------------------------------------------------------------
    // CHANGEMAP — cambio mappa (map_id=X, porta l'utente a luogo=-1)
    // -------------------------------------------------------------------------
    case 'changemap':
        $map_id = isset($data['map_id']) ? (int)$data['map_id'] : 0;

        if ($map_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mappa non valida']);
            exit;
        }

        // Verifica che la mappa esista
        $zona = gdrcd_query("SELECT id_click, nome FROM mappa_click WHERE id_click = $map_id LIMIT 1");
        if (empty($zona)) {
            echo json_encode(['success' => false, 'message' => 'Mappa non trovata']);
            exit;
        }

        $login_f = gdrcd_filter('in', $_SESSION['login']);

        // Leggo vecchio luogo prima di aggiornare
        $old = gdrcd_query("SELECT ultimo_luogo FROM personaggio WHERE nome = '$login_f' LIMIT 1");
        $old_luogo = (int)($old['ultimo_luogo'] ?? -1);

        // Aggiorno DB e sessione
        gdrcd_query("UPDATE personaggio SET ultima_mappa = $map_id, ultimo_luogo = -1 WHERE nome = '$login_f'");
        $_SESSION['mappa'] = $map_id;
        $_SESSION['luogo'] = -1;

        // Socket: notifica vecchio luogo (stanza lasciata) e mappa (loc:-1)
        if ($old_luogo >= 0) notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:-1');

        echo json_encode([
            'success'   => true,
            'mappa'     => $map_id,
            'luogo'     => -1,
            'nome_zona' => $zona['nome'],
            'old_luogo' => $old_luogo,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
