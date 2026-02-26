<?php
if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1 && $_SESSION['moderatore'] != 1) {
    echo '<div class="error">Non hai i permessi per accedere a questa pagina.</div>';
} else {
    if (isset($_POST['submit_request'])) {
        // Salva la richiesta nel database
        $user_name = gdrcd_filter('out', $_SESSION['login']);
        $chats = $_POST['chat_id'];
        $start_date = sprintf('%04d-%02d-%02d %02d:%02d:00',
            gdrcd_filter('num', $_POST['year_b']),
            gdrcd_filter('num', $_POST['month_b']),
            gdrcd_filter('num', $_POST['day_b']),
            gdrcd_filter('num', $_POST['hour_b']),
            gdrcd_filter('num', $_POST['minut_b'])
        );
        $end_date = sprintf('%04d-%02d-%02d %02d:%02d:00',
            gdrcd_filter('num', $_POST['year_e']),
            gdrcd_filter('num', $_POST['month_e']),
            gdrcd_filter('num', $_POST['day_e']),
            gdrcd_filter('num', $_POST['hour_e']),
            gdrcd_filter('num', $_POST['minut_e'])
        );

        foreach ($chats as $chat_id) {
            gdrcd_query("INSERT INTO autorizzazioni_log (user_name, chat_id, start_time, end_time, granted_by)
                         VALUES ('{$user_name}', {$chat_id}, '{$start_date}', '{$end_date}', 'PENDING')");
        }
        echo '<div class="success">La tua richiesta è stata inviata agli admin per approvazione.</div>';
        
        
        
        //invio segnalazione
        $segnalazione = "<a href='https://crystaltokyogdr.altervista.org/main.php?page=autorizza_log' target='_top'>Clicca qui</a>";
        $testo = gdrcd_filter_in("<b>$segnalazione" . " (<i>Un master ha appena richiesto autorizzazione per leggere i log chat</i>");

         // Recupera gli admin
         $admin_query = "SELECT nome FROM privilegi WHERE admin = 1";
         $admin_result = gdrcd_query($admin_query, 'result');

    while ($admin = gdrcd_query($admin_result, 'fetch')) {
        $nome = $admin['nome'];


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
                    
                    // Aggiorna il campo lettura per tutti i partecipanti tranne il mittente
            $sql_aggiorna_lettura = "
            UPDATE conversazioni_individuali 
            SET lettura = 0
            WHERE id_conversazione = $id_conversazione
        ";
        gdrcd_query($sql_aggiorna_lettura);
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

    // Mostra il form
    ?>
    <div class="page_title">
        <h2>Richiesta di Accesso ai Log</h2>
    </div>
    <div class="page_body">
        <form action="main.php?page=richiesta_log" method="post">
            <!-- Selezione delle chat -->
            <div class="form_label">Seleziona le chat</div>
            <div class="form_field">
                <?php
                $chats = gdrcd_query("SELECT id, nome FROM mappa WHERE chat = 1 AND privata = 0 ORDER BY nome", 'result');
                while ($chat = gdrcd_query($chats, 'fetch')) {
                    echo '<label>';
                    echo '<input type="checkbox" name="chat_id[]" value="' . $chat['id'] . '"> ';
                    echo gdrcd_filter('out', $chat['nome']);
                    echo '</label><br>';
                }
                gdrcd_query($chats, 'free');
                ?>
            </div>

            <!-- Selezione del periodo -->
            <div class="form_label">Periodo di autorizzazione</div>
            <div class="form_field">
                <div>Inizio:</div>
                <!-- Giorno -->
                <select name="day_b">
                    <?php for ($i = 1; $i <= 31; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select>
                <!-- Mese -->
                <select name="month_b">
                    <?php for ($i = 1; $i <= 12; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select>
                <!-- Anno -->
                <select name="year_b">
                    <?php for ($i = 2020; $i <= strftime('%Y') + 5; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select> -
                <!-- Ora -->
                <select name="hour_b">
                    <?php for ($i = 0; $i <= 23; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                    <?php } ?>
                </select>:
                <!-- Minuto -->
                <select name="minut_b">
                    <?php for ($i = 0; $i <= 59; $i += 5) { ?>
                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                    <?php } ?>
                </select>

                <div>Fine:</div>
                <!-- Giorno -->
                <select name="day_e">
                    <?php for ($i = 1; $i <= 31; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select>
                <!-- Mese -->
                <select name="month_e">
                    <?php for ($i = 1; $i <= 12; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select>
                <!-- Anno -->
                <select name="year_e">
                    <?php for ($i = 2020; $i <= strftime('%Y') + 5; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select> -
                <!-- Ora -->
                <select name="hour_e">
                    <?php for ($i = 0; $i <= 23; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                    <?php } ?>
                </select>:
                <!-- Minuto -->
                <select name="minut_e">
                    <?php for ($i = 0; $i <= 59; $i += 5) { ?>
                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                    <?php } ?>
                </select>
            </div>

            <!-- Invio della richiesta -->
            <div class="form_submit">
                <input type="submit" name="submit_request" value="Invia Richiesta">
            </div>
        </form>
    </div>
    <?php
}
?>
