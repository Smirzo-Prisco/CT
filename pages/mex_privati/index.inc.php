<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaggi Privati</title>
    <link rel="stylesheet" href="pages/mex_privati/new_sms.css">

</head>
<body>
<?php
// Nome del personaggio da cercare
$personaggio = $_SESSION['login'];

// Variabili per tracciare la presenza di nuovi messaggi
$new_messages_off = false;
$new_messages_on = false;

// Funzione per ottenere l'URL dell'avatar di un personaggio
function get_avatar_url($nome_personaggio) {
    $query = "SELECT url_img_chat FROM personaggio WHERE nome = '".gdrcd_filter('in', $nome_personaggio)."'";
    $result = gdrcd_query($query, 'result');
    $row = gdrcd_query($result, 'fetch');
    return $row ? $row['url_img_chat'] : 'http://japancrystal.altervista.org/imgs/avatars/mini_avatar_empty.png'; // Fallback se non trovato
}

// Funzione per ottenere il nome da mostrare e l'avatar
function get_display_data($mittente, $destinatario, $personaggio) {
    if ($mittente === $personaggio) {
        $display_name = $destinatario;
        $avatar_url = get_avatar_url($destinatario);
    } else {
        $display_name = $mittente;
        $avatar_url = get_avatar_url($mittente);
    }
    return ['display_name' => $display_name, 'avatar_url' => $avatar_url];
}

// Funzione per formattare la data nel formato gg-mm-aaaa
function format_date($date, $is_ongame) {
    $date_object = new DateTime($date);
    if ($is_ongame) {
        // Calcola l'anno ON basato sull'anno corrente
        $current_year = (int)date('Y');
        $on_year = 3077 + ($current_year - 2024); // Adatta l'anno ON
        $date_object->setDate($on_year, $date_object->format('m'), $date_object->format('d'));
    }
    return $date_object->format('d-m-Y H:i');
}

function is_unread($personaggio, $row) {
    if (!isset($row['lettura']) || $row['lettura'] === '') {
        return false; // Tratta i valori vuoti come letti
    }
    return $row['lettura'] == 0;
}







function is_group_message_unread($conversazione_id, $personaggio) {
    // Query per verificare se l'utente ha letto i messaggi del gruppo
    $sql = "
        SELECT lettura 
        FROM partecipazione_gruppo 
        WHERE gruppo_id = '".gdrcd_filter('num', $conversazione_id)."' 
        AND utente_nome = '".gdrcd_filter('in', $personaggio)."' 
        LIMIT 1
    ";
    $result = gdrcd_query($sql, 'result');
    $row = gdrcd_query($result, 'fetch');
    
    // Verifica se il campo 'lettura' è impostato a 0 (non letto)
    return ($row && $row['lettura'] == 0);
}


// Query per selezionare l'ultimo messaggio di ogni conversazione individuale e di gruppo
$sql_combined = "
    (SELECT 
        c.id_conversazione AS conversazione_id,
        s.mittente_nome,
        s.destinatario_nome,
        s.ongame,
        s.ora_spedizione,
        s.testo,
        'individuale' AS tipo,
        c.lettura,
        NULL AS titolo_gruppo,
        NULL AS gruppo_id,
        0 AS is_globale 
    FROM conversazioni_individuali c
    JOIN sms s ON c.id_conversazione = s.id_conversazione
    WHERE c.utente_nome = '".gdrcd_filter('in', $personaggio)."'
        AND s.ora_spedizione = (
            SELECT MAX(s2.ora_spedizione)
            FROM sms s2
            WHERE s2.id_conversazione = s.id_conversazione
        )
        AND s.mittente_nome != s.destinatario_nome
    )
    
    UNION
    
    (SELECT 
        pg.gruppo_id AS conversazione_id,
        s.mittente_nome,
        NULL AS destinatario_nome,
        s.ongame,
        s.ora_spedizione,
        s.testo,
        'gruppo' AS tipo,
        NULL AS lettura,
        s.titolo_gruppo,
        pg.gruppo_id,
        0 AS is_globale 
    FROM partecipazione_gruppo pg
    JOIN sms s ON pg.gruppo_id = s.gruppo_id
    WHERE pg.utente_nome = '".gdrcd_filter('in', $personaggio)."'
        AND s.ora_spedizione = (
            SELECT MAX(s2.ora_spedizione)
            FROM sms s2
            WHERE s2.gruppo_id = s.gruppo_id
        )
        AND s.is_globale = 0
    )
    
    UNION
    
    (SELECT 
        s.gruppo_id AS conversazione_id,
        s.mittente_nome,
        NULL AS destinatario_nome,
        s.ongame,
        MAX(s.ora_spedizione) AS ora_spedizione,  
        s.testo,
        'globale' AS tipo,
        NULL AS lettura,
        s.titolo_gruppo,
        s.gruppo_id,
        1 AS is_globale 
    FROM sms s
    WHERE s.is_globale = 1
    GROUP BY s.gruppo_id, s.titolo_gruppo, s.mittente_nome, s.ongame, s.testo 
    ORDER BY ora_spedizione DESC
    LIMIT 1 
    )
    
    ORDER BY is_globale DESC, ora_spedizione DESC
