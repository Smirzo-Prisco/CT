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

$op    = $_GET['op'] ?? '';
$data  = json_decode(file_get_contents('php://input'), true) ?? [];
$login = gdrcd_filter('in', $_SESSION['login']);

switch ($op) {

    // -------------------------------------------------------------------------
    // LIST — tutte le conversazioni (individuali + gruppi + globale)
    // -------------------------------------------------------------------------
    case 'list':
        $result = gdrcd_query("
            (SELECT
                c.id_conversazione AS conversazione_id,
                s.mittente_nome, s.destinatario_nome, s.ongame,
                s.ora_spedizione, s.testo,
                'individuale' AS tipo,
                c.lettura,
                NULL AS titolo_gruppo, NULL AS gruppo_id, 0 AS is_globale
            FROM conversazioni_individuali c
            JOIN sms s ON c.id_conversazione = s.id_conversazione
            WHERE c.utente_nome = '$login'
              AND s.mittente_nome != s.destinatario_nome
              AND s.ora_spedizione = (
                  SELECT MAX(s2.ora_spedizione) FROM sms s2
                  WHERE s2.id_conversazione = s.id_conversazione
              ))

            UNION

            (SELECT
                pg.gruppo_id AS conversazione_id,
                s.mittente_nome, NULL AS destinatario_nome, s.ongame,
                s.ora_spedizione, s.testo,
                'gruppo' AS tipo,
                pg.lettura,
                s.titolo_gruppo, pg.gruppo_id, 0 AS is_globale
            FROM partecipazione_gruppo pg
            JOIN sms s ON pg.gruppo_id = s.gruppo_id
            WHERE pg.utente_nome = '$login'
              AND s.is_globale = 0
              AND s.ora_spedizione = (
                  SELECT MAX(s2.ora_spedizione) FROM sms s2
                  WHERE s2.gruppo_id = s.gruppo_id
              ))

            UNION

            (SELECT
                s.gruppo_id AS conversazione_id,
                s.mittente_nome, NULL AS destinatario_nome, s.ongame,
                MAX(s.ora_spedizione) AS ora_spedizione, s.testo,
                'globale' AS tipo,
                NULL AS lettura,
                s.titolo_gruppo, s.gruppo_id, 1 AS is_globale
            FROM sms s
            WHERE s.is_globale = 1
            GROUP BY s.gruppo_id, s.titolo_gruppo, s.mittente_nome, s.ongame, s.testo
            ORDER BY ora_spedizione DESC LIMIT 1)

            ORDER BY ora_spedizione DESC", 'result');

        // Prima passata: raccoglie tutte le righe
        $rows = [];
        while ($row = gdrcd_query($result, 'fetch')) $rows[] = $row;
        gdrcd_query($result, 'free');

        // Batch query avatar: una sola SELECT IN invece di N query separate
        $contact_names = [];
        foreach ($rows as $row) {
            if ($row['tipo'] === 'individuale') {
                $contact_names[] = gdrcd_filter('in',
                    $row['mittente_nome'] === $_SESSION['login']
                        ? $row['destinatario_nome']
                        : $row['mittente_nome']
                );
            }
        }
        $avatars = [];
        if (!empty($contact_names)) {
            $names_in  = implode(',', array_map(fn($n) => "'$n'", array_unique($contact_names)));
            $av_result = gdrcd_query("SELECT nome, url_img_chat FROM personaggio WHERE nome IN ($names_in)", 'result');
            while ($av = gdrcd_query($av_result, 'fetch')) $avatars[$av['nome']] = $av['url_img_chat'];
            gdrcd_query($av_result, 'free');
        }

        // Seconda passata: costruisce l'array conversazioni
        $conversations = [];
        foreach ($rows as $row) {
            $tipo = $row['tipo'];

            if ($tipo === 'individuale') {
                $display_name = ($row['mittente_nome'] === $_SESSION['login'])
                    ? $row['destinatario_nome']
                    : $row['mittente_nome'];
                $unread     = ($row['lettura'] == 0);
                $avatar_url = $avatars[$display_name] ?? '';
            } elseif ($tipo === 'gruppo') {
                $display_name = $row['titolo_gruppo'] ?? 'Gruppo';
                $unread       = ($row['lettura'] == 0);
                $avatar_url   = '';
            } else {
                $display_name = $row['titolo_gruppo'] ?? 'Globale';
                $unread       = false;
                $avatar_url   = '';
            }

            $conversations[] = [
                'conversazione_id' => (int)$row['conversazione_id'],
                'tipo'             => $tipo,
                'gruppo_id'        => $row['gruppo_id'] ? (int)$row['gruppo_id'] : null,
                'is_globale'       => (bool)$row['is_globale'],
                'display_name'     => $display_name,
                'avatar_url'       => $avatar_url,
                'ultimo_mittente'  => $row['mittente_nome'],
                'ultimo_testo'     => mb_substr($row['testo'] ?? '', 0, 80),
                'ongame'           => (bool)$row['ongame'],
                'ora'              => $row['ora_spedizione'],
                'non_letto'        => $unread,
            ];
        }

        // Conta totali non letti
        $unread_ind = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali
            WHERE utente_nome = '$login' AND lettura = 0");
        $unread_grp = gdrcd_query("SELECT COUNT(*) AS n FROM partecipazione_gruppo
            WHERE utente_nome = '$login' AND lettura = 0");

        $perms = [
            'admin'        => ($_SESSION['admin']        ?? 0) == 1,
            'master'       => ($_SESSION['master']       ?? 0) == 1,
            'capogilda'    => ($_SESSION['capogilda']    ?? 0) == 1,
            'capomestiere' => ($_SESSION['capomestiere'] ?? 0) == 1,
        ];

        echo json_encode([
            'success'       => true,
            'conversations' => $conversations,
            'non_letti'     => (int)$unread_ind['n'] + (int)$unread_grp['n'],
            'perms'         => $perms,
        ]);
        break;

    // -------------------------------------------------------------------------
    // READ — messaggi di una conversazione (individuale o gruppo)
    // -------------------------------------------------------------------------
    case 'read':
        $conv_id  = (int)($_GET['conversazione_id'] ?? 0);
        $gruppo_id = (int)($_GET['gruppo_id'] ?? 0);

        // Filtro ongame per conversazioni individuali: il frontend passa il tipo della
        // conversazione aperta per evitare di mostrare messaggi dell'altro tipo in
        // conversazioni miste create prima del fix.
        $ongame_filter = '';
        if ($conv_id > 0 && isset($_GET['ongame'])) {
            $ongame_filter = ' AND s.ongame = ' . (int)$_GET['ongame'];
        }

        if ($conv_id > 0) {
            // Verifica accesso (l'utente deve partecipare alla conversazione)
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali
                WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }

            $result = gdrcd_query("SELECT s.mittente_nome, s.destinatario_nome,
                    s.testo, s.ongame, s.ora_spedizione, s.tipo_messaggio,
                    p.url_img_chat
                FROM sms s
                LEFT JOIN personaggio p ON s.mittente_nome = p.nome
                WHERE s.id_conversazione = $conv_id{$ongame_filter}
                ORDER BY s.ora_spedizione DESC LIMIT 200", 'result');

            // Segna come letto SEMPRE, anche per una chiamata "silent" — chi la
            // chiama sta gia' guardando il thread in tempo reale (vedi
            // fetchThreadSilent in MessagesInbox.jsx), quindi il messaggio va
            // considerato letto anche se e' appena arrivato via socket. "silent"
            // continua a saltare solo il self-emit di dm:update, che altrimenti
            // farebbe un loop infinito (dm:update → re-read → dm:update → ...).
            gdrcd_query("UPDATE conversazioni_individuali SET lettura = 1
                WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
            if (empty($_GET['silent'])) {
                notifySocketServer('dm:update', 'dm:' . $_SESSION['login']);
            }

        } elseif ($gruppo_id > 0) {
            // Verifica accesso al gruppo
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM partecipazione_gruppo
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }

            $result = gdrcd_query("SELECT s.mittente_nome, NULL AS destinatario_nome,
                    s.testo, s.ongame, s.ora_spedizione, s.tipo_messaggio,
                    p.url_img_chat
                FROM sms s
                LEFT JOIN personaggio p ON s.mittente_nome = p.nome
                WHERE s.gruppo_id = $gruppo_id
                ORDER BY s.ora_spedizione DESC LIMIT 200", 'result');

            // Segna come letto sempre (vedi commento nel ramo individuale sopra):
            // "silent" salta solo il self-emit di dm:update, non l'update DB.
            gdrcd_query("UPDATE partecipazione_gruppo SET lettura = 1
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
            if (empty($_GET['silent'])) {
                notifySocketServer('dm:update', 'dm:' . $_SESSION['login']);
            }

        } else {
            echo json_encode(['success' => false, 'message' => 'Specifica conversazione_id o gruppo_id']);
            exit;
        }

        $messages = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $messages[] = [
                'mittente'     => $row['mittente_nome'],
                'destinatario' => $row['destinatario_nome'],
                'testo'        => $row['testo'],
                'ongame'       => (bool)$row['ongame'],
                'ora'          => $row['ora_spedizione'],
                'avatar'       => $row['url_img_chat'],
                'tipo'         => $row['tipo_messaggio'],
            ];
        }
        gdrcd_query($result, 'free');

        // Riporta in ordine cronologico (la query usa DESC per il LIMIT efficiente)
        $messages = array_reverse($messages);

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    // -------------------------------------------------------------------------
    // SEND — invia un messaggio individuale o di gruppo
    // -------------------------------------------------------------------------
    case 'send':
        $destinatario = gdrcd_filter('in', $data['destinatario'] ?? '');
        $gruppo_id    = isset($data['gruppo_id']) ? (int)$data['gruppo_id'] : 0;
        $messaggio    = gdrcd_filter('in', $data['messaggio'] ?? '');
        $ongame       = (int)($data['ongame'] ?? 0);
        $is_globale   = (int)($data['is_globale'] ?? 0);
        $conv_id      = isset($data['conversazione_id']) ? (int)$data['conversazione_id'] : 0;

        if (empty($messaggio)) {
            echo json_encode(['success' => false, 'message' => 'Messaggio vuoto']);
            exit;
        }

        if ($gruppo_id > 0) {
            // Messaggio di gruppo — verifica partecipazione
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM partecipazione_gruppo
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Non sei membro del gruppo']);
                exit;
            }

            gdrcd_query("INSERT INTO sms
                (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame, is_globale, ora_spedizione)
                VALUES ('$login', '$messaggio', $gruppo_id, $gruppo_id, 'gruppo', $ongame, $is_globale, NOW())");

            // Aggiorna stato lettura per gli altri
            gdrcd_query("UPDATE partecipazione_gruppo
                SET lettura = IF(utente_nome = '$login', 1, 0)
                WHERE gruppo_id = $gruppo_id");

            // Notifica i membri del gruppo
            $members = gdrcd_query("SELECT utente_nome FROM partecipazione_gruppo
                WHERE gruppo_id = $gruppo_id AND utente_nome != '$login'", 'result');
            while ($m = gdrcd_query($members, 'fetch')) {
                notifySocketServer('dm:update', 'dm:' . $m['utente_nome']);
            }
            gdrcd_query($members, 'free');

            echo json_encode(['success' => true, 'tipo' => 'gruppo', 'gruppo_id' => $gruppo_id]);

        } elseif (!empty($destinatario)) {
            // Messaggio individuale — usa send_sms() di custom_functions
            // notifyNewDm=true: DM reale fra due giocatori (Fase E, evento nuovo_dm)
            send_sms($_SESSION['login'], $destinatario, '', $messaggio, $ongame, true);
            // send_sms() già chiama notifySocketServer internamente

            // Recupera id conversazione appena creata/aggiornata
            $conv = gdrcd_query("SELECT id_conversazione FROM sms
                WHERE mittente_nome = '$login' AND destinatario_nome = '" . gdrcd_filter('in', $destinatario) . "'
                ORDER BY ora_spedizione DESC LIMIT 1");

            echo json_encode([
                'success'          => true,
                'tipo'             => 'individuale',
                'conversazione_id' => $conv ? (int)$conv['id_conversazione'] : null,
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Specifica destinatario o gruppo_id']);
        }
        break;

    // -------------------------------------------------------------------------
    // ARCHIVE — messaggi inviati a sé stessi (archivio personale)
    // -------------------------------------------------------------------------
    case 'archive':
        $result = gdrcd_query("SELECT mittente_nome, testo, ongame, ora_spedizione
            FROM sms
            WHERE mittente_nome = '$login' AND destinatario_nome = '$login'
            ORDER BY ora_spedizione DESC", 'result');

        $messages = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $messages[] = [
                'testo'  => $row['testo'],
                'ongame' => (bool)$row['ongame'],
                'ora'    => $row['ora_spedizione'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    // -------------------------------------------------------------------------
    // MARKREAD — segna conversazione come letta
    // -------------------------------------------------------------------------
    case 'markread':
        $conv_id   = (int)($data['conversazione_id'] ?? 0);
        $gruppo_id = (int)($data['gruppo_id'] ?? 0);

        if ($conv_id > 0) {
            gdrcd_query("UPDATE conversazioni_individuali SET lettura = 1
                WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
        } elseif ($gruppo_id > 0) {
            gdrcd_query("UPDATE partecipazione_gruppo SET lettura = 1
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
        }

        notifySocketServer('dm:update', 'dm:' . $_SESSION['login']);
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // SENDMASS — invia a un gruppo di destinatari in base al tipo
    // -------------------------------------------------------------------------
    case 'sendMass':
        $tipo      = $data['tipo']     ?? '';
        $messaggio = gdrcd_filter('in', $data['messaggio'] ?? '');
        $ongame    = (int)($data['ongame'] ?? 0);

        $is_admin        = ($_SESSION['admin']        ?? 0) == 1;
        $is_master       = ($_SESSION['master']       ?? 0) == 1;
        $is_capogilda    = ($_SESSION['capogilda']    ?? 0) == 1;
        $is_capomestiere = ($_SESSION['capomestiere'] ?? 0) == 1;

        if (empty($messaggio)) {
            echo json_encode(['success' => false, 'message' => 'Messaggio vuoto']);
            exit;
        }

        $destinatari = [];

        switch ($tipo) {
            case 'presenti':
                if (!$is_admin && !$is_master) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT nome FROM personaggio WHERE ora_entrata > ora_uscita AND DATE_ADD(ultimo_refresh, INTERVAL 4 MINUTE) > NOW()", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['nome'];
                gdrcd_query($res, 'free');
                break;

            case 'broadcast':
                if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT nome FROM personaggio", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['nome'];
                gdrcd_query($res, 'free');
                break;

            case 'capogilda':
                if (!$is_admin && !$is_master && !$is_capogilda) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT nome FROM privilegi WHERE capogilda = 1", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['nome'];
                gdrcd_query($res, 'free');
                break;

            case 'capomestiere':
                if (!$is_admin && !$is_master && !$is_capomestiere) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT nome FROM privilegi WHERE capomestiere = 1", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['nome'];
                gdrcd_query($res, 'free');
                break;

            case 'gilda':
                if (!$is_admin && !$is_capogilda) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT clgpersonaggioruolo.personaggio FROM clgpersonaggioruolo
                    JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo
                    WHERE ruolo.gilda IN (
                        SELECT ruolo.gilda FROM clgpersonaggioruolo
                        JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo
                        WHERE clgpersonaggioruolo.personaggio = '$login' AND ruolo.gilda > -1
                    )", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['personaggio'];
                gdrcd_query($res, 'free');
                break;

            case 'tutto_mestiere':
                if (!$is_admin && !$is_capomestiere) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT clgpersonaggiomestiere.personaggio FROM clgpersonaggiomestiere
                    JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo
                    WHERE ruolo_mestiere.mestiere IN (
                        SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere
                        JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo
                        WHERE clgpersonaggiomestiere.personaggio = '$login' AND ruolo_mestiere.mestiere > -1
                    )", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['personaggio'];
                gdrcd_query($res, 'free');
                break;

            case 'tutto_inclinati':
                if (!$is_admin && !$is_master) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                $res = gdrcd_query("SELECT personaggio FROM clgpersonaggioinclinazione", 'result');
                while ($row = gdrcd_query($res, 'fetch')) $destinatari[] = $row['personaggio'];
                gdrcd_query($res, 'free');
                break;

            case 'multiplo':
                if (!$is_admin && !$is_master && !$is_capogilda && !$is_capomestiere) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); exit; }
                foreach (array_map('trim', explode(',', $data['destinatari'] ?? '')) as $nome_raw) {
                    $nome_safe = gdrcd_filter('in', $nome_raw);
                    if ($nome_safe && gdrcd_query("SELECT nome FROM personaggio WHERE nome = '$nome_safe'")) $destinatari[] = $nome_raw;
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Tipo non valido']);
                exit;
        }

        $label_tipo = [
            'presenti'        => 'Comunicazione ai personaggi in linea',
            'broadcast'       => 'Comunicazione a tutti i personaggi',
            'capogilda'       => 'Comunicazione ai capogilda',
            'capomestiere'    => 'Comunicazione ai capomestiere',
            'gilda'           => 'Comunicazione ai membri della gilda',
            'tutto_mestiere'  => 'Comunicazione ai membri del mestiere',
            'tutto_inclinati' => 'Comunicazione agli inclinati',
            'multiplo'        => 'Comunicazione a piu destinatari',
        ];
        $label     = $label_tipo[$tipo] ?? 'Comunicazione collettiva';
        $messaggio = '[' . $label . "]\n\n" . $messaggio;

        $inviati = 0;
        foreach ($destinatari as $dest_nome) {
            send_sms($_SESSION['login'], $dest_nome, '', $messaggio, $ongame);
            $inviati++;
        }

        echo json_encode(['success' => true, 'message' => "Messaggio inviato a $inviati destinatari."]);
        break;

    // -------------------------------------------------------------------------
    // DELETE_MSGS — elimina messaggi specifici da una conversazione.
    // Identificatore per riga: (mittente_nome, ora_spedizione) dentro la
    // conversazione — coppia sufficiente a unicità pratica (stessa precisione
    // al secondo, stesso mittente nella stessa conv).
    // -------------------------------------------------------------------------
    case 'delete_msgs':
        $conv_id   = (int)($data['conversazione_id'] ?? 0);
        $gruppo_id = (int)($data['gruppo_id']        ?? 0);
        $messaggi  = is_array($data['messaggi'] ?? null) ? $data['messaggi'] : [];

        if (empty($messaggi)) {
            echo json_encode(['success' => false, 'message' => 'Nessun messaggio selezionato']);
            exit;
        }

        if ($conv_id > 0) {
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali
                WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }
            foreach ($messaggi as $msg) {
                $mitt = gdrcd_filter('in', $msg['mittente'] ?? '');
                $ora  = gdrcd_filter('in', $msg['ora']      ?? '');
                if ($mitt && $ora) {
                    gdrcd_query("DELETE FROM sms WHERE id_conversazione = $conv_id AND mittente_nome = '$mitt' AND ora_spedizione = '$ora' LIMIT 1");
                }
            }
            echo json_encode(['success' => true]);

        } elseif ($gruppo_id > 0) {
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM partecipazione_gruppo
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }
            foreach ($messaggi as $msg) {
                $mitt = gdrcd_filter('in', $msg['mittente'] ?? '');
                $ora  = gdrcd_filter('in', $msg['ora']      ?? '');
                if ($mitt && $ora) {
                    gdrcd_query("DELETE FROM sms WHERE gruppo_id = $gruppo_id AND mittente_nome = '$mitt' AND ora_spedizione = '$ora' LIMIT 1");
                }
            }
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Specifica conversazione_id o gruppo_id']);
        }
        break;

    // -------------------------------------------------------------------------
    // DELETE_CONVS — elimina più conversazioni in un'unica chiamata
    // -------------------------------------------------------------------------
    case 'delete_convs':
        $conversazioni = is_array($data['conversazioni'] ?? null) ? $data['conversazioni'] : [];
        if (empty($conversazioni)) {
            echo json_encode(['success' => false, 'message' => 'Nessuna conversazione selezionata']);
            exit;
        }
        foreach ($conversazioni as $c) {
            $tipo      = $c['tipo']             ?? '';
            $conv_id   = (int)($c['conversazione_id'] ?? 0);
            $gruppo_id = (int)($c['gruppo_id']        ?? 0);

            if ($tipo === 'individuale' && $conv_id > 0) {
                $ok = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
                if ($ok['n'] == 0) continue;
                gdrcd_query("DELETE FROM conversazioni_individuali WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
                $rem = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali WHERE id_conversazione = $conv_id");
                if ((int)$rem['n'] === 0) gdrcd_query("DELETE FROM sms WHERE id_conversazione = $conv_id");

            } elseif ($tipo === 'gruppo' && $gruppo_id > 0) {
                $ok = gdrcd_query("SELECT COUNT(*) AS n FROM partecipazione_gruppo WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
                if ($ok['n'] == 0) continue;
                gdrcd_query("DELETE FROM partecipazione_gruppo WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
            }
        }
        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // DELETE_CONV — elimina la conversazione dal punto di vista dell'utente
    //   individuale: rimuove la riga in conversazioni_individuali; se entrambi
    //                i partecipanti hanno eliminato, pulisce anche i record sms.
    //   gruppo:      rimuove l'utente da partecipazione_gruppo (abbandona).
    // -------------------------------------------------------------------------
    case 'delete_conv':
        $conv_id   = (int)($data['conversazione_id'] ?? 0);
        $gruppo_id = (int)($data['gruppo_id']        ?? 0);

        if ($conv_id > 0) {
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali
                WHERE id_conversazione = $conv_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }

            gdrcd_query("DELETE FROM conversazioni_individuali
                WHERE id_conversazione = $conv_id AND utente_nome = '$login'");

            // Se non rimane nessun partecipante, elimina anche i messaggi
            $remaining = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali
                WHERE id_conversazione = $conv_id");
            if ((int)$remaining['n'] === 0) {
                gdrcd_query("DELETE FROM sms WHERE id_conversazione = $conv_id");
            }

            echo json_encode(['success' => true]);

        } elseif ($gruppo_id > 0) {
            $access = gdrcd_query("SELECT COUNT(*) AS n FROM partecipazione_gruppo
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");
            if ($access['n'] == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }

            gdrcd_query("DELETE FROM partecipazione_gruppo
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'");

            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Specifica conversazione_id o gruppo_id']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
