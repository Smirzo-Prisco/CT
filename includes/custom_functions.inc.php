<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

/**
 * Invia un'email HTML via Brevo API HTTP (porta 443).
 * Usato al posto di SMTP perché Hetzner blocca le porte 25/465/587 in uscita.
 * Richiede $PARAMETERS['smtp']['api_key'] in config.inc.php (fuori da git).
 *
 * @param string $to      Indirizzo destinatario
 * @param string $subject Oggetto
 * @param string $message Corpo HTML
 * @return bool           true se Brevo ha accettato il messaggio (HTTP 201)
 */
function send_mail(string $to, string $subject, string $message) : bool {
    $cfg = $GLOBALS['PARAMETERS']['smtp'] ?? null;
    if (!$cfg || empty($cfg['api_key'])) {
        error_log('[send_mail] api_key Brevo non configurata in config.inc.php');
        return false;
    }

    $from     = isset($cfg['from']) ? $cfg['from'] : $cfg['user'];
    $fromname = isset($cfg['fromname']) ? $cfg['fromname'] : 'Crystal Tokyo';

    $payload = json_encode([
        'sender'      => ['name' => $fromname, 'email' => $from],
        'to'          => [['email' => $to]],
        'subject'     => $subject,
        'htmlContent' => $message,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $cfg['api_key'],
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { error_log("[send_mail] curl error: $curlErr"); return false; }
    if ($httpCode !== 201) { error_log("[send_mail] Brevo API HTTP $httpCode: $response"); return false; }

    return true;
}

/**
 * Offset (in anni) fra l'anno reale e l'anno dell'ambientazione, letto da
 * $PARAMETERS['date']['offset'] in config.inc.php. Unico punto da cui va letto
 * questo valore: prima era duplicato come "+1053 years" hardcoded in api_map.php
 * e mai sincronizzato col parametro di config previsto per questo scopo.
 *
 * @return int
 */
function getAnnoOffset() : int {
    return (int)($GLOBALS['PARAMETERS']['date']['offset'] ?? 0);
}

/**
 * Anno dell'ambientazione "oggi" (anno reale + offset). Per ottenere l'anno di
 * un anno reale diverso da quello corrente (es. la vista mese del calendario),
 * sommare getAnnoOffset() direttamente a quell'anno invece di usare questa.
 *
 * @return int
 */
function getAnnoGioco() : int {
    return (int)date('Y') + getAnnoOffset();
}

function generateLinks($current_page, $total_pages, $url = "?") {
    $links = '';

    if($total_pages <= 1) return $links;

    // Link Prima e Precedente
    if($current_page > 1) {
        $links .= '<a href="' . $url . '&offset=1">Prima</a>';
        $links .= '<a href="' . $url . '&offset=' . ($current_page - 1) . '">Prec</a>';
    }

    // Numeri di pagina
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    for($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? ' class="active"' : '';
        $links .= '<a href="' . $url . '&offset=' . $i . '"' . $active . '>' . $i . '</a>';
    }

    // Link Successiva e Ultima
    if($current_page < $total_pages) {
        $links .= '<a href="' . $url . '&offset=' . ($current_page + 1) . '">Succ</a>';
        $links .= '<a href="' . $url . '&offset=' . $total_pages . '">Ultima</a>';
    }

    return '<div class="pagination">' . $links . '</div>';
}

function createNewObject() {
    session_start();

    // Sanificazione base dei dati
    $nome = gdrcd_filter('in', $_POST['nome_oggetto']);
    $descrizione = gdrcd_filter('in', $_POST['descrizione_oggetto']);
    $categoria = gdrcd_filter('in', $_POST['categoria']);
    $ubicabile = (int) $_POST['fit_in'];
    $tipo = (int) $_POST['tipo_oggetto'];
    $richiede_ricarica = (int) $_POST['richiede_ricarica'];
    $cariche = ($_POST['cariche'] === 'illimitato') ? 'illimitato' : (int) $_POST['cariche'];

    // Se è arma di gilda, forza cariche illimitate e nessuna ricarica
    if (isset($_POST['tipo_oggetto']) && (int)$_POST['tipo_oggetto'] == 15) {
        $cariche = 'illimitato';
        $richiede_ricarica = 0;
    }

    $isTemp = isset($_POST['isTemp']) ? 1 : 0;
    $temp_giorni = isset($_POST['temp_giorni']) ? (int) $_POST['temp_giorni'] : 0;
    $creatore = $_SESSION['login'];
    $data_inserimento = date('Y-m-d H:i:s');
    $urlimg = '';
    $id_oggetto = '';

    // Gestione immagine
    if (!empty($_FILES['img_oggetto']['tmp_name'])) {
        $img_tmp = $_FILES['img_oggetto']['tmp_name'];
        $img_nome = basename($_FILES['img_oggetto']['name']);
        $img_ext = strtolower(pathinfo($img_nome, PATHINFO_EXTENSION));
        $target_dir = "imgs/items/";
        $target_file = $target_dir . uniqid('oggetto_') . "." . $img_ext;

        // Resize automatico se troppo grande
        list($width, $height) = getimagesize($img_tmp);

        if ($width > 200 || $height > 200) {
            $new_w = 200;
            $new_h = 200;
            $src_img = null;

            switch ($img_ext) {
                case 'jpg':
                case 'jpeg': $src_img = imagecreatefromjpeg($img_tmp); break;
                case 'png': $src_img = imagecreatefrompng($img_tmp); break;
                case 'gif': $src_img = imagecreatefromgif($img_tmp); break;
            }

            if ($src_img) {
                $dst_img = imagecreatetruecolor($new_w, $new_h);
                imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
                imagejpeg($dst_img, $target_file, 90); // Salva come JPG
                imagedestroy($src_img);
                imagedestroy($dst_img);
                $urlimg = basename($target_file); // Rimuove ../
            }
        } else {
            move_uploaded_file($img_tmp, $target_file);
            $urlimg = basename($target_file); // Rimuove ../
        }
    }

    // Extra: Se è un'arma
    $tipo_arma = isset($_POST['tipo_arma']) ? (int) $_POST['tipo_arma'] : 0;
    $bonus_arma = isset($_POST['bonus_arma']) ? (int) $_POST['bonus_arma'] : 0;
    $ricarica_massima = isset($_POST['ricarica_massima']) ? (int) $_POST['ricarica_massima'] : 0;

    // Heal
    $heal = isset($_POST['salute_integra']) ? (int) $_POST['salute_integra'] : 0;
    $integrita = isset($_POST['integrita_integra']) ? (int) $_POST['integrita_integra'] : 0;

    // Bonus caratteristiche extra
    $bonus_car1_extra = isset($_POST['bonus_car1_extra']) ? (int) $_POST['bonus_car1_extra'] : 0;
    $bonus_car2_extra = isset($_POST['bonus_car2_extra']) ? (int) $_POST['bonus_car2_extra'] : 0;
    $bonus_car3_extra = isset($_POST['bonus_car3_extra']) ? (int) $_POST['bonus_car3_extra'] : 0;
    $bonus_car4_extra = isset($_POST['bonus_car4_extra']) ? (int) $_POST['bonus_car4_extra'] : 0;
    $bonus_car5_extra = isset($_POST['bonus_car5_extra']) ? (int) $_POST['bonus_car5_extra'] : 0;

    // Inserimento nel DB
    gdrcd_query("
        INSERT INTO oggetto (
            tipo, nome, creatore, data_inserimento, descrizione, ubicabile,
            cariche, heal, bonus_car1_extra, bonus_car2_extra, bonus_car3_extra,
            bonus_car4_extra, bonus_car5_extra, urlimg, isTemp, temp_giorni,
            tipo_arma, attacco, richiede_ricarica, ricarica_massima, categoria
        ) VALUES (
            $tipo, '$nome', '$creatore', '$data_inserimento', '$descrizione', $ubicabile,
            " . (is_numeric($cariche) ? $cariche : "'$cariche'") . ",
            $heal, $bonus_car1_extra, $bonus_car2_extra, $bonus_car3_extra,
            $bonus_car4_extra, $bonus_car5_extra, '$urlimg', $isTemp, $temp_giorni,
            $tipo_arma, $bonus_arma, $richiede_ricarica, $ricarica_massima, '$categoria'
        )
    ");

    echo "<div class='success'>
            Oggetto <b>$nome</b> aggiunto con successo!
          </div>";

    echo "<div class='action-links'>
        <a href='main.php?page=oggetto_aggiungi' class='ares'>
            Torna al caricamento oggetti
        </a>
        <a href='main.php?page=oggetto_assegna&id_oggetto=$id_oggetto' class='ares'>
            Assegna l'oggetto
        </a>
        <a href='main.php?page=oggetto_mercato&id_oggetto=$id_oggetto' class='ares'>
            Inserisci nel mercato
        </a>
        <a href='main.php?page=oggetto_modifica&id_oggetto=$id_oggetto' class='ares'>
            Modifica oggetto
        </a>
      </div>";
}

function hasPermesso(array $permessi_utente, array $permessi_richiesti) {
    foreach ($permessi_richiesti as $p) {
        if (!empty($permessi_utente[$p]) && $permessi_utente[$p] == 1) return true;
    }
    return false;
}

// Manda sms interni alla land
// On e off game usano conversazioni separate tra la stessa coppia di utenti.
// $notifyNewDm: accoda una notifica email (evento nuovo_dm, Fase E) solo per
// i DM realmente scambiati fra due giocatori — mai per i mittenti di sistema
// (Notifiche, Staff, System, Segnalazione, bot...), altrimenti un DM di
// sistema finirebbe per generare un'email "hai un nuovo messaggio" ricorsiva.
// Va passato true esplicitamente dai soli punti dove $from e' un personaggio
// reale che sta scrivendo a un altro giocatore (vedi api_messages.php).
function send_sms($from, $to, $title, $text, $ongame = 0, $notifyNewDm = false) {
    $from_safe  = gdrcd_filter('in', $from);
    $to_safe    = gdrcd_filter('in', $to);
    $ongame_int = $ongame ? 1 : 0;

    // Cerca una conversazione PURA con questo tipo ongame tra questa coppia.
    // NOT EXISTS esclude le conversazioni miste (legacy) che contengono anche
    // messaggi dell'altro tipo — quelle non vanno riutilizzate.
    $exists = gdrcd_query(
        "SELECT DISTINCT s.id_conversazione FROM sms s
         WHERE ((s.mittente_nome = '$from_safe' AND s.destinatario_nome = '$to_safe')
             OR (s.mittente_nome = '$to_safe'   AND s.destinatario_nome = '$from_safe'))
           AND s.ongame = $ongame_int
           AND NOT EXISTS (
             SELECT 1 FROM sms s2
             WHERE s2.id_conversazione = s.id_conversazione
               AND s2.ongame != $ongame_int
           )
         LIMIT 1",
        'result'
    );

    if (gdrcd_query($exists, 'num_rows') > 0) {
        $id_conversazione = gdrcd_query($exists, 'fetch')['id_conversazione'];

        gdrcd_query("INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                    VALUES ('$from_safe', '$to_safe', '$text', $id_conversazione, 'individuale', $ongame_int, NOW())");
        // Catturato subito dopo l'INSERT in sms: LAST_INSERT_ID() e' per
        // sessione, non per tabella — gli INSERT successivi su
        // conversazioni_individuali qui sotto lo sovrascriverebbero.
        $sms_id = (int)gdrcd_query('SELECT LAST_INSERT_ID() AS id')['id'];

        // Garantisce che entrambi abbiano la riga (può mancare in conversazioni pre-fix)
        $chkFrom = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali WHERE id_conversazione = $id_conversazione AND utente_nome = '$from_safe'");
        if ($chkFrom['n'] == 0) gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($id_conversazione, '$from_safe', 1)");

        $chkTo = gdrcd_query("SELECT COUNT(*) AS n FROM conversazioni_individuali WHERE id_conversazione = $id_conversazione AND utente_nome = '$to_safe'");
        if ($chkTo['n'] == 0) gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($id_conversazione, '$to_safe', 0)");

        // Solo il destinatario vede il nuovo messaggio come non letto
        gdrcd_query("UPDATE conversazioni_individuali SET lettura = 0 WHERE id_conversazione = $id_conversazione AND utente_nome = '$to_safe'");
    } else {
        $new_id = (gdrcd_query(gdrcd_query("SELECT MAX(id_conversazione) as max_id FROM sms", 'result'), 'fetch')['max_id'] + 1);

        gdrcd_query("INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                    VALUES ('$from_safe', '$to_safe', '$text', $new_id, 'individuale', $ongame_int, NOW())");
        $sms_id = (int)gdrcd_query('SELECT LAST_INSERT_ID() AS id')['id'];

        // Riga per entrambi: destinatario non letto, mittente già letto
        gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($new_id, '$to_safe',   0)");
        gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($new_id, '$from_safe', 1)");
    }

    notifySocketServer('dm:update', 'dm:' . $to);

    if ($notifyNewDm) queueNewDmEmailNotification($to_safe, $sms_id);
}

// Notifiche Fase E: accoda un'email "hai un nuovo messaggio privato" per il
// destinatario di un DM reale, se ha attivato il canale email per l'evento
// nuovo_dm (default OFF: a differenza degli altri eventi, il DM e' gia'
// visibile subito nell'inbox/badge, l'email qui e' un extra opt-in, non
// una mancata notifica in-app come per forum/DM — vedi brief originale).
// Nessun via_dm per questo evento: notificare un DM con un altro DM interno
// sarebbe circolare, l'unico canale applicabile e' l'email.
// riferimento_id = sms.id (non piu' sempre 0): il worker email lo usa per
// includere mittente e testo del messaggio direttamente nel corpo dell'email.
function queueNewDmEmailNotification(string $to_safe, int $sms_id): void {
    $pref = gdrcd_query("SELECT via_email FROM preferenze_notifiche
        WHERE nome = '$to_safe' AND evento = 'nuovo_dm'");
    $via_email = $pref ? (int)$pref['via_email'] : 0;
    if (!$via_email) return;

    gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato)
        VALUES ('$to_safe', 'nuovo_dm', $sms_id, 'email', 'pending')");
}

// Notifiche: "hai messaggi non letti nella chattina off" (vedi api_chatoff.php,
// che chiama questa funzione solo alla transizione letto->non letto, non ad
// ogni riga scritta da altri). A differenza degli altri eventi, qui il
// default e' OFF su entrambi i canali (gestito in api_global.php::
// getNotificationPrefs) — la chattina off e' molto attiva, un default ON
// sarebbe troppo invasivo.
function queueChatOffUnreadNotification(string $nome_f): void {
    $pref = gdrcd_query("SELECT via_dm, via_email FROM preferenze_notifiche
        WHERE nome = '$nome_f' AND evento = 'chat_off_non_letta'");
    $via_dm    = $pref ? (int)$pref['via_dm']    : 0;
    $via_email = $pref ? (int)$pref['via_email'] : 0;

    if ($via_dm) {
        gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato, data_invio)
            VALUES ('$nome_f', 'chat_off_non_letta', 0, 'dm', 'sent', NOW())");
        send_sms('Notifiche', $nome_f, '', 'Hai messaggi non letti nella chattina off.', 0);
    }

    if ($via_email) {
        gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato)
            VALUES ('$nome_f', 'chat_off_non_letta', 0, 'email', 'pending')");
    }
}

// Notifiche: "sei stato aggiunto a un impegno nel calendario" — accodata da
// api_calendario.php?op=create per ogni partecipante coinvolto in un nuovo
// evento (mai per l'autore, che lo sa gia' avendolo creato lui). Default
// OFF su entrambi i canali (come chat_off_non_letta): l'impegno e' gia'
// visibile nel proprio calendario, questa e' un extra opt-in.
function queueCalendarioEventoNotification(string $nome_f, int $evento_id, string $autore, string $data_evento): void {
    $pref = gdrcd_query("SELECT via_dm, via_email FROM preferenze_notifiche
        WHERE nome = '$nome_f' AND evento = 'calendario_nuovo_impegno'");
    $via_dm    = $pref ? (int)$pref['via_dm']    : 0;
    $via_email = $pref ? (int)$pref['via_email'] : 0;
    if (!$via_dm && !$via_email) return;

    $data_fmt = date('d/m/Y', strtotime($data_evento));
    $autore_html = htmlspecialchars($autore, ENT_QUOTES, 'UTF-8');

    if ($via_dm) {
        gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato, data_invio)
            VALUES ('$nome_f', 'calendario_nuovo_impegno', $evento_id, 'dm', 'sent', NOW())");
        send_sms('Notifiche', $nome_f, '', "$autore_html ti ha aggiunto a un impegno nel calendario per il $data_fmt.", 0);
    }

    if ($via_email) {
        gdrcd_query("INSERT INTO notifiche (nome, evento, riferimento_id, canale, stato)
            VALUES ('$nome_f', 'calendario_nuovo_impegno', $evento_id, 'email', 'pending')");
    }
}

function isAdminMasterMod($session) {
    return (isset($session['admin']) && $session['admin'] == 1) ||
           (isset($session['master']) && $session['master'] == 1) ||
           (isset($session['moderatore']) && $session['moderatore'] == 1);
}

/************* OGGETTI ******************************/
function canEditOggetto($oggetto) {
    session_start();

    // Admin può modificare tutto
    if ($_SESSION['admin'] == 1) return true;

    // Master può modificare solo i propri oggetti o quelli di tipo speciale
    if ($_SESSION['master'] == 1) {
        return $oggetto['creatore'] == $_SESSION['login'] || in_array($oggetto['tipo'], [8, 9, 10]);
    }

    // Altri ruoli possono modificare solo oggetti del loro mestiere
    $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".$_SESSION['login']."'");

    switch($mestiere['id_mestiere']) {
        case 3: return $oggetto['tipo'] == 8; // Magic
        case 4: return $oggetto['tipo'] == 9; // Secret
        case 1: return $oggetto['tipo'] == 10; // ICC
        default: return false;
    }
}

function getFiltriCategoria($categoria) {
    $filtri = ['escluse' => [], 'permesse' => []];

    switch ($categoria) {
        case 'standard':
            $filtri['escluse'] = ['Arma di Gilda', 'Armi', 'Droga', 'Magic Shop', 'Medicine', 'Mods', 'STRIKE', 'Secret Pandora'];
            break;
        case 'arma':
            $filtri['permesse'] = ['Arma di Gilda', 'Armi', 'STRIKE', 'Secret Pandora'];
            break;
        case 'curativo':
            $filtri['permesse'] = ['Magic Shop', 'Medicine', 'STRIKE', 'Secret Pandora'];
            break;
        case 'statistica':
            $filtri['permesse'] = ['Droga', 'Magic Shop', 'Mods', 'STRIKE', 'Secret Pandora'];
            break;
        case 'magico':
            $filtri['permesse'] = ['Magic Shop', 'Secret Pandora', 'Generico', 'Gioielli'];
            break;
    }

    return $filtri;
}

function shouldShowTipo($tipo, $filtri) {
    $descrizione = gdrcd_filter('out', $tipo['descrizione']);

    if (!empty($filtri['escluse']) && in_array($descrizione, $filtri['escluse'])) {
        return false;
    }

    if (!empty($filtri['permesse']) && !in_array($descrizione, $filtri['permesse'])) {
        return false;
    }

    return true;
}

function prepareDatiBase($post) {
    session_start();

    return [
        'nome' => gdrcd_filter('in', $post['nome']),
        'descrizione' => gdrcd_filter('in', $post['descrizione']),
        'ubicabile' => (int) $post['ubicabile'],
        'tipo' => (int) $post['tipo'],
        'categoria' => gdrcd_filter('in', $post['categoria']),
        'creatore' => $_SESSION['login']
    ];
}

function getCampiSpecificiCategoria($categoria, $post) {
    $campi = [];

    switch($categoria) {
        case 'arma':
            $campi = [
                'tipo_arma' => (int) $post['tipo_arma'],
                'attacco' => (int) $post['bonus_arma'],
                'ricarica_massima' => (int) $post['ricarica_massima'],
                'richiede_ricarica' => 1,
                'cariche' => 1 // Default per armi
            ];
            break;

        case 'curativo':
            $campi = [
                'heal' => (int) $post['salute_integra'],
                'bonus_car0' => (int) $post['integrita_integra'],
                'cariche' => (int) $post['cariche_curativo'], // NOME CORRETTO
                'ricarica_massima' => 0, // Curativo non ha ricarica massima
                'richiede_ricarica' => 0
            ];

            break;

        case 'statistica':
            $campi = [
                'temp_giorni' => (int) $post['temp_giorni'],
                'isTemp' => 1,
                'cariche' => (int) $post['cariche_statistica'], // NOME CORRETTO
                'ricarica_massima' => (int) $post['ricarica_massima_statistica'], // NOME CORRETTO
                'richiede_ricarica' => 1,
                'bonus_car1_extra' => (int) $post['bonus_car1_extra'],
                'bonus_car2_extra' => (int) $post['bonus_car2_extra'],
                'bonus_car3_extra' => (int) $post['bonus_car3_extra'],
                'bonus_car4_extra' => (int) $post['bonus_car4_extra'],
                'bonus_car5_extra' => (int) $post['bonus_car5_extra']
            ];
            break;

        case 'magico':
            $campi = [
                'cariche' => (int) $post['cariche_magico'], // NOME CORRETTO
                'ricarica_massima' => (int) $post['ricarica_massima_magico'], // NOME CORRETTO
                'richiede_ricarica' => 1
            ];
            break;

        case 'standard':
            $cariche = ($post['cariche_standard'] === 'illimitato') ? 'illimitato' : (int) $post['cariche_standard']; // NOME CORRETTO
            $campi = [
                'cariche' => $cariche,
                'richiede_ricarica' => 0
            ];
            break;
    }

    return $campi;
}

/**
 * Valida e salva un'immagine caricata via $_FILES, con naming collision-proof.
 * Funzione centralizzata: usata da saveImgObj(), saveImgGuild() e da qualsiasi
 * altro modulo che debba gestire l'upload di un'immagine (es. mestieri).
 *
 * @param array       $file       Sotto-array $_FILES['campo'] (tmp_name, name, error, size)
 * @param string      $folder     Cartella di destinazione relativa alla root, es. 'imgs/items/'
 * @param string      $prefix     Prefisso del nome file generato, es. 'oggetto_'
 * @param string|null $imgEsistente Nome file da cancellare se viene sostituito
 * @param int         $maxBytes   Dimensione massima consentita (default 3 MB)
 * @return string|null Nome del nuovo file salvato, o null se non è stato caricato nulla
 * @throws Exception se il file è presente ma non valido (tipo, dimensione, errore upload)
 */
function saveUploadedImage(array $file, string $folder, string $prefix, ?string $imgEsistente = null, int $maxBytes = 3145728): ?string {
    if (empty($file['tmp_name'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Errore durante il caricamento del file.');
    }

    if ($file['size'] > $maxBytes) {
        throw new Exception('Immagine troppo grande (massimo ' . round($maxBytes / 1048576, 1) . ' MB).');
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Tipo file non supportato. Usa JPEG, PNG, GIF o WebP.');
    }

    $img_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nomeFile = $prefix . uniqid() . '.' . $img_ext;
    $target_file = __DIR__ . '/../' . $folder . '/' . $nomeFile;

    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        throw new Exception('Errore nel caricamento dell\'immagine');
    }

    if ($imgEsistente && file_exists(__DIR__ . '/../' . $folder . '/' . $imgEsistente)) {
        unlink(__DIR__ . '/../' . $folder . '/' . $imgEsistente);
    }

    return $nomeFile;
}

function saveImgObj($dati, $imgEsistente, $files) {
    $nomeFile = saveUploadedImage($files['img_oggetto'] ?? [], 'imgs/items', 'oggetto_', $imgEsistente);

    if ($nomeFile !== null) {
        $dati['urlimg'] = $nomeFile;
    } elseif ($imgEsistente) {
        $dati['urlimg'] = $imgEsistente;
    } else {
        $dati['urlimg'] = '';
    }

    return $dati;
}

function updateOggetto($id, $dati) {
    $setParts = [];

    foreach ($dati as $campo => $valore) {
        if ($valore === 'illimitato') $setParts[] = "`$campo` = 'illimitato'";
        else $setParts[] = "`$campo` = '" . gdrcd_filter('in', $valore) . "'";
    }

    gdrcd_query("UPDATE oggetto SET " . implode(', ', $setParts) . " WHERE id_oggetto = $id");
}

function insertOggetto($dati) {
    $campi = [];
    $valori = [];

    foreach ($dati as $campo => $valore) {
        $campi[] = "`$campo`";

        if ($valore === 'illimitato') $valori[] = "'illimitato'";
        else $valori[] = "'" . gdrcd_filter('in', $valore) . "'";
    }

    // Aggiungi data creazione
    $campi[] = "data_inserimento";
    $valori[] = "NOW()";

    gdrcd_query("INSERT INTO oggetto (" . implode(', ', $campi) . ") VALUES (" . implode(', ', $valori) . ")");
}
/************* FINE OGGETTI ******************************/



/************* PERSONAGGI ******************************/

/**
 * Modifica salute e/o integrità di un personaggio rispettando i cap di gioco.
 *
 * Cap: salute 0–100, integrità 0–10.
 * Usare sempre questa funzione anziché UPDATE diretti su questi due campi.
 *
 * @param string $nome             Nome del personaggio (viene filtrato internamente)
 * @param int    $delta_salute     Variazione PS (+/-)
 * @param int    $delta_integrita  Variazione integrità (+/-)
 * @return array|false  ['salute', 'integrita', 'delta_salute', 'delta_integrita']
 *                      oppure false se il personaggio non esiste
 */
function adjustPgStats(string $nome, int $delta_salute = 0, int $delta_integrita = 0): array|false {
    $nome_f = gdrcd_filter('in', $nome);
    $pg = gdrcd_query("SELECT salute, integrita, salute_max, integrita_max FROM personaggio WHERE nome = '$nome_f'");
    if (!$pg) return false;

    $prev_salute    = (int)$pg['salute'];
    $prev_integrita = (int)$pg['integrita'];
    $cap_salute     = max(1, (int)($pg['salute_max']    ?? 100));
    $cap_integrita  = max(1, (int)($pg['integrita_max'] ?? 10));

    $new_salute    = max(0, min($cap_salute,    $prev_salute    + $delta_salute));
    $new_integrita = max(0, min($cap_integrita, $prev_integrita + $delta_integrita));

    $updates = [];
    if ($new_salute    !== $prev_salute)    $updates[] = "salute = $new_salute";
    if ($new_integrita !== $prev_integrita) $updates[] = "integrita = $new_integrita";

    if (!empty($updates))
        gdrcd_query("UPDATE personaggio SET " . implode(', ', $updates) . " WHERE nome = '$nome_f'");

    return [
        'salute'          => $new_salute,
        'integrita'       => $new_integrita,
        'salute_max'      => $cap_salute,
        'integrita_max'   => $cap_integrita,
        'delta_salute'    => $new_salute    - $prev_salute,
        'delta_integrita' => $new_integrita - $prev_integrita,
    ];
}

function getPuntiPg($pg = '') {
    $where = $pg != '' ? " WHERE nome = '$pg'" : '';

    $query_punti = "SELECT
                        ROW_NUMBER() OVER (ORDER BY (tot_shin + tot_xp + shin_to_spend + punto_skill) DESC) AS riga,
                        nome,
                        esperienza,
                        punto_skill,
                        shin_to_spend,
                        tot_shin,
                        tot_xp,
                        (tot_shin + tot_xp + shin_to_spend + punto_skill) as tot
                    FROM (
                        SELECT
                            nome,
                            esperienza,
                            punto_skill,
                            CAST(COALESCE(shin, 0) AS SIGNED) as shin_to_spend,
                            SUM(COALESCE(car1, 0) + COALESCE(car3, 0) + COALESCE(car5, 0) + COALESCE(car7, 0) + COALESCE(car9, 0)) as tot_shin,
                            SUM((COALESCE(car0, 0) - COALESCE(car1, 0)) + (COALESCE(car2, 0) - COALESCE(car3, 0)) + (COALESCE(car4, 0) - COALESCE(car5, 0)) + (COALESCE(car6, 0) - COALESCE(car7, 0)) + (COALESCE(car8, 0) - COALESCE(car9, 0))) as tot_xp
                        FROM personaggio
                        $where
                        GROUP BY nome
                    ) AS subquery
                    ORDER BY tot DESC";

    $punti = $pg != '' ? gdrcd_query($query_punti) : gdrcd_query($query_punti, 'result');

    return $punti;
}

function getExp_rPg($esperienza) {
    // Esempio del calcolo su 1.000 punti esperienza: (100/5=20) + (400/10=40) + (300/15=20) + (200/20=10) = 90

    $scaglioni = [[100,5], [500,10], [800,15], [1200,20], [1600,25], [1000000,30]];
    $punti = 0;
    $prec = 0;

    foreach ($scaglioni as $scaglione) {
        if ($esperienza <= $prec) break;

        $limite = $scaglione[0];
        $divisore = $scaglione[1];
        $diff = min($esperienza, $limite) - $prec;

        if ($diff > 0) $punti += $diff / $divisore;

        $prec = $limite;
    }

    return (int)$punti;
}

/**
 * Azzera un personaggio: statistiche riportate a 10, shin spesi/da spendere/
 * skill consolidati in un unico pool spendibile, skill e talenti acquistati
 * rimossi (Talento/Skill temporanea esclusi), storico spese cancellato.
 * Stessa identica logica del pulsante "Reset punti" in Gestione > Gestione
 * Personaggi (api_personaggio.php op=resetPg) — centralizzata qui per poter
 * essere richiamata anche dalla cancellazione soft (api_account.php
 * op=delete), senza duplicare la query in due file.
 * $nomeFiltrato va gia' passato da gdrcd_filter('in', ...) a cura del chiamante
 * (stessa convenzione delle altre funzioni PERSONAGGI in questo file).
 */
function resetPuntiPg(string $nomeFiltrato): bool {
    $punti        = getPuntiPg($nomeFiltrato);
    $esperienza_r = getExp_rPg($punti['esperienza']);
    $tot_shin     = $punti['shin_to_spend'] + $punti['tot_shin'] + $punti['punto_skill'];

    $ok1 = gdrcd_query("UPDATE personaggio SET
                    esperienza_r = $esperienza_r,
                    car0 = 10, car2 = 10, car4 = 10, car6 = 10, car8 = 10,
                    car1 = 0, car3 = 0, car5 = 0, car7 = 0, car9 = 0,
                    shin = $tot_shin,
                    punto_skill = 0.0, esperienza_s = 0
                WHERE nome = '$nomeFiltrato'");
    $ok2 = gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome = '$nomeFiltrato' AND id_abilita NOT IN (SELECT id_abilita FROM abilita WHERE tipo IN ('Talento', 'Skill temporanea'))");
    $ok3 = gdrcd_query("DELETE FROM log_spesa WHERE nome = '$nomeFiltrato'");

    return (bool)$ok1 && (bool)$ok2 && (bool)$ok3;
}

/**
 * Scioglie tutti i legami di un personaggio con razza (clgpersonaggioruolo),
 * mestiere vero (clgpersonaggiomestiere) e gilde giocatore
 * (clgpersonaggioaffiliazione — puo' essercene piu' di una, limite separato
 * da quello dei mestieri), azzerando anche le colonne denormalizzate su
 * personaggio. Stesso pattern di "Abbandona Razza" (leaveGuild, api_gilda.php)
 * e di "Dimettiti/Espelli" (servizi_adm_mestieri.inc.php op=fire), applicato
 * pero' a TUTTE le affiliazioni del personaggio insieme invece che a una sola
 * per volta.
 *
 * Complementare a resetPuntiPg(), non sovrapposta: qui non si toccano
 * shin/statistiche/skill, ne' si sottraggono i bonus statistiche di razza
 * dalle car0-9 (a differenza di leaveGuild) perche' quando le due funzioni
 * vengono usate insieme (cancellazione soft, vedi api_account.php e
 * api_manutenzione.php op=missing_soft) le statistiche sono gia' azzerate a
 * monte da resetPuntiPg() — sottrarre di nuovo i bonus li porterebbe sotto
 * il valore base.
 * $nomeFiltrato va gia' passato da gdrcd_filter('in', ...) a cura del chiamante.
 */
function scioglieAffiliazioniPg(string $nomeFiltrato): bool {
    $ok1 = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nomeFiltrato'");
    $ok2 = gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE personaggio = '$nomeFiltrato'");
    $ok3 = gdrcd_query("DELETE FROM clgpersonaggioaffiliazione WHERE personaggio = '$nomeFiltrato'");
    $ok4 = gdrcd_query("UPDATE personaggio SET
                    id_gilda = 0, id_ruolo_gilda = 0,
                    id_mestiere = 0, id_ruolo_mestiere = 1
                WHERE nome = '$nomeFiltrato'");

    return (bool)$ok1 && (bool)$ok2 && (bool)$ok3 && (bool)$ok4;
}

function getTotStatsPg($pg) {
    $where = $pg != '' ? " WHERE nome = '$pg'" : '';

    return gdrcd_query("SELECT SUM(COALESCE(car2, 0) + COALESCE(car4, 0) + COALESCE(car6, 0) + COALESCE(car8, 0)) as tot_stats FROM personaggio $where")['tot_stats'];
}

/**
 * Bonus di abilità per i tiri "non basati sulla fortuna" (dado generico, skill generiche).
 * Pari alla media delle quattro caratteristiche (car2+car4+car6+car8), divisa per 10 e poi per 2, arrotondata per difetto.
 *
 * @param int $totStats Somma di car2+car4+car6+car8 del personaggio (vedi getTotStatsPg()).
 */
function getBonusAbilita(int $totStats): int {
    return (int) floor(($totStats / 4 / 10) / 2);
}

/**
 * Applica il bonus di abilità sommandolo direttamente all'esito del dado.
 *
 * @param int $dado     Esito grezzo del dado (es. mt_rand(1, 20)).
 * @param int $totStats Somma di car2+car4+car6+car8 del personaggio (vedi getTotStatsPg()).
 */
function applicaBonusAbilita(int $dado, int $totStats): int {
    return $dado + getBonusAbilita($totStats);
}

/**
 * Calcola il livello di un personaggio in base al totale delle sue caratteristiche.
 *
 * @param int   $totStats Somma di car2+car4+car6+car8 del personaggio.
 * @param array $soglie   Soglie precaricate [{livello, soglia}, …] ordinate per soglia ASC.
 *                        Se vuoto, vengono lette dal DB (usare il parametro per query in lista).
 */
function getLevelPg(int $totStats, array $soglie = []): int {
    if (empty($soglie)) {
        $res = gdrcd_query("SELECT livello, soglia FROM gilda_soglie ORDER BY soglia ASC", 'result');
        while ($row = gdrcd_query($res, 'fetch')) $soglie[] = $row;
    }

    $level = 1;
    foreach ($soglie as $row) {
        if ($totStats <= (int)$row['soglia']) return max(1, (int)$row['livello']);
        $level = (int)$row['livello'];
    }
    return $level;
}
/************* FINE PERSONAGGI ******************************/


/************* GILDA ******************************/
function saveImgGuild($dati, $imgEsistente, $files) {
    $nomeFile = saveUploadedImage($files['immagine'] ?? [], 'imgs/guilds', 'guild_', $imgEsistente);

    if ($nomeFile !== null) {
        $dati['immagine'] = $nomeFile;
    } elseif ($imgEsistente) {
        $dati['immagine'] = $imgEsistente;
    } else {
        $dati['immagine'] = '';
    }

    return $dati;
}
/************* FINE GILDA ******************************/

/************* MESTIERI / GILDE GIOCATORE ******************************/

/**
 * Vero se il personaggio occupa, tramite un suo ruolo, la posizione di capo
 * su questo mestiere. Controlla sia clgpersonaggiomestiere (mestieri veri,
 * tipo=1) che clgpersonaggioaffiliazione (gilde giocatore, tipo!=1): un dato
 * id_mestiere può avere righe solo in una delle due, quindi nessuna ambiguità.
 * Centralizzata qui perché usata sia da pages/api_mestieri.php che da
 * pages/servizi_adm_mestieri.inc.php.
 */
function mestiere_e_capo_di($login, $id_mestiere) {
    if ($id_mestiere <= 0) return false;
    $login_esc = gdrcd_filter('in', $login);
    $id_mestiere = (int)$id_mestiere;
    $r = gdrcd_query(
        "SELECT COUNT(*) AS n FROM (
            SELECT rm.capo FROM clgpersonaggiomestiere cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
            WHERE cpm.personaggio = '$login_esc' AND rm.mestiere = $id_mestiere AND rm.capo = 1
            UNION ALL
            SELECT rm.capo FROM clgpersonaggioaffiliazione cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
            WHERE cpm.personaggio = '$login_esc' AND rm.mestiere = $id_mestiere AND rm.capo = 1
         ) t"
    );
    return ((int)($r['n'] ?? 0)) > 0;
}

/**
 * Vero se il personaggio ha una qualunque affiliazione (anche non da capo)
 * su questo mestiere, tramite clgpersonaggiomestiere o clgpersonaggioaffiliazione.
 */
function mestiere_e_membro_di($login, $id_mestiere) {
    if ($id_mestiere <= 0 && $id_mestiere !== -1) return false;
    $login_esc = gdrcd_filter('in', $login);
    $id_mestiere = (int)$id_mestiere;
    $r = gdrcd_query(
        "SELECT COUNT(*) AS n FROM (
            SELECT cpm.personaggio FROM clgpersonaggiomestiere cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
            WHERE cpm.personaggio = '$login_esc' AND rm.mestiere = $id_mestiere
            UNION ALL
            SELECT cpm.personaggio FROM clgpersonaggioaffiliazione cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
            WHERE cpm.personaggio = '$login_esc' AND rm.mestiere = $id_mestiere
         ) t"
    );
    return ((int)($r['n'] ?? 0)) > 0;
}
/************* FINE MESTIERI / GILDE GIOCATORE ******************************/

/**
 * Frammento SQL condiviso: vero se il personaggio è da considerarsi online.
 * Centralizza una condizione che prima era duplicata testualmente in tre punti
 * (api_map.php op=presenti, op=presenti_estesi, themes/crystal/home/index.php)
 * — tenerle sincronizzate a mano è stata la causa di un blocco del sito.
 *
 * Richiede che la query chiamante usi l'alias "p" per personaggio e includa:
 *   LEFT JOIN bot_status bs ON bs.bot_nome = p.nome
 *
 * Un personaggio è online se: ha una sessione aperta (ora_entrata > ora_uscita)
 * con attività recente (o, per i bot, non in pausa) — oppure, a prescindere
 * dalla sessione, se personaggio.sempre_online è attivo. Il flag vive
 * direttamente su personaggio (non su privilegi, che si è rivelata più
 * fragile: gestione_nomine.inc.php itera dinamicamente ogni sua colonna).
 */
function gdrcd_condizione_online() {
    return "(
        (
            p.ora_entrata > p.ora_uscita
            AND (
                (p.sesso != 'b' AND DATE_ADD(p.ultimo_refresh, INTERVAL 4 MINUTE) > NOW())
                OR (p.sesso = 'b' AND COALESCE(bs.paused, 1) = 0)
            )
        )
        OR COALESCE(p.sempre_online, 0) = 1
    )";
}

/**
 * Conteggio utenti online sul sito intero, con le stesse esclusioni/visibilità
 * di 'presenti_estesi' (pages/api_map.php): usata sia da 'presenti_totale' sia
 * dal totale mostrato nel badge del popover "Presenti" (case 'presenti') —
 * un'unica implementazione invece di riscrivere la query in ogni endpoint, per
 * evitare che si scollino di nuovo (successo gia' una volta: il totale del
 * badge non contava lo staff sempre_online perche' la query era stata
 * ricopiata a mano invece di riusare gdrcd_condizione_online()).
 */
function gdrcd_conteggio_online_totale(string $login, bool $is_staff): int {
    if ($login === 'Mino' || $login === 'Lii') {
        $exclude = '';
    } elseif ($login === 'Jamal' || $login === 'Alice') {
        $exclude = "AND p.nome NOT IN ('Megan', 'Niklaus')";
    } else {
        $exclude = "AND p.nome != 'Mino'";
    }

    $condizione_online = gdrcd_condizione_online();
    $invisible_filter  = $is_staff ? '' : 'AND p.is_invisible = 0';

    $tot = gdrcd_query(
        "SELECT COUNT(*) AS n
         FROM personaggio p
         LEFT JOIN bot_status bs ON bs.bot_nome = p.nome
         WHERE $condizione_online
           $exclude
           $invisible_filter"
    );

    return (int)$tot['n'];
}

/************* FORUM / QUEST ******************************/

/**
 * Recupera lo stato quest di una role_sessions, verificando che sia effettivamente
 * una quest (is_quest=1). Centralizza il controllo ripetuto identico in
 * getQuestRecapData e saveQuestRecap (api_roleSession.php).
 *
 * @return array|null ['is_quest' => 1, 'quest_recap_thread_id' => int|null], null se la
 *                     giocata non esiste o non è una quest.
 */
function getQuestRoleRow(int $id_role): ?array {
    $role = gdrcd_query("SELECT is_quest, quest_recap_thread_id FROM role_sessions WHERE id_role = $id_role");
    return ($role && !empty($role['is_quest'])) ? $role : null;
}

/**
 * Nome della mappa/location associata a una role_sessions. Centralizza una query
 * ripetuta in modo leggermente diverso in tre punti (awardShin, getQuestRecapData,
 * saveQuestRecap in api_roleSession.php).
 *
 * @return string Nome della location, stringa vuota se non trovata.
 */
function getRoleLocationName(int $id_role): string {
    $row = gdrcd_query("SELECT mappa.nome FROM role_sessions rs JOIN mappa ON mappa.id = rs.location WHERE rs.id_role = $id_role");
    return $row['nome'] ?? '';
}

/**
 * Cancellazione soft dei personaggi: la riga resta in `personaggio`, viene
 * solo marcata con permessi = DELETED (vedi api_account.php op=delete/
 * restore, api_manutenzione.php op=MISSING_SOFT). Centralizza qui la regola
 * di controllo cosi' cambiarla (es. introdurre altri stati di cancellazione)
 * richiede di toccare un solo punto invece di ogni query sparsa nel codice.
 */

/** Frammento SQL da mettere in AND: seleziona solo i personaggi NON cancellati. */
function sqlPgAttivo(string $colonna = 'permessi'): string {
    return "$colonna > " . DELETED;
}

/** Complementare a sqlPgAttivo(): frammento SQL per i soli personaggi cancellati. */
function sqlPgCancellato(string $colonna = 'permessi'): string {
    return "$colonna = " . DELETED;
}

/** Versione booleana, per quando il valore di permessi e' gia' stato letto (non una query). */
function isPgCancellato($permessi): bool {
    return (int)$permessi === DELETED;
}

// Helper: verifica se l'utente corrente può accedere a una sezione araldo.
// Spostata qui da api_forum.php (era locale al file) perché ora serve anche
// a createQuestPost(), condivisa con la generazione quest da role_recap.
function can_access_section(array $section): bool {
    $tipo        = (int)$section['tipo'];
    $proprietari = $section['proprietari'];

    // Capo-mestiere che ha confermato il mestiere
    $con_job = false;
    if ($tipo == SOLOMESTIERE) {
        $pg_m = gdrcd_query("SELECT conferma_mestiere FROM clgpersonaggiomestiere WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1");
        $con_job = ($pg_m && $pg_m['conferma_mestiere'] == 1);
    }

    if ($_SESSION['admin'] == 1) return true;

    return match($tipo) {
        ONGAME, INFO, COMUNICAZIONI, PERTUTTI => true,
        SOLORAZZA        => $_SESSION['id_razza'] == $proprietari,
        SOLOGILDA        => strpos($_SESSION['gilda'] ?? '', '*' . $proprietari . '*') !== false,
        SOLOMESTIERE     => ($_SESSION['mestiere'] == $proprietari && $con_job) || $_SESSION['capomestiere'] == 1,
        SOLOMASTERS      => $_SESSION['master'] == 1,
        SOLOMODERATORS   => $_SESSION['moderatore'] == 1,
        SOLOGUIDES       => $_SESSION['guida'] == 1,
        SOLOCAPOGILDA    => $_SESSION['capogilda'] == 1,
        SOLOCAPOMESTIERI => $_SESSION['capomestiere'] == 1,
        SOLOADMIN        => $_SESSION['admin'] == 1,
        default          => false,
    };
}

/**
 * Crea un post quest nella bacheca (araldo), inserisce i dati grezzi in
 * messaggio_quest e assegna esperienza/shin/mestiere/notorietà al master e ai
 * partecipanti. Stessa logica usata sia dal composer manuale nel forum
 * (api_forum.php?op=post_quest) sia dalla generazione automatica da role_recap
 * (api_roleSession.php?op=saveQuestRecap) — centralizzata per non doverle
 * tenere allineate a mano in due punti diversi.
 *
 * @param array $pg_punti [{nome, exp, shin, notorieta, mestiere}, ...]
 * @return int|null id del thread creato, null se la sezione non è valida/accessibile
 */
function createQuestPost(int $araldo_id, int $padre, string $titolo, string $tipologia, string $partec, string $location, string $riassunto, string $cons, string $note, string $valu, array $pg_punti, string $login): ?int {
    $section = gdrcd_query("SELECT id_araldo, tipo, proprietari, punti FROM araldo WHERE id_araldo = $araldo_id AND invisibile = 0 LIMIT 1");
    if (!$section || !can_access_section($section)) return null;

    // Corpo HTML formattato della quest (identico al vecchio insert_quest.inc.php)
    $t_h  = htmlspecialchars($titolo);
    $tp_h = htmlspecialchars($tipologia);
    $l_h  = htmlspecialchars($location);
    $p_h  = htmlspecialchars($partec);
    $r_h  = htmlspecialchars($riassunto);
    $c_h  = htmlspecialchars($cons);
    $n_h  = htmlspecialchars($note);
    $v_h  = htmlspecialchars($valu);

    $testo_quest = "<center>
<font color=\"#9a6353\" style=\"font-size:20px; text-transform: uppercase;\"><b>$t_h</b></font><br>
<font color=\"#9a6353\" style=\"font-size:12px;\">$tp_h</font>
</center><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Luogo</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$l_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Partecipanti</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$p_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Riassunto</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$r_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Conseguenze</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$c_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Note</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$n_h</font><br><br>
<font color=\"#e8967e\" style=\"font-size:12px;\">Valutazioni</font><br>
<font color=\"#8f8f8f\" style=\"font-size:12px; text-align: justify;\">$v_h</font>";

    $login_f = gdrcd_filter('in', $login);

    // Inserisce il post nella bacheca
    gdrcd_query("INSERT INTO messaggioaraldo
        (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, giornalista, anonimo)
        VALUES ($padre, $araldo_id, '" . gdrcd_filter('in', $titolo) . "', '" . gdrcd_filter('in', $testo_quest) . "',
        '$login_f', NOW(), NOW(), 'no', 'no')");

    $new_id    = (int)gdrcd_query('', 'last_id');
    $thread_id = ($padre == -1) ? $new_id : $padre;

    if ($padre != -1) {
        gdrcd_query("UPDATE messaggioaraldo SET data_ultimo_messaggio = NOW() WHERE id_messaggio = $padre");
        gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = $padre AND nome != '$login_f'");
    }

    // Inserisce i dati grezzi nella tabella messaggio_quest
    gdrcd_query("INSERT INTO messaggio_quest
        (id_messaggio, autore, titolo, location, partecipanti, riassunto, conseguenze, note, valutazioni, tipologia)
        VALUES ($thread_id, '$login_f',
        '" . gdrcd_filter('in', $titolo)    . "',
        '" . gdrcd_filter('in', $location)  . "',
        '" . gdrcd_filter('in', $partec)    . "',
        '" . gdrcd_filter('in', $riassunto) . "',
        '" . gdrcd_filter('in', $cons)      . "',
        '" . gdrcd_filter('in', $note)      . "',
        '" . gdrcd_filter('in', $valu)      . "',
        '" . gdrcd_filter('in', $tipologia) . "')");

    // XP al master in base alla tipologia
    $master_xp = match(true) {
        in_array($tipologia, ['Quest Singola', 'Evento']) => 2,
        $tipologia === 'Quest di Gilda'                    => 1,
        $tipologia === 'Assegnazione Esperienza o Notorietà' => 0,
        default                                            => 3,
    };

    if ($master_xp > 0) {
        gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, id_messaggio, commento)
            VALUES ('$login_f', '$master_xp', NOW(), '$thread_id', 'Master della quest')");
        gdrcd_query("UPDATE personaggio SET esperienza = esperienza + $master_xp, esperienza_r = esperienza_r + $master_xp
            WHERE nome = '$login_f'");
    }

    // Punti ai partecipanti (solo se la sezione lo prevede)
    if ((int)$section['punti'] > 0) {
        foreach ($pg_punti as $pg) {
            $pg_nome = trim($pg['nome'] ?? '');
            $pg_exp  = (float)($pg['exp']      ?? 0);
            $pg_shin = (float)($pg['shin']     ?? 0);
            $pg_not  = (int)($pg['notorieta']  ?? 0);
            $pg_mest = (float)($pg['mestiere'] ?? 0);

            if ($pg_nome === '' || ($pg_exp == 0 && $pg_shin == 0 && $pg_not == 0 && $pg_mest == 0)) continue;

            $pg_f = gdrcd_filter('in', $pg_nome);
            gdrcd_query("INSERT INTO Punti (nome, esperienza, notorieta, mestiere, shin, data_evento, id_messaggio, commento)
                VALUES ('$pg_f', '$pg_exp', '$pg_not', '$pg_mest', '$pg_shin', NOW(), '$thread_id', '')");
            gdrcd_query("UPDATE personaggio SET
                esperienza = esperienza + '$pg_exp',
                esperienza_r = esperienza_r + '$pg_exp',
                notorieta = notorieta + '$pg_not',
                esperienza_mestiere = esperienza_mestiere + '$pg_mest',
                shin = shin + '$pg_shin'
                WHERE nome = '$pg_f'");
        }
    }

    notifySocketServer('forum:update', 'global', ['araldo_id' => $araldo_id, 'thread_id' => $thread_id]);

    return $thread_id;
}

/**
 * Genera un riassunto narrativo (max 800 token) chiamando Claude Haiku sui messaggi
 * master (tipo 'M') di una giocata — stesso pattern di chiamata di api_chatbot.php.
 * Usata da api_roleSession.php?op=saveQuestRecap al posto del campo "riassunto"
 * libero del composer manuale nel forum.
 *
 * @return string Riassunto generato, stringa vuota se non ci sono messaggi master
 *                o se la chiamata fallisce (log dell'errore, il salvataggio della
 *                quest prosegue comunque senza bloccarsi).
 */
function generateQuestRiassunto(int $id_role): string {
    $result = gdrcd_query("SELECT mittente, testo FROM chat WHERE id_role = $id_role AND tipo = 'M' ORDER BY ora ASC", 'result');
    $azioni = [];
    while ($row = gdrcd_query($result, 'fetch')) {
        $azioni[] = trim($row['mittente'] . ': ' . strip_tags($row['testo']));
    }
    gdrcd_query($result, 'free');

    if (empty($azioni)) return '';

    $api_key = $GLOBALS['PARAMETERS']['anthropic']['api_key'] ?? '';
    if (empty($api_key)) {
        error_log('[quest_recap] api_key Anthropic non configurata in config.inc.php');
        return '';
    }

    $system = "Sei un narratore che riassume le azioni di una giocata di ruolo (GDR) a partire dalle "
            . "azioni testuali del master. Scrivi un riassunto narrativo in italiano, chiaro e scorrevole, "
            . "che copra TUTTI gli eventi principali nell'ordine in cui sono accaduti, anche se le azioni "
            . "fornite sono molte: non troncare né tralasciare eventi per stare corto, piuttosto scrivi in "
            . "modo più sintetico frase per frase. Non inventare eventi non presenti nel testo fornito. Il "
            . "testo verrà pubblicato in un forum che interpreta il BBCode, non il Markdown: per la "
            . "formattazione usa ESCLUSIVAMENTE tag BBCode (es. [b]testo[/b] per il grassetto, [i]testo[/i] "
            . "per il corsivo, [u]testo[/u] per il sottolineato) — non usare mai gli asterischi ** o * "
            . "tipici del Markdown.";

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        // 800 tagliava a meta' il riassunto nelle quest con molte azioni master: il
        // modello si fermava esattamente al limite di token, a prescindere da dove
        // fosse arrivato nel racconto. Alzato con margine ampio: resta comunque un
        // riassunto (vedi system prompt), non una trascrizione integrale.
        'max_tokens' => 4000,
        'system'     => $system,
        'messages'   => [
            ['role' => 'user', 'content' => implode("\n\n", $azioni)],
        ],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("[quest_recap] curl error: $curlErr");
        return '';
    }
    if ($httpCode !== 200) {
        error_log("[quest_recap] Anthropic API HTTP $httpCode: $response");
        return '';
    }

    $result_data = json_decode($response, true);
    return trim($result_data['content'][0]['text'] ?? '');
}
/************* FINE FORUM / QUEST ******************************/

/**
 * Filtro HTML permissivo: toglie solo i vettori di XSS reali (script,
 * attributi on*, javascript:), a differenza di gdrcd_html_filter() che con
 * $PARAMETERS['settings']['html'] = HTML_FILTER_HIGH blocca anche <img> e
 * <iframe> sostituendoli con testo d'errore visibile ("Immagini non
 * consentite"/"Frame non consentiti"). Per campi che storicamente venivano
 * mostrati senza alcun filtro (es. contenuto affetti, echo diretto nel PHP
 * legacy) l'obiettivo è restare compatibili con quel comportamento, non
 * aggiungere restrizioni nuove — vedi affetto_get in api_scheda.php.
 */
function gdrcd_html_filter_permissivo(string $str): string {
    $notAllowed = [
        "#(<script.*?>.*?(<\/script>)?)#is"      => '',
        "#( on[a-zA-Z]+=\"?'?[^\s\"']+'?\"?)#is" => '',
        "#(javascript:[^\s\"']+)#is"              => '',
    ];
    return preg_replace(array_keys($notAllowed), array_values($notAllowed), $str);
}
?>