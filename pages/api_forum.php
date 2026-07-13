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
$login = $_SESSION['login'];

// Fire-and-forget: notifica il server Socket.io di un nuovo post nel forum.
// Tutti i client connessi ricevono 'forum:update' e aggiornano la vista corrente.
function notifyForumUpdate(int $araldo_id, int $thread_id): void {
    $payload = json_encode(['event' => 'forum:update', 'room' => 'global', 'data' => ['araldo_id' => $araldo_id, 'thread_id' => $thread_id]]);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $payload, 'timeout' => 1, 'ignore_errors' => true]]);
    $port = $GLOBALS['PARAMETERS']['socket']['port'] ?? 3000;
    @file_get_contents("http://127.0.0.1:$port/notify", false, $ctx);
}

// can_access_section() è stata spostata in includes/custom_functions.inc.php:
// serve anche a createQuestPost(), condivisa con la generazione quest da role_recap.

// -------------------------------------------------------------------------
// Fase C notifiche: avvisa i follower di un thread quando arriva una nuova
// risposta. Ogni follower ha UNA sola riga in araldo_follow (unique key
// nome+tipo_oggetto+riferimento_id), quindi tipo_segui mappa direttamente
// sull'evento di preferenza da controllare — nessuna logica di dedup fra
// piu' ragioni di follow simultanee, lo schema la esclude in partenza.
// Consegna DM immediata via send_sms() (stesso sistema di MessagesInbox.jsx/
// il badge HUD, tabelle sms + conversazioni_individuali) — non la vecchia
// tabella "messaggi", ormai usata solo da alcuni pannelli staff legacy.
// Canale email accodato in "notifiche" per il worker della Fase E.
// -------------------------------------------------------------------------
function notifyThreadFollowers(int $thread_id, string $autore_commento, string $titolo_thread): void {
    $evento_da_tipo = [
        'autore'     => 'commento_post_proprio',
        'manuale'    => 'commento_post_seguito',
        'commentato' => 'commento_post_commentato',
    ];

    $autore_f = gdrcd_filter('in', $autore_commento);
    $follower_res = gdrcd_query("SELECT nome, tipo_segui FROM araldo_follow
        WHERE tipo_oggetto = 'thread' AND riferimento_id = $thread_id AND nome != '$autore_f'", 'result');

    while ($f = gdrcd_query($follower_res, 'fetch')) {
        $evento = $evento_da_tipo[$f['tipo_segui']] ?? null;
        if (!$evento) continue;

        $nome_f = gdrcd_filter('in', $f['nome']);

        // Riga assente in preferenze_notifiche = default (via_dm=1, via_email=0)
        $pref      = gdrcd_query("SELECT via_dm, via_email FROM preferenze_notifiche
            WHERE nome = '$nome_f' AND evento = '$evento'");
        $via_dm    = $pref ? (int)$pref['via_dm']    : 1;
        $via_email = $pref ? (int)$pref['via_email'] : 0;

        if (!$via_dm && !$via_email) continue;

        $testo = gdrcd_filter('in',
            "Nuovo commento di $autore_commento su \"$titolo_thread\": main.php?page=forum&thread=$thread_id");

        if ($via_dm) {
            gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato, data_invio)
                VALUES ('$nome_f', '$evento', $thread_id, 'dm', 'sent', NOW())");
            send_sms('Notifiche', $f['nome'], '', $testo, 0);
        }

        if ($via_email) {
            gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato)
                VALUES ('$nome_f', '$evento', $thread_id, 'email', 'pending')");
        }
    }
    gdrcd_query($follower_res, 'free');
}

// -------------------------------------------------------------------------
// Helper: etichetta leggibile per il tipo di sezione
// -------------------------------------------------------------------------
function section_label(int $tipo): string {
    return match($tipo) {
        ONGAME           => 'Bacheche On-Game',
        INFO             => 'Bacheche Role e Quest',
        COMUNICAZIONI    => 'Bacheche Informazioni',
        PERTUTTI         => 'Bacheche Off-Game',
        SOLORAZZA        => 'Bacheche di Razza',
        SOLOGILDA        => 'Bacheche Razza',
        SOLOMESTIERE     => 'Bacheche Mestieri',
        SOLOMASTERS      => 'Bacheche Master',
        SOLOMODERATORS   => 'Bacheche Moderatori',
        SOLOGUIDES       => 'Bacheche Guide',
        SOLOCAPOGILDA    => 'Bacheche Capofamiglia',
        SOLOCAPOMESTIERI => 'Solo capomestieri',
        SOLOADMIN        => 'Solo admin',
        default          => 'Sconosciuto',
    };
}

