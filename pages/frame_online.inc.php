<?php
/**
 * frame_online.inc.php — contenitore per il componente React OnlineUsers
 *
 * Phase 3.4: questo file non viene più incluso dal loop di left-right_frames.php.
 * Il container #online-users-container è ora hardcoded direttamente in
 * left-right_frames.php, e il montaggio di OnlineUsers è centralizzato in
 * footer.inc.php via ct:ready.
 *
 * File mantenuto come riferimento, ma non più attivo nel flusso principale.
 */
?>
<div class="pagina_presenti">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['logged_users']['plur']); ?></h2>
    </div>
    <div id="online-users-container"></div>
</div>
