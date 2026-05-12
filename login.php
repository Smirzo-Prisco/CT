<?php
/*Inizio la sessione */
session_start();

/*Includo i file principali */
require_once('includes/required.php');

/*Connessione al database*/
$handleDBConnection = gdrcd_connect();

/*Leggo i dati del form di login*/
$login1 = gdrcd_filter('get', $_POST['login1']);
$pass1 = gdrcd_filter('get', $_POST['pass1']);

/** * Fix per il funzionamento in locale dell'engine
 * @author Blancks
 */
$host = ($_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') ? 'localhost' : gethostbyaddr($_SERVER['REMOTE_ADDR']);

/** * Fine Fix
 */

/*Controllo se la postazione non sia stata esclusa dal sito*/
$result = gdrcd_query("SELECT * FROM blacklist WHERE ip = '".$_SERVER['REMOTE_ADDR']."' AND granted = 0", 'result');

if(gdrcd_query($result, 'num_rows') > 0) {
    gdrcd_query($result, 'free');

    /*Se la postazione è stata esclusa*/
    echo '<div class="error_box"><h2 class="error_major">'.$MESSAGE['warning']['blacklisted'].'</h2></div>';
    /*Registro l'evento (Tentativo di connessione da postazione esclusa)*/
    //gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".$login1."', 'Login_procedure', NOW(), ".BLOCKED.", '".$_SERVER['REMOTE_ADDR']."')");
    
    exit();
}


$login1 = ucwords(strtolower(trim($login1)));

/*Carico dal database il profilo dell'account (personaggio)*/
$record = gdrcd_query("SELECT personaggio.id_gilda, personaggio.ctnews_letto, personaggio.pass, personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.id_razza, personaggio.id_mestiere, personaggio.id_ruolo_mestiere, personaggio.ultimo_messaggio, personaggio.blocca_media, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, razza.sing_m, razza.sing_f, razza.icon AS url_img_razza, privilegi.admin as admin, privilegi.moderatore as moderatore, privilegi.master as master, privilegi.guida as guida, privilegi.capomestiere as capomestiere, privilegi.capogilda as capogilda, privilegi.grafico as grafico FROM personaggio LEFT JOIN razza ON personaggio.id_razza = razza.id_razza LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE personaggio.nome = '".gdrcd_filter('in', $login1)."' LIMIT 1");

/*Se esiste un personaggio corrispondente al nome ed alla password specificati*/
/** * Aggiunti i controlli sugli orari di connessione e disconnessione per impedire i doppi login con gli stessi account
 * Se si esce non correttamente dal gioco, sarà possibile entrare dopo 5 minuti dall'ultimo refresh registrato
 * @author Blancks
 */
#if( ! empty($record) and gdrcd_password_check($pass1, $record['pass']) && ($record['permessi'] > -1) && (strtotime($record['ora_entrata']) < strtotime($record['ora_uscita']) || (strtotime($record['ultimo_refresh']) + 300) < time())) {
if( !empty($record) && gdrcd_password_check($pass1, $record['pass']) && ($record['permessi'] > -1)) {
    // Controllo se l'utente ha una gilda e, se si, se è un custode, così inserisco in sessione la voce "custode" con il valore zero oppure uno
    $_SESSION['magic'] = ($record['id_mestiere'] == 3 ? 1 : 0);
    $_SESSION['custode'] = ($record['id_gilda'] == 4 ? 1 : 0);
    $_SESSION['login'] = $record['nome'];
    $_SESSION['cognome'] = $record['cognome'];
    $_SESSION['permessi'] = $record['permessi'];
    $_SESSION['sesso'] = $record['sesso'];
    $_SESSION['admin'] = $record['admin'];
    $_SESSION['capogilda'] = $record['capogilda'];
    $_SESSION['capomestiere'] = $record['capomestiere'];
    $_SESSION['master'] = $record['master'];
    $_SESSION['moderatore'] = $record['moderatore']; 
    $_SESSION['guida'] = $record['guida'];
    $_SESSION['grafico'] = $record['grafico'];
    $_SESSION['ctnews_letto'] = $record['ctnews_letto'];
    $_SESSION['user'] = 1;

    /** * Controllo sul bloccaggio dei suoni per l'utente
     * @author Blancks
     */
    $_SESSION['blocca_media'] = $record['blocca_media'];

    /** * Archiviazione dato utile per capire quanti nuovi topic in bacheca ci sono rispetto all'ultima visita
     * @author Blancks
     */
    $_SESSION['ultima_uscita'] = $record['ora_uscita'];

    $_SESSION['razza'] = ($record['sesso'] == 'f') ? $record['sing_f'] : $record['sing_m'];
    
    $_SESSION['mestiere'] = $record['id_mestiere'];
    $_SESSION['img_razza'] = $record['url_img_razza'];
    $_SESSION['id_razza'] = $record['id_razza'];
    $_SESSION['posizione'] = $record['posizione'];
    $_SESSION['mappa'] = (empty($record['ultima_mappa']) === true) ? 1 : $record['ultima_mappa'];
    $_SESSION['luogo'] = (empty($record['ultimo_luogo']) === true) ? -1 :  $_SESSION['luogo'] = $record['ultimo_luogo'];
    $_SESSION['tag'] = "";
    $_SESSION['last_message'] = 0;
    $_SESSION['last_istant_message'] = $record['ultimo_messaggio'];
    
    function log_doppio_esiste($nome1, $nome2, $IP) {
        $nome1 = gdrcd_filter('in', $nome1);
        $nome2 = gdrcd_filter('in', $nome2);

        $query = "SELECT id FROM log_doppi 
                WHERE ((Nome = '$nome1' AND Doppio = '$nome2') OR (Nome = '$nome2' AND Doppio = '$nome1')) 
                    AND IP = '".gdrcd_filter('in', $IP)."' 
                LIMIT 1";

        $result = gdrcd_query($query, 'result');
        $row = gdrcd_query($result, 'fetch');
        gdrcd_query($result, 'free');

        return $row ? $row['id'] : false;
    }

    function logga_doppio($pg1, $pg2, $IP, $Host, $Browser) {
        $id_doppio = log_doppio_esiste($pg1, $pg2, $IP);

        if ($id_doppio !== false) {
            // aggiorna solo la data
            gdrcd_query("UPDATE log_doppi SET DataEvento = NOW() WHERE id = $id_doppio");
        } else {
            // inserisce nuovo
            gdrcd_query("INSERT INTO log_doppi (Nome, Doppio, IP, Host, Browser, DataEvento) 
                        VALUES ('".gdrcd_filter('in', $pg1)."', 
                                '".gdrcd_filter('in', $pg2)."', 
                                '".gdrcd_filter('in', $IP)."', 
                                '".gdrcd_filter('in', $Host)."', 
                                '".gdrcd_filter('in', $Browser)."', 
                                NOW())");
        }
    }

    $res = gdrcd_query("SELECT ruolo.gilda, ruolo.immagine FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio = '".gdrcd_filter('in', $record['nome'])."'", 'result');

    while($row = gdrcd_query($res, 'fetch')) {
        $_SESSION['gilda'] .= ',*'.$row['gilda'].'*';
        $_SESSION['img_gilda'] .= $row['immagine'].',';
    }
    gdrcd_query($res, 'free');

    /* Carico l'ultimo ip con cui si è collegato il personaggio*/
    $lastlogindata = gdrcd_query("SELECT autore FROM log WHERE nome_interessato = '".gdrcd_filter('in', $_SESSION['login'])."' AND codice_evento=".LOGGEDIN." ORDER BY data_evento DESC LIMIT 1");

/*Se la postazione ha già un cookie attivo per un personaggio differente registro l'evento (Possibile account multiplo)
if((isset($_COOKIE['lastlogin']) === true) && ($_COOKIE['lastlogin'] != $_SESSION['login'])) {
    gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."','doppio (cookie)', NOW(), ".ACCOUNTMULTIPLO.", '".$_COOKIE['lastlogin']."')");
} elseif($lastlogindata['autore'] == $_SERVER['REMOTE_ADDR']) {
    gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."','doppio (ip)', NOW(), ".ACCOUNTMULTIPLO.", '".gdrcd_filter('in', $lastlogindata['nome_interessato'])."')");
}*/

/*Registro l'evento (Avvenuto login)
gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."','".$_SERVER['REMOTE_ADDR']."', NOW(), ".LOGGEDIN." ,'".$_SERVER['REMOTE_ADDR']."')");
*/

/*Cartella entrate
$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
$IP = $_SERVER['REMOTE_ADDR'];
$Host = gethostbyaddr($_SERVER['REMOTE_ADDR']);*/

/* Carico l'ultimo ip con cui si è collegato il personaggio
$last_entrata = gdrcd_query("SELECT Nome, COUNT(*) AS n_bianca FROM log_entrate WHERE IP = '$IP' && Nome != '".$_SESSION['login']."' ORDER BY DataEvento DESC LIMIT 1");
//doppi
if((isset($_COOKIE['lastlogin']) === true) && ($_COOKIE['lastlogin'] != $_SESSION['login'])) {
gdrcd_query("INSERT INTO log_doppi (Nome, Doppio, DataEvento, IP, Host) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."', '".$_COOKIE['lastlogin']."', NOW(), '$IP', '$Host')");
} else if ($last_entrata['n_bianca'] > 0) {
gdrcd_query("INSERT INTO log_doppi (Nome, Doppio, DataEvento, IP, Host) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."', '".$last_entrata['Nome']."', NOW(), '$IP', '$Host')");
}
*/

// Recupero IP reale da Cloudflare (fallback a REMOTE_ADDR)
$ip_raw = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR'];
$IP = gdrcd_filter('in', $ip_raw);
$Host = gdrcd_filter('in', gethostbyaddr($ip_raw));
$Browser = gdrcd_filter('in', $_SERVER['HTTP_USER_AGENT']);

// Ultimo utente collegato con questo IP (diverso dall'attuale)
$last_entrata_doppio = gdrcd_query("SELECT Nome, COUNT(*) AS n_bianca 
                            FROM log_entrate 
                            WHERE IP = '$IP' AND Nome != '".gdrcd_filter('in', $_SESSION['login'])."' 
                            ORDER BY DataEvento DESC 
                            LIMIT 1");

// Caso 1: cookie lastlogin è diverso dall'utente attuale
if (isset($_COOKIE['lastlogin']) && ($_COOKIE['lastlogin'] != $_SESSION['login'])) {
    logga_doppio($_SESSION['login'], $_COOKIE['lastlogin'], $IP, $Host, $Browser);
} else if ($last_entrata_doppio['n_bianca'] > 0) {
    logga_doppio($_SESSION['login'], $last_entrata_doppio['Nome'], $IP, $Host, $Browser);
}

// Aggiorna il cookie per tracciare il login
setcookie('lastlogin', $_SESSION['login'], time() + (86400 * 30), "/");

// Verifica se l'utente è già entrato nelle ultime 24 ore
$query_check = gdrcd_query("SELECT COUNT(*) as count FROM log_entrate WHERE Nome = '".gdrcd_filter('in', $_SESSION['login'])."' AND DataEvento > NOW() - INTERVAL 1 DAY");


if ($query_check['count'] > 0) {
    // L'utente è già entrato nelle ultime 24 ore, controlla l'IP
    $query_ip_check = gdrcd_query("SELECT COUNT(*) as count FROM log_entrate WHERE Nome = '".gdrcd_filter('in', $_SESSION['login'])."' AND IP = '$IP' AND DataEvento > NOW() - INTERVAL 1 DAY");

    if ($query_ip_check['count'] == 0) {
        // L'IP è diverso, inserisco il nuovo record
        gdrcd_query("INSERT INTO log_entrate (Nome, DataEvento, IP, Host) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."', NOW(), '$IP', '$Host')");
    }
} else {
    // L'utente non è mai entrato nelle ultime 24 ore, inserisco il nuovo record
    gdrcd_query("INSERT INTO log_entrate (Nome, DataEvento, IP, Host) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."', NOW(), '$IP', '$Host')");
}

} 
#elseif(strtotime($record['ora_entrata']) > strtotime($record['ora_uscita']) || (strtotime($record['ultimo_refresh']) + 300) > time()) {
#    /*Se la postazione è stata esclusa*/
#    echo '<div class="error_box"><h2 class="error_major">'.$MESSAGE['warning']['double_connection'].'</h2></div>';
#    /*Registro l'evento (Tentativo di connessione da postazione esclusa)*/
#    gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".$login1."', 'Login_procedure', NOW(), ".BLOCKED.", '".$_SERVER['REMOTE_ADDR']."')");
#    exit();
#} 
#else {
else if(empty($record) or !gdrcd_password_check($pass1, $record['pass'])){
    /*Sono stati inseriti username e password errati*/
    $_SESSION['login'] = '';

    if(($login1 != '') && ($pass1 != '')) {
        /*Registro l'evento (Login errato)
        gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento) VALUES ('".gdrcd_filter('in', $_SESSION['login'])."','".$host."', NOW(), ".ERRORELOGIN." ,'".$_SERVER['REMOTE_ADDR']."')");
        */
        $record = gdrcd_query("SELECT count(*) FROM log WHERE descrizione_evento = '".$_SERVER['REMOTE_ADDR']."' AND codice_evento = ".ERRORELOGIN." AND DATE_ADD(data_evento, INTERVAL 60 MINUTE) > NOW()");
        /*Se ho tentato 10 login fallendo nel giro di un ora*/
        $iErrorsNumber = $record['count(*)'];

        if($iErrorsNumber >= 10) {
            gdrcd_query("INSERT INTO blacklist (ip, nota, ora, host) VALUES ('".$_SERVER['REMOTE_ADDR']."', '".$login1." (tenta password)', NOW(), '".$Host."')");
        }
    }
}

/*Eseguo l'accesso*/
if($_SESSION['login'] != '') {
    if(gdrcd_controllo_esilio($_SESSION['login']) === true) {
        session_destroy();
        echo '<a href="index.php">'.$PARAMETERS['info']['homepage_name'].'</a>';
        exit();
    } else {
        /*Creo un cookie*/
        setcookie('lastlogin', $_SESSION['login'], 0, '', '', 0);

        if($PARAMETERS['settings']['auto_salary'] = 'ON') {
            /*Stipendio*/
            $row = gdrcd_query("SELECT soldi, banca, ultimo_stipendio FROM personaggio WHERE nome = '".$_SESSION['login']."' LIMIT 1");

            if ($row['ultimo_stipendio'] != strftime("%Y-%m-%d")) {
                $query_stipendio = "
                    SELECT COALESCE(SUM(unione_stipendi.stipendio), 0) as totale_stipendio 
                    FROM (
                        SELECT ruolo_mestiere.stipendio 
                        FROM clgpersonaggiomestiere 
                        LEFT JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo 
                        WHERE clgpersonaggiomestiere.personaggio = '".$_SESSION['login']."'
                        UNION ALL
                        SELECT ruolo_mestiere.stipendio 
                        FROM clgpersonaggiolavoro 
                        LEFT JOIN ruolo_mestiere ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo 
                        WHERE clgpersonaggiolavoro.personaggio = '".$_SESSION['login']."'
                    ) AS unione_stipendi";
                
                $result_stipendio = gdrcd_query($query_stipendio);
                $stipendio_tot = $result_stipendio['totale_stipendio'];

                // Aggiorna la banca con il totale degli stipendi e imposta la data dell'ultimo stipendio a oggi
                gdrcd_query("UPDATE personaggio SET banca = banca + ".$stipendio_tot.", ultimo_stipendio = NOW() WHERE nome = '".$_SESSION['login']."'");
            }
        }

        if($PARAMETERS['mode']['log_back_location'] == 'OFF') {
            $_SESSION['luogo'] = '-1';
            /*Inserisco nei presenti*/
            $remote_addr = $_SERVER['REMOTE_ADDR'];
            $login_filtered = gdrcd_filter('in', $_SESSION['login']);
            gdrcd_query("UPDATE personaggio SET ora_entrata = NOW(), ultimo_luogo='-1', ultimo_refresh = NOW(), last_ip = '$remote_addr', is_invisible = 0 WHERE nome = '$login_filtered'");
            notifySocketServer('users:update', 'loc:-1');

            /*Redirigo alla pagina del gioco*/
            header('Location: main.php?page=mappaclick&map_id='.$_SESSION['mappa'], true);
        } else {
            /*Inserisco nei presenti*/
            gdrcd_query("UPDATE personaggio SET ora_entrata = NOW(), ultimo_refresh = NOW(), last_ip = '".$_SERVER['REMOTE_ADDR']."',  is_invisible = 0 WHERE nome =  '".$_SESSION['login']."'");
            notifySocketServer('users:update', 'loc:' . (int)$_SESSION['luogo']);

            /*Redirigo alla pagina del gioco*/
            header('Location: main.php?dir='.$_SESSION['luogo'], true);
        }

        //setto gli estremi per il back//
        $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
        $now = time(); 
        $this_morning = strtotime('today 6:00'); 
        $last_action = strtotime($check_backing['last_date_back']);
        
        if ($now - $last_action >= (24 * 60 * 60) || ($last_action < $this_morning && $now >= $this_morning)) {
            gdrcd_query("UPDATE personaggio SET back_chat = 0 WHERE nome = '".$_SESSION['login']."'");
        }
    }//else
} else {
    /*Dichiaro il fallimento dell'operazione di login*/
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
        <link rel='stylesheet' href='themes/<?=$PARAMETERS['themes']['current_theme']?>/main.css' TYPE='text/css'>
        <link rel='stylesheet' href='themes/<?=$PARAMETERS['themes']['current_theme']?>/homepage.css'
              TYPE='text/css'>
        <link rel='shortcut icon' href='favicon.ico' />
    </head>
    <body>
        <div class="error_box">
            <h2 class="error_major"><?=$MESSAGE['error']['unknown_username']?></h2>
            <span class="error_details"><?=$MESSAGE['error']['unknown_username_details']?></span>
            <span class="error_details"><?=$MESSAGE['error']['unknown_username_failure_count']?></span>
            <span class="error_details"><?=$iErrorsNumber?></span>
            <span class="error_details"><?=$MESSAGE['error']['unknown_username_warning']?></span>
            <span class="error_details"><?=$MESSAGE['warning']['mailto']?></span>
            <a href="mailto:<?=$PARAMETERS['menu']['webmaster_email'] ?>">
                <?=$PARAMETERS['menu']['webmaster_email'] ?>
            </a> .
        </div>
    <?php
    session_destroy();
}
?>
    </body>
</html>
<?php
gdrcd_close_connection($handleDBConnection);
?>
