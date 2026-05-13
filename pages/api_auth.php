<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
$handleDBConnection = gdrcd_connect();

$op = $_GET['op'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($op) {

    // -------------------------------------------------------------------------
    // CHECK — il client chiede se c'è già una sessione attiva
    // -------------------------------------------------------------------------
    case 'check':
        if (!empty($_SESSION['login'])) {
            echo json_encode([
                'success'  => true,
                'login'    => $_SESSION['login'],
                'mappa'    => $_SESSION['mappa']  ?? 1,
                'luogo'    => $_SESSION['luogo']  ?? -1,
                'admin'    => $_SESSION['admin']  ?? 0,
                'master'   => $_SESSION['master'] ?? 0,
                'moderatore' => $_SESSION['moderatore'] ?? 0,
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    // -------------------------------------------------------------------------
    // LOGIN
    // -------------------------------------------------------------------------
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

        $record = gdrcd_query("SELECT personaggio.id_gilda, personaggio.ctnews_letto,
            personaggio.pass, personaggio.nome, personaggio.cognome, personaggio.permessi,
            personaggio.sesso, personaggio.ultima_mappa, personaggio.ultimo_luogo,
            personaggio.id_razza, personaggio.id_mestiere, personaggio.id_ruolo_mestiere,
            personaggio.ultimo_messaggio, personaggio.blocca_media,
            personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh,
            razza.sing_m, razza.sing_f, razza.icon AS url_img_razza,
            privilegi.admin, privilegi.moderatore, privilegi.master,
            privilegi.guida, privilegi.capomestiere, privilegi.capogilda, privilegi.grafico
            FROM personaggio
            LEFT JOIN razza      ON personaggio.id_razza = razza.id_razza
            LEFT JOIN privilegi  ON personaggio.nome = privilegi.nome
            WHERE personaggio.nome = '" . gdrcd_filter('in', $login1) . "' LIMIT 1");

        if (empty($record) || !gdrcd_password_check($pass1, $record['pass']) || $record['permessi'] < 0) {
            echo json_encode(['success' => false, 'message' => $MESSAGE['error']['login_failed'] ?? 'Credenziali non valide']);
            exit;
        }

        // Popola sessione (identica a login.php)
        $_SESSION['magic']              = ($record['id_mestiere'] == 3 ? 1 : 0);
        $_SESSION['custode']            = ($record['id_gilda'] == 4 ? 1 : 0);
        $_SESSION['login']              = $record['nome'];
        $_SESSION['cognome']            = $record['cognome'];
        $_SESSION['permessi']           = $record['permessi'];
        $_SESSION['sesso']              = $record['sesso'];
        $_SESSION['admin']              = $record['admin'];
        $_SESSION['capogilda']          = $record['capogilda'];
        $_SESSION['capomestiere']       = $record['capomestiere'];
        $_SESSION['master']             = $record['master'];
        $_SESSION['moderatore']         = $record['moderatore'];
        $_SESSION['guida']              = $record['guida'];
        $_SESSION['grafico']            = $record['grafico'];
        $_SESSION['ctnews_letto']       = $record['ctnews_letto'];
        $_SESSION['user']               = 1;
        $_SESSION['blocca_media']       = $record['blocca_media'];
        $_SESSION['ultima_uscita']      = $record['ora_uscita'];
        $_SESSION['razza']              = ($record['sesso'] == 'f') ? $record['sing_f'] : $record['sing_m'];
        $_SESSION['mestiere']           = $record['id_mestiere'];
        $_SESSION['img_razza']          = $record['url_img_razza'];
        $_SESSION['id_razza']           = $record['id_razza'];
        $_SESSION['mappa']              = (empty($record['ultima_mappa'])) ? 1 : $record['ultima_mappa'];
        $_SESSION['luogo']              = (empty($record['ultimo_luogo'])) ? -1 : $record['ultimo_luogo'];
        $_SESSION['tag']                = '';
        $_SESSION['last_message']       = 0;
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

        // Aggiorna presenza (identico a login.php)
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

        echo json_encode([
            'success' => true,
            'login'   => $_SESSION['login'],
            'mappa'   => $_SESSION['mappa'],
            'luogo'   => $_SESSION['luogo'],
            'redirect' => 'main.php?page=mappaclick&map_id=' . $_SESSION['mappa'],
        ]);
        break;

    // -------------------------------------------------------------------------
    // LOGOUT
    // -------------------------------------------------------------------------
    case 'logout':
        if (!empty($_SESSION['login'])) {
            gdrcd_query("UPDATE personaggio SET ora_uscita = NOW() WHERE nome='" . gdrcd_filter('in', $_SESSION['login']) . "'");
            notifySocketServer('users:update', 'loc:' . (int)$_SESSION['luogo']);
        }
        session_unset();
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
