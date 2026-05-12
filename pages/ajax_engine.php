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
        case 'getMessagesOngameStatus':
            session_start();
            $login = gdrcd_filter('in', $_SESSION['login']);
            $hasNewOn = false;
            $hasNewOff = false;

            $r_ind = gdrcd_query("SELECT s.ongame FROM conversazioni_individuali ci JOIN sms s ON ci.id_conversazione = s.id_conversazione WHERE ci.utente_nome = '$login' AND ci.lettura = 0 GROUP BY ci.id_conversazione", 'result');
            while ($row = gdrcd_query($r_ind, 'fetch')) {
                if ($row['ongame'] == 1) $hasNewOn = true; else $hasNewOff = true;
            }
            gdrcd_query($r_ind, 'free');

            $r_grp = gdrcd_query("SELECT MIN(s.ongame) as ongame FROM partecipazione_gruppo pg JOIN sms s ON pg.gruppo_id = s.gruppo_id WHERE pg.utente_nome = '$login' AND pg.lettura = 0 GROUP BY pg.gruppo_id", 'result');
            while ($row = gdrcd_query($r_grp, 'fetch')) {
                if ($row['ongame'] == 1) $hasNewOn = true; else $hasNewOff = true;
            }
            gdrcd_query($r_grp, 'free');

            echo json_encode(['hasNewOn' => $hasNewOn, 'hasNewOff' => $hasNewOff]);
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