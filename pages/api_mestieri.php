<?php
/**
 * api_mestieri.php — Endpoint JSON per il pannello React "Gestione mestieri"
 * e per l'auto-gestione delle gilde giocatore (mestiere.tipo != 1)
 *
 * op = list | get | tipi | save | hide | save_ruolo | delete_ruolo
 *      (solo admin — pannello di sistema, tutti i mestieri inclusi i globali)
 * op = mia_gilda | crea_gilda
 *      (chiunque sia loggato — riguardano solo la propria eventuale gilda)
 * op = dismetti_gilda | riassegna_capo
 *      (solo admin — interventi su una gilda giocatore)
 *
 * save / save_ruolo / delete_ruolo accettano anche il capo di una gilda
 * (mestiere.tipo != 1), non solo l'admin: l'autorizzazione è verificata
 * riga per riga tramite mestiere_e_capo_di(), mai tramite $_SESSION['capomestiere']
 * (quella è una nomina di staff su TUTTI i mestieri, cosa diversa).
 *
 * Letture (list/get/tipi/mia_gilda) via GET, scritture via POST (multipart
 * quando è coinvolta un'immagine, altrimenti form-urlencoded — $_POST
 * funziona identico in entrambi i casi).
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

$op       = $_GET['op'] ?? '';
$is_admin = (($_SESSION['admin'] ?? 0) == 1);
$login    = gdrcd_filter('in', $_SESSION['login']);

/** Ricarica un mestiere con la descrizione del tipo, per le risposte */
function carica_mestiere($id) {
    return gdrcd_query(
        "SELECT m.*, c.descrizione AS tipo_descrizione
         FROM mestiere m LEFT JOIN codtipomestiere c ON m.tipo = c.cod_tipo
         WHERE m.id_mestiere = " . (int)$id
    );
}

/** Ricarica l'elenco ruoli di un mestiere */
function carica_ruoli($id_mestiere) {
    $ruoli  = [];
    $result = gdrcd_query(
        "SELECT * FROM ruolo_mestiere WHERE mestiere = " . (int)$id_mestiere . " ORDER BY capo DESC, stipendio DESC",
        'result'
    );
    while ($row = gdrcd_query($result, 'fetch')) {
        $ruoli[] = $row;
    }
    gdrcd_query($result, 'free');
    return $ruoli;
}

// e_capo_di() -> mestiere_e_capo_di(), centralizzata in includes/custom_functions.inc.php
// (riusata anche da pages/servizi_adm_mestieri.inc.php)

/** cod_tipo dedicato alle gilde create dai giocatori — get-or-create, mai il cod_tipo=1 dei mestieri globali */
function tipo_gilda_id() {
    $row = gdrcd_query("SELECT cod_tipo FROM codtipomestiere WHERE descrizione = 'Gilda'");
    if (!$row) {
        gdrcd_query("INSERT INTO codtipomestiere (descrizione) VALUES ('Gilda')");
        $row = gdrcd_query("SELECT cod_tipo FROM codtipomestiere WHERE descrizione = 'Gilda'");
    }
    return (int)$row['cod_tipo'];
}

