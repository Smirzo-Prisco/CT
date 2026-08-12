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

                // Chiudo la role appena non restano pg reali, senza aspettare che i png
                // vengano espulsi uno per uno: endRoleSession() li elimina comunque.
                if(!pgIsInRole('', $_SESSION['luogo'], true, true)) endRoleSession($_SESSION['luogo']);

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
            // Solo lettura: richiude subito il lock di sessione, non serve tenerlo
            // per tutta la query (vedi getPgAllRoles per lo stesso ragionamento).
            session_write_close();
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
            // Solo lettura di $_SESSION: richiude subito il lock, non serve tenerlo per
            // tutte le query di questo case (era una delle chiamate piu' lunghe della
            // pagina, e teneva bloccate tutte le altre richieste sulla stessa sessione).
            session_write_close();
            ensureQuestSchema();
            $is_staff    = isAdminMasterMod($_SESSION);
            $login_f     = gdrcd_filter('in', $_SESSION['login']);
            $pg_param    = isset($_GET['pg'])    ? gdrcd_filter('in', trim($_GET['pg'])) : '';
            $gilda_param = ($is_staff && isset($_GET['gilda'])) ? (int)trim($_GET['gilda']) : null;

            $show_all   = $is_staff && $pg_param === 'all';
            $show_gilda = $is_staff && $gilda_param !== null;

            if ($show_gilda) {
                // Giocate di tutti i PG della razza selezionata, sia come giocatori
                // che come master (il master non passa da role_session_players: narra
                // in chat con tipo M/I/Y senza mai "unirsi" alla giocata).
                $gilda_cond = ($gilda_param === 0)
                    ? "COALESCE(id_gilda, 0) <= 0"
                    : "id_gilda = $gilda_param";
                // La UNION va incapsulata in una subquery derivata (FROM), non lasciata
                // dentro l'IN diretto: altrimenti MySQL la tratta come DEPENDENT SUBQUERY
                // e la rivaluta per ogni riga della query esterna (query da ~4s invece di
                // ~25ms, confermato con EXPLAIN — role_session_players compare sia dentro
                // la subquery che nel JOIN esterno, ed e' quello che innesca la dipendenza).
                $where = "WHERE role_sessions.id_role IN (
                    SELECT id_role FROM (
                        SELECT id_role FROM role_session_players WHERE pg_name IN (SELECT nome FROM personaggio WHERE $gilda_cond)
                        UNION
                        SELECT id_role FROM chat WHERE tipo IN ('M','I','Y') AND mittente IN (SELECT nome FROM personaggio WHERE $gilda_cond)
                        UNION
                        SELECT id_role FROM role_sessions WHERE master IN (SELECT nome FROM personaggio WHERE $gilda_cond)
                    ) AS matched_ids
                )";
            } elseif ($show_all) {
                $where = '';
            } else {
                // Stesso ragionamento: includo anche le giocate masterate senza essersi uniti.
                $pg_filter = ($is_staff && $pg_param !== '') ? $pg_param : $login_f;
                // Stessa incapsulazione in subquery derivata di cui sopra, stesso motivo.
                $where = "WHERE role_sessions.id_role IN (
                    SELECT id_role FROM (
                        SELECT id_role FROM role_session_players WHERE pg_name = '$pg_filter'
                        UNION
                        SELECT id_role FROM chat WHERE tipo IN ('M','I','Y') AND mittente = '$pg_filter'
                        UNION
                        SELECT id_role FROM role_sessions WHERE master = '$pg_filter'
                    ) AS matched_ids
                )";
            }

            $query = "SELECT role_sessions.id_role, role_sessions.location, role_sessions.start,
                             role_sessions.end, role_sessions.turn, role_sessions.is_quest,
                             role_sessions.quest_recap_thread_id, role_sessions.master, mq.titolo AS quest_titolo,
                             mappa.nome, mappa.id as luogo_id
                      FROM role_sessions
                      LEFT JOIN mappa ON role_sessions.location = mappa.id
                      LEFT JOIN messaggio_quest mq ON mq.id_messaggio = role_sessions.quest_recap_thread_id
                      INNER JOIN role_session_players ON role_sessions.id_role = role_session_players.id_role
                      $where
                      GROUP BY role_sessions.id_role
                      ORDER BY role_sessions.start DESC";
            $result = gdrcd_query($query, 'result');
            $roles = [];
            $tracked_master_map = []; // id_role => master, solo per le righe che lo hanno già tracciato

            while ($row = gdrcd_query($result, 'fetch')) {
                $roles[] = [
                    'id'           => (int)$row['id_role'],
                    'luogo_id'     => $row['luogo_id'],
                    'luogo'        => $row['nome'],
                    'data'         => date('Y-m-d', strtotime($row['start'])),
                    'oraInizio'    => date('H:i', strtotime($row['start'])),
                    'oraFine'      => $row['end'] !== null ? date('H:i', strtotime($row['end'])) : '',
                    'totTurni'     => (int)$row['turn'],
                    'partecipanti' => [], // popolato in batch subito dopo il ciclo, vedi sotto
                    'inCorso'      => $row['end'] === null,
                    'isQuest'      => !empty($row['is_quest']),
                    // >0 esclude sia NULL (mai generato) sia il valore sentinella -1 usato
                    // brevemente da saveQuestRecap per "prenotare" la generazione.
                    'questRecapThreadId' => ((int)($row['quest_recap_thread_id'] ?? 0)) > 0 ? (int)$row['quest_recap_thread_id'] : null,
                    'questRecapTitolo'   => $row['quest_titolo'] ?? '',
                    'icona'        => 'fas fa-globe',
                    'my_shin'      => 'none',
                    'pending_count' => 0,
                ];
                if (!empty($row['master'])) $tracked_master_map[(int)$row['id_role']] = $row['master'];
            }

            // Shin enrichment: flag/award status per giocata
            if (!empty($roles)) {
                $all_ids = implode(',', array_map('intval', array_column($roles, 'id')));

                // Partecipanti (giocatori + master) per tutte le giocate in due query totali
                // invece di due per ogni riga (era un N+1 che, su "chat" senza indice su
                // id_role/mittente, faceva una scansione completa della tabella per ogni giocata).
                $players_map = [];
                $res_pl = gdrcd_query("SELECT DISTINCT id_role, pg_name FROM role_session_players WHERE id_role IN ($all_ids)", 'result');
                while ($r = gdrcd_query($res_pl, 'fetch')) $players_map[(int)$r['id_role']][] = $r['pg_name'];

                // Master: prima quello già tracciato in role_sessions.master (nessuna query in
                // più, e' gia' nella riga), poi la scansione di chat SOLO per le giocate ancora
                // senza master tracciato (quest vecchie, o giocate mai diventate quest) — con
                // 'System' escluso: e' il mittente sentinella dei messaggi automatici (es. avviso
                // polizia in gestionePoliziaAutomatica(), tipo 'M' ma non un master reale).
                $masters_map = [];
                foreach ($tracked_master_map as $rid => $master_name) $masters_map[$rid] = [$master_name];

                $untracked_ids = array_diff(array_column($roles, 'id'), array_keys($tracked_master_map));
                if (!empty($untracked_ids)) {
                    $untracked_ids_str = implode(',', array_map('intval', $untracked_ids));
                    $res_ms = gdrcd_query("SELECT DISTINCT id_role, mittente FROM chat WHERE id_role IN ($untracked_ids_str) AND tipo IN ('M', 'I', 'Y') AND mittente IS NOT NULL AND mittente NOT IN ('', 'System')", 'result');
                    while ($r = gdrcd_query($res_ms, 'fetch')) $masters_map[(int)$r['id_role']][] = $r['mittente'];
                }

                foreach ($roles as &$role) {
                    $players = $players_map[$role['id']] ?? [];
                    $masters = $masters_map[$role['id']] ?? [];
                    $names   = array_values(array_unique(array_merge($players, $masters)));
                    $role['partecipanti'] = array_map(
                        fn($nome) => ['nome' => $nome, 'isMaster' => in_array($nome, $masters, true)],
                        $names
                    );
                }
                unset($role);

                $my_shin_map = [];
                $res_my = gdrcd_query("SELECT id_role, awarded_at FROM role_session_shin WHERE id_role IN ($all_ids) AND pg_name = '$login_f'", 'result');
                while ($r = gdrcd_query($res_my, 'fetch'))
                    $my_shin_map[(int)$r['id_role']] = $r['awarded_at'] ? 'awarded' : 'pending';

                $pending_map = [];
                $shin_status_map = [];
                if ($is_staff) {
                    $res_p = gdrcd_query("SELECT id_role, pg_name, awarded_at FROM role_session_shin WHERE id_role IN ($all_ids)", 'result');
                    while ($r = gdrcd_query($res_p, 'fetch')) {
                        $rid = (int)$r['id_role'];
                        $status = $r['awarded_at'] ? 'awarded' : 'pending';
                        $shin_status_map[$rid][$r['pg_name']] = $status;
                        if ($status === 'pending') $pending_map[$rid] = ($pending_map[$rid] ?? 0) + 1;
                    }
                }

                foreach ($roles as &$role) {
                    $role['my_shin']       = $my_shin_map[$role['id']] ?? 'none';
                    $role['pending_count'] = $pending_map[$role['id']] ?? 0;
                    if ($is_staff) $role['shin_status'] = $shin_status_map[$role['id']] ?? new stdClass();
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
            ensureQuestSchema();
            $login_f = gdrcd_filter('in', $_SESSION['login']);
            $id_role = isset($data['id_role']) ? (int)$data['id_role'] : 0;
            if (!$id_role) { echo json_encode(['success' => false, 'message' => 'ID mancante']); break; }

            $part = gdrcd_query("SELECT 1 FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login_f' LIMIT 1");
            if (!$part) { echo json_encode(['success' => false, 'message' => 'Non hai partecipato a questa giocata']); break; }

            // Le quest non passano dal flag/award manuale: lo shin, se previsto, lo assegna
            // direttamente lo staff con altri strumenti. is_quest=1 blocca la richiesta anche
            // se il resoconto non è ancora stato generato (quest_recap_thread_id ancora NULL).
            if (getQuestRoleRow($id_role) !== null) {
                echo json_encode(['success' => false, 'message' => 'Le giocate Quest non prevedono la richiesta shin']); break;
            }

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
                $nome_luogo = getRoleLocationName($rid);
                $resoconto  = 'Shin per la giocata' . ($nome_luogo !== '' ? " - $nome_luogo" : '');
                gdrcd_query("INSERT INTO Punti (nome, shin, data_evento, commento) VALUES ('$pg', '1', NOW(), '" . gdrcd_filter('in', $resoconto) . "')");

                $awarded++;
            }
            echo json_encode(['success' => true, 'message' => "$awarded shin assegnati", 'awarded' => $awarded]);
            break;

        case 'rejectShin':  // Staff: rifiuta la richiesta shin di un singolo giocatore
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']); break; }
            $id_role = isset($data['id_role']) ? (int)$data['id_role'] : 0;
            $pg_name = isset($data['pg_name']) ? gdrcd_filter('in', trim($data['pg_name'])) : '';
            if (!$id_role || $pg_name === '') { echo json_encode(['success' => false, 'message' => 'Dati mancanti']); break; }

            $existing = gdrcd_query("SELECT awarded_at FROM role_session_shin WHERE id_role = $id_role AND pg_name = '$pg_name'");
            if (!$existing) { echo json_encode(['success' => false, 'message' => 'Richiesta non trovata']); break; }
            if ($existing['awarded_at']) { echo json_encode(['success' => false, 'message' => 'Questa richiesta è già stata premiata']); break; }

            gdrcd_query("DELETE FROM role_session_shin WHERE id_role = $id_role AND pg_name = '$pg_name'");
            echo json_encode(['success' => true]);
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

            // Filtro per id_role (non più per stanza+intervallo date): due giocate diverse
            // nello stesso luogo condividono spesso lo stesso `end` (es. chiusura batch di
            // sessioni rimaste aperte), quindi un filtro temporale su `chat.ora` finiva per
            // includere anche i messaggi della giocata successiva fatta nello stesso posto.
            // Ogni riga di chat porta già l'id_role corretto (vedi chatInsertMessage()).
            $msg_result = gdrcd_query(
                "SELECT tipo, mittente, destinatario, ora, testo FROM chat
                 WHERE id_role = $id_role
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

                // Niente gdrcd_filter('out') qui: usa htmlentities e trasformerebbe in
                // testo letterale l'HTML reale gia' presente in chat.testo (es. <i>/<u>
                // dei messaggi di sistema per tiri/combattimento, vedi get_chat_messages
                // in api_chat.php) invece di interpretarlo. gdrcd_html_filter toglie solo
                // gli elementi pericolosi (script/iframe/on*), preservando l'HTML legittimo.
                $testo = gdrcd_html_filter($msg['testo']);

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

        case 'getQuestRecapData':  // Staff: dati precompilati per la modale "Assegna punti quest"
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); break; }
            ensureQuestSchema();
            $id_role = isset($_GET['id_role']) ? (int)$_GET['id_role'] : 0;
            if (!$id_role) { echo json_encode(['success' => false, 'message' => 'ID mancante']); break; }

            $role = getQuestRoleRow($id_role);
            if ($role === null) {
                echo json_encode(['success' => false, 'message' => 'Giocata non trovata o non contrassegnata come Quest']);
                break;
            }

            // > 0 esclude sia NULL (mai generato) sia il valore sentinella -1 usato da
            // saveQuestRecap per "prenotare" la generazione: per la durata di quel breve
            // lock, la modale deve continuare a mostrare il form, non un finto thread -1.
            $recap_thread_id = (int)($role['quest_recap_thread_id'] ?? 0);

            echo json_encode([
                'success'               => true,
                'location'              => getRoleLocationName($id_role),
                'partecipanti'          => getRolePgs($id_role, false, true), // esclude i png: punti solo ai pg reali
                'quest_recap_thread_id' => $recap_thread_id > 0 ? $recap_thread_id : null,
            ]);
            break;

        case 'saveQuestRecap':  // Staff: crea il post quest nel forum con riassunto generato dall'AI
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); break; }
            ensureQuestSchema();
            $id_role = isset($data['id_role']) ? (int)$data['id_role'] : 0;
            if (!$id_role) { echo json_encode(['success' => false, 'message' => 'ID mancante']); break; }

            $role = getQuestRoleRow($id_role);
            if ($role === null) {
                echo json_encode(['success' => false, 'message' => 'Giocata non trovata o non contrassegnata come Quest']);
                break;
            }
            if (!empty($role['quest_recap_thread_id'])) {
                echo json_encode(['success' => false, 'message' => 'Il resoconto è già stato generato per questa giocata']);
                break;
            }

            $titolo    = trim($data['titolo']      ?? '');
            $tipologia = trim($data['tipologia']   ?? '');
            $cons      = trim($data['conseguenze'] ?? '');
            $note      = trim($data['note']        ?? '');
            $valu      = trim($data['valutazioni'] ?? '');
            $pg_punti  = is_array($data['partecipanti_punti'] ?? null) ? $data['partecipanti_punti'] : [];
            // Non fidarsi del client: tiene solo i pg reali della giocata, escludendo i png
            // anche se qualcuno arrivasse comunque nel payload inviato.
            $pg_reali_punti = getRolePgs($id_role, false, true);
            $pg_punti  = array_values(array_filter($pg_punti, fn($p) => in_array($p['nome'] ?? '', $pg_reali_punti, true)));

            if ($titolo === '') { echo json_encode(['success' => false, 'message' => 'Titolo mancante']); break; }

            // Blocco atomico: "prenota" subito la generazione con un valore sentinella (-1),
            // solo se ancora NULL. Senza questo, due richieste ravvicinate (doppio click, o due
            // finestre) potrebbero superare entrambe il controllo sopra prima che la UPDATE finale
            // scriva il thread_id, creando due post duplicati. Da qui in poi il lavoro è "pesante"
            // (chiamata AI + INSERT multipli), motivo per cui il lock va preso appena prima.
            gdrcd_query("UPDATE role_sessions SET quest_recap_thread_id = -1 WHERE id_role = $id_role AND quest_recap_thread_id IS NULL");
            if (gdrcd_query('', 'affected') === 0) {
                echo json_encode(['success' => false, 'message' => 'Il resoconto è già stato generato per questa giocata']);
                break;
            }

            // Partecipanti e location sono campi "automatici": ricalcolati qui lato server,
            // non fidandosi di quanto arriva dal client.
            $location     = getRoleLocationName($id_role);
            $partecipanti = implode(', ', getRolePgs($id_role, false));

            $riassunto = generateQuestRiassunto($id_role);

            // Sezione fissa "Resoconti e Quest" (id_araldo=10), la stessa usata dal composer manuale nel forum
            $thread_id = createQuestPost(10, -1, $titolo, $tipologia, $partecipanti, $location, $riassunto, $cons, $note, $valu, $pg_punti, $_SESSION['login']);
            if ($thread_id === null) {
                // Sblocca: senza questo la riga resterebbe agganciata a -1 per sempre,
                // segnalata come "già generata" senza che esista alcun post.
                gdrcd_query("UPDATE role_sessions SET quest_recap_thread_id = NULL WHERE id_role = $id_role");
                echo json_encode(['success' => false, 'message' => 'Impossibile pubblicare nella bacheca Resoconti e Quest']);
                break;
            }

            gdrcd_query("UPDATE role_sessions SET quest_recap_thread_id = $thread_id WHERE id_role = $id_role");

            echo json_encode(['success' => true, 'thread_id' => $thread_id]);
            break;

        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>