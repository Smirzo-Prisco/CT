<?php
/**
 * gestione_bot.inc.php — Pannello gestione bot (sesso='b')
 * Incluso da gestione.php via ?page=gestione_bot
 * Accesso riservato agli admin.
 */

if (($_SESSION['admin'] ?? 0) != 1) {
    echo '<div class="error">Accesso non consentito.</div>';
    return;
}
?>
<div id="gestione-bot-root"></div>
<script>
document.addEventListener('ct:ready', function () {
    CT.mount('GestioneBot', 'gestione-bot-root', {});
});
</script>
