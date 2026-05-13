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

                $id_role = locationActiveRole($location); // Recupera l'eventuale role attiva nella chat

                // Se nella chat NON ci sono giocate attive, avvio una nuova role
                if(!$id_role) {
                    gdrcd_query("INSERT INTO role_sessions (`location`, `start`) VALUES ($location, NOW())");
                    $id_role = gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
                }
                
                // In ogni caso aggiungo il pg alla role
                addPgToRole($id_role, $login, $location);
                
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
            $location = $_SESSION['luogo'];
            $id_role = locationActiveRole($location);
            $users = getRolePgs($id_role, true); // Prendo tutti i pg della role, esclusi quelli che hanno chiuso il turno o sono usciti
            
            if(!empty($users)) $response = array('success' => true, 'message' => "Utenti della role!", 'users' => $users, 'id_role' => $id_role);
            else $response = array('success' => false, 'message' => "Errore! Nessun utente nella role. Sta cosa è impossibile!", 'users' => $users, 'id_role' => $id_role);

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
            $location = $_SESSION['luogo'];
            $id_role = locationActiveRole($location);
            
            if(is_numeric($id_role)) {
                closeTurn($id_role, $location);
                $response = array('success' => true, 'message' => "Turno chiuso forzatamente");
            } else $response = array('success' => false, 'message' => "Nessuna role attiva nella chat, impossibile chiudere il turno");

            echo json_encode($response);
            break;
            exit;
        case 'getPgAllRoles': // Prende tutte le role del pg - ATTENZIONARE! Verificare la presenza
            $where = isAdminMasterMod($_SESSION) ? "" : "WHERE role_session_players.pg_name = '".$_SESSION['login']."'";
            
            $query = "SELECT role_sessions.*, mappa.nome, mappa.id as luogo_id FROM role_sessions 
                        LEFT JOIN mappa ON role_sessions.location = mappa.id 
                        INNER JOIN role_session_players ON role_sessions.id_role = role_session_players.id_role 
                        $where";
            $result = gdrcd_query($query, 'result');
            $roles = [];
            
            while($row = gdrcd_query($result, 'fetch')) {
                $role = [];

                $role['id'] = (int)$row['id_role'];
                $role['luogo_id'] = $row['luogo_id'];
                $role['luogo'] = $row['nome'];
                $role['data'] = date("l j F Y", strtotime($row['start']));
                $role['oraInizio'] = date("H:i", strtotime($row['start']));
                $role['oraFine'] = $row['end'] !== null ? date("H:i", strtotime($row['end'])) : '';
                $role['totTurni'] = (int)$row['turn'];
                $role['partecipanti'] = getRolePgs($row['id_role'], false);
                $role['inCorso'] = $row['end'] === null ? true : false;
                $role['icona'] = 'fas fa-globe';

                array_push($roles, $role);
            }
            
            if(!empty($roles)) echo json_encode(array('success' => true, 'message' => "Giocate del pg", 'roles' => $roles));
            else echo json_encode(array('success' => false, 'message' => "Errore! Nessuna giocata trovata per il pg.", 'query' => $query));

            break;
        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>