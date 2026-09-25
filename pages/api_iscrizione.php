<?php
/**
 * api_iscrizione.php — API pubblica per la pagina di iscrizione
 *
 * op=options  → razze e mestieri disponibili per i dropdown
 * op=termini  → testo dei termini e condizioni (disclaimer + regolamento)
 * op=register → crea il personaggio (sostituisce il vecchio iscrizione.php)
 */

require_once dirname(__DIR__) . '/includes/required.php';
require_once dirname(__DIR__) . '/includes/custom_functions.inc.php';

$handleDBConnection = gdrcd_connect();

header('Content-Type: application/json; charset=utf-8');

$op = $_GET['op'] ?? '';

if ($op === 'options') {

    $razze = [];
    $r = gdrcd_query("SELECT id_gilda, nome FROM gilda WHERE visibile=1 ORDER BY nome", 'result');
    while ($row = gdrcd_query($r, 'fetch')) {
        $razze[] = ['id' => (int)$row['id_gilda'], 'nome' => $row['nome']];
    }
    gdrcd_query($r, 'free');

    $mestieri = [];
    // Solo i mestieri veri (tipo=1): esclude le gilde giocatore, che condividono
    // la stessa tabella ruolo_mestiere ma non sono scelte in fase di iscrizione
    $m = gdrcd_query("SELECT rm.id_ruolo, rm.nome_ruolo FROM ruolo_mestiere rm JOIN mestiere m ON rm.mestiere = m.id_mestiere WHERE rm.livello_mestiere = 3 AND m.tipo = 1 ORDER BY rm.nome_ruolo", 'result');
    while ($row = gdrcd_query($m, 'fetch')) {
        $mestieri[] = ['id' => (int)$row['id_ruolo'], 'nome' => $row['nome_ruolo']];
    }
    gdrcd_query($m, 'free');

    echo json_encode(['success' => true, 'razze' => $razze, 'mestieri' => $mestieri]);

} elseif ($op === 'termini') {

    echo json_encode([
        'success'    => true,
        'disclaimer' => $MESSAGE['register']['disclaimer'] ?? '',
        'regolamento' => $MESSAGE['register']['rules_read'] ?? '',
    ]);

} elseif ($op === 'register') {

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $email       = trim((string)($input['email'] ?? ''));
    $nome        = trim(gdrcd_capital_letter(gdrcd_filter('in', $input['nome'] ?? '')));
    $cognome     = trim(gdrcd_filter('in', $input['cognome'] ?? ''));
    $genere      = ($input['genere'] ?? 'm') === 'f' ? 'f' : 'm';
    $razza_id    = (int)($input['razza'] ?? 0);
    $mestiere_id = (int)($input['mestiere'] ?? 0);

    if ($email === '' || strpos($email, '@') === false || strpos($email, '.') === false) {
        echo json_encode(['success' => false, 'message' => $MESSAGE['register']['error']['email_needed']]);
        gdrcd_close_connection($handleDBConnection);
        exit;
    }

    if ($nome === '') {
        echo json_encode(['success' => false, 'message' => 'Il nome del personaggio è obbligatorio.']);
        gdrcd_close_connection($handleDBConnection);
        exit;
    }

    $result = gdrcd_query("SELECT email FROM personaggio WHERE email='" . gdrcd_filter('in', $email) . "' LIMIT 1", 'result');
    if (gdrcd_query($result, 'num_rows') > 0) {
        gdrcd_query($result, 'free');
        echo json_encode(['success' => false, 'message' => $MESSAGE['register']['error']['email_taken']]);
        gdrcd_close_connection($handleDBConnection);
        exit;
    }
    gdrcd_query($result, 'free');

    $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome='" . gdrcd_filter('in', $nome) . "' LIMIT 1", 'result');
    if (gdrcd_query($result, 'num_rows') > 0) {
        gdrcd_query($result, 'free');
        echo json_encode(['success' => false, 'message' => $MESSAGE['register']['error']['name_taken']]);
        gdrcd_close_connection($handleDBConnection);
        exit;
    }
    gdrcd_query($result, 'free');

    // mestiere = 0 ("Decidi poi"): il personaggio nasce senza legame a un mestiere,
    // stesso stato di "Disoccupato" già usato altrove (es. licenziamento in
    // gestione_mestiere.inc.php: id_mestiere=0, id_ruolo_mestiere=1)
    if ($mestiere_id === 0) {
        $mestiere_nome     = null;
        $lavoro             = 0;
        $id_ruolo_mestiere = 1;
    } else {
        $ruolo = gdrcd_query("SELECT rm.id_ruolo, rm.nome_ruolo FROM ruolo_mestiere rm JOIN mestiere m ON rm.mestiere = m.id_mestiere WHERE rm.id_ruolo = " . $mestiere_id . " AND rm.livello_mestiere = 3 AND m.tipo = 1 LIMIT 1");
        if (!$ruolo) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non valido.']);
            gdrcd_close_connection($handleDBConnection);
            exit;
        }
        $mestiere_nome = $ruolo['nome_ruolo'];

        // Famiglia numerica di personaggio.id_mestiere: mappatura storica per id_ruolo,
        // invariata da iscrizione.php (i valori corrispondono a ruolo_mestiere specifici)
        $mappa_lavoro = [1 => 0, 38 => 1, 76 => 2, 64 => 3, 83 => 4, 90 => 6, 12 => 6];
        $lavoro             = $mappa_lavoro[$mestiere_id] ?? 0;
        $id_ruolo_mestiere = $mestiere_id;
    }

    $gilda_nome = null;
    if ($razza_id !== 0) {
        $gilda_row = gdrcd_query("SELECT nome FROM gilda WHERE id_gilda = " . $razza_id . " LIMIT 1");
        if ($gilda_row) {
            $gilda_nome = $gilda_row['nome'];
        }
    }

    // personaggio.id_razza fisso: il dropdown "razza" pesca in realtà dalla tabella
    // gilda (vedi op=options) ed è solo informativo, non va scritto come vera razza
    $id_razza_default = 11000;

    $pass = gdrcd_genera_pass();

    $lastpasschange_field = '';
    $lastpasschange_value = '';
    if ($PARAMETERS['mode']['alert_password_change'] == 'ON' && $PARAMETERS['settings']['alert_password_change']['alert_from_signup'] == 'OFF') {
        $lastpasschange_field = ", ultimo_cambiopass";
        $lastpasschange_value = ", NOW()";
    }

    gdrcd_query("INSERT INTO personaggio (nome, cognome, pass, data_iscrizione, email, sesso, id_razza, id_mestiere, id_ruolo_mestiere, url_img, url_img_chat, car0, car1, car2, car3, car4, car5, car6, car7, car8, car9, shin, salute, salute_max, integrita, integrita_max, soldi, punto_razza, esperienza_mestiere, esperienza $lastpasschange_field) VALUES ('" . $nome . "', '" . $cognome . "', '" . gdrcd_encript($pass) . "', NOW(), '" . gdrcd_filter('in', $email) . "', '" . gdrcd_filter('in', $genere) . "', " . $id_razza_default . ", " . $lavoro . ", " . $id_ruolo_mestiere . ", 'http://crystaltokyogdr.altervista.org/imgs/avatars/avatar_empty.png', 'http://crystaltokyogdr.altervista.org/imgs/avatars/avatar_mini_empty.png', '10.0', '0', '10.0', '0', '10.0', '0', '10.0', '0', '10.0', '0', '0', " . gdrcd_filter('num', $PARAMETERS['settings']['max_hp']) . ", " . gdrcd_filter('num', $PARAMETERS['settings']['max_hp']) . ", " . gdrcd_filter('num', $PARAMETERS['settings']['max_integrita']) . ", " . gdrcd_filter('num', $PARAMETERS['settings']['max_integrita']) . ", " . gdrcd_filter('num', $PARAMETERS['settings']['first_money']) . ", '0.0', " . gdrcd_filter('num', $PARAMETERS['settings']['first_px_job']) . ", " . gdrcd_filter('num', $PARAMETERS['settings']['first_px']) . " $lastpasschange_value)");

    if ($mestiere_id !== 0) {
        gdrcd_query("INSERT INTO clgpersonaggiomestiere (personaggio, id_ruolo) VALUES ('" . $nome . "', " . $mestiere_id . ")");
    }

    // Rilevamento doppi in fase di iscrizione: il personaggio appena creato non
    // ha ancora una propria storia di log_entrate (non si è mai loggato), ma
    // l'IP e (se presente) il cookie device_id della richiesta di iscrizione sì —
    // stesse due fonti dati (log_entrate + log_dispositivi) del rilevamento doppi
    // al login (vedi case 'login' in api_auth.php), qui riapplicate a chi si
    // iscrive. Il solo segnale IP non basta: era l'unico controllato qui finché
    // un'iscrizione doppia da rete mobile (IP diverso da quello dell'ultimo login
    // del pg originale, ma stesso browser/device_id) non è passata inosservata.
    $ip_iscrizione = gdrcd_filter('in', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR']);
    $nome_filtrato = gdrcd_filter('in', $nome);

    $doppi_segnali = []; // Nome => ['ip', 'dispositivo']

    // Niente JOIN con personaggio: un doppio il cui personaggio "originale" è
    // stato nel frattempo cancellato definitivamente (es. Patrick, vedi caso
    // Jolie del 22/09) sparirebbe altrimenti da questo controllo, perché il
    // JOIN richiede una riga in personaggio che a quel punto non c'è più — pur
    // restando visibile al login (case 'login' in api_auth.php, che non fa
    // questo JOIN). log_entrate va letta da sola: è preservata apposta per
    // riconoscere un ritorno anche dopo la cancellazione (vedi commenti in
    // erase_pg.inc.php/erasepg_scelta.inc.php/erase_inactive.inc.php).
    $doppi_result = gdrcd_query("
        SELECT DISTINCT Nome
        FROM log_entrate
        WHERE IP = '$ip_iscrizione' AND Nome != '$nome_filtrato'
    ", 'result');
    while ($doppi_row = gdrcd_query($doppi_result, 'fetch')) {
        $doppi_segnali[$doppi_row['Nome']][] = 'ip';
    }
    gdrcd_query($doppi_result, 'free');

    // Il device_id è un cookie di lunga durata impostato al login (vedi api_auth.php):
    // chi si iscrive da un browser già usato per giocare un altro personaggio lo
    // porta con sé nella richiesta. Non ne generiamo uno nuovo qui: associare un
    // device_id a un nome resta compito esclusivo del login.
    $deviceId = $_COOKIE['device_id'] ?? null;
    if ($deviceId !== null) {
        $deviceIdF = gdrcd_filter('in', $deviceId);
        $doppi_dispositivo_result = gdrcd_query("
            SELECT DISTINCT Nome FROM log_dispositivi
            WHERE device_id = '$deviceIdF' AND Nome != '$nome_filtrato'
        ", 'result');
        while ($doppi_row = gdrcd_query($doppi_dispositivo_result, 'fetch')) {
            $doppi_segnali[$doppi_row['Nome']][] = 'dispositivo';
        }
        gdrcd_query($doppi_dispositivo_result, 'free');
    }

    // Stessa tabella log_doppi del login, stesso helper di upsert: così un doppio
    // rilevato in iscrizione compare anche nella tab Doppi (main.php?page=log&tab=doppi)
    // invece di restare visibile solo nel DM del momento.
    if (!empty($doppi_segnali)) {
        $host_iscrizione    = gdrcd_filter('in', gethostbyaddr($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR']));
        $browser_iscrizione = gdrcd_filter('in', $_SERVER['HTTP_USER_AGENT'] ?? '');
        foreach ($doppi_segnali as $altro_nome => $metodi) {
            foreach (array_unique($metodi) as $metodo) {
                $esiste = gdrcd_query("SELECT id FROM log_doppi WHERE ((Nome='$nome_filtrato' AND Doppio='" . gdrcd_filter('in', $altro_nome) . "') OR (Nome='" . gdrcd_filter('in', $altro_nome) . "' AND Doppio='$nome_filtrato')) AND IP='$ip_iscrizione' AND Metodo='" . gdrcd_filter('in', $metodo) . "' LIMIT 1");
                if ($esiste) {
                    gdrcd_query("UPDATE log_doppi SET DataEvento = NOW() WHERE id = " . (int)$esiste['id']);
                } else {
                    gdrcd_query("INSERT INTO log_doppi (Nome, Doppio, IP, Host, Browser, DataEvento, Metodo) VALUES ('$nome_filtrato', '" . gdrcd_filter('in', $altro_nome) . "', '$ip_iscrizione', '$host_iscrizione', '$browser_iscrizione', NOW(), '" . gdrcd_filter('in', $metodo) . "')");
                }
            }
        }
    }

    // DM di notifica a tutti gli admin — nuovo personaggio iscritto.
    // Il nome è un link cliccabile verso messages_center (compose diretto verso il pg,
    // vedi MessagesInbox.jsx che gestisce ?to=NOME) — testo/dangerouslySetInnerHTML,
    // per questo il nome visibile va comunque escapato con htmlspecialchars.
    $nome_link  = '<a href="main.php?page=messages_center&to=' . urlencode($nome) . '">' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</a>';
    $testo_dm_raw = 'Nuovo personaggio iscritto il ' . date('d/m/Y') . ' alle ' . date('H:i') . ': ' . $nome_link;
    if (!empty($doppi_segnali)) {
        $etichette_metodo = ['ip' => 'stesso IP', 'dispositivo' => 'stesso dispositivo'];
        $doppi_desc = [];
        foreach ($doppi_segnali as $altro_nome => $metodi) {
            $link = '<a href="main.php?page=messages_center&to=' . urlencode($altro_nome) . '">' . htmlspecialchars($altro_nome, ENT_QUOTES, 'UTF-8') . '</a>';
            $etichette = implode(' + ', array_map(fn($m) => $etichette_metodo[$m], array_unique($metodi)));
            $doppi_desc[] = "$link ($etichette)";
        }
        $testo_dm_raw .= ' — POSSIBILE DOPPIO: ' . implode(', ', $doppi_desc);
    }
    $testo_dm   = gdrcd_filter('in', $testo_dm_raw);
    $admin_list = gdrcd_query("SELECT nome FROM privilegi WHERE admin = 1", 'result');
    while ($admin_row = gdrcd_query($admin_list, 'fetch')) {
        send_sms('System', $admin_row['nome'], '', $testo_dm, 0);
    }
    gdrcd_query($admin_list, 'free');

    // DM di benvenuto al nuovo iscritto
    $testo_benvenuto = gdrcd_filter('in', "Benvenut* su Crystal Tokyo! Se hai bisogno di aiuto o hai delle curiosità sul sistema, puoi contattare qualsiasi membro dello staff online oppure chiedere al nostro Crystal Bot. Grazie per esserti iscritt* e buon divertimento.");
    send_sms('System', $nome, '', $testo_benvenuto, 0);

    $emailconfirmation = $PARAMETERS['mode']['emailconfirmation'] == 'ON';
    if ($emailconfirmation) {
        $text = $MESSAGE['register']['welcome']['message'][0] . ' ' . $PARAMETERS['info']['site_name'] . "\n\n " . $MESSAGE['register']['welcome']['message'][1] . "\n     " . $MESSAGE['register']['welcome']['message'][2] . "\n\n    " . $MESSAGE['register']['welcome']['message']['user'] . ' ' . $nome . "\n" . $MESSAGE['register']['welcome']['message']['pass'] . ' ' . $pass . "\n\n    " . $PARAMETERS['info']['webmaster_name'];
        $subject = $PARAMETERS['info']['site_name'] . ' - Registrazione di ' . $nome . ' ' . $cognome;
        send_mail($email, $subject, nl2br($text));
    }

    echo json_encode([
        'success'           => true,
        'nome'              => $nome,
        'password'          => $pass,
        'gilda_nome'        => $gilda_nome,
        'mestiere_nome'     => $mestiere_nome,
        'emailconfirmation' => $emailconfirmation,
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}

gdrcd_close_connection($handleDBConnection);