";




// Esegui la query combinata
$result_combined = gdrcd_query($sql_combined, 'result');


// Creazione delle sezioni ON e OFF
$messaggi_on = '';
$messaggi_off = '';
$titoli_gruppi_memorizzati = [];

// Funzione per ottenere l'anteprima del testo (primi 20 caratteri)
function get_preview($text) {
    $plain_text = strip_tags($text);
    return substr($plain_text, 0, 20) . (strlen($plain_text) > 20 ? '...' : '');
}

// Gestione delle conversazioni individuali
while ($row = gdrcd_query($result_combined, 'fetch')) {
    $conversazione_id = $row['conversazione_id'];
    $mittente = $row['mittente_nome'];
    $destinatario = $row['destinatario_nome'];
    $data = format_date($row['ora_spedizione'], $row['ongame']); // Formatta la data
    $preview = get_preview($row['testo']);
    
    

    // Gestione dei messaggi individuali
    if ($row['tipo'] == 'individuale') {
        // Determina il nome da visualizzare e l'avatar
        $display_data = get_display_data($mittente, $destinatario, $personaggio);
        $display_name = $display_data['display_name'];
        
        if ($mittente === 'Segnalazione') {
    $avatar_url = '../pages/msg/img/segnalazione.png';
} elseif ($mittente === 'Calendario') {
    $avatar_url = '../pages/msg/img/calendario.png';
} else {
    $avatar_url = $display_data['avatar_url'];
}
        
        // Verifica se il messaggio è non letto e imposta la classe CSS
        $avatar_class = is_unread($personaggio, $row) ? 'unread-avatar' : '';
        
        // Se il messaggio è non letto, aggiorna la variabile dei nuovi messaggi
        if (is_unread($personaggio, $row)) {
    if ($row['ongame'] == 1) {
        $new_messages_on = true; // Nuovo messaggio ON individuale
    } else {
        $new_messages_off = true; // Nuovo messaggio OFF individuale
    }
}
        
        // Link al messaggio individuale
        $link_messaggio = "../pages/mex_privati/message_content.php?conversazione_id=" . $conversazione_id;
        
    } elseif ($row['tipo'] == 'gruppo') {
    
        // Controlla se il titolo del gruppo è già stato memorizzato
        if (!isset($titoli_gruppi_memorizzati[$conversazione_id])) {
            // Esegui una query separata per ottenere il titolo del gruppo
            $sql_titolo_gruppo = "SELECT titolo_gruppo 
                                  FROM sms 
                                  WHERE gruppo_id = $conversazione_id 
                                  AND titolo_gruppo IS NOT NULL 
                                  LIMIT 1";
            $result_titolo = gdrcd_query($sql_titolo_gruppo, 'result');
            $row_titolo = gdrcd_query($result_titolo, 'fetch');

            // Memorizza il titolo del gruppo
            if ($row_titolo) {
                $titoli_gruppi_memorizzati[$conversazione_id] = $row_titolo['titolo_gruppo'];
            } else {
                $titoli_gruppi_memorizzati[$conversazione_id] = "Gruppo Sconosciuto"; // Valore di fallback
            }
        }

        // Usa il titolo del gruppo memorizzato
        $display_name = $titoli_gruppi_memorizzati[$conversazione_id];
        $avatar_url = '../../themes/crystal/imgs/sms/img_gruppo.jpg'; // Recupera l'avatar del gruppo
        
        // Verifica se ci sono messaggi non letti nel gruppo per l'utente corrente
        $avatar_class = is_group_message_unread($conversazione_id, $_SESSION['login']) ? 'unread-avatar' : '';
        
        if ($row['tipo'] == 'gruppo' && is_group_message_unread($conversazione_id, $_SESSION['login'])) {
    if ($row['ongame'] == 1) {
        $new_messages_on = true; // Nuovo messaggio ON di gruppo
    } else {
        $new_messages_off = true; // Nuovo messaggio OFF di gruppo
    }
}

        // Link al messaggio di gruppo
        $link_messaggio = "../pages/mex_privati/message_content.php?gruppo_id=" . $conversazione_id;
    } elseif ($row['tipo'] == 'globale') {
        // Gestione dei messaggi globali
        $display_name = "Messaggio Globale";
        $avatar_url = '../../themes/crystal/imgs/sms/img_globale.jpg'; // Puoi impostare un avatar specifico per i messaggi globali
        
        // Verifica se ci sono messaggi non letti nel gruppo per l'utente corrente
        $avatar_class = is_group_message_unread($conversazione_id, $_SESSION['login']) ? 'unread-avatar' : '';
        
        // Link al messaggio globale
        $link_messaggio = "../pages/mex_privati/message_content.php?gruppo_id=" . $conversazione_id;
    }

    // Genera l'HTML per ogni messaggio, sia individuale che di gruppo
    $html = "
        <a href='#' onclick=\"loadMessageContent('$link_messaggio'); return false;\">
            <div class='message-item'>
                <img src='{$avatar_url}' alt='Avatar' class='{$avatar_class}'>
                <div class='message-details'>
                    <p class='sender'>{$display_name}</p>
                    <p class='date'>{$data}</p>
                    <p class='preview'>{$preview}</p>
                </div>
            </div>
        </a>
    ";
    
    // Aggiungi il messaggio alla sezione ON o OFF in base al valore ongame
    if ($row['ongame'] == 1) {
        $messaggi_on .= $html;
    } else {
        $messaggi_off .= $html;
    }
}


