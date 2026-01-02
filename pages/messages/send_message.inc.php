<?php
if (gdrcd_filter('get', $_POST['multipli']) == 'singolo') {
    $check_dest = explode(',', gdrcd_filter('get', $_POST['destinatario']));
    $destinat = trim($check_dest[0]);
    if (gdrcd_filter('get', $_POST['url']) != "") {
        $_POST['testo'] = $_SESSION['login'] . ' ti ha segnalato questo [url=' . $_POST['url'] . ']link[/url].';
    }//if

    $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '" . $destinat . "'", 'result');
    if ((gdrcd_query($result, 'num_rows') > 0) && (empty($destinat) === false)) {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('" . $_SESSION['login'] . "', '" . gdrcd_capital_letter(gdrcd_filter('in', $destinat)) . "', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        gdrcd_query("INSERT INTO backmessaggi (mittente, destinatario, spedito, testo) VALUES ('" . $_SESSION['login'] . "', '" . gdrcd_capital_letter(gdrcd_filter('in', $destinat)) . "', NOW(), '" . gdrcd_filter('in', $_POST['testo']) . "')");
    }//if
} else {
    
    
    //PRESENTI SOLO ADMIN E MASTER
    
    if ($_POST['multipli'] == 'presenti') {
        $query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.online_status, personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.posizione, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, mappa.stanza_apparente, mappa.nome as luogo, mappa_click.nome as mappa FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN mappa_click ON personaggio.ultima_mappa = mappa_click.id_click LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW() ORDER BY personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.nome";
        $result = gdrcd_query($query, 'result');
        if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) {
        while ($record = gdrcd_query($result, 'fetch')) {
            gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('" . $_SESSION['login'] . "', '" . $record['nome'] . "', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        }
        }//fine presenti
    
    
    } elseif ($_POST['multipli'] == 'multiplo') {
        $check_dest = explode(',', $_POST['destinatario']);
        $query = "INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES";
        foreach ($check_dest as $destinat) {
            $destinat = trim($destinat);

            $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '" . gdrcd_filter('in', $destinat) . "'", 'result');
            if (gdrcd_query($result, 'num_rows') > 0) {
                $query .= " ('" . $_SESSION['login'] . "', '" . gdrcd_capital_letter(gdrcd_filter('in', $destinat)) . "', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "'),";
            }
        }
        $query = substr($query, 0, -1);
        gdrcd_query($query);
        /** * Bugfix: commentato la stampa della variabile $query. In caso di messaggio multiplo stampava
         * l'ultima query eseguita.
         * @author Rhllor
         */
    } 
    
    
    //CAPOGILDA (Solo x admin, master e capogilda)
        else if ($_POST['multipli']=='capogilda')
        {
        $capoguilds= "SELECT * FROM privilegi WHERE capogilda = 1";
        $resultguilds = gdrcd_query($capoguilds, 'result');
        if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capogilda'] == 1) {

        while($record = gdrcd_query($resultguilds , 'fetch'))
        {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('".$_SESSION['login']."', '".$record['nome']."', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        }
        }
        gdrcd_query($capoguilds , 'free');
        }
        
        //CAPOMESTIERE (Solo x admin, master e capomestiere)
        else if ($_POST['multipli']=='capomestiere')
        {
        $capojob= "SELECT * FROM privilegi WHERE capomestiere = 1";
        $resultjob = gdrcd_query($capojob, 'result');
        if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capomestiere'] == 1) {

        while($record = gdrcd_query($resultjob , 'fetch'))
        {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('".$_SESSION['login']."', '".$record['nome']."', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        }
        }
        gdrcd_query($capojob , 'free');
        }
        
        
        //TUTTA LA FAMIGLIA (Solo x admin e capofamiglia)
        else if ($_POST['multipli']=='gilda')
        {
        $guilds= "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.id_ruolo, ruolo.nome_ruolo, ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio= '".$_SESSION['login']."' AND ruolo.gilda>-1)";
        $resultguild = gdrcd_query($guilds, 'result');
        if($_SESSION['admin'] == 1 || $_SESSION['capogilda'] == 1) {

        while($record = gdrcd_query($resultguild , 'fetch'))
        {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('".$_SESSION['login']."', '".$record['personaggio']."', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        #echo "<script type='text/javascript'>alert('".$record["personaggio"]."');</script>";
        }
        }
        gdrcd_query($guilds , 'free');
        }
        
        
        //TUTTO IL MESTIERE (Solo x admin e capomestiere)
        else if ($_POST['multipli']=='tutto_mestiere')
        {
        $lavoro= "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1)";
        $risultato_lavoro = gdrcd_query($lavoro, 'result');
        if($_SESSION['admin'] == 1 || $_SESSION['capomestiere'] == 1) {

        while($record_lavoro = gdrcd_query($risultato_lavoro , 'fetch'))
        {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('".$_SESSION['login']."', '".$record_lavoro['personaggio']."', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        }
        }
        gdrcd_query($lavoro , 'free');
        }
        
        //TUTTI GLI INCLINATI (Solo x admin e capomestiere)
        else if ($_POST['multipli']=='tutto_inclinati')
        {
        $inclinati = gdrcd_query("SELECT personaggio FROM clgpersonaggioinclinazione", 'result');
            while ($row = gdrcd_query($inclinati, 'fetch'))
            {
                gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('" . $_SESSION['login'] . "', '" . $row['nome'] . "' , NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', 'staff')");
            }
        gdrcd_query($inclinati , 'free');
        }
        
    //GLOBALE SOLO ADMIN
    
    elseif ($_POST['multipli'] == "broadcast") {
        $personaggio = gdrcd_query("SELECT * FROM personaggio where nome = '" . $_SESSION['login'] . "'", 'result');

        if($_SESSION['admin'] == 1) {
            $query = gdrcd_query("SELECT nome FROM personaggio", 'result');
            while ($row = gdrcd_query($query, 'fetch'))
            {
                gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('" . $_SESSION['login'] . "', '" . $row['nome'] . "' , NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', 'staff')");
            }
        }
        gdrcd_query($query, 'free');
        
        
        
    } elseif (is_numeric($_POST['multipli']) === true) {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('" . $_SESSION['login'] . "', '" . $_POST['multipli'] . "', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
    } elseif (empty($_POST['destinatario']) === false) {
        
        
        //permessi per mex multiplo
        if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1) {
        $Dest = explode(',', $_POST['destinatario']);
        } else {
        $Dest[0] = $_POST['destinatario'];
        }
        for ($i = 0; $i <= count($Dest)-1; $i++) {
        $destinat = trim($Dest[$i]);
        $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '" . $destinat . "'", 'result');
       
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, titolo, testo, tipo) VALUES ('" . $_SESSION['login'] . "', '" . gdrcd_capital_letter(gdrcd_filter('in', $destinat)) . "', NOW(), '" . gdrcd_filter('in', $_POST['titolo']) . "', '" . gdrcd_filter('in', $_POST['testo']) . "', '" . gdrcd_filter('in', $_POST['tipo']) . "')");
        gdrcd_query("INSERT INTO backmessaggi (mittente, destinatario, spedito, testo) VALUES ('" . $_SESSION['login'] . "', '" . gdrcd_capital_letter(gdrcd_filter('in', $destinat)) . "', NOW(), '" . gdrcd_filter('in', $_POST['testo']) . "')");
        }//fine for
   }
   
}//else
?>
    <div class="warning">
        <?php echo $PARAMETERS['names']['private_message']['sing'] . $MESSAGE['interface']['messages']['sent']; ?>
    </div>
