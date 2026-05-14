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

$op = $_GET['op'] ?? '';
$pg = gdrcd_filter('in', $_GET['pg'] ?? '');

if (empty($pg)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parametro pg mancante']);
    exit;
}

$is_own    = ($pg === $_SESSION['login']);
$is_admin  = ($_SESSION['admin'] == 1);
$is_master = ($_SESSION['master'] == 1);
$is_mod    = ($_SESSION['moderatore'] == 1);
$is_staff  = ($is_admin || $is_master || $is_mod);

// Helper: carica il pg base (usato da più endpoint)
function load_pg(string $pg): ?array {
    $r = gdrcd_query("SELECT personaggio.*,
            razza.sing_m, razza.sing_f, razza.id_razza,
            razza.bonus_car0, razza.bonus_car1, razza.bonus_car2,
            razza.bonus_car3, razza.bonus_car4, razza.bonus_car5,
            gilda.nome AS nome_gilda,
            ruolo.nome_ruolo, ruolo.immagine AS immagine_famiglia,
            mestiere.nome AS nome_mestiere,
            ruolo_mestiere.nome_ruolo AS nome_ruolo_mestiere,
            ruolo_mestiere.immagine AS immagine_mestiere
        FROM personaggio
        LEFT JOIN razza          ON personaggio.id_razza = razza.id_razza
        LEFT JOIN gilda          ON personaggio.id_gilda = gilda.id_gilda
        LEFT JOIN ruolo          ON personaggio.id_ruolo_gilda = ruolo.id_ruolo
        LEFT JOIN mestiere       ON personaggio.id_mestiere = mestiere.id_mestiere
        LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo
        WHERE personaggio.nome = '$pg' LIMIT 1");
    return $r ?: null;
}

switch ($op) {

    // -------------------------------------------------------------------------
    // PROFILE — dati completi del personaggio (tutti possono vedere)
    // -------------------------------------------------------------------------
    case 'profile':
        $pg_data = load_pg($pg);
        if (!$pg_data) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non trovato']);
            exit;
        }

        // Esilio: solo staff vede la scheda di un esiliato
        $esiliato = ($pg_data['esilio'] > date('Y-m-d'));
        if ($esiliato && !$is_staff) {
            echo json_encode(['success' => false, 'message' => 'Personaggio esiliato', 'esilio' => true]);
            exit;
        }

        // Bonus da oggetti equipaggiati (posizione > ZAINO)
        $bo = gdrcd_query("SELECT
                SUM(oggetto.bonus_car0) AS BO0, SUM(oggetto.bonus_car1) AS BO1,
                SUM(oggetto.bonus_car2) AS BO2, SUM(oggetto.bonus_car3) AS BO3,
                SUM(oggetto.bonus_car4) AS BO4, SUM(oggetto.bonus_car5) AS BO5
            FROM oggetto
            JOIN clgpersonaggiooggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto
            WHERE clgpersonaggiooggetto.nome = '$pg'
            AND clgpersonaggiooggetto.posizione > " . ZAINO);

        // Cariche staff
        $privilegi = gdrcd_query("SELECT admin, moderatore, master, guida, grafico
            FROM privilegi WHERE nome = '$pg' LIMIT 1");

        // Dati pubblici
        $profile = [
            'success'       => true,
            'nome'          => $pg_data['nome'],
            'cognome'       => $pg_data['cognome'],
            'sesso'         => $pg_data['sesso'],
            'eta'           => $pg_data['eta'],
            'natoa'         => $pg_data['natoa'],
            'url_img'       => $pg_data['url_img'],
            'url_img_chat'  => $pg_data['url_img_chat'],
            'razza'         => $pg_data['sesso'] == 'f' ? $pg_data['sing_f'] : $pg_data['sing_m'],
            'nome_gilda'    => $pg_data['nome_gilda'],
            'nome_ruolo'    => $pg_data['nome_ruolo'],
            'immagine_famiglia' => $pg_data['immagine_famiglia'],
            'nome_mestiere' => $pg_data['nome_mestiere'],
            'nome_ruolo_mestiere' => $pg_data['nome_ruolo_mestiere'],
            'immagine_mestiere'   => $pg_data['immagine_mestiere'],
            'salute'        => (int)$pg_data['salute'],
            'salute_max'    => (int)$pg_data['salute_max'],
            'integrita'     => (int)$pg_data['integrita'],
            'integrita_max' => (int)$pg_data['integrita_max'],
            'notorieta'     => (float)$pg_data['notorieta'],
            'particolari'   => $pg_data['particolari'],
            'note_fato'     => $pg_data['note_fato'],
            'principale'    => $pg_data['principale'],
            'data_iscrizione' => $pg_data['data_iscrizione'],
            'ora_entrata'   => $pg_data['ora_entrata'],
            'esiliato'      => $esiliato,
            'privilegi'     => $privilegi ?: [],
        ];

        // Dati visibili solo al proprio pg o staff
        if ($is_own || $is_staff) {
            $profile['statistiche'] = [
                'car2'  => (int)$pg_data['car2']  + (int)($pg_data['bonus_car2'] ?? 0) + (int)($bo['BO2'] ?? 0),
                'car4'  => (int)$pg_data['car4']  + (int)($pg_data['bonus_car4'] ?? 0) + (int)($bo['BO4'] ?? 0),
                'car6'  => (int)$pg_data['car6']  + (int)($pg_data['bonus_car5'] ?? 0) + (int)($bo['BO4'] ?? 0),
                'car8'  => (int)$pg_data['car8'],
                'totale' => getTotStatsPg($pg),
                'livello' => $pg_data['id_gilda'] > 0 ? getLevelPg(getTotStatsPg($pg)) : 1,
            ];
            $profile['esperienza'] = (float)$pg_data['esperienza'];
            $profile['shin']       = (float)$pg_data['shin'];
            $profile['url_media']  = gdrcd_filter('fullurl', $pg_data['url_media'] ?? '');
        }

        echo json_encode($profile);
        break;

    // -------------------------------------------------------------------------
    // SKILLS — abilità del personaggio (solo proprio pg, master o admin)
    // -------------------------------------------------------------------------
    case 'skills':
        if (!$is_own && !$is_admin && !$is_master) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sezione riservata']);
            exit;
        }

        $result = gdrcd_query("SELECT a.id_abilita, a.nome, a.descrizione, a.tipo,
                a.sottotipo, a.car, a.costo, pa.grado, pa.usi
            FROM abilita a
            LEFT JOIN clgpersonaggioabilita pa ON a.id_abilita = pa.id_abilita
            WHERE pa.nome = '$pg'
            ORDER BY FIELD(a.tipo,
                'Default','Difensiva','Generica base','Generica avanzata',
                'Attacco base','Attacco medio','Attacco avanzato',
                'Mentale base','Mentale media','Mentale avanzata','Mentale di attacco',
                'Potere speciale','Skill temporanea','Talento'
            ), a.nome ASC", 'result');

        $skills = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $tipo = $row['tipo'];
            if (!isset($skills[$tipo])) $skills[$tipo] = [];
            $skills[$tipo][] = [
                'id'          => (int)$row['id_abilita'],
                'nome'        => $row['nome'],
                'descrizione' => $row['descrizione'],
                'tipo'        => $tipo,
                'sottotipo'   => $row['sottotipo'],
                'car'         => (int)$row['car'],
                'costo'       => (int)$row['costo'],
                'grado'       => (int)$row['grado'],
                'usi'         => $row['usi'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'skills' => $skills]);
        break;

    // -------------------------------------------------------------------------
    // EQUIPMENT — oggetti per posizione (zaino / indossato / ecc.)
    // -------------------------------------------------------------------------
    case 'equipment':
        $result = gdrcd_query("SELECT o.id_oggetto, o.nome, o.descrizione,
                o.categoria, o.effetto,
                po.posizione, po.cariche, po.numero, po.usato
            FROM oggetto o
            JOIN clgpersonaggiooggetto po ON o.id_oggetto = po.id_oggetto
            WHERE po.nome = '$pg'
            ORDER BY po.posizione DESC, o.categoria, o.nome ASC", 'result');

        $items = ['indossato' => [], 'zaino' => [], 'altro' => []];
        while ($row = gdrcd_query($result, 'fetch')) {
            $pos = (int)$row['posizione'];
            $bucket = $pos > ZAINO ? 'indossato' : ($pos == ZAINO ? 'zaino' : 'altro');
            $items[$bucket][] = [
                'id'         => (int)$row['id_oggetto'],
                'nome'       => $row['nome'],
                'descrizione' => $row['descrizione'],
                'categoria'  => $row['categoria'],
                'effetto'    => $row['effetto'],
                'posizione'  => $pos,
                'cariche'    => (int)$row['cariche'],
                'numero'     => (int)$row['numero'],
                'usato'      => (bool)$row['usato'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'items' => $items]);
        break;

    // -------------------------------------------------------------------------
    // HISTORY — background testuale (pubblico)
    // -------------------------------------------------------------------------
    case 'history':
        $pg_data = load_pg($pg);
        if (!$pg_data) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non trovato']);
            exit;
        }
        echo json_encode([
            'success'    => true,
            'principale' => $pg_data['principale'],
            'particolari' => $pg_data['particolari'],
            'note_fato'  => $pg_data['note_fato'],
        ]);
        break;

    // -------------------------------------------------------------------------
    // MENU — sezioni visibili per il pg corrente
    // -------------------------------------------------------------------------
    case 'menu':
        echo json_encode([
            'success'     => true,
            'scheda'      => true,
            'skills'      => $is_own || $is_admin || $is_master,
            'equip'       => true,
            'oggetti'     => $is_own || $is_admin,
            'punti'       => $is_own || $is_admin,
            'modifica'    => $is_own || $is_admin,
            'storia'      => true,
            'affetti'     => true,
            'transizioni' => true,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
