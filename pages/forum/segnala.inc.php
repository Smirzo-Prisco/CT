<?php
// Prima parte: gestione della segnalazione

if ($_REQUEST['op'] == 'segnala') {
    $gilda = gdrcd_query("SELECT * FROM personaggio WHERE nome ='" . $_SESSION['login'] . "'");
    $row = gdrcd_query("SELECT * FROM messaggioaraldo WHERE id_messaggio=" . gdrcd_filter('num', $_REQUEST['what']));
    $araldo = gdrcd_query("SELECT * FROM araldo WHERE id_araldo=" . $row['id_araldo']);

    echo '<h3>' . gdrcd_filter('out', 'Segnala la Discussione') . '</h3>
    <form action="main.php?page=forum&op=segnala" method="post">
    <input type="text" list="personaggi" name="interessato" placeholder="Nome del personaggio" />
    <br><select name="multipli">
        <option value="---" selected> ------- </option>';

    if ($_SESSION['admin'] == 1) {
        echo '<option value="broadcast">' . gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['all']) . '</option>';
    }

    echo '<option value="moderatore">Segnala a un moderatore</option>';

    if ($araldo['id_araldo'] == 7 || $araldo['id_araldo'] == 23) {
        echo '<option value="master">Segnala ai master</option>';
    } elseif ($araldo['id_araldo'] == 9 || $araldo['id_araldo'] == 110) {
        echo '<option value="capo_gilda">Segnala ai Capogilda</option>';
    } elseif ($araldo['id_araldo'] == 109) {
        echo '<option value="guide">Segnala alle guide</option>';
    }

    // Gestione delle famiglie
    $famiglie_gilde = [
        1 => 'paladini',
        2 => 'demoni',
        3 => 'setta',
        4 => 'custodi',
        5 => 'guardiani',
        6 => 'fiori',
        7 => 'lancaster'
    ];

    foreach ($famiglie_gilde as $id_gilda => $nome_gilda) {
        if ($araldo['tipo'] == 5 && ($_SESSION['admin'] == 1 || $gilda['id_gilda'] == $id_gilda)) {
            echo "<option value='$nome_gilda'>Segnala ai " . ucfirst($nome_gilda) . "</option>";
        }
    }

    // Gestione dei mestieri
    $mestieri = [
        2 => 'tae',
        3 => 'magic',
        4 => 'secret',
        6 => 'corte'
    ];

    foreach ($mestieri as $id_mestiere => $nome_mestiere) {
        if ($araldo['tipo'] == 6 && ($_SESSION['admin'] == 1 || $gilda['id_mestiere'] == $id_mestiere)) {
            echo "<option value='$nome_mestiere'>Segnala alla " . ucfirst($nome_mestiere) . "</option>";
        }
    }

    echo '</select>
    <br>Commenta la Segnalazione:<br>
    <textarea type="text" name="CommentoSegnalazione" cols="50" rows="6" placeholder="Eventuale commento"></textarea>
    <input type="hidden" name="what" value="' . $row["id_messaggio"] . '" />
    <input type="hidden" name="where" value="' . $row["id_araldo"] . '" />
    <input type="hidden" name="op" value="segnalaok" />
    <p>' . gdrcd_filter('out', 'Invia la segnalazione') . '</p>
    <input type="submit" value="' . gdrcd_filter('out', 'Segnala') . '" />
    </form>';
}

// Seconda parte: gestione dell'invio della segnalazione

