<?php
session_start();
/* Includo i file necessari */
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');

// Recupera l'ID della conversazione o del gruppo dalla query string
$conversazione_id = isset($_GET['conversazione_id']) ? intval($_GET['conversazione_id']) : 0;
$gruppo_id = isset($_GET['gruppo_id']) ? intval($_GET['gruppo_id']) : 0;
$is_archivio = isset($_GET['conversazione_id']) && $_GET['conversazione_id'] === 'archive';


$titolo_conversazione = '';
$partecipanti_gruppo = '';
$messaggi_html = '';



// Recupera i parametri 'personaggio' e 'ongame' dall'URL
$personaggio_scelto = isset($_GET['personaggio']) ? $_GET['personaggio'] : '';
$categoria_scelta = isset($_GET['ongame']) ? $_GET['ongame'] : '';

// Salva i dati nella sessione per usarli nel form di invio del messaggio
$_SESSION['selected_personaggio'] = $personaggio_scelto;
$_SESSION['selected_categoria'] = $categoria_scelta;

$_SESSION['id_conversazione'] = $conversazione_id;
$_SESSION['gruppo_id'] = $gruppo_id;
function update_read_status($conversazione_id, $gruppo_id = null) {
    $login = $_SESSION['login']; // Nome dell'utente corrente

    if ($gruppo_id === null) {
        // Conversazione individuale
        $sql = "UPDATE conversazioni_individuali 
        SET lettura = 1
        WHERE id_conversazione = $conversazione_id
        AND utente_nome = '$login'";
    } else {
        // Conversazione di gruppo
        $sql = "UPDATE partecipazione_gruppo 
                SET lettura = 1
                WHERE gruppo_id = $gruppo_id AND utente_nome = '$login'";
    }

    // Esegui l'UPDATE
    gdrcd_query($sql);
}

// Esempio di utilizzo per una conversazione individuale
if (isset($_GET['conversazione_id'])) {
    $conversazione_id = intval($_GET['conversazione_id']);
    update_read_status($conversazione_id);
}

// Esempio di utilizzo per una conversazione di gruppo
if (isset($_GET['gruppo_id'])) {
    $gruppo_id = intval($_GET['gruppo_id']);
    update_read_status(null, $gruppo_id);
}



