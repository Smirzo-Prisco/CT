<?php
/**
 * api_calendario.php — API per il calendario (vedi frontend/src/components/Calendario.jsx)
 *
 * Operazioni:
 *   GET  ?op=month&y=&m=          → eventi visibili nel mese, raggruppati per giorno (payload leggero)
 *   GET  ?op=day&data=YYYY-MM-DD  → eventi completi del giorno (nota, partecipanti, editable)
 *   POST ?op=create               → crea un evento
 *   POST ?op=update               → modifica un evento (solo autore o staff)
 *   POST ?op=delete               → elimina un evento (solo autore o staff)
 *   GET  ?op=search_personaggi&q= → autocomplete personaggi per il campo partecipanti
 *
 * Visibilità di un evento: autore, oppure personaggio coinvolto (calendario_partecipanti),
 * oppure evento pubblico (pubblico=1, impostabile solo da staff — vedi isAdminMasterMod()
 * in includes/custom_functions.inc.php).
 */

session_start();
require_once(__DIR__ . '/../config.inc.php');
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/functions.inc.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');

header('Content-Type: application/json');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

const CALENDARIO_COLORI = ['gold', 'azzurro', 'verde', 'rosso', 'arancio', 'viola', 'rosa', 'ciano'];

$login    = $_SESSION['login'];
$login_f  = gdrcd_filter('in', $login);
$is_staff = isAdminMasterMod($_SESSION);

session_write_close();

$op   = $_GET['op'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// -----------------------------------------------------------------------
// Helper: carica e valida un evento esistente per update/delete.
// Ritorna null (e già risposto in JSON) se non trovato o senza permesso.
// -----------------------------------------------------------------------
function loadEventoModificabile(int $id, string $login, bool $is_staff): ?array {
    $evento = gdrcd_query("SELECT * FROM calendario_eventi WHERE id = $id");
    if (!$evento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Evento non trovato']);
        return null;
    }
    if ($evento['autore'] !== $login && !$is_staff) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
        return null;
    }
    return $evento;
}

/** Valida e normalizza i campi comuni a create/update. Ritorna null (già risposto) se invalidi. */
function validaCampiEvento(array $data, bool $is_staff): ?array {
    $colore = $data['colore'] ?? '';
    if (!in_array($colore, CALENDARIO_COLORI, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Colore non valido']);
        return null;
    }

    $giorno = $data['data'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $giorno)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data non valida']);
        return null;
    }

    $ora = null;
    if (!empty($data['ora'])) {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $data['ora'], $m)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ora non valida']);
            return null;
        }
        $ora = "{$m[1]}:{$m[2]}:00";
    }

    $titolo = trim((string)($data['titolo'] ?? ''));
    $luogo  = trim((string)($data['luogo']  ?? ''));
    $nota   = trim((string)($data['nota']   ?? ''));

    $partecipanti = array_values(array_unique(array_filter(array_map(
        fn($n) => trim((string)$n),
        is_array($data['partecipanti'] ?? null) ? $data['partecipanti'] : []
    ))));

    return [
        'colore'       => $colore,
        'data'         => $giorno,
        'ora'          => $ora,
        'titolo'       => $titolo !== '' ? $titolo : null,
        'luogo'        => $luogo !== '' ? $luogo : null,
        'nota'         => $nota !== '' ? $nota : null,
        'pubblico'     => ($is_staff && !empty($data['pubblico'])) ? 1 : 0,
        'partecipanti' => $partecipanti,
    ];
}

/** Sostituisce i partecipanti di un evento — solo nomi che esistono davvero in personaggio. */
function salvaPartecipanti(int $evento_id, array $nomi, string $autore): void {
    gdrcd_query("DELETE FROM calendario_partecipanti WHERE evento_id = $evento_id");
    foreach ($nomi as $nome) {
        if ($nome === '' || $nome === $autore) continue;
        $nome_f = gdrcd_filter('in', $nome);
        $esiste = gdrcd_query("SELECT 1 FROM personaggio WHERE nome = '$nome_f' LIMIT 1");
        if (!$esiste) continue;
        gdrcd_query("INSERT IGNORE INTO calendario_partecipanti (evento_id, nome) VALUES ($evento_id, '$nome_f')");
    }
}

