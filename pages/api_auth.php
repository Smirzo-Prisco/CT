<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
$handleDBConnection = gdrcd_connect();

$op   = $_GET['op'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

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
        $login1 = gdrcd_filter('get', $data['login']    ?? '');
        // Filtro la password con 'get' (= addslashes) identico a login.php
        $pass1  = gdrcd_filter('get', $data['password'] ?? '');

        // Blacklist
        $bl = gdrcd_query("SELECT * FROM blacklist WHERE ip = '" . $_SERVER['REMOTE_ADDR'] . "' AND granted = 0", 'result');
        if (gdrcd_query($bl, 'num_rows') > 0) {
            gdrcd_query($bl, 'free');
            echo json_encode(['success' => false, 'message' => $MESSAGE['warning']['blacklisted'] ?? 'Accesso bloccato.']);
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
        try {
            $snd = gdrcd_query("SELECT suono_dm, suono_chat, suono_scheda FROM personaggio WHERE nome = '" . gdrcd_filter('in', $record['nome']) . "' LIMIT 1", 'query', true);
            $_SESSION['suono_dm']     = (int)($snd['suono_dm']     ?? 1);
            $_SESSION['suono_chat']   = (int)($snd['suono_chat']   ?? 1);
            $_SESSION['suono_scheda'] = (int)($snd['suono_scheda'] ?? 1);
        } catch (\Exception $e) {
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

        // Controllo esilio — query diretta per evitare l'echo HTML di gdrcd_controllo_esilio
        try {
            $exilio = gdrcd_query("SELECT esilio FROM personaggio WHERE nome = '" . gdrcd_filter('in', $record['nome']) . "' LIMIT 1", 'query', true);
            if (!empty($exilio['esilio']) && strtotime($exilio['esilio']) > time()) {
                session_destroy();
                echo json_encode(['success' => false, 'message' => 'Il tuo personaggio è attualmente in esilio.']);
                exit;
            }
        } catch (\Exception $e) {
            // Se il controllo fallisce, permetti il login
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

        // ── Operazioni non critiche: eseguite DOPO aver inviato la risposta ──
        // Se una di queste fallisce, il login è già completato lato client.

        // Stipendio automatico (se non già corrisposto oggi)
        try {
            if (isset($PARAMETERS['settings']['auto_salary']) && $PARAMETERS['settings']['auto_salary'] === 'ON') {
                $sal_row = gdrcd_query("SELECT ultimo_stipendio FROM personaggio WHERE nome = '$login_filtered' LIMIT 1", 'query', true);
                if (!empty($sal_row) && $sal_row['ultimo_stipendio'] != date('Y-m-d')) {
                    $q_stip = gdrcd_query("SELECT COALESCE(SUM(s.stipendio), 0) AS totale FROM (
                        SELECT ruolo_mestiere.stipendio FROM clgpersonaggiomestiere
                        LEFT JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo
                        WHERE clgpersonaggiomestiere.personaggio = '$login_filtered'
                        UNION ALL
                        SELECT ruolo_mestiere.stipendio FROM clgpersonaggiolavoro
                        LEFT JOIN ruolo_mestiere ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo
                        WHERE clgpersonaggiolavoro.personaggio = '$login_filtered'
                    ) AS s", 'query', true);
                    gdrcd_query("UPDATE personaggio SET banca = banca + " . (int)($q_stip['totale'] ?? 0) . ", ultimo_stipendio = NOW() WHERE nome = '$login_filtered'", 'query', true);
                }
            }
        } catch (\Exception $e) { /* non critico */ }

        // Tracciamento IP: log_entrate + rilevamento doppi account
        try {
            $ip_raw  = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
            $IP      = gdrcd_filter('in', $ip_raw);
            $Host    = gdrcd_filter('in', gethostbyaddr($ip_raw));
            $Browser = gdrcd_filter('in', $_SERVER['HTTP_USER_AGENT'] ?? '');

            $last_doppio = gdrcd_query("SELECT Nome, COUNT(*) AS n FROM log_entrate WHERE IP = '$IP' AND Nome != '$login_filtered' ORDER BY DataEvento DESC LIMIT 1", 'query', true);

            // Funzioni inline per evitare die() nei log_doppi
            $log_doppio_esiste = function(string $n1, string $n2, string $ip) use ($IP): int|false {
                $n1f = gdrcd_filter('in', $n1);
                $n2f = gdrcd_filter('in', $n2);
                $r   = gdrcd_query("SELECT id FROM log_doppi WHERE ((Nome='$n1f' AND Doppio='$n2f') OR (Nome='$n2f' AND Doppio='$n1f')) AND IP='" . gdrcd_filter('in', $ip) . "' LIMIT 1", 'result', true);
                $row = gdrcd_query($r, 'fetch');
                gdrcd_query($r, 'free');
                return $row ? (int)$row['id'] : false;
            };

            $logga_doppio = function(string $pg1, string $pg2, string $ip, string $host, string $browser) use ($log_doppio_esiste): void {
                $id = $log_doppio_esiste($pg1, $pg2, $ip);
                if ($id !== false) {
                    gdrcd_query("UPDATE log_doppi SET DataEvento = NOW() WHERE id = $id", 'query', true);
                } else {
                    gdrcd_query("INSERT INTO log_doppi (Nome, Doppio, IP, Host, Browser, DataEvento) VALUES ('" . gdrcd_filter('in', $pg1) . "','" . gdrcd_filter('in', $pg2) . "','$ip','$host','$browser', NOW())", 'query', true);
                }
            };

            if (isset($_COOKIE['lastlogin']) && $_COOKIE['lastlogin'] !== $_SESSION['login']) {
                $logga_doppio($_SESSION['login'], $_COOKIE['lastlogin'], $IP, $Host, $Browser);
            } elseif (!empty($last_doppio['n'])) {
                $logga_doppio($_SESSION['login'], $last_doppio['Nome'], $IP, $Host, $Browser);
            }
            setcookie('lastlogin', $_SESSION['login'], time() + (86400 * 30), '/');

            $check_24h = gdrcd_query("SELECT COUNT(*) AS c FROM log_entrate WHERE Nome = '$login_filtered' AND DataEvento > NOW() - INTERVAL 1 DAY", 'query', true);
            if (!empty($check_24h['c'])) {
                $check_ip = gdrcd_query("SELECT COUNT(*) AS c FROM log_entrate WHERE Nome = '$login_filtered' AND IP = '$IP' AND DataEvento > NOW() - INTERVAL 1 DAY", 'query', true);
                if (empty($check_ip['c'])) {
                    gdrcd_query("INSERT INTO log_entrate (Nome, DataEvento, IP, Host) VALUES ('$login_filtered', NOW(), '$IP', '$Host')", 'query', true);
                }
            } else {
                gdrcd_query("INSERT INTO log_entrate (Nome, DataEvento, IP, Host) VALUES ('$login_filtered', NOW(), '$IP', '$Host')", 'query', true);
            }
        } catch (\Exception $e) { /* non critico */ }

        // Reset back_chat se passate 24h o se è cambiata mattina (soglia ore 6)
        try {
            $backing      = gdrcd_query("SELECT last_date_back FROM personaggio WHERE nome = '$login_filtered' LIMIT 1", 'query', true);
            $now_ts       = time();
            $this_morning = strtotime('today 6:00');
            $last_action  = strtotime($backing['last_date_back'] ?? '');
            if ($now_ts - $last_action >= 86400 || ($last_action < $this_morning && $now_ts >= $this_morning)) {
                gdrcd_query("UPDATE personaggio SET back_chat = 0 WHERE nome = '$login_filtered'", 'query', true);
            }
        } catch (\Exception $e) { /* non critico */ }

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
