<?php
/**
 * gestione.inc.php — Phase SPA: la dashboard staff è migrata a Gestione.jsx
 *
 * Il CSS viene iniettato anche da AppRouter (injectCSS) alla navigazione SPA.
 * Il tag <link> qui sotto serve per il caricamento PHP diretto (primo accesso
 * o refresh), così il layout card è disponibile prima che React monti.
 *
 * React monta su #ct-app-content via ct:ready e carica il menu filtrato
 * per permessi tramite api_gestione.php?op=menu.
 *
 * Le sotto-pagine dello staff (gestione_personaggio, gestione_gilde, ecc.)
 * non sono ancora migrate al SPA e causano un reload PHP quando vengono aperte
 * dai link della dashboard.
 */
?>
<link rel="stylesheet" href="/themes/crystal/uffici_nuovi.css">
<div id="ct-app-content"></div>
