<?php
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
            $row = gdrcd_query("SELECT mappa.nome, mappa.descrizione, mappa.descrizione_immagine,
                    mappa.stato, mappa.immagine, mappa.stanza_apparente, mappa.privata,
                    mappa_click.meteo, mappa_click.nome AS nome_zona
                FROM mappa
                LEFT JOIN mappa_click ON mappa_click.id_click = mappa.id_mappa
                WHERE mappa.id = $luogo LIMIT 1");

            echo json_encode([
                'success'              => true,
                'tipo'                 => 'stanza',
                'luogo'                => $luogo,
                'mappa'                => $mappa,
                'nome'                 => $row['nome'] ?? '',
                'nome_zona'            => $row['nome_zona'] ?? '',
                'descrizione'          => $row['descrizione'] ?? '',
                'descrizione_immagine' => $row['descrizione_immagine'] ?? '',
                'stato'                => $row['stato'] ?? '',
                'immagine'             => $row['immagine'] ?? 'ingresso.png',
                'privata'              => (bool)($row['privata'] ?? false),
                'meteo'                => $row['meteo'] ?? '',
                'is_notte'             => $isNotte,
                'anno'                 => date('Y', strtotime('+1053 years')),
            ]);
        } else {
            // Il personaggio è sulla mappa (luogo = -1)
            $zona = gdrcd_query("SELECT nome, immagine, larghezza, altezza
                FROM mappa_click WHERE id_click = $mappa LIMIT 1");
            echo json_encode([
                'success'        => true,
                'tipo'           => 'mappa',
                'luogo'          => -1,
                'mappa'          => $mappa,
                'nome'           => $zona['nome']     ?? $PARAMETERS['names']['maps_location'],
                'immagine_mappa' => $zona['immagine'] ?? 'standard_mappa.png',
                'larghezza'      => (int)($zona['larghezza'] ?? 500),
                'altezza'        => (int)($zona['altezza']   ?? 330),
                'is_notte'       => $isNotte,
                'anno'           => date('Y', strtotime('+1053 years')),
            ]);
        }
        break;

    // -------------------------------------------------------------------------
    // GOTOMAP — lista completa mappe+stanze per il select di navigazione rapida
    // Replica la query del vecchio link_menu.inc.php ($gotomap_list)
    // -------------------------------------------------------------------------
    case 'gotomap':
        $result = gdrcd_query("
            SELECT mc.id_click, mc.nome AS nome_mappa,
                   m.id, m.nome AS nome_stanza, m.chat, m.pagina, m.id_mappa_collegata
            FROM mappa_click mc
            LEFT JOIN mappa m ON m.id_mappa = mc.id_click
            ORDER BY mc.nome, m.nome
        ", 'result');

        $maps = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $mapKey = $row['id_click'] . '|' . $row['nome_mappa'];
            if (!isset($maps[$mapKey])) $maps[$mapKey] = ['id' => (int)$row['id_click'], 'nome' => $row['nome_mappa'], 'stanze' => []];
            if (!empty($row['nome_stanza'])) {
                if ($row['chat'] != 0) {
                    $url = 'main.php?dir=' . $row['id'];
                } elseif ($row['id_mappa_collegata'] != 0) {
                    $url = 'main.php?page=mappaclick&map_id=' . $row['id_mappa_collegata'];
                } else {
                    $url = 'main.php?page=' . $row['pagina'];
                }
                $maps[$mapKey]['stanze'][] = ['id' => (int)$row['id'], 'nome' => $row['nome_stanza'], 'url' => $url];
            }
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'maps' => array_values($maps), 'mappa' => (int)$_SESSION['mappa'], 'luogo' => (int)$_SESSION['luogo']]);
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
    // ZONES — zone/pin della mappa cliccabile overview + stanze di ciascuna,
    // per il pannello di MapClick.jsx. Sostituisce l'ex costante statica
    // ZONES nel frontend: le zone sono righe di mappa_click diverse dalla
    // mappa corrente (map_id) con coordinate assegnate (larghezza/altezza,
    // qui riusate come cx/cy del pin — non sono piu' servite come width/
    // height reali da quando esiste una sola coppia di coordinate condivisa
    // fra le illustrazioni giorno/notte), le stanze sono le righe di mappa
    // con id_mappa = id_click della zona.
    // -------------------------------------------------------------------------
    case 'zones':
        $mainMapId = (int)($_GET['map_id'] ?? $_SESSION['mappa'] ?? 1);
        $is_staff  = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['master'] == 1);

        $result = gdrcd_query("SELECT id_click, nome, larghezza AS cx, altezza AS cy, descrizione
            FROM mappa_click
            WHERE id_click != $mainMapId AND larghezza > 0
            ORDER BY nome ASC", 'result');

        $zones = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $zones[(int)$row['id_click']] = [
                'id'    => (int)$row['id_click'],
                'nome'  => $row['nome'],
                'cx'    => (int)$row['cx'],
                'cy'    => (int)$row['cy'],
                'desc'  => $row['descrizione'] ?? '',
                'rooms' => [],
            ];
        }
        gdrcd_query($result, 'free');

        if (!empty($zones)) {
            $ids = implode(',', array_keys($zones));
            $rresult = gdrcd_query("SELECT id, nome, immagine, chat, pagina, id_mappa, id_mappa_collegata
                FROM mappa WHERE id_mappa IN ($ids) ORDER BY nome ASC", 'result');

            $rooms = [];
            while ($row = gdrcd_query($rresult, 'fetch')) {
                $rooms[] = $row;
            }
            gdrcd_query($rresult, 'free');

            // Conteggio presenti: un'unica query aggregata invece di una
            // COUNT(*) per ogni stanza (era N+1, con N stanze per ogni fetch
            // delle zone — moltiplicato per ogni client con la mappa aperta,
            // dato che il refetch e' guidato da un evento socket globale, vedi
            // sotto). Stessa condizione online di gdrcd_condizione_online(),
            // usata anche da 'presenti'/'presenti_estesi', per contare le
            // stesse persone del Pannello presenti online. Gli invisibili
            // contano solo per lo staff, stesso filtro di 'presenti_totale'.
            $counts = [];
            if (!empty($rooms)) {
                $roomIds           = implode(',', array_map(fn($r) => (int)$r['id'], $rooms));
                $condizione_online = gdrcd_condizione_online();
                $invisible_filter  = $is_staff ? '' : 'AND p.is_invisible = 0';
                $cresult = gdrcd_query("SELECT p.ultimo_luogo, COUNT(*) AS n
                    FROM personaggio p
                    LEFT JOIN bot_status bs ON bs.bot_nome = p.nome
                    WHERE $condizione_online
                      $invisible_filter
                      AND p.ultimo_luogo IN ($roomIds)
                    GROUP BY p.ultimo_luogo", 'result');
                while ($row = gdrcd_query($cresult, 'fetch')) {
                    $counts[(int)$row['ultimo_luogo']] = (int)$row['n'];
                }
                gdrcd_query($cresult, 'free');
            }

            foreach ($rooms as $row) {
                // Stessa logica di risoluzione url di 'gotomap' sopra.
                if ($row['chat'] != 0) {
                    $link = ['type' => 'dir', 'value' => (int)$row['id']];
                } elseif ($row['id_mappa_collegata'] != 0) {
                    $link = ['type' => 'map', 'value' => (int)$row['id_mappa_collegata']];
                } else {
                    $link = ['type' => 'page', 'value' => $row['pagina']];
                }

                $zones[(int)$row['id_mappa']]['rooms'][] = [
                    'id'    => (int)$row['id'],
                    'nome'  => $row['nome'],
                    'img'   => $row['immagine'] ?: 'ingresso.png',
                    'link'  => $link,
                    'count' => $counts[(int)$row['id']] ?? 0,
                ];
            }
        }

        echo json_encode(['success' => true, 'zones' => array_values($zones)]);
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
        // Rilascia il lock di sessione: InfoLocation chiama op=current subito dopo
        // aver ricevuto users:update — senza questo la sessione sarebbe ancora bloccata
        session_write_close();

        // Socket: notifica vecchio luogo, nuovo luogo e tutti gli osservatori globali
        notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:' . $dir);
        notifySocketServer('presenti:update', 'global');

        echo json_encode([
            'success'      => true,
            'luogo'        => $dir,
            'nome_stanza'  => $stanza['nome'],
            'old_luogo'    => $old_luogo,
        ]);
        break;

    // -------------------------------------------------------------------------
    // LEAVE — esce dalla stanza corrente e torna alla mappa (luogo=-1)
    // -------------------------------------------------------------------------
    case 'leave':
        $login_f = gdrcd_filter('in', $_SESSION['login']);
        $old_luogo = (int)($_SESSION['luogo'] ?? -1);

        gdrcd_query("UPDATE personaggio SET ultimo_luogo = -1 WHERE nome = '$login_f'");
        $_SESSION['luogo'] = -1;
        session_write_close();

        if ($old_luogo >= 0) notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:-1');
        notifySocketServer('presenti:update', 'global');

        echo json_encode(['success' => true, 'old_luogo' => $old_luogo]);
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
        session_write_close();

        // Socket: notifica vecchio luogo (stanza lasciata), mappa e osservatori globali
        if ($old_luogo >= 0) notifySocketServer('users:update', 'loc:' . $old_luogo);
        notifySocketServer('users:update', 'loc:-1');
        notifySocketServer('presenti:update', 'global');

        echo json_encode([
            'success'   => true,
            'mappa'     => $map_id,
            'luogo'     => -1,
            'nome_zona' => $zona['nome'],
            'old_luogo' => $old_luogo,
        ]);
        break;

    // -------------------------------------------------------------------------
    // PING — aggiorna solo ultimo_refresh per tenere il pg nella lista presenti.
    // Non restituisce dati: gli aggiornamenti UI arrivano via socket (users:update).
    // -------------------------------------------------------------------------
    case 'ping':
        $login = gdrcd_filter('in', $_SESSION['login']);
        session_write_close();
        gdrcd_query("UPDATE personaggio SET ultimo_refresh = NOW() WHERE nome = '$login'");
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // SET_IDLE — segna il personaggio come assente/presente senza render
    // Aggiorna ultimo_refresh per mantenere il pg visibile nella lista.
    // -------------------------------------------------------------------------
    case 'setIdle':
        $login = gdrcd_filter('in', $_SESSION['login']);
        $idle  = !empty($data['idle']) ? 1 : 0;
        // disponibile=0 → assente, disponibile=1 → presente
        $disp  = $idle ? 0 : 1;
        $luogo = (int)$_SESSION['luogo'];
        session_write_close();
        gdrcd_query("UPDATE personaggio SET disponibile = $disp, ultimo_refresh = NOW() WHERE nome = '$login'");
        notifySocketServer('users:update',   'loc:' . $luogo);
        notifySocketServer('presenti:update', 'global');
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // PRESENTI — utenti nella stessa stanza + heartbeat ultimo_refresh
    // -------------------------------------------------------------------------
    case 'presenti':
        $login   = gdrcd_filter('in', $_SESSION['login']);
        $is_staff = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['master'] == 1);

        $q = "UPDATE personaggio SET ultimo_refresh = NOW(), disponibile = 1";
        if (isset($_GET['disponibile'])) {
            $disp = gdrcd_filter('num', $_GET['disponibile']);
            $q   .= ", disponibile = $disp";
        } elseif (isset($_GET['invisibile']) && $is_staff) {
            $inv = gdrcd_filter('num', $_GET['invisibile']);
            $q  .= ", is_invisible = $inv";
        }
        $q .= " WHERE nome = '$login'";
        gdrcd_query($q);

        $luogo = (int)$_SESSION['luogo'];
        $mappa = (int)$_SESSION['mappa'];

        $condizione_online = gdrcd_condizione_online();
        $result = gdrcd_query(
            "SELECT p.nome, p.cognome, p.permessi, p.sesso, p.id_razza,
                    p.disponibile, p.is_invisible, p.salute,
                    m.stanza_apparente, m.nome AS luogo_nome,
                    ru_fam.immagine AS fam_img
             FROM personaggio p
             LEFT JOIN mappa       m    ON p.ultimo_luogo   = m.id
             LEFT JOIN ruolo       ru_fam ON p.id_ruolo_gilda = ru_fam.id_ruolo
             LEFT JOIN bot_status  bs   ON bs.bot_nome = p.nome
             WHERE $condizione_online
               AND p.ultimo_luogo = $luogo
               AND p.ultima_mappa = $mappa
             ORDER BY p.is_invisible, p.nome",
            'result'
        );

        $users = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            if ($row['is_invisible'] == 0 || $row['nome'] == $_SESSION['login']) {
                $users[] = [
                    'nome'             => $row['nome'],
                    'cognome'          => $row['cognome'],
                    'permessi'         => (int)$row['permessi'],
                    'sesso'            => $row['sesso'],
                    'id_razza'         => (int)$row['id_razza'],
                    'disponibile'      => (int)$row['disponibile'],
                    'is_invisible'     => (int)$row['is_invisible'],
                    'salute'           => (int)$row['salute'],
                    'luogo_nome'       => $row['stanza_apparente'] ?: ($row['luogo_nome'] ?? ''),
                    'gruppo_img'       => $row['fam_img'] ? 'imgs/guilds/' . $row['fam_img'] : '',
                ];
            }
        }
        gdrcd_query($result, 'free');

        $tot = gdrcd_query(
            "SELECT COUNT(*) AS n
             FROM personaggio p
             LEFT JOIN bot_status bs ON bs.bot_nome = p.nome
             WHERE p.ora_entrata > p.ora_uscita
               AND (
                 (p.sesso != 'b' AND DATE_ADD(p.ultimo_refresh, INTERVAL 4 MINUTE) > NOW())
                 OR (p.sesso = 'b' AND COALESCE(bs.paused, 1) = 0)
               )
               AND p.is_invisible = 0"
        );

        echo json_encode([
            'success'      => true,
            'users'        => $users,
            'total_online' => (int)$tot['n'],
            'self'         => $_SESSION['login'],
            'is_staff'     => $is_staff,
        ]);
        break;

    // -------------------------------------------------------------------------
    // PRESENTI_TOTALE — stesso conteggio di 'presenti_estesi' (identica condizione
    // online + esclusioni + visibilità invisibili) ma con una semplice COUNT senza
    // i JOIN pesanti, e senza il side-effect di 'presenti' (che aggiorna
    // ultimo_refresh/disponibile ad ogni chiamata: usata dal badge sull'icona
    // "Presenti" nel menu, che si aggiorna spesso e non deve alterare lo stato
    // di presenza dell'utente che lo visualizza).
    // -------------------------------------------------------------------------
    case 'presenti_totale':
        $login    = $_SESSION['login'];
        $is_staff = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['master'] == 1);

        if ($login === 'Mino' || $login === 'Lii') {
            $exclude = '';
        } elseif ($login === 'Jamal' || $login === 'Alice') {
            $exclude = "AND p.nome NOT IN ('Megan', 'Niklaus')";
        } else {
            $exclude = "AND p.nome != 'Mino'";
        }

        $condizione_online = gdrcd_condizione_online();
        $invisible_filter  = $is_staff ? '' : 'AND p.is_invisible = 0';

        $tot = gdrcd_query(
            "SELECT COUNT(*) AS n
             FROM personaggio p
             LEFT JOIN bot_status bs ON bs.bot_nome = p.nome
             WHERE $condizione_online
               $exclude
               $invisible_filter"
        );

        echo json_encode(['success' => true, 'total_online' => (int)$tot['n']]);
        break;

    // -------------------------------------------------------------------------
    // PRESENTI_ESTESI — lista completa di tutti gli utenti online, raggruppati
    // per mappa e stanza, con avatar, razza, famiglia/inclinazione, mestiere e
    // cariche staff. Usata dalla pagina main.php?page=presenti_estesi.
    //
    // Usa 2 query batch invece di N query per utente:
    //   1. Query principale con tutti i JOIN necessari
    //   2. Query batch per le inclinazioni (evita il problema N+1)
    //
    // Filtro visibilità: replica la logica hardcoded del vecchio presenti_estesi.inc.php
    // -------------------------------------------------------------------------
    case 'presenti_estesi':
        $login    = $_SESSION['login'];
        $is_staff = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['master'] == 1);

        // Filtro visibilità: alcuni personaggi speciali vedono liste diverse
        if ($login === 'Mino' || $login === 'Lii') {
            $exclude = '';                                              // vede tutti
        } elseif ($login === 'Jamal' || $login === 'Alice') {
            $exclude = "AND p.nome NOT IN ('Megan', 'Niklaus')";       // vede quasi tutti
        } else {
            $exclude = "AND p.nome != 'Mino'";                         // esclude Mino per tutti gli altri
        }

        // Query principale: recupera tutti i dati di base in un solo passaggio,
        // unendo mappa, razza, mestiere, famiglia e privilegi staff
        $condizione_online = gdrcd_condizione_online();
        $result = gdrcd_query("
            SELECT
                p.nome, p.cognome, p.sesso,
                p.is_invisible, p.disponibile, p.ultima_mappa, p.ultimo_luogo,
                p.url_img_chat, p.id_gilda, p.id_ruolo_gilda, p.salute,
                r.sing_m, r.sing_f, r.immagine             AS razza_img,
                m.stanza_apparente,    m.nome              AS stanza_nome,
                mc.nome                                    AS mappa_nome,
                rm.immagine            AS mestiere_img,    rm.nome_ruolo AS mestiere_nome,
                rm2.immagine           AS gilda_img,       rm2.nome_ruolo AS gilda_nome,
                ru_fam.immagine        AS fam_img,         ru_fam.nome_ruolo AS fam_nome,
                pr.admin               AS p_admin,         pr.moderatore AS p_mod,
                pr.master              AS p_master,        pr.guida      AS p_guida,
                pr.grafico             AS p_grafico
            FROM personaggio p
            LEFT JOIN mappa          m    ON p.ultimo_luogo     = m.id
            LEFT JOIN mappa_click    mc   ON p.ultima_mappa     = mc.id_click
            LEFT JOIN razza          r    ON p.id_razza         = r.id_razza
            LEFT JOIN ruolo_mestiere rm   ON p.id_ruolo_mestiere= rm.id_ruolo
            LEFT JOIN clgpersonaggioaffiliazione cga ON cga.personaggio = p.nome
            LEFT JOIN ruolo_mestiere rm2  ON cga.id_ruolo        = rm2.id_ruolo
            LEFT JOIN ruolo          ru_fam ON p.id_ruolo_gilda = ru_fam.id_ruolo
            LEFT JOIN privilegi      pr   ON pr.nome            = p.nome
            LEFT JOIN bot_status     bs   ON bs.bot_nome        = p.nome
            WHERE $condizione_online
              $exclude
            ORDER BY p.is_invisible, mc.nome, m.nome, p.nome
        ", 'result');

        $users = [];  // mappa nome → dati utente (per merge inclinazioni)
        $nomi  = [];  // lista nomi per la query batch inclinazioni

        while ($row = gdrcd_query($result, 'fetch')) {
            // Gli invisibili sono visibili solo allo staff
            if ($row['is_invisible'] == 1 && !$is_staff) continue;

            $nome = $row['nome'];
            $users[$nome] = [
                'nome'          => $nome,
                'cognome'       => $row['cognome']       ?? '',
                'sesso'         => $row['sesso'],
                'is_invisible'  => (bool)$row['is_invisible'],
                'disponibile'   => (int)$row['disponibile'],
                'ultima_mappa'  => (int)$row['ultima_mappa'],
                'ultimo_luogo'  => (int)$row['ultimo_luogo'],
                'url_img_chat'  => $row['url_img_chat']  ?? '',
                'salute'        => (int)$row['salute'],
                'razza_img'     => 'imgs/guilds/' . ($row['razza_img'] ?: 'standard_razza.png'),
                'razza_nome'    => $row['sing_' . $row['sesso']] ?? '',
                'stanza'        => $row['stanza_apparente'] ?: ($row['stanza_nome'] ?? ''),
                'mappa'         => $row['mappa_nome']    ?? '',
                // Mestiere vero (personaggio.id_ruolo_mestiere) e gilda giocatore (tabella
                // dedicata clgpersonaggioaffiliazione) sono ora due join indipendenti:
                // un personaggio può avere entrambi insieme, ciascuno nella sua colonna
                'mestiere_img'  => 'imgs/mestieri/' . ($row['mestiere_img'] ?: 'Umani.png'),
                'mestiere_nome' => $row['mestiere_nome'] ?? '',
                'gilda_img'     => $row['gilda_img'] ? 'imgs/mestieri/' . $row['gilda_img'] : '',
                'gilda_nome'    => $row['gilda_nome'] ?? '',
                // Famiglia/inclinazione: inizializzata dalla query principale,
                // sovrascritta dalla query batch inclinazioni se il pg ne ha una
                'gruppo_img'    => $row['fam_img'] ? 'imgs/guilds/' . $row['fam_img'] : '',
                'gruppo_nome'   => $row['fam_nome'] ?? '',
                'staff'         => [
                    'admin'      => !empty($row['p_admin']),
                    'moderatore' => !empty($row['p_mod']),
                    'master'     => !empty($row['p_master']),
                    'guida'      => !empty($row['p_guida']),
                    'grafico'    => !empty($row['p_grafico']),
                ],
            ];
            $nomi[] = gdrcd_filter('in', $nome);
        }
        gdrcd_query($result, 'free');

        // Query batch per le inclinazioni e per lo stato "in role":
        // un solo round-trip per gruppo invece di N query per utente.
        $in_role_set = [];
        if (!empty($nomi)) {
            $nomi_str = "'" . implode("','", $nomi) . "'";

            $r_incl = gdrcd_query("
                SELECT cpi.personaggio, i.immagine, i.nome
                FROM clgpersonaggioinclinazione cpi
                JOIN inclinazione i ON i.id_inclinazione = cpi.id_ruolo
                WHERE cpi.personaggio IN ($nomi_str)
            ", 'result');
            while ($row = gdrcd_query($r_incl, 'fetch')) {
                if (isset($users[$row['personaggio']])) {
                    // Sovrascrive il gruppo con l'inclinazione
                    $users[$row['personaggio']]['gruppo_img']  = 'imgs/inclinazioni/' . $row['immagine'];
                    $users[$row['personaggio']]['gruppo_nome'] = $row['nome'];
                }
            }
            gdrcd_query($r_incl, 'free');

            // Recupera i pg attualmente in una role attiva (non terminata, non freezata)
            $r_role = gdrcd_query("
                SELECT rsp.pg_name
                FROM role_session_players rsp
                INNER JOIN role_sessions rs ON rsp.id_role = rs.id_role
                WHERE rs.end IS NULL AND rs.freezed IS NULL AND rsp.end IS NULL
                  AND rsp.pg_name IN ($nomi_str)
            ", 'result');
            while ($row = gdrcd_query($r_role, 'fetch')) {
                $in_role_set[$row['pg_name']] = true;
            }
            gdrcd_query($r_role, 'free');
        }

        // Aggiunge il flag in_role a ciascun utente
        foreach ($users as $nome => &$u) {
            $u['in_role'] = isset($in_role_set[$nome]);
        }
        unset($u);

        echo json_encode([
            'success' => true,
            'users'   => array_values($users),
            'total'   => count($users),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