// Imposta le immagini dei pulsanti in base alla presenza di nuovi messaggi, inclusi quelli di gruppo
$off_button_image = $new_messages_off ? "../../themes/crystal/imgs/sms/MessaggioOff_Acceso.gif" : "./../themes/crystal/imgs/sms/MessaggioOff_Spento.png";
$on_button_image = $new_messages_on ? "../../themes/crystal/imgs/sms/MessaggioOn_Acceso.gif" : "./../themes/crystal/imgs/sms/MessaggioOn_Spento.png";

?>
    <div class="container">
        <div class="sidebar">
            <div class="header">
                <div class="header-container">
                    <h1 class="header-title">Messaggi Privati</h1>
                </div>
            </div>
            <div class="toggle-buttons">
                <div class="toggle-button" id="toggle-off">
                    <img src="<?= $off_button_image ?>" alt="Messaggi Off">
                </div>
                
                <div class="toggle-button active" id="toggle-on">
                    <img src="<?= $on_button_image ?>" alt="Messaggi On">
                </div>
                
            </div>
            <div class="messages-list" id="messages-list">
                <!-- Sezione dei messaggi "on" -->
                <div class="message-section active" id="messages-off">
                    <!-- Esempio di messaggi con avatar -->
                    
                    
                    
                    
                    
                    
                    
                    
                   <?= $messaggi_off ? $messaggi_off : '<p>Nessun messaggio OFF trovato.</p>'; ?>
                   
                </div>
                <!-- Sezione dei messaggi "off" -->
                <div class="message-section" id="messages-on">
                    <?= $messaggi_on ? $messaggi_on : '<p>Nessun messaggio ON trovato.</p>'; ?>
                </div>
            </div>
            <div class="navigation-buttons">
                <button id="prev-page" disabled>&lt;&lt;</button>
                <button id="next-page">&gt;&gt;</button>
            </div>
            
            <?php
            // Query per selezionare tutti i personaggi tranne l'utente corrente
            $sql_personaggi = "SELECT nome 
                   FROM personaggio 
                   WHERE nome != '".gdrcd_filter('in', $personaggio)."'
                   ORDER BY nome ASC";

           // Esegui la query e salva i risultati in un array
           $result_personaggi = gdrcd_query($sql_personaggi, 'result');
           $personaggi = [];
           while ($row = gdrcd_query($result_personaggi, 'fetch')) {
               $personaggi[] = $row['nome'];
           }
           

            ?>
                <div class="bottom-bar">
                
                <button id="new-message-button">Nuovo Messaggio</button> <!-- Nuovo pulsante generico -->
                <button id="archive-button">Archivio</button> <!-- Pulsante Archivio -->
                
                <!-- Popup per Nuovo SMS e Nuovo Gruppo -->
    <div id="new-message-popup" class="popup" style="display: none;">
        <button class="close-popup" id="close-new-message-popup-button">X</button>
        <div class="popup-content">
            <button id="new-sms-button">Nuovo SMS</button><br>
            <button id="multi-message-button">SMS Multiplo</button><br>
            <button id="new-group-button">Nuovo Gruppo</button>
        </div>
    </div>
    
    
                <div id="new-sms-popup" class="popup" style="display: none;">
        <button class="close-popup" id="close-popup-button">X</button>
        <div class="popup-content">
            <select id="new-sms-select">
                <option value="">Seleziona un personaggio</option>
                <?php foreach ($personaggi as $nome_personaggio): ?>
                    <option value="<?= $nome_personaggio ?>"><?= $nome_personaggio ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="ongame-offgame">
            <option value="0">OFF</option>
            <option value="1">ON</option>
            </select>
            
        </div>
    </div>
   

