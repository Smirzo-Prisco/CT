<?php
/**
 * gestione_account.inc.php — Shim di mount per il pannello React ripristino account
 * Incluso da gestione.php via ?page=gestione_account
 * Accesso riservato allo staff (admin o moderatore): se non autorizzato, redirect alla mappa.
 * Logica applicativa in pages/api_account.php, UI in
 * frontend/src/components/GestioneAccount.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1 && ($_SESSION['moderatore'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-account-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneAccount', 'gestione-account-root', {});
});
</script>
