<!--
    presenti_estesi.inc.php

    Pagina "Presenti Estesi" — lista completa di tutti i personaggi online,
    raggruppati per mappa e stanza, con avatar, razza, famiglia/inclinazione,
    mestiere e cariche staff.

    Il rendering è completamente delegato al componente React PresentiEstesi.jsx
    (nel bundle Vite), che si aggiorna in tempo reale tramite socket.io ogni volta
    che qualcuno entra o esce da una stanza (evento 'users:update').

    Il componente viene montato via ct:ready su #presenti-estesi-container
    non appena il bundle ct-app.js ha terminato il caricamento.
-->

<div class="pagina_presenti_estesa">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['logged_users']['page_title']); ?></h2>
    </div>

    <!-- Contenitore dove React monterà il componente PresentiEstesi -->
    <div id="presenti-estesi-container"></div>
</div>

<script>
/**
 * Monta il componente React PresentiEstesi su #presenti-estesi-container
 * non appena il bundle Vite ha terminato il caricamento (evento ct:ready).
 *
 * Usando ct:ready invece di DOMContentLoaded si evitano race condition
 * tra il parsing del DOM e il caricamento del modulo ES.
 */
document.addEventListener('ct:ready', function() {
    CT.mount('PresentiEstesi', 'presenti-estesi-container', {});
});
</script>
