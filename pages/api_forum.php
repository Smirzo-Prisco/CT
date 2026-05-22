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
        ONGAME           => 'Bacheche On-Game',
        INFO             => 'Bacheche Role e Quest',
        COMUNICAZIONI    => 'Bacheche Informazioni',
        PERTUTTI         => 'Bacheche Off-Game',
        SOLORAZZA        => 'Bacheche di Razza',
        SOLOGILDA        => 'Bacheche Via',
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

        echo json_encode([
            'success'    => true,
            'sezione_id' => (int)$messages[0]['padre'] == -1 ? $post['id_araldo'] : null,
            'thread_id'  => $thread_id,
            'chiuso'     => $messages[0]['chiuso'],
            'messages'   => $messages,
            'punti_list' => $punti_list,
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
    // DELETE_THREAD — elimina un thread e tutte le sue risposte (solo admin/mod)
    // -------------------------------------------------------------------------
    case 'delete_thread':
        if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
            http_response_code(403); echo json_encode(['success' => false]); exit;
        }
        $thread_id = (int)($data['thread'] ?? 0);
        gdrcd_query("DELETE FROM messaggioaraldo WHERE id_messaggio = $thread_id OR id_messaggio_padre = $thread_id");
        gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $thread_id");
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // READALL — segna tutti i thread di una sezione come letti
    // -------------------------------------------------------------------------
    case 'readall':
        $araldo_id = (int)($data['araldo'] ?? 0);

        // Verifica accesso alla sezione
        $section = gdrcd_query("SELECT id_araldo, tipo, proprietari FROM araldo
            WHERE id_araldo = $araldo_id AND invisibile = 0 LIMIT 1");
        if (!$section || !can_access_section($section)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        // Inserisce in araldo_letto tutti i thread della sezione non ancora letti
        $login_f = gdrcd_filter('in', $login);
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

        // Verifica accesso alla sezione
        $section = gdrcd_query("SELECT id_araldo, tipo, proprietari, punti FROM araldo
            WHERE id_araldo = $araldo_id AND invisibile = 0 LIMIT 1");
        if (!$section || !can_access_section($section)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accesso negato']);
            exit;
        }

        // Corpo HTML formattato della quest (identico al vecchio insert_quest.inc.php)
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

        $login_f = gdrcd_filter('in', $login);

        // Inserisce il post nella bacheca
        gdrcd_query("INSERT INTO messaggioaraldo
            (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, giornalista, anonimo)
            VALUES ($padre, $araldo_id, '" . gdrcd_filter('in', $titolo) . "', '" . gdrcd_filter('in', $testo_quest) . "',
            '$login_f', NOW(), NOW(), 'no', 'no')");

        $new_id    = (int)gdrcd_query('', 'last_id');
        $thread_id = ($padre == -1) ? $new_id : $padre;

        if ($padre != -1) {
            gdrcd_query("UPDATE messaggioaraldo SET data_ultimo_messaggio = NOW() WHERE id_messaggio = $padre");
            gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $padre AND nome != '$login_f'");
        }

        // Inserisce i dati grezzi nella tabella messaggio_quest
        gdrcd_query("INSERT INTO messaggio_quest
            (id_messaggio, autore, titolo, location, partecipanti, riassunto, conseguenze, note, valutazioni, tipologia)
            VALUES ($thread_id, '$login_f',
            '" . gdrcd_filter('in', $titolo)    . "',
            '" . gdrcd_filter('in', $location)  . "',
            '" . gdrcd_filter('in', $partec)    . "',
            '" . gdrcd_filter('in', $riassunto) . "',
            '" . gdrcd_filter('in', $cons)      . "',
            '" . gdrcd_filter('in', $note)      . "',
            '" . gdrcd_filter('in', $valu)      . "',
            '" . gdrcd_filter('in', $tipologia) . "')");

        // XP al master in base alla tipologia
        $master_xp = match(true) {
            in_array($tipologia, ['Quest Singola', 'Evento']) => 2,
            $tipologia === 'Quest di Gilda'                   => 1,
            $tipologia === 'Assegnazione Esperienza o Notorietà' => 0,
            default                                           => 3,
        };

        if ($master_xp > 0) {
            gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, id_messaggio, commento)
                VALUES ('$login_f', '$master_xp', NOW(), '$thread_id', 'Master della quest')");
            gdrcd_query("UPDATE personaggio SET esperienza = esperienza + $master_xp, esperienza_r = esperienza_r + $master_xp
                WHERE nome = '$login_f'");
        }

        // Punti ai partecipanti (solo se la sezione lo prevede)
        if ((int)$section['punti'] > 0) {
            foreach ($pg_punti as $pg) {
                $pg_nome = trim($pg['nome'] ?? '');
                $pg_exp  = (float)($pg['exp']       ?? 0);
                $pg_shin = (float)($pg['shin']      ?? 0);
                $pg_not  = (int)($pg['notorieta']   ?? 0);
                $pg_mest = (float)($pg['mestiere']  ?? 0);

                if ($pg_nome === '' || ($pg_exp == 0 && $pg_shin == 0 && $pg_not == 0 && $pg_mest == 0)) continue;

                $pg_f = gdrcd_filter('in', $pg_nome);
                gdrcd_query("INSERT INTO Punti (nome, esperienza, notorieta, mestiere, shin, data_evento, id_messaggio, commento)
                    VALUES ('$pg_f', '$pg_exp', '$pg_not', '$pg_mest', '$pg_shin', NOW(), '$thread_id', '')");
                gdrcd_query("UPDATE personaggio SET
                    esperienza = esperienza + '$pg_exp',
                    esperienza_r = esperienza_r + '$pg_exp',
                    notorieta = notorieta + '$pg_not',
                    esperienza_mestiere = esperienza_mestiere + '$pg_mest',
                    shin = shin + '$pg_shin'
                    WHERE nome = '$pg_f'");
            }
        }

        echo json_encode(['success' => true, 'thread_id' => $thread_id]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
