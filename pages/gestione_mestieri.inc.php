<?php
/**
 * gestione_mestieri.inc.php — Shim di mount per il pannello React mestieri/ruoli
 * Incluso da gestione.php via ?page=gestione_mestieri
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 * Logica applicativa in pages/api_mestieri.php, UI in
 * frontend/src/components/GestioneMestieri.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-mestieri-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneMestieri', 'gestione-mestieri-root', {});
});
</script>
