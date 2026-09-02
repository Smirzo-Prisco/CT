<?php
/**
 * gestione_luoghi.inc.php — Shim di mount per il pannello React luoghi
 * Incluso da gestione.php via ?page=gestione_luoghi
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 * Logica applicativa in pages/api_luoghi.php, UI in
 * frontend/src/components/GestioneLuoghi.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-luoghi-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneLuoghi', 'gestione-luoghi-root', {});
});
</script>
