<?php

// Recupera l'orario attuale
$current_timestamp = time();

// Calcola il timestamp che rappresenta 6 ore fa
$six_hours_ago = strtotime('-6 hours'); // 6 ore * 60 minuti * 60 secondi

$result_expired_limits = gdrcd_query("SELECT id FROM mappa WHERE timestamp_modifica_limite IS NOT NULL AND timestamp_modifica_limite < DATE_SUB(NOW(), INTERVAL 360 MINUTE)");

// Verifica se ci sono risultati
if (gdrcd_query($result_expired_limits, 'num_rows') > 0) {
while ($row = gdrcd_query($result_expired_limits, 'fetch')) {

    $id_mappa = $row['id'];

    // Cancella le righe corrispondenti in scelte_utenti
    gdrcd_query("DELETE FROM scelte_utenti WHERE id_luogo = $id_mappa");

    // Aggiorna i valori in mappa
    gdrcd_query("UPDATE mappa SET timestamp_modifica_limite = NULL, limite_lunghezza_massima = NULL WHERE id = $id_mappa");
}
}

//faccio lo stesso per chat_master

$result_expired_master=gdrcd_query("SELECT * FROM chat_master WHERE date_end < DATE_SUB(NOW(), INTERVAL 6 HOUR)");

// Scansiona i risultati e aggiorna i dati
if (gdrcd_query($result_expired_master, 'num_rows') > 0) {
while ($rowmaster = gdrcd_query($result_expired_master, 'fetch')) {

    $luogo_master = $rowmaster['luogo'];

    // Cancella le righe corrispondenti in scelte_utenti
    gdrcd_query("DELETE FROM chat_master WHERE luogo = $luogo_master");
}
}
?>