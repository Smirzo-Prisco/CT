<?php
if ($_SESSION['admin'] != 1) {
    echo '<div class="error">Non hai i permessi per accedere a questa pagina.</div>';
} else {
    if (isset($_POST['action'])) {
        $request_id = gdrcd_filter('num', $_POST['request_id']);
        $action = gdrcd_filter('out', $_POST['action']);
        $admin_name = gdrcd_filter('out', $_SESSION['login']);

        if ($action === 'approve') {
        
            // Recupera il nome di chi ha avanzato la richiesta
            $request_details = gdrcd_query("SELECT user_name FROM autorizzazioni_log WHERE id = {$request_id}", 'result');
            $request_user = gdrcd_query($request_details, 'fetch')['user_name'];
    
            // Approva la richiesta
            gdrcd_query("UPDATE autorizzazioni_log
                         SET granted_by = '{$admin_name}'
                         WHERE id = {$request_id}");
            echo '<div class="success">Richiesta approvata con successo.</div>';
            
            // Invio segnalazione
    $testo = gdrcd_filter('in', "La tua richiesta di leggere i log è stata accettata.");

    // Verifica se esiste già una conversazione con l'utente
    $sql_verifica_conversazione = "
        SELECT id_conversazione 
        FROM sms 
        WHERE mittente_nome = 'Segnalazione' 
          AND destinatario_nome = '" . gdrcd_filter('in', $request_user) . "' 
        LIMIT 1
    ";
    $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

    if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
        // Esiste già una conversazione, aggiungi il messaggio
        $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
        $id_conversazione = $row_conversazione['id_conversazione'];

        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('Segnalazione', '" . gdrcd_filter('in', $request_user) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Aggiorna il campo lettura
        $sql_aggiorna_lettura = "
            UPDATE conversazioni_individuali 
            SET lettura = 0
            WHERE id_conversazione = $id_conversazione
        ";
        gdrcd_query($sql_aggiorna_lettura);
    } else {
        // Non esiste conversazione, creane una nuova
        $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
        $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
        $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
        $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('Segnalazione', '" . gdrcd_filter('in', $request_user) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        $sql_inserisci_conversazione = "
            INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
            VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $request_user) . "', 0)
        ";
        gdrcd_query($sql_inserisci_conversazione);
    }
    
        } elseif ($action === 'deny') {
        
            // Recupera il nome di chi ha avanzato la richiesta
            $request_details = gdrcd_query("SELECT user_name FROM autorizzazioni_log WHERE id = {$request_id}", 'result');
            $request_user = gdrcd_query($request_details, 'fetch')['user_name'];
            
            // Nega la richiesta
            gdrcd_query("DELETE FROM autorizzazioni_log
                         WHERE id = {$request_id}");
            echo '<div class="success">Richiesta negata con successo.</div>';
            
            
            // Invio segnalazione
    $testo = gdrcd_filter('in', "La tua richiesta di leggere i log non è stata approvata.");

    // Verifica se esiste già una conversazione con l'utente
    $sql_verifica_conversazione = "
        SELECT id_conversazione 
        FROM sms 
        WHERE mittente_nome = 'Segnalazione' 
          AND destinatario_nome = '" . gdrcd_filter('in', $request_user) . "' 
        LIMIT 1
    ";
    $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

    if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
        // Esiste già una conversazione, aggiungi il messaggio
        $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
        $id_conversazione = $row_conversazione['id_conversazione'];

        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('Segnalazione', '" . gdrcd_filter('in', $request_user) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        // Aggiorna il campo lettura
        $sql_aggiorna_lettura = "
            UPDATE conversazioni_individuali 
            SET lettura = 0
            WHERE id_conversazione = $id_conversazione
        ";
        gdrcd_query($sql_aggiorna_lettura);
    } else {
        // Non esiste conversazione, creane una nuova
        $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
        $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
        $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
        $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

        $sql_inserisci_messaggio = "
            INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
            VALUES ('Segnalazione', '" . gdrcd_filter('in', $request_user) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
        ";
        gdrcd_query($sql_inserisci_messaggio);

        $sql_inserisci_conversazione = "
            INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
            VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $request_user) . "', 0)
        ";
        gdrcd_query($sql_inserisci_conversazione);
    }
        }
    }

    // Recupera richieste pendenti
    $requests = gdrcd_query("SELECT al.id, al.user_name, m.nome AS chat_name, al.start_time, al.end_time
                             FROM autorizzazioni_log al
                             JOIN mappa m ON al.chat_id = m.id
                             WHERE al.granted_by = 'PENDING'
                             ORDER BY al.start_time ASC", 'result');

    ?>
    <div class="page_title">
        <h2>Gestione Richieste di Accesso ai Log</h2>
    </div>
    <div class="page_body">
        <style>
            .log_requests_table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-family: 'Lato', sans-serif;
            }

            .log_requests_table th, .log_requests_table td {
                border: 1px solid #444;
                padding: 10px;
                text-align: center;
                color: #ce846f;
            }

            .log_requests_table th {
                background-color: #14172a;
                text-transform: uppercase;
                font-size: 14px;
            }

            .log_requests_table tr:nth-child(even) {
                background-color: rgba(54, 66, 115, 0.2);
            }

            .log_requests_table tr:nth-child(odd) {
                background-color: rgba(54, 66, 115, 0.1);
            }

            .approve_button, .deny_button {
                padding: 8px 15px;
                font-size: 14px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-family: 'Lato', sans-serif;
            }

            .approve_button {
                background-color: #4caf50;
                color: white;
                margin-right: 5px;
            }

            .deny_button {
                background-color: #f44336;
                color: white;
            }

            .success {
                color: #4caf50;
                font-size: 14px;
                margin-bottom: 20px;
            }

            .error {
                color: #f44336;
                font-size: 14px;
                margin-bottom: 20px;
            }
        </style>

        <?php if (gdrcd_query($requests, 'num_rows') > 0) { ?>
            <table class="log_requests_table">
                <thead>
                <tr>
                    <th>Utente</th>
                    <th>Chat</th>
                    <th>Inizio</th>
                    <th>Fine</th>
                    <th>Azioni</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($request = gdrcd_query($requests, 'fetch')) { ?>
                    <tr>
                        <td><?php echo gdrcd_filter('out', $request['user_name']); ?></td>
                        <td><?php echo gdrcd_filter('out', $request['chat_name']); ?></td>
                        <td><?php echo gdrcd_filter('out', $request['start_time']); ?></td>
                        <td><?php echo gdrcd_filter('out', $request['end_time']); ?></td>
                        <td>
                            <form action="main.php?page=autorizza_log" method="post" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="approve_button">Approva</button>
                            </form>
                            <form action="main.php?page=autorizza_log" method="post" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="action" value="deny">
                                <button type="submit" class="deny_button">Nega</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="error">Non ci sono richieste pendenti.</div>
        <?php } ?>
        <?php gdrcd_query($requests, 'free'); ?>
    </div>
    <?php
}
?>
