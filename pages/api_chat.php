<?php
if(isset($_GET['op']) && $_GET['op'] != '') {
    session_start();

    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');
    require_once(__DIR__ . '/../includes/chat_functions.inc.php');
    
    // IMPORTANTE: Solo per le richieste AJAX
    // Impedisce che warning/notice PHP finiscano nell'output e corrompano il JSON
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato']);
        exit;
    }

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    switch ($_GET['op']) {
        case 'tiraSkillChat':
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $id_role = locationActiveRole($luogo);
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
            $salute = $pg["salute"];
            $skill = isset($data['chat_skill']) ? (int)$data['chat_skill'] : 0;
            $livello = isset($data['livello_skill']) ? (int)$data['livello_skill'] : 0;
            $level_check = gdrcd_query("SELECT grado FROM clgpersonaggioabilita WHERE id_abilita = $skill AND nome = '$login'");
            $skill_info = gdrcd_query("SELECT nome, tipo, sottotipo, car FROM abilita WHERE id_abilita = $skill");
            $car = $PARAMETERS['names']['stats']['car'.$skill_info['car']]; // Nome della caratteristica della skill lanciata (Es. Mente, Destrezza, Potere, etc.)
            $bersaglio = isset($data['target']) ? $data['target'] : [];
            $id = gdrcd_filter('out', $skill);
            $can_send = (int)gdrcd_query("SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login'")['can_send'];
            $tiro = '';
            $turn = getTurn($id_role);

            /**************************** CONTROLLI   ************************************************/
                // Se il pg ha già lanciato un attacco e uno scudo nel turno precedente, non può attaccare
                if ($skill_info['tipo'] != 'Difensiva' && $can_send === 0) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione, non puoi agire: nel turno precedente hai già usato lo scudo difensivo.'));
                    exit;
                }
                // Se il pg non è nella role della chat
                if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                    exit;
                }
                
                // Obbligo di selezionare almeno un bersaglio
                if(empty($bersaglio)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione, selezione almeno un bersaglio!'));
                    exit;
                }

                // Se non ho tirato un talento e ho tirato un livello più alto di quello della mia skill, avviso con un messaggio in chat
                if ($skill_info['tipo'] != 'Talento' && $level_check['grado'] < $livello) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Hai scelto un livello superiore alla tua skill'));
                    exit;
                }

                // Se sto lanciando un attacco, devo verificare se non l'ho già lanciato in questo turno
                if($skill_info['car'] > 0 && checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'", "'difesa'", "'generica'"], $turn)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi effettuare due lanci nello stesso turno'));
                    exit;
                }

                // Se sto lanciando uno scudo, devo verificare se non l'ho già lanciato in questo turno
                if($skill_info['tipo'] === 'Difensiva' && checkMultipleLounch($id_role, $login, ["'difesa'"], $turn)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due scudi nello stesso turno'));
                    exit;
                }
                
                // Se sto lanciando una mental, devo verificare se non l'ho già lanciato in questo turno
                if($skill_info['sottotipo'] === 'comando' && checkMentaleComando($id_role, $login, $bersaglio, $turn)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due mentali di comando sullo stesso bersaglio per due turni di fila'));
                    exit;
                }
            /**************************** FINE  CONTROLLI   ************************************************/
            
            $messaggio = '';
            $sussurro = null;
            $dado_raw = 0;
            /**************************** COSTRUZIONE MESSAGGIO   ************************************************/
            switch ($skill_info['tipo']) {
                //  FISICA
                case 'Attacco base':
                case 'Attacco medio':
                case 'Attacco avanzato':
                    $messaggio .= "$login usa la skill fisica ".$skill_info['nome']." di livello $livello";
                    $diceLounch = lanciaStat($id_role, $login, implode(',', $bersaglio), true, $car, $car, $pg['car'.$skill_info['car']], $salute, 0, 0);
                    $dice = $diceLounch['risultato'];
                    $dado_raw = $diceLounch['dado_raw'];
                    $tiro = " con un tiro totale di $car di " . $diceLounch['risultato'];
                    $sussurro = $diceLounch['sussurro'];
                    break;
                // MENTALE
                case 'Mentale base':
                case 'Mentale media':
                case 'Mentale avanzata':
                case 'Mentale di attacco':
                    $messaggio .= "$login usa la skill mentale ".$skill_info['nome']." di livello $livello";
                    $diceLounch = lanciaStat($id_role, $login, implode(',', $bersaglio), true, $car, $car, $pg['car'.$skill_info['car']], $salute, 0, 0);
                    $dice = $diceLounch['risultato'];
                    $dado_raw = $diceLounch['dado_raw'];
                    $tiro = " con un tiro totale di $car di $dice";
                    $sussurro = $diceLounch['sussurro'];
                    break;
                // GENERICA
                case 'Generica base':
                case 'Generica avanzata':
                    $messaggio .= "$login usa la skill generica ".$skill_info['nome']." di livello $livello";
                    $car = 'generica';
                    $dice = mt_rand(1, 20);
                    $tiro = " con un tiro totale di $dice/20";
                    break;
                case 'Difensiva':
                    $car = 'difesa';
                    $messaggio .= "$login usa la skill difensiva ".$skill_info['nome']." di livello $livello";
                    $dice = mt_rand(1, 20);
                    $tiro = " con un tiro totale di $dice/20";
                    break;
                case 'Default':
                    $car = 'default';
                    $messaggio .= "$login usa la skill di default ".$skill_info['nome']." di livello $livello";
                    $dice = mt_rand(1, 20);
                    $tiro = " con un tiro totale di $dice/20";
                    break;
                case 'Talento':
                    $car = 'talento';
                    $messaggio .= "$login usa il talento ".$skill_info['nome'];
                    $talento = calcolaLivelloTalento($pg["car6"]); // In base a Tempra

                    // Se il talento è "Pronto Soccorso", gestisci la cura
                    if ($skill_info['nome'] == 'Pronto soccorso') $messaggio = gestisciProntoSoccorso($talento['livello'], $login, $bersaglio[0], $luogo, $salute);
                    else {
                        $messaggio .= " ".$talento['livello'];
                        $dice = mt_rand(1, 20);
                        $tiro = " con un tiro totale di $dice/20";
                    }
                    break;
                case 'Potere speciale':
                    $car = 'speciale';
                    $messaggio .= "$login usa la skill potere speciale ".$skill_info['nome']." di livello $livello";
                    break;
                case 'Skill Temporanea':
                    $car = 'temporanea';
                    $messaggio .= "$login usa la skill ".$skill_info['nome']." di livello $livello";
                    break;
                default:
                    $messaggio .= "$login usa una skill sconosciuta ".$skill_info['nome']." di livello $livello";
                    break;
            }
            /**************************** FINE  COSTRUZIONE MESSAGGIO   ************************************************/

            // Mesaggio in chat
            $messaggio .= " verso <u>".implode(',', $bersaglio)."</u>"; // Aggiungi il bersaglio al messaggio se presente
            $leggi = "<font color=\"#b4b6bf\">(<a href=\"#\" onclick=\"changeFrame('skill_desc.proc.php?id=$id');document.getElementById('id01').style.display='block'\">Leggi</a>)</font>";
            $messaggio .= " " . $leggi;
            $messaggio = gdrcd_filter('in', $messaggio); // Pulisco il messaggio
            $sussurro = isset($messaggio_talento['testo']) ? gdrcd_filter('in', $messaggio_talento['testo']) : $sussurro;

            /**************************** AZIONI   ************************************************/
            $id_fight = fight($id_role, $login, implode(',', $bersaglio), $skill, $livello, $car, $dice, 'usa una skill '.$skill_info['tipo'], 0, $dado_raw);
            chatInsertMessage($luogo, $login, null, $messaggio.$tiro, 'C', $sussurro, '', null); // Messaggio in chat

            // Notifica i bersagli in tempo reale per la risposta immediata
            if (in_array(strtolower($car), ['destrezza', 'mente', 'potere'])) {
                notifyAttackIncoming($id_role, $luogo, $login, $bersaglio, $car, $dice, $id_fight, $turn);
            }
            assegnaPuntoShin($luogo, $login); // Assegna il punto Shin se necessario
            gestionePoliziaAutomatica($luogo); // Gestione della polizia automatica
            gestisciSkillTemporanea($skill, $login); // Gestisci le skill temporanee
            /**************************** FINE  AZIONI   ************************************************/
            
            // Aggiorna la salute del personaggio se la skill lanciata non è un talento
            if($car != 'talento') gdrcd_query("UPDATE personaggio SET salute = salute-1 WHERE nome = '$login'");

            // Solo lo scudo (car='difesa') chiude il turno automaticamente se sent=1.
            // Le skill di attacco richiedono che il pg clicchi il bottone "Chiudi il turno".
            if ($car === 'difesa') checkAutoCloseAfterLaunch($id_role, $login, $luogo);

            // Risposta JSON per AJAX
            echo json_encode(array(
                'success' => true,
                'message' => 'Skill tirata con successo',
                'testo chat' => $messaggio,
                'caratteristica' => $skill_info['car']
            ));
            exit;
            break;
        /**
         * risposta_immediata — il bersaglio risponde a un attacco prima della fine turno.
         * Salva la scelta in role_fights (dado_risposta / difesa / subisce) e
         * pubblica un messaggio "Risultato provvisorio" visibile a tutti in chat.
         * L'elaborazione di fine turno legge questi record e li usa al posto dell'auto-difesa.
         */
        case 'risposta_immediata':
            $login    = $_SESSION['login'];
            $luogo    = $_SESSION['luogo'];
            $id_role  = locationActiveRole($luogo);
            $scelta   = $data['scelta']   ?? '';
            $id_fight = (int)($data['id_fight'] ?? 0);
            $turn     = getTurn($id_role);

            // Validazione: l'attacco esiste, appartiene alla role corrente e il login è tra i bersagli
            $fightRow = gdrcd_query("SELECT * FROM role_fights WHERE id = $id_fight AND id_role = $id_role AND turn = $turn");
            if (!$fightRow) {
                echo json_encode(['success' => false, 'message' => 'Attacco non trovato']);
                exit;
            }
            $fightTargets = array_map('trim', explode(',', $fightRow['target']));
            if (!in_array($login, $fightTargets)) {
                echo json_encode(['success' => false, 'message' => 'Non sei tra i bersagli di questo attacco']);
                exit;
            }

            $attacker  = $fightRow['striker'];
            $messaggio = '';
            $dice      = 0;

            switch ($scelta) {
                case 'dado':
                    $carDifesa  = getDefenceCar($fightRow['car'], $login);
                    $pg         = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
                    $diceResult = lanciaStat($id_role, $attacker, $login, true, $carDifesa['nome'], $carDifesa['nome'], $carDifesa['car'], $carDifesa['punti'], 0, 0);
                    $dice       = $diceResult['risultato'];
                    fight($id_role, $login, $attacker, 0, 0, 'dado_risposta', $dice, 'risposta immediata dado');
                    $sussurroStr = $diceResult['sussurro'] ? " ({$diceResult['sussurro']})" : '';
                    $messaggio = "<i>Risultato provvisorio:</i> $login tira il dado di difesa e ottiene <b>$dice</b>$sussurroStr contro l'attacco di $attacker";
                    break;

                case 'scudo':
                    $dice       = mt_rand(1, 20);
                    $shieldSkill = gdrcd_query("SELECT cpa.id_abilita FROM clgpersonaggioabilita cpa JOIN abilita a ON cpa.id_abilita = a.id_abilita WHERE cpa.nome = '$login' AND a.tipo = 'Difensiva' LIMIT 1");
                    $id_skill   = $shieldSkill ? (int)$shieldSkill['id_abilita'] : 0;
                    fight($id_role, $login, $login, $id_skill, 0, 'difesa', $dice, 'risposta immediata scudo');
                    $esito     = $dice >= 10 ? 'con successo' : 'senza successo';
                    $messaggio = "<i>Risultato provvisorio:</i> $login usa lo scudo con un tiro di <b>$dice/20</b> $esito";
                    break;

                case 'subisce':
                    fight($id_role, $login, $attacker, 0, 0, 'subisce', 0, 'risposta immediata subisce');
                    $messaggio = "<i>Risultato provvisorio:</i> $login decide di subire l'attacco di $attacker";
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Scelta non valida']);
                    exit;
            }

            $messaggio = gdrcd_filter('in', $messaggio);
            chatInsertMessage($luogo, $login, null, $messaggio, 'C', null, '', null);
            // Non chiamiamo checkTurnEnd: la difesa è una reazione, non l'azione principale del turno.
            // B deve ancora poter inviare la sua azione regolare senza essere bloccato da sent=1.

            if ($id_role) {
                if ($scelta === 'scudo') {
                    // Lo scudo (car='difesa') chiude il turno automaticamente se sent=1.
                    // Se non ha ancora inviato l'azione, il turno rimane aperto; checkTurnEnd lo
                    // rileverà tramite hasShieldLaunch quando invierà il testo.
                    checkAutoCloseAfterLaunch($id_role, $login, $luogo);
                } else {
                    // dado/subisce: semplici reazioni, non chiudono il turno del difensore.
                    checkTurnCanClose($id_role, $luogo);
                }
            }

            echo json_encode(['success' => true, 'scelta' => $scelta, 'dice' => $dice]);
            exit;
            break;

        /**
         * pending_attacks — restituisce gli attacchi del turno corrente che
         * coinvolgono il pg loggato come bersaglio e a cui non ha ancora risposto.
         * Usato da ChatShell al mount per ripristinare i pulsanti difesa dopo un refresh.
         */
        case 'pending_attacks':
            $login   = $_SESSION['login'];
            $luogo   = $_SESSION['luogo'];
            $id_role = locationActiveRole($luogo);

            if (!$id_role) { echo json_encode(['success' => true, 'attacks' => []]); exit; }

            $turn = getTurn($id_role);

            // Attacchi che: coinvolgono $login come bersaglio (target CSV) nel turno corrente
            // e per cui $login non ha ancora inserito dado_risposta/subisce/difesa
            $res = gdrcd_query("
                SELECT rf.id, rf.striker, rf.car, rf.dice
                FROM role_fights rf
                WHERE rf.id_role = $id_role
                  AND rf.turn    = $turn
                  AND rf.car     IN ('destrezza','mente','potere')
                  AND FIND_IN_SET('$login', rf.target) > 0
                  AND NOT EXISTS (
                    SELECT 1 FROM role_fights r2
                    WHERE r2.id_role = rf.id_role
                      AND r2.turn    = rf.turn
                      AND r2.striker = '$login'
                      AND (
                        (r2.car IN ('dado_risposta','subisce') AND r2.target = rf.striker)
                        OR r2.car = 'difesa'
                      )
                  )
            ", 'result');

            $attacks = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $canSend = (int)(gdrcd_query(
                    "SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login'"
                )['can_send'] ?? 1);

                $choices = ['dado'];

                if ($canSend === 1) {
                    $hasShield = (int)gdrcd_query(
                        "SELECT COUNT(*) as n FROM clgpersonaggioabilita cpa
                         JOIN abilita a ON cpa.id_abilita = a.id_abilita
                         WHERE cpa.nome = '$login' AND a.tipo = 'Difensiva'"
                    )['n'];
                    $alreadyShielded = checkMultipleLounch($id_role, $login, ["'difesa'"], $turn);
                    if ($hasShield > 0 && !$alreadyShielded) $choices[] = 'scudo';
                }
                $choices[] = 'subisce';

                $attacks[] = [
                    'id_fight' => (int)$row['id'],
                    'attacker' => $row['striker'],
                    'car'      => $row['car'],
                    'choices'  => $choices,
                ];
            }
            gdrcd_query($res, 'free');

            echo json_encode(['success' => true, 'attacks' => $attacks]);
            exit;
            break;

        /**
         * pending_close_turn — controlla se il pg loggato deve ancora confermare
         * la chiusura del turno (sent=1 ma close_turn=0).
         * Usato da ChatShell al mount per ripristinare il prompt dopo un refresh.
         */
        case 'pending_close_turn':
            $login   = $_SESSION['login'];
            $luogo   = $_SESSION['luogo'];
            $id_role = locationActiveRole($luogo);

            if (!$id_role) { echo json_encode(['success' => true, 'pending' => false]); exit; }

            $row = gdrcd_query("SELECT sent, close_turn FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login' AND `end` IS NULL");
            $pending = $row && (int)$row['sent'] === 1 && (int)$row['close_turn'] === 0 && !(bool)hasShieldLaunch($id_role, $login, getTurn($id_role));

            echo json_encode(['success' => true, 'pending' => $pending, 'id_role' => (int)$id_role]);
            exit;
            break;

        case 'tiraDadoChat': // Funzione ormai inutilizzata, la lascio soltanto perché magari un giorno vorremo reintrodurre i dadi
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $dice_type = isset($data['dice_type']) ? gdrcd_filter('in', gdrcd_angs($data['dice_type'])) : '';
            $dice_bonus = isset($data['dice_bonus']) ? (int)$data['dice_bonus'] : 0;
            $dice_malus = isset($data['dice_malus']) ? (int)$data['dice_malus'] : 0;
            $bersaglio = isset($data['target'][0]) ? $data['target'][0] : [];
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
            $salute = $pg["salute"];
            $caratteristica = '';
            $nome_tiro = '';
            $bonus_stats = true;
            $id_role = locationActiveRole($luogo);
            $turn = getTurn($id_role);

            /****************************  CONTROLLI   ************************************************/
            // Se il pg non è nella role della chat
            if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                exit;
            }
            
            // Per gli attacchi con dado il bersaglio deve essere uno solo
            if(empty($bersaglio)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, selezione errata del bersaglio!'));
                exit;
            }

            // Se sto lanciando un attacco, devo verificare se non l'ho già lanciato in questo turno
            if(checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'", "'difesa'"], $turn)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due attacchi nello stesso turno'));
                exit;
            }
            /**************************** FINE  CONTROLLI   ************************************************/
            switch ($dice_type) {
                case 'destrezza':
                    $caratteristica = $pg["car2"];
                    $nome_tiro = $dice_type;
                    break;
                case 'potere':
                    $caratteristica = $pg["car8"];
                    $nome_tiro = $dice_type;
                    break;
                case 'forza':
                    $caratteristica = $pg["car0"];
                    $nome_tiro = $dice_type;
                    break;
                case 'mente':
                    $caratteristica = $pg["car4"];
                    $nome_tiro = $dice_type;
                    break;
                case 'tempra':
                    $caratteristica = $pg["car6"];
                    $nome_tiro = $dice_type;
                    break;
                case 'D20':
                    $caratteristica = 0;
                    $nome_tiro = $dice_type;
                    break;
                case 'Usa dado master':
                    $caratteristica = 0;
                    $nome_tiro = "Master esegue un tiro totale di";
                    $bonus_stats = false;
                    break;
                case 'AttCreatura':
                    $caratteristica = 0;
                    $nome_tiro = "La creatura di $login attacca";
                    $bonus_stats = false;
                    break;
                case 'DifCreatura':
                    $caratteristica = 0;
                    $nome_tiro = "La creatura di $login si difende";
                    $bonus_stats = false;
                    break;
            }

            if ($caratteristica !== '') {
                $diceLounch = lanciaStat($id_role, $login, $bersaglio, $bonus_stats, $dice_type, $nome_tiro, $caratteristica, $salute, $dice_bonus, $dice_malus);
                fight($id_role, $login, $bersaglio, 0, 1, $nome_tiro, $diceLounch['risultato'], 'tira una stat'); // Funzione di gestione combattimenti
                chatInsertMessage($luogo, $login, null, $diceLounch['messaggio'], 'C', $diceLounch['sussurro'] != '' ? $diceLounch['sussurro'] : null);
            }

            // Risposta JSON per AJAX
            echo json_encode(array(
                'success' => true,
                'message' => 'Dado tirato con successo',
                'bonus e malus' => "$dice_bonus e $dice_malus",
                'bersaglio' => $bersaglio
            ));
            exit;
            break;
        case 'usaAttaccoChat':
            /****************************  Definizione variabili   ************************************************/
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $tipo_attacco = $data['tipo_attacco'];
            $bersaglio = isset($data['target'][0]) ? $data['target'][0] : [];
            $arma_body = isset($data['arma_body']) ? " mirando al ".$data['arma_body'] : '';
            $id_role = locationActiveRole($luogo);
            $turn = getTurn($id_role);
            $sussurro = '';
            $sussurro_specifico = '';
            $descrizione_attacco = '';
            $bonus_arma = 0;
            $check_abilita = 0;
            $malus_salute = 0;
            $is_devia = false;
            $car_fight = 'destrezza';

            // Ottieni informazioni sul pg
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
            $salute = $pg["salute"];

            // Calcolo del tiro di destrezza
            $destrezza = $pg["car2"] / 10;
            $d20 = mt_rand(1, 20);

            // Tiro fuori un numero, come bonus sul dado, in base a un tiro di tempra
            $bonus_talento = (int)calcolaLivelloTalento($pg["car6"])['bonus_talento'];
            /****************************  FINE Definizione variabili   ************************************************/

            /****************************  CONTROLLI   ************************************************/
                // Se il pg non è nella role della chat
                if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                    exit;
                }

                // Per gli attacchi con armi il bersaglio deve essere uno solo
                if(empty($bersaglio)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione, selezione errata del bersaglio!'));
                    exit;
                }

                // Se il pg ha già usato la sua azione nel turno precedente (attacco + scudo), non può attaccare
                $can_send = (int)gdrcd_query("SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login'")['can_send'];
                if ($can_send === 0) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione, non puoi attaccare: nel turno precedente hai già usato lo scudo difensivo.'));
                    exit;
                }

                // Se sto lanciando un attacco, devo verificare se non l'ho già lanciato in questo turno
                if(checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'", "'difesa'", "'devia'"], $turn)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi effettuare due lanci nello stesso turno'));
                    exit;
                }
            /**************************** FINE  CONTROLLI   ************************************************/

            if(is_numeric($tipo_attacco)) { // ***** Attacco con ARMA   *****
                $weapon = getWeaponAttack($tipo_attacco, $login, $bonus_talento);
                $bonus_arma = (int)$weapon['bonus_arma'];
                $check_abilita = (int)$weapon['check_abilita'];
                $sussurro_specifico = $weapon['sussurro_specifico'];
                $descrizione_attacco = $weapon['descrizione_attacco'];
            } elseif ($tipo_attacco == 'attacco_fisico') { // ***** Attacco FISICO   *****
                $descrizione_attacco = "attacca fisicamente";
                $check_abilita = gdrcd_query("SELECT COUNT(*) AS n_fisico FROM clgpersonaggioabilita WHERE id_abilita = 44 AND nome = '$login'")['n_fisico'];
                $sussurro_specifico = "+ $bonus_talento (talento corpo a corpo)";
            } elseif ($tipo_attacco == 'devia_attacco') { // ***** Devia attacco fisico   *****
                $is_devia = true;
                $car_fight = 'devia';
                $descrizione_attacco = 'devia attacco';
                // malus salute, totale, messaggio e sussurro calcolati nel blocco comune

            } elseif ($tipo_attacco == 'creatura') { // ***** Attacco con CREATURA   *****
                // Controllo se esiste la creatura
                $result = gdrcd_query("SELECT * FROM personaggio WHERE nome = 'creatura di $login'", 'result');

                // Controllo se effettivamente il pg ha evocato una creatura
                if(gdrcd_query($result, 'num_rows') > 0) {
                    $creatura = gdrcd_query($result, 'fetch');
                    $caratteristica = $creatura['car2']; // La creatura attacca con Destrezza
                    $descrizione_attacco = "attacca con creatura";
                    $nome_tiro = "La creatura di $login attacca";
                    $bonus_stats = false;
                    $dice_bonus = $dice_malus = 0; // Le creature non hanno bonus o malus

                    $diceLounch = lanciaStat($id_role, $creatura['nome'], $bersaglio, $bonus_stats, '', $nome_tiro, $caratteristica, $creatura['salute'], $dice_bonus, $dice_malus);
                    fight($id_role, $login, $bersaglio, 0, 1, $nome_tiro, $diceLounch['risultato'], 'creatura', 0, $diceLounch['dado_raw']); // Funzione di gestione combattimenti
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non hai evocato nessuna creatura.'));
                    exit;
                }
            }

            // Il bonus talento si applica solo se il pg ha la relativa abilità (corpo a corpo / tipo arma)
            $totale = $d20 + $destrezza + $bonus_arma + ($check_abilita > 0 ? $bonus_talento : 0);

            /*******    MALUS SALUTE    **********/
            if ($salute <= 50) {
                if ($salute > 40) $malus_salute = 1;
                elseif ($salute > 30) $malus_salute = 3;
                elseif ($salute > 20) $malus_salute = 5;
                elseif ($salute > 0) $malus_salute = 10;

                $totale -= $malus_salute;
            }
            /*******    FINE    MALUS SALUTE    **********/

            // Messaggio e sussurro dipendono dal tipo di attacco
            if ($is_devia) {
                $sussurro = "$d20/20 + $destrezza" . ($malus_salute > 0 ? " - $malus_salute (malus salute)" : '') . " = $totale";
                $messaggio = "$login tenta di deviare l'attacco fisico di <u>$bersaglio</u> con un tiro di destrezza di $totale";
            } else {
                $sussurro .= "$d20/20 + $destrezza" . ($check_abilita > 0 ? " + $bonus_arma (bonus arma) $sussurro_specifico" : '');
                if ($malus_salute > 0) $sussurro .= " - $malus_salute di malus per la salute = $totale";
                $messaggio = "$login $descrizione_attacco <u>$bersaglio</u>$arma_body con un tiro totale di destrezza di $totale";
            }

            // Registro l'attacco
            $id_fight = fight($id_role, $login, $bersaglio, 0, 1, $car_fight, $totale, $descrizione_attacco, 0, $d20);

            // Notifica bersaglio in tempo reale (solo per attacchi fisici/arma, non per devia)
            if (!$is_devia) {
                notifyAttackIncoming($id_role, $luogo, $login, [$bersaglio], 'destrezza', $totale, $id_fight, $turn);
            }

            chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
            gestionePoliziaAutomatica($luogo);

            echo json_encode(['success' => true, 'message' => $is_devia ? 'Deviazione dichiarata con successo.' : 'Attacco eseguito con successo.', 'tipo_attacco' => $tipo_attacco]);

            break;
        case 'tiraDadoGenericoChat':
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
            $salute = $pg["salute"];
            $back_chat = $pg['back_chat'] == 1 ? true : false;
            $dado_selezionato = isset($data['dado']) ? (int)$data['dado'] : 0;

            // Se il pg non è nella role della chat
            if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                exit;
            }
            
            if ($dado_selezionato > 0) {
                $num = mt_rand(1, $dado_selezionato);
                $messaggio = "$login esegue un tiro totale di $num/$dado_selezionato";

                chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
                /*
                if ($back_chat) chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
                else chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
                */
            }

            // Risposta JSON per AJAX
            echo json_encode(array('success' => true, 'message' => 'Dado generico tirato con successo'));
            exit;
            break;
        case 'get_chat_messages':
            // Se il client invia last=0 (primo caricamento o refresh di pagina),
            // azzera la sessione così la query restituisce i messaggi recenti
            if ((int)($data['last'] ?? -1) === 0) $_SESSION['last_message'] = 0;
            $last_message = $_SESSION['last_message'];
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $typeOrder = $PARAMETERS['mode']['chat_from_bottom'] == 'ON'? 'DESC' : 'ASC'; // Ordine della chat
            $check_expp = gdrcd_query("SELECT * FROM personaggio WHERE nome ='$login'"); // Recupero i dati dell'utente
            $check_back_chat = gdrcd_query("SELECT * FROM chat WHERE stanza = $luogo AND mittente = '$login' AND DATE_ADD(ora, INTERVAL 12 HOUR) >= NOW() AND (tipo = 'P' OR tipo = 'M')", 'result');
            $pgIsInRole = pgIsInRole($_SESSION['login'], $_SESSION['luogo']);

            // Costruzione query messaggi
            if ($_SESSION['admin'] == 1 || $check_expp['esperienza'] < 20) {
                $query = gdrcd_query(
                    "SELECT chat.id, chat.imgs, chat.mittente, chat.destinatario, chat.tipo, chat.ora, chat.testo, personaggio.url_img_chat, mappa.ora_prenotazione
                    FROM chat
                    INNER JOIN mappa ON mappa.id = chat.stanza
                    LEFT JOIN personaggio ON personaggio.nome = chat.mittente
                    WHERE chat.id > ".$last_message." 
                    AND (stanza = $luogo OR chat.tipo = 'G') 
                    AND chat.ora > IFNULL(mappa.ora_prenotazione, '0000-00-00 00:00:00') 
                    AND DATE_SUB(NOW(), INTERVAL 180 MINUTE) < ora 
                    ORDER BY id ". $typeOrder,
                    'result'
                );
            } else if(gdrcd_query($check_back_chat, 'num_rows') < 1) {
                $query = gdrcd_query(
                    "SELECT chat.id, chat.imgs, chat.mittente, chat.destinatario, chat.tipo, chat.ora, chat.testo, chat.backing, personaggio.url_img_chat, mappa.ora_prenotazione
                    FROM chat
                    INNER JOIN mappa ON mappa.id = chat.stanza
                    LEFT JOIN personaggio ON personaggio.nome = chat.mittente
                    WHERE chat.id > ".$last_message." 
                    AND (stanza = $luogo OR chat.tipo = 'G') 
                    AND chat.ora > IFNULL(mappa.ora_prenotazione, '0000-00-00 00:00:00') 
                    AND DATE_SUB(NOW(), INTERVAL 180 MINUTE) < ora 
                    AND chat.backing = '0' 
                    ORDER BY id ". $typeOrder,
                    'result'
                );
            } else {
                $query = gdrcd_query(
                    "SELECT chat.id, chat.imgs, chat.mittente, chat.destinatario, chat.tipo, chat.ora, chat.testo, personaggio.url_img_chat, mappa.ora_prenotazione
                    FROM chat
                    INNER JOIN mappa ON mappa.id = chat.stanza
                    LEFT JOIN personaggio ON personaggio.nome = chat.mittente
                    WHERE chat.id > ".$last_message." 
                    AND (stanza = $luogo OR chat.tipo = 'G') 
                    AND chat.ora > IFNULL(mappa.ora_prenotazione, '0000-00-00 00:00:00') 
                    AND DATE_SUB(NOW(), INTERVAL 180 MINUTE) < ora 
                    ORDER BY id ". $typeOrder, 'result' );
            }

            $chat_output = [];
            while ($row = gdrcd_query($query, 'fetch')) {
                $mittente = $row['mittente'];
                $add_chat = '';

                // Recupero immagini famiglia/mestiere
                $img_family = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5, gilda.nome as nome_gilda, ruolo.nome_ruolo, mestiere.nome as nome_mestiere, ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere, ruolo.immagine as immagine_famiglia, ruolo_mestiere.immagine as immagine_mestiere
                            FROM personaggio 
                            LEFT JOIN razza ON personaggio.id_razza=razza.id_razza
                            LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda
                            LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo
                            LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere
                            LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo
                            WHERE personaggio.nome = '".$mittente."'";
                $personaggi = gdrcd_query($img_family, 'result');
                $personaggio = gdrcd_query($personaggi, 'fetch');

                // Sicurezza XSS per immagini
                $row['url_img_chat'] = gdrcd_filter('fullurl', $row['url_img_chat']);

                $add_icon = '';
                if ($PARAMETERS['mode']['chaticons'] == 'ON') {
                    $icone_chat = explode(";", gdrcd_filter('out', $row['imgs']));
                    $add_icon = '<span class="chat_icons"> 
                                    <a href="#" onclick="Javascript: document.getElementById(\'message\').value=\'@'.$row['mittente'].'@\'; document.getElementById(\'message\').focus();">
                                        <img src="imgs/guilds/'.$personaggio['immagine_famiglia'].'">
                                    </a>
                                </span>';
                }

                $editAction = $login == $row['mittente'] ? 'onclick="editAction(this.innerText, '.$row['id'].');"' : '';
                $add_chat .= '<div id="'.$row['id'].'" class="chat_row_'.$row['tipo'].'" '.($row['tipo'] === 'P' ? $editAction : '').'>';
                        
                switch ($row['tipo']) {
                    case 'P': // messaggi parlati
                        $aperture = substr_count($row['testo'], '«');
                        $chiusure = substr_count($row['testo'], '»');
                        if ($aperture > $chiusure) {
                            $pos = strrpos($row['testo'], '«');
                            $fine_parlato = strpos($row['testo'], ' ', $pos);
                            if ($fine_parlato === false) $fine_parlato = strlen($row['testo']);
                            $row['testo'] = substr_replace($row['testo'], '</font>»', $fine_parlato, 0);
                        }
                        $row['testo'] = str_replace('«', '«<font color=#ce846f>', $row['testo']);
                        $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                        $row['testo'] = str_replace('[', '«<font color=#ce846f>', $row['testo']);
                        $row['testo'] = str_replace(']', '</font>»', $row['testo']);
                        if ($PARAMETERS['mode']['chat_avatar']=='ON' && !empty($row['url_img_chat'])) {
                            $add_chat .= '<img src="'.$row['url_img_chat'].'" class="chat_avatar" style="width:'.$PARAMETERS['settings']['chat_avatar']['width'].'px; height:'.$PARAMETERS['settings']['chat_avatar']['height'].'px;" />';
                        }
                        $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                        if (!empty($row['url_img_chat'])) $add_chat .= '<img src="'.$row['url_img_chat'].'" class="chat_avatar_inline" />';
                        if ($PARAMETERS['mode']['chaticons']=='ON') $add_chat .= $add_icon;
                        $add_chat .= '<span class="chat_name"><a href="main.php?page=scheda&pg='.$row['mittente'].'">'.$row['mittente'].'</a>';
                        if (!empty($row['destinatario'])) $add_chat .= '<span class="chat_tag"> [<font color=#d89d8c>'.gdrcd_filter('out',$row['destinatario']).'</font>]</span>';
                        $add_chat .= ': </span>';
                        $add_chat .= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $row['testo']).'</span>';
                        if ($PARAMETERS['mode']['chat_avatar']=='ON') $add_chat .= '<br style="clear:both;" />';
                        break;
                    case 'A': // azioni
                        $add_chat .= '<div class="chat_row_'.$row['tipo'].'">';
                        if ($PARAMETERS['mode']['chat_avatar']=='OFF' && !empty($row['url_img_chat'])) {
                            $add_chat .= '<img src="'.$row['url_img_chat'].'" class="chat_avatar" style="width:'.$PARAMETERS['settings']['chat_avatar']['width'].'px; height:'.$PARAMETERS['settings']['chat_avatar']['height'].'px;" />';
                        }
                        $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                        if (!empty($row['url_img_chat'])) $add_chat .= '<img src="'.$row['url_img_chat'].'" class="chat_avatar_inline" />';
                        if ($PARAMETERS['mode']['chaticons']=='ON') $add_chat .= $add_icon;
                        $add_chat .= '<span class="chat_name"><a href="#" onclick="document.getElementById(\'tag\').value=\''.$row['mittente'].'\'; document.getElementById(\'type\')[2].selected = \'1\'; document.getElementById(\'message\').focus();">'.$row['mittente'].'</a>';
                        if(!empty($row['destinatario'])) $add_chat .= '<span class="chat_tag"> ['.gdrcd_filter('out',$row['destinatario']).']</span>';
                        $add_chat .= '</span>';
                        $add_chat .= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $row['testo']).'</span>';
                        if ($PARAMETERS['mode']['chat_avatar']=='ON') $add_chat .= '<br style="clear:both;" />';
                        $add_chat .= '</div>';
                        break;
                    case 'S':
                    case 'Q':
                        if ($_SESSION['login']==$row['destinatario']) {
                            $add_chat .= '<span class="chat_sussurro">'.gdrcd_format_time($row['ora']).'</span> &nbsp;';
                            $msg_type = ($row['tipo']=='S') ? $MESSAGE['chat']['whisper']['by'] : $MESSAGE['chat']['whisper']['skill'];
                            $add_chat .= '<span class="chat_sussurro">'.$row['mittente'].' '.$msg_type.': </span>';
                            $add_chat .= '<span class="chat_sussurro">'.$row['testo'].'</span>';
                        } elseif ($_SESSION['login']==$row['mittente']) {
                            if ($row['tipo']=='S') {
                                $add_chat .= '<span class="chat_sussurro">'.gdrcd_format_time($row['ora']).'</span>&nbsp;';
                                $add_chat .= '<span class="chat_sussurro">'.$MESSAGE['chat']['whisper']['to'].' '.gdrcd_filter('out',$row['destinatario']).': </span>';
                                $add_chat .= '<span class="chat_sussurro">'.gdrcd_filter('out',$row['testo']).'</span>';
                            }
                        } elseif ($_SESSION['admin']==1) {
                            $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                            $add_chat .= '<span class="chat_name">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['from_to'].' '.gdrcd_filter('out',$row['destinatario']).' </span>';
                            $add_chat .= '<span class="chat_name">'.$row['testo'].'</span>';
                        }
                        break;

                    case 'N':
                        $row['testo'] = str_replace(['[',']','«','»'], ['[<font color=#7f99bc>', '</font>]', '«<font color=#7f99bc>', '</font>»'], $row['testo']);
                        $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                        $add_chat .= '<font color="white" style="font-size: 12px;"><b>'.$row['destinatario'].'</b></font> ';
                        $add_chat .= '<span class="chat_msg">'.gdrcd_chatcolor($row['testo']).'</span>';
                        break;
                    case 'M':
                        $row['testo'] = str_replace(['[',']','«','»','{','}','(br)','(center)','(/center)','(cor)','(/cor)','(link)','(/link)'],
                                                    ['[<font color=#d89d8c>', '</font>]', '«<font color=#d89d8c>', '</font>»',
                                                    '<font color=\'#ffd28a\' style=\'text-transform: uppercase;\'>','</font>','<br>','<center>','</center>','<i>','</i>','<a href=',' target="_blank"><b>Clikka qui</b></a>'],
                                                    $row['testo']);
                        $add_chat .= '<br>';
                        $add_chat .= '<table style="background-image: url(\'../themes/crystal/imgs/chat/sfondo_ms.png\'); background-repeat: repeat; border: 2px solid #07080e; width:95%;" align=center><tr><td align=justify>';
                        $add_chat .= '<span class="intestazione_master"><center>MASTER SCREEN ('.$row['mittente'].')<br>ORE '.gdrcd_format_time($row['ora']).'</center><br></span>';
                        $add_chat .= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], $row['testo'], true).'</span>';
                        $add_chat .= '</td></tr></table>';
                        break;
                    case 'I': $add_chat .= '<img class="chat_img" src="'.gdrcd_filter('fullurl',$row['testo']).'" />'; break;
                    case 'C':
                    case 'D':
                    case 'O':
                        if ($row['tipo'] != 'C') $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                        $add_chat .= '<span class="chat_skill">'.$row['testo'].'</span>';
                        break;
                    case 'X':
                    case 'G':
                        $prefix = ($row['tipo']=='X') ? 'MODERAZIONE' : 'FATO GLOBALE';
                        $add_chat .= '<br>';
                        $add_chat .= '<table style="background-image: url(\'../themes/crystal/imgs/chat/sfondo_ms.png\'); background-repeat: repeat; border: 2px solid #07080e; width:95%;" align=center><tr><td align=justify>';
                        $add_chat .= '<span class="intestazione_master"><center>'.$prefix.' ('.$row['mittente'].')<br>ORE '.gdrcd_format_time($row['ora']).'</center><br></span>';
                        $add_chat .= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out',$row['testo']), true).'</span>';
                        $add_chat .= '</td></tr></table>';
                        break;
                    case 'Z':
                        $add_chat .= '<span class="chat_msg_off">'.$row['mittente'].' scrive in OFF: ';
                        $add_chat .= '<span class="chat_msg_off">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
                        break;
                    case 'Y':
                        $add_chat .= '<center><iframe width="100" height="100" src="'.gdrcd_filter('fullurl',$row['testo']).'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                        break;
                }
                $add_chat .= '</div>';

                $chat_output[] = ['id' => (int)$row['id'], 'html' => $add_chat];

                // Se l'id dell'ultimo messaggio del ciclo while è maggiore dell'id salvato in sessione, ho dei nuovi messaggi
                if ($row['id'] > (int)$last_message) $last_message = $row['id'];

                // identifico se l'ultimo messaggio è dell'utente o meno
                $isLastMessageFromUser = ($row['mittente'] == $_SESSION['login']);
            }

            // Verifico se riprodurre l'audio per il nuovo messaggio
            $play = ($_SESSION['last_message'] > 0 && (isset($isLastMessageFromUser) && !$isLastMessageFromUser) && (isset($add_chat) && $add_chat != '')) ? true : false;

            // Aggiorno ultimo messaggio visualizzato
            $_SESSION['last_message'] = $last_message;

            echo json_encode([
                'status' => 'ok',
                'play' => $play,
                'activeRole' => locationActiveRole($_SESSION['luogo']),
                'canUsePanel' => $pgIsInRole || isAdminMasterMod($_SESSION),
                'canQuit' => $pgIsInRole,
                'charLimit' => (int)gdrcd_query("SELECT limite_lunghezza_massima FROM mappa WHERE id = ".$_SESSION['luogo'])['limite_lunghezza_massima'],
                'last_message' => (inT)$_SESSION['last_message'],
                'isLastMessageFromUser' => $isLastMessageFromUser,
                'add_chat' => $add_chat,
                'messages' => $chat_output // I messaggi della chat
            ]);
            break;
        case 'new_chat_message': // Nuovo messaggio in chat
            // Filtra e prepara i dati
            $login = $_SESSION['login'];
            $location = $_SESSION['luogo'];
            $chat_message = isset($data['message']) ? gdrcd_filter('in', gdrcd_angs($data['message'])) : '';
            $action_tag = isset($data['action_tag']) ? gdrcd_filter('in', gdrcd_angs($data['action_tag'])) : '';
            $tag_n_beyond = isset($data['tag']) ? gdrcd_filter('in', $data['tag']) : '';
            $type = isset($data['type']) ? gdrcd_filter('in', $data['type']) : '';
            $first_char = substr($chat_message, 0, 1);
            $second_char = substr($chat_message, 0, 4);
            $actual_healt = gdrcd_query("SELECT salute FROM personaggio WHERE nome = '$login'"); // Recupera la salute attuale del pg
            $id_role = locationActiveRole($location); // Recupera l'eventuale role attiva nella chat
            $pgIsInRole = pgIsInRole($login, $location); // Verifica se il pg è nella role della chat
            $m_type = determineMessageType($type, $first_char, $second_char, $chat_message, $login, $actual_healt, $_SESSION); // Determina il tipo di messaggio
            $typePermitted = in_array($m_type, array('S', 'M', 'G', 'X', 'Q', 'Z')); // Tipi di messaggi permessi senza role attiva
            $isAdminMasterMod = isAdminMasterMod($_SESSION); // Verifica se l'utente è admin/master/mod

            /****************************  CONTROLLI   ************************************************/
            // Se non c'è una role attiva e il tipo di messaggio non è tra quelli permessi senza role attiva, blocco l'invio
            if (!$id_role && !$typePermitted) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, devi avviare una role per inviare l\'azione!'));
                exit;
            }

            // Se il pg non è nella role della chat e sta cercando di inviare un messaggio che non è tra quelli permessi senza role attiva, blocco l'invio
            if (!$pgIsInRole && (!$isAdminMasterMod || !$typePermitted)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, devi unirti alla role per inviare l\'azione!'));
                exit;
            }
            
            // Se ho già inviato un'azione in questo turno
            $justSent = gdrcd_query("SELECT * FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login' AND `sent` = 1 AND `end` IS NULL", 'result');

            if ($justSent && gdrcd_query($justSent, 'num_rows') >= 1 && !$typePermitted) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi inviare due azioni nello stesso turno'));
                exit;
            }



            // Se il personaggio che invia è soggetto a una skill di durata, scalo i punti (integrità) — solo per le azioni P (una per turno)
            if ($m_type === 'P') checkSkillEffect($login, $location);
            /**************************** FINE  CONTROLLI   ************************************************/

            // Determina se è un sussurro, un messaggio normale o comando stanza privata
            if ($m_type === 'S') handleWhisperMessage($type, $chat_message, $tag_n_beyond, $m_type, $_SESSION);
            elseif ($type < "5" || $type > "7") handleNormalMessage($chat_message, $action_tag, $login, $m_type, $_SESSION, $PARAMETERS, $id_role);
            else handleRoomCommand($m_type, $tag_n_beyond, $_SESSION, $data);
            
            // Aggiorna tag nella sessione
            $_SESSION['tag'] = gdrcd_filter('in', $data['tag']);

            // Risposta JSON per AJAX
            echo json_encode(array(
                'success' => true,
                'message' => 'Messaggio registrato con successo',
                'data recived' => $data,
                'action_tag' => $action_tag,
                'tag'     => isset($_SESSION['tag']) ? $_SESSION['tag'] : "",
                'id_role' => $id_role,
                'pgIsInRole' => $pgIsInRole,
                'tag_n_beyond' => $tag_n_beyond
            ));
            exit;
        case 'saveEditAction':
            $content = isset($data['content']) ? gdrcd_filter('in', gdrcd_angs($data['content'])) : '';
            $id = isset($data['id']) ? gdrcd_filter('in', $data['id']) : '';

            // Modifico l'azione
            if(gdrcd_query("UPDATE chat set testo = '$content' WHERE id = $id")) echo json_encode(array('success' => true, 'message' => "Azione modificata con successo!"));
            else echo json_encode(array('success' => false, 'message' => "Errore nella modifica dell'azione."));

            break;
        case 'pulisciChat':
            if (isAdminMasterMod($_SESSION)) {
                // Recupera il nome della stanza basato sull'id della mappa
                $nome_stanza = gdrcd_query("SELECT nome FROM mappa WHERE id = ".$_SESSION['luogo']." LIMIT 1")['nome'];
                $msg = "Stanza $nome_stanza cancellata";
        
                // Cancellazione dei messaggi nella chat della stanza entro le ultime 3 ore (180 minuti)
                $pulisci = gdrcd_query("DELETE FROM chat WHERE stanza = ".$_SESSION['luogo']." AND DATE_ADD(ora, INTERVAL 180 MINUTE) >= NOW()");
        
                // Invia un sussurro all'utente per confermare l'avvenuta cancellazione
                chatInsertMessage($_SESSION['luogo'], 'System', $_SESSION['login'], $msg, 'S');
            }

            if($pulisci) echo json_encode(array('success' => true, 'message' => $msg));
            else echo json_encode(array('success' => false, 'message' => "Errore nella cancellazione della chat della stanza."));

            break;
        case 'curaPg':
            $login = $_SESSION['login'];
            $luogo = (int)$_SESSION['luogo'];

            if ($luogo !== 25) {
                echo json_encode(['success' => false, 'message' => 'Puoi usare questo comando solo in ospedale.']);
                exit;
            }

            $input_cura      = json_decode(file_get_contents('php://input'), true);
            $punti_richiesti = isset($input_cura['punti']) ? (int)$input_cura['punti'] : 0;

            if ($punti_richiesti < 1 || $punti_richiesti > 90) {
                echo json_encode(['success' => false, 'message' => 'Valore non valido. Inserisci un numero tra 1 e 90.']);
                exit;
            }

            $result = adjustPgStats($login, $punti_richiesti);
            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Personaggio non trovato.']);
                exit;
            }
            if ($result['delta_salute'] === 0) {
                echo json_encode(['success' => false, 'message' => 'La tua salute è già al massimo (100 PS).']);
                exit;
            }

            $punti_effettivi = $result['delta_salute'];
            $nuova_salute    = $result['salute'];
            $login_f         = gdrcd_filter('in', $login);

            // Accumula il debito giornaliero: un record per coppia (nome, data)
            gdrcd_query("INSERT INTO cure_emergenza (nome, punti, data_cura) VALUES ('$login_f', $punti_effettivi, CURDATE()) ON DUPLICATE KEY UPDATE punti = punti + $punti_effettivi");
            chatInsertMessage($luogo, 'System', $login, "ha ricevuto una cura di emergenza di $punti_effettivi PS. Salute attuale: $nuova_salute/100.", 'N');

            echo json_encode(['success' => true, 'punti_effettivi' => $punti_effettivi, 'nuova_salute' => $nuova_salute]);
            break;
        case 'setBackChat':
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];

            // Se il pg non è nella role della chat
            if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                exit;
            }

            $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='$login'");

            if ($check_backing['esperienza'] > 19 && $check_backing['back_chat'] == 0) {
                gdrcd_query("UPDATE personaggio SET back_chat = 1, last_date_back = NOW() WHERE nome = '$login'");

                chatInsertMessage($luogo, 'System', null, "Il player $login ha attivato il blocco backchat", 'Z');
                
                $title = 'Disattiva backchat';
                $img = 'BackchatON.png';
                $msg = "Blocco backchat attivato!";
            } else if ($check_backing['esperienza'] > 19 && $check_backing['back_chat'] == 1) {
                gdrcd_query("UPDATE personaggio SET back_chat = 0 WHERE nome = '$login'");

                chatInsertMessage($luogo, 'System', null, "Il player $login ha disattivato il blocco backchat", 'Z');
                
                $title = 'Attiva backchat';
                $img = 'BackchatOFF.png';
                $msg = "Blocco backchat disattivato!";
            }

            echo json_encode(array('success' => true, 'message' => $msg, 'image' => $img, 'title' => $title));

            break;
        case 'setCharLimit': // Imposta limite di tempo per round quest master
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $caratteri = (int)$data['charLimit'];

            // Fallimenti
            if ($luogo < 1) {
                echo json_encode(array('success' => false, 'message' => 'Devi essere in una location di gioco'));
                return false;
            }
            // Se il pg non è nella role della chat
            if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                exit;
            }
            // Se ho impostato un limite inferiore a 150 caratteri
            if ($caratteri < 150) {
                echo json_encode(array('success' => false, 'message' => 'I caratteri devono essere almeno 150'));
                exit;
            }
            // FINE fallimenti

            // Imposta il limite di caratteri per l'utente
            if (imposta_limite_caratteri_utente($login, $luogo, $caratteri)) aggiorna_limite_globale($luogo, $caratteri, $login); // Aggiorna il limite globale

            echo json_encode(array('success' => true, 'message' => 'Limite caratteri impostato correttamente.'));

            break;
        case 'revocaLimiteCaratteri':  // Revoco il limite di caratteri della chat se un pg non è d'accordo
            $luogo = $data['luogo'];
            $vecchio_limite = $data['vecchio_limite'];
            $nuovo_limite = $data['nuovo_limite'];
            $user = $data['user'];
            $testo = "E' stato revocato il limite di $nuovo_limite caratteri. Il limite è tornato a $vecchio_limite caratteri.";
            $testo = gdrcd_filter('in', $testo);

            // Controllo se la revoca è già stata fatta da qualcuno
            $check_sys_msg = gdrcd_query("SELECT * FROM chat WHERE stanza = $luogo AND mittente = 'System' AND testo = '$testo'", 'result');
            
            if (gdrcd_query($check_sys_msg, 'num_rows') == 0 &&
                gdrcd_query("UPDATE scelte_utenti SET lunghezza_massima = '$vecchio_limite', timestamp_modifica = NOW() WHERE nome = '$user' AND id_luogo = '$luogo'") &&
                gdrcd_query("UPDATE mappa SET timestamp_modifica_limite = NOW(), limite_lunghezza_massima = $vecchio_limite WHERE id = '$luogo'") &&
                chatInsertMessage($luogo, 'System', null, $testo, 'N', null)
            ) echo json_encode(['success' => true, 'message' => $testo]);
            else echo json_encode(['error' => false, 'message' => 'Limite già revocato da un altro pg']);
            
            break;
        case 'usaOggettoChat':
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $id_oggetto = (int) gdrcd_filter('post', $data['objChat']);

            // Se il pg non è nella role della chat
            if (!pgIsInRole($login, $luogo) && !isAdminMasterMod($_SESSION)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                exit;
            }

            // Recupera i dettagli dell'oggetto dal database
            $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id_oggetto");
            $parametri = gdrcd_query("SELECT salute, integrita FROM personaggio WHERE nome = '$login'");
            $salute_attuale = $parametri['salute'];
            $integrita_attuale = $parametri['integrita'];
            $nome_oggetto = $oggetto['nome'];
            $messaggio = "$login ha usato l\'oggetto " . gdrcd_filter('in', $nome_oggetto) . ".";

            $oggetto_usato = false;

            // Logica per curare SALUTE
            if ($oggetto['heal'] > 0) {
                if ($salute_attuale >= 100) chatInsertMessage($_SESSION['luogo'], 'System', $login, "La tua salute è già al massimo!", 'Q');
                elseif ($salute_attuale < 50) chatInsertMessage($_SESSION['luogo'], 'System', $login, "Gli oggetti non possono curare ferite così gravi!", 'Q');
                else {
                    $new_salute = min($salute_attuale + $oggetto['heal'], 100);
                    gdrcd_query("UPDATE personaggio SET salute = $new_salute WHERE nome = '$login'");
                    $oggetto_usato = true;
                }
            }

            // Logica per curare INTEGRITÀ
            if ($oggetto['integrita'] > 0) {
                if ($integrita_attuale >= 10) chatInsertMessage($_SESSION['luogo'], 'System', $login, "La tua integrità è già al massimo!", 'Q');
                elseif ($integrita_attuale < 3) chatInsertMessage($_SESSION['luogo'], 'System', $login, "Gli oggetti non possono curare ferite mentali così gravi!", 'Q');
                else {
                    $new_integrita = min($integrita_attuale + $oggetto['integrita'], 10);
                    gdrcd_query("UPDATE personaggio SET integrita = $new_integrita WHERE nome = '$login'");
                    $oggetto_usato = true;
                }
            } // Se almeno uno dei due effetti è stato applicato, consumo l'oggetto e invio il messaggio
            if ($oggetto_usato) {
                chatInsertMessage($_SESSION['luogo'], $login, null, $messaggio, 'C');

                gestisciInventario($login, $id_oggetto, $cariche);
            } // Verifica se l'oggetto è potenziabile (isTemp = 1)
            elseif ($oggetto['isTemp'] == 1) {
                // Controlla se c'è già un potenziamento attivo
                $potenziamento_attivo = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE nome = '$login' AND used = 1", 'result');
                
                // Se c'è già un potenziamento attivo, invia un messaggio privato di tipo "Q" e non usare l'oggetto
                if((gdrcd_query($potenziamento_attivo, 'num_rows') > 0)) chatInsertMessage($_SESSION['luogo'], 'System', $login, "Hai già un potenziamento attivo. Non puoi usare un altro oggetto potenziabile.", 'Q');
                else {
                    // Applica il potenziamento e segna l'oggetto come usato
                    gdrcd_query("UPDATE personaggio SET 
                        car0 = car0 + {$oggetto['bonus_car1_extra']}, 
                        car2 = car2 + {$oggetto['bonus_car2_extra']}, 
                        car4 = car4 + {$oggetto['bonus_car3_extra']}, 
                        car6 = car6 + {$oggetto['bonus_car4_extra']}, 
                        car8 = car8 + {$oggetto['bonus_car5_extra']}
                        WHERE nome = '$login'");

                    $data_scadenza = date('Y-m-d H:i:s', strtotime("+{$oggetto['temp_giorni']} days"));
                    gdrcd_query("UPDATE clgpersonaggiooggetto SET used = 1, data_scadenza = '$data_scadenza' WHERE id_oggetto = '$id_oggetto' AND nome = '$login'");

                    // Invia un messaggio in chat per indicare che l'oggetto è stato usato
                    chatInsertMessage($_SESSION['luogo'], $login, null, "$login ha usato l\'oggetto $nome_oggetto, potenziando i suoi parametri.", 'C', "Hai potenziato i tuoi parametri con $nome_oggetto. Effetto attivo fino al $data_scadenza.");
                }
            }
            // Se l'oggetto non è curativo né potenziabile, applica la logica di default
            else {
                chatInsertMessage($_SESSION['luogo'], $login, null, $messaggio, 'C');
                gestisciInventario($login, $id_oggetto, $cariche);
            }

            echo json_encode(array('success' => true, 'message' => 'Oggetto usato con successo.'));

            break;
        case 'editMasterPgChat':
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $nome_personaggio = addslashes(gdrcd_filter('post', $data['nome_personaggio_hidden']));
            $note_fato = addslashes(gdrcd_filter('post', $data['note_fato']));
            $particolari = addslashes(gdrcd_filter('post', $data['particolari']));
            $salute = (int)gdrcd_filter('post', $data['salute']);
            $integrita = (int)gdrcd_filter('post', $data['integrita']);
            $notorieta = (int)gdrcd_filter('post', $data['notorieta']);
            $soldi = (int)gdrcd_filter('post', $data['soldi']);

            //recupero valori attuali
            $actual = gdrcd_query("SELECT salute, integrita, notorieta, soldi FROM personaggio WHERE nome = '$nome_personaggio'");

            // Aggiorna i dati del personaggio nel database
            $query = "UPDATE personaggio SET 
                        note_fato = '$note_fato', 
                        particolari = '$particolari', 
                        salute = $salute, 
                        integrita = $integrita, 
                        notorieta = $notorieta, 
                        soldi = $soldi 
                    WHERE nome = '$nome_personaggio'";
            gdrcd_query($query);
            
            // Invia i messaggi in chat a seconda dei cambiamenti effettuati
            $msg = '';
            if ($salute != $actual['salute']) $msg .= "La tua salute è stata modificata in $salute/100.";
            if ($integrita != $actual['integrita']) $msg .= " La tua integrità è stata modificata in $integrita/10.";
            if ($soldi != $actual['soldi']) $msg .= " Il tuo conto adesso ha $soldi monete.";
            if ($notorieta != $actual['notorieta']) $msg .= " La tua notorietà pubblica adesso è di $notorieta.";
            
            chatInsertMessage($luogo, 'System', $nome_personaggio, $msg, 'S', null);

            echo json_encode(array('success' => true, 'message' => 'Note del master salvate con successo.', 'nome_personaggio' => $nome_personaggio));

            break;
        case 'newMasterPng':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $location = $_SESSION['luogo'];
            $id_role  = locationActiveRole($location);
            $pngName  = gdrcd_filter('post', $data['pngName']);
            if (!$pngName) { echo json_encode(['success' => false, 'message' => 'Nome PNG mancante']); exit; }

            $pngSalute    = max(1, min(200, (int)($data['salute']    ?? 100)));
            $pngDestrezza = max(1, min(20,  (int)($data['destrezza'] ?? 7)));
            $pngPotere    = max(1, min(20,  (int)($data['potere']    ?? 7)));
            $pngMente     = max(1, min(20,  (int)($data['mente']     ?? 7)));
            $pngTempra    = max(1, min(20,  (int)($data['tempra']    ?? 7)));

            // Se non c'è una role attiva nella stanza, ne apre una nuova
            if (!$id_role) {
                gdrcd_query("INSERT INTO role_sessions (`location`, `start`) VALUES ($location, NOW())");
                $id_role = (int)gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
            }

            // Inserisce o aggiorna il png nella tabella personaggi
            $existingPng = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '$pngName'", 'result');
            if (gdrcd_query($existingPng, 'num_rows') == 0) {
                gdrcd_query("INSERT INTO personaggio (nome, salute, salute_max, integrita, car2, car4, car6, car8)
                    VALUES ('$pngName', $pngSalute, $pngSalute, 100, $pngDestrezza, $pngMente, $pngTempra, $pngPotere)");
            } else {
                gdrcd_query("UPDATE personaggio SET salute=$pngSalute, car2=$pngDestrezza, car4=$pngMente, car6=$pngTempra, car8=$pngPotere
                    WHERE nome='$pngName'");
            }

            addPgToRole($id_role, $pngName, $location, 1);
            notifySocketServer('role:update', 'loc:' . $location);

            echo json_encode(['success' => true, 'message' => 'PNG aggiunto con successo!']);
            break;

        /**
         * getPgList — lista DISTINCT dei pg partecipanti alla role corrente (esclusi PNG).
         * Include anche i pg usciti (end IS NOT NULL) per consentire al master
         * di intervenire su personaggi che hanno lasciato il turno.
         */
        case 'getPgList':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $luogo   = (int)$_SESSION['luogo'];
            $id_role = locationActiveRole($luogo);
            if (!$id_role) { echo json_encode(['success' => true, 'pgs' => []]); exit; }
            $res = gdrcd_query(
                "SELECT DISTINCT pg_name FROM role_session_players WHERE id_role = $id_role AND png = 0",
                'result'
            );
            $pgs = [];
            while ($row = gdrcd_query($res, 'fetch')) $pgs[] = $row['pg_name'];
            echo json_encode(['success' => true, 'pgs' => $pgs]);
            break;

        /**
         * getPgData — carica i campi modificabili di un personaggio (uso master).
         */
        case 'getPgData':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $pgName = gdrcd_filter('get', $_GET['pg'] ?? '');
            if (!$pgName) { echo json_encode(['success' => false, 'message' => 'Nome mancante']); exit; }
            $pg = gdrcd_query("SELECT note_fato, particolari, salute, integrita, notorieta FROM personaggio WHERE nome = '$pgName' LIMIT 1");
            if (!$pg) { echo json_encode(['success' => false, 'message' => 'PG non trovato']); exit; }
            echo json_encode(['success' => true, 'data' => $pg]);
            break;

        /**
         * savePgData — salva i campi modificabili di un personaggio (uso master).
         * Campi: note_fato, particolari, salute, integrita, notorieta.
         */
        case 'savePgData':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $pgName     = gdrcd_filter('post', $data['pg'] ?? '');
            $noteFato   = gdrcd_filter('in', $data['note_fato']   ?? '');
            $particolari= gdrcd_filter('in', $data['particolari'] ?? '');
            $salute     = max(0, (int)($data['salute']    ?? 0));
            $integrita  = max(0, min(100, (int)($data['integrita']  ?? 0)));
            $notorieta  = max(0, min(100, (int)($data['notorieta']  ?? 0)));
            if (!$pgName) { echo json_encode(['success' => false, 'message' => 'Nome mancante']); exit; }
            gdrcd_query("UPDATE personaggio SET
                note_fato='$noteFato', particolari='$particolari',
                salute=$salute, integrita=$integrita, notorieta=$notorieta
                WHERE nome='$pgName'");
            echo json_encode(['success' => true, 'message' => 'Personaggio aggiornato.']);
            break;

        /**
         * newMasterPngAttack — il master attacca uno o più bersagli con un PNG,
         * usando il sistema di combattimento completo (fight + notifyAttackIncoming).
         * Sostituisce il vecchio newMasterPngAction per gli attacchi con dado.
         */
        case 'newMasterPngAttack':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $luogo   = (int)$_SESSION['luogo'];
            $login   = $_SESSION['login'];
            $id_role = locationActiveRole($luogo);
            if (!$id_role) { echo json_encode(['success' => false, 'message' => 'Nessuna role attiva']); exit; }

            $pngName       = gdrcd_filter('post', $data['pngName']      ?? '');
            $pngMessage    = gdrcd_filter('in',   gdrcd_filter('post', $data['pngMessage'] ?? ''));
            $pngCar        = $data['pngCar']       ?? 'destrezza';
            $targets       = is_array($data['targets'] ?? null) ? $data['targets'] : [];
            $damagePercent = max(1, min(100, (int)($data['damagePercent'] ?? 100)));
            $turn          = getTurn($id_role);

            if (!$pngName) { echo json_encode(['success' => false, 'message' => 'Nome PNG mancante']); exit; }

            // Mappa nome caratteristica → colonna DB
            $carColMap = ['destrezza'=>'car2','mente'=>'car4','tempra'=>'car6','potere'=>'car8'];
            $carCol    = $carColMap[$pngCar] ?? 'car2';

            // Recupera il valore della caratteristica dal PNG
            $pngRow = gdrcd_query("SELECT $carCol FROM personaggio WHERE nome = '$pngName' LIMIT 1");
            if (!$pngRow) { echo json_encode(['success' => false, 'message' => 'PNG non trovato']); exit; }
            $carVal = (int)$pngRow[$carCol];

            // Calcola il tiro: bonus dalla caratteristica (formula uguale a lanciaStat)
            $bonus  = (int)(($carVal / 10) - 1);
            $raw    = mt_rand(1, 20);
            $dice   = max(1, $raw + $bonus);

            // Messaggio azione in chat
            if ($pngMessage) {
                checkTurnEnd($luogo, $pngName, $id_role);
                chatInsertMessage($luogo, $login, $pngName, $pngMessage, 'N', null);
            }

            if (!empty($targets)) {
                $targetsStr = implode(',', array_map(fn($t) => gdrcd_filter('post', $t), $targets));
                $id_fight   = fight($id_role, $pngName, $targetsStr, 0, 0, $pngCar, $dice, 'attacco PNG master', $damagePercent);
                notifyAttackIncoming($id_role, $luogo, $pngName, $targets, $pngCar, $dice, $id_fight, $turn);
            }

            echo json_encode(['success' => true, 'message' => 'Attacco PNG inviato.', 'dice' => $dice]);
            break;

        /**
         * pending_attacks_png — attacchi del turno corrente verso PNG senza risposta.
         * Usato dal pannello master per mostrare i pulsanti dado/scudo/subisce per i PNG.
         */
        case 'pending_attacks_png':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $luogo   = (int)$_SESSION['luogo'];
            $id_role = locationActiveRole($luogo);
            if (!$id_role) { echo json_encode(['success' => true, 'attacks' => []]); exit; }
            $turn = getTurn($id_role);

            // Trova i PNG attivi nella role corrente
            $pngRes = gdrcd_query(
                "SELECT DISTINCT pg_name FROM role_session_players WHERE id_role = $id_role AND png = 1",
                'result'
            );
            $pngNames = [];
            while ($row = gdrcd_query($pngRes, 'fetch')) $pngNames[] = "'" . gdrcd_filter('in', $row['pg_name']) . "'";

            if (empty($pngNames)) { echo json_encode(['success' => true, 'attacks' => []]); exit; }
            $pngList = implode(',', $pngNames);

            $res = gdrcd_query("
                SELECT rf.id, rf.striker, rf.car, rf.dice, rf.target
                FROM role_fights rf
                WHERE rf.id_role = $id_role
                  AND rf.turn    = $turn
                  AND rf.car     IN ('destrezza','mente','potere')
                  AND NOT EXISTS (
                    SELECT 1 FROM role_fights r2
                    WHERE r2.id_role = rf.id_role
                      AND r2.turn    = rf.turn
                      AND (r2.car IN ('dado_risposta','subisce','difesa'))
                      AND r2.target  = rf.striker
                  )
            ", 'result');

            $attacks = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $rowTargets = array_map('trim', explode(',', $row['target']));
                foreach ($rowTargets as $t) {
                    if (!in_array("'$t'", $pngNames)) continue;
                    $attacks[] = [
                        'id_fight' => (int)$row['id'],
                        'attacker' => $row['striker'],
                        'car'      => $row['car'],
                        'dice'     => (int)$row['dice'],
                        'png'      => $t,
                        'choices'  => ['dado', 'subisce'],
                    ];
                }
            }
            echo json_encode(['success' => true, 'attacks' => $attacks]);
            break;

        /**
         * risposta_immediata_png — il master risponde a un attacco per conto di un PNG.
         * Logica identica a risposta_immediata ma usa $pngName invece di $login.
         */
        case 'risposta_immediata_png':
            if (!isAdminMasterMod($_SESSION)) { echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }
            $luogo    = (int)$_SESSION['luogo'];
            $id_role  = locationActiveRole($luogo);
            $pngName  = gdrcd_filter('post', $data['pngName']  ?? '');
            $scelta   = $data['scelta']   ?? '';
            $id_fight = (int)($data['id_fight'] ?? 0);
            $turn     = getTurn($id_role);

            if (!$pngName || !$id_role) { echo json_encode(['success' => false, 'message' => 'Dati mancanti']); exit; }

            $fightRow = gdrcd_query("SELECT * FROM role_fights WHERE id = $id_fight AND id_role = $id_role AND turn = $turn");
            if (!$fightRow) { echo json_encode(['success' => false, 'message' => 'Attacco non trovato']); exit; }
            $fightTargets = array_map('trim', explode(',', $fightRow['target']));
            if (!in_array($pngName, $fightTargets)) { echo json_encode(['success' => false, 'message' => 'Il PNG non è tra i bersagli']); exit; }

            $attacker  = $fightRow['striker'];
            $messaggio = '';
            $dice      = 0;

            switch ($scelta) {
                case 'dado':
                    $carDifesa  = getDefenceCar($fightRow['car'], $pngName);
                    $diceResult = lanciaStat($id_role, $attacker, $pngName, true, $carDifesa['nome'], $carDifesa['nome'], $carDifesa['car'], $carDifesa['punti'], 0, 0);
                    $dice       = $diceResult['risultato'];
                    fight($id_role, $pngName, $attacker, 0, 0, 'dado_risposta', $dice, 'risposta PNG dado');
                    $messaggio = "<i>Risultato provvisorio:</i> $pngName tira il dado di difesa e ottiene <b>$dice</b> contro l'attacco di $attacker";
                    break;
                case 'subisce':
                    fight($id_role, $pngName, $attacker, 0, 0, 'subisce', 0, 'risposta PNG subisce');
                    $messaggio = "<i>Risultato provvisorio:</i> $pngName subisce l'attacco di $attacker";
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Scelta non valida']); exit;
            }

            $messaggio = gdrcd_filter('in', $messaggio);
            chatInsertMessage($luogo, $pngName, null, $messaggio, 'C', null, '', null);
            checkTurnCanClose($id_role, $luogo);
            echo json_encode(['success' => true, 'scelta' => $scelta, 'dice' => $dice]);
            break;
        case 'newMasterPngAction':
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $pngName = gdrcd_filter('post', $data['pngName']);
            $pngMessage = gdrcd_filter('in', gdrcd_filter('post', $data['pngMessage']));
            $pngBonus = gdrcd_filter('post', $data['pngBonus']);
            $pngCar = gdrcd_filter('post', $data['pngCar']);
            $id_role = locationActiveRole($luogo); // Recupera l'eventuale role attiva nella chat

            // Se il messaggio è presente, inserisci un messaggio di tipo N
            if (!empty($pngMessage)) {
                checkTurnEnd($luogo, $pngName, $id_role); // Se è un'azione o un master, controllo il turno
                chatInsertMessage($luogo, $login, $pngName, $pngMessage, 'N', null);
            }
            if (!empty($pngBonus) && !empty($pngCar)) {
                // Se il messaggio è vuoto, ma "pngBonus" e "usa parametro" sono compilati
                $num = mt_rand(1, 20);
                $numtot = $num + $pngBonus;

                // Gestione dei messaggi in base al parametro usato
                switch ($pngCar) {
                    case 'destrezza': $pngMessage = "$pngName esegue un tiro totale di destrezza di $numtot ($num/20 + $pngBonus)"; break;
                    // case 'Usa forza': $pngMessage = "$pngName esegue un tiro totale di forza di $numtot ($num/20 + $pngBonus)"; break;
                    case 'mente': $pngMessage = "$pngName esegue un tiro totale di mente di $numtot ($num/20 + $pngBonus)"; break;
                    case 'tempra': $pngMessage = "$pngName esegue un tiro totale di tempra di $numtot ($num/20 + $pngBonus)"; break;
                    case 'potere': $pngMessage = "$pngName esegue un tiro totale di potere di $numtot ($num/20 + $pngBonus)"; break;
                    case 'dado':
                        $num_dado = mt_rand(1, $pngBonus);
                        $pngMessage = "$pngName lancia $num_dado/$pngBonus";
                        break;
                    default: $pngMessage = "$pngName esegue un'azione non specificata."; break;
                }
                chatInsertMessage($luogo, $pngName, null, $pngMessage, 'C', null);
            }

            echo json_encode(array('success' => true, 'message' => 'Azione PNG inviata con successo.'));

            break;
        // -------------------------------------------------------------------------
        // SHELL — dati iniziali per il componente React ChatShell.jsx
        //
        // Restituisce in un'unica chiamata tutto ciò che frame_chat.inc.php
        // calcolava lato PHP: info stanza, preferenze tipografiche, maxlength
        // textarea, visibilità pulsanti, inventario (oggetti/armi) e abilità.
        // Chiamato da ChatShell.jsx al mount per popolare l'intera UI della chat.
        // -------------------------------------------------------------------------
        case 'shell':
            $login   = $_SESSION['login'];
            $luogo   = (int)$_SESSION['luogo'];
            $login_f = gdrcd_filter('in', $login);

            // Dati stanza (aggiunge limite_lunghezza_massima per il maxlength)
            $info = gdrcd_query("SELECT nome, descrizione, stanza_apparente, invitati,
                        privata, proprietario, scadenza, limite_lunghezza_massima
                    FROM mappa WHERE id = $luogo LIMIT 1");

            // Dati personaggio (solo i campi utili alla shell)
            $pg = gdrcd_query("SELECT salute, integrita, esperienza, back_chat,
                        preferenze_chat
                    FROM personaggio WHERE nome = '$login_f' LIMIT 1");

            // Preferenze tipografiche (font, colori, grandezza, interlinea)
            $preferenze = !empty($pg['preferenze_chat'])
                ? (json_decode($pg['preferenze_chat'], true) ?? [])
                : [];

            // Verifica accesso stanza privata — stessa logica di frame_chat.inc.php
            $allowance = true;
            if ((int)$info['privata'] === 1) {
                $allowance    = false;
                $scadenza_ok  = $info['scadenza'] > date('Y-m-d H:i:s');
                $prop_ok      = $info['proprietario'] === gdrcd_capital_letter($login);
                $gilda_ok     = !empty($_SESSION['gilda'])
                                && strpos($_SESSION['gilda'], $info['proprietario']) !== false;
                $invit_ok     = strpos($info['invitati'], gdrcd_capital_letter($login)) !== false;
                $spy_ok       = ($PARAMETERS['mode']['spyprivaterooms'] === 'ON')
                                && ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1);
                if ($scadenza_ok && ($prop_ok || $gilda_ok || $invit_ok || $spy_ok)) {
                    $allowance = true;
                }
            }

            // Lunghezza massima textarea (default 2000 se non impostata in DB)
            $maxlength = (int)($info['limite_lunghezza_massima'] ?? 0) ?: 2000;

            // Flag staff
            $is_admin  = (int)$_SESSION['admin']      === 1;
            $is_master = (int)$_SESSION['master']     === 1;
            $is_mod    = (int)$_SESSION['moderatore'] === 1;
            $is_staff  = $is_admin || $is_master || $is_mod;

            // Visibilità pulsanti
            $show_backchat   = (float)$pg['esperienza'] > 19;
            $backchat_on     = (int)$pg['back_chat'] === 1;
            $show_cura       = ($luogo === 25 && (int)$pg['salute'] > 0 && (int)$pg['salute'] < 100);
            $show_pulisci    = $is_staff;
            $show_scacchiera = ($is_admin || $is_master) && $luogo !== 25;
            $can_master_msg  = $is_admin || $is_master;

            // Membro del mestiere Ospedale (id_mestiere = 10): può curare altri pg
            $is_ospedale_row = gdrcd_query(
                "SELECT 1 FROM clgpersonaggiomestiere
                 JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo
                 WHERE ruolo_mestiere.mestiere = 10
                   AND clgpersonaggiomestiere.personaggio = '$login_f'
                   AND clgpersonaggiomestiere.conferma_mestiere = 1
                 LIMIT 1"
            );
            $is_ospedale = !empty($is_ospedale_row);

            // Helper: recupera oggetti per categoria come array JSON
            $fetch_oggetti = function(string $categoria, string $extra = '') use ($login_f): array {
                $res  = gdrcd_query("SELECT c.id_oggetto, o.nome, c.cariche
                    FROM clgpersonaggiooggetto c
                    JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                    WHERE c.nome = '$login_f' AND o.categoria = '$categoria'
                    AND c.posizione > 0 AND c.cariche > 0 $extra
                    ORDER BY o.nome", 'result');
                $out = [];
                while ($row = gdrcd_query($res, 'fetch')) {
                    $out[] = [
                        'id'      => (int)$row['id_oggetto'],
                        'nome'    => gdrcd_filter('out', $row['nome']),
                        'cariche' => (int)$row['cariche'],
                    ];
                }
                gdrcd_query($res, 'free');
                return $out;
            };

            // Oggetti curativi: visibili solo se la salute non è piena o l'integrità bassa
            $salute    = (int)$pg['salute'];
            $integrita = (int)$pg['integrita'];
            $curativi  = (($salute < 100 && $salute > 49) || $integrita < 10)
                         ? $fetch_oggetti('curativo') : [];

            // Potenziamenti: visibili solo se non ce n'è già uno attivo
            $pot_attivo   = (int)gdrcd_query("SELECT COUNT(*) AS n FROM clgpersonaggiooggetto
                                WHERE nome = '$login_f' AND used = 1 AND isTemp = 1")['n'];
            $potenziamenti = ($pot_attivo === 0) ? $fetch_oggetti('statistica') : [];

            $magici = $fetch_oggetti('magico');

            // Oggetti standard: cariche > 0 oppure infinito (-1)
            $res_std = gdrcd_query("SELECT c.id_oggetto, o.nome, c.cariche
                FROM clgpersonaggiooggetto c JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                WHERE c.nome = '$login_f' AND o.categoria = 'standard'
                AND c.posizione > 0 AND (c.cariche > 0 OR c.cariche = -1)
                ORDER BY o.nome", 'result');
            $standard = [];
            while ($row = gdrcd_query($res_std, 'fetch')) {
                $standard[] = ['id' => (int)$row['id_oggetto'],
                               'nome' => gdrcd_filter('out', $row['nome']),
                               'cariche' => (int)$row['cariche']];
            }
            gdrcd_query($res_std, 'free');

            // Armi
            $res_armi = gdrcd_query("SELECT o.id_oggetto, o.nome, c.cariche
                FROM clgpersonaggiooggetto c LEFT JOIN oggetto o ON o.id_oggetto = c.id_oggetto
                WHERE c.nome = '$login_f' AND o.categoria = 'arma'
                AND (c.cariche > 0 OR c.cariche = -1) ORDER BY o.nome", 'result');
            $armi = [];
            while ($row = gdrcd_query($res_armi, 'fetch')) {
                $armi[] = ['id' => (int)$row['id_oggetto'],
                           'nome' => gdrcd_filter('out', $row['nome']),
                           'cariche' => (int)$row['cariche']];
            }
            gdrcd_query($res_armi, 'free');

            // Abilità raggruppate per categoria (stesso raggruppamento di frame_chat.inc.php)
            $gruppi = [
                'Default e Difensiva' => [], 'Generiche'    => [],
                'Attacchi'            => [], 'Mentali'      => [],
                'Poteri speciali'     => [], 'Skill Temporanee' => [],
                'Talenti'             => [],
            ];
            $res_ab = gdrcd_query("SELECT a.id_abilita, a.nome, a.tipo, pa.grado, pa.usi
                FROM clgpersonaggioabilita pa LEFT JOIN abilita a ON a.id_abilita = pa.id_abilita
                WHERE pa.nome = '$login_f' ORDER BY a.tipo DESC, a.id_abilita DESC", 'result');
            while ($row = gdrcd_query($res_ab, 'fetch')) {
                $item = ['id'    => (int)$row['id_abilita'],
                         'nome'  => $row['nome'],
                         'grado' => (int)$row['grado'],
                         'usi'   => $row['usi'],
                         'tipo'  => $row['tipo']];
                $t = $row['tipo'];
                if (in_array($t, ['Default', 'Difensiva']))                               $gruppi['Default e Difensiva'][] = $item;
                elseif (in_array($t, ['Generica base', 'Generica avanzata']))             $gruppi['Generiche'][] = $item;
                elseif (in_array($t, ['Attacco base', 'Attacco medio', 'Attacco avanzato'])) $gruppi['Attacchi'][] = $item;
                elseif (in_array($t, ['Mentale base', 'Mentale media', 'Mentale avanzata', 'Mentale di attacco'])) $gruppi['Mentali'][] = $item;
                elseif ($t === 'Potere speciale')  $gruppi['Poteri speciali'][] = $item;
                elseif ($t === 'Skill Temporanea') $gruppi['Skill Temporanee'][] = $item;
                elseif ($t === 'Talento')          $gruppi['Talenti'][] = $item;
            }
            gdrcd_query($res_ab, 'free');
            // Rimuovi i gruppi vuoti per non gonfiare la risposta
            $abilita = array_values(array_filter($gruppi, fn($g) => !empty($g)));
            // Ricostruisce come oggetto chiave→array per il JS
            $abilita_obj = [];
            foreach ($gruppi as $cat => $items) {
                if (!empty($items)) $abilita_obj[$cat] = $items;
            }

            // Creatura: pg con ruolo a livello > 0
            $creatura_row = gdrcd_query("SELECT ruolo.livello FROM ruolo
                JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo
                WHERE clgpersonaggioruolo.personaggio = '$login_f' LIMIT 1");
            $has_creatura = !empty($creatura_row) && (int)$creatura_row['livello'] > 0;

            // Role attiva: determina se mostrare il pannello GDR ai non-staff.
            // Staff lo vede sempre; altri solo quando c'è una role attiva nella stanza.
            $has_active_role = (bool)locationActiveRole($luogo);

            echo json_encode([
                'success'      => true,
                'luogo'        => $luogo,
                'login'        => $login,
                'stanza'       => [
                    'nome'             => gdrcd_filter('out', $info['nome']          ?? ''),
                    'descrizione'      => gdrcd_filter('out', $info['descrizione']   ?? ''),
                    'stanza_apparente' => (int)($info['stanza_apparente'] ?? 0),
                    'privata'          => (int)$info['privata'],
                    'allowance'        => $allowance,
                ],
                'preferenze'   => [
                    'font'           => $preferenze['font']           ?? null,
                    'colore_testo'   => $preferenze['colore_testo']   ?? null,
                    'grandezza'      => $preferenze['grandezza']      ?? null,
                    'interlinea'     => $preferenze['interlinea']     ?? null,
                    'colore_dialogo' => $preferenze['colore_dialogo'] ?? null,
                ],
                'maxlength'    => $maxlength,
                'pulsanti'     => [
                    'show_backchat'   => $show_backchat,
                    'backchat_on'     => $backchat_on,
                    'show_cura'       => $show_cura,
                    'show_pulisci'    => $show_pulisci,
                    'show_scacchiera' => $show_scacchiera,
                    'is_staff'        => $is_staff,
                    'can_master_msg'  => $can_master_msg,
                    'is_ospedale'     => $is_ospedale,
                ],
                'oggetti'      => [
                    'curativi'      => $curativi,
                    'potenziamenti' => $potenziamenti,
                    'magici'        => $magici,
                    'standard'      => $standard,
                    'armi'          => $armi,
                ],
                'abilita'      => $abilita_obj,
                'creatura'        => $has_creatura,
                'has_active_role' => $has_active_role,
                'submit_label'    => gdrcd_filter('out', $MESSAGE['interface']['forms']['submit'] ?? 'Invia'),
            ]);
            break;

        case 'curaAltroPg':
            $login    = $_SESSION['login'];
            $login_f  = gdrcd_filter('in', $login);
            $luogo    = (int)$_SESSION['luogo'];

            if ($luogo !== 25) {
                echo json_encode(['success' => false, 'message' => 'Puoi usare questo comando solo in ospedale.']);
                exit;
            }

            // Verifica che l'operatore appartenga al mestiere Ospedale
            $check_op = gdrcd_query(
                "SELECT 1 FROM clgpersonaggiomestiere
                 JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo
                 WHERE ruolo_mestiere.mestiere = 10
                   AND clgpersonaggiomestiere.personaggio = '$login_f'
                   AND clgpersonaggiomestiere.conferma_mestiere = 1
                 LIMIT 1"
            );
            if (empty($check_op)) {
                echo json_encode(['success' => false, 'message' => 'Non sei autorizzato ad eseguire questa operazione.']);
                exit;
            }

            $input_cura2   = json_decode(file_get_contents('php://input'), true);
            $target_raw    = trim($input_cura2['target'] ?? '');

            if ($target_raw === '') {
                echo json_encode(['success' => false, 'message' => 'Specifica il nome del personaggio da curare.']);
                exit;
            }

            $result_target = adjustPgStats($target_raw, 25);
            if (!$result_target) {
                echo json_encode(['success' => false, 'message' => 'Personaggio non trovato.']);
                exit;
            }
            if ($result_target['delta_salute'] === 0) {
                echo json_encode(['success' => false, 'message' => 'Il personaggio ha già la salute al massimo (100 PS).']);
                exit;
            }

            $ps_cura   = $result_target['delta_salute'];
            $nuova_sal = $result_target['salute'];
            chatInsertMessage($luogo, 'System', $login, "cura $target_raw di $ps_cura PS. Salute attuale: $nuova_sal/100.", 'N');

            echo json_encode(['success' => true, 'punti_effettivi' => $ps_cura, 'nuova_salute' => $nuova_sal]);
            break;

        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>