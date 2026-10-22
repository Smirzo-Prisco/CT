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

    // Porta invariata da gst_personaggio_skill.inc.php:57-81 — usata da
    // cancellaSkillAdmin per ricalcolare lato server il rimborso punti skill
    // invece di fidarsi di un valore inviato dal client (vedi commento sopra
    // il case 'cancellaSkillAdmin').
    function calcolaSpesaAcquistoSkill($tipo, $grado) {
        switch ($tipo) {
            case 'Default':
                return ($grado > 1) ? $grado - 1 : 0; // 0 Punti skill per il Lv0, poi 1 punti skill per livelli successivi
            case 'Difensiva':
                return 0; // 0 Punti skill
            case 'Generica base':
                return 1; // 1 Punto skill
            case 'Generica avanzata':
                return 3; // 3 Punti skill
            case 'Attacco base':
            case 'Mentale base':
                return ($grado > 1) ? $grado : 1; // 1 Punto skill per il Lv0, poi 1 Punti skill per i livelli successivi
            case 'Attacco medio':
            case 'Mentale media':
                return ($grado > 1) ? $grado + 2 : 3; // 3 Punti skill per il Lv0, poi 1 Punti skill per i livelli successivi
            case 'Attacco avanzato':
            case 'Mentale avanzata':
                return ($grado > 1) ? $grado + 4 : 5; // 5 Punti skill per il Lv0, poi 1 Punti skill per i livelli successivi
            case 'Potere speciale':
                return 50; // 50 Punti skill
            default:
                return 0; // Per tipologie non definite, consideriamo 0
        }
    }

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
                    'mestiere_punti' => 'esperienza_mestiere',
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
                        if (in_array($formField, array('eta', 'salute', 'integrita', 'shin', 'soldi', 'notorieta', 'mestiere_punti', 'razza'))) {
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
            // Stesso permesso richiesto dal bottone in gestione_personaggio.inc.php
            // ($permessi_azioni['cancella'] = ['admin']), mancante finora qui: chiunque
            // fosse autenticato poteva chiamare questo op direttamente.
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $queryDelete = gdrcd_query("DELETE FROM personaggio WHERE esilio IS NOT NULL");

            if ($queryDelete) echo json_encode(['success' => true, 'message' => 'Personaggi esiliati eliminati con successo']);
            else echo json_encode(['success' => false, 'message' => 'Errore nella cancellazione']);

            break;
        case 'deletePg':  // Admin: cancellazione fisica e irreversibile di un personaggio
            // Stessa logica delle pagine legacy erase_pg.inc.php / erasepg_scelta.inc.php /
            // erase_inactive.inc.php, richiamata via AJAX invece che con una form POST che
            // portava fuori dalla SPA (gestione_personaggio.inc.php navigava a
            // main.php?page=erasepg_scelta). log_entrate e log_doppi NON vengono cancellati:
            // sono lo storico usato per il rilevamento doppi account.
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $nome = isset($data['pg']) ? gdrcd_filter('in', trim($data['pg'])) : '';
            if ($nome === '') {
                echo json_encode(['success' => false, 'message' => 'Nome mancante']);
                break;
            }
            if ($nome === $_SESSION['login']) {
                echo json_encode(['success' => false, 'message' => 'Non puoi cancellare il tuo stesso personaggio']);
                break;
            }

            gdrcd_query("DELETE FROM personaggio WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM appuntamenti WHERE autore = '$nome'");
            gdrcd_query("DELETE FROM BakCalendario WHERE Destinatario = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE personaggio = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggiolavoro WHERE personaggio = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio = '$nome'");
            gdrcd_query("DELETE FROM clgpersonaggiomostrine WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM log WHERE nome_interessato = '$nome'");
            gdrcd_query("DELETE FROM messaggi WHERE destinatario = '$nome'");
            gdrcd_query("DELETE FROM messaggi WHERE mittente = '$nome'");
            gdrcd_query("DELETE FROM privilegi WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM Punti WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM araldo_letto WHERE nome = '$nome'");
            gdrcd_query("DELETE FROM struttura_affetti WHERE username = '$nome'");
            gdrcd_query("DELETE FROM log_spesa WHERE nome = '$nome'");

            echo json_encode(['success' => true, 'message' => "$nome cancellato definitivamente"]);
            break;
        case 'resetPg':  // Tolgo al pg tutti i punti shin, le skill e i talenti acquistati
            // Stesso permesso richiesto dal bottone in gestione_personaggio.inc.php
            // ($permessi_azioni['reset'] = ['admin']), mancante finora qui — critico perché
            // senza $data['pgs'] questo op azzera TUTTI i personaggi del sistema.
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            // Se non viene specificato alcun personaggio, agisco su tutti i personaggi del sistema
            $pgs = isset($data['pgs']) && is_array($data['pgs']) && count($data['pgs']) > 0 ? $data['pgs'] : gdrcd_query("SELECT * FROM personaggio ORDER BY nome ASC", 'result');

            $errors = [];

            // Ciclo tutti i nomi dei pg — logica di reset in resetPuntiPg()
            // (custom_functions.inc.php), condivisa anche con la cancellazione
            // soft (api_account.php op=delete).
            foreach ($pgs as $pg) {
                $nome   = $pg["nome"];
                $nome_f = gdrcd_filter('in', $nome);

                if (!resetPuntiPg($nome_f)) $errors[] = $nome;
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
        // -------------------------------------------------------------------------
        // ESILIO — porta a JSON la logica di gestione_esilio.inc.php, invariata,
        // per permettere la migrazione di quella pagina a React/SPA (vedi
        // conversazione di progetto del 2026-10-22). Stesso permesso di
        // gestione_esilio.inc.php:3 — admin o moderatore, non solo admin.
        // -------------------------------------------------------------------------
        case 'getPgPerEsilio': // Elenco pg raggruppato per il dropdown (equivalente ai due optgroup della pagina originale)
            if (($_SESSION['admin'] ?? 0) != 1 && ($_SESSION['moderatore'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $attiviRes = gdrcd_query("SELECT nome FROM personaggio WHERE (esilio < NOW() OR esilio IS NULL) ORDER BY nome", 'result');
            $attivi = [];
            while ($r = gdrcd_query($attiviRes, 'fetch')) $attivi[] = $r['nome'];
            gdrcd_query($attiviRes, 'free');

            $esiliatiRes = gdrcd_query("SELECT nome FROM personaggio WHERE esilio > NOW() ORDER BY nome", 'result');
            $esiliati = [];
            while ($r = gdrcd_query($esiliatiRes, 'fetch')) $esiliati[] = $r['nome'];
            gdrcd_query($esiliatiRes, 'free');

            echo json_encode(['success' => true, 'attivi' => $attivi, 'esiliati' => $esiliati]);
            break;
        case 'getEsilioStatus': // Stato esilio del pg selezionato (decide form esilia vs pulsante grazia)
            if (($_SESSION['admin'] ?? 0) != 1 && ($_SESSION['moderatore'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            if (!isset($_GET['name']) || trim($_GET['name']) === '') {
                echo json_encode(['success' => false, 'message' => 'Parametro mancante']);
                break;
            }
            $nomeEsilio = gdrcd_filter('in', trim($_GET['name']));
            $rigaEsilio = gdrcd_query("SELECT nome, esilio FROM personaggio WHERE nome = '$nomeEsilio'");
            if (!$rigaEsilio) {
                echo json_encode(['success' => false, 'message' => 'Personaggio non trovato']);
                break;
            }
            echo json_encode(['success' => true, 'nome' => $rigaEsilio['nome'], 'esilio' => $rigaEsilio['esilio']]);
            break;
        case 'exilePg': // Avvia l'esilio — stessa UPDATE di gestione_esilio.inc.php:12-18
            if (($_SESSION['admin'] ?? 0) != 1 && ($_SESSION['moderatore'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $pgEsilia = isset($data['pg']) ? gdrcd_filter('in', trim($data['pg'])) : '';
            if ($pgEsilia === '') {
                echo json_encode(['success' => false, 'message' => 'Personaggio mancante']);
                break;
            }
            $annoEsilio  = gdrcd_filter('num', $data['year'] ?? '');
            $meseEsilio  = gdrcd_filter('num', $data['month'] ?? '');
            $giornoEsilio = gdrcd_filter('num', $data['day'] ?? '');
            $causaleEsilio = gdrcd_filter('in', $data['causale'] ?? '');

            gdrcd_query(
                "UPDATE personaggio SET esilio = '$annoEsilio-$meseEsilio-$giornoEsilio', data_esilio = NOW(), " .
                "autore_esilio = '" . gdrcd_filter('in', $_SESSION['login']) . "', motivo_esilio = '$causaleEsilio' " .
                "WHERE nome = '$pgEsilia'"
            );
            echo json_encode(['success' => true, 'message' => "$pgEsilia esiliato"]);
            break;
        // -------------------------------------------------------------------------
        // LISTA PERSONAGGI — porta a JSON la query/i filtri di
        // gestione_personaggio.inc.php:24-58, invariati, per la migrazione di
        // quella pagina a React/SPA (vedi conversazione di progetto del
        // 2026-10-22). Stesso permesso della pagina originale (riga 48):
        // admin, master, moderatore, o personaggio con id_gilda == 4 (custode).
        // -------------------------------------------------------------------------
        case 'listPersonaggi':
            $beastRow = gdrcd_query("SELECT id_gilda FROM personaggio WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1");
            $isCustode = $beastRow && (int)$beastRow['id_gilda'] === 4;
            $isAdmin = ($_SESSION['admin'] ?? 0) == 1;
            $isMaster = ($_SESSION['master'] ?? 0) == 1;
            $isModeratore = ($_SESSION['moderatore'] ?? 0) == 1;
            if (!$isAdmin && !$isMaster && !$isModeratore && !$isCustode) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }

            $filtroPg = $_GET['filtro'] ?? 'tutti';
            switch ($filtroPg) {
                case 'inattivi':    $whereFiltro = "AND DATE_ADD(ora_entrata, INTERVAL 150 DAY) <= NOW() AND (esilio < NOW() OR esilio IS NULL)"; break;
                case 'mai_entrati': $whereFiltro = "AND esperienza = '0.0000' AND (DATE(ora_entrata) = DATE(data_iscrizione))"; break;
                case 'esiliati':    $whereFiltro = "AND (esilio > NOW() AND esilio IS NOT NULL)"; break;
                case 'eliminati':   $whereFiltro = "AND " . sqlPgCancellato(); break;
                default:            $whereFiltro = '';
            }

            $risultatoPg = gdrcd_query(
                "SELECT personaggio.nome, personaggio.cognome, personaggio.url_img_chat, personaggio.esilio, personaggio.permessi,
                        razza.nome_razza, mestiere.nome AS nome_mestiere, gilda.nome AS nome_gilda
                 FROM personaggio
                 LEFT JOIN razza    ON personaggio.id_razza    = razza.id_razza
                 LEFT JOIN mestiere ON personaggio.id_mestiere = mestiere.id_mestiere
                 LEFT JOIN gilda    ON personaggio.id_gilda    = gilda.id_gilda
                 WHERE 1=1 $whereFiltro
                 ORDER BY data_iscrizione DESC",
                'result'
            );

            $listaPg = [];
            while ($pgRow = gdrcd_query($risultatoPg, 'fetch')) {
                $isExiledPg = !empty($pgRow['esilio'])
                    && $pgRow['esilio'] !== '0000-00-00 00:00:00'
                    && $pgRow['esilio'] > date('Y-m-d H:i:s');
                $listaPg[] = [
                    'nome'            => $pgRow['nome'],
                    'cognome'         => $pgRow['cognome'],
                    'url_img_chat'    => $pgRow['url_img_chat'],
                    'nome_razza'      => $pgRow['nome_razza'],
                    'nome_mestiere'   => $pgRow['nome_mestiere'],
                    'nome_gilda'      => $pgRow['nome_gilda'],
                    'isExiled'        => $isExiledPg,
                    'esilioFormattato' => $isExiledPg ? gdrcd_format_date($pgRow['esilio']) : null,
                    'isDeleted'       => (int)$pgRow['permessi'] === -1,
                ];
            }
            gdrcd_query($risultatoPg, 'free');

            echo json_encode([
                'success' => true,
                'personaggi' => $listaPg,
                'perms' => [
                    'admin' => $isAdmin, 'master' => $isMaster,
                    'moderatore' => $isModeratore, 'custode' => $isCustode,
                ],
            ]);
            break;
        case 'listRazze': // Elenco razze per la select del modale di modifica
            $razzeRes = gdrcd_query("SELECT id_razza, nome_razza FROM razza ORDER BY nome_razza", 'result');
            $razze = [];
            while ($r = gdrcd_query($razzeRes, 'fetch')) $razze[] = $r;
            gdrcd_query($razzeRes, 'free');
            echo json_encode(['success' => true, 'razze' => $razze]);
            break;
        // -------------------------------------------------------------------------
        // SKILL (admin) — porta a JSON la logica di gst_personaggio_skill.inc.php,
        // per la migrazione di quella pagina a React/SPA (vedi conversazione di
        // progetto del 2026-10-22). Solo admin (gst_personaggio_skill.inc.php:3).
        //
        // Fix di sicurezza rispetto all'originale: depotenziaSkillAdmin e
        // cancellaSkillAdmin NON accettano piu' il "totale" (punti skill da
        // restituire) dal client — nella pagina vecchia arrivava in SQL senza
        // gdrcd_filter (vera iniezione SQL, mai sfruttata ma presente). Qui viene
        // sempre ricalcolato lato server da tipo/grado correnti con la stessa
        // calcolaSpesaAcquisto() della pagina originale (porta invariate le
        // tabelle di costo).
        // -------------------------------------------------------------------------
        case 'getPgNomi': // Elenco pg per il dropdown (equivalente a $elenco_pg della pagina originale)
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $nomiRes = gdrcd_query("SELECT nome, cognome FROM personaggio ORDER BY nome", 'result');
            $nomiPg = [];
            while ($r = gdrcd_query($nomiRes, 'fetch')) $nomiPg[] = $r;
            gdrcd_query($nomiRes, 'free');
            echo json_encode(['success' => true, 'personaggi' => $nomiPg]);
            break;
        case 'getPgSkillsAdmin': // Skill possedute dal pg selezionato, raggruppate per tipo
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            if (!isset($_GET['pg']) || trim($_GET['pg']) === '') {
                echo json_encode(['success' => false, 'message' => 'Parametro mancante']);
                break;
            }
            $pgSkill = gdrcd_filter('in', trim($_GET['pg']));
            $abilitaRes = gdrcd_query(
                "SELECT abilita.id_abilita, abilita.nome AS nome_abilita, abilita.tipo AS tipo_abilita, clgpersonaggioabilita.grado
                 FROM abilita
                 LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita
                 WHERE clgpersonaggioabilita.nome = '$pgSkill' AND abilita.tipo != 'Talento'
                 ORDER BY abilita.tipo, abilita.nome",
                'result'
            );
            $skillPerTipo = [];
            while ($ab = gdrcd_query($abilitaRes, 'fetch')) {
                $skillPerTipo[$ab['tipo_abilita']][] = [
                    'id_abilita'   => (int)$ab['id_abilita'],
                    'nome_abilita' => $ab['nome_abilita'],
                    'grado'        => (int)$ab['grado'],
                ];
            }
            gdrcd_query($abilitaRes, 'free');
            echo json_encode(['success' => true, 'skillPerTipo' => $skillPerTipo]);
            break;
        case 'depotenziaSkillAdmin': // Grado -1, rimborso fisso di 1 punto skill — stessa logica di gst_personaggio_skill.inc.php:8-10
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $pgDepot = isset($data['pg']) ? gdrcd_filter('in', trim($data['pg'])) : '';
            $idAbilitaDepot = isset($data['id_abilita']) ? (int)$data['id_abilita'] : 0;
            if ($pgDepot === '' || $idAbilitaDepot <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
                break;
            }
            gdrcd_query("UPDATE clgpersonaggioabilita SET grado = grado - 1 WHERE id_abilita = $idAbilitaDepot AND nome = '$pgDepot'");
            gdrcd_query("UPDATE personaggio SET punto_skill = punto_skill + 1, esperienza_s = esperienza_s + 1 WHERE nome = '$pgDepot'");
            echo json_encode(['success' => true, 'message' => 'Skill depotenziata']);
            break;
        case 'cancellaSkillAdmin': // Rimozione completa, rimborso totale ricalcolato lato server
            if (($_SESSION['admin'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $pgCancella = isset($data['pg']) ? gdrcd_filter('in', trim($data['pg'])) : '';
            $idAbilitaCancella = isset($data['id_abilita']) ? (int)$data['id_abilita'] : 0;
            if ($pgCancella === '' || $idAbilitaCancella <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
                break;
            }
            $skillCorrente = gdrcd_query(
                "SELECT abilita.tipo, clgpersonaggioabilita.grado
                 FROM clgpersonaggioabilita
                 INNER JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita
                 WHERE clgpersonaggioabilita.id_abilita = $idAbilitaCancella AND clgpersonaggioabilita.nome = '$pgCancella'"
            );
            if (!$skillCorrente) {
                echo json_encode(['success' => false, 'message' => 'Skill non trovata']);
                break;
            }
            $totaleCancella = calcolaSpesaAcquistoSkill($skillCorrente['tipo'], (int)$skillCorrente['grado']);

            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita = $idAbilitaCancella AND nome = '$pgCancella'");
            gdrcd_query("UPDATE personaggio SET punto_skill = punto_skill + $totaleCancella, esperienza_s = esperienza_s + $totaleCancella WHERE nome = '$pgCancella'");
            echo json_encode(['success' => true, 'message' => 'Skill rimossa']);
            break;
        case 'graziaPg': // Revoca l'esilio — stessa UPDATE di gestione_esilio.inc.php:22-24
            if (($_SESSION['admin'] ?? 0) != 1 && ($_SESSION['moderatore'] ?? 0) != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
                break;
            }
            $pgGrazia = isset($data['pg']) ? gdrcd_filter('in', trim($data['pg'])) : '';
            if ($pgGrazia === '') {
                echo json_encode(['success' => false, 'message' => 'Personaggio mancante']);
                break;
            }
            gdrcd_query("UPDATE personaggio SET esilio = NULL, data_esilio = '2009-07-01', autore_esilio = NULL, motivo_esilio = NULL WHERE nome = '$pgGrazia'");
            echo json_encode(['success' => true, 'message' => "$pgGrazia graziato"]);
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