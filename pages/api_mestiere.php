<?php
/**
 * api_mestiere.php — Selezione e avanzamento mestiere del personaggio
 *
 * op=getState  — stato corrente (step 2 = scelta, step 3 = avanzamento)
 * op=list      — elenco mestieri veri con conteggio affiliati (step 2, vista elenco)
 * op=detail    — gerarchia/affiliati/statuto di un mestiere (step 2, vista dettaglio)
 * op=change    — sceglie il mestiere e lo conferma subito (step 2 -> step 3)
 * op=levelUp   — avanza di livello nel mestiere confermato (step 3)
 *
 * @author Crystal Tokyo Dev
 */

session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
gdrcd_connect();

if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$login = gdrcd_filter('in', $_SESSION['login']);
$op    = $_GET['op'] ?? '';
session_write_close();

switch ($op) {

    // -------------------------------------------------------------------------
    // getState — stato personaggio + lista mestieri per lo step corrente
    // -------------------------------------------------------------------------
    case 'getState':
        $pg          = gdrcd_query("SELECT esperienza, esperienza_mestiere, id_mestiere, id_ruolo_mestiere FROM personaggio WHERE nome = '$login'");
        $exp         = (int)($pg['esperienza']          ?? 0);
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);

        $conferma       = gdrcd_query("SELECT conferma_mestiere, id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'");
        $hasConferma    = $conferma && (int)$conferma['conferma_mestiere'] >= 1;
        $currentIdRuolo = $conferma ? (int)$conferma['id_ruolo'] : 0;
        $step           = $hasConferma ? 3 : 2;

        if ($step === 2) {
            // Vista elenco/dettaglio spostata su op=list/op=detail (stesso
            // linguaggio visivo e stesse query di servizi_mestieri.inc.php,
            // vedi ScegliMestiere.jsx) — qui serve solo per instradare tra
            // step 2 e step 3, non serve piu' costruire l'elenco mestieri.
            $mestieri = [];
        } else {
            $res = gdrcd_query(
                "SELECT rm.id_ruolo, rm.nome_ruolo, rm.immagine, rm.mestiere, rm.livello_mestiere
                 FROM personaggio p
                 LEFT JOIN ruolo_mestiere rm ON p.id_mestiere = rm.mestiere
                 WHERE p.nome = '$login'
                   AND rm.livello_mestiere > 0
                   AND rm.livello_mestiere < (
                       SELECT livello_mestiere FROM ruolo_mestiere
                       JOIN clgpersonaggiomestiere ON ruolo_mestiere.id_ruolo = clgpersonaggiomestiere.id_ruolo
                       WHERE clgpersonaggiomestiere.personaggio = '$login'
                       LIMIT 1
                   )
                 ORDER BY rm.livello_mestiere",
                'result'
            );
            $mestieri = [];
            while ($row = gdrcd_query($res, 'fetch')) {
                $livello  = (int)$row['livello_mestiere'];
                // Livello 0 (capomestiere) non è accessibile in autonomia: assegnato solo da admin/capomestiere
                $unlocked = ($livello === 2 && $expMestiere >= 10)
                          || ($livello === 1 && $expMestiere >= 55);
                $mestieri[] = [
                    'id'       => (int)$row['id_ruolo'],
                    'nome'     => gdrcd_filter('out', (string)$row['nome_ruolo']),
                    'immagine' => (string)$row['immagine'],
                    'mestiere' => (int)$row['mestiere'],
                    'livello'  => $livello,
                    'unlocked' => $unlocked,
                ];
            }
            gdrcd_query($res, 'free');
        }

        echo json_encode([
            'success'        => true,
            'step'           => $step,
            'esperienza'     => $exp,
            'expMestiere'    => $expMestiere,
            'hasConferma'    => $hasConferma,
            'currentIdRuolo' => $currentIdRuolo,
            'mestieri'       => $mestieri,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    // -------------------------------------------------------------------------
    // list — elenco mestieri veri (tipo=1) con conteggio affiliati confermati.
    // Stessa query/stesso linguaggio visivo (classi .sm-*) della vista elenco
    // di servizi_mestieri.inc.php, qui ristretta ai soli mestieri veri (la'
    // e' invece pensata per le gilde giocatore con solo_gilde=1).
    // -------------------------------------------------------------------------
    case 'list':
        $result = gdrcd_query(
            "SELECT m.id_mestiere, m.nome, m.url_sito, c.descrizione
               FROM mestiere m
               JOIN codtipomestiere c ON m.tipo = c.cod_tipo
              WHERE m.visibile = 1 AND m.tipo = 1
              ORDER BY m.nome",
            'result'
        );
        $mestieri     = [];
        $sezione_desc = null;
        while ($row = gdrcd_query($result, 'fetch')) {
            $sezione_desc = $row['descrizione'];
            $numb = gdrcd_query(
                "SELECT COUNT(*) AS n FROM clgpersonaggiomestiere cpm
                   JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
                  WHERE rm.mestiere = " . (int)$row['id_mestiere'] . " AND cpm.conferma_mestiere = 1"
            );
            $mestieri[] = [
                'id'       => (int)$row['id_mestiere'],
                'nome'     => gdrcd_filter('out', (string)$row['nome']),
                'n'        => (int)($numb['n'] ?? 0),
                'url_sito' => $row['url_sito'] ? gdrcd_filter('out', (string)$row['url_sito']) : null,
            ];
        }
        gdrcd_query($result, 'free');

        echo json_encode([
            'success'      => true,
            'sezione'      => $sezione_desc ? gdrcd_filter('out', (string)$sezione_desc) : 'Mestieri',
            'mestieri'     => $mestieri,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    // -------------------------------------------------------------------------
    // detail — gerarchia, affiliati confermati e statuto di un mestiere vero.
    // Stesse query della vista dettaglio di servizi_mestieri.inc.php; in piu'
    // calcola entry_id_ruolo (il ruolo di livello piu' basso, per il pulsante
    // "Entra nel mestiere" lato client — vedi anche op=change).
    // -------------------------------------------------------------------------
    case 'detail':
        $id_mestiere = (int)($_GET['id_mestiere'] ?? 0);
        if ($id_mestiere <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non specificato']);
            exit;
        }
        $mestiere_row = gdrcd_query("SELECT nome, statuto FROM mestiere WHERE id_mestiere = $id_mestiere AND tipo = 1");
        if (!$mestiere_row) {
            echo json_encode(['success' => false, 'message' => 'Mestiere non trovato']);
            exit;
        }

        $rRuoli = gdrcd_query(
            "SELECT id_ruolo, nome_ruolo, immagine, stipendio, capo, livello_mestiere
               FROM ruolo_mestiere
              WHERE mestiere = $id_mestiere
              ORDER BY stipendio DESC",
            'result'
        );
        $ruoli      = [];
        $entryId    = null;
        $maxLivello = -1;
        while ($r = gdrcd_query($rRuoli, 'fetch')) {
            $ruoli[] = [
                'id_ruolo'   => (int)$r['id_ruolo'],
                'nome_ruolo' => gdrcd_filter('out', (string)$r['nome_ruolo']),
                'immagine'   => $r['immagine'] ? (string)$r['immagine'] : null,
                'capo'       => (int)$r['capo'],
            ];
            // Livello piu' basso = numero piu' alto (rango piu' junior, vedi
            // stesso criterio in op=getState/op=change) — MAX invece di un
            // valore fisso per non escludere in silenzio un mestiere con una
            // profondita' di livelli diversa.
            if ((int)$r['livello_mestiere'] > $maxLivello) {
                $maxLivello = (int)$r['livello_mestiere'];
                $entryId    = (int)$r['id_ruolo'];
            }
        }
        gdrcd_query($rRuoli, 'free');

        $rAff = gdrcd_query(
            "SELECT cpm.personaggio, p.cognome, rm.immagine, rm.nome_ruolo, rm.capo
               FROM clgpersonaggiomestiere cpm
               JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
               JOIN personaggio p ON p.nome = cpm.personaggio
              WHERE rm.mestiere = $id_mestiere
                AND cpm.conferma_mestiere = 1
              ORDER BY rm.capo DESC, rm.stipendio DESC",
            'result'
        );
        $affiliati = [];
        while ($r = gdrcd_query($rAff, 'fetch')) {
            $affiliati[] = [
                'personaggio' => gdrcd_filter('out', (string)$r['personaggio']),
                'cognome'     => gdrcd_filter('out', (string)$r['cognome']),
                'immagine'    => $r['immagine'] ? (string)$r['immagine'] : null,
                'nome_ruolo'  => gdrcd_filter('out', (string)$r['nome_ruolo']),
                'capo'        => (int)$r['capo'],
            ];
        }
        gdrcd_query($rAff, 'free');

        echo json_encode([
            'success'       => true,
            'mestiere'      => ['id' => $id_mestiere, 'nome' => gdrcd_filter('out', (string)$mestiere_row['nome'])],
            'statuto'       => !empty($mestiere_row['statuto']) ? gdrcd_bbcoder(gdrcd_filter('out', $mestiere_row['statuto'])) : null,
            'ruoli'         => $ruoli,
            'affiliati'     => $affiliati,
            'entryIdRuolo'  => $entryId,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    // -------------------------------------------------------------------------
    // change — sceglie il mestiere e lo conferma subito (step 2 -> step 3)
    // -------------------------------------------------------------------------
    // Prima era un doppio passaggio (change poi pick, quest'ultimo bloccato
    // sotto i 10 px esperienza): lasciava per un tempo indefinito una riga in
    // clgpersonaggiomestiere con conferma_mestiere=0, invisibile nelle liste
    // staff che filtrano sulle sole affiliazioni confermate (es.
    // gestione_mestiere.inc.php) ma comunque conteggiata nel limite di
    // affiliazioni — bloccando una nuova assunzione senza modo di vederla per
    // liberarla. Ora la scelta è definitiva ed immediata (stesso avviso
    // "non sarà più possibile cambiarlo" lato client, vedi ScegliMestiere.jsx).
    case 'change':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }

        $existing = gdrcd_query("SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '$login'", 'result');
        if (gdrcd_query($existing, 'num_rows') >= 1) {
            gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo, conferma_mestiere = 1 WHERE personaggio = '$login'");
        } else {
            gdrcd_query("INSERT INTO clgpersonaggiomestiere (id_ruolo, personaggio, conferma_mestiere) VALUES ($idRuolo, '$login', 1)");
        }
        gdrcd_query($existing, 'free');
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'");

        $titolo = gdrcd_filter('in', "Conferma nel mestiere - $login");
        $msg    = gdrcd_filter('in', "Il personaggio [b]{$login}[/b] ha appena confermato il ruolo nel mestiere");

        $araldo = gdrcd_query("SELECT id_araldo FROM araldo WHERE tipo = 6 AND proprietari = $mestiere");
        if ($araldo && !empty($araldo['id_araldo'])) {
            $id  = (int)$araldo['id_araldo'];
            $sql = "INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, titolo, messaggio, autore, data_messaggio, data_ultimo_messaggio, anonimo, giornalista)"
                 . " VALUES ('-1', $id, '$titolo', '$msg', '$login', NOW(), NOW(), 'no', 'no')";
            gdrcd_query($sql);
        }

        echo json_encode(['success' => true, 'message' => 'Mestiere confermato. Esci e rientra per vedere le modifiche.']);
        break;

    // -------------------------------------------------------------------------
    // levelUp — avanza al livello inferiore del mestiere confermato (step 3)
    // -------------------------------------------------------------------------
    case 'levelUp':
        $data     = json_decode(file_get_contents('php://input'), true);
        $idRuolo  = (int)($data['id_record'] ?? 0);
        $mestiere = (int)($data['mestiere']  ?? 0);

        if (!$idRuolo || !$mestiere) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }

        $ruolo = gdrcd_query("SELECT livello_mestiere FROM ruolo_mestiere WHERE id_ruolo = $idRuolo AND mestiere = $mestiere");
        if (!$ruolo) {
            echo json_encode(['success' => false, 'message' => 'Ruolo non valido']);
            exit;
        }
        $livelloRichiesto = (int)$ruolo['livello_mestiere'];

        // Livello 0 (capomestiere) non è accessibile in autonomia: solo admin/capomestiere
        if ($livelloRichiesto === 0) {
            echo json_encode(['success' => false, 'message' => 'Questo livello viene assegnato dal capomestiere']);
            exit;
        }

        $pg          = gdrcd_query("SELECT esperienza_mestiere FROM personaggio WHERE nome = '$login'");
        $expMestiere = (int)($pg['esperienza_mestiere'] ?? 0);
        if ($livelloRichiesto === 2 && $expMestiere < 10) {
            echo json_encode(['success' => false, 'message' => 'Servono almeno 10 punti mestiere per avanzare a questo livello']);
            exit;
        }
        if ($livelloRichiesto === 1 && $expMestiere < 55) {
            echo json_encode(['success' => false, 'message' => 'Servono almeno 55 punti mestiere per avanzare a questo livello']);
            exit;
        }

        gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo = $idRuolo WHERE personaggio = '$login'");
        gdrcd_query("UPDATE personaggio SET id_mestiere = $mestiere, id_ruolo_mestiere = $idRuolo WHERE nome = '$login'");

        echo json_encode(['success' => true, 'message' => 'Livello mestiere aggiornato']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}
