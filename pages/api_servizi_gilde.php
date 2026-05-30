<?php
/**
 * api_servizi_gilde.php — Dati famiglie e mestieri per ServiziGilde.jsx
 *
 * op=getList  — elenco famiglie (con conteggio iscritti) + elenco mestieri
 * op=getGuild — ruoli, affiliati e statuto di una singola famiglia
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

switch ($op) {

    // -------------------------------------------------------------------------
    // getList — famiglie visibili + mestieri visibili, con conteggi affiliati
    // -------------------------------------------------------------------------
    case 'getList':
        // Famiglie (gilde) — un'unica query con COUNT via JOIN
        $resG = gdrcd_query("
            SELECT g.id_gilda, g.nome,
                   COUNT(DISTINCT cpr.personaggio) AS cnt
            FROM gilda g
            LEFT JOIN ruolo             r   ON r.gilda      = g.id_gilda
            LEFT JOIN clgpersonaggioruolo cpr ON cpr.id_ruolo = r.id_ruolo
            WHERE g.visibile = 1 AND g.tipo != 4
            GROUP BY g.id_gilda
            ORDER BY g.tipo, g.id_gilda
        ", 'result');

        $gilde = [];
        while ($row = gdrcd_query($resG, 'fetch')) {
            $gilde[] = [
                'id'    => (int)$row['id_gilda'],
                'nome'  => gdrcd_filter('out', $row['nome']),
                'count' => (int)$row['cnt'],
            ];
        }
        gdrcd_query($resG, 'free');

        // Mestieri — conteggio confermati via JOIN su ruolo_mestiere
        $resM = gdrcd_query("
            SELECT m.id_mestiere, m.nome, m.tipo, m.url_sito,
                   ctm.descrizione AS tipo_desc,
                   COUNT(DISTINCT cpm.personaggio) AS cnt
            FROM mestiere m
            JOIN  codtipomestiere      ctm ON ctm.cod_tipo   = m.tipo
            LEFT JOIN ruolo_mestiere   rm  ON rm.mestiere    = m.id_mestiere
            LEFT JOIN clgpersonaggiomestiere cpm
                   ON cpm.id_ruolo = rm.id_ruolo AND cpm.conferma_mestiere = 1
            WHERE m.visibile = 1
            GROUP BY m.id_mestiere
            ORDER BY m.tipo, m.nome
        ", 'result');

        $mestieri = [];
        while ($row = gdrcd_query($resM, 'fetch')) {
            $mestieri[] = [
                'id'        => (int)$row['id_mestiere'],
                'nome'      => gdrcd_filter('out', $row['nome']),
                'tipo'      => (int)$row['tipo'],
                'tipo_desc' => gdrcd_filter('out', $row['tipo_desc']),
                'url_sito'  => $row['url_sito'] ?? '',
                'count'     => (int)$row['cnt'],
            ];
        }
        gdrcd_query($resM, 'free');

        echo json_encode(
            ['success' => true, 'gilde' => $gilde, 'mestieri' => $mestieri],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        break;

    // -------------------------------------------------------------------------
    // getGuild — ruoli, affiliati e statuto di una famiglia
    // -------------------------------------------------------------------------
    case 'getGuild':
        $idGilda = (int)($_GET['id_gilda'] ?? 0);

        if (!$idGilda) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'id_gilda mancante']);
            exit;
        }

        $gilda = gdrcd_query("SELECT nome, statuto FROM gilda WHERE id_gilda = $idGilda");
        if (!$gilda) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Famiglia non trovata']);
            exit;
        }

        // Ruoli ordinati per livello
        $resR = gdrcd_query("
            SELECT immagine, nome_ruolo, livello
            FROM ruolo
            WHERE gilda = $idGilda
            ORDER BY livello DESC, nome_ruolo DESC
        ", 'result');

        $ruoli = [];
        while ($row = gdrcd_query($resR, 'fetch')) {
            $ruoli[] = [
                'immagine'   => gdrcd_filter('out', $row['immagine']),
                'nome_ruolo' => gdrcd_filter('out', $row['nome_ruolo']),
            ];
        }
        gdrcd_query($resR, 'free');

        // Affiliati — personaggi speciali in cima (hardcoded nel PHP originale)
        $resA = gdrcd_query("
            SELECT cpr.personaggio, cpr.nickname, p.cognome,
                   r.immagine, r.nome_ruolo, r.livello,
                   CASE WHEN p.nome IN ('Acamar','Kirari','Etsuya') THEN 1 ELSE 0 END AS special
            FROM ruolo r
            JOIN clgpersonaggioruolo cpr ON cpr.id_ruolo = r.id_ruolo
            JOIN personaggio         p   ON p.nome       = cpr.personaggio
            WHERE r.gilda = $idGilda
            ORDER BY special DESC, r.livello DESC, r.nome_ruolo DESC
        ", 'result');

        $affiliati = [];
        while ($row = gdrcd_query($resA, 'fetch')) {
            $affiliati[] = [
                'nome'       => gdrcd_filter('out', $row['personaggio']),
                'cognome'    => gdrcd_filter('out', $row['cognome'] ?? ''),
                'nickname'   => $row['nickname'] ? gdrcd_filter('out', $row['nickname']) : null,
                'immagine'   => gdrcd_filter('out', $row['immagine']),
                'nome_ruolo' => gdrcd_filter('out', $row['nome_ruolo']),
                'special'    => (bool)$row['special'],
            ];
        }
        gdrcd_query($resA, 'free');

        // Statuto: BBCode → HTML lato server (come nel PHP originale)
        $statutoHtml = '';
        if (!empty($gilda['statuto'])) {
            $statutoHtml = gdrcd_bbcoder(gdrcd_filter('out', $gilda['statuto']));
        }

        echo json_encode([
            'success'    => true,
            'gilda'      => [
                'id'      => $idGilda,
                'nome'    => gdrcd_filter('out', $gilda['nome']),
                'statuto' => $statutoHtml,
            ],
            'ruoli'      => $ruoli,
            'affiliati'  => $affiliati,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