<!-- Form per la creazione del nuovo gruppo -->
<div id="new-group-popup" class="popup" style="display: none;">
    <button class="close-popup" id="close-group-popup-button">X</button>
    <div class="popup-content">
<form id="create-group-form" target="message-content-iframe" action="../pages/mex_privati/create_group.php" method="post">
            <input type="text" name="titolo_gruppo" id="group-title" placeholder="Titolo del gruppo" required>

            <div id="group-participants-checkboxes">
                <?php foreach ($personaggi as $nome_personaggio): ?>
                    <label>
                        <input type="checkbox" name="partecipanti[]" value="<?= $nome_personaggio ?>">
                        <?= $nome_personaggio ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
            
            <button type="submit">Crea Gruppo</button>
        </form>
    </div>
</div>





            </div>
        </div>
         <!-- Main Content Area -->
        <div class="main-content">
    <!-- Placeholder per l'immagine di sfondo quando il messaggio non è aperto -->
    <div id="message-placeholder" class="message-placeholder">
        <img src="../../themes/crystal/imgs/icone/logo_chat.png" alt="Logo Chat">
    </div>

    <!-- Iframe nascosto di default, visibile solo quando si apre un messaggio -->
    <div id="message-iframe-container" class="message-iframe-container" style="display: none;">
    <iframe name="message-content-iframe" class="message-content-iframe" frameborder="0"></iframe>
    </div>
</div>
        
        
        
    </div>
    
    
    
    
    
    
    
    
    
    
    
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Nasconde la sezione ON e mostra la sezione OFF al caricamento della pagina
    document.getElementById('messages-on').style.display = 'none';
    document.getElementById('messages-off').style.display = 'block';

    // Imposta lo stato attivo per il pulsante OFF e disattivo per il pulsante ON
    document.getElementById('toggle-off').classList.add('active');
    document.getElementById('toggle-on').classList.remove('active');

    // Aggiorna la paginazione per la sezione OFF al caricamento della pagina
    updatePagination('off');
});

