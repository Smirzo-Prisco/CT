<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

function send_mail($to, $subject, $message) {
    $from = $GLOBALS['PARAMETERS']['info']['webmaster_email'];
    $headers = 'From: ' . $from . "\r\n" .
        'Reply-To: ' . $from . "\r\n" .
        'X-Mailer: PHP/' . phpversion() . "\r\n" .
        'Content-type: text/html; charset=UTF-8' . "\r\n";

    return (bool) mail($to, $subject, $message, $headers);
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

    echo "<div class='success' style='text-align: center; margin-top: 20px;'>
            Oggetto <b>$nome</b> aggiunto con successo!
          </div>";

    echo "<div style='text-align: center; margin-top: 15px;'>
        <a href='main.php?page=oggetto_aggiungi' class='ares' style='background-color: #0f111d; padding: 5px 10px; text-decoration: none; color: #ce846f; border-radius: 5px; margin: 5px; display: inline-block;'>
            Torna al caricamento oggetti
        </a>
        <a href='main.php?page=oggetto_assegna&id_oggetto=$id_oggetto' class='ares' style='background-color: #0f111d; padding: 5px 10px; text-decoration: none; color: #ce846f; border-radius: 5px; margin: 5px; display: inline-block;'>
            Assegna l'oggetto
        </a>
        <a href='main.php?page=oggetto_mercato&id_oggetto=$id_oggetto' class='ares' style='background-color: #0f111d; padding: 5px 10px; text-decoration: none; color: #ce846f; border-radius: 5px; margin: 5px; display: inline-block;'>
            Inserisci nel mercato
        </a>
        <a href='main.php?page=oggetto_modifica&id_oggetto=$id_oggetto' class='ares' style='background-color: #0f111d; padding: 5px 10px; text-decoration: none; color: #ce846f; border-radius: 5px; margin: 5px; display: inline-block;'>
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
function send_sms($from, $to, $title, $text) {
    // Devo verificare se esiste già una conversazione in corso tra mittente e destinatario
    $exists = gdrcd_query("SELECT id_conversazione FROM sms WHERE mittente_nome = '".gdrcd_filter('in', $from)."' AND destinatario_nome = '".gdrcd_filter('in', $to)."' LIMIT 1", 'result');

    if (gdrcd_query($exists, 'num_rows') > 0) {
        // Esiste già una conversazione, inserisco un nuovo sms nella conversazione
        $id_conversazione = gdrcd_query($exists, 'fetch')['id_conversazione'];

        gdrcd_query("INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                    VALUES ('".gdrcd_filter('in', $from)."', '".gdrcd_filter('in', $to)."', '$text', $id_conversazione, 'individuale', '0', NOW())");

        // Segno l'sms come da leggere
        gdrcd_query("UPDATE conversazioni_individuali SET lettura = 0 WHERE id_conversazione = $id_conversazione");
    } else {
        // Non esiste una conversazione, ne creo una nuova generando un nuovo id
        $new_id = (gdrcd_query(gdrcd_query("SELECT MAX(id_conversazione) as max_id FROM sms", 'result'), 'fetch')['max_id'] + 1);

        // Inserisco prima l'sms
        gdrcd_query("INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                    VALUES ('".gdrcd_filter('in', $from)."', '".gdrcd_filter('in', $to)."', '$text', $new_id, 'individuale', '0', NOW())");

        // Poi inserisco la nuova conversazione
        gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                    VALUES ($new_id, '" . gdrcd_filter('in', $to) . "', 0)");
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

function getLevelPg($totStats) {
    $soglie = gdrcd_query("SELECT livello, soglia FROM gilda_soglie ORDER BY soglia ASC", 'result');
    $level = 1;

    while($row = gdrcd_query($soglie, 'fetch')) {
        if ($totStats <= (int)$row['soglia']) return (int)$row['livello']-1; // Considero il livello precedente

        $level = (int)$row['livello'];
    }

    return $level; // se supera tutte le soglie
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