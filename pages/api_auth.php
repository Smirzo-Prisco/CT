<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
$handleDBConnection = gdrcd_connect();

$op   = $_GET['op'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Helper: traccia i doppi account ──────────────────────────────────────────
function _log_doppio_esiste(string $nome1, string $nome2, string $IP): int|false {
    $n1 = gdrcd_filter('in', $nome1);
    $n2 = gdrcd_filter('in', $nome2);
    $r  = gdrcd_query("SELECT id FROM log_doppi
        WHERE ((Nome = '$n1' AND Doppio = '$n2') OR (Nome = '$n2' AND Doppio = '$n1'))
          AND IP = '" . gdrcd_filter('in', $IP) . "' LIMIT 1", 'result');
    $row = gdrcd_query($r, 'fetch');
    gdrcd_query($r, 'free');
    return $row ? (int)$row['id'] : false;
}

function _logga_doppio(string $pg1, string $pg2, string $IP, string $Host, string $Browser): void {
    $id = _log_doppio_esiste($pg1, $pg2, $IP);
    if ($id !== false) {
        gdrcd_query("UPDATE log_doppi SET DataEvento = NOW() WHERE id = $id");
    } else {
        gdrcd_query("INSERT INTO log_doppi (Nome, Doppio, IP, Host, Browser, DataEvento)
            VALUES ('" . gdrcd_filter('in', $pg1) . "','" . gdrcd_filter('in', $pg2) . "',
                    '" . gdrcd_filter('in', $IP)  . "','" . gdrcd_filter('in', $Host) . "',
                    '" . gdrcd_filter('in', $Browser) . "', NOW())");
    }
}

switch ($op) {

    // ── CHECK — il client chiede se c'è già una sessione attiva ──────────────
    case 'check':
        if (!empty($_SESSION['login'])) {
            echo json_encode([
                'success'    => true,
                'login'      => $_SESSION['login'],
                'mappa'      => $_SESSION['mappa']      ?? 1,
                'luogo'      => $_SESSION['luogo']      ?? -1,
                'admin'      => $_SESSION['admin']      ?? 0,
                'master'     => $_SESSION['master']     ?? 0,
                'moderatore' => $_SESSION['moderatore'] ?? 0,
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    // ── LOGIN ─────────────────────────────────────────────────────────────────
    case 'login':
        $login1 = gdrcd_filter('get', $data['login'] ?? '');
        $pass1  = $data['password'] ?? '';

        // Blacklist
        $bl = gdrcd_query("SELECT id FROM blacklist WHERE ip = '" . $_SERVER['REMOTE_ADDR'] . "' AND granted = 0", 'result');
        if (gdrcd_query($bl, 'num_rows') > 0) {
            gdrcd_query($bl, 'free');
            echo json_encode(['success' => false, 'message' => $MESSAGE['warning']['blacklisted']]);
            exit;
        }
        gdrcd_query($bl, 'free');

        $login1 = ucwords(strtolower(trim($login1)));

        $record = gdrcd_query("SELECT
                personaggio.id_gilda, personaggio.ctnews_letto,
                personaggio.pass, personaggio.nome, personaggio.cognome, personaggio.permessi,
                personaggio.sesso, personaggio.ultima_mappa, personaggio.ultimo_luogo,
                personaggio.id_razza, personaggio.id_mestiere, personaggio.id_ruolo_mestiere,
                personaggio.ultimo_messaggio, personaggio.blocca_media,
                personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh,
                razza.sing_m, razza.sing_f, razza.icon AS url_img_razza,
                privilegi.admin, privilegi.moderatore, privilegi.master,
                privilegi.guida, privilegi.capomestiere, privilegi.capogilda, privilegi.grafico
            FROM personaggio
            LEFT JOIN razza     ON personaggio.id_razza = razza.id_razza
            LEFT JOIN privilegi ON personaggio.nome = privilegi.nome
            WHERE personaggio.nome = '" . gdrcd_filter('in', $login1) . "' LIMIT 1");

        if (empty($record) || !gdrcd_password_check($pass1, $record['pass']) || $record['permessi'] < 0) {
            echo json_encode(['success' => false, 'message' => 'Nome personaggio o password non riconosciuti.']);
            exit;
        }

        // Popola sessione
        $_SESSION['magic']               = ($record['id_mestiere'] == 3 ? 1 : 0);
        $_SESSION['custode']             = ($record['id_gilda'] == 4 ? 1 : 0);
        $_SESSION['login']               = $record['nome'];
        $_SESSION['cognome']             = $record['cognome'];
        $_SESSION['permessi']            = $record['permessi'];
        $_SESSION['sesso']               = $record['sesso'];
        $_SESSION['admin']               = $record['admin'];
        $_SESSION['capogilda']           = $record['capogilda'];
        $_SESSION['capomestiere']        = $record['capomestiere'];
        $_SESSION['master']              = $record['master'];
        $_SESSION['moderatore']          = $record['moderatore'];
        $_SESSION['guida']               = $record['guida'];
        $_SESSION['grafico']             = $record['grafico'];
        $_SESSION['ctnews_letto']        = $record['ctnews_letto'];
        $_SESSION['user']                = 1;
        $_SESSION['blocca_media']        = $record['blocca_media'];
        // Query separata: le colonne suono_* potrebbero non esistere ancora se upgrade.php non è stato eseguito
        try {
            $snd = gdrcd_query("SELECT suono_dm, suono_chat, suono_scheda FROM personaggio WHERE nome = '" . gdrcd_filter('in', $record['nome']) . "' LIMIT 1");
            $_SESSION['suono_dm']     = (int)($snd['suono_dm']     ?? 1);
            $_SESSION['suono_chat']   = (int)($snd['suono_chat']   ?? 1);
            $_SESSION['suono_scheda'] = (int)($snd['suono_scheda'] ?? 1);
        } catch (\mysqli_sql_exception $e) {
            $_SESSION['suono_dm']     = 1;
            $_SESSION['suono_chat']   = 1;
            $_SESSION['suono_scheda'] = 1;
        }
        $_SESSION['ultima_uscita']       = $record['ora_uscita'];
        $_SESSION['razza']               = ($record['sesso'] == 'f') ? $record['sing_f'] : $record['sing_m'];
        $_SESSION['mestiere']            = $record['id_mestiere'];
        $_SESSION['img_razza']           = $record['url_img_razza'];
        $_SESSION['id_razza']            = $record['id_razza'];
        $_SESSION['mappa']               = (empty($record['ultima_mappa'])) ? 1 : $record['ultima_mappa'];
        $_SESSION['luogo']               = (empty($record['ultimo_luogo'])) ? -1 : $record['ultimo_luogo'];
        $_SESSION['tag']                 = '';
        $_SESSION['last_message']        = 0;
        $_SESSION['last_istant_message'] = $record['ultimo_messaggio'];

        // Gilda/ruolo
        $res = gdrcd_query("SELECT ruolo.gilda, ruolo.immagine
            FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo
            WHERE clgpersonaggioruolo.personaggio = '" . gdrcd_filter('in', $record['nome']) . "'", 'result');
        while ($row = gdrcd_query($res, 'fetch')) {
            $_SESSION['gilda']     .= ',*' . $row['gilda'] . '*';
            $_SESSION['img_gilda'] .= $row['immagine'] . ',';
        }
        gdrcd_query($res, 'free');

        // Controllo esilio
        if (gdrcd_controllo_esilio($_SESSION['login'])) {
            session_destroy();
            echo json_encode(['success' => false, 'message' => 'Il tuo personaggio è attualmente in esilio.']);
            exit;
        }

        // Aggiorna presenza e notifica socket
        $remote_addr    = $_SERVER['REMOTE_ADDR'];
        $login_filtered = gdrcd_filter('in', $_SESSION['login']);
        if ($PARAMETERS['mode']['log_back_location'] == 'OFF') {
            $_SESSION['luogo'] = '-1';
            gdrcd_query("UPDATE personaggio SET ora_entrata = NOW(), ultimo_luogo='-1', ultimo_refresh = NOW(), last_ip = '$remote_addr', is_invisible = 0 WHERE nome = '$login_filtered'");
            notifySocketServer('users:update', 'loc:-1');
        } else {
            gdrcd_query("UPDATE personaggio SET ora_entrata = NOW(), ultimo_refresh = NOW(), last_ip = '$remote_addr', is_invisible = 0 WHERE nome = '$login_filtered'");
            notifySocketServer('users:update', 'loc:' . (int)$_SESSION['luogo']);
        }
        notifySocketServer('presenti:update', 'global');

        // Stipendio automatico (se non già corrisposto oggi)
        if ($PARAMETERS['settings']['auto_salary'] === 'ON') {
            $sal_row = gdrcd_query("SELECT ultimo_stipendio FROM personaggio WHERE nome = '$login_filtered' LIMIT 1");
            if ($sal_row['ultimo_stipendio'] != date('Y-m-d')) {
                $q_stip = gdrcd_query("SELECT COALESCE(SUM(s.stipendio), 0) AS totale FROM (
                    SELECT ruolo_mestiere.stipendio FROM clgpersonaggiomestiere
                    LEFT JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo
                    WHERE clgpersonaggiomestiere.personaggio = '$login_filtered'
                    UNION ALL
                    SELECT ruolo_mestiere.stipendio FROM clgpersonaggiolavoro
                    LEFT JOIN ruolo_mestiere ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo
                    WHERE clgpersonaggiolavoro.personaggio = '$login_filtered'
                ) AS s");
                gdrcd_query("UPDATE personaggio SET banca = banca + " . (int)$q_stip['totale'] . ", ultimo_stipendio = NOW() WHERE nome = '$login_filtered'");
            }
        }

        // Tracciamento IP: log_entrate + rilevamento doppi account
        $ip_raw  = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
        $IP      = gdrcd_filter('in', $ip_raw);
        $Host    = gdrcd_filter('in', gethostbyaddr($ip_raw));
        $Browser = gdrcd_filter('in', $_SERVER['HTTP_USER_AGENT'] ?? '');

        $last_doppio = gdrcd_query("SELECT Nome, COUNT(*) AS n FROM log_entrate
            WHERE IP = '$IP' AND Nome != '$login_filtered' ORDER BY DataEvento DESC LIMIT 1");
        if (isset($_COOKIE['lastlogin']) && $_COOKIE['lastlogin'] !== $_SESSION['login']) {
            _logga_doppio($_SESSION['login'], $_COOKIE['lastlogin'], $IP, $Host, $Browser);
        } elseif (!empty($last_doppio['n'])) {
            _logga_doppio($_SESSION['login'], $last_doppio['Nome'], $IP, $Host, $Browser);
        }
        setcookie('lastlogin', $_SESSION['login'], time() + (86400 * 30), '/');

        $check_24h = gdrcd_query("SELECT COUNT(*) AS c FROM log_entrate WHERE Nome = '$login_filtered' AND DataEvento > NOW() - INTERVAL 1 DAY");
        if ($check_24h['c'] > 0) {
            $check_ip = gdrcd_query("SELECT COUNT(*) AS c FROM log_entrate WHERE Nome = '$login_filtered' AND IP = '$IP' AND DataEvento > NOW() - INTERVAL 1 DAY");
            if ($check_ip['c'] == 0) {
                gdrcd_query("INSERT INTO log_entrate (Nome, DataEvento, IP, Host) VALUES ('$login_filtered', NOW(), '$IP', '$Host')");
            }
        } else {
            gdrcd_query("INSERT INTO log_entrate (Nome, DataEvento, IP, Host) VALUES ('$login_filtered', NOW(), '$IP', '$Host')");
        }

        // Reset back_chat se passate 24h o se è cambiata mattina (soglia ore 6)
        $backing      = gdrcd_query("SELECT last_date_back FROM personaggio WHERE nome = '$login_filtered' LIMIT 1");
        $now_ts       = time();
        $this_morning = strtotime('today 6:00');
        $last_action  = strtotime($backing['last_date_back'] ?? '');
        if ($now_ts - $last_action >= 86400 || ($last_action < $this_morning && $now_ts >= $this_morning)) {
            gdrcd_query("UPDATE personaggio SET back_chat = 0 WHERE nome = '$login_filtered'");
        }

        $redirect = ($PARAMETERS['mode']['log_back_location'] == 'OFF')
            ? 'main.php?page=mappaclick&map_id=' . (int)$_SESSION['mappa']
            : 'main.php?dir=' . (int)$_SESSION['luogo'];

        echo json_encode([
            'success'  => true,
            'login'    => $_SESSION['login'],
            'mappa'    => $_SESSION['mappa'],
            'luogo'    => $_SESSION['luogo'],
            'redirect' => $redirect,
        ]);
        break;

    // ── LOGOUT ────────────────────────────────────────────────────────────────
    case 'logout':
        if (!empty($_SESSION['login'])) {
            gdrcd_query("UPDATE personaggio SET ora_uscita = NOW() WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'");
            notifySocketServer('users:update', 'loc:' . (int)$_SESSION['luogo']);
            notifySocketServer('presenti:update', 'global');
        }
        session_unset();
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
