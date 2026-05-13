<?php
/**
 * messages_center.inc.php
 *
 * Pagina "Messaggi Privati" — caricata da main.php?page=messages_center.
 *
 * In precedenza includeva mex_privati/index.inc.php (PHP server-side)
 * o altre sotto-pagine per lettura/scrittura.
 * Ora tutta la logica è gestita dal componente React MessagesInbox.jsx
 * nel bundle Vite, che si aggiorna in real-time tramite socket 'dm:update'.
 *
 * Il componente gestisce autonomamente:
 *   - Lista conversazioni ON / OFF
 *   - Apertura e lettura thread inline
 *   - Risposta rapida
 *   - Composizione nuovi messaggi
 *
 * Il CSS del vecchio inbox (new_sms.css) viene mantenuto per continuità visiva.
 */
include_once('../header.inc.php');
?>

<!-- CSS del vecchio inbox mantenuto per le classi usate dal componente React -->
<link rel="stylesheet" href="pages/mex_privati/new_sms.css">

<div class="pagina_messages_center">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']); ?></h2>
    </div>
    <div class="page_body">

        <!-- Contenitore dove React monterà il componente MessagesInbox -->
        <div id="messages-inbox-container"></div>

    </div><!-- page_body -->
</div><!-- pagina_messages_center -->

<script>
/**
 * Monta il componente React MessagesInbox su #messages-inbox-container
 * non appena il bundle Vite ha terminato il caricamento (evento ct:ready).
 *
 * Il componente gestisce autonomamente routing interno, fetch API e socket,
 * rendendo superflue le sotto-pagine PHP (msg_new1.inc.php, msg_read.inc.php, ecc.)
 * per il flusso standard di lettura e risposta.
 */
document.addEventListener('ct:ready', function() {
    CT.mount('MessagesInbox', 'messages-inbox-container', {});
});
</script>
