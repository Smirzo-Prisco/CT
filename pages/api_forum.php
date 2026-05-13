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
$login = $_SESSION['login'];

// -------------------------------------------------------------------------
// Helper: verifica se l'utente corrente può accedere a una sezione araldo
// -------------------------------------------------------------------------
function can_access_section(array $section): bool {
    $tipo       = (int)$section['tipo'];
    $proprietari = $section['proprietari'];

    // Capo-mestiere che ha confermato il mestiere
    $con_job = false;
    if ($tipo == SOLOMESTIERE) {
        $pg_m = gdrcd_query("SELECT conferma_mestiere FROM clgpersonaggiomestiere WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1");
        $con_job = ($pg_m && $pg_m['conferma_mestiere'] == 1);
    }

    if ($_SESSION['admin'] == 1) return true;

    return match($tipo) {
        ONGAME, INFO, COMUNICAZIONI, PERTUTTI => true,
        SOLORAZZA        => $_SESSION['id_razza'] == $proprietari,
        SOLOGILDA        => strpos($_SESSION['gilda'] ?? '', '*' . $proprietari . '*') !== false,
        SOLOMESTIERE     => ($_SESSION['mestiere'] == $proprietari && $con_job) || $_SESSION['capomestiere'] == 1,
        SOLOMASTERS      => $_SESSION['master'] == 1,
        SOLOMODERATORS   => $_SESSION['moderatore'] == 1,
        SOLOGUIDES       => $_SESSION['guida'] == 1,
        SOLOCAPOGILDA    => $_SESSION['capogilda'] == 1,
        SOLOCAPOMESTIERI => $_SESSION['capomestiere'] == 1,
        SOLOADMIN        => $_SESSION['admin'] == 1,
        default          => false,
    };
}

// -------------------------------------------------------------------------
// Helper: etichetta leggibile per il tipo di sezione
// -------------------------------------------------------------------------
function section_label(int $tipo): string {
    return match($tipo) {
        ONGAME           => 'On Game',
        INFO             => 'Info',
        COMUNICAZIONI    => 'Comunicazioni',
        PERTUTTI         => 'Per tutti',
        SOLORAZZA        => 'Solo razza',
        SOLOGILDA        => 'Solo famiglia',
        SOLOMESTIERE     => 'Solo mestiere',
        SOLOMASTERS      => 'Solo master',
        SOLOMODERATORS   => 'Solo moderatori',
        SOLOGUIDES       => 'Solo guide',
        SOLOCAPOGILDA    => 'Solo capofamiglia',
        SOLOCAPOMESTIERI => 'Solo capomestieri',
        SOLOADMIN        => 'Solo admin',
        default          => 'Sconosciuto',
    };
}

