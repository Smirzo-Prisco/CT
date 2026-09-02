<?php
/**
 * api_luoghi.php — Endpoint JSON per il pannello React "Gestione luoghi"
 * (sostituisce pages/gestione_luoghi.inc.php, PHP monolitico senza AJAX)
 *
 * Gestisce la tabella mappa (le stanze/luoghi del gioco). Campi runtime
 * gestiti da altre funzionalità (invitati, ora_prenotazione,
 * limite_lunghezza_massima/timestamp_modifica_limite — prenotazioni e limiti
 * caratteri per messaggio, mai stati nel vecchio form neanche loro) restano
 * fuori da questo endpoint di proposito.
 *
 * op = list | meta | get | save | delete   (solo admin)
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if (($_SESSION['admin'] ?? 0) != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    case 'list':
        $per_page = (int)$PARAMETERS['settings']['records_per_page'];
        $offset   = max(0, (int)($_GET['offset'] ?? 0)) * $per_page;

        $totale = (int)(gdrcd_query("SELECT COUNT(*) AS n FROM mappa")['n'] ?? 0);

        $luoghi = [];
        $result = gdrcd_query("
            SELECT m.id, m.nome, mc.nome AS mappa_nome, m.chat, m.privata
            FROM mappa m
            LEFT JOIN mappa_click mc ON mc.id_click = m.id_mappa
            ORDER BY m.nome
            LIMIT $offset, $per_page
        ", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $luoghi[] = [
                'id'         => (int)$row['id'],
                'nome'       => $row['nome'],
                'mappa_nome' => $row['mappa_nome'] ?? '—',
                'chat'       => (int)$row['chat'],
                'privata'    => (int)$row['privata'],
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'luoghi' => $luoghi, 'totale' => $totale, 'per_page' => $per_page]);
        break;

    // Dati di supporto per il form: elenco mappe (mappa_click), gilde,
    // personaggi (per Proprietario) e se le stanze private sono abilitate —
    // un'unica chiamata invece di 4 separate ad ogni apertura form.
    case 'meta':
        $mappe = [];
        $result = gdrcd_query("SELECT id_click, nome FROM mappa_click ORDER BY nome", 'result');
        while ($row = gdrcd_query($result, 'fetch')) $mappe[] = ['id' => (int)$row['id_click'], 'nome' => $row['nome']];
        gdrcd_query($result, 'free');

        $gilde = [];
        $result = gdrcd_query("SELECT id_gilda, nome FROM gilda ORDER BY nome", 'result');
        while ($row = gdrcd_query($result, 'fetch')) $gilde[] = ['id' => (int)$row['id_gilda'], 'nome' => $row['nome']];
        gdrcd_query($result, 'free');

        $personaggi = [];
        $result = gdrcd_query("SELECT nome, cognome FROM personaggio WHERE permessi >= 0 ORDER BY nome", 'result');
        while ($row = gdrcd_query($result, 'fetch')) $personaggi[] = ['nome' => $row['nome'], 'cognome' => $row['cognome']];
        gdrcd_query($result, 'free');

        echo json_encode([
            'success'            => true,
            'mappe'              => $mappe,
            'gilde'              => $gilde,
            'personaggi'         => $personaggi,
            'privaterooms'       => ($PARAMETERS['mode']['privaterooms'] ?? 'OFF') === 'ON',
        ]);
        break;

    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = gdrcd_query("SELECT * FROM mappa WHERE id = $id LIMIT 1");
        if ($row) {
            echo json_encode(['success' => true, 'luogo' => [
                'id'                  => (int)$row['id'],
                'nome'                => $row['nome'],
                'descrizione'         => $row['descrizione'],
                'stato'               => $row['stato'],
                'chat'                => (int)$row['chat'],
                'immagine'            => $row['immagine'],
                'descrizione_immagine'=> $row['descrizione_immagine'],
                'link_immagine'       => $row['link_immagine'],
                'link_immagine_hover' => $row['link_immagine_hover'],
                'pagina'              => $row['pagina'],
                'stanza_apparente'    => $row['stanza_apparente'],
                'id_mappa'            => (int)$row['id_mappa'],
                'id_mappa_collegata'  => (int)$row['id_mappa_collegata'],
                'x_cord'              => (int)$row['x_cord'],
                'y_cord'              => (int)$row['y_cord'],
                'privata'             => (int)$row['privata'],
                'proprietario'        => $row['proprietario'],
                'scadenza'            => $row['scadenza'],
                'costo'               => (int)$row['costo'],
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Luogo non trovato']);
        }
        break;

    case 'save':
        $id                  = (int)($_POST['id'] ?? 0);
        $nome                = trim($_POST['nome'] ?? '');
        $descrizione         = gdrcd_filter('in', $_POST['descrizione'] ?? '');
        $stato               = gdrcd_filter('in', $_POST['stato'] ?? '');
        $chat                = !empty($_POST['chat']) ? 1 : 0;
        $link_immagine       = gdrcd_filter('in', $_POST['link_immagine'] ?? '');
        $link_immagine_hover = gdrcd_filter('in', $_POST['link_immagine_hover'] ?? '');
        $pagina              = gdrcd_filter('in', $_POST['pagina'] ?? '');
        $stanza_apparente    = gdrcd_filter('in', $_POST['stanza_apparente'] ?? '');
        $id_mappa            = (int)($_POST['id_mappa'] ?? -1);
        $id_mappa_collegata  = (int)($_POST['id_mappa_collegata'] ?? 0);
        $x_cord              = (int)($_POST['x_cord'] ?? 0);
        $y_cord              = (int)($_POST['y_cord'] ?? 0);

        $privaterooms_on = ($PARAMETERS['mode']['privaterooms'] ?? 'OFF') === 'ON';
        $privata      = ($privaterooms_on && !empty($_POST['privata'])) ? 1 : 0;
        $proprietario = ($privaterooms_on && $privata) ? gdrcd_filter('in', trim($_POST['proprietario'] ?? '')) : '';
        $costo        = $privaterooms_on ? (int)($_POST['costo'] ?? 0) : 0;
        $scadenza     = ($privaterooms_on && !empty($_POST['scadenza'])) ? "'" . gdrcd_filter('in', $_POST['scadenza']) . " 00:00:00'" : 'NULL';

        if ($nome === '') {
            echo json_encode(['success' => false, 'message' => 'Il nome è obbligatorio']);
            exit;
        }

        // Immagini: due file separati e indipendenti — "immagine" (icona/sfondo
        // stanza, imgs/locations) e "descrizione_immagine" (illustrazione nel
        // popup descrizione luogo, imgs/descrizioni — usata da Hud.jsx/ChatViewer.jsx,
        // esisteva in DB ma il vecchio form non la esponeva affatto).
        $esistente = $id > 0 ? gdrcd_query("SELECT immagine, descrizione_immagine FROM mappa WHERE id = $id") : null;

        try {
            $nuova_immagine = saveUploadedImage($_FILES['immagine'] ?? [], 'themes/crystal/imgs/locations', 'luogo_', $esistente['immagine'] ?? null);
            $nuova_descr_img = saveUploadedImage($_FILES['descrizione_immagine'] ?? [], 'themes/crystal/imgs/descrizioni', 'descr_', $esistente['descrizione_immagine'] ?? null);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        $immagine      = $nuova_immagine ?? ($esistente['immagine'] ?? 'standard_luogo.png');
        $descr_img     = $nuova_descr_img ?? ($esistente['descrizione_immagine'] ?? 'standard_luogo.png');

        $nome_esc             = gdrcd_filter('in', $nome);
        $immagine_esc         = gdrcd_filter('in', $immagine);
        $descr_img_esc        = gdrcd_filter('in', $descr_img);
        $proprietario_sql     = $proprietario !== '' ? "'$proprietario'" : 'NULL';

        if ($id > 0) {
            gdrcd_query("UPDATE mappa SET
                nome = '$nome_esc', descrizione = '$descrizione', stato = '$stato', chat = $chat,
                immagine = '$immagine_esc', descrizione_immagine = '$descr_img_esc',
                link_immagine = '$link_immagine', link_immagine_hover = '$link_immagine_hover',
                pagina = '$pagina', stanza_apparente = '$stanza_apparente',
                id_mappa = $id_mappa, id_mappa_collegata = $id_mappa_collegata,
                x_cord = $x_cord, y_cord = $y_cord,
                privata = $privata, proprietario = $proprietario_sql, scadenza = $scadenza, costo = $costo
                WHERE id = $id LIMIT 1");
        } else {
            gdrcd_query("INSERT INTO mappa
                (nome, descrizione, stato, chat, immagine, descrizione_immagine, link_immagine, link_immagine_hover,
                 pagina, stanza_apparente, id_mappa, id_mappa_collegata, x_cord, y_cord, privata, proprietario, scadenza, costo, invitati)
                VALUES ('$nome_esc', '$descrizione', '$stato', $chat, '$immagine_esc', '$descr_img_esc', '$link_immagine', '$link_immagine_hover',
                 '$pagina', '$stanza_apparente', $id_mappa, $id_mappa_collegata, $x_cord, $y_cord, $privata, $proprietario_sql, $scadenza, $costo, '')");
        }

        echo json_encode(['success' => true, 'message' => $id > 0 ? 'Luogo aggiornato.' : 'Luogo creato.']);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID non valido']);
            exit;
        }

        // Protezioni assenti nel vecchio codice: nessun vincolo di integrità
        // nello schema su questi riferimenti a mappa.id.
        $mestiere_dipendente = gdrcd_query("SELECT nome FROM mestiere WHERE id_luogo = $id LIMIT 1");
        if ($mestiere_dipendente) {
            echo json_encode(['success' => false, 'message' => "Impossibile eliminare: è il luogo di lavoro del mestiere «{$mestiere_dipendente['nome']}». Riassegnalo prima da Gestione luoghi mestiere."]);
            exit;
        }

        $n_pg = (int)(gdrcd_query("SELECT COUNT(*) AS n FROM personaggio WHERE ultimo_luogo = $id AND permessi >= 0")['n'] ?? 0);
        if ($n_pg > 0) {
            echo json_encode(['success' => false, 'message' => "Impossibile eliminare: $n_pg personaggi hanno questo luogo come ultima posizione nota. Spostali prima di eliminare la stanza."]);
            exit;
        }

        gdrcd_query("DELETE FROM mappa WHERE id = $id LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Luogo eliminato.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