document.getElementById('toggle-on').addEventListener('click', function() {
    document.getElementById('messages-on').style.display = 'block';
    document.getElementById('messages-off').style.display = 'none';
    document.getElementById('toggle-on').classList.add('active');
    document.getElementById('toggle-off').classList.remove('active');
    updatePagination('on'); // Aggiorna la paginazione per la sezione ON
});

document.getElementById('toggle-off').addEventListener('click', function() {
    document.getElementById('messages-on').style.display = 'none';
    document.getElementById('messages-off').style.display = 'block';
    document.getElementById('toggle-on').classList.remove('active');
    document.getElementById('toggle-off').classList.add('active');
    updatePagination('off');
});

if (window.ctSocket) {
    window.ctSocket.on('dm:update', function() {
        fetch('/pages/ajax_engine.php?op=getMessagesOngameStatus')
            .then(r => r.json())
            .then(data => {
                const elOff = document.getElementById('toggle-off');
                const elOn  = document.getElementById('toggle-on');
                if (elOff) {
                    elOff.querySelector('img').src = data.hasNewOff
                        ? '../../themes/crystal/imgs/sms/MessaggioOff_Acceso.gif'
                        : './../themes/crystal/imgs/sms/MessaggioOff_Spento.png';
                    elOff.classList.toggle('active', data.hasNewOff);
                }
                if (elOn) {
                    elOn.querySelector('img').src = data.hasNewOn
                        ? '../../themes/crystal/imgs/sms/MessaggioOn_Acceso.gif'
                        : './../themes/crystal/imgs/sms/MessaggioOn_Spento.png';
                    elOn.classList.toggle('active', data.hasNewOn);
                }
            });
    });
}

const maxItemsPerPage = 10;
let currentPageOn = 1;
let currentPageOff = 1;

function updatePagination(section) {
    const activeSection = document.getElementById(section === 'on' ? 'messages-on' : 'messages-off');
    const items = activeSection.querySelectorAll('.message-item');
    const totalPages = Math.ceil(items.length / maxItemsPerPage);
    const currentPage = section === 'on' ? currentPageOn : currentPageOff;

    document.getElementById('prev-page').disabled = currentPage === 1;
    document.getElementById('next-page').disabled = currentPage === totalPages;

    items.forEach((item, index) => {
        item.style.display = (index >= (currentPage - 1) * maxItemsPerPage && index < currentPage * maxItemsPerPage) ? 'flex' : 'none';
    });
}

document.getElementById('prev-page').addEventListener('click', function() {
    const activeSectionId = document.querySelector('.message-section:not([style*="display: none"])').id;
    if (activeSectionId === 'messages-on' && currentPageOn > 1) {
        currentPageOn--;
        updatePagination('on');
    } else if (activeSectionId === 'messages-off' && currentPageOff > 1) {
        currentPageOff--;
        updatePagination('off');
    }
});

document.getElementById('next-page').addEventListener('click', function() {
    const activeSectionId = document.querySelector('.message-section:not([style*="display: none"])').id;
    const activeSection = document.getElementById(activeSectionId);
    const items = activeSection.querySelectorAll('.message-item');

    if (activeSectionId === 'messages-on' && currentPageOn < Math.ceil(items.length / maxItemsPerPage)) {
        currentPageOn++;
        updatePagination('on');
    } else if (activeSectionId === 'messages-off' && currentPageOff < Math.ceil(items.length / maxItemsPerPage)) {
        currentPageOff++;
        updatePagination('off');
    }
});

// Aggiornamento iniziale per la sezione attiva
updatePagination('off');







//selezione personaggio
document.getElementById('new-sms-button').addEventListener('click', function() {
    var popup = document.getElementById('new-sms-popup');
    popup.style.display = (popup.style.display === 'none') ? 'block' : 'none';
});

