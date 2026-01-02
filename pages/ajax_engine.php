<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_GET['op']) && $_GET['op'] != '') {
    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');
    
    // IMPORTANTE: Solo per le richieste AJAX
    header('Content-Type: application/json');   
    
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    /*********************  Recupero i dati dell'utente che voglio modificare   */
    switch ($_GET['op']) {
        // CHAT
        case 'tiraSkillChat':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $id_role = locationActiveRole($luogo);
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
            $salute = $pg["salute"];
            $skill = isset($data['chat_skill']) ? (int)$data['chat_skill'] : 0;
            $livello = isset($data['livello_skill']) ? (int)$data['livello_skill'] : 0;
            $level_check = gdrcd_query("SELECT grado FROM clgpersonaggioabilita WHERE id_abilita = $skill AND nome = '$login'");
            $skill_info = gdrcd_query("SELECT nome, tipo, car FROM abilita WHERE id_abilita = $skill");
            $car = $PARAMETERS['names']['stats']['car'.$skill_info['car']]; // Caratteristica della skill lanciata
            $bersaglio = isset($data['target']) ? $data['target'] : [];
            $id = gdrcd_filter('out', $skill);
            $can_send = (int)gdrcd_query("SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login'")['can_send'];
            $tiro = '';

            /**************************** CONTROLLI   ************************************************/
            // Se il pg ha già lanciato un attacco e uno scudo nel turno precedente, non può attaccare
            if ($skill_info['tipo'] != 'Difensiva' && $can_send === 0) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, hai già usato la tua azione per lanciare uno scudo!'));
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
            if($skill_info['car'] > 0 && checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'"])) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due attacchi nello stesso turno'));
                exit;
            }

            // Se sto lanciando uno scudo, devo verificare se non l'ho già lanciato in questo turno
            if($skill_info['tipo'] === 'Difensiva' && checkMultipleLounch($id_role, $login, ["'D20'"])) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due scudi nello stesso turno'));
                exit;
            }
            /**************************** FINE  CONTROLLI   ************************************************/
            
            // Determina il messaggio in base al tipo di skill
            $messaggio = creaMessaggioSkill($skill_info['tipo'], $skill_info['nome'], $livello, $login);

            // Se la skill è di tipo "Talento", esegui il calcolo per determinare il livello
            if ($skill_info['tipo'] == 'Talento') {
                $messaggio_talento = calcolaLivelloTalento($pg["car6"]);

                // Se il talento è "Pronto Soccorso", gestisci la cura
                if ($skill_info['nome'] == 'Pronto soccorso') $messaggio = gestisciProntoSoccorso($messaggio_talento['livello'], $login, $bersaglio[0], $luogo, $pg["salute"]);
                else $messaggio .= " " . $messaggio_talento['livello'];
            }

            $messaggio .= " verso <u>".implode(',', $bersaglio)."</u>"; // Aggiungi il bersaglio al messaggio se presente

            // Preparo il messaggio in chat con il popup della skill
            $leggi = "<font color=\"#b4b6bf\">(<a href=\"#\" onclick=\"changeFrame('skill_desc.proc.php?id=$id');document.getElementById('id01').style.display='block'\">Leggi</a>)</font>";
            $messaggio .= " " . $leggi;
            $messaggio = gdrcd_filter('in', $messaggio); // Pulisco il messaggio
            $sussurro = isset($messaggio_talento['testo']) ? gdrcd_filter('in', $messaggio_talento['testo']) : null;
            
            // Registro i dati della skill per elaborare il combattimento a fine turno, vale soltanto se la skill utilizza una caratteristica
            if($skill_info['car'] > 0) {
                $diceLounch = lanciaStat($id_role, $login, implode(',', $bersaglio), true, $car, $car, $pg['car'.$skill_info['car']], $salute, 0, 0);
                fight($id_role, $login, implode(',', $bersaglio), $skill, $livello, $car, $diceLounch['risultato'], 'usa una skill');
                $tiro = " con un tiro totale di $car di " . $diceLounch['risultato'];
                $sussurro = $diceLounch['sussurro'];
            }
            // Registro lo scudo per elaborare la difesa a fine turno
            if($skill_info['tipo'] === 'Difensiva') fight($id_role, $login, implode(',', $bersaglio), $skill, $livello, 'D20', mt_rand(1, 20), 'usa uno scudo');

            // Messaggio in chat
            chatInsertMessage($luogo, $login, null, $messaggio.$tiro, 'C', $sussurro, '', null);

            // Controllo se il long_turn è uguale al pg che lancia l'attacco', perché significa che è quello di fine turno
            if(gdrcd_query("SELECT long_turn FROM role_sessions WHERE id_role = $id_role LIMIT 1")['long_turn'] == $login) {
                $last_id = (int)gdrcd_query("SELECT MAX(id) AS last_id FROM chat")['last_id'];
                // Chiedo al bersaglio se vuole difendersi con lo scudo
                // Faccio la domanda per capire se devo chiudere il turno con l'azione oppure devo aspettare che lancino un attacco.
                // Il last id + 1 dovrebbe corrispondere all'id del sussurro che sto per inserire, così lo rimuovo dopo il click
                $scudo_msg = '<button onclick="lanciaScudo('.$id_role.', 1, '.($last_id+1).');">SI</button> Vuoi difenderti dall\'attacco con uno scudo? <button onclick="lanciaScudo('.$id_role.', 0, '.($last_id+1).');">NO</button>';

                foreach($bersaglio as $target) chatInsertMessage($luogo, 'System', $target, $scudo_msg, 'S');
            }
            
            assegnaPuntoShin($luogo, $login); // Assegna il punto Shin se necessario
            gestionePoliziaAutomatica($luogo); // Gestione della polizia automatica
            gestisciSkillTemporanea($skill, $login); // Gestisci le skill temporanee
            
            gdrcd_query("UPDATE personaggio SET salute = salute-1 WHERE nome = '$login'"); // Aggiorna la salute del personaggio

            // Risposta JSON per AJAX
            echo json_encode(array(
                'success' => true,
                'message' => 'Skill tirata con successo',
                'testo chat' => $messaggio,
                'caratteristica' => $skill_info['car']
            ));
            exit;
            break;
        case 'tiraDadoChat':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

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
            // $id_role = isset($data['id_role']) ? (int)$data['id_role'] : 0;
            $id_role = locationActiveRole($luogo);

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
            if(checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'"])) {
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
                'testo chat' => $diceLounch['messaggio'],
                'sussurro' => $diceLounch['sussurro'],
                'bonus e malus' => "$dice_bonus e $dice_malus",
                'bersaglio' => $bersaglio
            ));
            exit;
            break;
        case 'usaArmaChat':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $id_arma = (int)$data['arma_weapon'];
            $bersaglio = isset($data['target'][0]) ? $data['target'][0] : [];
            $target_area = $data['arma_body'];
            // $id_role = isset($data['id_role']) ? (int)$data['id_role'] : 0;
            $id_role = locationActiveRole($luogo);

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
            
            // Se sto lanciando un attacco, devo verificare se non l'ho già lanciato in questo turno
            if(checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'"])) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due attacchi nello stesso turno'));
                exit;
            }
            /**************************** FINE  CONTROLLI   ************************************************/

            // Ottieni informazioni sul pg
            $parametri = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
            $salute = $parametri["salute"];

            // Ottieni informazioni sull'arma
            $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id_arma");

            $nome_arma = $oggetto['nome'];
            $bonus_arma = $oggetto['attacco'];
            $tipo_arma = $oggetto['tipo_arma'];
            $obj_cat = $oggetto['categoria'];

            // Verifica abilità possedute
            $check_abilita = [
                'bianca' => gdrcd_query("SELECT COUNT(*) AS n_bianca FROM clgpersonaggioabilita WHERE id_abilita = '41' AND nome ='$login'")['n_bianca'],
                'lancio' => gdrcd_query("SELECT COUNT(*) AS n_lancio FROM clgpersonaggioabilita WHERE id_abilita = '42' AND nome ='$login'")['n_lancio'],
                'fuoco' => gdrcd_query("SELECT COUNT(*) AS n_fuoco FROM clgpersonaggioabilita WHERE id_abilita = '40' AND nome ='$login'")['n_fuoco'],
                'fisico' => gdrcd_query("SELECT COUNT(*) AS n_fisico FROM clgpersonaggioabilita WHERE id_abilita = '44' AND nome ='$login'")['n_fisico']
            ];

            // Calcolo del tiro di destrezza
            $destrezza = $parametri["car2"] / 10;
            $des_rand = mt_rand(1, 20);

            /*********  BONUS TALENTO   ***********/
            // Calcolo del livello talento (tempra)
            $tempra = $parametri["car6"];
            $tem_rand = mt_rand(1, 20);
            $tem_bonus = (($tempra / 10) - 1);
            $numtot_tem = $tem_rand + $tem_bonus;

            $bonus_talento = 1;

            if ($numtot_tem < 17) {
                $bonus_talento = 0.5;
                $livello = "di livello 1";
            } elseif ($numtot_tem > 25) {
                $bonus_talento = 1.5;
                $livello = "di livello 3";
            } else $livello = "di livello 2";
            /*********  FINE    BONUS TALENTO   ***********/

            // Determinazione del tipo di attacco
            $tipo = ($id_arma == '999999999') ? 'attacca fisicamente' : "attacca con $nome_arma";
            $messaggio = "$login $tipo";

            $messaggio .= " <u>$bersaglio</u>";
            if ($target_area != "") $messaggio .= " mirando al $target_area";

            $messaggio .= " con un tiro totale di destrezza di";

            // Calcolo del risultato finale con bonus/malus
            if ($id_arma == '999999999' && $check_abilita['fisico'] > 0) {
                $numtot_finale = $des_rand + $destrezza + $bonus_talento;
                $sussurro = "$des_rand/20 + $destrezza + $bonus_talento (talento corpo a corpo) = $numtot_finale";
            } elseif ($tipo_arma == 1 && $check_abilita['bianca'] > 0) {
                $numtot_finale = $num + $numdes + ($bonus_arma > 0 ? $bonus_arma : 0) + $bonus_talento;
                $numtot_finale = $des_rand + $destrezza + ($bonus_arma > 0 ? $bonus_arma : 0) + $bonus_talento;
                $sussurro = "$des_rand/20 + $destrezza";
                
                if ($bonus_arma > 0) $sussurro .= " + $bonus_arma (bonus arma)";
                $sussurro .= " + $bonus_talento (talento arma bianca) = $numtot_finale";
            } elseif ($tipo_arma == 2 && $check_abilita['lancio'] > 0) {
                $numtot_finale = $des_rand + $destrezza + ($bonus_arma > 0 ? $bonus_arma : 0) + $bonus_talento;
                $sussurro = "$des_rand/20 + $destrezza";

                if ($bonus_arma > 0) $sussurro .= " + $bonus_arma (bonus arma)";
                $sussurro .= " + $bonus_talento (talento arma da lancio) = $numtot_finale";
            } elseif ($tipo_arma == 3 && $check_abilita['fuoco'] > 0) {
                $numtot_finale = $des_rand + $destrezza + ($bonus_arma > 0 ? $bonus_arma : 0) + $bonus_talento;
                $sussurro = "$des_rand/20 + $destrezza";
                
                if ($bonus_arma > 0) $sussurro .= " + $bonus_arma (bonus arma)";
                $sussurro .= " + $bonus_talento (talento arma da fuoco) = $numtot_finale";
            } else {
                $numtot_finale = $des_rand + $destrezza + ($bonus_arma > 0 ? $bonus_arma : 0);
                $sussurro = "$des_rand/20 + $destrezza";

                if ($bonus_arma > 0) $sussurro .= " + $bonus_arma (bonus arma)";
                $sussurro .= " = $numtot_finale";
            }

            /*******    MALUS SALUTE    **********/
            if ($salute <= 50) {
                if ($salute > 40) $malus_salute = 1;
                elseif ($salute > 30) $malus_salute = 3;
                elseif ($salute > 20) $malus_salute = 5;
                elseif ($salute > 0) $malus_salute = 10;
                
                $numtot_finale -= $malus_salute;
            }

            if ($malus_salute > 0) $sussurro .= " - $malus_salute di malus per la salute = $numtot_finale";
            /*******    FINE    MALUS SALUTE    **********/

            $messaggio .= " $numtot_finale";
            
            // Scala una carica se NON è attacco fisico
            if ($id_arma != '999999999') {
                // Verifica che sia davvero un'arma
                if ($obj_cat === 'arma') gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = cariche - 1 WHERE nome = '$login' AND id_oggetto = '$id_arma' AND cariche > 0");
            }

            // Registro l'attacco
            fight($id_role, $login, $bersaglio, 0, 1, 'destrezza', $numtot_finale, 'attacca con arma'); // Funzione di gestione combattimenti
            
            // Inserisci i messaggi in chat
            chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
            
            // Gestione della polizia automatica
            gestionePoliziaAutomatica($luogo);
            
            // Controllo se il long_turn è uguale al pg che lancia l'attacco', perché significa che è quello di fine turno
            if(gdrcd_query("SELECT long_turn FROM role_sessions WHERE id_role = $id_role LIMIT 1")['long_turn'] == $login) {
                $last_id = (int)gdrcd_query("SELECT MAX(id) AS last_id FROM chat")['last_id'];
                // Chiedo al bersaglio se vuole difendersi con lo scudo
                // Faccio la domanda per capire se devo chiudere il turno con l'azione oppure devo aspettare che lancino un attacco.
                // Il last id + 1 dovrebbe corrispondere all'id del sussurro che sto per inserire, così lo rimuovo dopo il click
                $scudo_msg = '<button onclick="lanciaScudo('.$id_role.', 1, '.($last_id+1).');">SI</button> Vuoi difenderti dall\'attacco con uno scudo? <button onclick="lanciaScudo('.$id_role.', 1, '.($last_id+1).');">NO</button>';
                chatInsertMessage($luogo, 'System', $bersaglio, $scudo_msg, 'S');
            }

            echo json_encode(array('success' => true, 'message' => 'Attacco eseguito con successo.'));

            break;
        case 'tiraDadoGenericoChat':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

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

                if ($back_chat) chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
                else chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
            }

            // Risposta JSON per AJAX
            echo json_encode(array('success' => true, 'message' => 'Dado generico tirato con successo'));
            exit;
            break;
        case 'get_chat_messages':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

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
                            $add_chat .= '<span class="chat_sussurro">'.gdrcd_filter('out',$row['testo']).'</span>';
                        } elseif ($_SESSION['login']==$row['mittente']) {
                            if ($row['tipo']=='S') {
                                $add_chat .= '<span class="chat_sussurro">'.gdrcd_format_time($row['ora']).'</span>&nbsp;';
                                $add_chat .= '<span class="chat_sussurro">'.$MESSAGE['chat']['whisper']['to'].' '.gdrcd_filter('out',$row['destinatario']).': </span>';
                                $add_chat .= '<span class="chat_sussurro">'.gdrcd_filter('out',$row['testo']).'</span>';
                            }
                        } elseif ($_SESSION['admin']==1) {
                            $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                            $add_chat .= '<span class="chat_name">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['from_to'].' '.gdrcd_filter('out',$row['destinatario']).' </span>';
                            $add_chat .= '<span class="chat_name">'.gdrcd_filter('out',$row['testo']).'</span>';
                        }
                        break;

                    case 'N':
                        $row['testo'] = str_replace(['[',']','«','»'], ['[<font color=#7f99bc>', '</font>]', '«<font color=#7f99bc>', '</font>»'], $row['testo']);
                        $add_chat .= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                        $add_chat .= '<font color="white" style="font-size: 12px;"><b>'.$row['destinatario'].'</b></font> ';
                        $add_chat .= '<span class="chat_msg">'.gdrcd_chatcolor(gdrcd_filter('out',$row['testo'])).'</span>';
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
                        $add_chat .= '<span class="chat_skill">'.gdrcd_filter('out',$row['testo']).'</span>';
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
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            // Filtra e prepara i dati
            $login = $_SESSION['login'];
            $chat_message = isset($data['message']) ? gdrcd_filter('in', gdrcd_angs($data['message'])) : '';
            $action_tag = isset($data['action_tag']) ? gdrcd_filter('in', gdrcd_angs($data['action_tag'])) : '';
            $tag_n_beyond = isset($data['tag']) ? gdrcd_filter('in', $data['tag']) : '';
            $type = isset($data['type']) ? gdrcd_filter('in', $data['type']) : '';
            $first_char = substr($chat_message, 0, 1);
            $second_char = substr($chat_message, 0, 4);
            $actual_healt = gdrcd_query("SELECT salute FROM personaggio WHERE nome = '$login'"); // Recupera la salute attuale del pg
            $id_role = locationActiveRole($_SESSION['luogo']); // Recupera l'eventuale role attiva nella chat
            $pgIsInRole = pgIsInRole($login, $_SESSION['luogo']); // Verifica se il pg è nella role della chat
            $m_type = determineMessageType($type, $first_char, $second_char, $chat_message, $login, $actual_healt, $_SESSION); // Determina il tipo di messaggio
            $typePermitted = array('S', 'M', 'G', 'X', 'Q'); // Tipi di messaggi permessi senza role attiva

            /****************************  CONTROLLI   ************************************************/
            /* Se:
            - non c'è una role attiva in chat
            - il pg non è nella role della chat
            - il pg non è admin/master/mod
            - il tipo di messaggio non è tra quelli permessi */
            if ((!$id_role || !$pgIsInRole) && !isAdminMasterMod($_SESSION) && !in_array($m_type, $typePermitted)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, non puoi inviare l\'azione!'));
                exit;
            }
            
            // Se ho già inviato un'azione in questo turno
            $justSent = gdrcd_query("SELECT * FROM role_session_players WHERE id_role = $id_role AND pg_name = '$login' AND `sent` = 1", 'result');

            if ($justSent && gdrcd_query($justSent, 'num_rows') > 1) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi inviare due azioni nello stesso turno'));
                exit;
            }
            /**************************** FINE  CONTROLLI   ************************************************/

            // Determina se è un sussurro, un messaggio normale o comando stanza privata
            if ($m_type === 'S') handleWhisperMessage($type, $chat_message, $tag_n_beyond, $m_type, $_SESSION);
            elseif ($type < "5" || $type > "7") handleNormalMessage($chat_message, $action_tag, $login, $m_type, $first_char, $second_char, $actual_healt, $_SESSION, $PARAMETERS, $id_role, $pgIsInRole);
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
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            if (isAdminMasterMod($_SESSION)) {
                // Recupera il nome della stanza basato sull'id della mappa
                $nome_stanza = gdrcd_query("SELECT nome FROM mappa WHERE id = ".$_SESSION['luogo']." LIMIT 1")['nome'];
                $msg = "Stanza $nome_stanza cancellata";
        
                // Cancellazione dei messaggi nella chat della stanza entro le ultime 3 ore (180 minuti)
                $pulisci = gdrcd_query("DELETE FROM chat WHERE stanza = ".$_SESSION['luogo']." AND DATE_ADD(ora, INTERVAL 180 MINUTE) >= NOW()");
        
                // Invia un sussurro all'utente per confermare l'avvenuta cancellazione
                chatInsertMessage($_SESSION['luogo'], 'System', $_SESSION['login'], $msg, 'S');
            }

            if($pulisci && $sussurra) echo json_encode(array('success' => true, 'message' => $msg));
            else echo json_encode(array('success' => false, 'message' => "Errore nella cancellazione della chat della stanza."));

            break;
        case 'curaPg':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];

            // Se il pg non è nella role della chat
            if (!pgIsInRole($login, $luogo) &&  !isAdminMasterMod($_SESSION)) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione, nessuna role attiva per il tuo pg!'));
                exit;
            }

            // Recupera l'ultimo record di cura per il personaggio
            $last_healt = gdrcd_query("SELECT * FROM cure WHERE nome = '$login'");
            $last_date_cura = isset($last_healt['data_cura']) ? $last_healt['data_cura'] : null;

            // Imposta le date e condizioni per il controllo della cura
            $last_cura = 'yesterday 7:00:01';
            $current_cura = new DateTime('now');
            $new_day_cura = new DateTime('today 7:00');
            $date_power_cura = new DateTime($last_date_cura);

            if($current_cura < $new_day_cura) $new_day_cura->modify('-1day'); // Setto il range

            $ps = 10; // Punti salute da curare
            $actual_healt = gdrcd_query("SELECT salute FROM personaggio WHERE nome = '$login'");

            // Limita il recupero della salute a massimo 50 punti
            if (($ps + $actual_healt['salute']) > 50) $ps = 50 - $actual_healt['salute'];

            // Verifica se il personaggio può essere curato
            $paziente = gdrcd_query("SELECT * FROM cure WHERE nome = '$login'", 'result');

            if ($actual_healt['salute'] < 50 && (gdrcd_query($paziente, 'num_rows') < 1)) { // Se non esiste un record di cura, crea un nuovo record e cura il personaggio
                gdrcd_query("UPDATE personaggio SET salute = salute + '".$ps."' WHERE nome = '$login'");
                gdrcd_query("INSERT INTO cure (nome, data_cura) VALUES ('$login', NOW())");

                chatInsertMessage($luogo, 'Master', null, "Il personaggio $login cura 10 punti salute!", 'N');
            } elseif ($actual_healt['salute'] < 50 && $date_power_cura < $new_day_cura) { // Se esiste un record di cura e il giorno è nuovo, aggiorna la cura
                gdrcd_query("UPDATE personaggio SET salute = salute + '".$ps."' WHERE nome = '$login'");
                gdrcd_query("UPDATE cure SET data_cura = NOW() WHERE nome = '$login'");
                
                chatInsertMessage($luogo, 'Master', null, "Il personaggio $login cura 10 punti salute!", 'N');
            // Se la salute è superiore a 50, non permettere la cura
            } elseif ($actual_healt['salute'] > 50) chatInsertMessage($luogo, 'System', $login, "Non puoi usare questo comando se hai oltre 50 punti salute. Usa gli oggetti presenti nel mercato!", 'S');
            else chatInsertMessage($luogo, 'System', $login, "Ti sei già curato per oggi. Torna domani!", 'S'); // Altrimenti, informa il giocatore che si è già curato oggi
             
            echo json_encode(array('success' => true, 'message' => 'Operazione di cura eseguita con successo.'));

            break;
        case 'setBackChat':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
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
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
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
            if (imposta_limite_caratteri_utente($login, $luogo, $caratteri)) {
                // Aggiorna il limite globale
                aggiorna_limite_globale($luogo, $caratteri, $login);
            }

            echo json_encode(array('success' => true, 'message' => 'Limite caratteri impostato correttamente.'));

            break;
        case 'usaOggettoChat':
            session_start();

            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
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
            session_start();

            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
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
        case 'newMasterPngChat':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
            $pngName = gdrcd_filter('post', $data['pngName']);
            $pngMessage = gdrcd_filter('in', gdrcd_filter('post', $data['pngMessage']));
            $pngBonus = gdrcd_filter('post', $data['pngBonus']);
            $pngCar = gdrcd_filter('post', $data['pngCar']);

            // Se il messaggio è presente, inserisci un messaggio di tipo N
            if (!empty($pngMessage)) chatInsertMessage($luogo, $login, $pngName, $pngMessage, 'N', null);
            if (!empty($pngBonus) && !empty($pngCar)) {
                // Se il messaggio è vuoto, ma "pngBonus" e "usa parametro" sono compilati
                $num = mt_rand(1, 20);
                $numtot = $num + $pngBonus;

                // Gestione dei messaggi in base al parametro usato
                switch ($pngCar) {
                    case 'Usa destrezza': $pngMessage = "$pngName esegue un tiro totale di destrezza di $numtot ($num/20 + $pngBonus)"; break;
                    case 'Usa forza': $pngMessage = "$pngName esegue un tiro totale di forza di $numtot ($num/20 + $pngBonus)"; break;
                    case 'Usa mente': $pngMessage = "$pngName esegue un tiro totale di mente di $numtot ($num/20 + $pngBonus)"; break;
                    case 'Usa tempra': $pngMessage = "$pngName esegue un tiro totale di tempra di $numtot ($num/20 + $pngBonus)"; break;
                    case 'Usa dado':
                        $num_dado = mt_rand(1, $pngBonus);
                        $pngMessage = "$pngName lancia $num_dado/$pngBonus";
                        break;
                    default: $pngMessage = "$pngName esegue un'azione non specificata."; break;
                }
                chatInsertMessage($luogo, $pngName, null, $pngMessage, 'C', null);
            }

            echo json_encode(array('success' => true, 'message' => 'Azione PNG inviata con successo.'));

            break;
        // ROLE SESSION
        case 'addPgToRole':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            $login = $_SESSION['login'];
            $luogo = $_SESSION['luogo'];
        
            try {
                // Controlla se l'utente ha già una giocata in corso
                if(pgIsInRole($login)) {
                    echo json_encode(['success' => false,  'message' => "L'utente $userName ha già una giocata in corso."]);
                    exit;
                }

                $id_role = locationActiveRole($luogo); // Recupera l'eventuale role attiva nella chat

                // Se nella chat NON ci sono giocate attive, avvio una nuova role
                if(!$id_role) {
                    gdrcd_query("INSERT INTO role_sessions (`location`, `start`) VALUES ($luogo, NOW())");
                    $id_role = gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
                }
                
                // In ogni caso aggiungo il pg alla role
                gdrcd_query("INSERT INTO role_session_players (id_role, pg_name) VALUES ($id_role, '$login')");
                chatInsertMessage($luogo, 'System', $login, " si è ha aggiunto alla role", 'N');
                
                echo json_encode(['success' => true, 'message' => 'Giocatore aggiunto con successo!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Errore nel processamento degli utenti: ' . $e->getMessage(), 'error_details' => [ 'file' => $e->getFile(), 'line' => $e->getLine() ]]);
            }
            break;
        case 'quitRole':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            $user = isset($data['user']) ? gdrcd_filter('in', $data['user']) : $_SESSION['login'];
            $pgIsInRole = pgIsInRole($user, $_SESSION['luogo']); // Verifica se il pg è nella role della chat
            $set_end = "UPDATE role_session_players SET `end` = NOW() WHERE pg_name = '$user' AND id_role = ".locationActiveRole($_SESSION['luogo'])." AND `end` IS NULL LIMIT 1";

            // Se il pg è nella role, procedo con l'uscita
            if ($pgIsInRole && gdrcd_query($set_end)) {
                chatInsertMessage($_SESSION['luogo'], 'System', $user, " ha abbandonato la role", 'N');
                
                if(!pgIsInRole('', $_SESSION['luogo'], true)) endRoleSession($_SESSION['luogo']); // Se sono usciti tutti i pg, chiudo la role
                    
                echo json_encode(array('success' => true, 'message' => "Il personaggio $user è uscito dalla role."));
            } else echo json_encode(array('success' => false, 'message' => "Errore nell'uscita dalla role.", 'query' => $set_end));

            break;
        case 'getPgRolePlaying': // Serve per mostrare i personaggi coinvolti nella giocata corrente della chat
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
        
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
                        'inRole' => $row['end'] === null ? true : false
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
        case 'getRolePgs': // Prende tutti i personaggi della role
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            $id_role = isset($data['id_role']) ? gdrcd_filter('in', $data['id_role']) : '';
            $users = getRolePgs($id_role);
            
            if(!empty($users)) echo json_encode(array('success' => true, 'message' => "Utenti della role!", 'users' => $users));
            else echo json_encode(array('success' => false, 'message' => "Errore! Nessun utente nella role. Sta cosa è impossibile!"));

            break;
        case 'closeTurn':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            $id_role = isset($data['id_role']) ? (int)gdrcd_filter('in', $data['id_role']) : '';
            $suss_id = isset($data['suss_id']) ? (int)gdrcd_filter('in', $data['suss_id']) : 0;

            if($suss_id > 0) { // Elimino il sussurro
                $_SESSION['last_message'] = ($suss_id-1); // Devo forzare l'aggiornamento della chat
                
                gdrcd_query("DELETE FROM chat WHERE id = $suss_id LIMIT 1"); // Elimino il sussurro
            }

            closeTurn($id_role, $_SESSION['login'], $_SESSION['luogo']);

            echo json_encode(array('success' => true, 'message' => "Turno chiuso"));

            // Allungo il turno se l'ultimo ad azionare risponde che deve lanciare un attacco
            // if($query) echo json_encode(array('success' => true, 'message' => "Azione modificata con successo!"));
            // else echo json_encode(array('success' => false, 'message' => "Errore nella modifica dell'azione."));

            break;
        case 'lanciaScudo':
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');

            $id_role = isset($data['id_role']) ? (int)gdrcd_filter('in', $data['id_role']) : '';
            $yes = isset($data['yes']) ? (int)gdrcd_filter('in', $data['yes']) : '';
            $suss_id = isset($data['suss_id']) ? (int)gdrcd_filter('in', $data['suss_id']) : 0;
            $msg = '';

            if($suss_id > 0) {
                $_SESSION['last_message'] = ($suss_id-1); // Devo forzare l'aggiornamento della chat
                $msg = $yes == 1 ? 'Scelta registrata, lancio lo scudo' : 'Scelta registrata, lancio il dado';

                gdrcd_query("UPDATE chat SET testo = '$msg' WHERE id = $suss_id LIMIT 1"); // Modifico il sussurro
            }

            $query = $yes === 1 ? lanciaScudo($id_role, $_SESSION['luogo'], $_SESSION['login']) : closeTurn($id_role, $_SESSION['luogo']);

            // Allungo il turno se l'ultimo ad azionare risponde che deve lanciare un attacco
            if($query) echo json_encode(array('success' => true, 'message' => $suss_id));
            else echo json_encode(array('success' => false, 'message' => "Errore nel lancio dello scudo.", 'query' => $query));

            break;
        case 'getPgAllRoles': // Prende tutte le role del pg
            session_start();
            require_once(__DIR__ . '/../includes/chat_functions.inc.php');
            
            $query = "SELECT role_sessions.*, mappa.nome FROM role_sessions
                        LEFT JOIN mappa ON role_sessions.location = mappa.id
                        INNER JOIN role_session_players ON role_sessions.id_role = role_session_players.id_role
                        WHERE role_session_players.pg_name = '".$_SESSION['login']."'";
            $result = gdrcd_query($query, 'result');
            $roles = [];
            
            while($row = gdrcd_query($result, 'fetch')) {
                $role = [];

                $role['id'] = (int)$row['id_role'];
                $role['luogo'] = $row['nome'];
                $role['data'] = date("l j F Y", strtotime($row['start']));
                $role['oraInizio'] = date("H:i", strtotime($row['start']));
                $role['oraFine'] = $row['end'] !== null ? date("H:i", strtotime($row['end'])) : '';
                $role['totTurni'] = (int)$row['turn'];
                $role['partecipanti'] = getRolePgs($row['id_role']);
                $role['inCorso'] = $row['end'] === null ? true : false;
                $role['icona'] = 'fas fa-globe';

                array_push($roles, $role);
            }
            
            if(!empty($roles)) echo json_encode(array('success' => true, 'message' => "Giocate del pg", 'roles' => $roles));
            else echo json_encode(array('success' => false, 'message' => "Errore! Nessuna giocata trovata per il pg.", 'query' => $query));

            break;
        //  UTENTI
        case 'getPresentiOnline':
            session_start();

            if (!isset($_SESSION['login'])) {
                echo json_encode(['error' => 'Non autorizzato']);
                exit();
            }
            
            // CODICE ESISTENTE PER AGGIORNAMENTO REFRESH
            if(isset($_REQUEST['disponibile']) === true) {
                $query = "UPDATE personaggio SET ultimo_refresh = NOW(), disponibile=".gdrcd_filter('num', $_REQUEST['disponibile'])."  WHERE nome = '".gdrcd_filter('in', $_SESSION['login'])."'";
            } elseif(isset($_REQUEST['invisibile']) && ($_SESSION['admin']==1 || $_SESSION['moderatore']==1 || $_SESSION['master']==1)) {
                $query = "UPDATE personaggio SET ultimo_refresh = NOW(), is_invisible=".gdrcd_filter('num', $_REQUEST['invisibile'])."  WHERE nome = '".gdrcd_filter('in', $_SESSION['login'])."'";
            } else {
                $query = "UPDATE personaggio SET ultimo_refresh = NOW() WHERE nome = '".gdrcd_filter('in', $_SESSION['login'])."'";
            }
            gdrcd_query($query);
            
            // CARICA LISTA PRESENTI (stesso codice della tua pagina)
            $query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.is_invisible, mappa.stanza_apparente, mappa.nome as luogo FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE (personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW()) AND personaggio.ultimo_luogo = ".$_SESSION['luogo']." AND personaggio.ultima_mappa= ".$_SESSION['mappa']." ORDER BY personaggio.is_invisible, personaggio.ultimo_luogo, personaggio.nome";
            $result = gdrcd_query($query, 'result');
        
            $users = [];
            while($record = gdrcd_query($result, 'fetch')) {
                if(($record['is_invisible'] == 0) || ($record['nome'] == $_SESSION['login'])) {
                    
                    // DETERMINA ICONA RAZZA (stessa logica del tuo codice)
                    $race_icon = 'Png.png';
                    if ($record['id_razza'] >= 1000 && $record['id_razza'] < 2000) {
                        $race_icon = 'Marte.png';
                    } else if ($record['id_razza'] >= 2000 && $record['id_razza'] < 3000) {
                        $race_icon = 'Mercurio.png';
                    } else if ($record['id_razza'] >= 3000 && $record['id_razza'] < 4000) {
                        $race_icon = 'Luna.png';
                    } else if ($record['id_razza'] >= 4000 && $record['id_razza'] < 5000) {
                        $race_icon = 'Giove.png';
                    } else if ($record['id_razza'] >= 5000 && $record['id_razza'] < 6000) {
                        $race_icon = 'Venere.png';
                    } else if ($record['id_razza'] >= 6000 && $record['id_razza'] < 7000) {
                        $race_icon = 'Urano.png';
                    } else if ($record['id_razza'] >= 7000 && $record['id_razza'] < 8000) {
                        $race_icon = 'Nettuno.png';
                    } else if ($record['id_razza'] >= 8000 && $record['id_razza'] < 9000) {
                        $race_icon = 'Plutone.png';
                    } else if ($record['id_razza'] >= 9000 && $record['id_razza'] < 10000) {
                        $race_icon = 'Saturno.png';
                    } else if ($record['id_razza'] >= 10000 && $record['id_razza'] < 11000) {
                        $race_icon = 'Terra.png';
                    } else if ($record['id_razza'] == 11000) {
                        $race_icon = 'Nebbia.png';
                    }
                    
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
            
            $volto 	= $_GET['volto'];
            $query = "SELECT volto from personaggio where volto = '".strtolower($volto)."'";
            $results = gdrcd_query($query, 'result') or die('ok');
            
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
                'level' => getLevelPg($tot_stats),
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
            $pg_level = getLevelPg(getTotStatsPg($pg_name));
            $skillRes = [];
            $pg_shin = 0;
            
            $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$pg_name'");
            $skills = gdrcd_query("SELECT clgpersonaggioabilita.grado, abilita.livello_sblocco, abilita.id_abilita, abilita.nome as nome_abilita,
                                            abilita.descrizione as descrizione_abilita, abilita.descrizione as descrizione_abilita, abilita.tipo
                                    FROM abilita
                                    LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita AND clgpersonaggioabilita.nome = '$pg_name'
                                    WHERE abilita.id_gilda = (SELECT id_gilda FROM personaggio WHERE nome = '$pg_name')
                                    AND abilita.tipo IN (
                                        'Default', 'Difensiva', 'Generica base', 'Generica avanzata',
                                        'Attacco base', 'Attacco medio', 'Attacco avanzato',
                                        'Mentale base', 'Mentale media', 'Mentale avanzata',
                                        'Potere speciale')", 'result');

            foreach ($skills as $skl) {
                $tipo = '';
                $pg_shin = $skl['shin'];
                $locked = false; // Blocco la skill se questa richiede un livello pg più alto del mio

                if (substr($skl['tipo'], 0, strlen("Default")) === "Default") $tipo = 'Default';
                elseif (substr($skl['tipo'], 0, strlen("Difensiva")) === "Difensiva") $tipo = 'Default';
                elseif (substr($skl['tipo'], 0, strlen("Generica")) === "Generica") $tipo = 'Generica';
                elseif (substr($skl['tipo'], 0, strlen("Attacco")) === "Attacco") {
                    $tipo = 'Attacco';
                    $locked = ((int)$pg_level < (int)$skl['livello_sblocco']);
                } elseif (substr($skl['tipo'], 0, strlen("Mentale")) === "Mentale") {
                    $tipo = 'Mentale';
                    $locked = ((int)$pg_level < (int)$skl['livello_sblocco']);
                } elseif (substr($skl['tipo'], 0, strlen("Potere")) === "Potere") {
                    $tipo = 'Speciale';
                    $locked = ((int)$pg_level < (int)$skl['livello_sblocco']);
                }

                $skillRes[] = [
                    'id' => (int)$skl['id_abilita'],
                    'nome' => $skl['nome_abilita'],
                    'descrizione' => $skl['descrizione_abilita'],
                    'categoria' => $tipo,
                    'livello' => (int)$skl['grado'],
                    'maxLivello' => (int)$pg_level,
                    'locked' => $locked
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
                // Recupoero i dati del pg
                $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$pg_name'");
                $shinSpent = 0;

                // Verifico se il pg ha abbastanza shin
                foreach ($data['skills'] as $id => $levels) {
                    $old = $levels['old'];
                    $new = $levels['new'];
                    
                    // Calcolo cumulativo dei punti shin spesi per tutte le skill acquistate in questa sessione
                    for ($lvl = $old + 1; $lvl <= $new; $lvl++) $shinSpent += $lvl;

                    // Aggiorno il livello delle skill del pg
                    gdrcd_query("REPLACE INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('$pg_name', $id, $new)");
                }

                // Se sto tentando di spendere più shin di quelli disponibili, lancio un'eccezione
                // Altrimenti sottraggo gli shin al personaggio
                if ($pg['shin'] < $shinSpent && $data['shin'] == $shinSpent) throw new Exception('Non hai abbastanza shin!');
                else gdrcd_query("UPDATE personaggio SET shin = shin - $shinSpent WHERE nome = '$pg_name'");

                // Inserisco la spesa degli shin nei log
                gdrcd_query("INSERT INTO log_spesa (nome, punto_skill) VALUES ('$pg_name', $shinSpent)");

                echo json_encode(['success' => true, 'message' => 'Skill salvate con successo!']);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            
            exit;
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
        case 'removeGuildPg':  // Elimino il ruolo
            $nome = $data['nome'];
            $role = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'");
            $pg = gdrcd_query("UPDATE personaggio SET id_gilda = 0 WHERE nome = '$nome'");
            
            if ($pg && $role) echo json_encode(['success' => true, 'message' => 'Personaggio rimosso dalla gilda', 'query' => "DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'"]);
            else echo json_encode(['error' => false, 'message' => 'Impossibile rimuovere il personaggio']);
            
            break;
        case 'addGuildPg':  // Elimino il ruolo
            $nome = $data['nome'];
            $id_gilda = $data['id_gilda'];
            $remRole = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'");
            $role = gdrcd_query("SELECT * FROM ruolo WHERE gilda = $id_gilda AND livello = 1");
            $addRole = gdrcd_query("INSERT INTO clgpersonaggioruolo (personaggio, id_ruolo) VALUES ('$nome', ".$role['id_ruolo'].")");
            $pg = gdrcd_query("UPDATE personaggio SET id_gilda = $id_gilda WHERE nome = '$nome'");
            
            if ($remRole && $addRole && $pg) echo json_encode(['success' => true, 'message' => 'Personaggio aggiunto alla gilda']);
            else echo json_encode(['error' => false, 'message' => 'Abilità non trovata']);
            
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
        case 'getVoceStatuto':  // Recupero i dati del ruolo
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("SELECT * FROM statuti_new WHERE articolo = $id");
            
            if ($voce_statuto) echo json_encode($voce_statuto);
            else echo json_encode(['error' => false, 'message' => 'Voce statuto non trovata']);
            
            break;
        case 'deleteVoceStatuto':  // Elimino il ruolo
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("DELETE FROM statuti_new WHERE articolo = $id");
            
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
                    
                    $query = "UPDATE statuti_new SET ".implode(', ', $setParts)." WHERE articolo = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'articolo') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "INSERT INTO statuti_new (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
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
        case 'deleteSkill':  // Elimino il ruolo
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
        case 'getMessages': // Recupero i messaggi DM
            session_start();

            // Inizializza variabile per messaggi istantanei
            if (empty($_SESSION['last_istant_message'])) $_SESSION['last_istant_message'] = 0;

            $login = gdrcd_filter('in', $_SESSION['login']);

            // --- Messaggi non letti individuali ---
            $row_individuali = gdrcd_query(gdrcd_query("SELECT COUNT(*) AS cnt FROM conversazioni_individuali WHERE utente_nome = '$login' AND lettura = 0", 'result'), 'fetch');
            $cntNewMessageIndividuali = $row_individuali['cnt'];

            // --- Messaggi non letti gruppi ---
            $row_gruppo = gdrcd_query(gdrcd_query("SELECT COUNT(*) AS cnt FROM partecipazione_gruppo WHERE utente_nome = '$login' AND lettura = 0", 'result'), 'fetch');
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
        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
    /*********************  FINE    Recupero i dati dell'utente che voglio modificare   */
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>