switch ($op) {

    // -------------------------------------------------------------------------
    // SECTIONS — sezioni visibili all'utente (raggruppate per tipo)
    // -------------------------------------------------------------------------
    case 'sections':
        $result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari
            FROM araldo WHERE invisibile = 0 ORDER BY tipo, id_araldo", 'result');

        $sections = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            if (!can_access_section($row)) continue;

            // Conta thread non letti
            $unread = gdrcd_query("SELECT COUNT(*) AS n
                FROM messaggioaraldo ma
                WHERE ma.id_araldo = {$row['id_araldo']}
                AND ma.id_messaggio_padre = -1
                AND ma.id_messaggio NOT IN (
                    SELECT thread_id FROM araldo_letto WHERE nome = '" . gdrcd_filter('in', $login) . "'
                )");

            $label = section_label((int)$row['tipo']);
            if (!isset($sections[$label])) $sections[$label] = [];

            $sections[$label][] = [
                'id'          => (int)$row['id_araldo'],
                'nome'        => $row['nome'],
                'descrizione' => $row['descrizione'],
                'tipo'        => (int)$row['tipo'],
                'tipo_label'  => $label,
                'non_letti'   => (int)$unread['n'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'sections' => $sections]);
        break;

    // -------------------------------------------------------------------------
    // THREADS — thread di una sezione (paginati)
    // -------------------------------------------------------------------------
    case 'threads':
        $araldo_id = (int)($_GET['araldo'] ?? 0);
        $offset    = max(0, (int)($_GET['offset'] ?? 1) - 1);
        $per_page  = 20;

        // Verifica accesso alla sezione
        $section = gdrcd_query("SELECT id_araldo, nome, tipo, proprietari FROM araldo
            WHERE id_araldo = $araldo_id AND invisibile = 0 LIMIT 1");
        if (!$section || !can_access_section($section)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        $total = gdrcd_query("SELECT COUNT(*) AS n FROM messaggioaraldo
            WHERE id_araldo = $araldo_id AND id_messaggio_padre = -1");

        $result = gdrcd_query("SELECT ma.id_messaggio, ma.titolo, ma.autore,
                ma.data_messaggio, ma.data_ultimo_messaggio, ma.chiuso, ma.anonimo,
                (SELECT COUNT(*) FROM messaggioaraldo r WHERE r.id_messaggio_padre = ma.id_messaggio) AS n_risposte,
                (SELECT 1 FROM araldo_letto al WHERE al.thread_id = ma.id_messaggio AND al.nome = '" . gdrcd_filter('in', $login) . "' LIMIT 1) AS letto
            FROM messaggioaraldo ma
            WHERE ma.id_araldo = $araldo_id AND ma.id_messaggio_padre = -1
            ORDER BY ma.data_ultimo_messaggio DESC
            LIMIT $per_page OFFSET " . ($offset * $per_page), 'result');

        $threads = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $threads[] = [
                'id'                   => (int)$row['id_messaggio'],
                'titolo'               => $row['titolo'],
                'autore'               => $row['anonimo'] == 1 ? 'Anonimo' : $row['autore'],
                'data'                 => $row['data_messaggio'],
                'data_ultimo_messaggio' => $row['data_ultimo_messaggio'],
                'chiuso'               => (bool)$row['chiuso'],
                'n_risposte'           => (int)$row['n_risposte'],
                'letto'                => (bool)$row['letto'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode([
            'success'    => true,
            'sezione'    => ['id' => (int)$section['id_araldo'], 'nome' => $section['nome']],
            'threads'    => $threads,
            'totale'     => (int)$total['n'],
            'pagina'     => $offset + 1,
            'per_pagina' => $per_page,
        ]);
        break;

    // -------------------------------------------------------------------------
    // READ — thread completo con risposte
    // -------------------------------------------------------------------------
    case 'read':
        $thread_id = (int)($_GET['thread'] ?? 0);

        // Carica thread padre + risposte in un'unica query
        $result = gdrcd_query("SELECT ma.id_messaggio, ma.id_messaggio_padre,
                ma.titolo, ma.messaggio, ma.autore, ma.data_messaggio,
                ma.chiuso, ma.anonimo, ma.giornalista,
                a.tipo, a.nome AS nome_sezione, a.proprietari, a.id_araldo,
                p.url_img_chat
            FROM messaggioaraldo ma
            LEFT JOIN araldo a ON ma.id_araldo = a.id_araldo
            LEFT JOIN personaggio p ON ma.autore = p.nome
            WHERE (ma.id_messaggio_padre = $thread_id AND ma.id_messaggio_padre != -1)
               OR ma.id_messaggio = $thread_id
            ORDER BY ma.id_messaggio_padre, ma.data_messaggio", 'result');

        $post = gdrcd_query($result, 'fetch');
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Thread non trovato']);
            exit;
        }

        // Verifica accesso
        if (!can_access_section(['tipo' => $post['tipo'], 'proprietari' => $post['proprietari']])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        // Segna come letto
        $already_read = gdrcd_query("SELECT COUNT(*) AS n FROM araldo_letto
            WHERE nome = '" . gdrcd_filter('in', $login) . "' AND thread_id = $thread_id");
        if ($already_read['n'] == 0) {
            gdrcd_query("INSERT INTO araldo_letto (nome, thread_id) VALUES ('" . gdrcd_filter('in', $login) . "', $thread_id)");
        }

        // Costruisci la risposta con padre + risposte
        $messages = [];
        do {
            $msg_text = $post['messaggio'];
            // Rimuove la firma di modifica dal corpo del messaggio
            $pos = strrpos($msg_text, 'Modificato da');
            $edit_note = $pos !== false ? trim(substr($msg_text, $pos)) : null;
            if ($pos !== false) $msg_text = trim(substr($msg_text, 0, $pos));

            $messages[] = [
                'id'         => (int)$post['id_messaggio'],
                'padre'      => (int)$post['id_messaggio_padre'],
                'titolo'     => $post['titolo'],
                'messaggio'  => $msg_text,
                'edit_note'  => $edit_note,
                'autore'     => $post['anonimo'] == 1 ? 'Anonimo' : $post['autore'],
                'avatar'     => $post['url_img_chat'],
                'data'       => $post['data_messaggio'],
                'chiuso'     => (bool)$post['chiuso'],
                'giornalista' => (bool)$post['giornalista'],
            ];
        } while ($post = gdrcd_query($result, 'fetch'));
        gdrcd_query($result, 'free');

        echo json_encode([
            'success'    => true,
            'sezione_id' => (int)$messages[0]['padre'] == -1 ? $post['id_araldo'] : null,
            'thread_id'  => $thread_id,
            'chiuso'     => $messages[0]['chiuso'],
            'messages'   => $messages,
        ]);
        break;

    // -------------------------------------------------------------------------
    // POST — nuovo thread o risposta (POST JSON)
    // -------------------------------------------------------------------------
    case 'post':
        $araldo_id = (int)($data['araldo'] ?? 0);
        $padre     = (int)($data['padre']  ?? -1);
        $titolo    = gdrcd_filter('in', $data['titolo']    ?? '');
        $messaggio = gdrcd_filter('in', $data['messaggio'] ?? '');
        $anonimo   = (int)($data['anonimo']   ?? 0);
        $giornalista = (int)($data['giornalista'] ?? 0);

        if (empty($messaggio)) {
            echo json_encode(['success' => false, 'message' => 'Messaggio vuoto']);
            exit;
        }

        // Verifica sezione
        $section = gdrcd_query("SELECT id_araldo, tipo, proprietari FROM araldo
            WHERE id_araldo = $araldo_id AND invisibile = 0 LIMIT 1");
        if (!$section || !can_access_section($section)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        // Se è una risposta, verifica che il thread non sia chiuso
        if ($padre != -1) {
            $thread = gdrcd_query("SELECT chiuso FROM messaggioaraldo
                WHERE id_messaggio = $padre AND id_messaggio_padre = -1 LIMIT 1");
            if ($thread && $thread['chiuso'] == 1 && $_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
                echo json_encode(['success' => false, 'message' => 'Thread chiuso']);
                exit;
            }
        }

        gdrcd_query("INSERT INTO messaggioaraldo
            (id_messaggio_padre, id_araldo, titolo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio)
            VALUES ($padre, $araldo_id, '$titolo', '$messaggio', '" . gdrcd_filter('in', $login) . "', $anonimo, $giornalista, NOW(), NOW())");

        $new_id = (int)gdrcd_query('', 'last_id');

        // Se è risposta, aggiorna data_ultimo_messaggio del padre e invalida i letti
        if ($padre != -1) {
            gdrcd_query("UPDATE messaggioaraldo SET data_ultimo_messaggio = NOW()
                WHERE id_messaggio = $padre");
            gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $padre AND nome != '" . gdrcd_filter('in', $login) . "'");
        }

        // Punti mestiere per bacheche specifiche
        $check_m = gdrcd_query("SELECT id_mestiere, esperienza_mestiere FROM personaggio WHERE nome = '" . gdrcd_filter('in', $login) . "'");
        if (($check_m['id_mestiere'] == 1 && $araldo_id == 131) ||
            ($check_m['id_mestiere'] == 2 && in_array($araldo_id, [17, 140]))) {
            gdrcd_query("UPDATE personaggio SET esperienza_mestiere = esperienza_mestiere + 0.5, last_date_mestiere = NOW() WHERE nome = '" . gdrcd_filter('in', $login) . "'");
        }

        echo json_encode([
            'success'    => true,
            'id'         => $new_id,
            'thread_id'  => $padre == -1 ? $new_id : $padre,
        ]);
        break;

    // -------------------------------------------------------------------------
    // MARKREAD — segna thread come letto
    // -------------------------------------------------------------------------
    case 'markread':
        $thread_id = (int)($data['thread'] ?? 0);
        $check = gdrcd_query("SELECT COUNT(*) AS n FROM araldo_letto
            WHERE nome = '" . gdrcd_filter('in', $login) . "' AND thread_id = $thread_id");
        if ($check['n'] == 0) {
            gdrcd_query("INSERT INTO araldo_letto (nome, thread_id) VALUES ('" . gdrcd_filter('in', $login) . "', $thread_id)");
        }
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
