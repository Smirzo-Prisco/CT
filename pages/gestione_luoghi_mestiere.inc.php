<?php
/**
 * gestione_luoghi_mestiere.inc.php — Shim di mount per il pannello React
 * associazione mestiere -> luogo
 * Incluso da gestione.php via ?page=gestione_luoghi_mestiere
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 *
 * Logica applicativa in pages/api_mestieri.php (op=list, luoghi, mestiere_set_luogo),
 * UI in frontend/src/components/GestioneLuoghiMestiere.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-luoghi-mestiere-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneLuoghiMestiere', 'gestione-luoghi-mestiere-root', {});
});
</script>
