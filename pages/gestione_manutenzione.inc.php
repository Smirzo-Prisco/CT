<?php
/**
 * gestione_manutenzione.inc.php — Shim di mount per il pannello React di manutenzione
 * Incluso da gestione.php via ?page=gestione_manutenzione
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 * Logica applicativa in pages/api_manutenzione.php, UI in
 * frontend/src/components/GestioneManutenzione.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-manutenzione-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneManutenzione', 'gestione-manutenzione-root', {});
});
</script>
