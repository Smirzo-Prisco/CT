<?php
/**
 * 2026_07_22_calendario_migra_dati.php — migra gli eventi dal vecchio
 * calendario (tabelle appuntamenti/BakCalendario) al nuovo schema
 * calendario_eventi/calendario_partecipanti.
 *
 * Una tantum, da eseguire manualmente via SSH DOPO aver applicato
 * 2026_07_22_calendario.sql:
 *   php /var/www/crystaltokyo/gdrcd/migrations/2026_07_22_calendario_migra_dati.php
 *
 * Non e' un cron e non va aggiunto al crontab — un solo run basta, rieseguirlo
 * duplicherebbe gli eventi (nessun controllo di idempotenza, non serve per
 * uno script one-shot).
 *
 * Mappatura titolo (vecchia categoria) -> colore/pubblico:
 *   Giocata personale  -> gold
 *   Giocata di gilda   -> azzurro
 *   Giocata di mestiere-> verde
 *   Quest              -> viola,  pubblico = 1
 *   Evento             -> rosso,  pubblico = 1
 *
 * destinatario (stringa comma-separated) viene splittato in una riga per
 * nome in calendario_partecipanti. BakCalendario (coda SMS transitoria, 2
 * righe) non viene migrata: non e' storico da preservare.
 */

require_once(__DIR__ . '/../includes/required.php');
gdrcd_connect();

const MAPPA_COLORE = [
    'Giocata personale'   => ['colore' => 'gold',    'pubblico' => 0],
    'Giocata di gilda'    => ['colore' => 'azzurro', 'pubblico' => 0],
    'Giocata di mestiere' => ['colore' => 'verde',   'pubblico' => 0],
    'Quest'               => ['colore' => 'viola',   'pubblico' => 1],
    'Evento'              => ['colore' => 'rosso',   'pubblico' => 1],
];

function parseOrario(string $orario): ?string {
    if (preg_match('/^(\d{1,2}):(\d{2})/', trim($orario), $m)) {
        return sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
    }
    return null;
}

$res = gdrcd_query('SELECT * FROM appuntamenti', 'result');

$migrati = 0;
$partecipanti_creati = 0;

while ($row = gdrcd_query($res, 'fetch')) {
    $mappa = MAPPA_COLORE[$row['titolo']] ?? ['colore' => 'gold', 'pubblico' => 0];

    $autore_f = gdrcd_filter('in', $row['autore']);
    $luogo_f  = gdrcd_filter('in', $row['luogo']);
    $nota     = trim($row['testo']);
    $nota_f   = $nota !== '' ? "'" . gdrcd_filter('in', $nota) . "'" : 'NULL';
    $data_sql = date('Y-m-d', (int)$row['str_data']);
    $ora      = parseOrario($row['orario'] ?? '');
    $ora_sql  = $ora !== null ? "'$ora'" : 'NULL';

    gdrcd_query("INSERT INTO calendario_eventi (autore, colore, luogo, data, ora, nota, pubblico)
        VALUES ('$autore_f', '{$mappa['colore']}', '$luogo_f', '$data_sql', $ora_sql, $nota_f, {$mappa['pubblico']})");

    $evento_id = gdrcd_query('SELECT LAST_INSERT_ID() AS id')['id'];
    $migrati++;

    $nomi = array_unique(array_filter(array_map('trim', explode(',', $row['destinatario']))));
    foreach ($nomi as $nome) {
        if ($nome === '' || $nome === $row['autore']) continue;
        $nome_f = gdrcd_filter('in', $nome);
        gdrcd_query("INSERT IGNORE INTO calendario_partecipanti (evento_id, nome) VALUES ($evento_id, '$nome_f')");
        $partecipanti_creati++;
    }
}
gdrcd_query($res, 'free');

echo "Migrazione completata: $migrati eventi, $partecipanti_creati partecipanti creati.\n";
