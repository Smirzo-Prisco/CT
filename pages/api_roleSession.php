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
            $res_pgs = gdrcd_query("SELECT DISTINCT pg_name FROM role_session_players ORDER BY pg_name ASC", 'result');
            $pg_names = [];
            while ($r = gdrcd_query($res_pgs, 'fetch')) $pg_names[] = $r['pg_name'];
            echo json_encode(['success' => true, 'pgs' => $pg_names]);
            break;

        case 'getPgAllRoles':
            $is_staff   = isAdminMasterMod($_SESSION);
            $login_f    = gdrcd_filter('in', $_SESSION['login']);
            $pg_param   = isset($_GET['pg']) ? gdrcd_filter('in', trim($_GET['pg'])) : '';
            $show_all   = $is_staff && $pg_param === 'all';
            $pg_filter  = $show_all ? null : ($is_staff && $pg_param !== '' ? $pg_param : $login_f);

            $where = $pg_filter !== null
                ? "WHERE role_session_players.pg_name = '$pg_filter'"
                : '';

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
                ];
            }

            echo json_encode([
                'success'      => true,
                'roles'        => $roles,
                'is_staff'     => $is_staff,
                'current_user' => $_SESSION['login'],
            ]);
            break;
        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>