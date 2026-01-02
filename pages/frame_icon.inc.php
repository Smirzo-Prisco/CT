<?php /*Frame del box con il link alle bacheche e ai messaggi - cambiato messaggi.inc.php in menu*/ ?>
<iframe src="pages/menu_icons.inc.php" class="iframe_icon" allowtransparency="true" frameborder="0"
        scrolling="no">
    <p><?php gdrcd_filter_out(print $MESSAGE['errors']['can_t_load_frame']) . ' (http://' . $PARAMETERS['info']['site_url'] . '/pages/messaggi.inc.php'; ?></p>
</iframe>