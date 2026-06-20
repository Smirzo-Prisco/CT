<?php
/**
 * api_anagrafe.php — Lista personaggi, statistiche e ricerca
 *
 * op=getStats   — totale personaggi + conteggio per razza (ruolo/famiglia)
 * op=getAll     — tutti i personaggi con livello calcolato, mestiere e razza
 * op=getByLetter — personaggi filtrati per lettera iniziale (legacy)
 *
 * @author Crystal Tokyo Dev
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}
session_write_close();

$op = $_GET['op'] ?? '';

// ── Helpers livello ───────────────────────────────────────────────────────────

/** Carica la tabella soglie livello una volta sola. */
function loadSoglie(): array {
    $res    = gdrcd_query("SELECT livello, soglia FROM gilda_soglie ORDER BY soglia ASC", 'result');
    $soglie = [];
    while ($r = gdrcd_query($res, 'fetch')) $soglie[] = $r;
    gdrcd_query($res, 'free');
    return $soglie;
}

/** Calcola il livello del personaggio dalle soglie precaricate. */
function computeLevel(int $totStats, array $soglie): int {
    $level = 1;
    foreach ($soglie as $row) {
        if ($totStats <= (int)$row['soglia']) return max(1, (int)$row['livello'] - 1);
        $level = (int)$row['livello'];
    }
    return $level;
}

// ── Query condivisa ───────────────────────────────────────────────────────────

/**
 * Esegue la query personaggi con il WHERE dato e costruisce l'array risultato.
 * Il livello viene calcolato in PHP dalle soglie precaricate, evitando N query.
 */
function buildPersonaggiList(string $where, array $soglie): array {
    $res = gdrcd_query(
        "SELECT personaggio.nome, personaggio.cognome, personaggio.ultimo_refresh,
                (COALESCE(personaggio.car2,0)+COALESCE(personaggio.car4,0)+COALESCE(personaggio.car6,0)+COALESCE(personaggio.car8,0)) AS tot_stats,
                gilda.nome AS nome_gilda, ruolo.immagine AS img_gilda,
                ruolo_mestiere.nome_ruolo AS nome_mestiere, ruolo_mestiere.immagine AS img_mestiere,
                razza.sing_m, razza.immagine AS img_razza
         FROM personaggio
         LEFT JOIN ruolo          ON personaggio.id_ruolo_gilda    = ruolo.id_ruolo
         LEFT JOIN gilda           ON ruolo.gilda                   = gilda.id_gilda
         LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo
         LEFT JOIN razza           ON personaggio.id_razza          = razza.id_razza
         WHERE $where
         GROUP BY personaggio.nome
         ORDER BY personaggio.nome ASC",
        'result'
    );

    $list = [];
    while ($row = gdrcd_query($res, 'fetch')) {
        $list[] = [
            'nome'          => $row['nome'],
            'cognome'       => $row['cognome'],
            'ultimoRefresh' => $row['ultimo_refresh'],
            'livello'       => computeLevel((int)$row['tot_stats'], $soglie),
            'gilda'         => ['nome' => $row['nome_gilda']    ?? '', 'img' => $row['img_gilda']    ?? ''],
            'mestiere'      => ['nome' => $row['nome_mestiere'] ?? '', 'img' => $row['img_mestiere'] ?? ''],
            'razza'         => ['nome' => $row['sing_m']        ?? '', 'img' => $row['img_razza']    ?? ''],
        ];
    }
    gdrcd_query($res, 'free');
    return $list;
}

// ── Operazioni ────────────────────────────────────────────────────────────────

switch ($op) {

    // -------------------------------------------------------------------------
    // getStats — totale + conteggio per razza (ruolo/famiglia)
    // -------------------------------------------------------------------------
    case 'getStats':
        $total = (int)(gdrcd_query(
            "SELECT COUNT(*) AS c FROM personaggio WHERE (esilio < NOW() OR esilio IS NULL)"
        )['c'] ?? 0);

        $rRes  = gdrcd_query(
            "SELECT gilda.nome AS nome_gilda, ruolo.immagine, COUNT(*) AS cnt
             FROM personaggio
             LEFT JOIN ruolo  ON personaggio.id_ruolo_gilda = ruolo.id_ruolo
             LEFT JOIN gilda  ON ruolo.gilda                = gilda.id_gilda
             WHERE (personaggio.esilio < NOW() OR personaggio.esilio IS NULL)
             GROUP BY gilda.id_gilda
             ORDER BY cnt DESC",
            'result'
        );
        $razze = [];
        while ($r = gdrcd_query($rRes, 'fetch')) {
            $razze[] = [
                'nome'  => $r['nome_gilda'] ?? '',
                'img'   => $r['immagine'] ?? '',
                'count' => (int)$r['cnt'],
            ];
        }
        gdrcd_query($rRes, 'free');

        echo json_encode(
            ['success' => true, 'total' => $total, 'razze' => $razze],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        break;

    // -------------------------------------------------------------------------
    // getAll — tutti i personaggi (filtro client-side nel componente React)
    // -------------------------------------------------------------------------
    case 'getAll':
        $soglie = loadSoglie();
        $list   = buildPersonaggiList(
            "(personaggio.esilio < NOW() OR personaggio.esilio IS NULL)",
            $soglie
        );
        $out = json_encode(
            ['success' => true, 'personaggi' => $list],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($out === false) $out = json_encode(['success' => false, 'message' => 'Errore codifica: ' . json_last_error_msg()]);
        echo $out;
        break;

    // -------------------------------------------------------------------------
    // getByLetter — personaggi filtrati per lettera iniziale (legacy)
    // -------------------------------------------------------------------------
    case 'getByLetter':
        $letter = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $_GET['letter'] ?? ''), 0, 1));

        if (empty($letter)) {
            echo json_encode(['success' => true, 'personaggi' => []]);
            exit;
        }

        $soglie = loadSoglie();
        $where  = "personaggio.nome LIKE '" . gdrcd_filter('in', $letter) . "%'"
                . " AND (personaggio.esilio < NOW() OR personaggio.esilio IS NULL)";
        $list   = buildPersonaggiList($where, $soglie);

        $out = json_encode(
            ['success' => true, 'personaggi' => $list],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($out === false) $out = json_encode(['success' => false, 'message' => 'Errore codifica: ' . json_last_error_msg()]);
        echo $out;
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
