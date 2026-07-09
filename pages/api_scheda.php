<?php
session_start();
header('Content-Type: application/json');

// Impedisce che warning/notice PHP compaiano nella risposta JSON
ini_set('display_errors', '0');
error_reporting(0);

// Shutdown handler: cattura E_ERROR (OOM, timeout, ecc.) che non sono Throwable
// e che kill-ano il processo silenziosamente con display_errors=Off,
// restituendo risposta vuota al client ("Unexpected end of JSON input").
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
        while (ob_get_level()) ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal: ' . $e['message']]);
    }
});

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}
session_write_close();

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

        // Skills con descrizioni HTML grandi possono superare il memory_limit di default:
        // allochiamo abbastanza per il fetch + json_encode dell'intero set.
        ini_set('memory_limit', '256M');

        $result = gdrcd_query("SELECT a.id_abilita, a.nome, a.descrizione, a.tipo,
                a.sottotipo, a.car, a.max_lvl, pa.grado, pa.usi
            FROM abilita a
            LEFT JOIN clgpersonaggioabilita pa ON a.id_abilita = pa.id_abilita
            WHERE pa.nome = '$pg'
            ORDER BY FIELD(a.tipo,
                'Default','Difensiva','Generica base','Generica avanzata',
                'Attacco base','Attacco medio','Attacco avanzato',
                'Mentale base','Mentale media','Mentale avanzata','Mentale di attacco',
                'Potere speciale','Skill temporanea','Talento'
            ), a.nome ASC", 'result');

        // Guard: se la query fallisce gdrcd_query potrebbe chiamare die()
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Errore nella query delle abilità']);
            break;
        }

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
                    'max_lvl'     => (int)$row['max_lvl'],
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
    // EQUIP — oggetti indossati (omino) + zaino (posizione > 0)
    // -------------------------------------------------------------------------
    case 'equip':
        // Sesso del pg per immagine omino (m/f)
        $res_sex = gdrcd_query("SELECT sesso FROM personaggio WHERE nome = '$pg'", 'result');
        $row_sex = gdrcd_query($res_sex, 'fetch');
        gdrcd_query($res_sex, 'free');
        $sesso = $row_sex ? $row_sex['sesso'] : 'm';

        // Oggetti indossati (posizione > 1) per la figura omino, indicizzati per slot
        $res_worn = gdrcd_query("
            SELECT o.id_oggetto, o.nome, o.urlimg, po.posizione
            FROM clgpersonaggiooggetto po
            JOIN oggetto o ON po.id_oggetto = o.id_oggetto
            WHERE po.posizione > 1 AND po.nome = '$pg'
        ", 'result');
        $worn = [];
        while ($row = gdrcd_query($res_worn, 'fetch')) {
            $worn[(int)$row['posizione']] = [
                'id'     => (int)$row['id_oggetto'],
                'nome'   => $row['nome'],
                'urlimg' => $row['urlimg'],
            ];
        }
        gdrcd_query($res_worn, 'free');

        // Tutti gli oggetti visibili nello zaino/indossati (posizione > 0)
        $res_items = gdrcd_query("
            SELECT o.id_oggetto, o.nome AS nome_oggetto, o.descrizione,
                   o.urlimg, o.ubicabile, o.tipo,
                   po.posizione, po.cariche, po.numero,
                   po.commento, po.commento_master, po.commento_shop
            FROM clgpersonaggiooggetto po
            LEFT JOIN oggetto o ON po.id_oggetto = o.id_oggetto
            WHERE po.nome = '$pg' AND po.posizione > 0
            ORDER BY o.nome DESC
        ", 'result');
        $items_equip = [];
        while ($row = gdrcd_query($res_items, 'fetch')) {
            $items_equip[] = [
                'id'              => (int)$row['id_oggetto'],
                'nome'            => $row['nome_oggetto'],
                'descrizione'     => $row['descrizione'],
                'urlimg'          => $row['urlimg'],
                'ubicabile'       => (int)$row['ubicabile'],
                'tipo'            => (int)$row['tipo'],
                'posizione'       => (int)$row['posizione'],
                'cariche'         => (int)$row['cariche'],
                'numero'          => (int)$row['numero'],
                'commento'        => $row['commento']        ?? '',
                'commento_master' => $row['commento_master'] ?? '',
                'commento_shop'   => $row['commento_shop']   ?? '',
            ];
        }
        gdrcd_query($res_items, 'free');

        // Personaggi disponibili per operazione "cedi"
        if ($PARAMETERS['mode']['give_only_if_online'] == 'ON') {
            $chars_q = "SELECT nome FROM personaggio
                WHERE ultimo_luogo = " . (int)($_SESSION['luogo'] ?? -1) . "
                AND ultimo_luogo <> -1 AND nome <> '$pg'
                AND DATE_ADD(ultimo_refresh, INTERVAL 2 MINUTE) > NOW()
                ORDER BY nome";
        } else {
            $chars_q = "SELECT nome FROM personaggio WHERE nome <> '$pg' ORDER BY nome";
        }
        $res_chars = gdrcd_query($chars_q, 'result');
        $chars = [];
        while ($row = gdrcd_query($res_chars, 'fetch')) { $chars[] = $row['nome']; }
        gdrcd_query($res_chars, 'free');

        echo json_encode([
            'success'  => true,
            'sesso'    => $sesso,
            'worn'     => $worn,
            'items'    => $items_equip,
            'chars'    => $chars,
            'is_admin' => $is_admin || $is_mod,
        ]);
        break;

    // -------------------------------------------------------------------------
    // EQUIP_ACTION — operazioni di scrittura sull'equipaggiamento
    // -------------------------------------------------------------------------
    case 'equip_action':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
            exit;
        }
        if (!$is_own && !$is_admin && !$is_mod) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Azione non autorizzata']);
            exit;
        }
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $sub  = $body['sub']       ?? '';
        $id   = (int)($body['id_oggetto'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID oggetto mancante']); break; }

        switch ($sub) {
            case 'abbandona':
                $num = (int)($body['numero'] ?? 1);
                if ($num <= 1) {
                    gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                } else {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                }
                echo json_encode(['success' => true]);
                break;

            case 'cedi':
                $to  = gdrcd_filter('in', $body['give_item'] ?? '');
                $car = (int)($body['cariche'] ?? 0);
                $num = (int)($body['numero']  ?? 1);
                if (!$to) { echo json_encode(['success' => false, 'message' => 'Destinatario mancante']); break; }
                if ($num <= 1) {
                    gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                } else {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                }
                $ex = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = $id AND nome = '$to'", 'result');
                if (gdrcd_query($ex, 'num_rows') > 0) {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero + 1 WHERE id_oggetto = $id AND nome = '$to'");
                } else {
                    gdrcd_query("INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, cariche, numero) VALUES ('$to', $id, $car, 1)");
                }
                gdrcd_query($ex, 'free');
                echo json_encode(['success' => true]);
                break;

            case 'indossa':
                $pos = (int)($body['posizione'] ?? 0);
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = $pos WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'in_zaino':
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 1 WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'riponi':
                // Sposta da zaino/indossato → inventario (posizione=0)
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 0 WHERE id_oggetto = $id AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Operazione sconosciuta']);
        }
        break;

    // -------------------------------------------------------------------------
    // OGGETTI_ALL — vista unificata: tutti gli oggetti del pg (posizione >= 0)
    // Proprietario/admin/shop keeper → tutti i pos. Chiunque altro → solo pos > 0.
    // -------------------------------------------------------------------------
    case 'oggetti_all':
        // Mestiere del visitatore (serve per commento_shop e per access shop)
        $pg_login_oa  = gdrcd_filter('in', $_SESSION['login']);
        $me_row_oa    = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '$pg_login_oa'");
        $id_mes_oa    = $me_row_oa ? (int)$me_row_oa['id_mestiere'] : 0;
        $is_shop_oa   = ($id_mes_oa === 3 || $id_mes_oa === 4);

        // Visibilità: full (pos >= 0) per owner/admin/shop, solo portati (pos > 0) per tutti
        $full_access  = $is_own || $is_admin || $is_shop_oa;
        $where_pos    = $full_access ? 'po.posizione >= 0' : 'po.posizione > 0';

        // Sesso del pg per l'omino
        $row_sex_oa = gdrcd_query("SELECT sesso FROM personaggio WHERE nome = '$pg'");
        $sesso_oa   = $row_sex_oa ? $row_sex_oa['sesso'] : 'm';

        // Oggetti indossati (posizione > 1) indicizzati per slot → usati dall'omino
        $res_worn_oa = gdrcd_query("
            SELECT o.id_oggetto, o.nome, o.urlimg, po.posizione
            FROM clgpersonaggiooggetto po
            JOIN oggetto o ON po.id_oggetto = o.id_oggetto
            WHERE po.posizione > 1 AND po.nome = '$pg'
        ", 'result');
        $worn_oa = [];
        while ($row = gdrcd_query($res_worn_oa, 'fetch')) {
            $worn_oa[(int)$row['posizione']] = [
                'id'     => (int)$row['id_oggetto'],
                'nome'   => $row['nome'],
                'urlimg' => $row['urlimg'],
            ];
        }
        gdrcd_query($res_worn_oa, 'free');

        // Tutti gli oggetti visibili, con nome categoria
        $res_all_oa = gdrcd_query("
            SELECT o.id_oggetto, o.nome AS nome_oggetto, o.descrizione,
                   o.urlimg, o.ubicabile,
                   c.descrizione AS categoria,
                   po.posizione, po.numero, po.cariche,
                   po.commento, po.commento_master, po.commento_shop
            FROM clgpersonaggiooggetto po
            LEFT JOIN oggetto o ON po.id_oggetto = o.id_oggetto
            LEFT JOIN codtipooggetto c ON o.tipo = c.cod_tipo
            WHERE po.nome = '$pg' AND $where_pos
            ORDER BY po.posizione ASC, c.descrizione, o.nome
        ", 'result');
        $all_items_oa = [];
        while ($row = gdrcd_query($res_all_oa, 'fetch')) {
            $all_items_oa[] = [
                'id'              => (int)$row['id_oggetto'],
                'nome'            => $row['nome_oggetto'],
                'descrizione'     => $row['descrizione'],
                'urlimg'          => $row['urlimg'],
                'ubicabile'       => (int)$row['ubicabile'],
                'categoria'       => $row['categoria'] ?? 'Altro',
                'posizione'       => (int)$row['posizione'],
                'numero'          => (int)$row['numero'],
                'cariche'         => (int)$row['cariche'],
                'commento'        => $row['commento']        ?? '',
                'commento_master' => $row['commento_master'] ?? '',
                'commento_shop'   => $row['commento_shop']   ?? '',
            ];
        }
        gdrcd_query($res_all_oa, 'free');

        // Lista personaggi per "cedi" (solo se proprietario o admin)
        $chars_oa = [];
        if ($is_own || $is_admin) {
            if ($PARAMETERS['mode']['give_only_if_online'] == 'ON') {
                $cq_oa = "SELECT nome FROM personaggio
                    WHERE ultimo_luogo = " . (int)($_SESSION['luogo'] ?? -1) . "
                    AND ultimo_luogo <> -1 AND nome <> '$pg'
                    AND DATE_ADD(ultimo_refresh, INTERVAL 2 MINUTE) > NOW()
                    ORDER BY nome";
            } else {
                $cq_oa = "SELECT nome FROM personaggio WHERE nome <> '$pg' ORDER BY nome";
            }
            $res_chars_oa = gdrcd_query($cq_oa, 'result');
            while ($row = gdrcd_query($res_chars_oa, 'fetch')) { $chars_oa[] = $row['nome']; }
            gdrcd_query($res_chars_oa, 'free');
        }

        echo json_encode([
            'success'      => true,
            'sesso'        => $sesso_oa,
            'worn'         => $worn_oa,
            'items'        => $all_items_oa,
            'chars'        => $chars_oa,
            'is_admin'     => $is_admin || $is_mod,
            'id_mestiere'  => $id_mes_oa,
            'full_access'  => $full_access,
        ]);
        break;

    // -------------------------------------------------------------------------
    // OGGETTI_UNIF_ACTION — operazioni di scrittura sulla vista unificata oggetti
    // Gestisce tutte le azioni: posizione, cessione, commenti.
    // -------------------------------------------------------------------------
    case 'oggetti_unif_action':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
            exit;
        }
        $body_ua = json_decode(file_get_contents('php://input'), true) ?? [];
        $sub_ua  = $body_ua['sub']       ?? '';
        $id_ua   = (int)($body_ua['id_oggetto'] ?? 0);
        if (!$id_ua) { echo json_encode(['success' => false, 'message' => 'ID oggetto mancante']); break; }

        switch ($sub_ua) {

            case 'abbandona':
                if (!$is_own) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $num_ua = (int)($body_ua['numero'] ?? 1);
                if ($num_ua <= 1) {
                    gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                } else {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                }
                echo json_encode(['success' => true]);
                break;

            // indossa: sposta a qualsiasi posizione (1=zaino, 2-9=slot, 0 non permesso qui)
            case 'indossa':
                if (!$is_own && !$is_admin && !$is_mod) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $pos_ua = max(1, (int)($body_ua['posizione'] ?? 1));
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = $pos_ua WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            // riponi: riporta all'inventario (posizione=0)
            case 'riponi':
                if (!$is_own && !$is_admin && !$is_mod) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 0 WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'cedi':
                if (!$is_own && !$is_admin && !$is_mod) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $to_ua  = gdrcd_filter('in', $body_ua['give_item'] ?? '');
                $car_ua = (int)($body_ua['cariche'] ?? 0);
                $num_ua = (int)($body_ua['numero']  ?? 1);
                if (!$to_ua) { echo json_encode(['success' => false, 'message' => 'Destinatario mancante']); break; }
                if ($num_ua <= 1) {
                    gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                } else {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                }
                $ex_ua = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = $id_ua AND nome = '$to_ua'", 'result');
                if (gdrcd_query($ex_ua, 'num_rows') > 0) {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero + 1 WHERE id_oggetto = $id_ua AND nome = '$to_ua'");
                } else {
                    gdrcd_query("INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, cariche, numero) VALUES ('$to_ua', $id_ua, $car_ua, 1)");
                }
                gdrcd_query($ex_ua, 'free');
                echo json_encode(['success' => true]);
                break;

            case 'commenta':
                if (!$is_own) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $comm_ua = gdrcd_filter('in', $body_ua['commento'] ?? '');
                gdrcd_query("UPDATE clgpersonaggiooggetto SET commento = '$comm_ua' WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'commenta_master':
                if (!$is_admin && !$is_master) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $comm_uam = gdrcd_filter('in', $body_ua['commento_master'] ?? '');
                gdrcd_query("UPDATE clgpersonaggiooggetto SET commento_master = '$comm_uam' WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'commenta_shop':
                $pg_login_ua = gdrcd_filter('in', $_SESSION['login']);
                $me_row_ua   = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '$pg_login_ua'");
                $id_mes_ua   = $me_row_ua ? (int)$me_row_ua['id_mestiere'] : 0;
                if (!$is_admin && $id_mes_ua != 3 && $id_mes_ua != 4) {
                    echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break;
                }
                $comm_uas = gdrcd_filter('in', $body_ua['commento_shop'] ?? '');
                gdrcd_query("UPDATE clgpersonaggiooggetto SET commento_shop = '$comm_uas' WHERE id_oggetto = $id_ua AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Operazione sconosciuta']);
        }
        break;

    // -------------------------------------------------------------------------
    // OGGETTI — inventario (posizione=0), vista per categoria o lista items
    // -------------------------------------------------------------------------
    case 'oggetti':
        if (!$is_own && !$is_admin) {
            // Permesso speciale per shop keeper (mestiere 3=Magic)
            $pg_login = gdrcd_filter('in', $_SESSION['login']);
            $tm = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '$pg_login'");
            $id_mestiere = $tm ? (int)$tm['id_mestiere'] : 0;
            $what_check  = (int)($_GET['what'] ?? 0);
            if (!($id_mestiere == 3 && $what_check == 8)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
                exit;
            }
        }

        $what = $_GET['what'] ?? '';

        if ($what === '') {
            // Vista indice: categorie con conteggio
            $res = gdrcd_query("
                SELECT COUNT(*) AS numero, c.cod_tipo, c.descrizione
                FROM oggetto o
                JOIN codtipooggetto c ON o.tipo = c.cod_tipo
                JOIN clgpersonaggiooggetto po ON po.id_oggetto = o.id_oggetto
                WHERE po.nome = '$pg' AND po.posizione = 0
                GROUP BY c.cod_tipo
                ORDER BY c.descrizione
            ", 'result');
            $categorie = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $categorie[] = [
                    'cod_tipo'    => $row['cod_tipo'],
                    'descrizione' => $row['descrizione'],
                    'numero'      => (int)$row['numero'],
                ];
            }
            gdrcd_query($res, 'free');

            // Mestiere del visitatore per permessi lato client
            $pg_login2   = gdrcd_filter('in', $_SESSION['login']);
            $me_row      = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '$pg_login2'");
            $id_mestiere = $me_row ? (int)$me_row['id_mestiere'] : 0;

            echo json_encode([
                'success'      => true,
                'categorie'    => $categorie,
                'is_admin'     => $is_admin || $is_mod,
                'is_master'    => $is_master,
                'id_mestiere'  => $id_mestiere,
            ]);
        } else {
            // Vista dettaglio: oggetti di un tipo specifico
            $what_safe = gdrcd_filter('in', $what);
            $res = gdrcd_query("
                SELECT o.id_oggetto, o.nome AS nome_oggetto, o.descrizione,
                       o.urlimg, o.ubicabile, o.tipo,
                       po.numero, po.cariche,
                       po.commento, po.commento_master, po.commento_shop
                FROM clgpersonaggiooggetto po
                LEFT JOIN oggetto o ON po.id_oggetto = o.id_oggetto
                WHERE o.tipo = '$what_safe' AND po.nome = '$pg' AND po.posizione = 0
                ORDER BY o.nome DESC
            ", 'result');
            $items_ogg = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $items_ogg[] = [
                    'id'              => (int)$row['id_oggetto'],
                    'nome'            => $row['nome_oggetto'],
                    'descrizione'     => $row['descrizione'],
                    'urlimg'          => $row['urlimg'],
                    'ubicabile'       => (int)$row['ubicabile'],
                    'numero'          => (int)$row['numero'],
                    'cariche'         => (int)$row['cariche'],
                    'commento'        => $row['commento']        ?? '',
                    'commento_master' => $row['commento_master'] ?? '',
                    'commento_shop'   => $row['commento_shop']   ?? '',
                ];
            }
            gdrcd_query($res, 'free');
            $cat_row  = gdrcd_query("SELECT descrizione FROM codtipooggetto WHERE cod_tipo = '$what_safe'");
            $cat_desc = $cat_row ? $cat_row['descrizione'] : $what;
            echo json_encode(['success' => true, 'items' => $items_ogg, 'categoria' => $cat_desc]);
        }
        break;

    // -------------------------------------------------------------------------
    // OGGETTI_ACTION — operazioni di scrittura sull'inventario
    // -------------------------------------------------------------------------
    case 'oggetti_action':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
            exit;
        }
        $body2 = json_decode(file_get_contents('php://input'), true) ?? [];
        $sub2  = $body2['sub']       ?? '';
        $id2   = (int)($body2['id_oggetto'] ?? 0);
        if (!$id2) { echo json_encode(['success' => false, 'message' => 'ID oggetto mancante']); break; }

        switch ($sub2) {
            case 'togli':
                if (!$is_own) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 0 WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'in_zaino':
                if (!$is_own) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 1 WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'abbandona':
                if (!$is_own) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $num2 = (int)($body2['numero'] ?? 1);
                if ($num2 <= 1) {
                    gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                } else {
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                }
                echo json_encode(['success' => true]);
                break;

            case 'commenta':
                if (!$is_own) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $comm = gdrcd_filter('in', $body2['commento'] ?? '');
                gdrcd_query("UPDATE clgpersonaggiooggetto SET commento = '$comm' WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'commenta_master':
                if (!$is_admin && !$is_master) { echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break; }
                $comm_m = gdrcd_filter('in', $body2['commento_master'] ?? '');
                gdrcd_query("UPDATE clgpersonaggiooggetto SET commento_master = '$comm_m' WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            case 'commenta_shop':
                $pg_login3   = gdrcd_filter('in', $_SESSION['login']);
                $me_row3     = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '$pg_login3'");
                $id_mes3     = $me_row3 ? (int)$me_row3['id_mestiere'] : 0;
                if (!$is_admin && $id_mes3 != 3 && $id_mes3 != 4) {
                    echo json_encode(['success' => false, 'message' => 'Non autorizzato']); break;
                }
                $comm_s = gdrcd_filter('in', $body2['commento_shop'] ?? '');
                gdrcd_query("UPDATE clgpersonaggiooggetto SET commento_shop = '$comm_s' WHERE id_oggetto = $id2 AND nome = '$pg' LIMIT 1");
                echo json_encode(['success' => true]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Operazione sconosciuta']);
        }
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

    // -------------------------------------------------------------------------
    // PUNTI — storico PX, shin o punti mestiere (solo proprio pg o admin)
    // ?tipo=px|shin|mestiere  ?offset=N (numero pagina, default 0)
    // -------------------------------------------------------------------------
    case 'punti':
        if (!$is_own && !$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sezione riservata']);
            exit;
        }
        $tipo     = $_GET['tipo']   ?? 'px';
        $offset   = max(0, (int)($_GET['offset'] ?? 0));
        $per_page = (int)($PARAMETERS['settings']['view_logs'] ?? 20);
        $sql_off  = $offset * $per_page;

        if ($tipo === 'mestiere') {
            // Tabella dedicata PuntiMestiere
            $count_q  = gdrcd_query("SELECT COUNT(*) AS n FROM PuntiMestiere WHERE nome = '$pg'");
            $totale   = (int)($count_q['n'] ?? 0);
            $result   = gdrcd_query(
                "SELECT pm.*, ma.titolo
                 FROM PuntiMestiere pm
                 LEFT JOIN messaggioaraldo ma ON ma.id_messaggio = pm.id_messaggio
                 WHERE pm.nome = '$pg'
                 ORDER BY pm.data_evento DESC LIMIT $sql_off, $per_page",
                'result'
            );
            $field    = 'mestiere';
            $fallback = 'Ruolata di mestiere';
        } elseif ($tipo === 'shin') {
            // Tabella Punti, solo righe con shin > 0
            $count_q  = gdrcd_query("SELECT COUNT(*) AS n FROM Punti WHERE nome = '$pg' AND shin > 0");
            $totale   = (int)($count_q['n'] ?? 0);
            $result   = gdrcd_query(
                "SELECT p.*, ma.titolo
                 FROM Punti p
                 LEFT JOIN messaggioaraldo ma ON ma.id_messaggio = p.id_messaggio
                 WHERE p.nome = '$pg' AND p.shin > 0
                 ORDER BY p.data_evento DESC LIMIT $sql_off, $per_page",
                'result'
            );
            $field    = 'shin';
            $fallback = 'Assegnazione shin';
        } else {
            // px — tabella Punti completa
            $count_q  = gdrcd_query("SELECT COUNT(*) AS n FROM Punti WHERE nome = '$pg'");
            $totale   = (int)($count_q['n'] ?? 0);
            $result   = gdrcd_query(
                "SELECT p.*, ma.titolo
                 FROM Punti p
                 LEFT JOIN messaggioaraldo ma ON ma.id_messaggio = p.id_messaggio
                 WHERE p.nome = '$pg'
                 ORDER BY p.data_evento DESC LIMIT $sql_off, $per_page",
                'result'
            );
            $field    = 'px'; // non usato direttamente, ma px ha due campi
            $fallback = 'Ruolata libera';
        }

        $records = [];
        if ($result) {
            while ($row = gdrcd_query($result, 'fetch')) {
                $titolo = (!empty($row['titolo'])) ? $row['titolo'] : $fallback;
                $entry  = [
                    'data'      => $row['data_evento'] ?? '',
                    'titolo'    => $titolo,
                    'commento'  => $row['commento']    ?? '',
                ];
                if ($tipo === 'mestiere') $entry['mestiere']    = $row['mestiere']    ?? '';
                if ($tipo === 'shin')     $entry['shin']        = $row['shin']        ?? 0;
                if ($tipo === 'px')      { $entry['esperienza'] = $row['esperienza']  ?? 0;
                                           $entry['notorieta']  = $row['notorieta']   ?? 0; }
                $records[] = $entry;
            }
            gdrcd_query($result, 'free');
        }
        echo json_encode([
            'success'  => true,
            'records'  => $records,
            'totale'   => $totale,
            'per_page' => $per_page,
            'offset'   => $offset,
        ]);
        break;

    // PUNTI_ALL — storico unificato PX + Shin + Mestiere, senza paginazione server-side.
    // Il client filtra per tipo e pagina lato React.
    // -------------------------------------------------------------------------
    case 'punti_all':
        if (!$is_own && !$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sezione riservata']);
            exit;
        }
        $all = [];

        // Tabella Punti: tipi/punti includono solo i valori effettivamente > 0
        // (una riga può essere solo 'px', solo 'shin', o entrambi insieme)
        $res = gdrcd_query(
            "SELECT p.*, ma.titolo
             FROM Punti p
             LEFT JOIN messaggioaraldo ma ON ma.id_messaggio = p.id_messaggio
             WHERE p.nome = '$pg'
             ORDER BY p.data_evento DESC",
            'result'
        );
        if ($res) {
            while ($row = gdrcd_query($res, 'fetch')) {
                $esperienza = (float)($row['esperienza'] ?? 0);
                $shin       = (float)($row['shin'] ?? 0);
                $tipi  = [];
                $punti = [];
                if ($esperienza > 0) { $tipi[] = 'px'; $punti['px'] = $esperienza; }
                if ($shin > 0)       { $tipi[] = 'shin'; $punti['shin'] = $shin; }
                $all[] = [
                    'data'     => $row['data_evento'] ?? '',
                    'titolo'   => !empty($row['titolo']) ? $row['titolo'] : 'Ruolata libera',
                    'commento' => $row['commento'] ?? '',
                    'tipi'     => $tipi,
                    'punti'    => $punti,
                ];
            }
            gdrcd_query($res, 'free');
        }

        // Tabella PuntiMestiere
        $res = gdrcd_query(
            "SELECT pm.*, ma.titolo
             FROM PuntiMestiere pm
             LEFT JOIN messaggioaraldo ma ON ma.id_messaggio = pm.id_messaggio
             WHERE pm.nome = '$pg'
             ORDER BY pm.data_evento DESC",
            'result'
        );
        if ($res) {
            while ($row = gdrcd_query($res, 'fetch')) {
                $all[] = [
                    'data'     => $row['data_evento'] ?? '',
                    'titolo'   => !empty($row['titolo']) ? $row['titolo'] : 'Ruolata di mestiere',
                    'commento' => $row['commento'] ?? '',
                    'tipi'     => ['mestiere'],
                    'punti'    => ['mestiere' => (float)($row['mestiere'] ?? 0)],
                ];
            }
            gdrcd_query($res, 'free');
        }

        usort($all, fn($a, $b) => strcmp($b['data'], $a['data']));
        echo json_encode(['success' => true, 'records' => $all]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
