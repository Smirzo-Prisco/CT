<?php
/**
 * api_account.php — Cancellazione/ripristino account
 *
 * Endpoint:
 *   GET  ?op=getInfo            — email corrente (per CancellaAccount.jsx, autocancellazione)
 *   POST ?op=delete             — auto-cancellazione (verifica email + password): permessi=-1
 *   GET  ?op=getStaffInfo       — permessi staff correnti (per GestioneAccount.jsx)
 *   GET  ?op=getDeletedAccounts — lista account con permessi=-1 (solo staff)
 *   POST ?op=restore            — ripristina un account cancellato: permessi=0 (solo staff)
 *   GET  ?op=getActiveAccounts  — lista account cancellabili forzatamente (solo staff)
 *   POST ?op=forceDelete        — cancella forzatamente un account: permessi=-1 (solo staff)
 *
 * Autorizzazione staff: $_SESSION['admin']/['moderatore'] (flag da privilegi,
 * impostati al login in api_auth.php), NON personaggio.permessi — quest'ultimo
 * e' un asse indipendente (0=attivo/-1=cancellato, vedi DELETED/USER sotto),
 * non piu' usato in nessun altro punto del progetto per il livello di staff
 * (che infatti risultava sempre 0 anche per un vero admin, con la prima
 * versione di questo file: la sezione staff non compariva mai davvero).
 * I moderatori (non admin) non possono toccare account con privilegi.admin=1.
 *
 * Nota sulla verifica password: il vecchio user_cancella_pg.inc.php confrontava
 * `pass = gdrcd_encript($_POST['new_pass'])` in SQL — gdrcd_encript() genera un
 * salt casuale ad ogni chiamata, quindi due hash della stessa password in
 * chiaro non coincidono MAI: quel confronto falliva sempre silenziosamente
 * (0 righe aggiornate, ma nessun controllo sull'esito), quindi l'autocancellazione
 * non ha mai funzionato davvero. Qui si usa gdrcd_password_check() sull'hash
 * gia' salvato, la funzione corretta per verificare una password in chiaro.
 *
 * @author Crystal Tokyo Dev
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
require_once(__DIR__ . '/../includes/constant_values.inc.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$op       = $_GET['op'] ?? '';
$login    = gdrcd_filter('in', $_SESSION['login']);
$isAdmin  = (int)($_SESSION['admin'] ?? 0) === 1;
$isStaff  = $isAdmin || (int)($_SESSION['moderatore'] ?? 0) === 1;

switch ($op) {

    // -------------------------------------------------------------------------
    // getInfo — email corrente (autocancellazione)
    // -------------------------------------------------------------------------
    case 'getInfo':
        $row = gdrcd_query("SELECT email FROM personaggio WHERE nome = '$login'");
        echo json_encode([
            'success' => true,
            'email'   => gdrcd_filter('out', $row['email'] ?? ''),
        ]);
        break;

    // -------------------------------------------------------------------------
    // delete — auto-cancellazione: verifica email + password, poi permessi=-1
    // -------------------------------------------------------------------------
    case 'delete':
        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $emailIn  = gdrcd_filter_email($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($emailIn === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Email e password sono obbligatorie.']);
            exit;
        }

        $row = gdrcd_query("SELECT email, pass FROM personaggio WHERE nome = '$login'");
        if (!$row || $row['email'] !== $emailIn || !gdrcd_password_check($password, $row['pass'])) {
            echo json_encode(['success' => false, 'message' => 'Email o password errate.']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET permessi = " . DELETED . " WHERE nome = '$login'");
        gdrcd_query(
            "INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento)
             VALUES ('$login', '$login', NOW(), " . DELETEPG . ", 'Account cancellato dal proprietario')"
        );

        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Account cancellato.']);
        break;

    // -------------------------------------------------------------------------
    // getStaffInfo — permessi staff correnti (per GestioneAccount.jsx)
    // -------------------------------------------------------------------------
    case 'getStaffInfo':
        if (!$isStaff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }
        echo json_encode(['success' => true, 'isAdmin' => $isAdmin]);
        break;

    // -------------------------------------------------------------------------
    // getDeletedAccounts — account con permessi=-1 (solo staff)
    // -------------------------------------------------------------------------
    case 'getDeletedAccounts':
        if (!$isStaff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $res  = gdrcd_query("SELECT nome FROM personaggio WHERE permessi = " . DELETED . " ORDER BY nome", 'result');
        $list = [];
        while ($row = gdrcd_query($res, 'fetch')) $list[] = gdrcd_filter('out', $row['nome']);
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'accounts' => $list]);
        break;

    // -------------------------------------------------------------------------
    // restore — ripristina un account cancellato: permessi=0 (solo staff)
    // -------------------------------------------------------------------------
    case 'restore':
        if (!$isStaff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $account = gdrcd_filter('in', $data['account'] ?? '');

        if ($account === '') {
            echo json_encode(['success' => false, 'message' => 'Seleziona un account.']);
            exit;
        }

        gdrcd_query("UPDATE personaggio SET permessi = " . USER . " WHERE nome = '$account' AND permessi = " . DELETED);
        gdrcd_query(
            "INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento)
             VALUES ('$account', '$login', NOW(), " . DELETEPG . ", 'Account ripristinato')"
        );

        echo json_encode(['success' => true, 'message' => 'Account ripristinato.']);
        break;

    // -------------------------------------------------------------------------
    // getActiveAccounts — account cancellabili forzatamente (solo staff)
    // I moderatori non possono toccare account con privilegi.admin=1, gli admin si.
    // -------------------------------------------------------------------------
    case 'getActiveAccounts':
        if (!$isStaff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $where = $isAdmin
            ? "WHERE p.permessi > " . DELETED
            : "WHERE p.permessi > " . DELETED . " AND COALESCE(pr.admin, 0) = 0";

        $res  = gdrcd_query(
            "SELECT p.nome FROM personaggio p
             LEFT JOIN privilegi pr ON pr.nome = p.nome
             $where ORDER BY p.nome",
            'result'
        );
        $list = [];
        while ($row = gdrcd_query($res, 'fetch')) $list[] = gdrcd_filter('out', $row['nome']);
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'accounts' => $list]);
        break;

    // -------------------------------------------------------------------------
    // forceDelete — cancella forzatamente un account: permessi=-1 (solo staff)
    // -------------------------------------------------------------------------
    case 'forceDelete':
        if (!$isStaff) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permessi insufficienti']);
            exit;
        }

        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $account = gdrcd_filter('in', $data['account'] ?? '');

        if ($account === '') {
            echo json_encode(['success' => false, 'message' => 'Seleziona un account.']);
            exit;
        }

        if (!$isAdmin) {
            $targetIsAdmin = (bool)gdrcd_query("SELECT admin FROM privilegi WHERE nome = '$account'")['admin'] ?? false;
            if ($targetIsAdmin) {
                echo json_encode(['success' => false, 'message' => 'Non puoi cancellare un account dello staff superiore.']);
                exit;
            }
        }

        gdrcd_query("UPDATE personaggio SET permessi = " . DELETED . " WHERE nome = '$account'");
        gdrcd_query(
            "INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento)
             VALUES ('$account', '$login', NOW(), " . DELETEPG . ", 'Account cancellato da staff')"
        );

        echo json_encode(['success' => true, 'message' => 'Account cancellato.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
