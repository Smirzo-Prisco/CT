<!--
    mappaclick.inc.php

    Pagina mappa di gioco — caricata da main.php?page=mappaclick.

    Phase 4: il rendering è gestito da MapClick.jsx via AppRouter.
    Questo file fornisce solo il container #ct-app-content su cui
    AppRouter monta il componente, più la pulizia periodica PHP dei
    limiti di lunghezza messaggio scaduti.

    La navigazione verso una stanza (dir=X) fa ancora un reload PHP
    perché aggiorna ultimo_luogo in DB (main.php lines 36-46).
    Il cambio mappa (map_id=X in URL) viene invece gestito da
    MapClick.jsx via api_map.php?op=changemap se in navigazione SPA,
    oppure da main.php direttamente in visita PHP diretta.
-->

<?php
/**
 * Pulizia automatica ogni 6 ore: rimuove i limiti di lunghezza messaggio
 * dalle stanze scaduti, sia quelli imposti dai master che quelli della chat_master.
 */
$limite_role = gdrcd_query(
    "SELECT * FROM mappa WHERE timestamp_modifica_limite IS NOT NULL
     AND timestamp_modifica_limite < DATE_SUB(NOW(), INTERVAL 6 HOUR)",
    'result'
);
while ($row = gdrcd_query($limite_role, 'fetch')) {
    $id = $row['id'];
    gdrcd_query("DELETE FROM scelte_utenti WHERE id_luogo = $id");
    gdrcd_query("UPDATE mappa SET timestamp_modifica_limite = NULL, limite_lunghezza_massima = NULL WHERE id = $id");
}

$limite_master = gdrcd_query(
    "SELECT * FROM chat_master WHERE date_end < DATE_SUB(NOW(), INTERVAL 6 HOUR)",
    'result'
);
while ($rowm = gdrcd_query($limite_master, 'fetch')) {
    gdrcd_query("DELETE FROM chat_master WHERE luogo = " . (int)$rowm['luogo']);
}
?>

<!-- AppRouter legge ?page=mappaclick e renderizza il componente MapClick -->
<div id="ct-app-content"></div>
