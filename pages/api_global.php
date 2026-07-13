<?php
if(isset($_GET['op']) && $_GET['op'] != '') {
    session_start();
    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');
    
    // IMPORTANTE: Solo per le richieste AJAX
    header('Content-Type: application/json');

    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato']);
        exit;
    }
    session_write_close();

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    /*********************  Recupero i dati dell'utente che voglio modificare   */
    switch ($_GET['op']) {
        //  GLOBALI
        case 'getPresentiOnline':
            session_start();

            if (!isset($_SESSION['login'])) {
                echo json_encode(['error' => 'Non autorizzato']);
                exit();
            }
            
            // CODICE ESISTENTE PER AGGIORNAMENTO REFRESH
            $login = gdrcd_filter('in', $_SESSION['login']);
            $is_staff = ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['master'] == 1);
            $query = "UPDATE personaggio SET ultimo_refresh = NOW()";

            if (isset($_REQUEST['disponibile'])) {
                $disponibile = gdrcd_filter('num', $_REQUEST['disponibile']);
                $query .= ", disponibile = " . $disponibile;
            } elseif (isset($_REQUEST['invisibile']) && $is_staff) {
                $invisibile = gdrcd_filter('num', $_REQUEST['invisibile']);
                $query .= ", is_invisible = " . $invisibile;
            }
            $query .= " WHERE nome = '" . $login . "'";
            gdrcd_query($query);

            // CARICA LISTA PRESENTI (stesso codice della tua pagina)
            $query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.is_invisible, mappa.stanza_apparente, mappa.nome as luogo FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE (personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW()) AND personaggio.ultimo_luogo = ".$_SESSION['luogo']." AND personaggio.ultima_mappa= ".$_SESSION['mappa']." ORDER BY personaggio.is_invisible, personaggio.ultimo_luogo, personaggio.nome";
            $result = gdrcd_query($query, 'result');
        
            $users = [];
            while($record = gdrcd_query($result, 'fetch')) {
                if(($record['is_invisible'] == 0) || ($record['nome'] == $_SESSION['login'])) {
                    // DETERMINA ICONA RAZZA (stessa logica del tuo codice)
                    $race_icon = 'Png.png';
                    if ($record['id_razza'] >= 1000 && $record['id_razza'] < 2000) $race_icon = 'Marte.png';
                    else if ($record['id_razza'] >= 2000 && $record['id_razza'] < 3000) $race_icon = 'Mercurio.png';
                    else if ($record['id_razza'] >= 3000 && $record['id_razza'] < 4000) $race_icon = 'Luna.png';
                    else if ($record['id_razza'] >= 4000 && $record['id_razza'] < 5000) $race_icon = 'Giove.png';
                    else if ($record['id_razza'] >= 5000 && $record['id_razza'] < 6000) $race_icon = 'Venere.png';
                    else if ($record['id_razza'] >= 6000 && $record['id_razza'] < 7000) $race_icon = 'Urano.png';
                    else if ($record['id_razza'] >= 7000 && $record['id_razza'] < 8000) $race_icon = 'Nettuno.png';
                    else if ($record['id_razza'] >= 8000 && $record['id_razza'] < 9000) $race_icon = 'Plutone.png';
                    else if ($record['id_razza'] >= 9000 && $record['id_razza'] < 10000) $race_icon = 'Saturno.png';
                    else if ($record['id_razza'] >= 10000 && $record['id_razza'] < 11000) $race_icon = 'Terra.png';
                    else if ($record['id_razza'] == 11000) $race_icon = 'Nebbia.png';
                    
                    $users[] = [
                        'nome' => $record['nome'],
                        'cognome' => $record['cognome'],
                        'permessi' => $record['permessi'],
                        'sesso' => $record['sesso'],
                        'id_razza' => $record['id_razza'],
                        'disponibile' => $record['disponibile'],
                        'is_invisible' => $record['is_invisible'],
                        'race_icon' => $race_icon,
                        'luogo' => $record['luogo'],
                        'stanza_apparente' => $record['stanza_apparente']
                    ];
                }
            }
            gdrcd_query($result, 'free');
            
            // CONTA UTENTI ONLINE
            $record = gdrcd_query("SELECT COUNT(*) AS numero FROM personaggio WHERE personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW() AND personaggio.is_invisible = 0");
            $total_online = $record['numero'];
            
            // OUTPUT JSON
            echo json_encode([
                'users' => $users,
                'total_online' => $total_online
            ]);
            
            exit();
            break;
        // -------------------------------------------------------------------------
        // METEO — restituisce dati meteo (rigenera se scaduti da >24h)
        // -------------------------------------------------------------------------
        // -------------------------------------------------------------------------
        // EVENTS_TODAY — verifica se ci sono eventi/appuntamenti oggi per l'utente
        // Usato da MenuIcons.jsx per l'icona Calendario animata
        // -------------------------------------------------------------------------
        case 'getOpenRoles': // Icona giocate: si illumina se il personaggio loggato ha una role ancora aperta
            $login_f = gdrcd_filter('in', $_SESSION['login']);
            $n = (int)gdrcd_query("
                SELECT COUNT(*) AS n
                FROM role_session_players rsp
                INNER JOIN role_sessions rs ON rsp.id_role = rs.id_role
                WHERE rsp.pg_name = '$login_f'
                  AND rsp.end IS NULL
                  AND rs.end IS NULL
            ")['n'];
            echo json_encode(['success' => true, 'has_open_roles' => $n > 0]);
            break;

        case 'events_today':
            $login_f = gdrcd_filter('in', $_SESSION['login']);
            $sql = gdrcd_query("SELECT COUNT(*) AS n FROM appuntamenti
                WHERE (autore = '$login_f' OR destinatario = '$login_f' OR titolo = 'Quest' OR titolo = 'Evento')
                AND DATE(FROM_UNIXTIME(str_data)) = CURDATE()", 'result');
            $row = gdrcd_query($sql, 'fetch');
            echo json_encode(['success' => true, 'has_events' => (int)$row['n'] > 0]);
            break;

        case 'meteo':
            $meteoQ = gdrcd_query("SELECT * FROM meteo WHERE id = 1", 'result');
            $meteo  = gdrcd_query($meteoQ, 'fetch');

            if ($meteo) {
                $ora    = new DateTime();
                $ultimo = new DateTime($meteo['datetime_aggiornamento']);
                $stale  = ($ora->diff($ultimo)->days >= 1) || ($ultimo->format('Y-m-d') != $ora->format('Y-m-d'));

                if ($stale) {
                    $mese = (int)date('n');

                    // Usa closures (variabili) invece di named functions per evitare
                    // "Cannot redeclare" se il file viene eseguito più volte nel processo
                    $condFn = function($m) {
                        if (in_array($m, [12,1,2]))  { $c = ['nuvoloso','pioggia','neve','sole_nebbia','sole_nuvoloso','temporale']; }
                        elseif (in_array($m, [3,4,5])){ $c = ['sole','nuvoloso','sole_nuvoloso','pioggia','temporale']; }
                        elseif (in_array($m, [6,7,8])){ $c = ['sole','nuvoloso','pioggia','temporale']; }
                        else                          { $c = ['sole','sole_nuvoloso','nuvoloso','pioggia','sole_nebbia']; }
                        return $c[array_rand($c)];
                    };
                    $tempFn = function($m, $t = 'giorno') {
                        if (in_array($m, [12,1,2]))  return $t === 'giorno' ? rand(-5,10)  : rand(-10,5);
                        if (in_array($m, [3,4,5]))   return $t === 'giorno' ? rand(10,20)  : rand(5,15);
                        if (in_array($m, [6,7,8]))   return $t === 'giorno' ? rand(25,35)  : rand(15,25);
                        if (in_array($m, [9,10,11])) return $t === 'giorno' ? rand(10,20)  : rand(5,15);
                        return rand(10,25);
                    };
                    $ventoFn = function($m) {
                        if (in_array($m, [12,1,2]))  { $v = ['assente','brezza','medio','forte']; }
                        elseif (in_array($m, [3,4,5])){ $v = ['assente','brezza','medio']; }
                        elseif (in_array($m, [6,7,8])){ $v = ['brezza','medio','forte']; }
                        else                          { $v = ['assente','brezza','medio','forte']; }
                        return $v[array_rand($v)];
                    };

                    $mg = gdrcd_filter('in', $condFn($mese));
                    $mn = gdrcd_filter('in', $condFn($mese));
                    $tg = $tempFn($mese, 'giorno');
                    $tn = $tempFn($mese, 'notte');
                    $vg = gdrcd_filter('in', $ventoFn($mese));
                    $vn = gdrcd_filter('in', $ventoFn($mese));

                    gdrcd_query("UPDATE meteo SET
                        meteo_giorno_precedente  = meteo_giorno_attuale,
                        meteo_notte_precedente   = meteo_notte_attuale,
                        temperatura_giorno_precedente = temperatura_giorno_attuale,
                        temperatura_notte_precedente  = temperatura_notte_attuale,
                        vento_giorno_precedente  = vento_giorno_attuale,
                        vento_notte_precedente   = vento_notte_attuale,
                        meteo_giorno_attuale     = '$mg',
                        meteo_notte_attuale      = '$mn',
                        temperatura_giorno_attuale = $tg,
                        temperatura_notte_attuale  = $tn,
                        vento_giorno_attuale     = '$vg',
                        vento_notte_attuale      = '$vn',
                        datetime_aggiornamento   = NOW()
                        WHERE id = 1");

                    $meteoQ = gdrcd_query("SELECT * FROM meteo WHERE id = 1", 'result');
                    $meteo  = gdrcd_query($meteoQ, 'fetch');
                }
            }

            // Mappa condizioni notte → immagine corretta
            $notte_map = ['sole_nuvoloso'=>'luna_nuvoloso','sole_nebbia'=>'luna_nebbia','nuvoloso'=>'luna_nuvoloso','sole'=>'mezza_luna'];
            $img_notte_att  = $notte_map[$meteo['meteo_notte_attuale']]  ?? $meteo['meteo_notte_attuale'];
            $img_notte_prec = $notte_map[$meteo['meteo_notte_precedente']] ?? $meteo['meteo_notte_precedente'];

            echo json_encode([
                'success'    => true,
                'attuale'    => [
                    'giorno_img'  => $meteo['meteo_giorno_attuale'],
                    'notte_img'   => $img_notte_att,
                    'temp_max'    => (int)$meteo['temperatura_giorno_attuale'],
                    'temp_min'    => (int)$meteo['temperatura_notte_attuale'],
                    'vento_giorno'=> $meteo['vento_giorno_attuale'],
                    'vento_notte' => $meteo['vento_notte_attuale'],
                ],
                'precedente' => [
                    'giorno_img'  => $meteo['meteo_giorno_precedente'],
                    'notte_img'   => $img_notte_prec,
                    'temp_max'    => (int)$meteo['temperatura_giorno_precedente'],
                    'temp_min'    => (int)$meteo['temperatura_notte_precedente'],
                    'vento_giorno'=> $meteo['vento_giorno_precedente'],
                    'vento_notte' => $meteo['vento_notte_precedente'],
                ],
            ]);
            break;

        case 'getMessages': // Recupero i messaggi DM
            session_start();

            // Inizializza variabile per messaggi istantanei
            if (empty($_SESSION['last_istant_message'])) $_SESSION['last_istant_message'] = 0;

            $login = gdrcd_filter('in', $_SESSION['login']);

            // --- Messaggi non letti individuali ---
            // Conta solo conversazioni che hanno almeno un messaggio visibile in lista
            // (stesso filtro di api_messages.php?op=list), per evitare che conversazioni
            // vuote o orfane con lettura=0 tengano l'icona animata in eterno.
            $row_individuali = gdrcd_query(gdrcd_query("
                SELECT COUNT(*) AS cnt FROM conversazioni_individuali c
                WHERE c.utente_nome = '$login' AND c.lettura = 0
                  AND EXISTS (
                      SELECT 1 FROM sms s
                      WHERE s.id_conversazione = c.id_conversazione
                        AND s.mittente_nome != s.destinatario_nome
                  )
            ", 'result'), 'fetch');
            $cntNewMessageIndividuali = $row_individuali['cnt'];

            // --- Messaggi non letti gruppi ---
            $row_gruppo = gdrcd_query(gdrcd_query("
                SELECT COUNT(*) AS cnt FROM partecipazione_gruppo pg
                WHERE pg.utente_nome = '$login' AND pg.lettura = 0
                  AND EXISTS (
                      SELECT 1 FROM sms s
                      WHERE s.gruppo_id = pg.gruppo_id
                        AND s.is_globale = 0
                  )
            ", 'result'), 'fetch');
            $cntNewMessageGruppo = $row_gruppo['cnt'];

            // --- Totale messaggi non letti ---
            $cntNewMessage = $cntNewMessageIndividuali + $cntNewMessageGruppo;
            $hasNewMessage = ($cntNewMessage > 0);

            $allowAudio = ($PARAMETERS['mode']['allow_audio'] === 'ON' ? true : false);

            echo json_encode(array(
                'success' => true,
                'message' => 'Nuovi messaggi?',
                'hasNew' => $hasNewMessage,
                'allowAudio' => $allowAudio
            ));

            break;
        case 'getChatOff': // Recupero i messaggi della chat off
            session_start();

            $hasNewMessage = gdrcd_query("SELECT COUNT(*) AS presenza FROM chat_letta WHERE nome ='".$_SESSION['login']."'")['presenza'] == 0 ? false : true;

            echo json_encode(array(
                'success' => true,
                'message' => 'Nuovi messaggi?',
                'hasNew' => $hasNewMessage
            ));

            break;
        case 'getForumUnread': // Icona forum: si illumina se c'e' almeno un thread non letto in una sezione accessibile
            $login_f = gdrcd_filter('in', $_SESSION['login']);
            $result = gdrcd_query("SELECT id_araldo, tipo, proprietari FROM araldo WHERE invisibile = 0", 'result');
            $hasUnread = false;
            while ($row = gdrcd_query($result, 'fetch')) {
                if (!can_access_section($row)) continue;
                $unread = gdrcd_query("SELECT COUNT(*) AS n
                    FROM messaggioaraldo ma
                    WHERE ma.id_araldo = {$row['id_araldo']}
                    AND ma.id_messaggio_padre = -1
                    AND ma.id_messaggio NOT IN (
                        SELECT thread_id FROM araldo_letto WHERE nome = '$login_f'
                    )");
                if ((int)$unread['n'] > 0) { $hasUnread = true; break; }
            }
            gdrcd_query($result, 'free');
            echo json_encode(['success' => true, 'has_unread' => $hasUnread]);
            break;
        // UTENTI
        case 'getUsr':  // Recupero i dati dell'utente
            if(!isset($_GET['name'])) {
                echo json_encode(['error' => 'Parametro mancante']);
                break;
            }
            
            $nome = gdrcd_filter('in', $_GET['name']);
            $result = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$nome'");
            
            if($result) echo json_encode($result);
            else echo json_encode(['error' => 'Non trovato']);

            break;
        case 'checkVolto':  // Controlla se esiste il volto del pg
            if(!isset($_GET['volto'])) {
                echo json_encode(['error' => 'Parametro mancante']);
                break;
            }
            
            $volto  = gdrcd_filter('in', strtolower($_GET['volto']));
            $query  = "SELECT volto FROM personaggio WHERE volto = '$volto'";
            $results = gdrcd_query($query, 'result');
            
            if(gdrcd_query($results, 'num_rows') > 0) {
                $querys = "SELECT nome, cognome from personaggio WHERE volto = '".strtolower($volto)."'";
                $rt = gdrcd_query($querys);
                
                // echo "<span style='color:red; text-transform: none;'>Volto utilizzato da ".$rt['nome']."&nbsp;".$rt['cognome']."</span>";
                echo json_encode(["Volto utilizzato da ".$rt['nome']." ".$rt['cognome']]);
            } else {
                // echo "<span style='color:green; text-transform: none;'>Volto disponibile</span>";
                echo json_encode(["Volto disponibile"]);
            }

            break;
        case 'savePg':  // Salvo i dati del pg
            try {
                // Mappatura campi form -> database
                $fieldMappings = array(
                    'nome' => 'nome',
                    'cognome' => 'cognome',
                    'eta' => 'eta',
                    'email' => 'email',
                    'luogo' => 'natoa',
                    'volto' => 'volto',
                    'musica' => 'url_media',
                    'alias' => 'nickname',
                    'img' => 'url_img',
                    'imgchat' => 'url_img_chat',
                    'background' => 'principale',
                    'storia' => 'storia',
                    'dice' => 'descrizione',
                    'off' => 'off',
                    'salute' => 'salute',
                    'integrita' => 'integrita',
                    'shin' => 'shin',
                    'soldi' => 'soldi',
                    'notorieta' => 'notorieta',
                    'particolari' => 'particolari',
                    'note_master' => 'note_fato',
                    'razza' => 'id_razza',
                    'suoni' => 'suoni'
                );
                
                $set_parts = array();
                $campi_modificati = 0;
                
                // Processa solo i campi che sono stati inviati (modificati)
                foreach ($fieldMappings as $formField => $dbField) {
                    if (isset($_POST[$formField])) {
                        // Applica il filtro appropriato
                        if (in_array($formField, array('eta', 'salute', 'integrita', 'shin', 'soldi', 'notorieta', 'razza'))) {
                            $value = gdrcd_filter('num', $_POST[$formField]);
                        } elseif ($formField === 'email') {
                            $value = gdrcd_filter('email', $_POST[$formField]);
                        } elseif ($formField === 'suoni') {
                            $value = ($_POST[$formField] == '1') ? 1 : 0;
                        } else {
                            $value = gdrcd_filter('in', $_POST[$formField]);
                        }
                        
                        $set_parts[] = "$dbField = '$value'";
                        $campi_modificati++;
                    }
                }

                // Verifica che ci siano campi da aggiornare
                if (empty($set_parts)) {
                    echo json_encode(['success' => true, 'message' => 'Nessuna modifica da salvare', 'campi_modificati' => 0]);
                    break;
                }
                
                // Nome del personaggio da modificare
                $nome_personaggio = isset($_POST['personaggio']) ? gdrcd_filter('in', $_POST['personaggio']) : '';
                if (empty($nome_personaggio)) {
                    throw new Exception('Nome personaggio non specificato');
                }
                
                // Esegui l'update
                $query = "UPDATE personaggio SET " . implode(', ', $set_parts) . " WHERE nome = '$nome_personaggio'";
                
                if (gdrcd_query($query)) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Personaggio aggiornato', 
                        'campi_modificati' => $campi_modificati,
                        'query' => $query // Solo per debug, rimuovi in produzione
                    ]);
                } else throw new Exception('Errore nel database');
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }

            break;
        
            case 'deleteEsiliati':  // Elimino tutti i pg esiliati
            $queryDelete = gdrcd_query("DELETE FROM personaggio WHERE esilio != '0000-00-00' AND esilio IS NOT NULL");
            
            if ($queryDelete) echo json_encode(['success' => true, 'message' => 'Personaggi esiliati eliminati con successo']);
            else echo json_encode(['success' => false, 'message' => 'Errore nella cancellazione']);

            break;
        case 'resetPg':  // Tolgo al pg tutti i punti shin, le skill e i talenti acquistati
            // Se non viene specificato alcun personaggio, agisco su tutti i personaggi del sistema
            $pgs = isset($data['pgs']) && is_array($data['pgs']) && count($data['pgs']) > 0 ? $data['pgs'] : gdrcd_query("SELECT * FROM personaggio ORDER BY nome ASC", 'result');
            
            // Ciclo tutti i nomi dei pg
            foreach ($pgs as $pg) {
                $nome = $pg["nome"];
                
                // Recupero tutti i punti del pg
                $punti = getPuntiPg($nome); // Tutti i punti
                $esperienza_r = getExp_rPg($punti['esperienza']); // Punti esperienza
                $tot_shin = ($punti['shin_to_spend'] + $punti['tot_shin'] + $punti['punto_skill']); // Punti shin
                /*
                - UPDATE
                    - Esperienza residua calcolata in base agli scaglioni di guadagno dell'esperienza
                    - Riporto le statistiche a 10
                    - Elimino gli shin assegnati alle statistiche
                    - Assegna gli shin calcolati su quelli assegnati alle statistiche + quelli ancora da spendere + quelli assegnati alle skill
                    - Elimino gli shin assegnati alle skill
                */
                $query_updatePg = gdrcd_query("UPDATE personaggio SET
                                esperienza_r = $esperienza_r,
                                car0 = 10, car2 = 10, car4 = 10, car6 = 10, car8 = 10,
                                car1 = 0, car3 = 0, car5 = 0, car7 = 0, car9 = 0,
                                shin = $tot_shin,
                                punto_skill = 0.0, esperienza_s = 0
                            WHERE nome = '$nome'");
                // Elimino le skill e i talenti assegnati al pg e i log che tengono traccia dell'assegnazione degli shin
                $query_deleteSkillTalentiPg = gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome = '$nome' AND id_abilita NOT IN (SELECT id_abilita FROM abilita WHERE tipo IN ('Talento', 'Skill temporanea'))");
                $query_deleteLogPg = gdrcd_query("DELETE from log_spesa WHERE nome = '$nome'");

                if ($query_updatePg && $query_deleteSkillTalentiPg && $query_deleteLogPg) echo json_encode(['success' => true, 'message' => 'Personaggi azzerati con successo']);
                else echo json_encode(['success' => false, 'message' => 'Errore nell\'azzeramento del personaggio']);
            }

            break;
        case 'getPuntiPg': // Recupero le statistiche del pg
            session_start();

            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '".gdrcd_filter('in', $_SESSION['login'])."'"); // prendo le informazioni del personaggio
            $tot_stats = getTotStatsPg($pg['nome']); // Somma totale delle statistiche (definisce il livello)
            $tot_shin_assegnati = $pg['car1'] + $pg['car3'] + $pg['car5'] + $pg['car7'] + $pg['car9'] + $pg['punto_skill']; // Punti shin assegnati alle statistiche e alle skill
            $tot_xp_assegnati = ($pg['car0'] - $pg['car1']) + ($pg['car2'] - $pg['car3']) + ($pg['car4'] - $pg['car5']) + ($pg['car6'] - $pg['car7']) + ($pg['car8'] - $pg['car9']) - 50; // Punti esperienza assegnati alle statistiche
            $esperienza = $pg['esperienza']; // Esperienza del pg
            $esperienza_r = getExp_rPg($esperienza); // Calcolo l'esperienza residua
            $shin = number_format($pg['shin'], 1); // Shin residui
            $xp_da_assegnare = ($esperienza_r - $tot_xp_assegnati);

            // Inizializzo l'array $attributes inserendo di default i punti skill perché sono fissi e richiedono esclusivamente i punti shin
            $attributes = array();

            // Proseguo con l'aggiungere tutte le stats dell'utente, suddivise tra px e shin
            foreach ($pg as $key => $value) {
                // Quando ciclo le info del pg, se trovo le stats (car), le lavoro
                if (strpos($key, 'car') === 0) {
                    $num_car = substr($key, -1); // Prendo il numero finale del campo 'car'
                    $stat = '';

                    switch ($key) {
                        case 'car2': $stat = 'Destrezza'; break;
                        case 'car4': $stat = 'Mente'; break;
                        case 'car6': $stat = 'Tempra'; break;
                        case 'car8': $stat = 'Potere'; break;
                    }
                    
                    if($stat != '') {
                        array_push($attributes, array(
                            "field" => $key,
                            "name" => $stat,
                            "onlyShin" => false,
                            "xp" => (int)($pg[$key] - $pg['car'.($num_car + 1)]), // es: se ho car1, per ottenere gli xp assegnati, devo prendere (car1 - car2)
                            "shin" => (float)$pg['car'.($num_car + 1)] // es: se ho car1, per ottenere gli shin assegnati, devo prendere car2
                        ));
                    }
                }
            }

            // Recupero le soglie per definire il livello del pg nel front-end
            $res = gdrcd_query("SELECT livello, soglia FROM gilda_soglie ORDER BY soglia ASC", "result");
            $soglie = [];
            while ($row = gdrcd_query($res, "fetch")) $soglie[] = $row;

            echo json_encode(array(
                'xpDisponibili' => (float)$xp_da_assegnare,
                'shinDisponibili' => (int)$shin,
                'xpAssegnati' => (float)$tot_xp_assegnati,
                'shinAssegnati' => (int)$tot_shin_assegnati,
                'level' => $pg['id_gilda'] > 0 ? getLevelPg($tot_stats) : 0,
                'soglie' => $soglie,
                'tot stats' => $tot_stats,
                'attributes' => $attributes
            ));
            break;
        case 'savePuntiPg': // Salvo le statistiche del pg
            session_start();

            $setParts = [
                "esperienza_r = ".$data['xpDisponibili'],
                "shin = ".$data['shinDisponibili']
            ];
            $campi = ["`nome`"];
            $valori = ["'".$_SESSION['login']."'"];

            // Costruisco l'update dei punti del personaggio
            foreach ($data['attributes'] as $stat){
                $name = $stat['name'];
                $field = $stat['field'];
                $shin = gdrcd_filter('num', $stat['shin']);
                $xp = gdrcd_filter('num', $stat['xp']);

                // Aggiorno i campi principali
                $setParts[] = "`$field` = ($shin + $xp)";
                
                // Aggiorno eventuali shin
                if(strpos($field, 'car') === 0) {
                    $num_car = substr($field, -1); // Prendo il numero finale del campo 'car'
                    $setParts[] = "`car".($num_car + 1)."` = $shin";

                    // Parametri per log_spesa
                    $campi[] = "`$name`";
                }
                // Parametri per log_spesa
                if($field === 'punto_skill') $campi[] = "`$field`";
                
                $valori[] = $shin; // Parametri per log_spesa
            }
                    
            $query = "UPDATE personaggio SET ".implode(', ', $setParts)." WHERE nome = '".gdrcd_filter('get', $_SESSION['login'])."'";
            $log_spesa = "INSERT INTO log_spesa (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";

            if(gdrcd_query($query) && gdrcd_query($log_spesa)){
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Punti salvati con successo!'
                ));
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Errore nel salvataggio!',
                    'data recived' => $data,
                    'query' => $query,
                    'log_spesa' => $log_spesa
                ));
            }


            exit;
        case 'getSkillPg': // Recupero le skill del pg
            session_start();

            $pg_name = gdrcd_filter('in', $_SESSION['login']);
            $skillRes = [];
            $pg_shin = 0;
            
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$pg_name'");
            $skills = gdrcd_query("SELECT clgpersonaggioabilita.grado, abilita.tipo, abilita.livello_sblocco, abilita.max_lvl, abilita.id_abilita,
                                        abilita.nome as nome_abilita, abilita.descrizione as descrizione_abilita, abilita.descrizione as descrizione_abilita
                                    FROM abilita
                                    LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita AND clgpersonaggioabilita.nome = '$pg_name'
                                    WHERE abilita.id_gilda = (SELECT id_gilda FROM personaggio WHERE nome = '$pg_name')
                                    AND abilita.tipo IN (
                                        'Default', 'Difensiva', 'Generica base', 'Generica avanzata',
                                        'Attacco base', 'Attacco medio', 'Attacco avanzato',
                                        'Mentale base', 'Mentale media', 'Mentale avanzata', 'Mentale di attacco',
                                        'Potere speciale')", 'result');
            $pg_level = $pg['id_gilda'] > 0 ? getLevelPg(getTotStatsPg($pg_name)) : 0;
            
            foreach ($skills as $skl) {
                $tipo = '';
                $pg_shin = $skl['shin'];
                $locked = false; // Blocco la skill se questa richiede un livello pg più alto del mio
                $itCosts = false; // Indica se la skill richiede shin per essere aumentata di livello

                if (substr($skl['tipo'], 0, strlen("Default")) === "Default") $tipo = 'Default';
                elseif (substr($skl['tipo'], 0, strlen("Difensiva")) === "Difensiva") $tipo = 'Default';
                elseif (substr($skl['tipo'], 0, strlen("Generica")) === "Generica") $tipo = 'Generica';
                elseif (substr($skl['tipo'], 0, strlen("Attacco")) === "Attacco") {
                    $tipo = 'Attacco';
                    $locked = ((int)$pg_level < (int)$skl['livello_sblocco']);
                    $itCosts = true;
                } elseif (substr($skl['tipo'], 0, strlen("Mentale")) === "Mentale") {
                    $tipo = 'Mentale';
                    $locked = ((int)$pg_level < (int)$skl['livello_sblocco']);
                    $itCosts = true;
                } elseif (substr($skl['tipo'], 0, strlen("Potere")) === "Potere") {
                    $tipo = 'Speciale';
                    $locked = ((int)$pg_level < (int)$skl['livello_sblocco']);
                    $itCosts = true;
                }

                $skillRes[] = [
                    'id' => (int)$skl['id_abilita'],
                    'nome' => $skl['nome_abilita'],
                    'descrizione' => $skl['descrizione_abilita'],
                    'categoria' => $tipo,
                    'livello' => (int)$skl['grado'],
                    'maxLivello' => (int)$skl['max_lvl'],
                    'tipo' => $skl['tipo'],
                    'locked' => $locked,
                    'itCosts' => $itCosts
                ];
            }

            echo json_encode([
                'success' => true,
                'shinDisponibili' => (float)$pg['shin'],
                'livelloPg' => (float)$pg_level,
                'skills' => $skillRes
            ]);
            exit;
            break;
        case 'saveSkillPg': // Salvo le skill del pg
            session_start();

            try {
                $pg_name = gdrcd_filter('in', $_SESSION['login']);
                $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$pg_name'"); // Recupoero i dati del pg

                // Verifico se il pg ha abbastanza shin
                foreach ($data['skills'] as $id => $levels) {
                    $new = $levels['new'];

                    // Aggiorno il livello delle skill del pg
                    gdrcd_query("REPLACE INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('$pg_name', $id, $new)");
                }

                // Se sto tentando di spendere più shin di quelli disponibili, lancio un'eccezione
                // Altrimenti sottraggo gli shin al personaggio
                if ($pg['shin'] < $data['shin']) throw new Exception('Non hai abbastanza shin!');
                else gdrcd_query("UPDATE personaggio SET shin = ".$data['shin']." WHERE nome = '$pg_name'");

                // Inserisco la spesa degli shin nei log
                gdrcd_query("INSERT INTO log_spesa (nome, punto_skill) VALUES ('$pg_name', ".($pg['shin'] - $data['shin']).")");

                echo json_encode(['success' => true, 'message' => 'Skill salvate con successo!']);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            
            exit;
        // OGGETTI
        case 'getObj':  // Recupero i dati dell'oggetto
            $id = (int)$_GET['id'];
            $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id");
            
            if ($oggetto) echo json_encode(['success' => true, 'oggetto' => $oggetto]);
            else echo json_encode(['error' => false, 'message' => 'Oggetto non trovato']);
            
            break;
        case 'getTipiObj':  // Recupero i tipi di oggetto
            $categoria = gdrcd_filter('in', $_GET['categoria']);
    
            // Applica filtri in base alla categoria (stessa logica del codice originale)
            $categorie_escluse = [];
            $categorie_permesse = [];
            
            switch ($categoria) {
                case 'standard': $categorie_escluse = ['Arma di Gilda', 'Armi', 'Droga', 'Magic Shop', 'Medicine', 'Mods', 'STRIKE', 'Secret Pandora']; break;
                case 'arma': $categorie_permesse = ['Arma di Gilda', 'Armi', 'STRIKE', 'Secret Pandora']; break;
                case 'curativo': $categorie_permesse = ['Magic Shop', 'Medicine', 'STRIKE', 'Secret Pandora']; break;
                case 'statistica': $categorie_permesse = ['Droga', 'Magic Shop', 'Mods', 'STRIKE', 'Secret Pandora']; break;
                case 'magico': $categorie_permesse = ['Magic Shop', 'Secret Pandora', 'Generico', 'Gioielli']; break;
            }
            
            $tipi = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');
            $tipi_filtrati = [];
            
            while($t = gdrcd_query($tipi, 'fetch')) {
                $descrizione = gdrcd_filter('out', $t['descrizione']);
                $mostra = true;
                
                if (!empty($categorie_escluse) && in_array($descrizione, $categorie_escluse)) $mostra = false;
                if (!empty($categorie_permesse) && !in_array($descrizione, $categorie_permesse)) $mostra = false;
                if ($mostra) $tipi_filtrati[] = $t;
            }
            
            echo json_encode(['success' => true, 'tipi' => $tipi_filtrati]);
            
            break;
        case 'saveObj':  // Salvo i dati dell'oggetto
            try {
                $id = isset($_POST['id_oggetto']) ? (int)$_POST['id_oggetto'] : 0;
                $isEdit = ($id > 0);
                
                // Verifica dati obbligatori
                if (empty($_POST['nome']) || empty($_POST['descrizione']) || empty($_POST['categoria']) || empty($_POST['tipo'])) echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $_POST]);
                
                // Verifica permessi per modifica
                if ($isEdit) {
                    $oggettoEsistente = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id");

                    if (!$oggettoEsistente || !canEditOggetto($oggettoEsistente)) echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questo oggetto', 'dati' => canEditOggetto($oggettoEsistente)]);
                }
                
                // Prepara dati base
                $dati = prepareDatiBase($_POST);
                $categoria = gdrcd_filter('in', $_POST['categoria']);
                
                // Aggiungi campi specifici per categoria
                $dati = array_merge($dati, getCampiSpecificiCategoria($categoria, $_POST));
                
                // Gestione immagine
                $dati = saveImgObj($dati, $isEdit ? $oggettoEsistente['urlimg'] : null, $_FILES);
                
                // Esegui query
                if ($isEdit) updateOggetto($id, $dati);
                else insertOggetto($dati);
                
                echo json_encode(['success' => true, 'message' => 'Oggetto salvato con successo!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'deleteObj':  // Elimino un oggetto
            $id_oggetto = (int)$data['id_oggetto'];
            $obj = gdrcd_query("SELECT clgpersonaggiooggetto.nome, clgpersonaggiooggetto.numero, oggetto.costo, oggetto.urlimg
                                FROM clgpersonaggiooggetto
                                LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto
                                WHERE clgpersonaggiooggetto.id_oggetto = $id_oggetto");
            
            if ($obj['numero']) {
                // Risarcisco gli eventuali possessori
                while($refound = gdrcd_query($obj, 'fetch')) {
                    gdrcd_query("UPDATE personaggio SET soldi = (soldi + (".gdrcd_filter('num', $obj['costo'])." * ".gdrcd_filter('num', $obj['numero']).")) WHERE nome = '".gdrcd_filter_in($obj['nome'])."'");
                }
                // Elimino gli oggetti assegnati ai pg dal database
                $queryDelete = gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id_oggetto");
            }
            // Elimino l'immagine associata all'oggetto se esiste
            if (!empty($obj['urlimg']) && file_exists(__DIR__ . '/../uploads/oggetti/' . $obj['urlimg'])) {
                unlink(__DIR__ . '/../uploads/oggetti/' . $obj['urlimg']);
            }
            // Elimino l'oggetto dal database
            $queryDelete = gdrcd_query("DELETE FROM oggetto WHERE id_oggetto = $id_oggetto");
            
            if ($queryDelete) echo json_encode(['success' => true, 'message' => 'Oggetto eliminato con successo']);
            else echo json_encode(['error' => false, 'message' => 'Errore nella cancellazione']);

            break;
        case 'buyObj':  // Acquisto oggetti dal mercato
            $id_oggetto = $data['id_oggetto'];
            $user = $data['user'];
            $costo = ($data['costo'] * $data['qty']);
            $qty = $data['qty'];
            $obj = gdrcd_query("SELECT oggetto.*, mercato.numero as numero_mercato, GROUP_CONCAT(clgpersonaggiooggetto.nome SEPARATOR ', ') AS nomi
								FROM oggetto
								LEFT JOIN clgpersonaggiooggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto
                                LEFT JOIN mercato ON oggetto.id_oggetto = mercato.id_oggetto
								WHERE oggetto.id_oggetto = $id_oggetto
                                GROUP BY oggetto.id_oggetto");
                                
            // Se acquisto un oggetto del pandora, specifico che non è utilizzabile finché non viene giocato in ON
            $comment = $obj['tipo'] == 9 ? gdrcd_filter('in', "Oggetto comprato nel mercato, ma non ancora comprato in ON, per questo è inutilizzabile") : '';
            // Normalizzo cariche: se illimitato, lo metto a -1, altrimenti lo lascio com'è
            $cariche = $obj['cariche'] === 'illimitato' ? -1 : (int)$obj['cariche'];
            
            // Se già possiedo l'oggetto, ne aumento il numero nel mio equipaggiamento
            $nomi = array_map('trim', explode(',', $obj['nomi'])); // Se ci sono utenti che hanno già acquistato questo oggetto, prendo i loro nomi
            if(in_array($user, $nomi)) $query_clgPgObj = "UPDATE clgpersonaggiooggetto SET numero = (numero + $qty) WHERE id_oggetto = $id_oggetto AND nome = '$user'";
            else $query_clgPgObj = "INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, commento_shop, cariche, numero, posizione, isTemp, temp_giorni) VALUES ('$user', $id_oggetto, '$comment', ".$cariche.", $qty, 0, ".gdrcd_filter('num', $obj['isTemp']).", ".gdrcd_filter('num', $obj['temp_giorni']).")";
            // Scalo i soldi del pg
            $query_pg = "UPDATE personaggio SET soldi = (soldi - $costo) WHERE nome = '$user' LIMIT 1";
            // Scalo la quantità di pezzi per l'oggetto presente nel mercato
            $query_mercato = $obj['numero_mercato'] > 1 ? "UPDATE mercato SET numero = (numero - $qty) WHERE id_oggetto = $id_oggetto LIMIT 1" : "DELETE FROM mercato WHERE id_oggetto = $id_oggetto LIMIT 1";

            if (gdrcd_query($query_clgPgObj) && gdrcd_query($query_pg) && gdrcd_query($query_mercato) ) echo json_encode(['success' => true, 'message' => 'Oggetto acquistato con successo!']);
            else echo json_encode(['error' => false, 'message' => 'Errore nelle query di acquisto']);
            
            break;
        case 'sellObj':  // Vendita oggetti del mercato
            $id_oggetto = $data['id_oggetto'];
            $user = $data['user'];
            $qty = $data['qty'];

            // Controllo che il PG abbia l'oggetto in inventario
            $query = "SELECT clgpersonaggiooggetto.numero, oggetto.costo FROM clgpersonaggiooggetto LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.id_oggetto = $id_oggetto AND clgpersonaggiooggetto.nome = '$user'";
            $result = gdrcd_query($query, 'result');

            if(gdrcd_query($result, 'num_rows') > 0) {
                $row = gdrcd_query($result, 'fetch');
                gdrcd_query($result, 'free');

                // Calcolo il costo di vendita in base alla percentuale di rivendita
                $costo = floor(($row['costo'] / 100) * (100 - $PARAMETERS['settings']['resell_price']));

                // Se vendo tutti gli oggetti in mio possesso li elimino dall'inventario, altrimenti tolgo la quantità venduta, altrimenti significa che non ho abbastanza oggetti da vendere
                if($row['numero'] == $qty) $query_clgPgObj = "DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id_oggetto AND nome = '$user' LIMIT 1";
                elseif($row['numero'] > $qty) $query_clgPgObj = "UPDATE clgpersonaggiooggetto SET numero = (numero - $qty) WHERE id_oggetto = $id_oggetto AND nome = '$user' LIMIT 1";
                else {
                    echo json_encode(['error' => false, 'message' => 'Non hai abbastanza oggetti da vendere']);
                    break;
                }

                // Aggiungo i soldi della vendita al pg
                $query_pg = "UPDATE personaggio SET soldi = (soldi + ".gdrcd_filter('num', $costo).") WHERE nome = '$user' LIMIT 1";
                // Rimetto i pezzi disponibili nel mercato
                $query_mercato = "UPDATE mercato SET numero = (numero + $qty) WHERE id_oggetto = $id_oggetto LIMIT 1";

                if (gdrcd_query($query_clgPgObj) && gdrcd_query($query_pg) && gdrcd_query($query_mercato)) echo json_encode(['success' => true, 'message' => 'Oggetto venduto con successo!']);
                else echo json_encode(['error' => false, 'message' => 'Errore nelle query di vendita']);
            } else echo json_encode(['error' => false, 'message' => 'Non hai questo oggetto da vendere']);
            
            break;
        // GILDE
        case 'getGuild':  // Recupero i dati della gilda
            $id = (int)$_GET['id'];
            $gilda = gdrcd_query("SELECT * FROM gilda WHERE id_gilda = $id");
            
            if ($gilda) echo json_encode($gilda);
            else echo json_encode(['error' => false, 'message' => 'Gilda non trovata']);
            
            break;
        case 'deleteGuild':  // Elimino la gilda
            $id = (int)$_GET['id'];
            $gilda = gdrcd_query("DELETE FROM gilda WHERE id_gilda = $id");
            $ruoli = gdrcd_query("DELETE FROM ruolo WHERE gilda = $id");
            
            if ($gilda && $ruoli) echo json_encode(['success' => true, 'message' => 'Gilda eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Gilda non trovata']);
            
            break;
        case 'saveGuild':   // Salvo la gilda
            try {
                $id = isset($_POST['id_gilda']) ? (int)$_POST['id_gilda'] : 0;
                $isEdit = ($id > 0);
                $_POST['visibile'] = isset($_POST['visibile']) ? 1 : 0;
                
                // Verifica dati obbligatori
                if (empty($_POST['nome']) || empty($_POST['tipo'])) echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $_POST]);
                
                // Verifica permessi per modifica
                if ($isEdit) {
                    $guildEsistente = gdrcd_query("SELECT * FROM gilda WHERE id_gilda = $id");

                    if (!$guildEsistente || !canEditOggetto($guildEsistente)) echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questa gilda']);
                }

                // Gestione immagine
                $dati = saveImgGuild($_POST, $isEdit ? $guildEsistente['immagine'] : null, $_FILES);
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) $setParts[] = "`$campo` = '" . gdrcd_filter('in', $valore) . "'";
                    
                    $query = "UPDATE gilda SET ".implode(', ', $setParts)." WHERE id_gilda = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_gilda') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".gdrcd_filter('in', $valore)."'";
                    }
                    
                    $query = "INSERT INTO gilda (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }
                
                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Gilda salvata con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio della gilda']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'getRole':  // Recupero i dati del ruolo
            $id = (int)$_GET['id'];
            $ruolo = gdrcd_query("SELECT * FROM ruolo WHERE id_ruolo = $id");
            
            if ($ruolo) echo json_encode($ruolo);
            else echo json_encode(['error' => false, 'message' => 'Ruolo non trovato']);
            
            break;
        case 'deleteRole':  // Elimino il ruolo
            $id = (int)$_GET['id'];
            $ruolo = gdrcd_query("DELETE FROM ruolo WHERE id_ruolo = $id");
            $ruolo_pg = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE id_ruolo = $id");
            
            if ($ruolo && $ruolo_pg) echo json_encode(['success' => true, 'message' => 'Ruolo eliminato con successo']);
            else echo json_encode(['error' => false, 'message' => 'Ruolo non trovato']);
            
            break;
        case 'saveRole':    // Salvo il ruolo della gilda
            try {
                $id = isset($_POST['id_ruolo']) ? (int)$_POST['id_ruolo'] : 0;
                $isEdit = ($id > 0);
                $_POST['capo'] = isset($_POST['capo']) ? 1 : 0;
                
                // Verifica dati obbligatori
                if (empty($_POST['nome_ruolo']) || !is_numeric($_POST['gilda']) || !is_numeric($_POST['capo']) || !is_numeric($_POST['livello'])){
                    echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $_POST]);
                    exit;
                }

                // Verifica permessi per modifica
                if ($isEdit) {
                    $guildEsistente = gdrcd_query("SELECT * FROM ruolo WHERE id_ruolo = $id");

                    if (!$guildEsistente || !canEditOggetto($guildEsistente)) echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questo ruolo']);
                }

                // Gestione immagine
                $dati = saveImgGuild($_POST, $isEdit ? $guildEsistente['immagine'] : null, $_FILES);
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) $setParts[] = "`$campo` = '" . gdrcd_filter('in', $valore) . "'";
                    
                    $query = "UPDATE ruolo SET ".implode(', ', $setParts)." WHERE id_ruolo = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_ruolo') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".gdrcd_filter('in', $valore)."'";
                    }
                    
                    $query = "INSERT INTO ruolo (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }
                
                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Ruolo salvato con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio del ruolo']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'deleteSoglia':  // Elimino la soglia del livello gilda
            $id = (int)$_GET['id'];
            $soglia = gdrcd_query("DELETE FROM gilda_soglie WHERE id_soglia = $id");
            
            if ($soglia) echo json_encode(['success' => true, 'message' => 'Soglia eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Ruolo non trovato']);
            
            break;
        case 'saveSoglia':  // Salvo la soglia del livello gilda
            $id_soglia = $data['id_soglia'];
            $livello_soglia = $data['livello_soglia'];
            $soglia = $data['soglia'];
            $danno = $data['danno'];

            // Verifica dati obbligatori
            if (!is_numeric($id_soglia) || !is_numeric($livello_soglia) || !is_numeric($soglia) || !is_numeric($danno)) {
                echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $data]);
                return;
            }

            // Recupero tutte le soglie per controllare che quella che salvo sia giusta. Mi raccomando, mantenere l'ORDER BY nella query
            $soglie = gdrcd_query("SELECT * FROM gilda_soglie WHERE id_soglia NOT IN ($id_soglia) ORDER BY livello", 'result');
            
            foreach ($soglie as $s) {
                // La soglia che si vuole impostare per questo livello è maggiore rispetto a quella dei livelli più alti o minore rispetto a quella dei livelli più bassi
                if($id_soglia == s['id_soglia']) $exists = true;
                // Esiste già una soglia impostata per questo livello. Modificare direttamente la soglia esistente.
                if($livello_soglia == $s['livello']) {
                    echo json_encode(['success' => false, 'message' => 'Esiste già una soglia impostata per questo livello. Modificare direttamente la soglia esistente.']);
                    return;
                }
                // La soglia che si vuole impostare per questo livello è maggiore rispetto a quella dei livelli più alti o minore rispetto a quella dei livelli più bassi
                if(($livello_soglia > $s['livello'] && $soglia <= $s['soglia']) || ($livello_soglia < $s['livello'] && $soglia > $s['soglia'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'La soglia non può essere inferiore o uguale a quella impostata per un livello più basso o viceversa.',
                        'Soglie' => "$soglia - ".$s['soglia'],
                        'Livelli' => "$livello_soglia - ".$s['livello']
                    ]);
                    return;
                }
            }

            $query = $id_soglia > 0 ? "UPDATE gilda_soglie SET livello = $livello_soglia, soglia = $soglia, danno = $danno WHERE id_soglia = $id_soglia" :
                                        "INSERT INTO gilda_soglie (livello, soglia, danno) VALUES ($livello_soglia, $soglia, $danno)";

            if (gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Soglia impostata con successo per questo livello!']);
            else echo json_encode(['error' => false, 'message' => 'Errore nella query!']);

            break;
        case 'getVoceStatuto':  // Recupero i dati dello statuto della gilda
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("SELECT * FROM statuti WHERE articolo = $id");
            
            if ($voce_statuto) echo json_encode($voce_statuto);
            else echo json_encode(['error' => false, 'message' => 'Voce statuto non trovata']);
            
            break;
        case 'deleteVoceStatuto':  // Elimino lo statuto
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("DELETE FROM statuti WHERE articolo = $id");
            
            if ($voce_statuto) echo json_encode(['success' => true, 'message' => 'Voce eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Voce non trovata']);
            
            break;
        case 'saveVoceStatuto': // Salvo la voce dello statuto
            try {
                $dati = $_POST;
                $id = isset($dati['articolo']) ? (int)$dati['articolo'] : 0;
                $isEdit = ($id > 0);
                
                // Verifica dati obbligatori
                if (empty($dati['titolo']) || empty($dati['testo'])) {
                    echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $dati]);
                    return;
                }
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) {
                        if($campo == 'articolo') continue; // Salto l'id in fase di inserimento
                        
                        $setParts[] = "`$campo` = '".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "UPDATE statuti SET ".implode(', ', $setParts)." WHERE articolo = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'articolo') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "INSERT INTO statuti (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }

                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Voce salvata con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio della voce']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'getSkill':  // Recupero i dati della skill
            $id = (int)$_GET['id'];
            $skill = gdrcd_query("SELECT * FROM abilita WHERE id_abilita = $id");
            
            if ($skill) echo json_encode($skill);
            else echo json_encode(['error' => false, 'message' => 'Abilità non trovata - '."SELECT * FROM abilita WHERE id_gilda = $id"]);
            
            break;
        case 'deleteSkill':  // Elimino la skill
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("DELETE FROM abilita WHERE id_abilita = $id");
            
            if ($voce_statuto) echo json_encode(['success' => true, 'message' => 'Abilità eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Abilità non trovata']);
            
            break;
        case 'saveSkill':   // Salvo l'abilità
            try {
                $dati = $_POST;
                $id = isset($dati['id_abilita']) ? (int)$dati['id_abilita'] : 0;
                $isEdit = ($id > 0);
                
                // Verifica dati obbligatori
                if (empty($dati['nome']) || empty($dati['descrizione'])) {
                    echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $dati]);
                    return;
                }
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_abilita') continue; // Salto l'id in fase di inserimento
                        
                        $setParts[] = "`$campo` = '".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "UPDATE abilita SET ".implode(', ', $setParts)." WHERE id_abilita = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_abilita') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "INSERT INTO abilita (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }

                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Abilità salvata con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio della abilità']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'saveSoundPrefs': // Salvo le preferenze suoni dell'utente
            session_start();
            $dm     = isset($data['dm'])     ? ($data['dm']     ? 1 : 0) : 1;
            $chat   = isset($data['chat'])   ? ($data['chat']   ? 1 : 0) : 1;
            $scheda = isset($data['scheda']) ? ($data['scheda'] ? 1 : 0) : 1;
            $login  = gdrcd_filter('in', $_SESSION['login']);
            gdrcd_query("UPDATE personaggio SET suono_dm=$dm, suono_chat=$chat, suono_scheda=$scheda WHERE nome='$login'");
            $_SESSION['suono_dm']     = $dm;
            $_SESSION['suono_chat']   = $chat;
            $_SESSION['suono_scheda'] = $scheda;
            echo json_encode(['success' => true]);
            break;

        // -------------------------------------------------------------------------
        // NOTIFICHE — preferenze per evento x canale (Uffici > Preferenze)
        // Solo i 3 eventi gia' generati dal motore fan-out (Fase C, api_forum.php):
        // nuovo_post_sezione e nuovo_dm arrivano con le fasi F/E, quando esistera'
        // davvero un trigger che li produce.
        // -------------------------------------------------------------------------
        case 'getNotificationPrefs':
            session_start();
            $login_f = gdrcd_filter('in', $_SESSION['login']);

            $eventi = ['commento_post_seguito', 'commento_post_commentato', 'commento_post_proprio'];
            $prefs  = [];
            foreach ($eventi as $ev) $prefs[$ev] = ['dm' => 1, 'email' => 0]; // default

            $res = gdrcd_query("SELECT evento, via_dm, via_email FROM preferenze_notifiche
                WHERE nome = '$login_f'", 'result');
            while ($row = gdrcd_query($res, 'fetch')) {
                if (isset($prefs[$row['evento']])) {
                    $prefs[$row['evento']] = ['dm' => (int)$row['via_dm'], 'email' => (int)$row['via_email']];
                }
            }
            gdrcd_query($res, 'free');

            echo json_encode(['success' => true, 'prefs' => $prefs]);
            break;

        case 'saveNotificationPrefs':
            session_start();
            $login_f = gdrcd_filter('in', $_SESSION['login']);

            $eventi_validi = ['commento_post_seguito', 'commento_post_commentato', 'commento_post_proprio'];
            foreach (($data['prefs'] ?? []) as $evento => $canali) {
                if (!in_array($evento, $eventi_validi, true)) continue; // ignora chiavi non valide/impreviste

                $via_dm    = !empty($canali['dm'])    ? 1 : 0;
                $via_email = !empty($canali['email']) ? 1 : 0;

                gdrcd_query("INSERT INTO preferenze_notifiche (nome, evento, via_dm, via_email)
                    VALUES ('$login_f', '$evento', $via_dm, $via_email)
                    ON DUPLICATE KEY UPDATE via_dm = VALUES(via_dm), via_email = VALUES(via_email)");
            }

            echo json_encode(['success' => true]);
            break;

        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
    /*********************  FINE    Recupero i dati dell'utente che voglio modificare   */
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>