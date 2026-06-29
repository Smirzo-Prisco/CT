<?php
/**
 * bot_heartbeat.php — Aggiornamento presenza bot schedulata
 *
 * Porta online (aggiorna ultimo_refresh) i bot il cui schedule
 * prevede presenza nel giorno e nell'ora correnti.
 * I bot con bot_status.paused = 1 vengono saltati.
 *
 * Aggiungere al crontab del server (ogni 2 minuti):
 *   * /2 * * * * php /var/www/html/cron/bot_heartbeat.php >> /var/log/bot_heartbeat.log 2>&1
 *
 * Convenzione giorni: 0=Lunedì ... 6=Domenica (date('N') - 1)
 */

define('BOT_HEARTBEAT', true);
require_once(__DIR__ . '/../includes/required.php');
gdrcd_connect();

$day  = (int)date('N') - 1; // 0=Lun ... 6=Dom
$time = date('H:i:s');

$active = gdrcd_query(
    "SELECT p.nome
     FROM personaggio p
     JOIN bot_schedule bs ON bs.bot_nome = p.nome
     LEFT JOIN bot_status st ON st.bot_nome = p.nome
     WHERE p.sesso = 'b'
       AND bs.giorno = $day
       AND bs.ora_inizio <= '$time'
       AND bs.ora_fine   >= '$time'
       AND COALESCE(st.paused, 0) = 0",
    'result'
);

// Inizializza ora_uscita per bot che non l'hanno mai avuta (evita NULL in confronti)
gdrcd_query("UPDATE personaggio SET ora_uscita = '2000-01-01 00:00:00' WHERE sesso = 'b' AND ora_uscita IS NULL");

$count = 0;
while ($row = gdrcd_query($active, 'fetch')) {
    $nome = gdrcd_filter('in', $row['nome']);
    gdrcd_query("UPDATE personaggio SET ultimo_refresh = NOW(), ora_entrata = NOW() WHERE nome = '$nome'");
    $count++;
}
gdrcd_query($active, 'free');

echo date('Y-m-d H:i:s') . " — bot heartbeat: $count aggiornati\n";