// Helper: verifica se l'utente corrente è admin o moderatore
function is_staff(): bool {
    return $_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1;
}

switch ($op) {

    // -------------------------------------------------------------------------
    // SECTIONS — sezioni visibili all'utente (raggruppate per tipo)
    // -------------------------------------------------------------------------
    case 'sections':
        $result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari, punti
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
                'punti'       => (int)$row['punti'],
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
                ma.data_messaggio, ma.data_ultimo_messaggio, ma.chiuso, ma.anonimo, ma.importante,
                (SELECT COUNT(*) FROM messaggioaraldo r WHERE r.id_messaggio_padre = ma.id_messaggio) AS n_risposte,
                (SELECT 1 FROM araldo_letto al WHERE al.thread_id = ma.id_messaggio AND al.nome = '" . gdrcd_filter('in', $login) . "' LIMIT 1) AS letto
            FROM messaggioaraldo ma
            WHERE ma.id_araldo = $araldo_id AND ma.id_messaggio_padre = -1
            ORDER BY ma.importante DESC, ma.data_ultimo_messaggio DESC
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
                'importante'           => (bool)$row['importante'],
                'n_risposte'           => (int)$row['n_risposte'],
                'letto'                => (bool)$row['letto'],
                'can_delete'           => ($row['autore'] == $login || is_staff()),
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

        // Segna come letto — include araldo_id (NOT NULL nella tabella)
        $already_read = gdrcd_query("SELECT COUNT(*) AS n FROM araldo_letto
            WHERE nome = '" . gdrcd_filter('in', $login) . "' AND thread_id = $thread_id");
        if ($already_read['n'] == 0) {
            $araldo_id_r = (int)$post['id_araldo'];
            gdrcd_query("INSERT IGNORE INTO araldo_letto (nome, araldo_id, thread_id)
                VALUES ('" . gdrcd_filter('in', $login) . "', $araldo_id_r, $thread_id)");
        }

        // Salva info sezione dal primo post (prima che il do-while consumi tutti i record)
        $sezione = ['id' => (int)$post['id_araldo'], 'nome' => $post['nome_sezione']];

        // Costruisci la risposta con padre + risposte
        $messages = [];
        do {
            $msg_text = $post['messaggio'];
            // Rimuove la firma di modifica dal corpo del messaggio
            $pos = strrpos($msg_text, 'Modificato da');
            if ($pos === false) $pos = strrpos($msg_text, 'Modificato:');
            $edit_note = $pos !== false ? trim(substr($msg_text, $pos)) : null;
            if ($pos !== false) $msg_text = trim(substr($msg_text, 0, $pos));

            // gdrcd_bbcoder lavora sul testo grezzo (come pages/forum/read.inc.php):
            // non applicare gdrcd_filter('out') qui perché i post possono contenere HTML
            // diretto (<b>, <span>, ecc.) che verrebbe escapato in entità letterali.
            $msg_html = gdrcd_bbcoder($msg_text);

            $messages[] = [
                'id'            => (int)$post['id_messaggio'],
                'padre'         => (int)$post['id_messaggio_padre'],
                'titolo'        => $post['titolo'],
                'messaggio'     => $msg_html,
                'messaggio_raw' => $msg_text,
                'edit_note'     => $edit_note,
                'autore'        => $post['anonimo'] == 1 ? 'Anonimo' : $post['autore'],
                'avatar'        => $post['url_img_chat'],
                'data'          => $post['data_messaggio'],
                'chiuso'        => (bool)$post['chiuso'],
                'giornalista'   => (bool)$post['giornalista'],
                'can_edit'      => ($post['autore'] == $login || $_SESSION['admin'] == 1),
                'can_delete'    => ($post['autore'] == $login || is_staff()),
            ];
        } while ($post = gdrcd_query($result, 'fetch'));
        gdrcd_query($result, 'free');

        // Punti assegnati al thread (solo per master/admin)
        $punti_list = [];
        if ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1) {
            $px_res = gdrcd_query("SELECT nome, esperienza, shin, notorieta, commento
                FROM Punti WHERE id_messaggio = $thread_id ORDER BY nome", 'result');
            while ($px = gdrcd_query($px_res, 'fetch')) {
                $punti_list[] = [
                    'nome'      => $px['nome'],
                    'exp'       => (float)$px['esperienza'],
                    'shin'      => (float)$px['shin'],
                    'notorieta' => (int)$px['notorieta'],
                    'commento'  => $px['commento'],
                ];
            }
            gdrcd_query($px_res, 'free');
        }

        // Stato follow (pulsante Segui/Non seguire lato client)
        $follow_row = gdrcd_query("SELECT 1 FROM araldo_follow
            WHERE nome = '" . gdrcd_filter('in', $login) . "' AND tipo_oggetto = 'thread' AND riferimento_id = $thread_id");

        echo json_encode([
            'success'      => true,
            'sezione'      => $sezione,
            'thread_id'    => $thread_id,
            'chiuso'       => $messages[0]['chiuso'],
            'messages'     => $messages,
            'punti_list'   => $punti_list,
            'is_following' => (bool)$follow_row,
        ]);
        break;

    // -------------------------------------------------------------------------
    // FOLLOW_THREAD — toggle "Segui"/"Non seguire" manuale su un thread
    // -------------------------------------------------------------------------
    case 'follow_thread':
        $thread_id = (int)($data['thread_id'] ?? 0);
        $login_f   = gdrcd_filter('in', $login);

        $already = gdrcd_query("SELECT 1 FROM araldo_follow
            WHERE nome = '$login_f' AND tipo_oggetto = 'thread' AND riferimento_id = $thread_id");

        if ($already) {
            gdrcd_query("DELETE FROM araldo_follow
                WHERE nome = '$login_f' AND tipo_oggetto = 'thread' AND riferimento_id = $thread_id");
            $is_following = false;
        } else {
            // Non declassa mai un follow 'autore' gia' presente (caso limite:
            // due richieste quasi simultanee, l'IGNORE di case 'post' potrebbe
            // non aver ancora scritto la riga quando arriva questo toggle).
            gdrcd_query("INSERT INTO araldo_follow (nome, tipo_oggetto, riferimento_id, tipo_segui)
                VALUES ('$login_f', 'thread', $thread_id, 'manuale')
                ON DUPLICATE KEY UPDATE tipo_segui = IF(tipo_segui = 'autore', 'autore', 'manuale')");
            $is_following = true;
        }

        echo json_encode(['success' => true, 'is_following' => $is_following]);
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


        $resp_thread_id = $padre == -1 ? $new_id : $padre;

        // Auto-follow: chi crea il thread lo segue come 'autore', chi
        // risponde come 'commentato' — IGNORE non tocca un follow gia'
        // esistente (es. l'autore che risponde al proprio thread resta
        // 'autore', non viene declassato a 'commentato').
        $follow_tipo = $padre == -1 ? 'autore' : 'commentato';
        gdrcd_query("INSERT IGNORE INTO araldo_follow (nome, tipo_oggetto, riferimento_id, tipo_segui)
            VALUES ('" . gdrcd_filter('in', $login) . "', 'thread', $resp_thread_id, '$follow_tipo')");

        // Fan-out notifiche: solo per le risposte (un thread appena creato non
        // ha ancora follower da avvisare, a parte l'autore stesso appena
        // aggiunto sopra — mai notificato per la propria azione).
        if ($padre != -1) {
            $thread_row = gdrcd_query("SELECT titolo FROM messaggioaraldo
                WHERE id_messaggio = $padre AND id_messaggio_padre = -1 LIMIT 1");
            notifyThreadFollowers($padre, $login, $thread_row['titolo'] ?? '');
        }

        notifyForumUpdate($araldo_id, $resp_thread_id);

        echo json_encode([
            'success'    => true,
            'id'         => $new_id,
            'thread_id'  => $resp_thread_id,
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
            $mr_araldo = gdrcd_query("SELECT id_araldo FROM messaggioaraldo
                WHERE id_messaggio = $thread_id AND id_messaggio_padre = -1 LIMIT 1");
            $mr_araldo_id = $mr_araldo ? (int)$mr_araldo['id_araldo'] : 0;
            gdrcd_query("INSERT IGNORE INTO araldo_letto (nome, araldo_id, thread_id)
                VALUES ('" . gdrcd_filter('in', $login) . "', $mr_araldo_id, $thread_id)");
        }
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // TOGGLE_IMPORTANT — alterna lo stato "importante" di un thread (solo staff)
    // -------------------------------------------------------------------------
    case 'toggle_important':
        if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1 && $_SESSION['master'] != 1 && $_SESSION['capogilda'] != 1 && $_SESSION['capomestiere'] != 1) {
            http_response_code(403); echo json_encode(['success' => false]); exit;
        }
        $thread_id  = (int)($data['thread'] ?? 0);
        $araldo_id  = (int)($data['araldo'] ?? 0);
        $current    = gdrcd_query("SELECT importante FROM messaggioaraldo WHERE id_messaggio = $thread_id LIMIT 1");
        $new_val    = $current['importante'] == 1 ? 0 : 1;

        // Massimo 4 thread importanti per sezione
        if ($new_val == 1) {
            $check = gdrcd_query("SELECT COUNT(*) AS n FROM messaggioaraldo WHERE importante = 1 AND id_araldo = $araldo_id");
            if ($check['n'] >= 4) { echo json_encode(['success' => false, 'message' => 'Massimo 4 topic importanti per sezione']); exit; }
        }
        gdrcd_query("UPDATE messaggioaraldo SET importante = $new_val WHERE id_messaggio = $thread_id");
        echo json_encode(['success' => true, 'importante' => (bool)$new_val]);
        break;

    // -------------------------------------------------------------------------
    // TOGGLE_CLOSE — apre o chiude un thread (solo staff)
    // -------------------------------------------------------------------------
    case 'toggle_close':
        if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1 && $_SESSION['master'] != 1 && $_SESSION['capogilda'] != 1 && $_SESSION['capomestiere'] != 1) {
            http_response_code(403); echo json_encode(['success' => false]); exit;
        }
        $thread_id = (int)($data['thread'] ?? 0);
        $current   = gdrcd_query("SELECT chiuso FROM messaggioaraldo WHERE id_messaggio = $thread_id LIMIT 1");
        $new_val   = $current['chiuso'] == 1 ? 0 : 1;
        gdrcd_query("UPDATE messaggioaraldo SET chiuso = $new_val WHERE id_messaggio = $thread_id");
        echo json_encode(['success' => true, 'chiuso' => (bool)$new_val]);
        break;

    // -------------------------------------------------------------------------
    // DELETE_THREAD — elimina un thread e tutte le sue risposte (autore o staff)
    // -------------------------------------------------------------------------
    case 'delete_thread':
        $thread_id  = (int)($data['thread'] ?? 0);
        $thread_row = gdrcd_query("SELECT autore FROM messaggioaraldo WHERE id_messaggio = $thread_id AND id_messaggio_padre = -1 LIMIT 1");
        if (!$thread_row) { echo json_encode(['success' => false, 'message' => 'Thread non trovato']); exit; }
        if (!is_staff() && $thread_row['autore'] != $login) {
            http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit;
        }
        gdrcd_query("DELETE FROM messaggioaraldo WHERE id_messaggio = $thread_id OR id_messaggio_padre = $thread_id");
        gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $thread_id");
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // DELETE_POST — elimina un singolo messaggio/risposta (autore o staff)
    // -------------------------------------------------------------------------
    case 'delete_post':
        $id_msg = (int)($data['id_messaggio'] ?? 0);
        $row    = gdrcd_query("SELECT autore, id_messaggio_padre FROM messaggioaraldo WHERE id_messaggio = $id_msg LIMIT 1");
        if (!$row) { echo json_encode(['success' => false, 'message' => 'Messaggio non trovato']); exit; }
        if ($row['id_messaggio_padre'] == -1) {
            echo json_encode(['success' => false, 'message' => 'Per eliminare un thread usa delete_thread']); exit;
        }
        if ($row['autore'] != $login && !is_staff()) {
            http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit;
        }
        gdrcd_query("DELETE FROM messaggioaraldo WHERE id_messaggio = $id_msg LIMIT 1");
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // READALL — segna come letti tutti i thread di una sezione o di tutte
    //           le sezioni accessibili (araldo=0)
    // -------------------------------------------------------------------------
    case 'readall':
        $araldo_id = (int)($data['araldo'] ?? 0);
        $login_f   = gdrcd_filter('in', $login);

        if ($araldo_id === 0) {
            // Modalità globale: raccoglie gli ID di tutte le sezioni accessibili
            $res = gdrcd_query("SELECT id_araldo, tipo, proprietari FROM araldo
                WHERE invisibile = 0", 'result');
            $ids = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                if (can_access_section($row)) $ids[] = (int)$row['id_araldo'];
            }
            gdrcd_query($res, 'free');

            if (!empty($ids)) {
                $in = implode(',', $ids);
                gdrcd_query("INSERT IGNORE INTO araldo_letto (nome, araldo_id, thread_id)
                    SELECT '$login_f', ma.id_araldo, ma.id_messaggio
                    FROM messaggioaraldo ma
                    WHERE ma.id_araldo IN ($in)
                      AND ma.id_messaggio_padre = -1
                      AND ma.id_messaggio NOT IN (
                          SELECT thread_id FROM araldo_letto WHERE nome = '$login_f'
                      )");
            }

            echo json_encode(['success' => true]);
            break;
        }

        // Modalità sezione singola: verifica accesso
        $section = gdrcd_query("SELECT id_araldo, tipo, proprietari FROM araldo
            WHERE id_araldo = $araldo_id AND invisibile = 0 LIMIT 1");
        if (!$section || !can_access_section($section)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        // Inserisce in araldo_letto tutti i thread della sezione non ancora letti
        gdrcd_query("INSERT IGNORE INTO araldo_letto (nome, araldo_id, thread_id)
            SELECT '$login_f', ma.id_araldo, ma.id_messaggio
            FROM messaggioaraldo ma
            WHERE ma.id_araldo = $araldo_id
              AND ma.id_messaggio_padre = -1
              AND ma.id_messaggio NOT IN (
                  SELECT thread_id FROM araldo_letto WHERE nome = '$login_f'
              )");

        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // POST_QUEST — crea un resoconto quest con campi strutturati (solo master/admin)
    // -------------------------------------------------------------------------
    case 'post_quest':
        if ($_SESSION['master'] != 1 && $_SESSION['admin'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
            exit;
        }

        $araldo_id = (int)($data['araldo'] ?? 0);
        $padre     = (int)($data['padre']  ?? -1);
        $titolo    = trim($data['titolo']       ?? '');
        $tipologia = trim($data['tipologia']    ?? '');
        $partec    = trim($data['partecipanti'] ?? '');
        $location  = trim($data['location']     ?? '');
        $riassunto = trim($data['riassunto']    ?? '');
        $cons      = trim($data['conseguenze']  ?? '');
        $note      = trim($data['note']         ?? '');
        $valu      = trim($data['valutazioni']  ?? '');
        $pg_punti  = is_array($data['partecipanti_punti'] ?? null) ? $data['partecipanti_punti'] : [];

        if (empty($titolo)) {
            echo json_encode(['success' => false, 'message' => 'Titolo mancante']);
            exit;
        }

        $thread_id = createQuestPost($araldo_id, $padre, $titolo, $tipologia, $partec, $location, $riassunto, $cons, $note, $valu, $pg_punti, $login);
        if ($thread_id === null) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        echo json_encode(['success' => true, 'thread_id' => $thread_id]);
        break;

    // -------------------------------------------------------------------------
    // GET_QUEST — recupera dati grezzi di una quest per il form di modifica.
    // Fallback: se messaggio_quest non ha il record (post legacy), restituisce
    // il titolo da messaggioaraldo e campi vuoti — così il form si apre comunque.
    // -------------------------------------------------------------------------
    case 'get_quest':
        if ($_SESSION['master'] != 1 && $_SESSION['admin'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
            exit;
        }
        $thread_id = (int)($_GET['thread_id'] ?? 0);
        if ($thread_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID non valido']);
            exit;
        }
        $row = gdrcd_query(
            "SELECT mq.tipologia, mq.location, mq.partecipanti,
                    mq.riassunto, mq.conseguenze, mq.note, mq.valutazioni,
                    ma.titolo AS ma_titolo, ma.messaggio AS ma_messaggio
             FROM messaggioaraldo ma
             LEFT JOIN messaggio_quest mq ON mq.id_messaggio = ma.id_messaggio
             WHERE ma.id_messaggio = $thread_id LIMIT 1"
        );
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Thread non trovato']);
            exit;
        }

        // Punti partecipanti esistenti (commento vuoto = partecipanti, non master)
        $punti_res = gdrcd_query(
            "SELECT nome, esperienza AS exp, shin, notorieta, mestiere
             FROM Punti
             WHERE id_messaggio = $thread_id AND (commento = '' OR commento IS NULL)
             ORDER BY ID",
            'result'
        );
        $existing_punti = [];
        while ($p = gdrcd_query($punti_res, 'fetch')) {
            $existing_punti[] = [
                'nome'      => $p['nome'],
                'exp'       => (float)$p['exp'],
                'shin'      => (float)$p['shin'],
                'notorieta' => (int)$p['notorieta'],
                'mestiere'  => (float)$p['mestiere'],
            ];
        }
        gdrcd_query($punti_res, 'free');

        // Se non esiste un record in messaggio_quest (post legacy), parsa l'HTML del post
        if ($row['tipologia'] === null) {
            $html = $row['ma_messaggio'] ?? '';
            $dec  = fn($s) => html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $parsed_titolo = '';
            if (preg_match('/<b>(.*?)<\/b>/is', $html, $m)) {
                $parsed_titolo = $dec($m[1]);
            }

            $parsed_tipologia = '';
            // Cerca i font color="#9a6353": il primo contiene <b>titolo</b>, il secondo è la tipologia
            if (preg_match_all('/<font[^>]*color="#9a6353"[^>]*>(.*?)<\/font>/is', $html, $matches)) {
                foreach ($matches[1] as $chunk) {
                    if (stripos($chunk, '<b>') === false) { // salta il titolo in grassetto
                        $parsed_tipologia = trim($dec(strip_tags($chunk)));
                        break;
                    }
                }
            }

            $label_map = [
                'Luogo'        => 'location',
                'Partecipanti' => 'partecipanti',
                'Riassunto'    => 'riassunto',
                'Conseguenze'  => 'conseguenze',
                'Note'         => 'note',
                'Valutazioni'  => 'valutazioni',
            ];
            $parsed_fields = [];
            foreach ($label_map as $label => $key) {
                $pat = '/<font[^>]*color="#e8967e"[^>]*>' . preg_quote($label, '/') . '<\/font><br>\s*<font[^>]*color="#8f8f8f"[^>]*>(.*?)<\/font>/is';
                $parsed_fields[$key] = preg_match($pat, $html, $m) ? $dec($m[1]) : '';
            }

            echo json_encode([
                'success' => true,
                'quest'   => [
                    'titolo'             => $parsed_titolo ?: ($dec(strip_tags($row['ma_titolo'] ?? ''))),
                    'tipologia'          => $parsed_tipologia,
                    'location'           => $parsed_fields['location'],
                    'partecipanti'       => $parsed_fields['partecipanti'],
                    'riassunto'          => $parsed_fields['riassunto'],
                    'conseguenze'        => $parsed_fields['conseguenze'],
                    'note'               => $parsed_fields['note'],
                    'valutazioni'        => $parsed_fields['valutazioni'],
                    'partecipanti_punti' => $existing_punti,
                ],
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'quest'   => [
                    'titolo'             => $row['ma_titolo'],
                    'tipologia'          => $row['tipologia']    ?? '',
                    'location'           => $row['location']     ?? '',
                    'partecipanti'       => $row['partecipanti'] ?? '',
                    'riassunto'          => $row['riassunto']    ?? '',
                    'conseguenze'        => $row['conseguenze']  ?? '',
                    'note'               => $row['note']         ?? '',
                    'valutazioni'        => $row['valutazioni']  ?? '',
                    'partecipanti_punti' => $existing_punti,
                ],
            ]);
        }
        break;

    // -------------------------------------------------------------------------
    // EDIT_QUEST — modifica quest: rigenera HTML + aggiorna dati grezzi
    // Non modifica i punti già assegnati (gestiti separatamente da punti_png)
    // -------------------------------------------------------------------------
    case 'edit_quest':
        if ($_SESSION['master'] != 1 && $_SESSION['admin'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
            exit;
        }
        $thread_id = (int)($data['thread_id'] ?? 0);
        $titolo    = trim($data['titolo']       ?? '');
        $tipologia = trim($data['tipologia']    ?? '');
        $partec    = trim($data['partecipanti'] ?? '');
        $location  = trim($data['location']     ?? '');
        $riassunto = trim($data['riassunto']    ?? '');
        $cons      = trim($data['conseguenze']  ?? '');
        $note      = trim($data['note']         ?? '');
        $valu      = trim($data['valutazioni']  ?? '');

        if ($thread_id <= 0 || empty($titolo)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dati non validi']);
            exit;
        }

        // Rigenera il corpo HTML esattamente come in post_quest
        $t_h  = htmlspecialchars($titolo);
        $tp_h = htmlspecialchars($tipologia);
        $l_h  = htmlspecialchars($location);
        $p_h  = htmlspecialchars($partec);
        $r_h  = htmlspecialchars($riassunto);
        $c_h  = htmlspecialchars($cons);
        $n_h  = htmlspecialchars($note);
        $v_h  = htmlspecialchars($valu);

        $testo_quest = "<center>
<font color=\"#9a6353\" style=\"font-size:20px; text-transform: uppercase;\"><b>$t_h</b></font><br>
<font color=\"#9a6353\" style=\"font-size:12px;\">$tp_h</font>
</center><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Luogo</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$l_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Partecipanti</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$p_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Riassunto</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$r_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Conseguenze</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$c_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Note</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$n_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Valutazioni</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$v_h</font>";

        gdrcd_query(
            "UPDATE messaggioaraldo
             SET titolo = '"    . gdrcd_filter('in', $titolo)     . "',
                 messaggio = '" . gdrcd_filter('in', $testo_quest) . "'
             WHERE id_messaggio = $thread_id LIMIT 1"
        );
        // UPSERT: crea il record se mancante (post legacy senza riga in messaggio_quest).
        // titolo escluso dall'UPDATE: messaggioaraldo.titolo è già aggiornato sopra ed è la sorgente autorevole.
        $tp_db = gdrcd_filter('in', $tipologia);
        $l_db  = gdrcd_filter('in', $location);
        $p_db  = gdrcd_filter('in', $partec);
        $r_db  = gdrcd_filter('in', $riassunto);
        $c_db  = gdrcd_filter('in', $cons);
        $n_db  = gdrcd_filter('in', $note);
        $v_db  = gdrcd_filter('in', $valu);
        $t_db  = gdrcd_filter('in', mb_substr($titolo, 0, 200));
        gdrcd_query(
            "INSERT INTO messaggio_quest
                 (id_messaggio, titolo, tipologia, location, partecipanti,
                  riassunto, conseguenze, note, valutazioni)
             VALUES
                 ($thread_id, '$t_db', '$tp_db', '$l_db', '$p_db',
                  '$r_db', '$c_db', '$n_db', '$v_db')
             ON DUPLICATE KEY UPDATE
                 tipologia    = '$tp_db',
                 location     = '$l_db',
                 partecipanti = '$p_db',
                 riassunto    = '$r_db',
                 conseguenze  = '$c_db',
                 note         = '$n_db',
                 valutazioni  = '$v_db'"
        );

        // Aggiorna punti partecipanti se la sezione li prevede
        $pg_punti = is_array($data['partecipanti_punti'] ?? null) ? $data['partecipanti_punti'] : [];
        $section_check = gdrcd_query(
            "SELECT a.punti FROM messaggioaraldo ma
             JOIN araldo a ON a.id_araldo = ma.id_araldo
             WHERE ma.id_messaggio = $thread_id LIMIT 1"
        );
        if ((int)($section_check['punti'] ?? 0) > 0) {
            // Reversa i punti partecipanti esistenti (commento vuoto = partecipanti, non master)
            $old_res = gdrcd_query(
                "SELECT nome, esperienza, shin, notorieta, mestiere FROM Punti
                 WHERE id_messaggio = $thread_id AND (commento = '' OR commento IS NULL)",
                'result'
            );
            while ($old = gdrcd_query($old_res, 'fetch')) {
                $old_f = gdrcd_filter('in', $old['nome']);
                gdrcd_query("UPDATE personaggio SET
                    esperienza          = esperienza          - '{$old['esperienza']}',
                    esperienza_r        = esperienza_r        - '{$old['esperienza']}',
                    notorieta           = notorieta           - '{$old['notorieta']}',
                    esperienza_mestiere = esperienza_mestiere - '{$old['mestiere']}',
                    shin                = shin                - '{$old['shin']}'
                    WHERE nome = '$old_f'");
            }
            gdrcd_query($old_res, 'free');

            gdrcd_query("DELETE FROM Punti WHERE id_messaggio = $thread_id AND (commento = '' OR commento IS NULL)");

            foreach ($pg_punti as $pg) {
                $pg_nome = trim($pg['nome'] ?? '');
                $pg_exp  = (float)($pg['exp']      ?? 0);
                $pg_shin = (float)($pg['shin']     ?? 0);
                $pg_not  = (int)($pg['notorieta']  ?? 0);
                $pg_mest = (float)($pg['mestiere'] ?? 0);
                if ($pg_nome === '' || ($pg_exp == 0 && $pg_shin == 0 && $pg_not == 0 && $pg_mest == 0)) continue;
                $pg_f = gdrcd_filter('in', $pg_nome);
                gdrcd_query("INSERT INTO Punti (nome, esperienza, notorieta, mestiere, shin, data_evento, id_messaggio, commento)
                    VALUES ('$pg_f', '$pg_exp', '$pg_not', '$pg_mest', '$pg_shin', NOW(), '$thread_id', '')");
                gdrcd_query("UPDATE personaggio SET
                    esperienza          = esperienza          + '$pg_exp',
                    esperienza_r        = esperienza_r        + '$pg_exp',
                    notorieta           = notorieta           + '$pg_not',
                    esperienza_mestiere = esperienza_mestiere + '$pg_mest',
                    shin                = shin                + '$pg_shin'
                    WHERE nome = '$pg_f'");
            }
        }

        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // EDIT_POST — modifica testo e titolo di un messaggio (autore o admin)
    // -------------------------------------------------------------------------
    case 'edit_post':
        $id_msg    = (int)($data['id_messaggio'] ?? 0);
        $titolo    = gdrcd_filter('in', trim($data['titolo']    ?? ''));
        $messaggio = gdrcd_filter('in', trim($data['messaggio'] ?? ''));

        if (empty($messaggio)) {
            echo json_encode(['success' => false, 'message' => 'Messaggio vuoto']);
            exit;
        }

        $row = gdrcd_query("SELECT autore, anonimo FROM messaggioaraldo WHERE id_messaggio = $id_msg LIMIT 1");
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Messaggio non trovato']);
            exit;
        }
        if ($row['autore'] != $login && $_SESSION['admin'] != 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        $time = date('d/m/Y H:i');
        $login_f   = gdrcd_filter('in', $login);
        $text_edit = ($row['anonimo'] == 1)
            ? 'Modificato: ' . $time
            : 'Modificato da ' . $login_f . ': ' . $time;

        gdrcd_query("UPDATE messaggioaraldo SET messaggio = '$messaggio\n\n\n\n $text_edit', titolo = '$titolo' WHERE id_messaggio = $id_msg LIMIT 1");

        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // SEGNALA — staff invia un DM globale con link al post segnalato
    // -------------------------------------------------------------------------
    case 'segnala':
        if (!($_SESSION['admin'] ?? 0) && !($_SESSION['moderatore'] ?? 0) && !($_SESSION['master'] ?? 0)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Non autorizzato.']);
            break;
        }

        $thread_id = isset($data['thread_id']) ? (int)$data['thread_id'] : 0;
        $post_id   = isset($data['post_id'])   ? (int)$data['post_id']   : 0;
        $commento  = trim($data['commento'] ?? '');

        if (!$thread_id || !$post_id || $commento === '') {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti.']);
            break;
        }

        // Recupera titolo del thread e autore del post segnalato
        $thread_row = gdrcd_query("SELECT titolo FROM messaggioaraldo WHERE id_messaggio = $thread_id LIMIT 1");
        $post_row   = gdrcd_query("SELECT autore  FROM messaggioaraldo WHERE id_messaggio = $post_id   LIMIT 1");

        if (!$thread_row || !$post_row) {
            echo json_encode(['success' => false, 'message' => 'Post non trovato.']);
            break;
        }

        $titolo   = $thread_row['titolo'];
        $autore   = $post_row['autore'];
        $post_ref = ($post_id !== $thread_id) ? ' (risposta di ' . htmlspecialchars($autore, ENT_QUOTES, 'UTF-8') . ')' : '';
        $url      = 'main.php?page=forum&thread=' . $thread_id;

        // Il testo è salvato come HTML e renderizzato con dangerouslySetInnerHTML in MessagesInbox.
        // I valori utente (login, titolo, commento) vengono escaped; l'URL è derivato da un intero.
        $testo = gdrcd_filter('in',
            '<b>Segnalazione staff da ' . htmlspecialchars($login, ENT_QUOTES, 'UTF-8') . '</b><br><br>' .
            'Thread: <i>' . htmlspecialchars($titolo, ENT_QUOTES, 'UTF-8') . $post_ref . '</i><br>' .
            '<a href="' . $url . '" target="_blank">Clicca qui</a><br><br>' .
            'Note: ' . nl2br(htmlspecialchars($commento, ENT_QUOTES, 'UTF-8'))
        );

        // Invia DM off-game a tutti i giocatori
        $res = gdrcd_query("SELECT nome FROM personaggio", 'result');
        $inviati = 0;
        while ($row = gdrcd_query($res, 'fetch')) {
            send_sms($login, $row['nome'], '', $testo, 0);
            $inviati++;
        }
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'message' => "Segnalazione inviata a $inviati giocatori."]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
