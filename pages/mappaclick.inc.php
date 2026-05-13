<!--
    mappaclick.inc.php

    Pagina mappa di gioco — caricata da main.php?page=mappaclick.

    Il rendering visivo è delegato al componente React MapClick.jsx
    (bundle Vite) che gestisce:
      - Immagine mappa giorno/notte
      - 9 hotspot zone cliccabili con popup stanze
      - Badge utenti online per stanza (real-time via socket 'users:update')
      - Navigazione verso le stanze via main.php?dir=X

    Rimane in PHP solo la pulizia periodica dei limiti di character scaduti.
-->

<link rel="stylesheet" href="../themes/crystal/mappa_principale.css">

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

<!-- Contenitore dove React monterà il componente MapClick -->
<div id="map-container"></div>

<script>
/**
 * Monta il componente React MapClick su #map-container non appena
 * il bundle Vite ha terminato il caricamento (evento ct:ready).
 */
document.addEventListener('ct:ready', function() {
    CT.mount('MapClick', 'map-container', {});
});
</script>
