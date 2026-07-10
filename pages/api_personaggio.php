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

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    /*********************  Recupero i dati dell'utente che voglio modificare   */
    switch ($_GET['op']) {
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

            $errors = [];

            // Ciclo tutti i nomi dei pg
            foreach ($pgs as $pg) {
                $nome   = $pg["nome"];
                $nome_f = gdrcd_filter('in', $nome);

                // Recupero tutti i punti del pg
                $punti        = getPuntiPg($nome_f); // Tutti i punti
                $esperienza_r = getExp_rPg($punti['esperienza']); // Punti esperienza
                $tot_shin     = ($punti['shin_to_spend'] + $punti['tot_shin'] + $punti['punto_skill']); // Punti shin
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
                            WHERE nome = '$nome_f'");
                // Elimino le skill e i talenti assegnati al pg e i log che tengono traccia dell'assegnazione degli shin
                $query_deleteSkillTalentiPg = gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome = '$nome_f' AND id_abilita NOT IN (SELECT id_abilita FROM abilita WHERE tipo IN ('Talento', 'Skill temporanea'))");
                $query_deleteLogPg = gdrcd_query("DELETE from log_spesa WHERE nome = '$nome_f'");

                if (!$query_updatePg || !$query_deleteSkillTalentiPg || !$query_deleteLogPg) $errors[] = $nome;
            }

            if (empty($errors)) echo json_encode(['success' => true,  'message' => 'Personaggi azzerati con successo']);
            else                 echo json_encode(['success' => false, 'message' => 'Errore nell\'azzeramento di: ' . implode(', ', $errors)]);

            break;
        case 'getPuntiPg': // Recupero le statistiche del pg
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
            // Vincolo: Tempra (car6) non può mai superare il doppio del totale assegnato a Mente (car4)
            $tot_mente = 0;
            $tot_tempra = 0;
            foreach ($data['attributes'] as $stat) {
                $tot = (float)gdrcd_filter('num', $stat['xp']) + (float)gdrcd_filter('num', $stat['shin']);
                if ($stat['field'] === 'car4') $tot_mente = $tot;
                if ($stat['field'] === 'car6') $tot_tempra = $tot;
            }
            if ($tot_tempra > $tot_mente * 2) {
                echo json_encode(['success' => false, 'message' => 'Tempra non può superare il doppio del totale di Mente']);
                exit;
            }

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
        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
    /*********************  FINE    Recupero i dati dell'utente che voglio modificare   */
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>