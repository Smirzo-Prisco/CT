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

        // Lavoro attivo (ruolo in mestiere tramite clgpersonaggiolavoro)
        $lavoro_q = gdrcd_query("SELECT ruolo_mestiere.nome_ruolo
            FROM clgpersonaggiolavoro
            JOIN ruolo_mestiere ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo
            WHERE clgpersonaggiolavoro.personaggio = '$pg' LIMIT 1");
        $lavoro = $lavoro_q ? $lavoro_q['nome_ruolo'] : null;

        // Dati pubblici
        $profile = [
            'success'       => true,
            // Flag di accesso — usati da Scheda.jsx per mostrare/nascondere sezioni
            'is_own'        => $is_own,
            'is_staff'      => $is_staff,
            'is_admin'      => $is_admin,
            'is_master'     => $is_master,
            // Anagrafica
            'nome'          => $pg_data['nome'],
            'cognome'       => $pg_data['cognome'],
            'sesso'         => $pg_data['sesso'],
            'eta'           => $pg_data['eta'],
            'natoa'         => $pg_data['natoa'],
            'lavoro'        => $lavoro,
            'url_img'       => $pg_data['url_img'],
            'url_img_chat'  => $pg_data['url_img_chat'],
            'razza'         => $pg_data['sesso'] == 'f' ? $pg_data['sing_f'] : $pg_data['sing_m'],
            'nome_gilda'    => $pg_data['nome_gilda'],
            'nome_ruolo'    => $pg_data['nome_ruolo'],
            'immagine_famiglia' => $pg_data['immagine_famiglia'],
            'nome_mestiere' => $pg_data['nome_mestiere'],
            'nome_ruolo_mestiere' => $pg_data['nome_ruolo_mestiere'],
            'immagine_mestiere'   => $pg_data['immagine_mestiere'],
            // Vitali (pubblici)
            'salute'        => (int)$pg_data['salute'],
            'salute_max'    => (int)$pg_data['salute_max'],
            'integrita'     => (int)$pg_data['integrita'],
            'integrita_max' => (int)$pg_data['integrita_max'],
            'notorieta'     => (float)$pg_data['notorieta'],
            // Testi (pubblici)
            'particolari'   => $pg_data['particolari'],
            'note_fato'     => $pg_data['note_fato'],
            'principale'    => $pg_data['principale'],
            'storia'        => $pg_data['storia'],
            'descrizione'   => $pg_data['descrizione'],
            'off'           => $pg_data['off'],
            // Date
            'data_iscrizione' => $pg_data['data_iscrizione'],
            'ora_entrata'   => $pg_data['ora_entrata'],
            'esiliato'      => $esiliato,
            'privilegi'     => $privilegi ?: [],
            // Nomi configurabili delle statistiche — letti da $PARAMETERS
            'config'        => [
                'stat_names' => [
                    'car2'      => $PARAMETERS['names']['stats']['car2']      ?? 'car2',
                    'car4'      => $PARAMETERS['names']['stats']['car4']      ?? 'car4',
                    'car6'      => $PARAMETERS['names']['stats']['car6']      ?? 'car6',
                    'car8'      => $PARAMETERS['names']['stats']['car8']      ?? 'car8',
                    'hitpoints' => $PARAMETERS['names']['stats']['hitpoints'] ?? 'Salute',
                    'integrita' => $PARAMETERS['names']['stats']['integrita'] ?? 'Integrità',
                    'notorieta' => $PARAMETERS['names']['stats']['notorieta'] ?? 'Notorietà',
                    'race_sing' => $PARAMETERS['names']['race']['sing']       ?? 'Spirito',
                ],
            ],
        ];

        // Dati visibili solo al proprio pg o staff
        if ($is_own || $is_staff) {
            $profile['statistiche'] = [
                'car2'   => (int)$pg_data['car2']  + (int)($pg_data['bonus_car2'] ?? 0) + (int)($bo['BO2'] ?? 0),
                'car4'   => (int)$pg_data['car4']  + (int)($pg_data['bonus_car4'] ?? 0) + (int)($bo['BO4'] ?? 0),
                'car6'   => (int)$pg_data['car6']  + (int)($pg_data['bonus_car6'] ?? 0) + (int)($bo['BO6'] ?? 0),
                'car8'   => (int)$pg_data['car8'],
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

        // ob_start cattura qualsiasi output accidentale (warning PHP, ecc.)
        // che altrimenti corromperebbe la risposta JSON.
        ob_start();
        try {
            // iconv//IGNORE rimuove fisicamente i byte non-UTF8.
            // Applicato a TUTTI i campi stringa incluso il tipo (usato come array key).
            $clean = fn(?string $s): string => @iconv('UTF-8', 'UTF-8//IGNORE', $s ?? '') ?: '';

            $skills = [];
            while ($row = gdrcd_query($result, 'fetch')) {
                $tipo = $clean($row['tipo']); // pulizia anche sulla chiave dell'array
                if (!isset($skills[$tipo])) $skills[$tipo] = [];
                $skills[$tipo][] = [
                    'id'          => (int)$row['id_abilita'],
                    'nome'        => $clean($row['nome']),
                    'descrizione' => $clean($row['descrizione']),
                    'tipo'        => $tipo,
                    'sottotipo'   => $clean($row['sottotipo']),
                    'car'         => (int)$row['car'],
                    'costo'       => (int)$row['costo'],
                    'grado'       => (int)$row['grado'],
                    'usi'         => $row['usi'] !== null ? $clean((string)$row['usi']) : null,
                ];
            }
            gdrcd_query($result, 'free');

            ob_end_clean(); // scarta qualsiasi output accidentale catturato

            $json = json_encode(
                ['success' => true, 'skills' => $skills],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
            );
            // Controlla anche stringa vuota (possibile con JSON_PARTIAL_OUTPUT_ON_ERROR)
            echo ($json !== false && $json !== '')
                ? $json
                : json_encode(['success' => false, 'message' => 'json_encode: ' . json_last_error_msg()]);
        } catch (Throwable $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
        }
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

    // -------------------------------------------------------------------------
    // FORM_DATA — tutti i campi editabili per scheda_modifica (own o admin)
    // -------------------------------------------------------------------------
    case 'form_data':
        if (!$is_own && !$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }
        $pg_data = load_pg($pg);
        if (!$pg_data) {
            echo json_encode(['success' => false, 'message' => 'Personaggio non trovato']);
            exit;
        }
        // Nickname TokyoBook (tabella tokyobook)
        $tb_r = gdrcd_query("SELECT nickname FROM tokyobook WHERE personaggio = '$pg' LIMIT 1");
        $nick_tokyo = $tb_r ? $tb_r['nickname'] : null;
        // Nickname Gilda (tabella clgpersonaggioruolo)
        $gg_r = gdrcd_query("SELECT nickname FROM clgpersonaggioruolo WHERE personaggio = '$pg' LIMIT 1");
        $nick_gilda = $gg_r ? $gg_r['nickname'] : null;
        echo json_encode([
            'success'                => true,
            'is_own'                 => $is_own,
            'is_admin'               => $is_admin,
            'is_staff'               => $is_staff,
            'is_master'              => $is_master,
            'nome'                   => $pg_data['nome'],
            'cognome'                => $pg_data['cognome']    ?? '',
            'eta'                    => $pg_data['eta']        ?? '',
            'natoa'                  => $pg_data['natoa']      ?? '',
            'volto'                  => $pg_data['volto']      ?? '',
            'url_img'                => $pg_data['url_img']    ?? '',
            'url_img_chat'           => $pg_data['url_img_chat'] ?? '',
            'principale'             => $pg_data['principale'] ?? '',
            'storia'                 => $pg_data['storia']     ?? '',
            'descrizione'            => $pg_data['descrizione'] ?? '',
            'off'                    => $pg_data['off']        ?? '',
            'blocca_media'           => (bool)($pg_data['blocca_media'] ?? 0),
            'url_media'              => $pg_data['url_media']  ?? '',
            'nickname_tokyo'         => $nick_tokyo,
            'nickname_tokyo_readonly' => ($nick_tokyo !== null) && !$is_admin,
            'nickname_gilda'         => $nick_gilda,
            'nickname_gilda_set'     => ($gg_r !== false),
            'nickname_gilda_readonly' => (!empty($nick_gilda)) && !$is_admin,
            'allow_audio'            => ($PARAMETERS['mode']['allow_audio'] === 'ON'),
        ]);
        break;

    // -------------------------------------------------------------------------
    // SAVE_MODIFICA — salva il form di modifica scheda (POST, own o admin)
    // -------------------------------------------------------------------------
    case 'save_modifica':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
            exit;
        }
        if (!$is_own && !$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dati non validi']);
            exit;
        }
        $f = fn($k) => gdrcd_filter('in', $data[$k] ?? '');
        // Aggiornamento tabella personaggio
        gdrcd_query(sprintf(
            "UPDATE personaggio SET
                cognome='%s', volto='%s', storia='%s', off='%s',
                principale='%s', descrizione='%s', eta=%d, natoa='%s',
                blocca_media=%d, url_img='%s', url_img_chat='%s', url_media='%s'
             WHERE nome='%s'",
            $f('cognome'), $f('volto'), $f('storia'), $f('off'),
            $f('principale'), $f('descrizione'), (int)($data['eta'] ?? 0), $f('natoa'),
            ($data['blocca_media'] ?? false) ? 1 : 0,
            gdrcd_filter('in', gdrcd_filter('fullurl', $data['url_img']      ?? '')),
            gdrcd_filter('in', gdrcd_filter('fullurl', $data['url_img_chat'] ?? '')),
            gdrcd_filter('in', gdrcd_filter('fullurl', $data['url_media']    ?? '')),
            $pg
        ));
        if ($is_own) $_SESSION['blocca_media'] = ($data['blocca_media'] ?? false) ? 1 : 0;
        // Nickname TokyoBook
        $nick_tokyo_new = gdrcd_filter('in', $data['nickname_tokyo'] ?? '');
        if (!empty($nick_tokyo_new)) {
            $existing_tb = gdrcd_query("SELECT nickname FROM tokyobook WHERE personaggio = '$pg' LIMIT 1");
            if ($existing_tb) {
                if ($is_admin || empty($existing_tb['nickname']))
                    gdrcd_query("UPDATE tokyobook SET nickname='$nick_tokyo_new' WHERE personaggio='$pg'");
            } else {
                gdrcd_query("INSERT INTO tokyobook (personaggio, nickname) VALUES ('$pg', '$nick_tokyo_new')");
            }
        }
        // Nickname Gilda (solo aggiornamento, non inserimento)
        $nick_gilda_new = gdrcd_filter('in', $data['nickname_gilda'] ?? '');
        if (!empty($nick_gilda_new)) {
            $existing_gg = gdrcd_query("SELECT nickname FROM clgpersonaggioruolo WHERE personaggio = '$pg' LIMIT 1");
            if ($existing_gg && ($is_admin || empty($existing_gg['nickname'])))
                gdrcd_query("UPDATE clgpersonaggioruolo SET nickname='$nick_gilda_new' WHERE personaggio='$pg'");
        }
        echo json_encode(['success' => true, 'message' => 'Scheda aggiornata con successo']);
        break;

    // -------------------------------------------------------------------------
    // AFFETTI — lista affetti del personaggio per tipo (pubblico)
    // -------------------------------------------------------------------------
    case 'affetti':
        // SELECT * per non dipendere dall'esatta capitalizzazione dei nomi colonna
        // (la tabella usa nomi misti: nomePg, Nome, Cognome — stesso pattern dell'inc.php originale)
        $result = gdrcd_query(
            "SELECT * FROM struttura_affetti WHERE username = '$pg' ORDER BY tipologia, nomePg",
            'result'
        );
        $tipologie = ['legami' => [], 'nemici' => [], 'famiglia' => [], 'conoscenze' => [], 'memories' => []];
        if ($result) {
            while ($row = gdrcd_query($result, 'fetch')) {
                $tipo = $row['tipologia'] ?? '';
                if (array_key_exists($tipo, $tipologie)) {
                    // Accesso ai campi con lo stesso nome usato nell'inc.php originale
                    $tipologie[$tipo][] = [
                        'id'      => (int)($row['id']     ?? 0),
                        'nomePg'  => $row['nomePg']       ?? '',
                        'avatar'  => $row['avatar']        ?? '',
                        'nome'    => $row['Nome']          ?? '',
                        'cognome' => $row['Cognome']       ?? '',
                        'titolo'  => $row['titolo']        ?? '',
                    ];
                }
            }
            gdrcd_query($result, 'free');
        }
        echo json_encode([
            'success'  => true,
            'affetti'  => $tipologie,
            'can_add'  => ($pg === $_SESSION['login']),
        ]);
        break;

    // -------------------------------------------------------------------------
    // TRANSIZIONI — log bonifici/stipendi PX (pubblico)
    // -------------------------------------------------------------------------
    case 'transizioni':
        $num_logs = (int)($PARAMETERS['settings']['view_logs'] ?? 50);
        $result   = gdrcd_query(
            "SELECT descrizione_evento, autore, data_evento, nome_interessato
             FROM log
             WHERE (nome_interessato = '$pg' OR autore = '$pg')
               AND (codice_evento = " . BONIFICO . " OR codice_evento = " . STIPENDIO . ")
             ORDER BY data_evento DESC LIMIT $num_logs",
            'result'
        );
        $transizioni = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $transizioni[] = [
                'descrizione'      => $row['descrizione_evento'],
                'nome_interessato' => $row['nome_interessato'],
                'data'             => $row['data_evento'],
                'autore'           => $row['autore'],
            ];
        }
        gdrcd_query($result, 'free');
        echo json_encode(['success' => true, 'transizioni' => $transizioni]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