document.getElementById('close-popup-button').addEventListener('click', function() {
    document.getElementById('new-sms-popup').style.display = 'none';
});










document.getElementById('new-sms-select').addEventListener('change', function() {
    updateIframe();
});

document.getElementById('ongame-offgame').addEventListener('change', function() {
    updateIframe();
});

function updateIframe() {
    var personaggio = document.getElementById('new-sms-select').value;
    var ongameOffgame = document.getElementById('ongame-offgame').value;

    if (personaggio !== "" && ongameOffgame !== "") {
        var iframe = document.querySelector('.message-content-iframe');
        iframe.src = '../pages/mex_privati/message_content.php?personaggio=' + encodeURIComponent(personaggio) + '&ongame=' + encodeURIComponent(ongameOffgame);

        // Nascondi il placeholder e mostra l'iframe
        document.getElementById('message-placeholder').style.display = 'none';
        document.getElementById('message-iframe-container').style.display = 'block';
    }
}












//comparsa iframe
function openMessage() {
    document.getElementById('message-placeholder').style.display = 'none';
    document.getElementById('message-iframe-container').style.display = 'block';
}

// Simulazione di apertura del messaggio (aggiungi questa funzione ai tuoi messaggi)
document.querySelectorAll('.message-item').forEach(function(item) {
    item.addEventListener('click', function() {
        openMessage();
    });
});









// Gestione click su Messaggio Multiplo
document.getElementById('multi-message-button').addEventListener('click', function() {
    var iframe = document.querySelector('.message-content-iframe');
    iframe.src = '../pages/mex_privati/multi_message.php';  // Apri la nuova pagina nell'iframe

    // Nascondi il placeholder e mostra l'iframe, come nell'archivio
    document.getElementById('message-placeholder').style.display = 'none';
    document.getElementById('message-iframe-container').style.display = 'block';
});






//per i gruppi
// Gestione apertura/chiusura popup nuovo gruppo
document.getElementById('new-group-button').addEventListener('click', function() {
    var popup = document.getElementById('new-group-popup');
    popup.style.display = (popup.style.display === 'none') ? 'block' : 'none';
});

document.getElementById('close-group-popup-button').addEventListener('click', function() {
    document.getElementById('new-group-popup').style.display = 'none';
});

// Eventuale gestione della creazione del gruppo
document.getElementById('create-group-form').addEventListener('submit', function() {
    var iframeContainer = document.getElementById('message-iframe-container');
    iframeContainer.style.display = 'block'; // Mostra l'iframe
    document.getElementById('message-placeholder').style.display = 'none'; // Nascondi il placeholder se presente
});













//per caricare iframe
function loadMessageContent(url) {
    var iframe = document.querySelector('.message-content-iframe');
    iframe.src = url;

    // Nascondi il placeholder e mostra l'iframe
    document.getElementById('message-placeholder').style.display = 'none';
    document.getElementById('message-iframe-container').style.display = 'block';
}



//archivio

// Mostra/Nasconde il popup per Nuovo SMS e Nuovo Gruppo
document.getElementById('new-message-button').addEventListener('click', function() {
    var popup = document.getElementById('new-message-popup');
    popup.style.display = (popup.style.display === 'none' || popup.style.display === '') ? 'block' : 'none';
});

// Gestione click su Archivio
document.getElementById('archive-button').addEventListener('click', function() {
    var iframe = document.querySelector('.message-content-iframe');
    var login = '<?= $_SESSION['login'] ?>'; // Recupera il nome dell'utente loggato
    iframe.src = '../pages/mex_privati/message_content.php?conversazione_id=archive&personaggio=' + encodeURIComponent(login);

    // Nascondi il placeholder e mostra l'iframe
    document.getElementById('message-placeholder').style.display = 'none';
    document.getElementById('message-iframe-container').style.display = 'block';
});

// Chiusura popup Nuovo Messaggio
document.getElementById('close-new-message-popup-button').addEventListener('click', function() {
    document.getElementById('new-message-popup').style.display = 'none';
});


</script>


</body>
</html>