switch ($op) {

    // -------------------------------------------------------------------------
    // MONTH — payload leggero per la griglia mensile (pallini colorati + hover)
    // -------------------------------------------------------------------------
    case 'month':
        $y = (int)($_GET['y'] ?? 0);
        $m = (int)($_GET['m'] ?? 0);
        if ($y < 2000 || $y > 2100 || $m < 1 || $m > 12) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mese non valido']);
            break;
        }

        $res = gdrcd_query("
            SELECT e.id, e.colore, e.ora, e.luogo, e.autore, e.data,
                   (SELECT COUNT(*) FROM calendario_partecipanti p2 WHERE p2.evento_id = e.id) AS n_partecipanti
            FROM calendario_eventi e
            LEFT JOIN calendario_partecipanti p ON p.evento_id = e.id AND p.nome = '$login_f'
            WHERE YEAR(e.data) = $y AND MONTH(e.data) = $m
              AND (e.autore = '$login_f' OR p.nome IS NOT NULL OR e.pubblico = 1)
            GROUP BY e.id
            ORDER BY e.data ASC, e.ora ASC
        ", 'result');

        $giorni = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $giorni[$row['data']][] = [
                'id'             => (int)$row['id'],
                'colore'         => $row['colore'],
                'ora'            => $row['ora'] !== null ? substr($row['ora'], 0, 5) : null,
                'luogo'          => $row['luogo'],
                'autore'         => $row['autore'],
                'n_partecipanti' => (int)$row['n_partecipanti'],
            ];
        }
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'giorni' => $giorni]);
        break;

    // -------------------------------------------------------------------------
    // DAY — dettaglio completo degli eventi di un giorno
    // -------------------------------------------------------------------------
    case 'day':
        $giorno = $_GET['data'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $giorno)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data non valida']);
            break;
        }
        $giorno_f = gdrcd_filter('in', $giorno);

        $res = gdrcd_query("
            SELECT e.*
            FROM calendario_eventi e
            LEFT JOIN calendario_partecipanti p ON p.evento_id = e.id AND p.nome = '$login_f'
            WHERE e.data = '$giorno_f'
              AND (e.autore = '$login_f' OR p.nome IS NOT NULL OR e.pubblico = 1)
            GROUP BY e.id
            ORDER BY e.ora ASC
        ", 'result');

        $eventi = [];
        while ($row = gdrcd_query($res, 'fetch')) {
            $partRes = gdrcd_query("SELECT nome FROM calendario_partecipanti WHERE evento_id = " . (int)$row['id'], 'result');
            $partecipanti = [];
            while ($p = gdrcd_query($partRes, 'fetch')) $partecipanti[] = $p['nome'];
            gdrcd_query($partRes, 'free');

            $eventi[] = [
                'id'           => (int)$row['id'],
                'autore'       => $row['autore'],
                'titolo'       => $row['titolo'],
                'colore'       => $row['colore'],
                'luogo'        => $row['luogo'],
                'data'         => $row['data'],
                'ora'          => $row['ora'] !== null ? substr($row['ora'], 0, 5) : null,
                'nota'         => $row['nota'],
                'pubblico'     => (bool)$row['pubblico'],
                'partecipanti' => $partecipanti,
                'editable'     => $row['autore'] === $login || $is_staff,
            ];
        }
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'eventi' => $eventi]);
        break;

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------
    case 'create':
        $c = validaCampiEvento($data, $is_staff);
        if ($c === null) break;

        // Solo in creazione: non si puo' prenotare un impegno nel passato.
        // Non si applica a update, altrimenti diventerebbe impossibile
        // modificare/aggiungere partecipanti sui 148 eventi storici migrati.
        if ($c['data'] < date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Non puoi creare un impegno nel passato']);
            break;
        }

        $titolo_sql = $c['titolo'] !== null ? "'" . gdrcd_filter('in', $c['titolo']) . "'" : 'NULL';
        $luogo_sql  = $c['luogo']  !== null ? "'" . gdrcd_filter('in', $c['luogo'])  . "'" : 'NULL';
        $nota_sql   = $c['nota']   !== null ? "'" . gdrcd_filter('in', $c['nota'])   . "'" : 'NULL';
        $ora_sql    = $c['ora']    !== null ? "'{$c['ora']}'" : 'NULL';

        gdrcd_query("INSERT INTO calendario_eventi (autore, titolo, colore, luogo, data, ora, nota, pubblico)
            VALUES ('$login_f', $titolo_sql, '{$c['colore']}', $luogo_sql, '{$c['data']}', $ora_sql, $nota_sql, {$c['pubblico']})");

        $evento_id = (int)gdrcd_query('SELECT LAST_INSERT_ID() AS id')['id'];
        salvaPartecipanti($evento_id, $c['partecipanti'], $login);

        echo json_encode(['success' => true, 'id' => $evento_id]);
        break;

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------
    case 'update':
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0 || loadEventoModificabile($id, $login, $is_staff) === null) break;

        $c = validaCampiEvento($data, $is_staff);
        if ($c === null) break;

        $titolo_sql = $c['titolo'] !== null ? "'" . gdrcd_filter('in', $c['titolo']) . "'" : 'NULL';
        $luogo_sql  = $c['luogo']  !== null ? "'" . gdrcd_filter('in', $c['luogo'])  . "'" : 'NULL';
        $nota_sql   = $c['nota']   !== null ? "'" . gdrcd_filter('in', $c['nota'])   . "'" : 'NULL';
        $ora_sql    = $c['ora']    !== null ? "'{$c['ora']}'" : 'NULL';

        gdrcd_query("UPDATE calendario_eventi SET
            titolo = $titolo_sql, colore = '{$c['colore']}', luogo = $luogo_sql,
            data = '{$c['data']}', ora = $ora_sql, nota = $nota_sql, pubblico = {$c['pubblico']}
            WHERE id = $id");

        salvaPartecipanti($id, $c['partecipanti'], $login);

        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // DELETE
    // -------------------------------------------------------------------------
    case 'delete':
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0 || loadEventoModificabile($id, $login, $is_staff) === null) break;

        gdrcd_query("DELETE FROM calendario_partecipanti WHERE evento_id = $id");
        gdrcd_query("DELETE FROM calendario_eventi WHERE id = $id");

        echo json_encode(['success' => true]);
        break;

    // -------------------------------------------------------------------------
    // LUOGHI — popola il select "Luogo" del form (stesso elenco del vecchio calendario)
    // -------------------------------------------------------------------------
    case 'luoghi':
        $res = gdrcd_query("SELECT DISTINCT nome FROM mappa WHERE nome IS NOT NULL AND nome != '' ORDER BY nome ASC", 'result');
        $nomi = [];
        while ($row = gdrcd_query($res, 'fetch')) $nomi[] = $row['nome'];
        gdrcd_query($res, 'free');
        echo json_encode(['success' => true, 'luoghi' => $nomi]);
        break;

    // -------------------------------------------------------------------------
    // SEARCH PERSONAGGI — autocomplete campo partecipanti (stile menzione @ chat)
    // -------------------------------------------------------------------------
    case 'search_personaggi':
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            echo json_encode(['success' => true, 'nomi' => []]);
            break;
        }
        $q_f = gdrcd_filter('in', $q);
        $res = gdrcd_query("SELECT nome FROM personaggio
            WHERE nome LIKE '$q_f%' AND nome != '$login_f'
            ORDER BY nome ASC LIMIT 10", 'result');

        $nomi = [];
        while ($row = gdrcd_query($res, 'fetch')) $nomi[] = $row['nome'];
        gdrcd_query($res, 'free');

        echo json_encode(['success' => true, 'nomi' => $nomi]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
