<?php
/**
 * gestione_mappe.inc.php — Shim di mount per il pannello React mappe
 * Incluso da gestione.php via ?page=gestione_mappe
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 * Logica applicativa in pages/api_mappe.php, UI in
 * frontend/src/components/GestioneMappe.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-mappe-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneMappe', 'gestione-mappe-root', {});
});
</script>
