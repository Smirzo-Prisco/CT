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
function send_sms($from, $to, $title, $text, $ongame = 0) {
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

        // Riga per entrambi: destinatario non letto, mittente già letto
        gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($new_id, '$to_safe',   0)");
        gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($new_id, '$from_safe', 1)");
    }

    notifySocketServer('dm:update', 'dm:' . $to);
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

function saveImgObj($dati, $imgEsistente = null, $files) {
    if (!empty($files['img_oggetto']['tmp_name']) && $files['img_oggetto']['error'] === UPLOAD_ERR_OK) {
        // Validazione file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $files['img_oggetto']['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Tipo file non supportato. Usa JPEG, PNG, GIF o WebP.');
        }

        // Upload nuova immagine
        $img_ext = strtolower(pathinfo($files['img_oggetto']['name'], PATHINFO_EXTENSION));
        $nomeFile = "oggetto_".uniqid().".".$img_ext;
        $target_file = __DIR__ . "/../imgs/items/" . $nomeFile;

        if (!move_uploaded_file($files['img_oggetto']['tmp_name'], $target_file)) {
            throw new Exception('Errore nel caricamento dell\'immagine');
        }

        $dati['urlimg'] = $nomeFile;

        // Elimina vecchia immagine se esiste
        if ($imgEsistente && file_exists(__DIR__ . "/../imgs/items/" . $imgEsistente)) {
            unlink(__DIR__ . "/../imgs/items/" . $imgEsistente);
        }
    } elseif ($imgEsistente && empty($files['img_oggetto']['tmp_name'])) {
        // Mantieni immagine esistente
        $dati['urlimg'] = $imgEsistente;
    } else {
        // Nessuna immagine
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

function getTotStatsPg($pg) {
    $where = $pg != '' ? " WHERE nome = '$pg'" : '';

    return gdrcd_query("SELECT SUM(COALESCE(car2, 0) + COALESCE(car4, 0) + COALESCE(car6, 0) + COALESCE(car8, 0)) as tot_stats FROM personaggio $where")['tot_stats'];
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
function saveImgGuild($dati, $imgEsistente = null, $files) {
    if (!empty($files['immagine']['tmp_name']) && $files['immagine']['error'] === UPLOAD_ERR_OK) {
        $img = $files['immagine'];

        // Validazione file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $img['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Tipo file non supportato. Usa JPEG, PNG, GIF o WebP.');
        }

        // Upload nuova immagine
        $img_ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $nomeFile = "guild_".uniqid().".".$img_ext;
        $target_file = __DIR__ . "/../imgs/guilds/" . $nomeFile;

        if (!move_uploaded_file($img['tmp_name'], $target_file)) {
            throw new Exception('Errore nel caricamento dell\'immagine');
        }

        $dati['immagine'] = $nomeFile;

        // Elimina vecchia immagine se esiste
        if ($imgEsistente && file_exists(__DIR__ . "/../imgs/guilds/" . $imgEsistente)) {
            unlink(__DIR__ . "/../imgs/guilds/" . $imgEsistente);
        }
    } elseif ($imgEsistente && empty($img['tmp_name'])) {
        // Mantieni immagine esistente
        $dati['immagine'] = $imgEsistente;
    } else {
        // Nessuna immagine
        $dati['immagine'] = '';
    }

    return $dati;
}
/************* FINE GILDA ******************************/
?>