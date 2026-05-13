<?php
/**
 * forum.inc.php — contenitore standard per AppRouter (Phase 3.1)
 *
 * Carica i CSS necessari e fornisce il div #ct-app-content su cui
 * AppRouter monterà il componente Forum.
 * Il div è lo stesso per tutte le pagine migrate, così AppRouter può
 * fare navigazione client-side tra forum, messaggi e presenti_estesi
 * senza ricaricare la pagina.
 */
?>

<link rel="stylesheet" href="themes/crystal/bacheca.css">
<link rel="stylesheet" href="themes/crystal/forum.css">
<link rel="stylesheet" href="themes/crystal/presenti.css">

<div id="ct-app-content"></div>
