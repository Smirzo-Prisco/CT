<?php

// Verifica se l'utente è un amministratore
if ($_SESSION['admin'] != 1) {
    echo "Accesso negato.";
    exit();
}

// Numero di messaggi per pagina
$messaggi_per_pagina = 20;

// Recupera la pagina corrente dalla query string (default: 1)
$pagina_corrente = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_corrente - 1) * $messaggi_per_pagina;

// Se è stata selezionata una conversazione, visualizza i messaggi
if (isset($_GET['conversazione_id'])) {
    $conversazione_id = (int)$_GET['conversazione_id'];

    // Query per contare il numero totale di messaggi nella conversazione
    $conteggio_messaggi_query = "
        SELECT COUNT(*) as totale
        FROM sms
        WHERE id_conversazione = $conversazione_id
    ";
    $conteggio_messaggi_result = gdrcd_query($conteggio_messaggi_query, 'result');
    $conteggio_messaggi_row = gdrcd_query($conteggio_messaggi_result, 'fetch');
    $totale_messaggi = $conteggio_messaggi_row['totale'];
    $totale_pagine = ceil($totale_messaggi / $messaggi_per_pagina);

    // Query per recuperare i messaggi della conversazione con paginazione
    $messaggi_query = "
        SELECT mittente_nome, testo, ora_spedizione, ongame
        FROM sms
        WHERE id_conversazione = $conversazione_id
        ORDER BY ora_spedizione DESC
        LIMIT $offset, $messaggi_per_pagina
    ";
    $messaggi_result = gdrcd_query($messaggi_query, 'result');

    echo '<div class="message-list">';
echo "<h3>Dettagli della Conversazione</h3><ul>";

while ($messaggio = gdrcd_query($messaggi_result, 'fetch')) {
    $mittente = htmlspecialchars($messaggio['mittente_nome']);
    $testo = nl2br(htmlspecialchars($messaggio['testo']));
    $ora_spedizione = $messaggio['ongame'] == 1 
        ? date('d-m-', strtotime($messaggio['ora_spedizione'])) . '3077 ' . date('H:i', strtotime($messaggio['ora_spedizione']))
        : date('d-m-Y H:i', strtotime($messaggio['ora_spedizione']));

    echo "<li><strong style='color: #5cb85c;'>{$mittente}</strong> ({$ora_spedizione}): <p style='margin-top: 5px; padding: 10px; border-radius: 5px;'>{$testo}</p></li>";
}
echo "</ul></div>";

// Paginazione
echo "<div class='pagination'>";
if ($pagina_corrente > 1) {
    echo "<a href='main.php?page=log_msg&conversazione_id=$conversazione_id&pagina=" . ($pagina_corrente - 1) . "'>Pagina Precedente</a>";
}
if ($pagina_corrente < $totale_pagine) {
    echo "<a href='main.php?page=log_msg&conversazione_id=$conversazione_id&pagina=" . ($pagina_corrente + 1) . "'>Pagina Successiva</a>";
}
echo "</div>";

    // Libera i risultati delle query
    gdrcd_query($messaggi_result, 'free');
    gdrcd_query($conteggio_messaggi_result, 'free');

} else {
    // Codice per visualizzare la selezione del personaggio e delle conversazioni
    // Recupera tutti i personaggi
    $personaggi_query = "SELECT nome FROM personaggio ORDER BY nome ASC";
    $personaggi_result = gdrcd_query($personaggi_query, 'result');
    ?>

    
    
    
        


        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
            }

            h2, h3 {
           r: #444;
                text-align: center;
            }

            form {
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }

            form label {
                display: block;
                margin-bottom: 8px;
                font-weight: bold;
            }

            form select {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            form button {
                width: 100%;
                padding: 10px;
                background-color: #5cb85c;
                border: none;
                color: #fff;
                font-size: 16px;
                border-radius: 4px;
                cursor: pointer;
            }

            form button:hover {
                background-color: #4cae4c;
            }

            .conversation-list, .message-list {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }

            .conversation-list ul, .message-list ul {
                list-style: none;
                padding: 0;
            }

            .conversation-list li, .message-list li {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }

            .conversation-list li a, .message-list li p {
                text-decoration: none;
            }

            .conversation-list li a:hover {
                color: #5cb85c;
            }

            .message-list li {
                margin-bottom: 15px;
            }

            .message-list li strong {
                display: block;
            }

            .pagination {
                text-align: center;
                margin-top: 20px;
            }

            .pagination a {
                margin: 0 5px;
                padding: 10px 15px;
                text-decoration: none;
                border-radius: 4px;
            }

            .pagination a:hover {
                background-color: #4cae4c;
            }
        </style>
    
    
        <h2>Seleziona un Personaggio per visualizzare le conversazioni</h2>

        <!-- Form per selezionare un personaggio -->
        <form action="main.php?page=log_msg" method="post">
            <label for="personaggio">Seleziona Personaggio:</label>
            <select name="personaggio" id="personaggio">
                <option value="">-- Seleziona un Personaggio --</option>
                <?php while ($personaggio = gdrcd_query($personaggi_result, 'fetch')) { ?>
                    <option value="<?php echo htmlspecialchars($personaggio['nome']); ?>">
                        <?php echo htmlspecialchars($personaggio['nome']); ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit">Visualizza Conversazioni</button>
        </form>

        <?php
        // Se è stato selezionato un personaggio, visualizza l'elenco delle conversazioni
        if (isset($_POST['personaggio']) && !empty($_POST['personaggio'])) {
            $personaggio_selezionato = gdrcd_filter('in', $_POST['personaggio']);

            // Query per recuperare le conversazioni ON
            $conversazioni_on_query = "
                SELECT id_conversazione, destinatario_nome, mittente_nome, MAX(ora_spedizione) AS ultima_ora
                FROM sms
                WHERE 
                    (mittente_nome = '$personaggio_selezionato' OR destinatario_nome = '$personaggio_selezionato')
                    AND ongame = 1
                    AND mittente_nome NOT IN ('Segnalazione', 'Calendario', 'System')
                    AND destinatario_nome NOT IN ('Segnalazione', 'Calendario', 'System')
                GROUP BY id_conversazione
                ORDER BY ultima_ora DESC
            ";
            $conversazioni_on_result = gdrcd_query($conversazioni_on_query, 'result');

            // Query per recuperare le conversazioni OFF
            $conversazioni_off_query = "
                SELECT id_conversazione, destinatario_nome, mittente_nome, MAX(ora_spedizione) AS ultima_ora
                FROM sms
                WHERE 
                    (mittente_nome = '$personaggio_selezionato' OR destinatario_nome = '$personaggio_selezionato')
                    AND ongame = 0
                    AND mittente_nome NOT IN ('Segnalazione', 'Calendario', 'System', 'Lii')
                    AND destinatario_nome NOT IN ('Segnalazione', 'Calendario', 'System', 'Lii')
                GROUP BY id_conversazione
                ORDER BY ultima_ora DESC
            ";
            $conversazioni_off_result = gdrcd_query($conversazioni_off_query, 'result');

            echo '<div class="conversation-list">';
            // Mostra le conversazioni ON
            if (gdrcd_query($conversazioni_on_result, 'num_rows') > 0) {
                echo "<h3>Conversazioni ON</h3><ul>";
                while ($conversazione = gdrcd_query($conversazioni_on_result, 'fetch')) {
                    $altro_utente = ($conversazione['mittente_nome'] === $personaggio_selezionato) ? $conversazione['destinatario_nome'] : $conversazione['mittente_nome'];
                    echo "<li><a href='main.php?page=log_msg&conversazione_id=" . $conversazione['id_conversazione'] . "'>Conversazione con " . htmlspecialchars($altro_utente) . " (Ultima risposta: " . $conversazione['ultima_ora'] . ")</a></li>";
                }
                echo "</ul>";
            }

            // Mostra le conversazioni OFF
            if (gdrcd_query($conversazioni_off_result, 'num_rows') > 0) {
                echo "<h3>Conversazioni OFF</h3><ul>";
                while ($conversazione = gdrcd_query($conversazioni_off_result, 'fetch')) {
                    $altro_utente = ($conversazione['mittente_nome'] === $personaggio_selezionato) ? $conversazione['destinatario_nome'] : $conversazione['mittente_nome'];
                    echo "<li><a href='main.php?page=log_msg&conversazione_id=" . $conversazione['id_conversazione'] . "'>Conversazione con " . htmlspecialchars($altro_utente) . " (Ultima risposta: " . $conversazione['ultima_ora'] . ")</a></li>";
                }
                echo "</ul>";
            }
            echo '</div>';

            // Libera i risultati delle query
            gdrcd_query($conversazioni_on_result, 'free');
            gdrcd_query($conversazioni_off_result, 'free');
        }
        ?>

    
    

    <?php
    // Libera i risultati delle query
    gdrcd_query($personaggi_result, 'free');
}
?>
