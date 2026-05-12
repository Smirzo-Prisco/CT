<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_GET['op']) && $_GET['op'] != '') {
    session_start();

    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');
    require_once(__DIR__ . '/../includes/chat_functions.inc.php');
    
    // IMPORTANTE: Solo per le richieste AJAX
    header('Content-Type: application/json');   
    
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
                if($skill_info['sottotipo'] === 'comando' && checkMentaleComando($id_role, $bersaglio, $turn)) {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi lanciare due mentali di comando sullo stesso bersaglio per due turni di fila'));
                    exit;
                }
            /**************************** FINE  CONTROLLI   ************************************************/
            
            $messaggio = '';
            $sussurro = null;
            /**************************** COSTRUZIONE MESSAGGIO   ************************************************/
            switch ($skill_info['tipo']) {
                //  FISICA
                case 'Attacco base':
                case 'Attacco medio':
                case 'Attacco avanzato':
                    $messaggio .= "$login usa la skill fisica ".$skill_info['nome']." di livello $livello";
                    $diceLounch = lanciaStat($id_role, $login, implode(',', $bersaglio), true, $car, $car, $pg['car'.$skill_info['car']], $salute, 0, 0);
                    $dice = $diceLounch['risultato'];
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
            fight($id_role, $login, implode(',', $bersaglio), $skill, $livello, $car, $dice, 'usa una skill '.$skill_info['tipo']);
            chatInsertMessage($luogo, $login, null, $messaggio.$tiro, 'C', $sussurro, '', null); // Messaggio in chat
            assegnaPuntoShin($luogo, $login); // Assegna il punto Shin se necessario
            gestionePoliziaAutomatica($luogo); // Gestione della polizia automatica
            gestisciSkillTemporanea($skill, $login); // Gestisci le skill temporanee
            /**************************** FINE  AZIONI   ************************************************/
            
            // Aggiorna la salute del personaggio se la skill lanciata non è un talento
            if($car != 'talento') gdrcd_query("UPDATE personaggio SET salute = salute-1 WHERE nome = '$login'");

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
                
                // Se sto lanciando un attacco, devo verificare se non l'ho già lanciato in questo turno
                if(checkMultipleLounch($id_role, $login, ["'destrezza'", "'potere'", "'mente'", "'difesa'"], $turn)) {
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
                    fight($id_role, $login, $bersaglio, 0, 1, $nome_tiro, $diceLounch['risultato'], 'creatura'); // Funzione di gestione combattimenti
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Attenzione! Non hai evocato nessuna creatura.'));
                    exit;
                }
            }

            // Se il pg ha il supporto di un'abilità (talento)
            $totale = $d20 + $destrezza + $bonus_arma + $bonus_talento;
            $sussurro .= "$d20/20 + $destrezza".($check_abilita > 0 ? " + $bonus_arma (bonus arma) $sussurro_specifico" : '');

            /*******    MALUS SALUTE    **********/
            if ($salute <= 50) {
                if ($salute > 40) $malus_salute = 1;
                elseif ($salute > 30) $malus_salute = 3;
                elseif ($salute > 20) $malus_salute = 5;
                elseif ($salute > 0) $malus_salute = 10;
                
                $totale -= $malus_salute;
            }

            if ($malus_salute > 0) $sussurro .= " - $malus_salute di malus per la salute = $totale";
            /*******    FINE    MALUS SALUTE    **********/

            $messaggio = "$login $descrizione_attacco <u>$bersaglio</u>$arma_body con un tiro totale di destrezza di $totale";
            
            // Registro l'attacco
            fight($id_role, $login, $bersaglio, 0, 1, 'destrezza', $totale, $descrizione_attacco); // Funzione di gestione combattimenti
            
            // Inserisci i messaggi in chat
            chatInsertMessage($luogo, $login, null, $messaggio, 'C', $sussurro);
            
            // Gestione della polizia automatica
            gestionePoliziaAutomatica($luogo);

            echo json_encode(array('success' => true, 'message' => 'Attacco eseguito con successo.', 'tipo attacco' => $tipo_attacco));

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

            // Se sono l'unico pg nella role e sto cercando di inviare un messaggio che non è tra quelli permessi senza role attiva, blocco l'invio
            if(count(getRolePgs($id_role)) == 1 && !$typePermitted) {
                echo json_encode(array('success' => false, 'message' => 'Attenzione! Non puoi inviare un\'azione se sei l\'unico pg nella role'));
                exit;
            }

            // Se il personaggio che invia è soggetto a una skill di durata, scalo i punti (integrità) e controllo se ne ha troppo pochi
            checkSkillEffect($login, $location);
            /**************************** FINE  CONTROLLI   ************************************************/

            // Determina se è un sussurro, un messaggio normale o comando stanza privata
            if ($m_type === 'S') handleWhisperMessage($type, $chat_message, $tag_n_beyond, $m_type, $_SESSION);
            elseif ($type < "5" || $type > "7") handleNormalMessage($chat_message, $action_tag, $login, $m_type, $_SESSION, $PARAMETERS, $id_role);
            else handleRoomCommand($m_type, $tag_n_beyond, $_SESSION, $data);
            
            // Aggiorna tag nella sessione
            $_SESSION['tag'] = gdrcd_filter('in', $data['tag']);

            // Risposta JSON per AJAX
            notifySocketServer('chat:update', 'chat:' . (int)$location);
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
            $location = $_SESSION['luogo'];
            $id_role = locationActiveRole($location); // Recupera l'eventuale role attiva nella chat
            $pngName = gdrcd_filter('post', $data['pngName']);

            // Inserisco il png nella tabella dei personaggi se non esiste già
            $existingPng = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '$pngName'", 'result');
            if (gdrcd_query($existingPng, 'num_rows') == 0)  gdrcd_query("INSERT INTO personaggio (nome, salute, integrita, car2) VALUES ('$pngName', 100, 100, 7)");

            // Inserisco il png nella role
            addPgToRole($id_role, $pngName, $location, 1);

            echo json_encode(array('success' => true, 'message' => 'PNG aggiunto con successo!'));

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
        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>