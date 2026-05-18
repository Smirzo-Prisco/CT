<?php
/**
 * scheda.inc.php — Phase SPA: la scheda personaggio è migrata a Scheda.jsx
 *
 * Il CSS viene iniettato anche da AppRouter (injectCSS) alla navigazione SPA.
 * I tag <link> qui sotto servono per il caricamento PHP diretto (primo accesso
 * o refresh della pagina), così il layout è disponibile anche prima che React
 * abbia montato il componente.
 *
 * React monta su #ct-app-content via ct:ready, legge ?pg=NomePg dalla URL
 * e carica i dati tramite api_scheda.php?op=profile.
 */
?>
<!-- <link rel="stylesheet" href="/themes/crystal/scheda.css"> -->
<link rel="stylesheet" href="/themes/crystal/scheda_menu.css">
<div id="ct-app-content"></div>
