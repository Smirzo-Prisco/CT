<?php
/**
 * forum.inc.php
 *
 * Pagina del forum (Araldo) — caricata da main.php?page=forum.
 *
 * Il rendering è completamente delegato al componente React Forum.jsx
 * nel bundle Vite, che gestisce navigazione interna tra:
 *   - Lista sezioni (raggruppate per tipo, con badge non letti)
 *   - Lista thread di una sezione (paginata)
 *   - Lettura thread completo con tutte le risposte
 *   - Composizione nuovo thread
 *   - Form di risposta inline nel thread
 */
?>

<!-- CSS del forum mantenuto per le classi usate dal componente React -->
<link rel="stylesheet" href="themes/crystal/bacheca.css">
<link rel="stylesheet" href="themes/crystal/forum.css">

<div class="page_title">
    <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur']); ?></h2>
</div>

<!-- Contenitore dove React monterà il componente Forum -->
<div id="forum-container"></div>

<script>
/**
 * Monta il componente React Forum su #forum-container non appena
 * il bundle Vite ha terminato il caricamento (evento ct:ready).
 */
document.addEventListener('ct:ready', function() {
    CT.mount('Forum', 'forum-container', {});
});
</script>
