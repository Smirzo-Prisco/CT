<?php
/**
 * gestione_bacheche.inc.php — Shim di mount per il pannello React bacheche
 * Incluso da gestione.php via ?page=gestione_bacheche
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 * Logica applicativa in pages/api_bacheche.php, UI in
 * frontend/src/components/GestioneBacheche.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-bacheche-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneBacheche', 'gestione-bacheche-root', {});
});
</script>
