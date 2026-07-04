<?php
/**
 * narrazione_worker.php — Worker in background per la narrazione IA
 *
 * Processo persistente (gestito da systemd, unit narrazione-worker.service),
 * NON un cron classico: interroga in loop due code, una riga alla volta,
 * per non sovraccaricare l'LLM locale (llama.cpp, già limitato via
 * CPUQuota in llama-server.service — vedi memoria "project_local_llm"):
 *
 *  - narrazione_queue: riassunto breve automatico di una giocata appena
 *    conclusa, accodato a personaggio.descrizione (silenzioso)
 *  - narrazione_richieste (stato='approvata'): rigenerazione COMPLETA di
 *    descrizione a partire da tutte le giocate concluse del personaggio
 *    (sovrascrive tutto, richiesta esplicita del giocatore + approvazione
 *    admin — vedi pages/api_scheda.php e pages/api_narrazione_admin.php)
 */

define('NARRAZIONE_WORKER', true);
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
require_once(__DIR__ . '/../includes/narrazione_functions.inc.php');
gdrcd_connect();

function narrazione_html_paragrafo(string $testo): string {
    return '<p>' . nl2br(htmlspecialchars(trim($testo), ENT_QUOTES, 'UTF-8')) . '</p>';
}

/** Processa al più una riga pending di narrazione_queue. Ritorna true se ha lavorato. */
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

/** Processa al più una richiesta approvata di rigenerazione completa. Ritorna true se ha lavorato. */
function processa_richiesta_completa(): bool {
    $req = gdrcd_query("SELECT id, pg_name FROM narrazione_richieste WHERE stato = 'approvata' ORDER BY creato_il ASC LIMIT 1");
    if (!$req) return false;

    gdrcd_query("UPDATE narrazione_richieste SET stato = 'in_elaborazione' WHERE id = " . (int)$req['id']);

    $pg_name = $req['pg_name'];
    $pg_esc  = gdrcd_filter('in', $pg_name);

    // Tutte le giocate concluse a cui ha partecipato, in ordine cronologico
    $giocate = gdrcd_query("SELECT rs.id_role
        FROM role_sessions rs
        JOIN role_session_players rsp ON rsp.id_role = rs.id_role
        WHERE rsp.pg_name = '$pg_esc' AND rsp.png = 0 AND rs.end IS NOT NULL
        ORDER BY rs.start ASC", 'result');

    $paragrafi = [];
    while ($g = gdrcd_query($giocate, 'fetch')) {
        $riassunto = narrazione_riassunto_giocata((int)$g['id_role'], $pg_name);
        if ($riassunto !== null) $paragrafi[] = narrazione_html_paragrafo($riassunto);
    }
    gdrcd_query($giocate, 'free');

    $testo_finale = $paragrafi
        ? implode('', $paragrafi)
        : '<p>Nessuna giocata conclusa trovata per generare una narrazione.</p>';

    gdrcd_query("UPDATE personaggio SET descrizione = '" . gdrcd_filter('in', $testo_finale) . "' WHERE nome = '$pg_esc'");
    gdrcd_query("UPDATE narrazione_richieste SET stato = 'completata', completato_il = NOW() WHERE id = " . (int)$req['id']);

    echo date('Y-m-d H:i:s') . " [rigenerazione] completata per $pg_name (" . count($paragrafi) . " giocate riassunte)\n";
    return true;
}

echo date('Y-m-d H:i:s') . " — narrazione_worker avviato\n";

while (true) {
    $ha_lavorato = processa_coda_automatica();
    $ha_lavorato = processa_richiesta_completa() || $ha_lavorato;

    if (!$ha_lavorato) sleep(5);
}
