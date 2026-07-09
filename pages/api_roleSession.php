<?php
if(isset($_GET['op']) && $_GET['op'] != '') {
    session_start();

    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');
    require_once(__DIR__ . '/../includes/chat_functions.inc.php');
    
    // IMPORTANTE: Solo per le richieste AJAX
    header('Content-Type: application/json');

    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato']);
        exit;
    }

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    switch ($_GET['op']) {
        // ROLE SESSION
        case 'addPgToRole':
            $login = $_SESSION['login'];
            $location = $_SESSION['luogo'];

            try {
                // Controlla se l'utente ha già una giocata in corso
                if(pgIsInRole($login)) {
                    echo json_encode(['success' => false,  'message' => "L'utente $userName ha già una giocata in corso."]);
                    exit;
                }

                // Controlla se esiste un debito di cura di emergenza da giorni precedenti
                $login_safe = gdrcd_filter('in', $login);
                $debt = gdrcd_query("SELECT SUM(punti) AS totale FROM cure_emergenza WHERE nome = '$login_safe' AND data_cura < CURDATE()");
                $debito_ps = (int)($debt['totale'] ?? 0);
                if ($debito_ps > 0) {
                    $malus        = (int)ceil($debito_ps * 1.30);
                    $result_debito = adjustPgStats($login, -$malus);
                    gdrcd_query("DELETE FROM cure_emergenza WHERE nome = '$login_safe' AND data_cura < CURDATE()");
                    $sal_dopo    = $result_debito ? $result_debito['salute']     : '?';
                    $sal_max_d   = $result_debito ? $result_debito['salute_max'] : '?';
                    chatInsertMessage($location, 'System', $login, "sconta il debito della cura di emergenza: -$malus PS ($debito_ps + 30%). Salute: $sal_dopo/$sal_max_d.", 'N');
                }

                $id_role = locationActiveRole($location); // Recupera l'eventuale role attiva nella chat

                // Se nella chat NON ci sono giocate attive, avvio una nuova role
                if(!$id_role) {
                    gdrcd_query("INSERT INTO role_sessions (`location`, `start`) VALUES ($location, NOW())");
                    $id_role = gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
                }

                // In ogni caso aggiungo il pg alla role
                addPgToRole($id_role, $login, $location);

                // Notifica tutti i client nella stessa stanza:
                // TargetSelector.jsx ascolta 'role:update' per ricaricare la lista bersagli
                notifySocketServer('role:update', 'loc:' . $location);

                echo json_encode(['success' => true, 'message' => 'Giocatore aggiunto con successo!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Errore nel processamento degli utenti: ' . $e->getMessage(), 'error_details' => [ 'file' => $e->getFile(), 'line' => $e->getLine() ]]);
            }
            break;
        case 'quitRole':
            $id_role = locationActiveRole($_SESSION['luogo']); // Recupera l'eventuale role attiva nella chat
            $user = isset($data['user']) ? gdrcd_filter('in', $data['user']) : $_SESSION['login'];
            $pgIsInRole = pgIsInRole($user, $_SESSION['luogo']); // Verifica se il pg è nella role della chat
            $set_end = "UPDATE role_session_players SET `sent` = 0, close_turn = 0, `end` = NOW() WHERE pg_name = '$user' AND id_role = ".$id_role." AND `end` IS NULL LIMIT 1";

            // Se il pg è nella role, procedo con l'uscita
            if ($pgIsInRole && gdrcd_query($set_end)) {
                chatInsertMessage($_SESSION['luogo'], 'System', $user, " ha abbandonato la role", 'N');

                if(!pgIsInRole('', $_SESSION['luogo'], true)) endRoleSession($_SESSION['luogo']); // Se sono usciti tutti i pg, chiudo la role

                checkTurnEnd($_SESSION['luogo'], $user, $id_role); // Controllo se i pg rimasti hanno tutti già azionato nel turno, così propongo la chiusura

                // Notifica tutti i client nella stessa stanza che la lista role è cambiata
                notifySocketServer('role:update', 'loc:' . $_SESSION['luogo']);

                echo json_encode(array('success' => true, 'message' => "Il personaggio $user è uscito dalla role."));
            } else echo json_encode(array('success' => false, 'message' => "Errore nell'uscita dalla role.", 'query' => $set_end));

            break;
        case 'getPgRolePlaying': // Serve per mostrare i personaggi coinvolti nella giocata corrente della chat
            try {
                $query = "SELECT role_session_players.* FROM role_session_players 
                            INNER JOIN role_sessions ON role_session_players.id_role = role_sessions.id_role 
                            WHERE role_sessions.end IS NULL 
                            AND role_sessions.freezed IS NULL 
                            AND role_sessions.location = ".$_SESSION['luogo'];
                $result = gdrcd_query($query, 'result');
                $usersInRole = [];

                while ($row = gdrcd_query($result, 'fetch')) {
                    $usersInRole[] = [
                        'name' => $row['pg_name'],
                        'inRole' => $row['end'] === null ? true : false,
                        'sent' => $row['sent'] == 1 ? true : false,
                        'closed' => $row['close_turn'] == 1 ? true : false
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Personaggi giocanti recuperati con successo',
                    'users' => $usersInRole,
                    'canQuit' => isAdminMasterMod($_SESSION),
                    'canAdd' => pgIsInRole($_SESSION['login'], $_SESSION['luogo']) || isAdminMasterMod($_SESSION)
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Errore nel processamento degli utenti: ' . $e->getMessage(), 'error_details' => [ 'file' => $e->getFile(), 'line' => $e->getLine() ]]);
            }
            break;
        case 'getPngRolePlaying': // Serve per caricare i PNG, creati nella role, all'interno della sezione del pannello chat dedicata ai master
            try {
                $query = "SELECT role_session_players.* FROM role_session_players 
                            INNER JOIN role_sessions ON role_session_players.id_role = role_sessions.id_role 
                            WHERE role_session_players.png = 1 
                            AND role_session_players.end IS NULL 
                            AND role_sessions.end IS NULL 
                            AND role_sessions.freezed IS NULL 
                            AND role_sessions.location = ".$_SESSION['luogo'];
                $result = gdrcd_query($query, 'result');
                $pngInRole = [];

                while ($row = gdrcd_query($result, 'fetch')) $pngInRole[] = $row['pg_name'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'PNG recuperati con successo',
                    'png' => $pngInRole
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Errore nel processamento degli utenti: ' . $e->getMessage(), 'error_details' => [ 'file' => $e->getFile(), 'line' => $e->getLine() ]]);
            }
            break;
        case 'getRolePgs': // Prende tutti i personaggi della role
            $location = (int)$_SESSION['luogo'];
            $id_role  = locationActiveRole($location);

            // Se non c'è una role attiva nella stanza, $id_role è false.
            // Restituiamo risposta vuota invece di passare false alla query
            // (PHP interpola false come stringa vuota → "WHERE id_role =" → SQL error → 500).
            if (!$id_role) {
                echo json_encode(['success' => false, 'message' => 'Nessuna role attiva', 'users' => [], 'id_role' => 0]);
                break;
            }

            $users = getRolePgs($id_role, true);

            if (!empty($users)) $response = ['success' => true,  'message' => 'Utenti della role!',                            'users' => $users, 'id_role' => $id_role];
            else                $response = ['success' => false, 'message' => 'Nessun utente attivo nella role corrente.', 'users' => [],     'id_role' => $id_role];

            echo json_encode($response);
            break;
        case 'closePgTurn': // Chiude il turno di un pg, se è l'ultimo chiude anche la role
            $id_role = isset($data['id_role']) ? (int)gdrcd_filter('in', $data['id_role']) : '';
            $suss_id = isset($data['suss_id']) ? (int)gdrcd_filter('in', $data['suss_id']) : 0;
            $pgName = isset($data['pgName']) ? gdrcd_filter('in', $data['pgName']) : '';

            if($suss_id > 0) { // Elimino il sussurro
                $_SESSION['last_message'] = ($suss_id-1); // Devo forzare l'aggiornamento della chat
                
                gdrcd_query("DELETE FROM chat WHERE id = $suss_id LIMIT 1"); // Elimino il sussurro
            }

            closePgTurn($id_role, $pgName, $_SESSION['luogo']);

            echo json_encode(array('success' => true, 'message' => "Turno chiuso"));
            break;
        case 'closeTurn': // Chiudo il turno fornzatamente, indipendentemente da tutto
            if (!isAdminMasterMod($_SESSION)) {
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                exit;
            }

            $location = $_SESSION['luogo'];
            $id_role = locationActiveRole($location);
            
            if(is_numeric($id_role)) {
                closeTurn($id_role, $location);
                $response = array('success' => true, 'message' => "Turno chiuso forzatamente");
            } else $response = array('success' => false, 'message' => "Nessuna role attiva nella chat, impossibile chiudere il turno");

            echo json_encode($response);
            break;
            exit;
        case 'getRoleParticipants':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false]); break; }
            $res_pgs = gdrcd_query("
                SELECT DISTINCT rsp.pg_name,
                       CASE WHEN COALESCE(p.id_gilda, 0) > 0 THEN p.id_gilda ELSE 0 END        AS id_gilda,
                       CASE WHEN COALESCE(p.id_gilda, 0) > 0 THEN COALESCE(g.nome, 'Senza razza') ELSE 'Senza razza' END AS gilda_nome
                FROM role_session_players rsp
                LEFT JOIN personaggio p ON p.nome = rsp.pg_name
                LEFT JOIN gilda       g ON g.id_gilda = p.id_gilda
                ORDER BY rsp.pg_name ASC
            ", 'result');
            $pg_list = [];
            while ($r = gdrcd_query($res_pgs, 'fetch')) {
                $pg_list[] = [
                    'pg_name'    => $r['pg_name'],
                    'id_gilda'   => (int)$r['id_gilda'],
                    'gilda_nome' => $r['gilda_nome'],
                ];
            }
            echo json_encode(['success' => true, 'pgs' => $pg_list]);
            break;

        case 'getPgAllRoles':
            $is_staff    = isAdminMasterMod($_SESSION);
            $login_f     = gdrcd_filter('in', $_SESSION['login']);
            $pg_param    = isset($_GET['pg'])    ? gdrcd_filter('in', trim($_GET['pg'])) : '';
            $gilda_param = ($is_staff && isset($_GET['gilda'])) ? (int)trim($_GET['gilda']) : null;

            $show_all   = $is_staff && $pg_param === 'all';
            $show_gilda = $is_staff && $gilda_param !== null;

            if ($show_gilda) {
                // Giocate di tutti i PG della razza selezionata
                $gilda_cond = ($gilda_param === 0)
                    ? "COALESCE(id_gilda, 0) <= 0"
                    : "id_gilda = $gilda_param";
                $where = "WHERE role_session_players.pg_name IN (SELECT nome FROM personaggio WHERE $gilda_cond)";
            } elseif ($show_all) {
                $where = '';
            } else {
                $pg_filter = ($is_staff && $pg_param !== '') ? $pg_param : $login_f;
                $where = "WHERE role_session_players.pg_name = '$pg_filter'";
            }

            $query = "SELECT role_sessions.id_role, role_sessions.location, role_sessions.start,
                             role_sessions.end, role_sessions.turn, mappa.nome, mappa.id as luogo_id
                      FROM role_sessions
                      LEFT JOIN mappa ON role_sessions.location = mappa.id
                      INNER JOIN role_session_players ON role_sessions.id_role = role_session_players.id_role
                      $where
                      GROUP BY role_sessions.id_role
                      ORDER BY role_sessions.start DESC";
            $result = gdrcd_query($query, 'result');
            $roles = [];

            while ($row = gdrcd_query($result, 'fetch')) {
                $roles[] = [
                    'id'           => (int)$row['id_role'],
                    'luogo_id'     => $row['luogo_id'],
                    'luogo'        => $row['nome'],
                    'data'         => date('Y-m-d', strtotime($row['start'])),
                    'oraInizio'    => date('H:i', strtotime($row['start'])),
                    'oraFine'      => $row['end'] !== null ? date('H:i', strtotime($row['end'])) : '',
                    'totTurni'     => (int)$row['turn'],
                    'partecipanti' => getRolePgs($row['id_role'], false),
                    'inCorso'      => $row['end'] === null,
                    'icona'        => 'fas fa-globe',
                    'my_shin'      => 'none',
                    'pending_count' => 0,
                ];
            }

            // Shin enrichment: flag/award status per giocata
            if (!empty($roles)) {
                $all_ids = implode(',', array_map('intval', array_column($roles, 'id')));

                $my_shin_map = [];
                $res_my = gdrcd_query("SELECT id_role, awarded_at FROM role_session_shin WHERE id_role IN ($all_ids) AND pg_name = '$login_f'", 'result');
                while ($r = gdrcd_query($res_my, 'fetch'))
                    $my_shin_map[(int)$r['id_role']] = $r['awarded_at'] ? 'awarded' : 'pending';

                $pending_map = [];
                if ($is_staff) {
                    $res_p = gdrcd_query("SELECT id_role, COUNT(*) AS cnt FROM role_session_shin WHERE id_role IN ($all_ids) AND awarded_at IS NULL GROUP BY id_role", 'result');
                    while ($r = gdrcd_query($res_p, 'fetch'))
                        $pending_map[(int)$r['id_role']] = (int)$r['cnt'];
                }

                foreach ($roles as &$role) {
                    $role['my_shin']       = $my_shin_map[$role['id']] ?? 'none';
                    $role['pending_count'] = $pending_map[$role['id']] ?? 0;
                }
                unset($role);
            }

            echo json_encode([
                'success'      => true,
                'roles'        => $roles,
                'is_staff'     => $is_staff,
                'current_user' => $_SESSION['login'],
            ]);
            break;
        case 'flagRole':  // Toggle richiesta shin su una giocata
            $login_f = gdrcd_filter('in', $_SESSION['login']);
            $id_role = isset($data['id_role']) ? (int)$data['id_role'] : 0;
            if (!$id_role) { echo json_encode(['success' => false, 'message' => 'ID mancante']); break; }

            $part = gdrcd_query("SELECT 1 FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login_f' LIMIT 1");
            if (!$part) { echo json_encode(['success' => false, 'message' => 'Non hai partecipato a questa giocata']); break; }

            $existing = gdrcd_query("SELECT awarded_at FROM role_session_shin WHERE id_role = $id_role AND pg_name = '$login_f'");
            if ($existing && $existing['awarded_at']) {
                echo json_encode(['success' => false, 'message' => 'Questa giocata è già stata premiata']); break;
            }
            if ($existing) {
                gdrcd_query("DELETE FROM role_session_shin WHERE id_role = $id_role AND pg_name = '$login_f'");
                echo json_encode(['success' => true, 'action' => 'unflagged']);
            } else {
                gdrcd_query("INSERT INTO role_session_shin (id_role, pg_name) VALUES ($id_role, '$login_f')");
                echo json_encode(['success' => true, 'action' => 'flagged']);
            }
            break;

        case 'awardShin':  // Staff: assegna shin alle giocate flaggate
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); break; }
            $login_f  = gdrcd_filter('in', $_SESSION['login']);
            $id_roles = array_filter(array_map('intval', $data['id_roles'] ?? []), fn($v) => $v > 0);
            if (empty($id_roles)) { echo json_encode(['success' => false, 'message' => 'Nessuna giocata selezionata']); break; }

            $ids_sql = implode(',', $id_roles);
            $res_flags = gdrcd_query("SELECT id_role, pg_name FROM role_session_shin WHERE id_role IN ($ids_sql) AND awarded_at IS NULL", 'result');
            $awarded = 0;
            while ($r = gdrcd_query($res_flags, 'fetch')) {
                $pg  = gdrcd_filter('in', $r['pg_name']);
                $rid = (int)$r['id_role'];
                gdrcd_query("UPDATE role_session_shin SET awarded_at = NOW(), awarded_by = '$login_f' WHERE id_role = $rid AND pg_name = '$pg'");
                gdrcd_query("UPDATE personaggio SET shin = shin + 1 WHERE nome = '$pg'");

                // Registra l'assegnazione in Punti (stesso pattern di awardExperience()): senza
                // questo insert lo shin aggiornava solo il totale su personaggio, ma non compariva
                // mai nello storico di scheda_px/scheda_px_shin, che legge solo dalla tabella Punti.
                $nome_luogo = gdrcd_query("SELECT mappa.nome FROM role_sessions rs JOIN mappa ON mappa.id = rs.location WHERE rs.id_role = $rid")['nome'] ?? '';
                $resoconto  = 'Shin per la giocata' . ($nome_luogo !== '' ? " - $nome_luogo" : '');
                gdrcd_query("INSERT INTO Punti (nome, shin, data_evento, commento) VALUES ('$pg', '1', NOW(), '" . gdrcd_filter('in', $resoconto) . "')");

                $awarded++;
            }
            echo json_encode(['success' => true, 'message' => "$awarded shin assegnati", 'awarded' => $awarded]);
            break;

        case 'getRoleLog':
            $id_role  = isset($_GET['id']) ? (int)trim($_GET['id']) : 0;
            if (!$id_role) { echo json_encode(['success' => false, 'message' => 'ID mancante']); break; }

            $is_staff = isAdminMasterMod($_SESSION);
            $login_f  = gdrcd_filter('in', $_SESSION['login']);

            // Verifica accesso: deve aver partecipato o essere staff
            if (!$is_staff) {
                $check = gdrcd_query("SELECT 1 FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login_f' LIMIT 1");
                if (!$check) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); break; }
            }

            $role_row = gdrcd_query("SELECT rs.*, m.nome FROM role_sessions rs LEFT JOIN mappa m ON rs.location = m.id WHERE rs.id_role = $id_role");
            if (!$role_row) { echo json_encode(['success' => false, 'message' => 'Giocata non trovata']); break; }

            $r_start    = $role_row['start'];
            $r_end      = $role_row['end'];
            $r_location = (int)$role_row['location'];
            $end_sql    = $r_end ? "AND ora <= '$r_end'" : "AND ora <= NOW()";

            $role_info = [
                'id'           => $id_role,
                'luogo'        => gdrcd_filter('out', $role_row['nome']),
                'luogo_id'     => $r_location,
                'data'         => date('Y-m-d', strtotime($r_start)),
                'oraInizio'    => date('H:i', strtotime($r_start)),
                'oraFine'      => $r_end ? date('H:i', strtotime($r_end)) : null,
                'totTurni'     => (int)$role_row['turn'],
                'partecipanti' => getRolePgs($id_role, false),
                'inCorso'      => $r_end === null,
            ];

            $msg_result = gdrcd_query(
                "SELECT tipo, mittente, destinatario, ora, testo FROM chat
                 WHERE stanza = $r_location AND ora >= '$r_start' $end_sql
                 ORDER BY ora ASC",
                'result'
            );

            $messages = [];
            while ($msg = gdrcd_query($msg_result, 'fetch')) {
                $tipo = $msg['tipo'];

                // Sussurri: solo mittente, destinatario o staff
                if ($tipo === 'S' && !$is_staff
                    && $msg['mittente'] !== $_SESSION['login']
                    && $msg['destinatario'] !== $_SESSION['login']) continue;

                $testo = gdrcd_filter('out', $msg['testo']);

                // Colora il dialogo tra parentesi/capovolta
                if (in_array($tipo, ['P', 'A', 'M', 'G', 'X'])) {
                    $testo = str_replace(['[', ']', '«', '»'],
                        ['[<span class="rl-dialog">', '</span>]',
                         '«<span class="rl-dialog">', '</span>»'],
                        $testo);
                }
                // Colore natura per master/fato globale
                if (in_array($tipo, ['M', 'G', 'X'])) {
                    $testo = str_replace(['{', '}'], ['<span class="rl-nature">', '</span>'], $testo);
                }
                // Sottolinea nome utente corrente
                if (in_array($tipo, ['P', 'A', 'M'])) {
                    $testo = gdrcd_chatme($_SESSION['login'], $testo);
                }

                $messages[] = [
                    'tipo'         => $tipo,
                    'mittente'     => gdrcd_filter('out', $msg['mittente']),
                    'destinatario' => gdrcd_filter('out', $msg['destinatario']),
                    'ora'          => date('H:i', strtotime($msg['ora'])),
                    'testo'        => $testo,
                ];
            }

            echo json_encode(['success' => true, 'role' => $role_info, 'messages' => $messages, 'login' => $_SESSION['login']]);
            break;

        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>