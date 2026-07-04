<?php
/**
 * narrazione_test_latino.php — test manuale rapido: rigenera la narrazione
 * di Latino usando solo le ultime N giocate concluse (default 4), per
 * verificare la qualità dei fix (troncamento UTF-8, finish_reason,
 * max_tokens) senza aspettare l'intero backlog del worker.
 *
 * Uso: php cron/narrazione_test_latino.php [N]
 *
 * Non tocca narrazione_queue / narrazione_richieste: accoda in fondo a
 * personaggio.descrizione (dopo un separatore <hr>) il riassunto delle sole
 * ultime N giocate, senza toccare il testo già presente.
 */
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
require_once(__DIR__ . '/../includes/narrazione_functions.inc.php');
gdrcd_connect();

function narrazione_test_html_paragrafo(string $testo): string {
    return '<p>' . nl2br(htmlspecialchars(trim($testo), ENT_QUOTES, 'UTF-8')) . '</p>';
}

$pg_name = 'Latino';
$limite  = isset($argv[1]) ? max(1, (int)$argv[1]) : 4;

$result = gdrcd_query("SELECT rs.id_role FROM role_sessions rs
    JOIN role_session_players rsp ON rsp.id_role = rs.id_role
    WHERE rsp.pg_name = '" . gdrcd_filter('in', $pg_name) . "' AND rsp.png = 0 AND rs.end IS NOT NULL
    ORDER BY rs.start DESC LIMIT $limite", 'result');
$id_roles = [];
while ($row = gdrcd_query($result, 'fetch')) $id_roles[] = (int)$row['id_role'];
gdrcd_query($result, 'free');
$id_roles = array_reverse($id_roles); // ordine cronologico

if (!$id_roles) {
    echo "Nessuna giocata conclusa trovata per $pg_name.\n";
    exit(1);
}

echo "Elaboro " . count($id_roles) . " giocate per $pg_name: " . implode(', ', $id_roles) . "\n\n";

$paragrafi = [];
foreach ($id_roles as $id_role) {
    echo "--- giocata id_role=$id_role ---\n";
    $inizio    = microtime(true);
    $riassunto = narrazione_riassunto_giocata($id_role, $pg_name);
    $durata    = round(microtime(true) - $inizio, 1);
    if ($riassunto === null) {
        echo "[errore: LLM non ha risposto o giocata senza messaggi] ({$durata}s)\n\n";
        continue;
    }
    echo "$riassunto\n({$durata}s)\n\n";
    $paragrafi[] = narrazione_test_html_paragrafo($riassunto);
}

if (!$paragrafi) {
    echo "Nessun riassunto generato, descrizione non modificata.\n";
    exit(1);
}

$pg_esc = gdrcd_filter('in', $pg_name);
$html   = '<hr>' . implode('', $paragrafi);
gdrcd_query("UPDATE personaggio SET descrizione = CONCAT(COALESCE(descrizione, ''), '" . gdrcd_filter('in', $html) . "') WHERE nome = '$pg_esc'");

echo "Fatto: accodati " . count($paragrafi) . " riassunti in coda a personaggio.descrizione di $pg_name (dopo <hr>).\n";
