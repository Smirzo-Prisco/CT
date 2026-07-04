<?php
/**
 * narrazione_worker.php — Worker in background per la narrazione IA
 *
 * Processo persistente (systemd, unit narrazione-worker.service). Ogni
 * iterazione del loop elabora AL PIÙ UNA giocata — mai un'intera richiesta
 * di rigenerazione in blocco — per poter dare sempre precedenza ai job più
 * urgenti quando le risorse (LLM locale, CPUQuota limitata) scarseggiano:
 *
 *  1. narrazione_queue — riassunto automatico di una giocata appena
 *     conclusa (silenzioso): un solo passo, un solo personaggio coinvolto
 *  2. narrazione_richieste (approvata/in_elaborazione) — rigenerazione
 *     COMPLETA di descrizione: elaborata UNA giocata alla volta, con
 *     progresso salvato in narrazione_richieste_progresso. Priorità più
 *     bassa: avanza di un passo solo se non c'è nulla in (1) da fare,
 *     così un job automatico può sempre intromettersi tra un passo e
 *     l'altro di una rigenerazione lunga (anche ore), invece di aspettarne
 *     la fine.
 *
 * Il progresso persiste su DB, non in memoria: se il worker si riavvia a
 * metà di una rigenerazione, riprende dall'ultima giocata già riassunta.
 * La sovrascrittura finale di descrizione avviene solo quando tutte le
 * giocate di una richiesta sono state elaborate.
 */

define('NARRAZIONE_WORKER', true);
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
require_once(__DIR__ . '/../includes/narrazione_functions.inc.php');
gdrcd_connect();

function narrazione_html_paragrafo(string $testo): string {
    return '<p>' . nl2br(htmlspecialchars(trim($testo), ENT_QUOTES, 'UTF-8')) . '</p>';
}

/** PRIORITÀ 1 — job automatico (una giocata). Ritorna true se ha lavorato. */
function processa_coda_automatica(): bool {
    $row = gdrcd_query("SELECT id, id_role, pg_name FROM narrazione_queue WHERE stato = 'pending' ORDER BY creato_il ASC LIMIT 1");
    if (!$row) return false;

    gdrcd_query("UPDATE narrazione_queue SET stato = 'processing' WHERE id = " . (int)$row['id']);

    $riassunto = narrazione_riassunto_giocata((int)$row['id_role'], $row['pg_name']);
    if ($riassunto === null) {
        gdrcd_query("UPDATE narrazione_queue SET stato = 'error', errore = 'LLM non ha risposto o giocata senza messaggi', elaborato_il = NOW() WHERE id = " . (int)$row['id']);
        return true;
    }

    $pg_esc = gdrcd_filter('in', $row['pg_name']);
    $html   = narrazione_html_paragrafo($riassunto);
    gdrcd_query("UPDATE personaggio SET descrizione = CONCAT(COALESCE(descrizione, ''), '" . gdrcd_filter('in', $html) . "') WHERE nome = '$pg_esc'");
    gdrcd_query("UPDATE narrazione_queue SET stato = 'done', elaborato_il = NOW() WHERE id = " . (int)$row['id']);

    echo date('Y-m-d H:i:s') . " [auto] riassunto accodato per {$row['pg_name']} (id_role={$row['id_role']})\n";
    return true;
}

/** Prossima giocata (in ordine cronologico) non ancora riassunta per questa richiesta. */
function prossima_giocata_da_fare(int $richiesta_id, string $pg_name): ?int {
    $pg_esc = gdrcd_filter('in', $pg_name);
    $row = gdrcd_query("SELECT rs.id_role
        FROM role_sessions rs
        JOIN role_session_players rsp ON rsp.id_role = rs.id_role
        LEFT JOIN narrazione_richieste_progresso p ON p.richiesta_id = $richiesta_id AND p.id_role = rs.id_role
        WHERE rsp.pg_name = '$pg_esc' AND rsp.png = 0 AND rs.end IS NOT NULL AND p.id IS NULL
        ORDER BY rs.start ASC LIMIT 1");
    return $row ? (int)$row['id_role'] : null;
}

/** Concatena il progresso salvato e sovrascrive descrizione — chiamata solo a fine richiesta. */
function finalizza_richiesta_completa(int $richiesta_id, string $pg_name): int {
    $result = gdrcd_query("SELECT p.riassunto FROM narrazione_richieste_progresso p
        JOIN role_sessions rs ON rs.id_role = p.id_role
        WHERE p.richiesta_id = $richiesta_id ORDER BY rs.start ASC", 'result');

    $paragrafi = [];
    while ($r = gdrcd_query($result, 'fetch')) {
        if (trim($r['riassunto']) !== '') $paragrafi[] = narrazione_html_paragrafo($r['riassunto']);
    }
    gdrcd_query($result, 'free');

    $testo_finale = $paragrafi
        ? implode('', $paragrafi)
        : '<p>Nessuna giocata conclusa trovata per generare una narrazione.</p>';

    $pg_esc = gdrcd_filter('in', $pg_name);
    gdrcd_query("UPDATE personaggio SET descrizione = '" . gdrcd_filter('in', $testo_finale) . "' WHERE nome = '$pg_esc'");
    gdrcd_query("UPDATE narrazione_richieste SET stato = 'completata', completato_il = NOW() WHERE id = $richiesta_id");

    return count($paragrafi);
}

/** PRIORITÀ 2 — un solo passo (una giocata) di una rigenerazione completa. Ritorna true se ha lavorato. */
function processa_un_passo_richiesta_completa(): bool {
    $req = gdrcd_query("SELECT id, pg_name FROM narrazione_richieste
        WHERE stato IN ('approvata', 'in_elaborazione') ORDER BY creato_il ASC LIMIT 1");
    if (!$req) return false;

    $richiesta_id = (int)$req['id'];
    $pg_name      = $req['pg_name'];

    gdrcd_query("UPDATE narrazione_richieste SET stato = 'in_elaborazione' WHERE id = $richiesta_id AND stato = 'approvata'");

    $id_role = prossima_giocata_da_fare($richiesta_id, $pg_name);

    if ($id_role === null) {
        $n_giocate = finalizza_richiesta_completa($richiesta_id, $pg_name);
        echo date('Y-m-d H:i:s') . " [rigenerazione] completata per $pg_name ($n_giocate giocate riassunte)\n";
        return true;
    }

    $riassunto     = narrazione_riassunto_giocata($id_role, $pg_name) ?? '';
    $riassunto_esc = gdrcd_filter('in', $riassunto);
    gdrcd_query("INSERT INTO narrazione_richieste_progresso (richiesta_id, id_role, riassunto) VALUES ($richiesta_id, $id_role, '$riassunto_esc')");

    echo date('Y-m-d H:i:s') . " [rigenerazione] passo completato per $pg_name (id_role=$id_role)\n";
    return true;
}

echo date('Y-m-d H:i:s') . " — narrazione_worker avviato\n";

while (true) {
    $ha_lavorato = processa_coda_automatica();
    if (!$ha_lavorato) $ha_lavorato = processa_un_passo_richiesta_completa();

    if (!$ha_lavorato) sleep(5);
}
