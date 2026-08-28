<?php
/**
 * gestione_statuti_new.inc.php — Shim di mount per il pannello React statuti mestiere
 * Incluso da gestione.php via ?page=gestione_statuti_new
 * Accesso riservato agli admin: se non admin, redirect alla mappa.
 *
 * Sostituisce la vecchia pagina monolitica (statuti gilda + statuti mestiere,
 * ~390 righe di PHP): la sezione statuti gilda è stata rimossa perché già
 * gestita da gestione.php?page=gestione_gilde (accordion "Statuto" per gilda).
 *
 * Logica applicativa in pages/api_mestieri.php (op=list, statuti_list,
 * statuti_save, statuti_delete), UI in frontend/src/components/GestioneStatuti.jsx
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    $redirect = 'main.php?page=mappaclick&map_id=' . (int)($_SESSION['mappa'] ?? 1);
    echo '<script>window.location.href="' . $redirect . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $redirect . '"></noscript>';
    exit;
}
?>
<div id="gestione-statuti-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneStatuti', 'gestione-statuti-root', {});
});
</script>