switch ($op) {

    // ===========================================================================
    // PANNELLO ADMIN — tutti i mestieri, inclusi i globali (tipo = 1)
    // ===========================================================================

    case 'list':
        if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }

        $per_page = (int)$PARAMETERS['settings']['records_per_page'];
        $offset   = max(0, (int)($_GET['offset'] ?? 0)) * $per_page;

        // Mestieri veri (tipo = 1): pochi e fissi, si mostrano tutti in un'unica tabella, senza paginazione
        $result = gdrcd_query(
            "SELECT m.id_mestiere, m.nome, m.visibile, m.tipo, c.descrizione AS tipo_descrizione
             FROM mestiere m LEFT JOIN codtipomestiere c ON m.tipo = c.cod_tipo
             WHERE m.tipo = 1
             ORDER BY m.nome",
            'result'
        );
        $mestieri = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $mestieri[] = $row;
        }
        gdrcd_query($result, 'free');

        // Gilde giocatore (tipo != 1): potenzialmente molte, tabella separata con paginazione propria
        $totale_gilde = (int)gdrcd_query("SELECT COUNT(*) AS n FROM mestiere WHERE tipo != 1")['n'];
        $result = gdrcd_query(
            "SELECT m.id_mestiere, m.nome, m.visibile, m.tipo, c.descrizione AS tipo_descrizione
             FROM mestiere m LEFT JOIN codtipomestiere c ON m.tipo = c.cod_tipo
             WHERE m.tipo != 1
             ORDER BY m.nome LIMIT $offset, $per_page",
            'result'
        );
        $gilde = [];
        while ($row = gdrcd_query($result, 'fetch')) {
            $gilde[] = $row;
        }
        gdrcd_query($result, 'free');

        echo json_encode([
            'success'      => true,
            'mestieri'     => $mestieri,
            'gilde'        => $gilde,
            'totale_gilde' => $totale_gilde,
            'per_page'     => $per_page,
        ]);
        break;

    case 'tipi':
        if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }

        $tipi   = [];
        $result = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipomestiere ORDER BY descrizione", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $tipi[] = $row;
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'tipi' => $tipi]);
        break;

    case 'get':
        if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0 && $id !== -1) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non specificato']);
            exit;
        }

        // id = -1 è il mestiere "virtuale" usato per i ruoli/lavori indipendenti,
        // non ha una riga propria nella tabella mestiere
        if ($id === -1) {
            $mestiere = ['id_mestiere' => -1, 'nome' => 'Lavori indipendenti', 'indipendenti' => true];
        } else {
            $mestiere = carica_mestiere($id);
            if (!$mestiere) {
                echo json_encode(['success' => false, 'message' => 'Mestiere non trovato']);
                exit;
            }
        }

        $tipi   = [];
        $result = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipomestiere ORDER BY descrizione", 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
            $tipi[] = $row;
        }
        gdrcd_query($result, 'free');

        echo json_encode(['success' => true, 'mestiere' => $mestiere, 'ruoli' => carica_ruoli($id), 'tipi' => $tipi]);
        break;

    case 'hide':
        if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }

        $id = (int)($_POST['id_mestiere'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non specificato']);
            exit;
        }
        gdrcd_query("UPDATE mestiere SET visibile=0 WHERE id_mestiere=$id LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Mestiere nascosto.']);
        break;

    // Scioglie fisicamente una gilda giocatore (mai un mestiere globale tipo=1)
    case 'dismetti_gilda':
        if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }

        $id = (int)($_POST['id_mestiere'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Gilda non specificata']);
            exit;
        }
        $mestiere = carica_mestiere($id);
        if (!$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Gilda non trovata']);
            exit;
        }
        if ((int)$mestiere['tipo'] === 1) {
            echo json_encode(['success' => false, 'message' => 'I mestieri globali non possono essere sciolti da qui']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET id_mestiere=0, id_ruolo_mestiere=1 WHERE nome IN (SELECT personaggio FROM clgpersonaggiomestiere WHERE id_ruolo IN (SELECT id_ruolo FROM ruolo_mestiere WHERE mestiere=$id))");
        gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo IN (SELECT id_ruolo FROM ruolo_mestiere WHERE mestiere=$id)");
        gdrcd_query("DELETE FROM ruolo_mestiere WHERE mestiere=$id");
        gdrcd_query("DELETE FROM mestiere WHERE id_mestiere=$id");
        echo json_encode(['success' => true, 'message' => 'Gilda sciolta.']);
        break;

    // Trasferisce il ruolo di capo di una gilda a un altro membro già affiliato
    case 'riassegna_capo':
        if (!$is_admin) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accesso negato']); exit; }

        $id_mestiere = (int)($_POST['mestiere'] ?? 0);
        $nuovo_capo  = gdrcd_filter('in', $_POST['personaggio'] ?? '');
        if ($id_mestiere <= 0 || $nuovo_capo === '') {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }

        $ruolo_capo = gdrcd_query("SELECT id_ruolo FROM ruolo_mestiere WHERE mestiere=$id_mestiere AND capo=1 LIMIT 1");
        if (!$ruolo_capo) {
            echo json_encode(['success' => false, 'message' => 'Questa gilda non ha un ruolo di capo definito']);
            exit;
        }
        $id_ruolo_capo = (int)$ruolo_capo['id_ruolo'];

        $membro = gdrcd_query("SELECT cpm.id_ruolo FROM clgpersonaggiomestiere cpm JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo WHERE cpm.personaggio='$nuovo_capo' AND rm.mestiere=$id_mestiere");
        if (!$membro) {
            echo json_encode(['success' => false, 'message' => 'Il personaggio scelto non è membro di questa gilda']);
            exit;
        }

        // Retrocede l'eventuale vecchio capo (se diverso dal nuovo) al grado più basso disponibile,
        // o lo rimuove dalla gilda se non esiste nessun altro ruolo
        $vecchio_capo = gdrcd_query("SELECT personaggio FROM clgpersonaggiomestiere WHERE id_ruolo=$id_ruolo_capo AND personaggio != '$nuovo_capo' LIMIT 1");
        if ($vecchio_capo) {
            $nome_vecchio = gdrcd_filter('in', $vecchio_capo['personaggio']);
            $ruolo_base   = gdrcd_query("SELECT id_ruolo FROM ruolo_mestiere WHERE mestiere=$id_mestiere AND capo=0 ORDER BY livello_mestiere DESC LIMIT 1");
            if ($ruolo_base) {
                $id_ruolo_base = (int)$ruolo_base['id_ruolo'];
                gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo=$id_ruolo_base WHERE personaggio='$nome_vecchio'");
                gdrcd_query("UPDATE personaggio SET id_ruolo_mestiere=$id_ruolo_base WHERE nome='$nome_vecchio'");
            } else {
                gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE personaggio='$nome_vecchio'");
                gdrcd_query("UPDATE personaggio SET id_mestiere=0, id_ruolo_mestiere=1 WHERE nome='$nome_vecchio'");
            }
        }

        gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo=$id_ruolo_capo WHERE personaggio='$nuovo_capo'");
        gdrcd_query("UPDATE personaggio SET id_mestiere=$id_mestiere, id_ruolo_mestiere=$id_ruolo_capo WHERE nome='$nuovo_capo'");

        echo json_encode(['success' => true, 'message' => 'Capo riassegnato.', 'ruoli' => carica_ruoli($id_mestiere)]);
        break;

    // ===========================================================================
    // AUTO-GESTIONE GILDA GIOCATORE — chiunque sia loggato
    // ===========================================================================

    // La gilda del personaggio loggato, se ne ha una (mestiere.tipo != 1)
    case 'mia_gilda':
        $row = gdrcd_query(
            "SELECT m.id_mestiere, rm.capo
             FROM clgpersonaggiomestiere cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
             JOIN mestiere m ON rm.mestiere = m.id_mestiere
             WHERE cpm.personaggio = '$login' AND m.tipo != 1
             LIMIT 1"
        );

        if ($row) {
            $id = (int)$row['id_mestiere'];
            echo json_encode([
                'success'  => true,
                'ha_gilda' => true,
                'e_capo'   => (bool)$row['capo'],
                'mestiere' => carica_mestiere($id),
                'ruoli'    => carica_ruoli($id),
            ]);
        } else {
            $affiliazioni = (int)gdrcd_query("SELECT COUNT(*) AS n FROM clgpersonaggiomestiere WHERE personaggio = '$login'")['n'];
            $puo_creare   = $affiliazioni < (int)$PARAMETERS['settings']['jobs_limit'];
            echo json_encode(['success' => true, 'ha_gilda' => false, 'puo_creare' => $puo_creare]);
        }
        break;

    // Crea una nuova gilda: il fondatore ne diventa automaticamente il capo
    case 'crea_gilda':
        try {
            $nome = trim($_POST['nome'] ?? '');
            if ($nome === '') {
                echo json_encode(['success' => false, 'message' => 'Il nome è obbligatorio']);
                exit;
            }

            $affiliazioni = (int)gdrcd_query("SELECT COUNT(*) AS n FROM clgpersonaggiomestiere WHERE personaggio = '$login'")['n'];
            if ($affiliazioni >= (int)$PARAMETERS['settings']['jobs_limit']) {
                echo json_encode(['success' => false, 'message' => 'Hai già raggiunto il limite di affiliazioni consentite']);
                exit;
            }

            $nome_esc = gdrcd_filter('in', $nome);
            $esiste   = (int)gdrcd_query("SELECT COUNT(*) AS n FROM mestiere WHERE nome = '$nome_esc'")['n'];
            if ($esiste > 0) {
                echo json_encode(['success' => false, 'message' => 'Esiste già una gilda o un mestiere con questo nome']);
                exit;
            }

            $statuto = gdrcd_filter('in', $_POST['statuto'] ?? '');
            $tipo    = tipo_gilda_id();

            $immagine = saveUploadedImage($_FILES['immagine'] ?? [], 'imgs/mestieri', 'gilda_', null);
            $immagine = $immagine ?? 'standard_mestiere.png';
            $immagine_esc = gdrcd_filter('in', $immagine);

            gdrcd_query("INSERT INTO mestiere (nome, tipo, immagine, url_sito, visibile, statuto) VALUES ('$nome_esc', $tipo, '$immagine_esc', '', 1, '$statuto')");
            $id_mestiere = (int)gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];

            gdrcd_query("INSERT INTO ruolo_mestiere (nome_ruolo, mestiere, immagine, stipendio, capo, livello_mestiere) VALUES ('Capo', $id_mestiere, 'standard_gilda.png', 0, 1, 1)");
            $id_ruolo = (int)gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];

            // conferma_mestiere=1 subito: per le gilde giocatore l'affiliazione è diretta
            // (hire/fondazione), non passa mai dal flusso di conferma dei mestieri globali
            gdrcd_query("INSERT INTO clgpersonaggiomestiere (personaggio, id_ruolo, conferma_mestiere, scadenza) VALUES ('$login', $id_ruolo, 1, NOW())");
            gdrcd_query("UPDATE personaggio SET id_mestiere=$id_mestiere, id_ruolo_mestiere=$id_ruolo WHERE nome='$login'");

            echo json_encode(['success' => true, 'message' => 'Gilda creata.', 'mestiere' => carica_mestiere($id_mestiere), 'ruoli' => carica_ruoli($id_mestiere)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===========================================================================
    // SAVE / SAVE_RUOLO / DELETE_RUOLO — admin (su tutto) o capo (sulla propria gilda)
    // ===========================================================================

    case 'save':
        try {
            $id     = (int)($_POST['id_mestiere'] ?? 0);
            $isEdit = ($id > 0);
            $esistente = $isEdit ? carica_mestiere($id) : null;
            if ($isEdit && !$esistente) {
                echo json_encode(['success' => false, 'message' => 'Mestiere non trovato']);
                exit;
            }

            if (!$is_admin) {
                if (!$isEdit || !mestiere_e_capo_di($login, $id)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                    exit;
                }
                if ((int)$esistente['tipo'] === 1) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'I mestieri globali sono gestiti solo dagli admin']);
                    exit;
                }
            }

            $nome = trim($_POST['nome'] ?? '');
            // Il tipo non è mai scelto dal capo: resta quello esistente. Solo l'admin può cambiarlo.
            $tipo = $is_admin ? (int)($_POST['tipo'] ?? 0) : (int)$esistente['tipo'];

            if ($nome === '' || $tipo <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nome e tipo sono obbligatori']);
                exit;
            }

            $visibile = (isset($_POST['visibile']) && $_POST['visibile'] == '1') ? 1 : 0;
            $url_sito = gdrcd_filter('in', $_POST['url_sito'] ?? '');
            $statuto  = gdrcd_filter('in', $_POST['statuto'] ?? '');
            $nome_esc = gdrcd_filter('in', $nome);

            $immagine = saveUploadedImage($_FILES['immagine'] ?? [], 'imgs/mestieri', 'mestiere_', $esistente['immagine'] ?? null);
            if ($immagine === null) {
                $immagine = $esistente['immagine'] ?? 'standard_mestiere.png';
            }
            $immagine_esc = gdrcd_filter('in', $immagine);

            if ($isEdit) {
                gdrcd_query("UPDATE mestiere SET nome='$nome_esc', tipo=$tipo, visibile=$visibile, immagine='$immagine_esc', url_sito='$url_sito', statuto='$statuto' WHERE id_mestiere=$id LIMIT 1");
            } else {
                gdrcd_query("INSERT INTO mestiere (nome, tipo, immagine, url_sito, visibile, statuto) VALUES ('$nome_esc', $tipo, '$immagine_esc', '$url_sito', $visibile, '$statuto')");
                $id = (int)gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
            }

            echo json_encode(['success' => true, 'message' => 'Mestiere salvato.', 'mestiere' => carica_mestiere($id)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_ruolo':
        try {
            $id_ruolo      = (int)($_POST['id_ruolo'] ?? 0);
            $isEdit        = ($id_ruolo > 0);
            $mestiere_post = (int)($_POST['mestiere'] ?? 0);
            $nome_ruolo    = trim($_POST['nome'] ?? '');

            $esistente = $isEdit ? gdrcd_query("SELECT * FROM ruolo_mestiere WHERE id_ruolo=$id_ruolo") : null;
            if ($isEdit && !$esistente) {
                echo json_encode(['success' => false, 'message' => 'Ruolo non trovato']);
                exit;
            }
            // Il mestiere di riferimento per l'autorizzazione è sempre quello reale, non quanto dichiarato dal client
            $mestiere     = $isEdit ? (int)$esistente['mestiere'] : $mestiere_post;
            $e_ruolo_capo = $isEdit && (int)$esistente['capo'] === 1;

            if (!$is_admin) {
                if (!mestiere_e_capo_di($login, $mestiere)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                    exit;
                }
                $mestiere_row = carica_mestiere($mestiere);
                if (!$mestiere_row || (int)$mestiere_row['tipo'] === 1) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'I mestieri globali sono gestiti solo dagli admin']);
                    exit;
                }
                // un capo non può spostare un ruolo verso un altro mestiere
                $mestiere_post = $mestiere;
                // sul proprio ruolo di comando il capo può cambiare solo l'immagine:
                // nome/livello/stipendio/flag restano quelli esistenti, non modificabili da qui
                if ($e_ruolo_capo) {
                    $nome_ruolo = $esistente['nome_ruolo'];
                }
            }

            // mestiere = -1 è il contenitore "virtuale" dei ruoli/lavori indipendenti (solo admin, sopra)
            if (($mestiere_post <= 0 && $mestiere_post !== -1) || $nome_ruolo === '') {
                echo json_encode(['success' => false, 'message' => 'Nome ruolo e mestiere sono obbligatori']);
                exit;
            }

            if (!$is_admin && $e_ruolo_capo) {
                $livello   = (int)$esistente['livello_mestiere'];
                $stipendio = (int)$esistente['stipendio'];
            } else {
                $livello   = max(1, min(3, (int)($_POST['livello_mestiere'] ?? 3)));
                $stipendio = (int)($_POST['stipendio'] ?? 0);
            }
            $capo = (isset($_POST['capo']) && $_POST['capo'] == '1') ? 1 : 0;
            // un capo non può crearsi un altro ruolo di comando: solo "riassegna capo" (admin) tocca quel flag,
            // ma se sta editando il proprio ruolo di comando il flag capo=1 va preservato
            if (!$is_admin) { $capo = $e_ruolo_capo ? 1 : 0; }
            $nome_esc  = gdrcd_filter('in', $nome_ruolo);

            $immagine = saveUploadedImage($_FILES['immagine'] ?? [], 'imgs/mestieri', 'ruolo_', $esistente['immagine'] ?? null);
            if ($immagine === null) {
                $immagine = $esistente['immagine'] ?? 'standard_gilda.png';
            }
            $immagine_esc = gdrcd_filter('in', $immagine);

            if ($isEdit) {
                gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo='$nome_esc', capo=$capo, immagine='$immagine_esc', mestiere=$mestiere_post, livello_mestiere=$livello, stipendio=$stipendio WHERE id_ruolo=$id_ruolo LIMIT 1");
            } else {
                gdrcd_query("INSERT INTO ruolo_mestiere (nome_ruolo, mestiere, immagine, stipendio, capo, livello_mestiere) VALUES ('$nome_esc', $mestiere_post, '$immagine_esc', $stipendio, $capo, $livello)");
            }

            echo json_encode(['success' => true, 'message' => 'Ruolo salvato.', 'ruoli' => carica_ruoli($mestiere_post)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_ruolo':
        $id_ruolo = (int)($_POST['id_ruolo'] ?? 0);
        if ($id_ruolo <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ruolo non specificato']);
            exit;
        }

        $ruolo = gdrcd_query("SELECT mestiere, capo FROM ruolo_mestiere WHERE id_ruolo=$id_ruolo");
        if (!$ruolo) {
            echo json_encode(['success' => false, 'message' => 'Ruolo non trovato']);
            exit;
        }
        $mestiere = (int)$ruolo['mestiere'];

        if (!$is_admin) {
            if (!mestiere_e_capo_di($login, $mestiere)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accesso negato']);
                exit;
            }
            if ((int)$ruolo['capo'] === 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Il ruolo di comando non può essere eliminato da qui']);
                exit;
            }
            $mestiere_row = carica_mestiere($mestiere);
            if (!$mestiere_row || (int)$mestiere_row['tipo'] === 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'I mestieri globali sono gestiti solo dagli admin']);
                exit;
            }
        }

        gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo=$id_ruolo");
        gdrcd_query("DELETE FROM ruolo_mestiere WHERE id_ruolo=$id_ruolo LIMIT 1");
        echo json_encode(['success' => true, 'message' => 'Ruolo eliminato.', 'ruoli' => carica_ruoli($mestiere)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