if ($is_archivio) {
    $utente_corrente = $_SESSION['login'];

    // Esegui la query per recuperare i messaggi dell'archivio (messaggi inviati dall'utente a sé stesso)
    $sql = "SELECT s.mittente_nome, s.destinatario_nome, s.testo, s.ora_spedizione, s.ongame
            FROM sms s
            WHERE s.mittente_nome = '$utente_corrente' 
              AND s.destinatario_nome = '$utente_corrente'
            ORDER BY s.ora_spedizione DESC";
    $result = gdrcd_query($sql, 'result');

    // Verifica che la query abbia restituito risultati
    if (gdrcd_query($result, 'num_rows') > 0) {
        $titolo_conversazione = "Archivio personale";

        while ($row = gdrcd_query($result, 'fetch')) {
            $testo_messaggio = htmlspecialchars($row['testo']);
            
            if ($row['ongame'] == 1) { // Verifica se il messaggio è ON
                $ora_spedizione = date('d-m-', strtotime($row['ora_spedizione'])) . '3077 ' . date('H:i', strtotime($row['ora_spedizione']));
            } else { // Se il messaggio è OFF
                $ora_spedizione = date('d-m-Y H:i', strtotime($row['ora_spedizione']));
            }

            // Visualizzazione dei messaggi nell'archivio
            $messaggi_html .= "
            <div class='message sent'>
                <div class='message-sender'>{$row['mittente_nome']}</div>
                <p>{$testo_messaggio}</p>
                <span class='time'>{$ora_spedizione}</span>
            </div>";
        }
    } else {
        $titolo_conversazione = "Nessun messaggio nell'archivio.";
    }
} 
elseif ($conversazione_id > 0) {
    // Esegui la query per recuperare i dettagli della conversazione individuale e i messaggi
    $sql = "SELECT s.mittente_nome, s.destinatario_nome, s.titolo_gruppo, s.tipo_messaggio, s.testo, s.ongame, s.ora_spedizione, s.gruppo_id, s.is_globale
            FROM sms s
            WHERE s.id_conversazione = $conversazione_id
            ORDER BY s.ora_spedizione DESC";
    $result = gdrcd_query($sql, 'result');

    // Verifica che la query abbia restituito risultati
    if (gdrcd_query($result, 'num_rows') > 0) {
        $utente_corrente = $_SESSION['login'];

        // Ciclo attraverso tutti i messaggi e determina i dettagli della conversazione
        while ($row = gdrcd_query($result, 'fetch')) {
        
        
        // Se l'utente corrente è il mittente, il destinatario sarà l'altra persona
        $destinatario = ($_SESSION['login'] === $row['mittente_nome']) ? $row['destinatario_nome'] : $row['mittente_nome'];
        $ongame_offgame = $row['ongame'];  // 1 per ongame, 0 per offgame
        $is_globale = $row['is_globale'];  // Recupera il valore di is_globale

        // Salva i dati nella sessione per usarli nel form
        $_SESSION['selected_personaggio'] = $destinatario;
        $_SESSION['selected_categoria'] = $ongame_offgame;
        $_SESSION['is_globale'] = $is_globale;  // Salva is_globale nella sessione
        
            // Solo per il primo ciclo, imposta i dettagli della conversazione
            if ($titolo_conversazione == '') {
                if ($row['tipo_messaggio'] == 'individuale') {
                    $utente1 = htmlspecialchars($row['mittente_nome']);
                    $utente2 = htmlspecialchars($row['destinatario_nome']);
                    $titolo_conversazione = "Conversazione tra: $utente1 e $utente2";
                } else {
                    $titolo_conversazione = htmlspecialchars($row['titolo_gruppo']);

                    // Recupera i partecipanti del gruppo
                    $sql_partecipanti = "SELECT pg.utente_nome
                                         FROM partecipazione_gruppo pg
                                         WHERE pg.gruppo_id = " . intval($row['gruppo_id']);
                    $result_partecipanti = gdrcd_query($sql_partecipanti, 'result');

                    $partecipanti = [];
                    while ($row_partecipanti = gdrcd_query($result_partecipanti, 'fetch')) {
                        $partecipanti[] = htmlspecialchars($row_partecipanti['utente_nome']);
                    }
                    $partecipanti_gruppo = "<small>Partecipanti: " . implode(", ", $partecipanti) . "</small>";
                }
            }

            // Visualizzazione dei messaggi
            $mittente = htmlspecialchars($row['mittente_nome']);
            $testo_messaggio = in_array($mittente, ['Segnalazione', 'Calendario']) 
    ? htmlspecialchars_decode($row['testo']) 
    : nl2br(htmlspecialchars($row['testo']));
    
            if ($row['ongame'] == 1) { // Verifica se il messaggio è ON
                $ora_spedizione = date('d-m-', strtotime($row['ora_spedizione'])) . '3077 ' . date('H:i', strtotime($row['ora_spedizione']));
            } else { // Se il messaggio è OFF
                $ora_spedizione = date('d-m-Y H:i', strtotime($row['ora_spedizione']));
            }

            if ($mittente === 'Segnalazione' || $mittente === 'Calendario') {
        // Stile personalizzato per i messaggi di System
        $messaggi_html .= "
        <div class='message system'>
            $testo_messaggio
            </div>";
          } elseif ($mittente === $utente_corrente) {
            
            // Verifica se il destinatario ha letto il messaggio
            $sql_lettura = "SELECT lettura FROM conversazioni_individuali WHERE id_conversazione = $conversazione_id AND utente_nome = '$destinatario'";
            $result_lettura = gdrcd_query($sql_lettura, 'result');
            $row_lettura = gdrcd_query($result_lettura, 'fetch');
            $messaggio_letto = ($row_lettura && $row_lettura['lettura'] == 1); // Verifica se il messaggio è stato letto

            // Aggiungi le doppie spunte se il messaggio è stato letto
            $spunte = $messaggio_letto ? "&check;" : "";
    
    
                $messaggi_html .= "
                <div class='message sent'>
                    <div class='message-sender'>{$mittente}</div>
                    <p>{$testo_messaggio}</p>
                    <span class='time'>{$ora_spedizione} <span class='tick-icon'>{$spunte}</span></span>
                </div>";
            } else {
                $messaggi_html .= "
                <div class='message received'>
                    <div class='message-sender'>{$mittente}</div>
                    <p>{$testo_messaggio}</p>
                    <span class='time'>{$ora_spedizione}</span>
                </div>";
            }
        }
    } else {
        $titolo_conversazione = "Conversazione non trovata.";
    }
} elseif ($gruppo_id > 0) {
    // Esegui la query per recuperare i dettagli della conversazione di gruppo e i messaggi
    $sql = "SELECT s.mittente_nome, s.destinatario_nome, s.titolo_gruppo, s.tipo_messaggio, s.testo, s.ongame, s.ora_spedizione, s.gruppo_id, s.is_globale
            FROM sms s
            WHERE s.id_conversazione = $gruppo_id
            ORDER BY s.ora_spedizione DESC";
    $result = gdrcd_query($sql, 'result');

    // Verifica che la query abbia restituito risultati
    if (gdrcd_query($result, 'num_rows') > 0) {
        $utente_corrente = $_SESSION['login'];

        

        // Ciclo attraverso tutti i messaggi e li visualizza
        while ($row = gdrcd_query($result, 'fetch')) {
        
        
        
        $destinatario = $row['titolo_gruppo'];  // Nome del gruppo
        $ongame_offgame = $row['ongame'];  // 1 per ongame, 0 per offgame
        $is_globale = $row['is_globale'];  // Recupera il valore di is_globale

        // Salva i dati nella sessione per usarli nel form
        $_SESSION['selected_personaggio'] = $destinatario;
        $_SESSION['selected_categoria'] = $ongame_offgame;
        $_SESSION['is_globale'] = $is_globale; 
        
// Solo per il primo ciclo, imposta i dettagli della conversazione
            if ($titolo_conversazione == '') {
                if ($row['tipo_messaggio'] == 'individuale') {
                    $utente1 = htmlspecialchars($row['mittente_nome']);
                    $utente2 = htmlspecialchars($row['destinatario_nome']);
                    $titolo_conversazione = "Conversazione tra: $utente1 e $utente2";
                } else {
                    $titolo_conversazione = htmlspecialchars($row['titolo_gruppo']);
                    
                    // Verifica se il messaggio è globale
                    $is_globale = (isset($row['is_globale']) && $row['is_globale'] == 1);
                    
                    if ($is_globale) {
                        // Se il messaggio è globale, imposta "Tutti gli utenti" come partecipanti
                        $partecipanti_gruppo = "<em>Partecipanti: Tutti gli utenti</em>";
                        $titolo_conversazione = "Messaggio Globale"; // Imposta un titolo specifico per il messaggio globale
                    } else {
                    
                    // Recupera i partecipanti del gruppo
                    $sql_partecipanti = "SELECT pg.utente_nome
                                         FROM partecipazione_gruppo pg
                                         WHERE pg.gruppo_id = " . intval($row['gruppo_id']);
                    $result_partecipanti = gdrcd_query($sql_partecipanti, 'result');

                    $partecipanti = [];
                    while ($row_partecipanti = gdrcd_query($result_partecipanti, 'fetch')) {
                        $partecipanti[] = htmlspecialchars($row_partecipanti['utente_nome']);
                    }
                    $partecipanti_gruppo = "<em>Partecipanti: " . implode(", ", $partecipanti) . "</em>";
                }
               }  
            }
            
            $mittente = htmlspecialchars($row['mittente_nome']);
            $testo_messaggio = $mittente === 'System' 
    ? htmlspecialchars_decode($row['testo']) 
    : nl2br(htmlspecialchars($row['testo']));
    
            if ($row['ongame'] == 1) { // Verifica se il messaggio è ON
                $ora_spedizione = date('d-m-', strtotime($row['ora_spedizione'])) . '3077 ' . date('H:i', strtotime($row['ora_spedizione']));
            } else { // Se il messaggio è OFF
                $ora_spedizione = date('d-m-Y H:i', strtotime($row['ora_spedizione']));
            }
            
            // Query per verificare chi ha letto il messaggio
        $sql_lettura = "SELECT utente_nome, lettura FROM partecipazione_gruppo WHERE gruppo_id = $gruppo_id AND utente_nome != '$mittente'";
        $result_lettura = gdrcd_query($sql_lettura, 'result');

        $partecipanti_letto = [];
        $tutti_hanno_letto = true;
        $nessuno_ha_letto = true;

        while ($row_lettura = gdrcd_query($result_lettura, 'fetch')) {
            if ($row_lettura['lettura'] == 1) {
                $partecipanti_letto[] = $row_lettura['utente_nome'];
                $nessuno_ha_letto = false;
            } else {
                $tutti_hanno_letto = false;
            }
        }

        // Aggiungi le spunte e il tooltip in base allo stato di lettura
        if ($tutti_hanno_letto) {
            $spunte = "<span class='tick-icon'>&check;</span>";
        } elseif (!$nessuno_ha_letto) {
            $spunte = "<span class='tick-icon' style='color: gray;' title='Letto da: " . implode(", ", $partecipanti_letto) . "'>&check;</span>";
        } else {
            $spunte = "<span class='tick-icon' title='Letto da: " . implode(", ", $partecipanti_letto) . "'>&check;</span>";
        }
        
        

            if ($mittente === 'System') {
        // Stile personalizzato per i messaggi di System
        $messaggi_html .= "
        <div class='message system'>
            $testo_messaggio
            </div>";
          } elseif ($mittente === $utente_corrente) {
                $messaggi_html .= "
                <div class='message sent'>
                    <div class='message-sender'>{$mittente}</div>
                    <p>{$testo_messaggio}</p>
                    <span class='time'>{$ora_spedizione} {$spunte}</span>
                </div>";
            } else {
                $messaggi_html .= "
                <div class='message received'>
                    <div class='message-sender'>{$mittente}</div>
                    <p>{$testo_messaggio}</p>
                    <span class='time'>{$ora_spedizione}</span>
                </div>";
            }
        }
    } else {
        $titolo_conversazione = "Conversazione non trovata.";
    }
} else {
    // Gestione degli altri casi (come hai già implementato)
    $login = $_SESSION['login']; // Recupera il nome utente dalla sessione
    $personaggio = isset($_GET['personaggio']) ? gdrcd_filter('in', $_GET['personaggio']) : '';
    $ongame = isset($_GET['ongame']) ? intval($_GET['ongame']) : -1;

    if ($personaggio !== '' && $ongame !== -1) {
        // Query per cercare la conversazione
        $sql = "
            SELECT * FROM sms 
            WHERE 
            (
                (mittente_nome = '$login' AND destinatario_nome = '$personaggio') 
                OR (mittente_nome = '$personaggio' AND destinatario_nome = '$login')
            )
            AND ongame = $ongame
            ORDER BY ora_spedizione DESC
        ";
        $result = gdrcd_query($sql, 'result');

        // Verifica che la query abbia restituito risultati
        if (gdrcd_query($result, 'num_rows') > 0) {
            $utente_corrente = $_SESSION['login'];
            
            

            // Ciclo attraverso tutti i messaggi e determina i dettagli della conversazione
            while ($row = gdrcd_query($result, 'fetch')) {
            
            // Salva l'ID della conversazione nella sessione
             $_SESSION['id_conversazione'] = $row['id_conversazione'];
        
                // Solo per il primo ciclo, imposta i dettagli della conversazione
                if ($titolo_conversazione == '') {
                    if ($row['tipo_messaggio'] == 'individuale') {
                        $utente1 = htmlspecialchars($row['mittente_nome']);
                        $utente2 = htmlspecialchars($row['destinatario_nome']);
                        $titolo_conversazione = "Conversazione tra: $utente1 e $utente2";
                    } else {
                        $titolo_conversazione = htmlspecialchars($row['titolo_gruppo']);

                        // Recupera i partecipanti del gruppo
                        $sql_partecipanti = "SELECT pg.utente_nome
                                             FROM partecipazione_gruppo pg
                                             WHERE pg.gruppo_id = " . intval($row['gruppo_id']);
                        $result_partecipanti = gdrcd_query($sql_partecipanti, 'result');

                        $partecipanti = [];
                        while ($row_partecipanti = gdrcd_query($result_partecipanti, 'fetch')) {
                            $partecipanti[] = htmlspecialchars($row_partecipanti['utente_nome']);
                        }
                        $partecipanti_gruppo = "<small>Partecipanti: " . implode(", ", $partecipanti) . "</small>";
                    }
                }

                // Visualizzazione dei messaggi
                $mittente = htmlspecialchars($row['mittente_nome']);
                $testo_messaggio = nl2br(htmlspecialchars($row['testo']));
                
                if ($row['ongame'] == 1) { // Verifica se il messaggio è ON
                $ora_spedizione = date('d-m-', strtotime($row['ora_spedizione'])) . '3077 ' . date('H:i', strtotime($row['ora_spedizione']));
            } else { // Se il messaggio è OFF
                $ora_spedizione = date('d-m-Y H:i', strtotime($row['ora_spedizione']));
            }

                if ($mittente === $utente_corrente) {
                    // Verifica se il destinatario ha letto il messaggio
    $sql_lettura = "SELECT lettura FROM conversazioni_individuali WHERE id_conversazione = ".$row['id_conversazione']." AND utente_nome = '".$row['destinatario_nome']."'";
    $result_lettura = gdrcd_query($sql_lettura, 'result');
    $row_lettura = gdrcd_query($result_lettura, 'fetch');
    $messaggio_letto = ($row_lettura && $row_lettura['lettura'] == 1); // Verifica se il messaggio è stato letto

    // Aggiungi le doppie spunte se il messaggio è stato letto
    $spunte = $messaggio_letto ? "&check;" : "";
                
                    $messaggi_html .= "
                    <div class='message sent'>
                        <div class='message-sender'>{$mittente}</div>
                        <p>{$testo_messaggio}</p>
                        <span class='time'>{$ora_spedizione} <span class='tick-icon'>{$spunte}</span></span>
                    </div>";
                } else {
                    $messaggi_html .= "
                    <div class='message received'>
                        <div class='message-sender'>{$mittente}</div>
                        <p>{$testo_messaggio}</p>
                        <span class='time'>{$ora_spedizione}</span>
                    </div>";
                }
            }
        } else {
            // Nessuna conversazione trovata
            $titolo_conversazione = "Inizia la conversazione";
        }
    } else {
        $titolo_conversazione = "Parametri mancanti o non validi.";
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging Content</title>
    <link rel="stylesheet" href="new_sms.css">
</head>
<body>

<div class="message-container">

    <div class="main-header">
    
    
    <div class="header-title-container">
        <div class="header-title">
            <span class="pagination-arrow" id="prev-page" onclick="goToPreviousPage()"><<</span>
            <?= $titolo_conversazione ?>
            <span class="pagination-arrow" id="next-page" onclick="goToNextPage()">>></span>
        </div>
    </div>
    <div clss="partecipanti-container">
        <div class="partecipanti-gruppo">
            <small> <?= $partecipanti_gruppo ?></small>
        </div>
    </div>
</div>
    <div class="messages">
        <?php
        if (!empty($messaggi_html)) {
            echo $messaggi_html;
        } else {
            echo "<p class='no-messages'>Inizia la conversazione</p>";
        }
        ?>
    </div>
     
    <div class="message-input-container">
    <?php //if (!$is_globale || ($_SESSION['admin'] && $is_globale)) {
    if ((!$is_globale || ($_SESSION['admin'] && $is_globale)) && !in_array($_SESSION['selected_personaggio'], ['Segnalazione', 'Calendario', 'Moderazione'])) { 
    ?>
    
        <form id="send-message-form" action="send_sms.php" method="post">
    <!-- Campi nascosti per passare i dati del destinatario e la tipologia ongame-offgame -->
    <input type="hidden" name="destinatario" id="destinatario-input" value="<?php echo isset($_SESSION['selected_personaggio']) ? $_SESSION['selected_personaggio'] : ''; ?>">
    <input type="hidden" name="ongame_offgame" id="ongame-offgame-input" value="<?php echo isset($_SESSION['selected_categoria']) ? $_SESSION['selected_categoria'] : ''; ?>">
    <input type="hidden" name="id_conversazione" value="<?php echo isset($_SESSION['id_conversazione']) ? $_SESSION['id_conversazione'] : ''; ?>">
    <input type="hidden" name="gruppo_id" value="<?php echo isset($_SESSION['gruppo_id']) ? $_SESSION['gruppo_id'] : ''; ?>">
    <input type="hidden" name="is_globale" value="<?php echo isset($_SESSION['is_globale']) ? $_SESSION['is_globale'] : ''; ?>">
    <?php if ($is_archivio) { ?>
        <input type="hidden" name="is_archivio" value="1">
    <?php } ?>
    
    <!-- Campo per scrivere il messaggio -->
        <textarea name="messaggio" class="message-input" placeholder="Scrivi un messaggio..." rows="3"></textarea>

    <!-- Pulsante di invio -->
    <button type="submit" class="send-button">
        <img src="../../themes/crystal/imgs/sms/Invio_messaggio.png" alt="Invia Messaggio">
    </button>
        
            <?php if ($gruppo_id > 0 && $is_globale == 0): ?>
            <!-- Pulsante "Gestisci Gruppo" solo per i gruppi -->
            <button id="manage-group-button_gruppo" class="send-button">
                <img src="../../themes/crystal/imgs/sms/modifica_gruppo.png" alt="Gestisci Gruppo">
            </button>
        <?php endif; ?>     
        </form>
        
        
        
        <?php if ($is_globale == 0 && $mittente !== 'Segnalazione'): ?>
    <!-- Pulsante "Download" -->
    <button id="download-messages-button" class="send-button" onclick="openDownloadPopup()">
        <img src="../../themes/crystal/imgs/sms/download.png" alt="Download Conversazione">
    </button>
<?php endif; ?>


        <?php } else { ?>
        <?php if ($is_globale) { ?>
       <p><em>Solo i coordinatori possono scrivere.</em></p>
    <?php } ?>
<?php } ?>
    </div>
</div>
    
    
    
    
    
    








<!-- Popup per selezionare il range di date -->
<div id="download-popup" style="display:none;">
    <form action="download_conversation.php" method="get">
    <input type="hidden" name="id_conversazione" value="<?php echo isset($_SESSION['id_conversazione']) ? $_SESSION['id_conversazione'] : ''; ?>">
    
    <label for="start_date">Data inizio:</label>
    <input type="date" id="start_date" name="start_date" required>
    
    <label for="end_date">Data fine:</label>
    <input type="date" id="end_date" name="end_date" required>
    
    <button type="submit">SCARICA</button>
</form>

</div>


    
    
    

<!-- Popup Gestione Gruppo -->
<div id="popup-overlay" class="popup-overlay"></div>
<div id="group-management-popup_gruppo" class="popup_gruppo">
    <div class="popup-content_gruppo">
    
    
        <button class="close-popup_gruppo" id="close-group-popup-button_gruppo">X</button>
        <h2>Gestisci Gruppo</h2>
        
        <!-- Modifica Titolo -->
        <form method="post" action="gestione_gruppo.php">
    <div class="form-group_gruppo">
        <label for="group-title_gruppo">Modifica Titolo:</label>
        <input type="text" id="group-title_gruppo" name="nuovo_titolo" value="<?= htmlspecialchars($titolo_conversazione) ?>">
        <input type="hidden" name="gruppo_id" value="<?= $gruppo_id ?>">
        <button type="submit" name="modifica_titolo" class="save-button_gruppo">Modifica</button>
    </div>
</form>

        
        <!-- Aggiungi Partecipanti -->
<form action="gestione_gruppo.php" method="post">
    <!-- Campo nascosto per il gruppo_id -->
    <input type="hidden" name="gruppo_id" value="<?= $gruppo_id ?>">

    <div class="form-group_gruppo">
        <label for="aggiungi-partecipanti_gruppo">Aggiungi Partecipanti:</label>
        <select id="aggiungi-partecipanti_gruppo" name="aggiungi_partecipanti[]">
            <option value="">Seleziona un partecipante</option> <!-- Opzione vuota per default -->
            <?php
            // Query per selezionare tutti i personaggi tranne quelli già nel gruppo
            $sql_personaggi = "
                SELECT nome 
                FROM personaggio 
                WHERE nome NOT IN (
                    SELECT utente_nome 
                    FROM partecipazione_gruppo 
                    WHERE gruppo_id = $gruppo_id
                )
                ORDER BY nome ASC
            ";
            $result_personaggi = gdrcd_query($sql_personaggi, 'result');

            // Popola il select con i nomi dei personaggi non ancora nel gruppo
            while ($row = gdrcd_query($result_personaggi, 'fetch')) {
                $nome_personaggio = htmlspecialchars($row['nome']);
                echo "<option value='$nome_personaggio'>$nome_personaggio</option>";
            }
            ?>
        </select>
    </div>

    <!-- Pulsante per aggiungere il partecipante -->
    <button type="submit" name="aggiungi_partecipante" class="save-button_gruppo">Aggiungi Partecipante</button>
</form>


        
        <!-- Rimuovi Partecipanti (con select) -->
<form action="gestione_gruppo.php" method="post">
    <!-- Campo nascosto per il gruppo_id -->
    <input type="hidden" name="gruppo_id" value="<?= $gruppo_id ?>">

    <div class="form-group_gruppo">
        <label for="rimuovi-partecipanti_gruppo">Rimuovi Partecipanti:</label>
        <select id="rimuovi-partecipanti_gruppo" name="rimuovi_partecipanti">
            <option value="">Seleziona un partecipante</option> <!-- Opzione vuota per default -->
            <?php foreach ($partecipanti as $nome_partecipante): ?>
                <?php if ($nome_partecipante !== $_SESSION['login']): ?>
                    <option value="<?= $nome_partecipante ?>"><?= $nome_partecipante ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Pulsante per rimuovere il partecipante -->
    <button type="submit" name="rimuovi_partecipante" class="save-button_gruppo">Rimuovi Partecipante</button>
</form>

<br>        
        <!-- Abbandona Gruppo -->
<form action="gestione_gruppo.php" method="post">
    <!-- Campo nascosto per il gruppo_id -->
    <input type="hidden" name="gruppo_id" value="<?= $gruppo_id ?>">
    
    <!-- Pulsante per abbandonare il gruppo -->
    <div class="form-group_gruppo">
        <button type="submit" name="abbandona_gruppo" class="abbandona-button_gruppo">Abbandona Gruppo</button>
    </div>
</form>

        
        
    </div>
</div>



    
    <script>
const messages = document.querySelectorAll('.message');
const maxMessagesPerPage = 8;
let currentPage = 1;

function updatePagination() {
    const totalMessages = messages.length;
    const totalPages = Math.ceil(totalMessages / maxMessagesPerPage);

    // Mostra solo i messaggi della pagina corrente
    messages.forEach((message, index) => {
        message.style.display = 'none';
        const start = (currentPage - 1) * maxMessagesPerPage;
        const end = currentPage * maxMessagesPerPage;
        if (index >= start && index < end) {
            message.style.display = 'block';
        }
    });

    // Mostra o nascondi le frecce di navigazione
    document.getElementById('prev-page').style.display = currentPage > 1 ? 'inline-block' : 'none';
    document.getElementById('next-page').style.display = currentPage < totalPages ? 'inline-block' : 'none';
}

function goToPreviousPage() {
    if (currentPage > 1) {
        currentPage--;
        updatePagination();
    }
}

function goToNextPage() {
    const totalMessages = messages.length;
    const totalPages = Math.ceil(totalMessages / maxMessagesPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        updatePagination();
    }
}

// Inizializza la paginazione alla prima pagina
updatePagination();






//popup per i gruppi
// Apre il popup per i gruppi
document.getElementById('manage-group-button_gruppo').addEventListener('click', function(event) {
    event.preventDefault();  // Previene l'invio non intenzionale del form
    document.getElementById('group-management-popup_gruppo').classList.add('show');
    document.getElementById('popup-overlay').classList.add('show');  // Mostra l'overlay sfocato
});

// Chiude il popup cliccando sul pulsante "X"
document.getElementById('close-group-popup-button_gruppo').addEventListener('click', function(event) {
    event.preventDefault();  // Previene l'invio non intenzionale del form
    document.getElementById('group-management-popup_gruppo').classList.remove('show');
    document.getElementById('popup-overlay').classList.remove('show');  // Nasconde l'overlay sfocato
});

// Chiude il popup cliccando sull'overlay sfocato
document.getElementById('popup-overlay').addEventListener('click', function() {
    document.getElementById('group-management-popup_gruppo').classList.remove('show');
    document.getElementById('popup-overlay').classList.remove('show');
});


// Aggiungi la logica per il pulsante "Abbandona Gruppo"
document.getElementById('abbandona-gruppo-button_gruppo').addEventListener('click', function(event) {
    event.preventDefault();  // Previene l'invio non intenzionale del form
    if (confirm('Sei sicuro di voler abbandonare il gruppo?')) {
        // Aggiungi la logica per abbandonare il gruppo qui
        alert('Hai abbandonato il gruppo.');
    }
});

// Aggiungi la logica per salvare le modifiche al gruppo
document.getElementById('save-group-changes_gruppo').addEventListener('click', function(event) {
    event.preventDefault();  // Previene l'invio non intenzionale del form
    // Aggiungi la logica per salvare le modifiche qui
    alert('Modifiche salvate.');
});












document.addEventListener('DOMContentLoaded', () => {
    const messageItems = document.querySelectorAll('.message-item, .new-message-button, .create-group-button');
    const mainContent = document.querySelector('.main-content');
    const popupOverlay = document.querySelector('.popup-overlay');

    // Aggiungi un evento click a ciascun elemento per aprire il popup
    messageItems.forEach(item => {
        item.addEventListener('click', () => {
            mainContent.classList.add('active');  // Mostra il popup
            popupOverlay.classList.add('active'); // Mostra l'overlay
        });
    });

    // Chiudi il popup quando si clicca fuori dal popup
    popupOverlay.addEventListener('click', () => {
        mainContent.classList.remove('active');
        popupOverlay.classList.remove('active');
    });
});

// Event delegation per chiudere il popup con la X
document.addEventListener('click', function(event) {
    if (event.target.id === 'closePopup') {
        const mainContent = document.querySelector('.main-content');
        const popupOverlay = document.querySelector('.popup-overlay');
        mainContent.classList.remove('active');
        popupOverlay.classList.remove('active');
    }
});





function openDownloadPopup() {
    document.getElementById('download-popup').style.display = 'block';
}

function closeDownloadPopup() {
    document.getElementById('download-popup').style.display = 'none';
}
</script>

</body>
</html>
