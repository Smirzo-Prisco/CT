<?php
/**
 * uffici.inc.php — Phase SPA: la pagina Uffici è migrata a Uffici.jsx
 *
 * Il CSS viene iniettato da AppRouter (injectCSS) alla navigazione SPA.
 * Il tag <link> serve per il caricamento PHP diretto (primo accesso o refresh).
 *
 * React monta su #ct-app-content via ct:ready e renderizza il componente
 * statico con le tre colonne (Utilità, Strumenti, Potenziamento).
 * I link interni puntano a pagine PHP ancora non migrate e causano reload.
 */
?>
<link rel="stylesheet" href="/themes/crystal/uffici_layout.css">
<div id="ct-app-content"></div>