if ($_REQUEST['op'] == 'segnalaok') {
    $segnalazione = "<a href='main.php?page=forum&op=read&what=" . gdrcd_filter('num', $_REQUEST['what']) . "&where=" . gdrcd_filter('num', $_REQUEST['where']) . "' target='_top'>Clicca qui</a>";
    $CommentoSegnalazione = gdrcd_filter('in', $_POST['CommentoSegnalazione']);
    $testo = gdrcd_filter_in("<b>$segnalazione" . " (<i>Messaggio segnalato da " . $_SESSION['login'] . "</i>)<br><br></b>" . "$CommentoSegnalazione");

    // 1. Gestione dei destinatari multipli in base al ruolo
    $destinatari_multipli = [
        'moderatore' => "SELECT nome FROM privilegi WHERE moderatore = 1",
        'guide' => "SELECT nome FROM privilegi WHERE guida = 1",
        'master' => "SELECT nome FROM privilegi WHERE master = 1",
        'capo_gilda' => "SELECT nome FROM privilegi WHERE capogilda = 1"
    ];

    foreach ($destinatari_multipli as $key => $query) {
        if ($_POST['multipli'] == $key) {
            $result = gdrcd_query($query, 'result');
            while ($record = gdrcd_query($result, 'fetch')) {
                $nome = $record['nome'];

                // Invia segnalazione direttamente nel ciclo
                $sql_verifica_conversazione = "
                    SELECT id_conversazione 
                    FROM sms 
                    WHERE mittente_nome = 'Segnalazione' 
                    AND destinatario_nome = '" . gdrcd_filter('in', $nome) . "' 
                    LIMIT 1
                ";
                $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

                if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
                    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
                    $id_conversazione = $row_conversazione['id_conversazione'];

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);
                } else {
                    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
                    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
                    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
                    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);

                    $sql_inserisci_conversazione = "
                        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                        VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $nome) . "', 0)
                    ";
                    gdrcd_query($sql_inserisci_conversazione);
                }
            }
        }
    }

    // 2. Gestione delle segnalazioni alle famiglie
    $famiglie = [
        'paladini' => 1,
        'demoni' => 2,
        'setta' => 3,
        'custodi' => 4,
        'guardiani' => 5,
        'fiori' => 6,
        'lancaster' => 7
    ];

    foreach ($famiglie as $key => $id_gilda) {
        if ($_POST['multipli'] == $key) {
            $query = "
                SELECT clgpersonaggioruolo.personaggio 
                FROM clgpersonaggioruolo 
                JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo 
                WHERE ruolo.gilda = $id_gilda
            ";
            $result = gdrcd_query($query, 'result');
            while ($record = gdrcd_query($result, 'fetch')) {
                $nome = $record['personaggio'];

                // Invia segnalazione direttamente nel ciclo
                // Stessa logica di invio usata sopra per i destinatari multipli
                $sql_verifica_conversazione = "
                    SELECT id_conversazione 
                    FROM sms 
                    WHERE mittente_nome = 'Segnalazione' 
                    AND destinatario_nome = '" . gdrcd_filter('in', $nome) . "' 
                    LIMIT 1
                ";
                $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

                if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
                    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
                    $id_conversazione = $row_conversazione['id_conversazione'];

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);
                } else {
                    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
                    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
                    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
                    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);

                    $sql_inserisci_conversazione = "
                        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                        VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $nome) . "', 0)
                    ";
                    gdrcd_query($sql_inserisci_conversazione);
                }
            }
        }
    }

    // 3. Gestione delle segnalazioni ai mestieri
    $mestieri = [
        'icc' => 1,
        'tae' => 2,
        'magic' => 3,
        'secret' => 4,
        'corte' => 6
    ];

    foreach ($mestieri as $key => $id_mestiere) {
        if ($_POST['multipli'] == $key) {
            $query = "
                SELECT clgpersonaggiomestiere.personaggio 
                FROM clgpersonaggiomestiere 
                JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo 
                WHERE ruolo_mestiere.mestiere = $id_mestiere
                AND clgpersonaggiomestiere.conferma_mestiere = 1
            ";
            $result = gdrcd_query($query, 'result');
            while ($record = gdrcd_query($result, 'fetch')) {
                $nome = $record['personaggio'];

                // Invia segnalazione direttamente nel ciclo
                // Stessa logica di invio usata sopra per i destinatari multipli
                $sql_verifica_conversazione = "
                    SELECT id_conversazione 
                    FROM sms 
                    WHERE mittente_nome = 'Segnalazione' 
                    AND destinatario_nome = '" . gdrcd_filter('in', $nome) . "' 
                    LIMIT 1
                ";
                $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

                if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
                    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
                    $id_conversazione = $row_conversazione['id_conversazione'];

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);
                } else {
                    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
                    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
                    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
                    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);

                    $sql_inserisci_conversazione = "
                        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                        VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $nome) . "', 0)
                    ";
                    gdrcd_query($sql_inserisci_conversazione);
                }
            }
        }
    }

    // 4. Gestione della segnalazione a tutti (broadcast)
    if ($_POST['multipli'] == 'broadcast') {
        // Trova l'ID del gruppo globale con mittente "System"
        $sql_globale = "SELECT id_conversazione FROM sms WHERE mittente_nome = 'System' AND is_globale = 1 LIMIT 1";
        $result_globale = gdrcd_query($sql_globale, 'result');
        $row_globale = gdrcd_query($result_globale, 'fetch');
        $id_conversazione_globale = $row_globale['id_conversazione'];

        // Inserisci il nuovo messaggio nella conversazione globale
        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame, is_globale, ora_spedizione)
            VALUES ('System', '$testo', $id_conversazione_globale, $id_conversazione_globale, 'gruppo', '0', 1, NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Aggiorna il campo lettura per tutti i partecipanti tranne il mittente
        $sql_aggiorna_lettura = "
            UPDATE partecipazione_gruppo 
            SET lettura = IF(utente_nome = '" . gdrcd_filter('in', $_SESSION['login']) . "', 1, 0)
            WHERE gruppo_id = $id_conversazione_globale
        ";
        gdrcd_query($sql_aggiorna_lettura);

        // Inserisci nuovi partecipanti nel gruppo globale se non sono già presenti
        $sql_nuovi_utenti = "
            SELECT nome 
            FROM personaggio 
            WHERE nome NOT IN (
                SELECT utente_nome 
                FROM partecipazione_gruppo 
                WHERE gruppo_id = $id_conversazione_globale
            )
        ";
        $result_nuovi_utenti = gdrcd_query($sql_nuovi_utenti, 'result');

        while ($row_nuovo_utente = gdrcd_query($result_nuovi_utenti, 'fetch')) {
            $nome_nuovo_utente = gdrcd_filter('in', $row_nuovo_utente['nome']);
            $sql_inserisci_nuovo_utente = "
                INSERT INTO partecipazione_gruppo (utente_nome, gruppo_id, lettura)
                VALUES ('$nome_nuovo_utente', $id_conversazione_globale, 0)
            ";
            gdrcd_query($sql_inserisci_nuovo_utente);
        }
    }

    // Se non è stata selezionata un'opzione multipla o un destinatario, gestisce la segnalazione singola
    if ($_POST['multipli'] == '---' || !isset($_POST['multipli'])) {
        $destinatari = isset($_POST['interessato']) ? explode(',', $_POST['interessato']) : [];

        foreach ($destinatari as $destinatario) {
            $destinatario = trim($destinatario);

            if (!empty($destinatario)) {
                $sql_verifica_conversazione = "
                    SELECT id_conversazione 
                    FROM sms 
                    WHERE mittente_nome = 'Segnalazione' 
                    AND destinatario_nome = '" . gdrcd_filter('in', $destinatario) . "' 
                    LIMIT 1
                ";
                $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

                if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
                    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
                    $id_conversazione = $row_conversazione['id_conversazione'];

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $destinatario) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);

                    $sql_aggiorna_lettura = "
                        UPDATE conversazioni_individuali 
                        SET lettura = 0 
                        WHERE id_conversazione = $id_conversazione 
                        AND utente_nome = '" . gdrcd_filter('in', $destinatario) . "'
                    ";
                    gdrcd_query($sql_aggiorna_lettura);
                } else {
                    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
                    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
                    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
                    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $destinatario) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);

                    $sql_inserisci_conversazione = "
                        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                        VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $destinatario) . "', 0)
                    ";
                    gdrcd_query($sql_inserisci_conversazione);
                }
            }
        }
    }

    // Messaggio di conferma della segnalazione
    $back = "forum&op=read&what=" . $_REQUEST['what'] . "&where=" . $_REQUEST['where'];
    echo '<div class="error">Messaggio segnalato!<br>';
    echo '<a href="main.php?page=' . $back . '" target="_top">Torna al post</a>';
    echo '</div>';
} // fine segnalaok
?>
